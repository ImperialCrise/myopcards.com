<?php
$initialResult = json_encode($result, JSON_HEX_APOS | JSON_HEX_TAG);
$userCardsJson = json_encode($userCards, JSON_HEX_APOS | JSON_HEX_TAG);
$setsJson = json_encode($sets, JSON_HEX_APOS | JSON_HEX_TAG);
$colorsJson = json_encode($colors, JSON_HEX_APOS | JSON_HEX_TAG);
$raritiesJson = json_encode($rarities, JSON_HEX_APOS | JSON_HEX_TAG);
$typesJson = json_encode($types, JSON_HEX_APOS | JSON_HEX_TAG);
$filtersJson = json_encode($filters, JSON_HEX_APOS | JSON_HEX_TAG);
?>

<div class="flex flex-col lg:flex-row gap-6" x-data="cardBrowser()" x-init="init()">
    <!-- Sidebar Filters -->
    <aside class="lg:w-64 flex-shrink-0 lg:sticky lg:top-24 lg:self-start">
        <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden w-full flex items-center justify-between glass rounded-xl px-4 py-3 mb-2">
            <span class="text-sm font-medium text-white flex items-center gap-2"><i data-lucide="sliders-horizontal" class="w-4 h-4"></i> Filters</span>
            <i data-lucide="chevron-down" class="w-4 h-4 text-dark-400 transition" :class="sidebarOpen && 'rotate-180'"></i>
        </button>
        <div x-show="sidebarOpen || window.innerWidth >= 1024" x-transition class="glass rounded-xl p-5 space-y-4">
            <div>
                <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5">Search</label>
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-dark-400"></i>
                    <input type="text" x-model="f.q" @input.debounce.350ms="doSearch()" placeholder="Card name or ID..."
                        class="w-full pl-9 pr-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5">Set</label>
                <select x-model="f.set_id" @change="doSearch()" class="w-full px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <option value="">All Sets</option>
                    <template x-for="s in sets" :key="s"><option :value="s" x-text="s"></option></template>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5">Color</label>
                <select x-model="f.color" @change="doSearch()" class="w-full px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <option value="">All Colors</option>
                    <template x-for="c in colors" :key="c"><option :value="c" x-text="c"></option></template>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5">Rarity</label>
                <select x-model="f.rarity" @change="doSearch()" class="w-full px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <option value="">All Rarities</option>
                    <template x-for="r in rarities" :key="r"><option :value="r" x-text="r"></option></template>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5">Type</label>
                <select x-model="f.type" @change="doSearch()" class="w-full px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <option value="">All Types</option>
                    <template x-for="t in types" :key="t"><option :value="t" x-text="t"></option></template>
                </select>
            </div>
            <button @click="resetFilters()" class="block w-full text-center text-xs text-dark-400 hover:text-gold-400 transition py-1">Reset all filters</button>
        </div>
    </aside>

    <!-- Card Grid -->
    <div class="flex-1 min-w-0">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-display font-bold text-white">Card Database</h1>
                <p class="text-sm text-dark-400 mt-1"><span x-text="totalFormatted"></span> cards found</p>
            </div>
            <div class="flex items-center gap-3">
                <div x-show="loading" class="flex items-center gap-2 text-dark-400 text-sm">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
                <div class="flex items-center gap-1.5">
                    <i data-lucide="arrow-up-down" class="w-4 h-4 text-dark-400"></i>
                    <select x-model="f.sort" @change="doSearch()"
                        class="px-3 py-1.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                        <option value="set">Set / Number</option>
                        <option value="price">Price (High)</option>
                        <option value="price_asc">Price (Low)</option>
                        <option value="rarity">Rarity</option>
                        <option value="name">Name (A-Z)</option>
                        <option value="name_desc">Name (Z-A)</option>
                        <option value="newest">Newest</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4">
            <template x-for="card in cards" :key="card.id">
                <div class="group card-hover relative">
                    <a :href="'/cards/' + card.card_set_id" class="block">
                        <div class="glass rounded-xl overflow-hidden">
                            <div class="relative aspect-[5/7] bg-dark-700">
                                <img :src="card.card_image_url || ''" alt="" class="w-full h-full object-cover" loading="lazy"
                                     @error="$el.style.display='none'; $el.parentElement.classList.add('skeleton')">
                                <template x-if="ownedCards[card.id]">
                                    <div class="absolute top-1.5 right-1.5 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center shadow-lg">
                                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                </template>
                                <template x-if="card.rarity">
                                    <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 text-[10px] font-bold text-white rounded shadow"
                                          :class="rarityClass(card.rarity)" x-text="card.rarity"></span>
                                </template>
                            </div>
                            <div class="p-2.5">
                                <p class="text-xs font-bold text-white truncate" x-text="card.card_name"></p>
                                <div class="flex items-center justify-between mt-1">
                                    <span class="text-[10px] text-dark-400" x-text="card.card_set_id"></span>
                                    <span x-show="card.market_price" class="text-[10px] font-bold text-gold-400" x-text="'$' + parseFloat(card.market_price || 0).toFixed(2)"></span>
                                </div>
                            </div>
                        </div>
                    </a>
                    <button @click.prevent.stop="addToCollection(card)"
                        class="absolute bottom-14 right-2 w-8 h-8 bg-gold-500 text-dark-900 rounded-full flex items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 translate-y-1 group-hover:translate-y-0 transition-all duration-200 hover:bg-gold-400 hover:scale-110 z-10"
                        :title="ownedCards[card.id] ? ('Owned: ' + ownedCards[card.id] + ' — add another') : 'Add to collection'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"></path></svg>
                    </button>
                </div>
            </template>
        </div>

        <!-- Pagination -->
        <div x-show="totalPages > 1" class="mt-8 flex justify-center gap-2">
            <button @click="goPage(page - 1)" :disabled="page <= 1" class="px-3 py-2 glass rounded-lg text-sm text-dark-300 hover:text-white transition disabled:opacity-30">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            <template x-for="p in pageRange" :key="p">
                <button @click="goPage(p)" :class="p === page ? 'bg-gold-500 text-dark-900 font-bold' : 'glass text-dark-300 hover:text-white'"
                    class="px-3 py-2 rounded-lg text-sm font-medium transition" x-text="p"></button>
            </template>
            <button @click="goPage(page + 1)" :disabled="page >= totalPages" class="px-3 py-2 glass rounded-lg text-sm text-dark-300 hover:text-white transition disabled:opacity-30">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </button>
        </div>
    </div>
</div>

<script>
function cardBrowser() {
    return {
        cards: [], total: 0, page: 1, totalPages: 1, loading: false,
        sidebarOpen: false,
        f: <?= $filtersJson ?>,
        sets: <?= $setsJson ?>,
        colors: <?= $colorsJson ?>,
        rarities: <?= $raritiesJson ?>,
        types: <?= $typesJson ?>,
        ownedCards: <?= $userCardsJson ?>,

        init() {
            const initial = <?= $initialResult ?>;
            this.cards = initial.cards;
            this.total = initial.total;
            this.page = initial.page;
            this.totalPages = initial.total_pages;
        },

        get totalFormatted() {
            return this.total.toLocaleString();
        },

        get pageRange() {
            const pages = [];
            const start = Math.max(1, this.page - 3);
            const end = Math.min(this.totalPages, this.page + 3);
            for (let i = start; i <= end; i++) pages.push(i);
            return pages;
        },

        async doSearch(newPage) {
            this.loading = true;
            if (!newPage) this.page = 1;
            const params = new URLSearchParams();
            if (this.f.q) params.set('q', this.f.q);
            if (this.f.set_id) params.set('set_id', this.f.set_id);
            if (this.f.color) params.set('color', this.f.color);
            if (this.f.rarity) params.set('rarity', this.f.rarity);
            if (this.f.type) params.set('type', this.f.type);
            if (this.f.sort && this.f.sort !== 'set') params.set('sort', this.f.sort);
            params.set('page', String(this.page));

            const url = window.location.pathname + '?' + params.toString();
            window.history.replaceState({}, '', url);

            try {
                const res = await fetch('/api/cards/search?' + params.toString());
                const data = await res.json();
                this.cards = data.cards;
                this.total = data.total;
                this.page = data.page;
                this.totalPages = data.total_pages;
            } catch (e) {}
            this.loading = false;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        goPage(p) {
            if (p < 1 || p > this.totalPages) return;
            this.page = p;
            this.doSearch(true);
        },

        resetFilters() {
            this.f = { q: '', set_id: '', color: '', rarity: '', type: '', sort: 'set' };
            this.doSearch();
        },

        async addToCollection(card) {
            const res = await apiPost('/collection/add', { card_id: card.id, quantity: 1 });
            if (res.success) {
                this.ownedCards[card.id] = (this.ownedCards[card.id] || 0) + 1;
                showToast(card.card_name + ' added');
            } else {
                showToast(res.message || 'Error', 'error');
            }
        },

        rarityClass(r) {
            const m = { SEC: 'bg-gradient-to-r from-gold-500 to-amber-600', SP: 'bg-gradient-to-r from-purple-500 to-pink-500', SR: 'bg-gradient-to-r from-blue-500 to-cyan-500', R: 'bg-gradient-to-r from-emerald-500 to-green-500', L: 'bg-gradient-to-r from-gold-500 to-amber-500' };
            return m[r] || 'bg-gradient-to-r from-gray-500 to-gray-600';
        }
    }
}
</script>
