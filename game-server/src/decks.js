const db = require('./db');

async function loadDeckForGame(deckId, userId) {
  const decks = await db.query(
    'SELECT d.id, d.leader_card_id, c.id AS leader_id, c.card_name AS leader_name, c.card_set_id AS leader_set_id, c.card_image_url AS leader_image_url, c.card_color AS leader_color, c.card_type AS leader_type, c.card_power AS leader_power, c.life AS leader_life, c.card_text AS leader_text FROM decks d JOIN cards c ON c.id = d.leader_card_id WHERE d.id = ? AND d.user_id = ?',
    [deckId, userId]
  );
  if (!decks || decks.length === 0) return null;
  const deck = decks[0];
  const leaderCard = {
    id: deck.leader_id,
    card_name: deck.leader_name,
    card_set_id: deck.leader_set_id,
    card_image_url: deck.leader_image_url,
    card_color: deck.leader_color,
    card_type: deck.leader_type,
    card_power: deck.leader_power,
    life: deck.leader_life,
    card_text: deck.leader_text
  };
  const rows = await db.query(
    'SELECT dc.card_id, dc.quantity, ca.id, ca.card_name, ca.card_set_id, ca.card_type, ca.card_color, ca.card_cost, ca.card_power, ca.card_image_url, ca.card_text FROM deck_cards dc JOIN cards ca ON ca.id = dc.card_id WHERE dc.deck_id = ? ORDER BY dc.card_id',
    [deckId]
  );
  const deckCards = [];
  (rows || []).forEach(r => {
    for (let q = 0; q < (r.quantity || 1); q++) {
      deckCards.push({
        id: r.id,
        card_id: r.card_id,
        card_name: r.card_name,
        card_set_id: r.card_set_id,
        card_type: r.card_type,
        card_color: r.card_color,
        card_cost: r.card_cost,
        card_power: r.card_power,
        card_image_url: r.card_image_url,
        card_text: r.card_text
      });
    }
  });
  return { leaderCard, deckCards };
}

module.exports = { loadDeckForGame };
