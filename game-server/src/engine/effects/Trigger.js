const EffectEngine = require('./EffectEngine');
function run(game, playerIndex, card) { return EffectEngine.resolveTrigger(game, playerIndex, card); }
module.exports = { run };
