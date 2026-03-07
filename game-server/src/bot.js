function canPlayCard(card, gameState) {
  const cost = parseInt(card.card_cost, 10) || 0;
  const availableDon = gameState.donArea ? gameState.donArea.filter(d => !d.rested).length : 0;
  return availableDon >= cost;
}

function getBotDecision(gameState, difficulty) {
  if (gameState.phase === 'main' && gameState.hand && gameState.hand.length > 0) {
    const canPlay = gameState.hand.filter(c => canPlayCard(c, gameState));
    if (canPlay.length > 0) {
      const idx = difficulty === 'easy' ? Math.floor(Math.random() * canPlay.length) : 0;
      const card = canPlay[idx];
      return { type: 'play', data: { card, index: gameState.hand.indexOf(card) } };
    }
  }
  return { type: 'pass', data: {} };
}

module.exports = { getBotDecision, canPlayCard };
