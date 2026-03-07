function parsePower(val) {
  if (val == null || val === '') return 0;
  const n = parseInt(String(val).replace(/[^0-9]/g, ''), 10);
  return Number.isFinite(n) ? n : 0;
}

function resolveAttack(game, attackerPlayerIndex, attackerSource, target) {
  const attacker = game.board.getPlayer(attackerPlayerIndex);
  const defender = game.board.getOpponent(attackerPlayerIndex);
  const attackerCard = attackerSource === 'leader' ? attacker.leader : (attacker.characters[attackerSource] || null);
  if (!attackerCard || attackerCard.rested) return { valid: false, reason: 'invalid_attacker' };
  if (attackerCard.attachedDon == null) attackerCard.attachedDon = 0;
  const attackerPower = parsePower(attackerCard.card_power) + (attackerCard.attachedDon || 0) * 1000;
  const targetLeader = target && target.type === 'leader';

  if (targetLeader) {
    attackerCard.rested = true;
    const lifeResult = defender.takeLifeDamage();
    const doubleAttack = (attackerCard.card_text || '').indexOf('[Double Attack]') !== -1;
    if (doubleAttack && defender.lifeRemaining > 0) defender.takeLifeDamage();
    if (defender.lifeRemaining < 0) {
      game.status = 'finished';
      game.winnerId = attacker.userId;
    }
    return {
      valid: true,
      targetLeader: true,
      lifeDamage: 1 + (doubleAttack ? 1 : 0),
      trigger: lifeResult.trigger,
      triggerCard: lifeResult.card,
      gameOver: game.status === 'finished'
    };
  }

  const targetIndex = target && target.type === 'character' ? target.index : -1;
  const defenderChar = targetIndex >= 0 && defender.characters[targetIndex] ? defender.characters[targetIndex] : null;
  if (!defenderChar) return { valid: false, reason: 'no_target' };
  if (!defenderChar.rested) return { valid: false, reason: 'target_active' };

  const defenderPower = parsePower(defenderChar.card_power) + (defenderChar.attachedDon || 0) * 1000;
  attackerCard.rested = true;

  if (attackerPower >= defenderPower) {
    defender.characters.splice(targetIndex, 1);
    defender.trash.push(defenderChar);
    return { valid: true, ko: true, attackerPower, defenderPower };
  }

  return { valid: true, ko: false, attackerPower, defenderPower };
}

function canBlock(defender, attackerSlot) {
  const blocker = defender.characters.find(c => !c.rested && (c.card_text || '').indexOf('[Blocker]') !== -1);
  return !!blocker;
}

function declareAttack(game, attackerPlayerIndex, attackerSource, target) {
  if (game.turnManager.phase !== 'main') return { valid: false, reason: 'not_main_phase' };
  return resolveAttack(game, attackerPlayerIndex, attackerSource, target);
}

module.exports = {
  parsePower,
  resolveAttack,
  canBlock,
  declareAttack
};
