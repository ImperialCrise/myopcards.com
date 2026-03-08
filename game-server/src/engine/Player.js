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
    this.lifeStartCount = 0;
  }

  setupLifeZone(count) {
    this.life = [];
    this.lifeStartCount = count;
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

  takeLifeDamage() {
    if (this.life.length <= 0) return { card: null, trigger: false };
    const card = this.life.shift();
    if (card) this.hand.push(card);
    const hasTrigger = !!(card && card.card_text && card.card_text.indexOf('[Trigger]') !== -1);
    return { card: card || null, trigger: hasTrigger };
  }

  getCounterCardsInHand() {
    const result = [];
    for (let i = 0; i < this.hand.length; i++) {
      const c = this.hand[i];
      const cv = parseInt(c.counter_amount, 10);
      if (cv > 0) {
        result.push({ handIndex: i, card_name: c.card_name, card_image_url: c.card_image_url, counterValue: cv });
      }
    }
    return result;
  }

  getBlockerCharacters() {
    const result = [];
    for (let i = 0; i < this.characters.length; i++) {
      const c = this.characters[i];
      if (!c.rested && c.card_text && c.card_text.indexOf('[Blocker]') !== -1) {
        result.push({
          charIndex: i,
          card_name: c.card_name,
          card_image_url: c.card_image_url,
          card_power: c.card_power,
          attachedDon: c.attachedDon || 0
        });
      }
    }
    return result;
  }

  useCounterCard(handIndex) {
    if (handIndex < 0 || handIndex >= this.hand.length) return 0;
    const card = this.hand[handIndex];
    const cv = parseInt(card.counter_amount, 10) || 0;
    if (cv <= 0) return 0;
    this.hand.splice(handIndex, 1);
    this.trash.push(card);
    return cv;
  }

  activateBlocker(charIndex) {
    if (charIndex < 0 || charIndex >= this.characters.length) return false;
    const c = this.characters[charIndex];
    if (c.rested) return false;
    if (!c.card_text || c.card_text.indexOf('[Blocker]') === -1) return false;
    c.rested = true;
    return true;
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

  removeDonFromArea() {
    const idx = this.donArea.findIndex(d => !d.rested);
    if (idx === -1) return false;
    this.donArea.splice(idx, 1);
    return true;
  }

  addDonToArea(amount) {
    amount = amount || 1;
    for (let i = 0; i < amount && this.donDeck > 0; i++) {
      this.donDeck--;
      this.donArea.push({ rested: false });
    }
  }

  returnAttachedDonToArea() {
    let total = 0;
    if (this.leader && this.leader.attachedDon) {
      total += this.leader.attachedDon;
      this.leader.attachedDon = 0;
    }
    for (const c of this.characters) {
      const n = c.attachedDon || 0;
      total += n;
      c.attachedDon = 0;
    }
    for (let i = 0; i < total; i++) {
      this.donArea.push({ rested: false });
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
      lifeStartCount: this.lifeStartCount,
      donDeck: this.donDeck,
      donArea: this.donArea.map(d => ({ rested: d.rested }))
    };
  }
}

module.exports = { Player, shuffle };
