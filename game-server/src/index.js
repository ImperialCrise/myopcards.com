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
  var dbType = room.gameType === 'ranked' ? 'ranked' : (room.gameType === 'bot' ? 'bot' : 'casual');
  var p2Id = room.player2UserId || null;
  if (p2Id === 0) p2Id = null;
  try {
    var result = await db.query(
      "INSERT INTO games (player1_id, player2_id, game_type, status, started_at) VALUES (?, ?, ?, 'active', NOW())",
      [room.player1UserId, p2Id, dbType]
    );
    room.dbGameId = (result && result.insertId) ? result.insertId : null;
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
    } catch (e) { console.error('[ELO] DB update failed:', e.message); }
  } else {
    try {
      await db.query(
        'UPDATE leaderboard SET games_played = games_played + 1, wins = wins + ?, losses = losses + ?, last_game_at = NOW() WHERE user_id = ?',
        [p1Won ? 1 : 0, p1Won ? 0 : 1, p1.userId]
      );
      if (!isBot && p2.userId) {
        await db.query(
          'UPDATE leaderboard SET games_played = games_played + 1, wins = wins + ?, losses = losses + ?, last_game_at = NOW() WHERE user_id = ?',
          [p2Won ? 1 : 0, p2Won ? 0 : 1, p2.userId]
        );
      }
    } catch (e) { /* ignore */ }
  }

  return result;
}

function emitGameOver(room, eloResult) {
  if (!eloResult) return;
  var baseData = { winnerId: eloResult.winnerId, turns: eloResult.turns, gameType: eloResult.gameType };
  if (room.player1SocketId) {
    var s = io.sockets.sockets.get(room.player1SocketId);
    if (s) s.emit('gameOver', Object.assign({}, baseData, {
      won: eloResult.winnerId === eloResult.p1.userId,
      you: eloResult.p1,
      opponent: { username: eloResult.p2.username, oldElo: eloResult.p2.oldElo, newElo: eloResult.p2.newElo }
    }));
  }
  if (room.player2SocketId) {
    var s = io.sockets.sockets.get(room.player2SocketId);
    if (s) s.emit('gameOver', Object.assign({}, baseData, {
      won: eloResult.winnerId === eloResult.p2.userId,
      you: eloResult.p2,
      opponent: { username: eloResult.p1.username, oldElo: eloResult.p1.oldElo, newElo: eloResult.p1.newElo }
    }));
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
    userId: player1.userId, username: player1.username || 'Player 1', elo: p1Elo,
    leaderCard: p1Deck.leaderCard, deckCards: p1Deck.deckCards
  }, {
    userId: player2.userId, username: player2.username || 'Player 2', elo: p2Elo,
    leaderCard: p2Deck.leaderCard, deckCards: p2Deck.deckCards
  });
  var roomData = {
    game, gameType: gameType || 'casual',
    player1UserId: player1.userId, player2UserId: player2.userId,
    player1SocketId: player1.socketId, player2SocketId: player2.socketId,
    eloProcessed: false, dbGameId: null, botAttackQueue: null
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

function isDefenderBot(room, defenderPlayerIndex) {
  return room.player2SocketId === null && defenderPlayerIndex === 1;
}

function botDefend(game) {
  if (!game.pendingCombat) return;
  var c = game.pendingCombat;
  var defIdx = c.defenderPlayerIndex;
  var defender = game.board.getPlayer(defIdx);

  if (c.currentTargetType === 'leader' && !c.blockerUsed) {
    var blockers = defender.getBlockerCharacters();
    if (blockers.length > 0) {
      var atkPower = c.attackerPower;
      var bestBlocker = null;
      for (var b of blockers) {
        var bPower = game.getEffectivePower(defender.characters[b.charIndex]);
        if (bPower >= atkPower && (!bestBlocker || bPower < bestBlocker.power)) {
          bestBlocker = { idx: b.charIndex, power: bPower };
        }
      }
      if (!bestBlocker && defender.life.length <= 1) {
        bestBlocker = { idx: blockers[0].charIndex };
      }
      if (bestBlocker) {
        game.useBlocker(defIdx, bestBlocker.idx);
      }
    }
  }

  var atkPower = c.attackerPower;
  var currentDefender;
  if (c.blockerUsed && c.blockerCharIndex >= 0) {
    currentDefender = defender.characters[c.blockerCharIndex];
  } else if (c.currentTargetType === 'leader') {
    currentDefender = defender.leader;
  } else {
    currentDefender = defender.characters[c.currentTargetIndex];
  }
  var defPower = game.getEffectivePower(currentDefender) + c.counterBoost;

  if (atkPower >= defPower) {
    var counters = defender.getCounterCardsInHand();
    var needed = atkPower - defPower + 1;
    var totalAvailable = 0;
    for (var ct of counters) totalAvailable += ct.counterValue;

    if (totalAvailable >= needed) {
      counters.sort(function (a, b) { return a.counterValue - b.counterValue; });
      var remaining = needed;
      var toPlay = [];
      for (var ct of counters) {
        if (remaining <= 0) break;
        toPlay.push(ct.handIndex);
        remaining -= ct.counterValue;
      }
      toPlay.sort(function (a, b) { return b - a; });
      for (var idx of toPlay) {
        game.playCounter(defIdx, idx);
      }
    }
  }
}

function botPlayCards(game) {
  var bot = game.board.player2;
  var played = true;
  while (played) {
    played = false;
    var best = -1;
    var bestCost = -1;
    for (var i = 0; i < bot.hand.length; i++) {
      var card = bot.hand[i];
      var cost = parseInt(card.card_cost, 10) || 0;
      var type = ((card.card_type || '') + '').toLowerCase();
      if (type.indexOf('event') !== -1 || type.indexOf('leader') !== -1) continue;
      if (cost <= bot.getAvailableDon() && cost > bestCost) {
        best = i;
        bestCost = cost;
      }
    }
    if (best >= 0) {
      var r = game.playCard(1, best, true);
      if (r.ok) played = true;
    }
  }
}

function botAttachDon(game) {
  var bot = game.board.player2;
  var available = bot.getAvailableDon();
  if (available <= 0) return;

  var targets = [];
  for (var i = 0; i < bot.characters.length; i++) {
    var c = bot.characters[i];
    if (!c.rested && !c.summonSick) {
      targets.push({ type: 'character', index: i, power: parseInt(c.card_power, 10) || 0 });
    }
  }
  if (bot.leader && !bot.leader.rested) {
    targets.push({ type: 'leader', index: 0, power: parseInt(bot.leader.card_power, 10) || 0 });
  }

  targets.sort(function (a, b) { return b.power - a.power; });

  for (var t of targets) {
    if (bot.getAvailableDon() <= 0) break;
    game.attachDon(1, t.type, t.index);
  }
}

function botPlanAttacks(game) {
  var bot = game.board.player2;
  var opp = game.board.player1;
  var attacks = [];

  if (bot.leader && !bot.leader.rested && !bot.leader.summonSick) {
    attacks.push({ type: 'leader', index: 0, targetType: 'leader', targetIndex: 0 });
  }

  for (var i = 0; i < bot.characters.length; i++) {
    var c = bot.characters[i];
    if (c && !c.rested && !c.summonSick) {
      var restedTarget = -1;
      for (var j = 0; j < opp.characters.length; j++) {
        if (opp.characters[j].rested) {
          var charPower = game.getEffectivePower(c);
          var tgtPower = game.getEffectivePower(opp.characters[j]);
          if (charPower >= tgtPower) {
            restedTarget = j;
            break;
          }
        }
      }

      if (restedTarget >= 0) {
        attacks.push({ type: 'character', index: i, targetType: 'character', targetIndex: restedTarget });
      } else {
        attacks.push({ type: 'character', index: i, targetType: 'leader', targetIndex: 0 });
      }
    }
  }

  return attacks;
}

function emitAttackAnimation(room, combat, attackerPlayerIndex) {
  var side0 = attackerPlayerIndex === 0 ? 'my' : 'opp';
  var side1 = attackerPlayerIndex === 0 ? 'opp' : 'my';
  var base = {
    attackerType: combat.attackerType || 'leader',
    attackerIndex: combat.attackerIndex || 0,
    targetType: combat.targetType || 'leader',
    targetIndex: combat.targetIndex || 0,
    hit: !!combat.hit,
    damage: !!combat.damage,
    ko: !!combat.ko,
    gameOver: !!combat.gameOver,
    blockerUsed: !!combat.blockerUsed
  };
  emitToPlayer(room, 0, 'attackAnimation', Object.assign({}, base, { side: side0 }));
  emitToPlayer(room, 1, 'attackAnimation', Object.assign({}, base, { side: side1 }));
}

function processNextBotAttack(room, humanSocket) {
  if (!room.botAttackQueue || room.botAttackQueue.length === 0) {
    room.botAttackQueue = null;
    room.game.endTurn();
    emitStateToBoth(room);
    if (room.game.status === 'finished') checkAndProcessEnd(room);
    return;
  }

  var atk = room.botAttackQueue.shift();
  var game = room.game;

  var declareResult = game.declareAttack(1, atk.type, atk.index, atk.targetType, atk.targetIndex);
  if (!declareResult.ok) {
    processNextBotAttack(room, humanSocket);
    return;
  }

  var humanDefender = game.board.player1;
  var blockers = humanDefender.getBlockerCharacters();
  var counters = humanDefender.getCounterCardsInHand();
  var hasDefense = blockers.length > 0 || counters.length > 0;

  if (!hasDefense || game.pendingCombat.currentTargetType === 'character') {
    var result = game.resolveCombat();
    emitAttackAnimation(room, result, 1);
    setTimeout(function () {
      emitStateToBoth(room);
      if (result.gameOver) {
        checkAndProcessEnd(room);
      } else {
        processNextBotAttack(room, humanSocket);
      }
    }, 700);
  } else {
    room.botAttacking = true;
    emitStateToBoth(room);
  }
}

app.get('/health', function (req, res) { res.json({ ok: true }); });

var pendingMatches = new Map();
var pendingMatchCounter = 1;

function createPendingMatch(player1, player2, mode) {
  var matchId = 'M' + (pendingMatchCounter++);
  var pm = {
    matchId: matchId,
    mode: mode,
    player1: player1,
    player2: player2,
    p1Accepted: false,
    p2Accepted: false,
    timer: null
  };

  pm.timer = setTimeout(function () {
    var m = pendingMatches.get(matchId);
    if (!m) return;
    pendingMatches.delete(matchId);
    var s1 = io.sockets.sockets.get(m.player1.socketId);
    var s2 = io.sockets.sockets.get(m.player2.socketId);
    if (s1) s1.emit('matchDeclined', { reason: 'timeout' });
    if (s2) s2.emit('matchDeclined', { reason: 'timeout' });
  }, 32000);

  pendingMatches.set(matchId, pm);
  return pm;
}

async function finalizePendingMatch(pm) {
  if (pm.timer) clearTimeout(pm.timer);
  pendingMatches.delete(pm.matchId);

  var gameId = await createGameFromMatch(pm.player1, pm.player2, pm.mode);
  if (!gameId) {
    var s1 = io.sockets.sockets.get(pm.player1.socketId);
    var s2 = io.sockets.sockets.get(pm.player2.socketId);
    if (s1) s1.emit('error', { message: 'Failed to load decks' });
    if (s2) s2.emit('error', { message: 'Failed to load decks' });
    return;
  }
  var s1 = io.sockets.sockets.get(pm.player1.socketId);
  var s2 = io.sockets.sockets.get(pm.player2.socketId);
  if (s1) s1.emit('gameStart', { gameId: gameId });
  if (s2) s2.emit('gameStart', { gameId: gameId });
}

io.on('connection', function (socket) {
  socket.emit('connected', { message: 'Game server connected' });

  socket.on('findMatch', async function (data) {
    try {
      var userId = data && data.userId;
      var deckId = data && data.deckId;
      var mode = (data && data.mode) || 'casual';
      if (!userId || !deckId) return socket.emit('error', { message: 'Missing userId or deckId' });
      var elo = mode === 'ranked' ? await getElo(userId) : 1000;
      var player = { userId: userId, deckId: deckId, username: data.username || 'Player', socketId: socket.id, elo: elo };
      matchmaking.enqueue(mode, player);
      var match = matchmaking.findMatch(mode, player);
      if (!match) return;
      matchmaking.dequeue(mode, userId);

      var pm = createPendingMatch(match.player1, match.player2, mode);

      var s1 = io.sockets.sockets.get(match.player1.socketId);
      var s2 = io.sockets.sockets.get(match.player2.socketId);
      if (s1) s1.emit('matchReady', {
        matchId: pm.matchId,
        mode: mode,
        opponentName: match.player2.username,
        opponentElo: match.player2.elo || 1000
      });
      if (s2) s2.emit('matchReady', {
        matchId: pm.matchId,
        mode: mode,
        opponentName: match.player1.username,
        opponentElo: match.player1.elo || 1000
      });
    } catch (e) {
      socket.emit('error', { message: e && e.sqlMessage ? 'Database error' : (e && e.message) || 'Failed to find match' });
    }
  });

  socket.on('cancelQueue', function (data) {
    var userId = data && data.userId;
    var mode = (data && data.mode) || 'casual';
    if (userId) {
      matchmaking.dequeue('ranked', userId);
      matchmaking.dequeue('casual', userId);
    }
  });

  socket.on('acceptMatch', function (data) {
    var matchId = data && data.matchId;
    var pm = pendingMatches.get(matchId);
    if (!pm) return;

    if (pm.player1.socketId === socket.id) pm.p1Accepted = true;
    else if (pm.player2.socketId === socket.id) pm.p2Accepted = true;
    else return;

    if (pm.p1Accepted && pm.p2Accepted) {
      var s1 = io.sockets.sockets.get(pm.player1.socketId);
      var s2 = io.sockets.sockets.get(pm.player2.socketId);
      if (s1) s1.emit('matchAccepted', { who: 'both' });
      if (s2) s2.emit('matchAccepted', { who: 'both' });
      setTimeout(function () { finalizePendingMatch(pm); }, 1500);
    } else {
      var otherSocketId = pm.player1.socketId === socket.id ? pm.player2.socketId : pm.player1.socketId;
      var otherSocket = io.sockets.sockets.get(otherSocketId);
      if (otherSocket) otherSocket.emit('matchAccepted', { who: 'opponent' });
    }
  });

  socket.on('declineMatch', function (data) {
    var matchId = data && data.matchId;
    var pm = pendingMatches.get(matchId);
    if (!pm) return;
    if (pm.timer) clearTimeout(pm.timer);
    pendingMatches.delete(matchId);

    var s1 = io.sockets.sockets.get(pm.player1.socketId);
    var s2 = io.sockets.sockets.get(pm.player2.socketId);
    if (s1) s1.emit('matchDeclined', { reason: 'declined' });
    if (s2) s2.emit('matchDeclined', { reason: 'declined' });
  });

  socket.on('vsBot', async function (data) {
    try {
      var userId = data && data.userId;
      var deckId = data && data.deckId;
      if (!userId || !deckId) return socket.emit('error', { message: 'Missing userId or deckId' });
      var [p1Deck, p1Elo] = await Promise.all([
        decks.loadDeckForGame(deckId, userId),
        getElo(userId)
      ]);
      if (!p1Deck) return socket.emit('error', { message: 'Deck not found' });
      var gameId = gameIdCounter++;
      var game = new Game(gameId, {
        userId: userId, username: data.username || 'Player', elo: p1Elo,
        leaderCard: p1Deck.leaderCard, deckCards: p1Deck.deckCards
      }, {
        userId: 0, username: 'Bot', elo: 1000,
        leaderCard: p1Deck.leaderCard, deckCards: [...p1Deck.deckCards]
      });
      var roomData = {
        game: game, gameType: 'bot',
        player1UserId: userId, player2UserId: 0,
        player1SocketId: socket.id, player2SocketId: null,
        eloProcessed: false, dbGameId: null, botAttackQueue: null, botAttacking: false
      };
      setRoom(gameId, roomData);
      insertGameRow(roomData);
      socket.emit('gameStart', { gameId: gameId });
    } catch (e) {
      socket.emit('error', { message: e && e.sqlMessage ? 'Database error' : (e && e.message) || 'Failed to start game' });
    }
  });

  socket.on('createCustom', async function (data) {
    var userId = data && data.userId;
    var deckId = data && data.deckId;
    if (!userId || !deckId) return socket.emit('error', { message: 'Missing userId or deckId' });
    var code = Math.random().toString(36).slice(2, 8).toUpperCase();
    customRooms.set(code, { player1: { userId: userId, deckId: deckId, username: data.username || 'Player', socketId: socket.id } });
    socket.emit('customRoomCreated', { code: code });
  });

  socket.on('joinCustom', async function (data) {
    try {
      var code = (data && data.code || '').toUpperCase().trim();
      var userId = data && data.userId;
      var deckId = data && data.deckId;
      if (!code || !userId || !deckId) return socket.emit('error', { message: 'Missing code, userId or deckId' });
      var room = customRooms.get(code);
      if (!room || room.player2) return socket.emit('error', { message: 'Room not found or full' });
      room.player2 = { userId: userId, deckId: deckId, username: data.username || 'Player', socketId: socket.id };
      customRooms.delete(code);

      var p1Elo = await getElo(room.player1.userId);
      var p2Elo = await getElo(userId);
      var pm = createPendingMatch(room.player1, room.player2, 'custom');

      var s1 = io.sockets.sockets.get(room.player1.socketId);
      if (s1) s1.emit('matchReady', {
        matchId: pm.matchId,
        mode: 'custom',
        opponentName: data.username || 'Player',
        opponentElo: p2Elo || 1000
      });
      socket.emit('matchReady', {
        matchId: pm.matchId,
        mode: 'custom',
        opponentName: room.player1.username || 'Player',
        opponentElo: p1Elo || 1000
      });
    } catch (e) {
      socket.emit('error', { message: e && e.sqlMessage ? 'Database error' : (e && e.message) || 'Failed to join game' });
    }
  });

  socket.on('joinGame', function (data) {
    var gameId = data && data.gameId;
    var userId = data && data.userId;
    try {
      var room = getRoom(gameId);
      if (!room || !room.game) return;

      var pIndex = -1;
      if (room.player1UserId === userId) { pIndex = 0; room.player1SocketId = socket.id; }
      else if (room.player2UserId === userId) { pIndex = 1; room.player2SocketId = socket.id; }
      else { socket.emit('error', { message: 'You are not a player in this game' }); return; }

      var state = room.game.getStateForPlayer(pIndex);
      state.playerIndex = pIndex;
      state.gameType = room.gameType || 'casual';
      socket.emit('gameState', state);
    } catch (e) {
      socket.emit('error', { message: (e && e.message) || 'Failed to load game state' });
    }
  });

  socket.on('playCard', function (data) {
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

  socket.on('attack', function (data) {
    try {
      var gameId = data && data.gameId;
      var room = getRoom(gameId);
      if (!room || !room.game) return;
      var pIndex = resolvePlayerIndex(room, socket);
      if (pIndex < 0) return;

      var game = room.game;
      var declareResult = game.declareAttack(
        pIndex,
        data.attackerType,
        typeof data.attackerIndex === 'number' ? data.attackerIndex : -1,
        data.targetType,
        typeof data.targetIndex === 'number' ? data.targetIndex : -1
      );

      if (!declareResult.ok) {
        socket.emit('actionResult', declareResult);
        return;
      }

      var defenderIdx = 1 - pIndex;

      if (isDefenderBot(room, defenderIdx)) {
        botDefend(game);
        var result = game.resolveCombat();
        emitAttackAnimation(room, result, pIndex);
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

  socket.on('useBlocker', function (data) {
    try {
      var gameId = data && data.gameId;
      var charIndex = typeof data.charIndex === 'number' ? data.charIndex : -1;
      var room = getRoom(gameId);
      if (!room || !room.game || !room.game.pendingCombat) return;
      var pIndex = resolvePlayerIndex(room, socket);
      if (pIndex < 0) return;
      var result = room.game.useBlocker(pIndex, charIndex);
      socket.emit('actionResult', result);
      if (result.ok) emitStateToBoth(room);
    } catch (e) {
      socket.emit('error', { message: (e && e.message) || 'Blocker failed' });
    }
  });

  socket.on('playCounter', function (data) {
    try {
      var gameId = data && data.gameId;
      var handIndex = typeof data.handIndex === 'number' ? data.handIndex : -1;
      var room = getRoom(gameId);
      if (!room || !room.game || !room.game.pendingCombat) return;
      var pIndex = resolvePlayerIndex(room, socket);
      if (pIndex < 0) return;
      var result = room.game.playCounter(pIndex, handIndex);
      socket.emit('actionResult', result);
      if (result.ok) emitStateToBoth(room);
    } catch (e) {
      socket.emit('error', { message: (e && e.message) || 'Counter failed' });
    }
  });

  socket.on('finishDefense', function (data) {
    try {
      var gameId = data && data.gameId;
      var room = getRoom(gameId);
      if (!room || !room.game || !room.game.pendingCombat) return;
      var pIndex = resolvePlayerIndex(room, socket);
      if (pIndex < 0) return;

      var game = room.game;
      var attackerPlayerIndex = game.pendingCombat.attackerPlayerIndex;
      var result = game.resolveCombat();

      emitAttackAnimation(room, result, attackerPlayerIndex);

      setTimeout(function () {
        emitStateToBoth(room);
        if (result.gameOver) {
          checkAndProcessEnd(room);
        } else if (room.botAttacking) {
          room.botAttacking = false;
          setTimeout(function () {
            processNextBotAttack(room, socket);
          }, 400);
        }
      }, 600);
    } catch (e) {
      socket.emit('error', { message: (e && e.message) || 'Defense resolve failed' });
    }
  });

  socket.on('attachDon', function (data) {
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

  socket.on('endTurn', function (data) {
    var gameId = data && data.gameId;
    try {
      var room = getRoom(gameId);
      if (!room || !room.game) return;
      var pIndex = resolvePlayerIndex(room, socket);
      if (pIndex < 0) return;

      var game = room.game;
      if (game.turnManager.currentPlayerIndex !== pIndex) {
        socket.emit('actionResult', { ok: false, reason: 'Not your turn' });
        return;
      }
      if (game.pendingCombat) {
        socket.emit('actionResult', { ok: false, reason: 'Resolve combat first' });
        return;
      }

      game.endTurn();

      if (game.status === 'finished') {
        emitStateToBoth(room);
        checkAndProcessEnd(room);
        return;
      }

      if (room.player2SocketId === null && game.turnManager.currentPlayerIndex === 1) {
        botPlayCards(game);
        botAttachDon(game);
        var attacks = botPlanAttacks(game);

        if (attacks.length === 0) {
          game.endTurn();
          emitStateToBoth(room);
          if (game.status === 'finished') checkAndProcessEnd(room);
          return;
        }

        room.botAttackQueue = attacks;
        emitStateToBoth(room);
        setTimeout(function () {
          processNextBotAttack(room, socket);
        }, 500);
        return;
      }

      emitStateToBoth(room);
      if (game.status === 'finished') checkAndProcessEnd(room);
    } catch (e) {
      socket.emit('error', { message: (e && e.message) || 'Invalid action' });
    }
  });
});

server.listen(PORT, BIND, function () {
  console.log('Game server listening on ' + BIND + ':' + PORT);
});
