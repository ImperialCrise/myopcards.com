function cardBrowser() {
    var data = window.__PAGE_DATA || {};
    return {
        cards: [], total: 0, page: 1, totalPages: 1, loading: false,
        sidebarOpen: false,
        f: data.filters || {},
        sets: data.sets || [],
        colors: data.colors || [],
        rarities: data.rarities || [],
        types: data.types || [],
        ownedCards: data.ownedCards || {},

        init() {
            var initial = data.initialResult || { cards: [], total: 0, page: 1, total_pages: 1 };
            this.cards = initial.cards;
            this.total = initial.total;
            this.page = initial.page;
            this.totalPages = initial.total_pages;
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
