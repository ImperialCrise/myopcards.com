const KEYWORDS = ['Rush', 'Blocker', 'Double Attack', 'Banish', 'On K.O.'];

function parseKeywords(cardText) {
  if (!cardText || typeof cardText !== 'string') return [];
  const found = [];
  KEYWORDS.forEach(kw => {
    if (cardText.indexOf('[' + kw + ']') !== -1) found.push(kw);
  });
  return found;
}

function hasKeyword(card, keyword) {
  const kw = parseKeywords(card && card.card_text);
  return kw.indexOf(keyword) !== -1;
}

function parseTriggerBlocks(cardText) {
  if (!cardText || typeof cardText !== 'string') return [];
  const blocks = [];
  const regexes = [
    { type: 'OnPlay', re: /\[On Play\]\s*[-–—:]\s*(.+?)(?=\[|$)/gi },
    { type: 'WhenAttacking', re: /\[When Attacking\]\s*[-–—:]\s*(.+?)(?=\[|$)/gi },
    { type: 'Trigger', re: /\[Trigger\]\s*[-–—:]\s*(.+?)(?=\[|$)/gi },
    { type: 'ActivateMain', re: /\[Activate: Main\]\s*[-–—:]\s*(.+?)(?=\[|$)/gi },
    { type: 'Counter', re: /\[Counter\]\s*[-–—:]\s*(.+?)(?=\[|$)/gi }
  ];
  regexes.forEach(({ type, re }) => {
    let m;
    while ((m = re.exec(cardText)) !== null) blocks.push({ type, text: m[1].trim() });
  });
  return blocks;
}

function resolveOnPlay(game, playerIndex, card) {
  const blocks = parseTriggerBlocks(card.card_text || '');
  return { resolved: true, effects: blocks.filter(b => b.type === 'OnPlay') };
}

function resolveWhenAttacking(game, playerIndex, card) {
  const blocks = parseTriggerBlocks(card.card_text || '');
  return { resolved: true, effects: blocks.filter(b => b.type === 'WhenAttacking') };
}

function resolveTrigger(game, playerIndex, card) {
  const blocks = parseTriggerBlocks(card.card_text || '');
  return { resolved: true, effects: blocks.filter(b => b.type === 'Trigger') };
}

function resolveCounter(game, defenderIndex, card) {
  const blocks = parseTriggerBlocks(card.card_text || '');
  return { resolved: true, effects: blocks.filter(b => b.type === 'Counter') };
}

function resolveActivateMain(game, playerIndex, card) {
  const blocks = parseTriggerBlocks(card.card_text || '');
  return { resolved: true, effects: blocks.filter(b => b.type === 'ActivateMain') };
}

module.exports = { parseKeywords, hasKeyword, parseTriggerBlocks, resolveOnPlay, resolveWhenAttacking, resolveTrigger, resolveCounter, resolveActivateMain, KEYWORDS };
