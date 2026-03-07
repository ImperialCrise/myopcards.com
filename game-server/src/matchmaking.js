const queue = { ranked: [], casual: [] };
const ELO_RANGE = 200;
const ELO_K = 32;

function enqueue(mode, player) {
  if (!queue[mode]) queue[mode] = [];
  if (queue[mode].some(p => p.userId === player.userId)) return;
  queue[mode].push({ ...player, queuedAt: Date.now() });
}

function dequeue(mode, userId) {
  if (!queue[mode]) return null;
  const idx = queue[mode].findIndex(p => p.userId === userId);
  if (idx === -1) return null;
  const [p] = queue[mode].splice(idx, 1);
  return p;
}

function findRankedMatch(player) {
  const list = queue.ranked || [];
  const elo = player.elo != null ? player.elo : 1000;
  let range = ELO_RANGE;
  const now = Date.now();
  for (let i = 0; i < list.length; i++) {
    if (list[i].userId === player.userId) continue;
    const otherElo = list[i].elo != null ? list[i].elo : 1000;
    const inRange = Math.abs(elo - otherElo) <= range;
    if (inRange) {
      const [matched] = list.splice(i, 1);
      return { player1: player, player2: matched };
    }
  }
  return null;
}

function findCasualMatch() {
  const list = queue.casual || [];
  if (list.length < 2) return null;
  const [a, b] = list.splice(0, 2);
  return { player1: a, player2: b };
}

function findMatch(mode, player) {
  if (mode === 'ranked') return findRankedMatch(player);
  return findCasualMatch();
}

function expectedScore(a, b) {
  return 1 / (1 + Math.pow(10, (b - a) / 400));
}

function eloUpdate(elo, expected, score) {
  return Math.round(elo + ELO_K * (score - expected));
}

module.exports = { enqueue, dequeue, findMatch, findRankedMatch, findCasualMatch, eloUpdate, expectedScore, queue, ELO_K };
