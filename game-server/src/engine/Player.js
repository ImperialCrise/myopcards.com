function shuffle(arr) {
  const a = [...arr];
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
}

class Player {
  constructor(opts) {
    const { id, userId, username, deckCards, leaderCard } = opts || {};
    this.id = id;
    this.userId = userId;
    this.username = username || 'Player';
    this.elo = opts.elo || 1000;
    this.leader = leaderCard ? Object.assign({}, leaderCard, { rested: false, attachedDon: 0 }) : null;
    this.characters = [];
    this.stage = null;
    this.hand = [];
    this.deck = shuffle([...(deckCards || [])]);
    this.trash = [];
    this.life = [];
    this.donDeck = 10;
    this.donArea = [];
    this.maxCharacters = 5;
  }

  /**
   * Rule 5-2-1-7: draw N cards face-down from deck
   * into the life zone (top of deck → bottom of life zone).
   */
  setupLifeZone(count) {
    this.life = [];
    for (let i = 0; i < count && this.deck.length > 0; i++) {
      this.life.push(this.deck.shift());
    }
  }

  draw(n) {
    n = n || 1;
    const drawn = [];
    for (let i = 0; i < n && this.deck.length > 0; i++) {
      const c = this.deck.shift();
      drawn.push(c);
      this.hand.push(c);
    }
    return drawn;
  }

  /**
   * Rule 4-6-2-1 / 7-1-4-1-1-2: when leader takes damage,
   * move top card from life zone to hand.
   */
  takeLifeDamage() {
    if (this.life.length <= 0) return { card: null, trigger: false };
    const card = this.life.shift();
    if (card) this.hand.push(card);
    return { card: card || null, trigger: !!card };
  }

  restDon(count) {
    let n = 0;
    for (const d of this.donArea) {
      if (n >= count) break;
      if (!d.rested) { d.rested = true; n++; }
    }
    return n;
  }

  activeDon(count) {
    let n = 0;
    for (const d of this.donArea) {
      if (n >= count) break;
      if (d.rested) { d.rested = false; n++; }
    }
    return n;
  }

  getAvailableDon() {
    return this.donArea.filter(d => !d.rested).length;
  }

  addDonToArea(amount) {
    amount = amount || 1;
    for (let i = 0; i < amount && this.donDeck > 0; i++) {
      this.donDeck--;
      this.donArea.push({ rested: false });
    }
  }

  /**
   * Rule 6-2-3: return all DON!! attached to leader/characters
   * to cost zone as RESTED. They will be unrested in refreshAll().
   */
  returnAttachedDonToArea() {
    let total = 0;
    if (this.leader && this.leader.attachedDon) {
      for (let i = 0; i < this.leader.attachedDon; i++) this.donArea.push({ rested: true });
      total += this.leader.attachedDon;
      this.leader.attachedDon = 0;
    }
    for (const c of this.characters) {
      const n = c.attachedDon || 0;
      for (let i = 0; i < n; i++) this.donArea.push({ rested: true });
      c.attachedDon = 0;
      total += n;
    }
    return total;
  }

  refreshAll() {
    if (this.leader) this.leader.rested = false;
    for (const c of this.characters) c.rested = false;
    for (const d of this.donArea) d.rested = false;
  }

  serialize() {
    return {
      id: this.id,
      userId: this.userId,
      username: this.username,
      elo: this.elo,
      leader: this.leader ? Object.assign({}, this.leader) : null,
      characters: this.characters.map(c => Object.assign({}, c)),
      stage: this.stage ? Object.assign({}, this.stage) : null,
      handCount: this.hand.length,
      deckCount: this.deck.length,
      trashCount: this.trash.length,
      lifeRemaining: this.life.length,
      donDeck: this.donDeck,
      donArea: this.donArea.map(d => ({ rested: d.rested }))
    };
  }
}

module.exports = { Player, shuffle };
