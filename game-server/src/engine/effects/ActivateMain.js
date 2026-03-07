const EffectEngine = require('./EffectEngine');
function run(game, playerIndex, card) { return EffectEngine.resolveActivateMain(game, playerIndex, card); }
module.exports = { run };
