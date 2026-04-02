function mcCardsLog() {
    if (window.__MYOPCARDS_DEBUG_CARDS) {
        var a = Array.prototype.slice.call(arguments);
        a.unshift('[myopcards:cards]');
        console.log.apply(console, a);
    }
}

function cardBrowser() {
    var data = window.__PAGE_DATA || {};
    var dFilters = data.filters || {};
    mcCardsLog('cardBrowser() invoked; __PAGE_DATA keys:', data && Object.keys(data));
    return {
        cards: [], total: 0, page: 1, totalPages: 1, loading: false,
        sidebarOpen: false,
        f: {
            q: dFilters.q ?? '',
            set_id: dFilters.set_id ?? '',
            color: dFilters.color ?? '',
            rarity: dFilters.rarity ?? '',
            type: dFilters.type ?? '',
            sort: dFilters.sort ?? 'set',
        },
        sets: data.sets || [],
        colors: data.colors || [],
        rarities: data.rarities || [],
        types: data.types || [],
        ownedCards: data.ownedCards || {},

        init() {
            if (this._mcCardsInitDone) {
                mcCardsLog('init() skipped (already ran — Alpine or transition can fire twice)');
                return;
            }
            this._mcCardsInitDone = true;

            this.applyFiltersFromUrl();
            this.ensureSelectOptionsMatchFilters();
            var initial = data.initialResult || { cards: [], total: 0, page: 1, total_pages: 1 };
            this.cards = initial.cards;
            this.total = initial.total;
            this.page = initial.page;
            this.totalPages = initial.total_pages;
            mcCardsLog('init OK; f =', JSON.parse(JSON.stringify(this.f)), 'total', this.total);

            var self = this;
            this.$nextTick(function () {
                self._syncFilterSelectDom();
                self.$nextTick(function () {
                    self._syncFilterSelectDom();
                });
            });
        },

        /** Native <select> display can desync from Alpine when options come from x-for; force .value after DOM updates. */
        _syncFilterSelectDom() {
            var pairs = [['setSel', 'set_id'], ['colorSel', 'color'], ['raritySel', 'rarity'], ['typeSel', 'type'], ['sortSel', 'sort']];
            for (var i = 0; i < pairs.length; i++) {
                var ref = pairs[i][0];
                var key = pairs[i][1];
                var el = this.$refs[ref];
                if (!el || el.tagName !== 'SELECT') {
                    continue;
                }
                var want = this.f[key] != null ? String(this.f[key]) : '';
                if (el.value !== want) {
                    el.value = want;
                    mcCardsLog('DOM sync', ref, '=', want);
                }
            }
        },

        /** Query string is the source of truth on full page load (bookmark / reload). */
        applyFiltersFromUrl() {
            try {
                var p = new URLSearchParams(window.location.search || '');
                if (window.__MYOPCARDS_DEBUG_CARDS) {
                    mcCardsLog('URL set_id=', p.get('set_id'), 'type=', p.get('type'));
                }
                if (p.has('q')) {
                    this.f.q = p.get('q') || '';
                }
                if (p.has('set_id')) {
                    this.f.set_id = p.get('set_id') || '';
                }
                if (p.has('color')) {
                    this.f.color = p.get('color') || '';
                }
                if (p.has('rarity')) {
                    this.f.rarity = p.get('rarity') || '';
                }
                if (p.has('type')) {
                    this.f.type = p.get('type') || '';
                }
                if (p.has('sort')) {
                    this.f.sort = p.get('sort') || 'set';
                }
            } catch (e) {
                console.warn('[myopcards:cards] applyFiltersFromUrl error', e);
            }
        },

        /** <select> only shows x-model when a matching <option> exists (URL/deep-link values may be missing from DISTINCT lists). */
        ensureSelectOptionsMatchFilters() {
            var prependIfMissing = function (arr, val) {
                if (val === undefined || val === null || String(val).trim() === '') return arr;
                var s = String(val);
                if (arr.indexOf(s) === -1) return [s].concat(arr);
                return arr;
            };
            this.sets = prependIfMissing(this.sets, this.f.set_id);
            this.colors = prependIfMissing(this.colors, this.f.color);
            this.rarities = prependIfMissing(this.rarities, this.f.rarity);
            this.types = prependIfMissing(this.types, this.f.type);
        },

        get totalFormatted() {
            return this.total.toLocaleString();
        },

        get pageRange() {
            var pages = [];
            var start = Math.max(1, this.page - 3);
            var end = Math.min(this.totalPages, this.page + 3);
            for (var i = start; i <= end; i++) pages.push(i);
            return pages;
        },

        async doSearch(newPage) {
            this.loading = true;
            if (!newPage) this.page = 1;
            var params = new URLSearchParams();
            if (this.f.q) params.set('q', this.f.q);
            if (this.f.set_id) params.set('set_id', this.f.set_id);
            if (this.f.color) params.set('color', this.f.color);
            if (this.f.rarity) params.set('rarity', this.f.rarity);
            if (this.f.type) params.set('type', this.f.type);
            if (this.f.sort && this.f.sort !== 'set') params.set('sort', this.f.sort);
            params.set('page', String(this.page));

            var url = window.location.pathname + '?' + params.toString();
            window.history.replaceState({}, '', url);

            try {
                var res = await fetch('/api/cards/search?' + params.toString());
                var result = await res.json();
                this.cards = result.cards;
                this.total = result.total;
                this.page = result.page;
                this.totalPages = result.total_pages;
            } catch (e) {
                console.warn('[myopcards:cards] doSearch fetch error', e);
            }
            this.loading = false;
            var self = this;
            this.$nextTick(function () {
                self._syncFilterSelectDom();
            });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        goPage(p) {
            if (p < 1 || p > this.totalPages) return;
            this.page = p;
            this.doSearch(true);
        },

        resetFilters() {
            Object.assign(this.f, { q: '', set_id: '', color: '', rarity: '', type: '', sort: 'set' });
            this.doSearch();
        },

        async addToCollection(card) {
            var res = await apiPost('/collection/add', { card_id: card.id, quantity: 1 });
            if (res.success) {
                this.ownedCards[card.id] = (this.ownedCards[card.id] || 0) + 1;
                showToast(card.card_name + ' added');
            } else {
                showToast(res.message || 'Error', 'error');
            }
        },

        rarityClass(r) {
            var m = { SEC: 'bg-gradient-to-r from-gold-500 to-amber-600', SP: 'bg-gradient-to-r from-purple-500 to-pink-500', SR: 'bg-gradient-to-r from-blue-500 to-cyan-500', R: 'bg-gradient-to-r from-emerald-500 to-green-500', L: 'bg-gradient-to-r from-gold-500 to-amber-500' };
            return m[r] || 'bg-gradient-to-r from-gray-500 to-gray-600';
        }
    }
}
