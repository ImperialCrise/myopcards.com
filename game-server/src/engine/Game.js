const { Player } = require('./Player');
const { Board } = require('./Board');
const { TurnManager, PHASES } = require('./TurnManager');

class Game {
  constructor(gameId, player1Config, player2Config) {
    this.gameId = gameId;
    this.status = 'active';
    this.winnerId = null;
    this.actionLog = [];
    this.pendingCombat = null;

    const p1 = new Player({
      id: 'p1',
      userId: player1Config.userId,
      username: player1Config.username,
      elo: player1Config.elo || 1000,
      leaderCard: player1Config.leaderCard,
      deckCards: player1Config.deckCards || []
    });
    const p2 = new Player({
      id: 'p2',
      userId: player2Config.userId,
      username: player2Config.username,
      elo: player2Config.elo || 1000,
      leaderCard: player2Config.leaderCard,
      deckCards: player2Config.deckCards || []
    });

    p1.draw(5);
    p2.draw(5);

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
    if (this.actionLog.length > 40) this.actionLog.shift();
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

  _runAutoPhases(isGameStart) {
    const cp = this.currentPlayer;
    const isFirstTurn = this._isFirstTurnForCurrentPlayer();

    cp.returnAttachedDonToArea();
    cp.refreshAll();
    this._log('Refresh: all cards active');

    const isP1FirstTurn = this.turnManager.currentPlayerIndex === 0 && isFirstTurn;
    if (!isP1FirstTurn) {
      const drawn = cp.draw(1);
      if (drawn.length > 0) this._log('Draw: +1 card');
    }

    if (this._checkDeckOut()) return;

    const donCount = isP1FirstTurn ? 1 : 2;
    cp.addDonToArea(donCount);
    this._log('DON!!: +' + donCount + ' (total ' + cp.donArea.length + ')');

    this.turnManager.phase = 'main';
    this.turnManager.phaseIndex = 3;
  }

  getEffectivePower(card) {
    if (!card) return 0;
    const base = parseInt(card.card_power, 10) || 0;
    const donBoost = (card.attachedDon || 0) * 1000;
    return base + donBoost;
  }

  playCard(playerIndex, handIndex, autoTrash) {
    if (this.status !== 'active') return { ok: false, reason: 'Game is over' };
    if (this.pendingCombat) return { ok: false, reason: 'Combat in progress' };
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

    const hasRush = !!(card.card_text && card.card_text.indexOf('[Rush]') !== -1);
    player.characters.push(Object.assign({}, card, {
      rested: false,
      attachedDon: 0,
      summonSick: !hasRush
    }));
    player.hand.splice(handIndex, 1);
    this._log('Played: ' + (card.card_name || 'Character') + (hasRush ? ' (Rush!)' : '') + ' (cost ' + cost + ')');
    return { ok: true, action: 'character', rush: hasRush };
  }

  trashCharacter(playerIndex, charIndex) {
    if (this.status !== 'active') return { ok: false };
    if (this.pendingCombat) return { ok: false };
    if (this.turnManager.phase !== 'main') return { ok: false };
    if (this.turnManager.currentPlayerIndex !== playerIndex) return { ok: false };

    const player = this.board.getPlayer(playerIndex);
    if (charIndex < 0 || charIndex >= player.characters.length) return { ok: false, reason: 'Invalid character' };

    const removed = player.characters.splice(charIndex, 1)[0];
    player.trash.push(removed);
    this._log('Trashed ' + (removed.card_name || 'character'));
    return { ok: true };
  }

  attachDon(playerIndex, targetType, targetIndex) {
    if (this.status !== 'active') return { ok: false };
    if (this.pendingCombat) return { ok: false };
    if (this.turnManager.phase !== 'main') return { ok: false };
    if (this.turnManager.currentPlayerIndex !== playerIndex) return { ok: false };

    const player = this.board.getPlayer(playerIndex);
    if (player.getAvailableDon() <= 0) return { ok: false, reason: 'No active DON!!' };

    if (targetType === 'leader' && player.leader) {
      player.removeDonFromArea();
      player.leader.attachedDon = (player.leader.attachedDon || 0) + 1;
      this._log('Attached DON!! to leader (+1000 power)');
      return { ok: true };
    }

    if (targetType === 'character' && targetIndex >= 0 && targetIndex < player.characters.length) {
      player.removeDonFromArea();
      player.characters[targetIndex].attachedDon = (player.characters[targetIndex].attachedDon || 0) + 1;
      this._log('Attached DON!! to ' + (player.characters[targetIndex].card_name || 'character'));
      return { ok: true };
    }

    return { ok: false, reason: 'Invalid target' };
  }

  declareAttack(playerIndex, attackerType, attackerIdx, targetType, targetIdx) {
    if (this.status !== 'active') return { ok: false, reason: 'Game over' };
    if (this.pendingCombat) return { ok: false, reason: 'Combat already in progress' };
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

    if (targetType === 'character') {
      if (targetIdx < 0 || targetIdx >= opp.characters.length) return { ok: false, reason: 'Invalid target' };
      if (!opp.characters[targetIdx].rested) return { ok: false, reason: 'Can only attack rested characters' };
    }

    attacker.rested = true;

    const defenderPlayerIndex = 1 - playerIndex;
    const atkPower = this.getEffectivePower(attacker);
    const atkName = attacker.card_name || (attackerType === 'leader' ? 'Leader' : 'Character');

    let defender = null;
    if (targetType === 'leader') {
      defender = opp.leader;
    } else {
      defender = opp.characters[targetIdx];
    }
    const defPower = this.getEffectivePower(defender);

    const hasDoubleAttack = !!(attacker.card_text && attacker.card_text.indexOf('[Double Attack]') !== -1);
    const hasBanish = !!(attacker.card_text && attacker.card_text.indexOf('[Banish]') !== -1);

    this.pendingCombat = {
      attackerPlayerIndex: playerIndex,
      defenderPlayerIndex: defenderPlayerIndex,
      attackerType: attackerType,
      attackerIndex: attackerIdx || 0,
      attackerCard: attacker,
      attackerName: atkName,
      attackerPower: atkPower,
      originalTargetType: targetType,
      originalTargetIndex: targetIdx || 0,
      currentTargetType: targetType,
      currentTargetIndex: targetIdx || 0,
      defenderPower: defPower,
      counterBoost: 0,
      countersPlayed: [],
      blockerUsed: false,
      blockerCharIndex: -1,
      doubleAttack: hasDoubleAttack,
      banish: hasBanish
    };

    this._log(atkName + ' attacks ' + (targetType === 'leader' ? 'Leader' : (defender.card_name || 'character')) + '! (' + atkPower + ' power)');

    return {
      ok: true,
      pending: true,
      combat: this._getCombatInfo(defenderPlayerIndex)
    };
  }

  _getCombatInfo(forPlayerIndex) {
    const c = this.pendingCombat;
    if (!c) return null;
    const defender = this.board.getPlayer(c.defenderPlayerIndex);
    const isDefender = forPlayerIndex === c.defenderPlayerIndex;

    let currentDefender = null;
    if (c.blockerUsed && c.blockerCharIndex >= 0) {
      currentDefender = defender.characters[c.blockerCharIndex];
    } else if (c.currentTargetType === 'leader') {
      currentDefender = defender.leader;
    } else {
      currentDefender = defender.characters[c.currentTargetIndex];
    }

    const currentDefPower = this.getEffectivePower(currentDefender) + c.counterBoost;

    const info = {
      attackerName: c.attackerName,
      attackerPower: c.attackerPower,
      attackerType: c.attackerType,
      attackerIndex: c.attackerIndex,
      targetType: c.currentTargetType,
      targetIndex: c.currentTargetIndex,
      defenderPower: currentDefPower,
      counterBoost: c.counterBoost,
      blockerUsed: c.blockerUsed,
      doubleAttack: c.doubleAttack,
      isDefender: isDefender,
      attackerPlayerIndex: c.attackerPlayerIndex
    };

    if (isDefender) {
      const blockers = (c.currentTargetType === 'leader' && !c.blockerUsed)
        ? defender.getBlockerCharacters()
        : [];
      const counters = defender.getCounterCardsInHand();
      info.availableBlockers = blockers;
      info.counterCards = counters;
      info.hasDefenseOptions = blockers.length > 0 || counters.length > 0;
    }

    return info;
  }

  useBlocker(defenderPlayerIndex, charIndex) {
    if (!this.pendingCombat) return { ok: false, reason: 'No combat' };
    if (this.pendingCombat.defenderPlayerIndex !== defenderPlayerIndex) return { ok: false, reason: 'Not defender' };
    if (this.pendingCombat.blockerUsed) return { ok: false, reason: 'Blocker already used' };
    if (this.pendingCombat.currentTargetType !== 'leader') return { ok: false, reason: 'Can only block attacks on leader' };

    const defender = this.board.getPlayer(defenderPlayerIndex);
    if (!defender.activateBlocker(charIndex)) return { ok: false, reason: 'Invalid blocker' };

    const blocker = defender.characters[charIndex];
    this.pendingCombat.blockerUsed = true;
    this.pendingCombat.blockerCharIndex = charIndex;
    this.pendingCombat.currentTargetType = 'character';
    this.pendingCombat.currentTargetIndex = charIndex;
    this.pendingCombat.defenderPower = this.getEffectivePower(blocker);

    this._log((blocker.card_name || 'Blocker') + ' blocks the attack!');

    return {
      ok: true,
      combat: this._getCombatInfo(defenderPlayerIndex)
    };
  }

  playCounter(defenderPlayerIndex, handIndex) {
    if (!this.pendingCombat) return { ok: false, reason: 'No combat' };
    if (this.pendingCombat.defenderPlayerIndex !== defenderPlayerIndex) return { ok: false, reason: 'Not defender' };

    const defender = this.board.getPlayer(defenderPlayerIndex);
    const cv = defender.useCounterCard(handIndex);
    if (cv <= 0) return { ok: false, reason: 'Not a counter card' };

    this.pendingCombat.counterBoost += cv;
    this.pendingCombat.countersPlayed.push(cv);
    this._log('Counter +' + cv + '! (total boost: +' + this.pendingCombat.counterBoost + ')');

    return {
      ok: true,
      counterValue: cv,
      totalBoost: this.pendingCombat.counterBoost,
      combat: this._getCombatInfo(defenderPlayerIndex)
    };
  }

  resolveCombat() {
    if (!this.pendingCombat) return { ok: false, reason: 'No combat' };

    const c = this.pendingCombat;
    const attacker = this.board.getPlayer(c.attackerPlayerIndex);
    const defender = this.board.getPlayer(c.defenderPlayerIndex);

    let defenderCard = null;
    if (c.blockerUsed && c.blockerCharIndex >= 0) {
      defenderCard = defender.characters[c.blockerCharIndex];
    } else if (c.currentTargetType === 'leader') {
      defenderCard = defender.leader;
    } else if (c.currentTargetIndex >= 0 && c.currentTargetIndex < defender.characters.length) {
      defenderCard = defender.characters[c.currentTargetIndex];
    }

    const atkPower = c.attackerPower;
    const defPower = this.getEffectivePower(defenderCard) + c.counterBoost;

    const result = {
      ok: true,
      attackerType: c.attackerType,
      attackerIndex: c.attackerIndex,
      targetType: c.currentTargetType,
      targetIndex: c.currentTargetIndex,
      attackerPower: atkPower,
      defenderPower: defPower,
      counterBoost: c.counterBoost,
      blockerUsed: c.blockerUsed,
      hit: false,
      damage: false,
      ko: false,
      gameOver: false,
      lifeRemaining: defender.life.length,
      triggers: []
    };

    if (atkPower >= defPower) {
      result.hit = true;

      const isTargetLeader = c.currentTargetType === 'leader' && !c.blockerUsed;

      if (isTargetLeader) {
        const damageCount = c.doubleAttack ? 2 : 1;
        result.damage = true;

        for (let d = 0; d < damageCount; d++) {
          if (defender.life.length === 0) {
            this.status = 'finished';
            this.winnerId = attacker.userId;
            result.gameOver = true;
            this._log(c.attackerName + ' delivers the final blow!');
            break;
          }

          const lifeResult = defender.takeLifeDamage();
          result.lifeRemaining = defender.life.length;

          if (lifeResult.trigger && lifeResult.card) {
            result.triggers.push({
              card_name: lifeResult.card.card_name,
              card_text: lifeResult.card.card_text
            });
            this._log('Trigger! ' + (lifeResult.card.card_name || 'Life card'));
          }
        }

        if (!result.gameOver) {
          this._log(c.attackerName + ' hit leader! Life: ' + defender.life.length);
        }
      } else {
        const targetIdx = c.blockerUsed ? c.blockerCharIndex : c.currentTargetIndex;
        if (targetIdx >= 0 && targetIdx < defender.characters.length) {
          const ko = defender.characters.splice(targetIdx, 1)[0];
          if (c.banish) {
            this._log((ko.card_name || 'character') + ' banished!');
          } else {
            defender.trash.push(ko);
            this._log(c.attackerName + " KO'd " + (ko.card_name || 'character'));
          }
          result.ko = true;
        }
      }
    } else {
      this._log(c.attackerName + ' attack blocked! (' + atkPower + ' vs ' + defPower + ')');
    }

    this.pendingCombat = null;
    return result;
  }

  skipDefense() {
    return this.resolveCombat();
  }

  endTurn() {
    if (this.status !== 'active') return false;
    if (this.pendingCombat) return false;

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
    const combatInfo = this.pendingCombat ? this._getCombatInfo(playerIndex) : null;
    return {
      gameId: this.gameId,
      status: this.status,
      winnerId: this.winnerId,
      turn: Object.assign({}, this.turnManager.serialize(), {
        isFirstTurn: this._isFirstTurnForCurrentPlayer()
      }),
      board: this.board.getStateForPlayer(playerIndex),
      combat: combatInfo,
      log: this.actionLog.slice(-10)
    };
  }

  getState() {
    return {
      gameId: this.gameId,
      status: this.status,
      winnerId: this.winnerId,
      turn: this.turnManager.serialize(),
      board: this.board.getPublicState(this.turnManager.currentPlayerIndex),
      log: this.actionLog.slice(-10)
    };
  }
}

module.exports = { Game };
