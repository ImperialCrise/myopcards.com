const EffectEngine = require('./EffectEngine');
function isRush(card) { return EffectEngine.hasKeyword(card, 'Rush'); }
function isBlocker(card) { return EffectEngine.hasKeyword(card, 'Blocker'); }
function hasDoubleAttack(card) { return EffectEngine.hasKeyword(card, 'Double Attack'); }
function hasBanish(card) { return EffectEngine.hasKeyword(card, 'Banish'); }
function hasOnKO(card) { return EffectEngine.hasKeyword(card, 'On K.O.'); }
module.exports = { isRush, isBlocker, hasDoubleAttack, hasBanish, hasOnKO };
