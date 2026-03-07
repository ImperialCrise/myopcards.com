<?php
$deck = $deck ?? null;
$initialDeck = [
    'id' => $deck['id'] ?? null,
    'name' => $deck['name'] ?? '',
    'leader_card_id' => $deck['leader_card_id'] ?? null,
    'leader_name' => $deck['leader_name'] ?? null,
    'leader_set_id' => $deck['leader_set_id'] ?? null,
    'leader_image_url' => $deck['leader_image_url'] ?? null,
    'leader_color' => $deck['leader_color'] ?? null,
    'cards' => array_map(function ($r) {
        return [
            'card_id' => (int)$r['card_id'],
            'quantity' => (int)$r['quantity'],
            'card_name' => $r['card_name'],
            'card_set_id' => $r['card_set_id'],
            'card_type' => $r['card_type'] ?? '',
            'card_color' => $r['card_color'] ?? '',
            'card_image_url' => $r['card_image_url'] ?? null,
        ];
    }, $deck['cards'] ?? []),
];
$initialJson = json_encode($initialDeck, JSON_HEX_APOS | JSON_HEX_TAG);
$colorsJson = json_encode($colors ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$typesJson = json_encode($types ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$setsJson = json_encode($sets ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$safeInitial = str_replace("'", "&#39;", $initialJson);
$safeColors = str_replace("'", "&#39;", $colorsJson);
$safeTypes = str_replace("'", "&#39;", $typesJson);
$safeSets = str_replace("'", "&#39;", $setsJson);
?>
<style>
.db-wrap { display: flex; gap: 24px; min-height: calc(100vh - 120px); }
.db-left { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 16px; }
.db-right { width: 360px; flex-shrink: 0; display: flex; flex-direction: column; gap: 16px; position: sticky; top: 80px; align-self: flex-start; max-height: calc(100vh - 100px); }
@media (max-width: 1024px) {
  .db-wrap { flex-direction: column; }
  .db-right { width: 100%; position: static; max-height: none; }
}

.db-panel { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); border-radius: 16px; padding: 20px; }
.db-title { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: rgba(255,255,255,0.4); margin-bottom: 12px; }
.db-title span { color: #f59e0b; }

.db-name-input {
  width: 100%; padding: 10px 14px; background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: #fff; font-size: 1rem;
}
.db-name-input:focus { outline: none; border-color: rgba(245,158,11,0.5); }

.db-search-input {
  width: 100%; padding: 10px 14px; background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; color: #fff; font-size: 0.9rem;
}
.db-search-input:focus { outline: none; border-color: rgba(245,158,11,0.5); }

.db-color-filter { display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; }
.db-color-btn {
  padding: 4px 12px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; cursor: pointer;
  background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.6); border: 1px solid rgba(255,255,255,0.1);
}
.db-color-btn.active { background: rgba(245,158,11,0.15); color: #f59e0b; border-color: rgba(245,158,11,0.3); }

.db-leader-picked {
  display: flex; align-items: center; gap: 14px; padding: 12px;
  background: rgba(245,158,11,0.06); border: 1px solid rgba(245,158,11,0.2); border-radius: 12px;
}
.db-leader-picked img { width: 56px; height: 78px; object-fit: cover; border-radius: 8px; flex-shrink: 0; }
.db-leader-info { flex: 1; min-width: 0; }
.db-leader-info p { margin: 0; }
.db-leader-name { font-weight: 600; color: #fff; font-size: 0.95rem; }
.db-leader-meta { font-size: 0.8rem; color: rgba(255,255,255,0.5); }
.db-leader-remove { padding: 6px 12px; border-radius: 8px; background: rgba(239,68,68,0.15); color: #ef4444; border: none; cursor: pointer; font-size: 0.8rem; font-weight: 600; }
.db-leader-remove:hover { background: rgba(239,68,68,0.25); }

.db-leader-list { max-height: 260px; overflow-y: auto; display: flex; flex-direction: column; gap: 4px; margin-top: 8px; }
.db-leader-item {
  display: flex; align-items: center; gap: 10px; padding: 8px 10px;
  border-radius: 10px; cursor: pointer; transition: background 0.15s; border: none; background: none; color: inherit; width: 100%; text-align: left;
}
.db-leader-item:hover { background: rgba(255,255,255,0.06); }
.db-leader-item img { width: 36px; height: 50px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
.db-leader-item-name { font-size: 0.85rem; color: #fff; }
.db-leader-item-set { font-size: 0.75rem; color: rgba(255,255,255,0.4); }

.db-card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 10px; max-height: 400px; overflow-y: auto; margin-top: 10px; }
.db-card-cell { position: relative; cursor: pointer; border-radius: 10px; overflow: hidden; border: 2px solid transparent; transition: all 0.15s; aspect-ratio: 5/7; }
.db-card-cell:hover { border-color: rgba(255,255,255,0.3); }
.db-card-cell.in-deck { border-color: #22c55e; }
.db-card-cell img { width: 100%; height: 100%; object-fit: cover; display: block; }
.db-card-cell .db-card-qty { position: absolute; top: 4px; right: 4px; background: #f59e0b; color: #000; font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 6px; }
.db-card-cell .db-card-add { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5); opacity: 0; transition: opacity 0.15s; font-weight: 700; font-size: 1.5rem; color: #fff; }
.db-card-cell:hover .db-card-add { opacity: 1; }
.db-card-cell .db-card-cost { position: absolute; top: 4px; left: 4px; background: rgba(0,0,0,0.8); color: #fff; font-size: 0.7rem; font-weight: 700; padding: 2px 6px; border-radius: 6px; }

.db-deck-cards { overflow-y: auto; flex: 1; min-height: 0; display: flex; flex-direction: column; gap: 4px; }
.db-deck-entry {
  display: flex; align-items: center; gap: 10px; padding: 6px 8px; border-radius: 8px;
  background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);
}
.db-deck-entry.db-deck-entry-mismatch {
  border: 2px solid #ef4444; background: rgba(239,68,68,0.08); box-shadow: 0 0 0 1px rgba(239,68,68,0.3);
}
.db-deck-entry.db-deck-entry-mismatch .db-deck-entry-meta { color: #ef4444; font-weight: 600; }
.db-deck-entry img { width: 32px; height: 45px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
.db-deck-entry-info { flex: 1; min-width: 0; }
.db-deck-entry-name { font-size: 0.82rem; font-weight: 500; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.db-deck-entry-meta { font-size: 0.7rem; color: rgba(255,255,255,0.4); }
.db-deck-entry-qty { font-size: 0.9rem; font-weight: 700; color: #f59e0b; width: 24px; text-align: center; }
.db-qty-btns { display: flex; gap: 4px; }
.db-qty-btn {
  width: 24px; height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center;
  font-size: 1rem; font-weight: 700; cursor: pointer; border: none; transition: all 0.15s;
}
.db-qty-btn.plus { background: rgba(34,197,94,0.15); color: #22c55e; }
.db-qty-btn.plus:hover { background: rgba(34,197,94,0.25); }
.db-qty-btn.minus { background: rgba(239,68,68,0.15); color: #ef4444; }
.db-qty-btn.minus:hover { background: rgba(239,68,68,0.25); }

.db-summary { display: flex; flex-wrap: wrap; gap: 12px; font-size: 0.85rem; }
.db-summary-item { color: rgba(255,255,255,0.5); }
.db-summary-item b { color: #fff; }

.db-rules { font-size: 0.75rem; color: rgba(255,255,255,0.35); margin-top: 8px; line-height: 1.5; }
.db-rules p { margin: 2px 0; }

.db-errors { font-size: 0.82rem; color: #fbbf24; margin-bottom: 8px; }

.db-save-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.db-cancel { padding: 8px 18px; border-radius: 10px; background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.6); text-decoration: none; font-size: 0.9rem; font-weight: 500; }
.db-cancel:hover { background: rgba(255,255,255,0.1); color: #fff; }
.db-save-btn {
  padding: 10px 24px; border-radius: 10px; font-weight: 700; font-size: 0.95rem; cursor: pointer; border: none; transition: all 0.2s;
}
.db-save-btn.enabled { background: #f59e0b; color: #000; }
.db-save-btn.enabled:hover { background: #fbbf24; }
.db-save-btn.disabled { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.3); cursor: not-allowed; }

.db-empty-deck { text-align: center; padding: 24px 16px; color: rgba(255,255,255,0.3); font-size: 0.85rem; }
</style>

<div x-data='deckBuilder(<?= $safeInitial ?>, <?= $safeColors ?>, <?= $safeSets ?>, <?= $safeTypes ?>)' x-init="init()">

    <div class="db-save-bar" style="margin-bottom: 20px;">
        <div>
            <h1 class="text-2xl font-bold text-white" style="margin:0"><?= $deck ? 'Edit Deck' : 'Create Deck' ?></h1>
            <p class="text-sm" style="color:rgba(255,255,255,0.4);margin:4px 0 0">1 Leader + 50 cards (max 4 copies, leader colors). 10 DON!! are provided in-game.</p>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <a href="/decks" class="db-cancel">Cancel</a>
            <button @click="saveDeck()" :disabled="saving || !isValid"
                class="db-save-btn" :class="(saving || !isValid) ? 'disabled' : 'enabled'"
                x-text="saving ? 'Saving...' : (deckId ? 'Update Deck' : 'Save Deck')"></button>
        </div>
    </div>

    <div class="db-wrap">
        <div class="db-left">
            <div class="db-panel">
                <label class="db-title">Deck Name</label>
                <input type="text" x-model="deckName" maxlength="100" placeholder="My deck" class="db-name-input">
            </div>

            <div class="db-panel">
                <div class="db-title">Leader</div>
                <template x-if="leader">
                    <div class="db-leader-picked">
                        <img :src="cardImgSrc(leader.card_image_url)" :alt="leader.card_name">
                        <div class="db-leader-info">
                            <p class="db-leader-name" x-text="leader.card_name"></p>
                            <p class="db-leader-meta" x-text="leader.card_set_id + ' · ' + (leader.card_color || '')"></p>
                        </div>
                        <button type="button" @click="clearLeader()" class="db-leader-remove">Change</button>
                    </div>
                </template>
                <template x-if="!leader">
                    <div>
                        <input type="text" x-model="leaderSearch" @input.debounce.300ms="searchLeader()" placeholder="Search for a Leader..." class="db-search-input">
                        <div x-show="leaderResults.length" class="db-leader-list">
                            <template x-for="c in leaderResults" :key="c.id">
                                <button type="button" @click="selectLeader(c)" class="db-leader-item">
                                    <img :src="cardImgSrc(c.card_image_url)" :alt="c.card_name">
                                    <div>
                                        <div class="db-leader-item-name" x-text="c.card_name"></div>
                                        <div class="db-leader-item-set" x-text="c.card_set_id + ' · ' + (c.card_color || '')"></div>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <div class="db-panel" x-show="leader">
                <div class="db-title">Add Cards</div>
                <input type="text" x-model="cardSearch" @input.debounce.300ms="searchCards()" placeholder="Search cards..." class="db-search-input">
                <div class="db-color-filter">
                    <button type="button" class="db-color-btn" :class="{ active: !cardSearchColor }" @click="cardSearchColor = ''; searchCards()">All</button>
                    <template x-for="c in allowedColorOptions" :key="c">
                        <button type="button" class="db-color-btn" :class="{ active: cardSearchColor === c }" @click="cardSearchColor = c; searchCards()" x-text="c"></button>
                    </template>
                </div>
                <div class="db-card-grid">
                    <template x-for="c in cardResults" :key="c.id">
                        <div class="db-card-cell" :class="{ 'in-deck': getDeckQty(c.id) > 0 }"
                            @click="addCard(c)" :title="c.card_name + ' (' + (c.card_cost || 0) + ' cost)'">
                            <img :src="cardImgSrc(c.card_image_url)" :alt="c.card_name">
                            <span class="db-card-cost" x-text="c.card_cost || '0'"></span>
                            <span class="db-card-qty" x-show="getDeckQty(c.id) > 0" x-text="'×' + getDeckQty(c.id)"></span>
                            <div class="db-card-add">+</div>
                        </div>
                    </template>
                </div>
                <p x-show="cardResults.length === 0" style="color:rgba(255,255,255,0.35);font-size:0.85rem;margin-top:12px;">Type a keyword to search or browse by color.</p>
            </div>

            <div class="db-panel" x-show="leader && recommendedCards.length > 0">
                <div class="db-title">Recommended for <span x-text="leader ? leader.card_color : ''"></span></div>
                <div class="db-card-grid">
                    <template x-for="c in recommendedCards" :key="'rec-' + c.id">
                        <div class="db-card-cell" :class="{ 'in-deck': getDeckQty(c.id) > 0 }"
                            @click="addCard(c)" :title="c.card_name">
                            <img :src="cardImgSrc(c.card_image_url)" :alt="c.card_name">
                            <span class="db-card-cost" x-text="c.card_cost || '0'"></span>
                            <span class="db-card-qty" x-show="getDeckQty(c.id) > 0" x-text="'×' + getDeckQty(c.id)"></span>
                            <div class="db-card-add">+</div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="db-right">
            <div class="db-panel" style="display:flex;flex-direction:column;flex:1;min-height:0;">
                <div class="db-title">Deck List <span x-text="'(' + totalCards + '/50)'"></span></div>
                <p x-show="validationErrors.length" class="db-errors" x-text="validationErrors.join(' · ')"></p>
                <div class="db-deck-cards">
                    <template x-for="entry in deckCards" :key="entry.card_id">
                        <div class="db-deck-entry" :class="{ 'db-deck-entry-mismatch': cardColorMismatch(entry) }">
                            <img :src="cardImgSrc(entry.card_image_url)" :alt="entry.card_name">
                            <div class="db-deck-entry-info">
                                <div class="db-deck-entry-name" x-text="entry.card_name"></div>
                                <div class="db-deck-entry-meta" x-text="entry.card_set_id + ' · ' + (entry.card_type || '') + (cardColorMismatch(entry) ? ' — Color mismatch' : '')"></div>
                            </div>
                            <span class="db-deck-entry-qty" x-text="entry.quantity"></span>
                            <div class="db-qty-btns">
                                <button type="button" class="db-qty-btn plus" @click="addCardById(entry)" :disabled="entry.quantity >= 4 || totalCards >= 50">+</button>
                                <button type="button" class="db-qty-btn minus" @click="decreaseCard(entry.card_id)">−</button>
                            </div>
                        </div>
                    </template>
                    <div x-show="deckCards.length === 0" class="db-empty-deck">
                        Click cards on the left to add them to your deck. You need 50 cards total.
                    </div>
                </div>
            </div>

            <div class="db-panel">
                <div class="db-title">Summary</div>
                <div class="db-summary">
                    <span class="db-summary-item">Leader: <b x-text="leader ? leader.card_name : 'None'"></b></span>
                    <span class="db-summary-item">Cards: <b x-text="totalCards + '/50'"></b></span>
                    <span class="db-summary-item">Unique: <b x-text="deckCards.length"></b></span>
                </div>
                <div class="db-rules">
                    <p>• 1 Leader + 50 cards</p>
                    <p>• Max 4 copies per card</p>
                    <p>• Colors must match Leader</p>
                    <p>• 10 DON!! provided in-game</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('deckBuilder', (initial, colors, sets, types) => ({
        deckId: initial.id || null,
        deckName: initial.name || '',
        leader: initial.leader_card_id ? {
            id: initial.leader_card_id,
            card_name: initial.leader_name,
            card_set_id: initial.leader_set_id,
            card_image_url: initial.leader_image_url,
            card_color: initial.leader_color
        } : null,
        deckCards: (initial.cards || []).map(c => ({
            card_id: c.card_id,
            quantity: c.quantity,
            card_name: c.card_name,
            card_set_id: c.card_set_id,
            card_type: c.card_type,
            card_color: c.card_color,
            card_image_url: c.card_image_url
        })),
        leaderSearch: '',
        leaderResults: [],
        cardSearch: '',
        cardSearchColor: '',
        cardResults: [],
        recommendedCards: [],
        recommendedLoading: false,
        saving: false,

        cardImgSrc(url) {
            if (url == null || typeof url !== 'string') return '/assets/img/card-back.png';
            if (url.indexOf('optcgapi.com') !== -1) return '/api/card-image?url=' + encodeURIComponent(url);
            return url;
        },

        get allowedColorOptions() {
            if (!this.leader || !this.leader.card_color) return colors || [];
            return this.normalizeColors(this.leader.card_color);
        },
        normalizeColors(colorStr) {
            if (!colorStr || typeof colorStr !== 'string') return [];
            return colorStr.split(/[\s\/]+/).map(s => s.trim()).filter(Boolean);
        },
        get totalCards() {
            return this.deckCards.reduce((n, e) => n + e.quantity, 0);
        },
        get overMaxCopy() {
            return this.deckCards.some(e => e.quantity > 4);
        },
        get hasColorMismatch() {
            return this.deckCards.some(e => this.cardColorMismatch(e));
        },
        cardColorMismatch(entry) {
            if (!this.leader || !this.leader.card_color) return false;
            const allowed = this.normalizeColors(this.leader.card_color);
            if (allowed.length === 0) return false;
            const cardColors = this.normalizeColors(entry.card_color);
            if (cardColors.length === 0) return false;
            return !cardColors.some(c => allowed.includes(c));
        },
        get validationErrors() {
            const err = [];
            if (!this.leader) err.push('Select a leader');
            if (this.totalCards < 50) err.push((50 - this.totalCards) + ' more cards needed');
            if (this.totalCards > 50) err.push('Remove ' + (this.totalCards - 50) + ' cards');
            if (this.overMaxCopy) err.push('Max 4 copies per card');
            if (this.hasColorMismatch) err.push('Color mismatch');
            return err;
        },
        get isValid() {
            return this.leader && this.deckName.trim() && this.totalCards === 50 && !this.overMaxCopy && !this.hasColorMismatch;
        },
        init() {
            this.searchLeader();
            if (this.leader) this.loadRecommended();
        },
        async searchLeader() {
            const q = (this.leaderSearch || '').trim();
            const params = new URLSearchParams({ type: 'Leader', page: 1 });
            if (q.length >= 2) params.set('q', q);
            const r = await fetch('/api/cards/search?' + params);
            const data = await r.json();
            this.leaderResults = (data.cards || []).slice(0, q.length >= 2 ? 15 : 20);
        },
        clearLeader() {
            this.leader = null;
            this.recommendedCards = [];
            this.searchLeader();
        },
        selectLeader(c) {
            this.leader = { id: c.id, card_name: c.card_name, card_set_id: c.card_set_id, card_image_url: c.card_image_url, card_color: c.card_color };
            this.leaderResults = [];
            this.leaderSearch = '';
            this.searchCards();
            this.loadRecommended();
        },
        async loadRecommended() {
            if (!this.leader || !this.leader.card_color) { this.recommendedCards = []; return; }
            this.recommendedLoading = true;
            try {
                const r = await fetch('/api/cards/recommended?color=' + encodeURIComponent(this.leader.card_color.trim()));
                const data = await r.json();
                this.recommendedCards = (data.cards || []).slice(0, 24);
            } finally {
                this.recommendedLoading = false;
            }
        },
        async searchCards() {
            const q = (this.cardSearch || '').trim();
            const params = new URLSearchParams({ page: 1, per_page: 60 });
            if (q) params.set('q', q);
            if (this.cardSearchColor) params.set('color', this.cardSearchColor);
            const r = await fetch('/api/cards/search?' + params);
            const data = await r.json();
            this.cardResults = (data.cards || []).filter(c => (c.card_type || '') !== 'Leader');
        },
        getDeckQty(cardId) {
            const e = this.deckCards.find(x => x.card_id === cardId);
            return e ? e.quantity : 0;
        },
        addCard(c) {
            if (this.totalCards >= 50) return;
            const entry = this.deckCards.find(x => x.card_id === c.id);
            if (entry) {
                if (entry.quantity >= 4) return;
                entry.quantity += 1;
            } else {
                this.deckCards.push({
                    card_id: c.id, quantity: 1,
                    card_name: c.card_name, card_set_id: c.card_set_id,
                    card_type: c.card_type, card_color: c.card_color,
                    card_image_url: c.card_image_url
                });
            }
        },
        addCardById(entry) {
            if (this.totalCards >= 50 || entry.quantity >= 4) return;
            entry.quantity += 1;
        },
        decreaseCard(cardId) {
            const entry = this.deckCards.find(x => x.card_id === cardId);
            if (!entry) return;
            if (entry.quantity <= 1) this.deckCards = this.deckCards.filter(x => x.card_id !== cardId);
            else entry.quantity -= 1;
        },
        removeCard(cardId) {
            this.deckCards = this.deckCards.filter(x => x.card_id !== cardId);
        },
        async saveDeck() {
            if (!this.isValid || this.saving) return;
            this.saving = true;
            const payload = {
                id: this.deckId,
                name: this.deckName.trim(),
                leader_card_id: this.leader.id,
                cards: this.deckCards.map(e => ({ card_id: e.card_id, quantity: e.quantity }))
            };
            try {
                const r = await fetch('/api/decks/save', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
                const data = await r.json();
                if (data.success) {
                    window.location.href = data.id ? '/decks/' + data.id + '/edit' : '/decks';
                } else {
                    alert(data.error || 'Save failed.');
                }
            } catch (e) {
                alert('Network error.');
            }
            this.saving = false;
        }
    }));
});
</script>
