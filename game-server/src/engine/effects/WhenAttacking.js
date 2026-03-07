const EffectEngine = require('./EffectEngine');
function run(game, playerIndex, card) { return EffectEngine.resolveWhenAttacking(game, playerIndex, card); }
module.exports = { run };
