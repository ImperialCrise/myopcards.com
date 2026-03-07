const EffectEngine = require('./EffectEngine');
function run(game, playerIndex, card) { return EffectEngine.resolveOnPlay(game, playerIndex, card); }
module.exports = { run };
