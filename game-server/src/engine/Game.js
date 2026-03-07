const { Player } = require('./Player');
const { Board } = require('./Board');
const { TurnManager, PHASES } = require('./TurnManager');

class Game {
  constructor(gameId, player1Config, player2Config) {
    this.gameId = gameId;
    this.status = 'active';
    this.winnerId = null;
    this.actionLog = [];

    const p1 = new Player({
      id: 'p1',
      userId: player1Config.userId,
      username: player1Config.username,
      leaderCard: player1Config.leaderCard,
      deckCards: player1Config.deckCards || []
    });
    const p2 = new Player({
      id: 'p2',
      userId: player2Config.userId,
      username: player2Config.username,
      leaderCard: player2Config.leaderCard,
      deckCards: player2Config.deckCards || []
    });

    p1.draw(5);
    p2.draw(5);

    /**
     * Rule 5-2-1-7: life cards = N cards drawn face-down
     * from the top of the deck (N = leader life value).
     */
    const p1Life = parseInt((player1Config.leaderCard && player1Config.leaderCard.life) || 5, 10);
    const p2Life = parseInt((player2Config.leaderCard && player2Config.leaderCard.life) || 5, 10);
    p1.setupLifeZone(p1Life);
    p2.setupLifeZone(p2Life);

    this.board = new Board(p1, p2);
    this.turnManager = new TurnManager();
    this.turnManager.turnCount = 1;
    this.turnManager.currentPlayerIndex = 0;

    this.p1TurnsTaken = 0;
    this.p2TurnsTaken = 0;

    this._runAutoPhases(true);
  }

  get currentPlayer() {
    return this.board.getPlayer(this.turnManager.currentPlayerIndex);
  }

  get opponent() {
    return this.board.getOpponent(this.turnManager.currentPlayerIndex);
  }

  _log(msg) {
    this.actionLog.push({
      turn: this.turnManager.turnCount,
      player: this.turnManager.currentPlayerIndex,
      msg: msg
    });
    if (this.actionLog.length > 30) this.actionLog.shift();
  }

  _checkDeckOut() {
    const p1 = this.board.player1;
    const p2 = this.board.player2;
    if (p1.deck.length === 0) {
      this.status = 'finished';
      this.winnerId = p2.userId;
      this._log('Player 1 deck out — loses!');
      return true;
    }
    if (p2.deck.length === 0) {
      this.status = 'finished';
      this.winnerId = p1.userId;
      this._log('Player 2 deck out — loses!');
      return true;
    }
    return false;
  }

  _isFirstTurnForCurrentPlayer() {
    const idx = this.turnManager.currentPlayerIndex;
    return idx === 0 ? this.p1TurnsTaken === 0 : this.p2TurnsTaken === 0;
  }

  /**
   * Rule 6-2 → 6-4: Refresh, Draw, DON!! auto-phases,
   * then land on Main phase for player interaction.
   */
  _runAutoPhases(isGameStart) {
    const cp = this.currentPlayer;
    const isFirstTurn = this._isFirstTurnForCurrentPlayer();

    /**
     * Rule 6-2-3: return all DON!! attached to leader/characters
     * back to cost zone (RESTED).
     * Rule 6-2-4: then unrest everything.
     */
    cp.returnAttachedDonToArea();
    cp.refreshAll();
    this._log('Refresh: all cards active');

    /**
     * Rule 6-3-1: draw 1 card. First player does NOT draw
     * on their first turn.
     */
    const isP1FirstTurn = this.turnManager.currentPlayerIndex === 0 && isFirstTurn;
    if (!isP1FirstTurn) {
      const drawn = cp.draw(1);
      if (drawn.length > 0) this._log('Draw: +1 card');
    }

    if (this._checkDeckOut()) return;

    /**
     * Rule 6-4-1: add 2 DON!! from DON!! deck.
     * First player adds only 1 on their first turn.
     */
    const donCount = isP1FirstTurn ? 1 : 2;
    cp.addDonToArea(donCount);
    this._log('DON!!: +' + donCount + ' (total ' + cp.donArea.length + ')');

    this.turnManager.phase = 'main';
    this.turnManager.phaseIndex = 3;
  }

  playCard(playerIndex, handIndex, autoTrash) {
    if (this.status !== 'active') return { ok: false, reason: 'Game is over' };
    if (this.turnManager.phase !== 'main') return { ok: false, reason: 'Not in main phase' };
    if (this.turnManager.currentPlayerIndex !== playerIndex) return { ok: false, reason: 'Not your turn' };

    const player = this.board.getPlayer(playerIndex);
    if (handIndex < 0 || handIndex >= player.hand.length) return { ok: false, reason: 'Invalid card' };

    const card = player.hand[handIndex];
    const cost = parseInt(card.card_cost, 10) || 0;
    const available = player.getAvailableDon();

    if (cost > available) {
      return { ok: false, reason: 'Need ' + cost + ' DON!! (have ' + available + ')' };
    }

    if (cost > 0) player.restDon(cost);

    const type = ((card.card_type || card.type) || '').toLowerCase();

    if (type.indexOf('stage') !== -1 || type.indexOf('lieu') !== -1) {
      if (player.stage) player.trash.push(player.stage);
      player.stage = Object.assign({}, card);
      player.hand.splice(handIndex, 1);
      this._log('Played stage: ' + (card.card_name || 'Stage'));
      return { ok: true, action: 'stage' };
    }

    if (type.indexOf('event') !== -1 || type.indexOf('événement') !== -1) {
      player.trash.push(Object.assign({}, card));
      player.hand.splice(handIndex, 1);
      this._log('Played event: ' + (card.card_name || 'Event'));
      return { ok: true, action: 'event' };
    }

    /**
     * Rule 3-7-6-1: if 5 characters already on field,
     * player must choose one to discard before playing new one.
     * For auto/bot play, we trash the weakest character.
     */
    if (player.characters.length >= player.maxCharacters) {
      if (autoTrash) {
        let weakest = 0;
        for (let i = 1; i < player.characters.length; i++) {
          if ((parseInt(player.characters[i].card_power, 10) || 0) <
              (parseInt(player.characters[weakest].card_power, 10) || 0)) {
            weakest = i;
          }
        }
        const removed = player.characters.splice(weakest, 1)[0];
        player.trash.push(removed);
        this._log('Trashed ' + (removed.card_name || 'character') + ' to make room');
      } else {
        if (cost > 0) player.activeDon(cost);
        return { ok: false, reason: 'Character zone full (5 max) — trash one first' };
      }
    }

    player.characters.push(Object.assign({}, card, {
      rested: false,
      attachedDon: 0,
      summonSick: true
    }));
    player.hand.splice(handIndex, 1);
    this._log('Played: ' + (card.card_name || 'Character') + ' (cost ' + cost + ')');
    return { ok: true, action: 'character' };
  }

  /**
   * Rule 3-7-6-1: player-initiated character replacement
   * when field is full.
   */
  trashCharacter(playerIndex, charIndex) {
    if (this.status !== 'active') return { ok: false };
    if (this.turnManager.phase !== 'main') return { ok: false };
    if (this.turnManager.currentPlayerIndex !== playerIndex) return { ok: false };

    const player = this.board.getPlayer(playerIndex);
    if (charIndex < 0 || charIndex >= player.characters.length) return { ok: false, reason: 'Invalid character' };

    const removed = player.characters.splice(charIndex, 1)[0];
    player.trash.push(removed);
    this._log('Trashed ' + (removed.card_name || 'character'));
    return { ok: true };
  }

  /**
   * Rule 6-5-5: give DON!! to leader or character
   * (+1000 power during your turn per DON!!).
   */
  attachDon(playerIndex, targetType, targetIndex) {
    if (this.status !== 'active') return { ok: false };
    if (this.turnManager.phase !== 'main') return { ok: false };
    if (this.turnManager.currentPlayerIndex !== playerIndex) return { ok: false };

    const player = this.board.getPlayer(playerIndex);
    if (player.getAvailableDon() <= 0) return { ok: false, reason: 'No active DON!!' };

    if (targetType === 'leader' && player.leader) {
      player.restDon(1);
      player.leader.attachedDon = (player.leader.attachedDon || 0) + 1;
      this._log('Attached DON!! to leader (+1000 power)');
      return { ok: true };
    }

    if (targetType === 'character' && targetIndex >= 0 && targetIndex < player.characters.length) {
      player.restDon(1);
      player.characters[targetIndex].attachedDon = (player.characters[targetIndex].attachedDon || 0) + 1;
      this._log('Attached DON!! to ' + (player.characters[targetIndex].card_name || 'character'));
      return { ok: true };
    }

    return { ok: false, reason: 'Invalid target' };
  }

  /**
   * Rule 7: Attacks and combat.
   * Rule 6-5-5-2: DON!! boost only during owner's turn.
   */
  _getEffectivePower(card, isOwnersTurn) {
    const base = parseInt(card.card_power, 10) || 0;
    const donBoost = isOwnersTurn ? ((card.attachedDon || 0) * 1000) : 0;
    return base + donBoost;
  }

  /**
   * Rule 6-5-6-1: Neither player can combat on their first turn.
   * Rule 7-1-1-2: Can attack leader OR rested characters.
   * Rule 7-1-4: Compare power; attacker >= defender = hit.
   */
  attack(playerIndex, attackerType, attackerIdx, targetType, targetIdx) {
    if (this.status !== 'active') return { ok: false, reason: 'Game over' };
    if (this.turnManager.phase !== 'main') return { ok: false, reason: 'Not main phase' };
    if (this.turnManager.currentPlayerIndex !== playerIndex) return { ok: false, reason: 'Not your turn' };

    if (this._isFirstTurnForCurrentPlayer()) {
      return { ok: false, reason: 'Cannot attack on your first turn' };
    }

    const player = this.board.getPlayer(playerIndex);
    const opp = this.board.getOpponent(playerIndex);

    let attacker = null;
    if (attackerType === 'leader') {
      attacker = player.leader;
    } else if (attackerType === 'character' && attackerIdx >= 0 && attackerIdx < player.characters.length) {
      attacker = player.characters[attackerIdx];
    }

    if (!attacker) return { ok: false, reason: 'Invalid attacker' };
    if (attacker.rested) return { ok: false, reason: 'Card is rested' };
    if (attacker.summonSick) return { ok: false, reason: 'Just played — cannot attack yet' };

    attacker.rested = true;

    const atkPower = this._getEffectivePower(attacker, true);
    const atkName = attacker.card_name || (attackerType === 'leader' ? 'Leader' : 'Character');

    if (targetType === 'leader') {
      if (!opp.leader) return { ok: false, reason: 'No leader' };
      const defPower = this._getEffectivePower(opp.leader, false);

      if (atkPower >= defPower) {
        if (opp.life.length === 0) {
          this.status = 'finished';
          this.winnerId = player.userId;
          this._log(atkName + ' delivers the final blow!');
          return { ok: true, hit: true, gameOver: true, winner: player.userId };
        }

        const lifeCard = opp.takeLifeDamage();
        this._log(atkName + ' hit leader! Life: ' + opp.life.length);
        return {
          ok: true, hit: true, damage: true,
          lifeRemaining: opp.life.length,
          lifeCard: lifeCard.card ? (lifeCard.card.card_name || 'card') : null,
          atkPower: atkPower, defPower: defPower
        };
      }

      this._log(atkName + ' blocked (' + atkPower + ' vs ' + defPower + ')');
      return { ok: true, hit: false, atkPower: atkPower, defPower: defPower };
    }

    if (targetType === 'character' && targetIdx >= 0 && targetIdx < opp.characters.length) {
      const target = opp.characters[targetIdx];
      if (!target.rested) return { ok: false, reason: 'Can only attack rested characters' };

      const defPower = this._getEffectivePower(target, false);

      if (atkPower >= defPower) {
        const ko = opp.characters.splice(targetIdx, 1)[0];
        opp.trash.push(ko);
        this._log(atkName + " KO'd " + (ko.card_name || 'character'));
        return { ok: true, hit: true, ko: true, atkPower: atkPower, defPower: defPower };
      }

      this._log(atkName + ' attack failed (' + atkPower + ' vs ' + defPower + ')');
      return { ok: true, hit: false, atkPower: atkPower, defPower: defPower };
    }

    return { ok: false, reason: 'Invalid target' };
  }

  endTurn() {
    if (this.status !== 'active') return false;

    if (this.turnManager.currentPlayerIndex === 0) {
      this.p1TurnsTaken++;
    } else {
      this.p2TurnsTaken++;
    }

    for (const c of this.currentPlayer.characters) {
      c.summonSick = false;
    }

    this._log('Turn ended');

    this.turnManager.currentPlayerIndex = 1 - this.turnManager.currentPlayerIndex;
    if (this.turnManager.currentPlayerIndex === 0) {
      this.turnManager.turnCount++;
    }

    this._runAutoPhases(false);
    return true;
  }

  getStateForPlayer(playerIndex) {
    return {
      gameId: this.gameId,
      status: this.status,
      winnerId: this.winnerId,
      turn: Object.assign({}, this.turnManager.serialize(), {
        isFirstTurn: this._isFirstTurnForCurrentPlayer()
      }),
      board: this.board.getStateForPlayer(playerIndex),
      log: this.actionLog.slice(-8)
    };
  }

  getState() {
    return {
      gameId: this.gameId,
      status: this.status,
      winnerId: this.winnerId,
      turn: this.turnManager.serialize(),
      board: this.board.getPublicState(this.turnManager.currentPlayerIndex),
      log: this.actionLog.slice(-8)
    };
  }
}

module.exports = { Game };
