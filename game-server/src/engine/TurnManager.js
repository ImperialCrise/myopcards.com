const PHASES = ['refresh', 'draw', 'don', 'main', 'end'];

class TurnManager {
  constructor() {
    this.turnCount = 0;
    this.currentPlayerIndex = 0;
    this.phaseIndex = 0;
    this.phase = PHASES[0];
  }

  get currentPhase() {
    return PHASES[this.phaseIndex];
  }

  get isPlayer1Turn() {
    return this.currentPlayerIndex === 0;
  }

  advancePhase() {
    this.phaseIndex++;
    if (this.phaseIndex >= PHASES.length) {
      this.phaseIndex = 0;
      this.currentPlayerIndex = 1 - this.currentPlayerIndex;
      this.turnCount++;
    }
    this.phase = PHASES[this.phaseIndex];
    return this.phase;
  }

  startTurn() {
    this.phaseIndex = 0;
    this.phase = PHASES[0];
    if (this.turnCount === 0 && this.currentPlayerIndex === 0) this.turnCount = 1;
    return this.phase;
  }

  serialize() {
    return {
      turnCount: this.turnCount,
      currentPlayerIndex: this.currentPlayerIndex,
      phase: this.phase,
      phaseIndex: this.phaseIndex,
    };
  }
}

module.exports = { TurnManager, PHASES };
