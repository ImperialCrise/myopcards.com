class Board {
  constructor(player1, player2) {
    this.player1 = player1;
    this.player2 = player2;
  }

  getPlayer(index) {
    return index === 0 ? this.player1 : this.player2;
  }

  getOpponent(index) {
    return index === 0 ? this.player2 : this.player1;
  }

  getPublicState(currentPlayerIndex) {
    const p1 = this.player1.serialize();
    const p2 = this.player2.serialize();
    return {
      player1: Object.assign({}, p1, { hand: null, handCount: p1.handCount, deckCount: p1.deckCount }),
      player2: Object.assign({}, p2, { hand: null, handCount: p2.handCount, deckCount: p2.deckCount }),
      currentPlayerIndex
    };
  }

  getStateForPlayer(playerIndex) {
    const mePlayer = this.getPlayer(playerIndex);
    const me = mePlayer.serialize();
    me.hand = (mePlayer.hand || []).map(c => Object.assign({}, c));
    const opp = this.getOpponent(playerIndex).serialize();
    return {
      me,
      opponent: Object.assign({}, opp, { hand: null, handCount: opp.handCount, deckCount: opp.deckCount }),
      currentPlayerIndex: playerIndex
    };
  }
}

module.exports = { Board };
