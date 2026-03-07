const path = require('path');
require('dotenv').config({ path: path.resolve(__dirname, '../../.env') });
const express = require('express');
const http = require('http');
const { Server } = require('socket.io');
const db = require('./db');
const decks = require('./decks');
const { Game } = require('./engine/Game');
const matchmaking = require('./matchmaking');
const { getRoom, setRoom } = require('./rooms');

const app = express();
app.use(express.json());
const server = http.createServer(app);
const io = new Server(server, {
  cors: { origin: process.env.APP_URL || '*', credentials: true },
  path: '/socket.io/'
});

const PORT = parseInt(process.env.GAME_SERVER_PORT || '3001', 10);
const BIND = process.env.BIND_ADDRESS || '127.0.0.1';
let gameIdCounter = 1;
const customRooms = new Map();

async function getElo(userId) {
  if (!userId) return 1000;
  await db.query('INSERT IGNORE INTO leaderboard (user_id) VALUES (?)', [userId]).catch(() => {});
  const rows = await db.query('SELECT elo_rating FROM leaderboard WHERE user_id = ?', [userId]).catch(() => []);
  return (rows[0] && rows[0].elo_rating != null) ? rows[0].elo_rating : 1000;
}

async function insertGameRow(room) {
  var game = room.game;
  var dbType = room.gameType === 'ranked' ? 'ranked' : (room.gameType === 'bot' ? 'bot' : 'casual');
  var p2Id = room.player2UserId || null;
  if (p2Id === 0) p2Id = null;
  try {
    var result = await db.query(
      "INSERT INTO games (player1_id, player2_id, game_type, status, started_at) VALUES (?, ?, ?, 'active', NOW())",
      [room.player1UserId, p2Id, dbType]
    );
    room.dbGameId = (result && result.insertId) ? result.insertId : null;
    console.log('[DB] Game row inserted, dbGameId=' + room.dbGameId);
  } catch (e) {
    console.error('[DB] insertGameRow failed:', e.message);
  }
}

async function updateGameRow(room) {
  if (!room.dbGameId) return;
  var game = room.game;
  var winnerId = game.winnerId || null;
  if (winnerId === 0) winnerId = null;
  try {
    await db.query(
      "UPDATE games SET status = 'finished', winner_id = ?, turn_count = ?, finished_at = NOW() WHERE id = ?",
      [winnerId, game.turnManager.turnCount, room.dbGameId]
    );
  } catch (e) {
    console.error('[DB] updateGameRow failed:', e.message);
  }
}

function calcEloChange(playerElo, opponentElo, won, K) {
  K = K || 32;
  var expected = 1 / (1 + Math.pow(10, (opponentElo - playerElo) / 400));
  var actual = won ? 1 : 0;
  return Math.round(K * (actual - expected));
}

async function processGameEnd(room) {
  if (room.eloProcessed) return null;
  room.eloProcessed = true;

  var game = room.game;
  if (!game || game.status !== 'finished' || !game.winnerId) return null;

  var p1 = game.board.player1;
  var p2 = game.board.player2;
  var isRanked = room.gameType === 'ranked';
  var isBot = room.player2UserId === 0;

  await updateGameRow(room);

  var p1Won = game.winnerId === p1.userId;
  var p2Won = game.winnerId === p2.userId;

  var result = {
    winnerId: game.winnerId,
    p1: { userId: p1.userId, username: p1.username, oldElo: p1.elo, newElo: p1.elo, change: 0 },
    p2: { userId: p2.userId, username: p2.username, oldElo: p2.elo, newElo: p2.elo, change: 0 },
    turns: game.turnManager.turnCount,
    gameType: room.gameType || 'casual'
  };

  if (isRanked && !isBot) {
    var p1Change = calcEloChange(p1.elo, p2.elo, p1Won);
    var p2Change = calcEloChange(p2.elo, p1.elo, p2Won);

    result.p1.change = p1Change;
    result.p1.newElo = p1.elo + p1Change;
    result.p2.change = p2Change;
    result.p2.newElo = p2.elo + p2Change;

    try {
      await db.query(
        'UPDATE leaderboard SET elo_rating = elo_rating + ?, wins = wins + ?, losses = losses + ?, games_played = games_played + 1, streak = IF(? = 1, GREATEST(streak, 0) + 1, LEAST(streak, 0) - 1), best_streak = IF(? = 1, GREATEST(best_streak, GREATEST(streak, 0) + 1), best_streak), last_game_at = NOW() WHERE user_id = ?',
        [p1Change, p1Won ? 1 : 0, p1Won ? 0 : 1, p1Won ? 1 : 0, p1Won ? 1 : 0, p1.userId]
      );
      await db.query(
        'UPDATE leaderboard SET elo_rating = elo_rating + ?, wins = wins + ?, losses = losses + ?, games_played = games_played + 1, streak = IF(? = 1, GREATEST(streak, 0) + 1, LEAST(streak, 0) - 1), best_streak = IF(? = 1, GREATEST(best_streak, GREATEST(streak, 0) + 1), best_streak), last_game_at = NOW() WHERE user_id = ?',
        [p2Change, p2Won ? 1 : 0, p2Won ? 0 : 1, p2Won ? 1 : 0, p2Won ? 1 : 0, p2.userId]
      );
      console.log('[ELO] Ranked game ended: ' + p1.username + ' ' + (p1Won ? 'won' : 'lost') + ' (' + p1Change + ') vs ' + p2.username + ' (' + p2Change + ')');
    } catch (e) {
      console.error('[ELO] DB update failed:', e.message);
    }
  } else if (isBot) {
    try {
      await db.query(
        'UPDATE leaderboard SET games_played = games_played + 1, wins = wins + ?, losses = losses + ?, last_game_at = NOW() WHERE user_id = ?',
        [p1Won ? 1 : 0, p1Won ? 0 : 1, p1.userId]
      );
    } catch (e) { /* ignore bot game stats errors */ }
  } else {
    try {
      await db.query(
        'UPDATE leaderboard SET games_played = games_played + 1, wins = wins + ?, losses = losses + ?, last_game_at = NOW() WHERE user_id = ?',
        [p1Won ? 1 : 0, p1Won ? 0 : 1, p1.userId]
      );
      if (p2.userId) {
        await db.query(
          'UPDATE leaderboard SET games_played = games_played + 1, wins = wins + ?, losses = losses + ?, last_game_at = NOW() WHERE user_id = ?',
          [p2Won ? 1 : 0, p2Won ? 0 : 1, p2.userId]
        );
      }
    } catch (e) { /* ignore casual game stats errors */ }
  }

  return result;
}

function emitGameOver(room, eloResult) {
  if (!eloResult) return;
  var game = room.game;

  var baseData = {
    winnerId: eloResult.winnerId,
    turns: eloResult.turns,
    gameType: eloResult.gameType
  };

  if (room.player1SocketId) {
    var s = io.sockets.sockets.get(room.player1SocketId);
    if (s) {
      s.emit('gameOver', Object.assign({}, baseData, {
        won: eloResult.winnerId === eloResult.p1.userId,
        you: eloResult.p1,
        opponent: { username: eloResult.p2.username, oldElo: eloResult.p2.oldElo, newElo: eloResult.p2.newElo }
      }));
    }
  }
  if (room.player2SocketId) {
    var s = io.sockets.sockets.get(room.player2SocketId);
    if (s) {
      s.emit('gameOver', Object.assign({}, baseData, {
        won: eloResult.winnerId === eloResult.p2.userId,
        you: eloResult.p2,
        opponent: { username: eloResult.p1.username, oldElo: eloResult.p1.oldElo, newElo: eloResult.p1.newElo }
      }));
    }
  }
}

async function checkAndProcessEnd(room) {
  if (room.game && room.game.status === 'finished' && !room.eloProcessed) {
    var result = await processGameEnd(room);
    if (result) emitGameOver(room, result);
  }
}

async function createGameFromMatch(player1, player2, gameType) {
  var [p1Deck, p2Deck, p1Elo, p2Elo] = await Promise.all([
    decks.loadDeckForGame(player1.deckId, player1.userId),
    decks.loadDeckForGame(player2.deckId, player2.userId),
    getElo(player1.userId),
    getElo(player2.userId)
  ]);
  if (!p1Deck || !p2Deck) return null;
  var gameId = gameIdCounter++;
  var game = new Game(gameId, {
    userId: player1.userId,
    username: player1.username || 'Player 1',
    elo: p1Elo,
    leaderCard: p1Deck.leaderCard,
    deckCards: p1Deck.deckCards
  }, {
    userId: player2.userId,
    username: player2.username || 'Player 2',
    elo: p2Elo,
    leaderCard: p2Deck.leaderCard,
    deckCards: p2Deck.deckCards
  });
  var roomData = {
    game,
    gameType: gameType || 'casual',
    player1UserId: player1.userId,
    player2UserId: player2.userId,
    player1SocketId: player1.socketId,
    player2SocketId: player2.socketId,
    eloProcessed: false,
    dbGameId: null
  };
  setRoom(gameId, roomData);
  insertGameRow(roomData);
  return gameId;
}

function resolvePlayerIndex(room, socket) {
  if (room.player1SocketId === socket.id) return 0;
  if (room.player2SocketId === socket.id) return 1;
  return -1;
}

function emitToPlayer(room, pIndex, event, data) {
  var socketId = pIndex === 0 ? room.player1SocketId : room.player2SocketId;
  if (!socketId) return;
  var s = io.sockets.sockets.get(socketId);
  if (s) s.emit(event, data);
}

function emitStateToBoth(room) {
  var g = room.game;
  if (room.player1SocketId) {
    var s = io.sockets.sockets.get(room.player1SocketId);
    if (s) {
      var st = g.getStateForPlayer(0);
      st.playerIndex = 0;
      st.gameType = room.gameType || 'casual';
      s.emit('gameState', st);
    }
  }
  if (room.player2SocketId) {
    var s = io.sockets.sockets.get(room.player2SocketId);
    if (s) {
      var st = g.getStateForPlayer(1);
      st.playerIndex = 1;
      st.gameType = room.gameType || 'casual';
      s.emit('gameState', st);
    }
  }
}

app.get('/health', (req, res) => { res.json({ ok: true }); });

function botPlay(game, socket) {
  var bot = game.board.player2;
  var attacks = [];
  var played = true;
  while (played) {
    played = false;
    for (var i = 0; i < bot.hand.length; i++) {
      var card = bot.hand[i];
      var cost = parseInt(card.card_cost, 10) || 0;
      var type = ((card.card_type || '') + '').toLowerCase();
      if (type.indexOf('event') !== -1 || type.indexOf('leader') !== -1) continue;
      if (cost <= bot.getAvailableDon()) {
        var r = game.playCard(1, i, true);
        if (r.ok) { played = true; break; }
      }
    }
  }
  if (bot.leader && !bot.leader.rested && !bot.leader.summonSick) {
    var r = game.attack(1, 'leader', 0, 'leader', 0);
    if (r.ok) attacks.push({ attackerType: 'leader', attackerIndex: 0, targetType: 'leader', targetIndex: 0, hit: !!r.hit, damage: !!r.damage, ko: !!r.ko, side: 'opp' });
  }
  for (var i = 0; i < bot.characters.length; i++) {
    var c = bot.characters[i];
    if (c && !c.rested && !c.summonSick) {
      var r = game.attack(1, 'character', i, 'leader', 0);
      if (r.ok) attacks.push({ attackerType: 'character', attackerIndex: i, targetType: 'leader', targetIndex: 0, hit: !!r.hit, damage: !!r.damage, ko: !!r.ko, side: 'opp' });
    }
  }
  if (socket && attacks.length > 0) {
    socket.emit('botAttacks', attacks);
  }
  return attacks.length;
}

io.on('connection', (socket) => {
  socket.emit('connected', { message: 'Game server connected' });

  socket.on('findMatch', async (data) => {
    try {
      var userId = data && data.userId;
      var deckId = data && data.deckId;
      var mode = (data && data.mode) || 'casual';
      if (!userId || !deckId) return socket.emit('error', { message: 'Missing userId or deckId' });
      var elo = mode === 'ranked' ? await getElo(userId) : 1000;
      var player = { userId, deckId, username: data.username || 'Player', socketId: socket.id, elo };
      matchmaking.enqueue(mode, player);
      var match = matchmaking.findMatch(mode, player);
      if (!match) return;
      matchmaking.dequeue(mode, userId);
      var gameId = await createGameFromMatch(match.player1, match.player2, mode);
      if (!gameId) return socket.emit('error', { message: 'Failed to load decks' });
      var p1Socket = io.sockets.sockets.get(match.player1.socketId);
      var p2Socket = io.sockets.sockets.get(match.player2.socketId);
      if (p1Socket) p1Socket.emit('gameStart', { gameId });
      if (p2Socket) p2Socket.emit('gameStart', { gameId });
    } catch (e) {
      socket.emit('error', { message: e && e.sqlMessage ? 'Database error' : (e && e.message) || 'Failed to find match' });
    }
  });

  socket.on('vsBot', async (data) => {
    try {
      var userId = data && data.userId;
      var deckId = data && data.deckId;
      var difficulty = (data && data.difficulty) || 'easy';
      if (!userId || !deckId) return socket.emit('error', { message: 'Missing userId or deckId' });
      var [p1Deck, p1Elo] = await Promise.all([
        decks.loadDeckForGame(deckId, userId),
        getElo(userId)
      ]);
      if (!p1Deck) return socket.emit('error', { message: 'Deck not found' });
      var gameId = gameIdCounter++;
      var game = new Game(gameId, {
        userId,
        username: data.username || 'Player',
        elo: p1Elo,
        leaderCard: p1Deck.leaderCard,
        deckCards: p1Deck.deckCards
      }, {
        userId: 0,
        username: 'Bot',
        elo: 1000,
        leaderCard: p1Deck.leaderCard,
        deckCards: [...p1Deck.deckCards]
      });
      var roomData = {
        game,
        gameType: 'bot',
        player1UserId: userId,
        player2UserId: 0,
        player1SocketId: socket.id,
        player2SocketId: null,
        eloProcessed: false,
        dbGameId: null
      };
      setRoom(gameId, roomData);
      insertGameRow(roomData);
      socket.emit('gameStart', { gameId });
    } catch (e) {
      socket.emit('error', { message: e && e.sqlMessage ? 'Database error' : (e && e.message) || 'Failed to start game' });
    }
  });

  socket.on('createCustom', async (data) => {
    var userId = data && data.userId;
    var deckId = data && data.deckId;
    if (!userId || !deckId) return socket.emit('error', { message: 'Missing userId or deckId' });
    var code = Math.random().toString(36).slice(2, 8).toUpperCase();
    customRooms.set(code, { player1: { userId, deckId, username: data.username || 'Player', socketId: socket.id } });
    socket.emit('customRoomCreated', { code });
  });

  socket.on('joinCustom', async (data) => {
    try {
      var code = (data && data.code || '').toUpperCase().trim();
      var userId = data && data.userId;
      var deckId = data && data.deckId;
      if (!code || !userId || !deckId) return socket.emit('error', { message: 'Missing code, userId or deckId' });
      var room = customRooms.get(code);
      if (!room || room.player2) return socket.emit('error', { message: 'Room not found or full' });
      room.player2 = { userId, deckId, username: data.username || 'Player', socketId: socket.id };
      var gameId = await createGameFromMatch(room.player1, room.player2, 'custom');
      customRooms.delete(code);
      if (!gameId) return socket.emit('error', { message: 'Failed to load decks' });
      var p1Socket = io.sockets.sockets.get(room.player1.socketId);
      if (p1Socket) p1Socket.emit('gameStart', { gameId });
      socket.emit('gameStart', { gameId });
    } catch (e) {
      socket.emit('error', { message: e && e.sqlMessage ? 'Database error' : (e && e.message) || 'Failed to join game' });
    }
  });

  socket.on('joinGame', (data) => {
    var gameId = data && data.gameId;
    var userId = data && data.userId;
    console.log('[Server] joinGame gameId=' + gameId + ' userId=' + userId + ' socketId=' + socket.id);
    try {
      var room = getRoom(gameId);
      if (!room || !room.game) {
        console.log('[Server] joinGame: no room for gameId=' + gameId);
        return;
      }

      var pIndex = -1;
      if (room.player1UserId === userId) {
        pIndex = 0;
        room.player1SocketId = socket.id;
      } else if (room.player2UserId === userId) {
        pIndex = 1;
        room.player2SocketId = socket.id;
      } else {
        console.log('[Server] joinGame: userId=' + userId + ' not in this game (p1=' + room.player1UserId + ' p2=' + room.player2UserId + ')');
        socket.emit('error', { message: 'You are not a player in this game' });
        return;
      }

      var state = room.game.getStateForPlayer(pIndex);
      state.playerIndex = pIndex;
      state.gameType = room.gameType || 'casual';
      console.log('[Server] joinGame: player' + (pIndex + 1) + ' connected, phase=' + (state.turn && state.turn.phase));
      socket.emit('gameState', state);
    } catch (e) {
      console.error('[Server] joinGame error', e);
      socket.emit('error', { message: (e && e.message) || 'Failed to load game state' });
    }
  });

  socket.on('playCard', (data) => {
    try {
      var gameId = data && data.gameId;
      var handIndex = typeof (data && data.handIndex) === 'number' ? data.handIndex : -1;
      var room = getRoom(gameId);
      if (!room || !room.game) return;
      var pIndex = resolvePlayerIndex(room, socket);
      if (pIndex < 0) return;
      var result = room.game.playCard(pIndex, handIndex);
      socket.emit('actionResult', result);
      if (result.ok) {
        emitStateToBoth(room);
        checkAndProcessEnd(room);
      }
    } catch (e) {
      socket.emit('error', { message: (e && e.message) || 'Invalid play' });
    }
  });

  socket.on('attack', (data) => {
    try {
      var gameId = data && data.gameId;
      var room = getRoom(gameId);
      if (!room || !room.game) return;
      var pIndex = resolvePlayerIndex(room, socket);
      if (pIndex < 0) return;
      var result = room.game.attack(
        pIndex,
        data.attackerType,
        typeof data.attackerIndex === 'number' ? data.attackerIndex : -1,
        data.targetType,
        typeof data.targetIndex === 'number' ? data.targetIndex : -1
      );
      socket.emit('actionResult', result);
      if (result.ok) {
        socket.emit('attackAnimation', {
          attackerType: data.attackerType,
          attackerIndex: data.attackerIndex || 0,
          targetType: data.targetType,
          targetIndex: data.targetIndex || 0,
          hit: !!result.hit,
          damage: !!result.damage,
          ko: !!result.ko,
          gameOver: !!result.gameOver,
          side: 'my'
        });
        var oppIndex = 1 - pIndex;
        emitToPlayer(room, oppIndex, 'attackAnimation', {
          attackerType: data.attackerType,
          attackerIndex: data.attackerIndex || 0,
          targetType: data.targetType,
          targetIndex: data.targetIndex || 0,
          hit: !!result.hit,
          damage: !!result.damage,
          ko: !!result.ko,
          gameOver: !!result.gameOver,
          side: 'opp'
        });
        setTimeout(function () {
          emitStateToBoth(room);
          if (result.gameOver) checkAndProcessEnd(room);
        }, 600);
      } else {
        emitStateToBoth(room);
      }
    } catch (e) {
      socket.emit('error', { message: (e && e.message) || 'Attack failed' });
    }
  });

  socket.on('attachDon', (data) => {
    try {
      var gameId = data && data.gameId;
      var room = getRoom(gameId);
      if (!room || !room.game) return;
      var pIndex = resolvePlayerIndex(room, socket);
      if (pIndex < 0) return;
      var result = room.game.attachDon(pIndex, data.targetType, data.targetIndex || 0);
      socket.emit('actionResult', result);
      if (result.ok) emitStateToBoth(room);
    } catch (e) {
      socket.emit('error', { message: (e && e.message) || 'Invalid action' });
    }
  });

  socket.on('endTurn', (data) => {
    var gameId = data && data.gameId;
    try {
      var room = getRoom(gameId);
      if (!room || !room.game) return;
      var pIndex = resolvePlayerIndex(room, socket);
      if (pIndex < 0) return;

      if (room.game.turnManager.currentPlayerIndex !== pIndex) {
        socket.emit('actionResult', { ok: false, reason: 'Not your turn' });
        return;
      }

      room.game.endTurn();

      if (room.game.status === 'finished') {
        emitStateToBoth(room);
        checkAndProcessEnd(room);
        return;
      }

      if (room.player2SocketId === null && room.game.turnManager.currentPlayerIndex === 1) {
        var botAtkCount = botPlay(room.game, socket);
        room.game.endTurn();
        var delay = botAtkCount > 0 ? (botAtkCount * 700 + 400) : 0;
        if (delay > 0) {
          setTimeout(function () {
            emitStateToBoth(room);
            if (room.game.status === 'finished') checkAndProcessEnd(room);
          }, delay);
          return;
        }
      }

      emitStateToBoth(room);
      if (room.game.status === 'finished') checkAndProcessEnd(room);
    } catch (e) {
      socket.emit('error', { message: (e && e.message) || 'Invalid action' });
    }
  });
});

server.listen(PORT, BIND, () => {
  console.log('Game server listening on ' + BIND + ':' + PORT);
});
