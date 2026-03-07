const EffectEngine = require('./EffectEngine');

function run(game, defenderIndex, card) {
  return EffectEngine.resolveCounter(game, defenderIndex, card);
}

module.exports = { run };
