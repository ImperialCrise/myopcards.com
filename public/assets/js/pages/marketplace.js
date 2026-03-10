/* Marketplace Alpine.js Components */

document.addEventListener('alpine:init', () => {

    /* ============================================================
     * marketplaceBrowse — Browse marketplace index
     * ============================================================ */
    Alpine.data('marketplaceBrowse', () => ({
        cards: [],
        recentSales: [],
        sets: [],
        colors: [],
        rarities: [],
        filters: { q: '', set_id: '', rarity: '', color: '', condition: '', price_min: '', price_max: '', sort: 'price_asc' },
        loading: false,
        page: 1,
        totalPages: 1,
        totalResults: 0,

        init() {
            const d = window.__PAGE_DATA || {};
            this.sets = d.sets || [];
            this.colors = d.colors || [];
            this.rarities = d.rarities || [];
            this.recentSales = d.recentSales || [];
            if (d.popularCards && d.popularCards.length) {
                this.cards = d.popularCards;
                this.totalResults = d.popularCards.length;
            } else {
                this.doSearch();
            }
        },

        async doSearch() {
            this.page = 1;
            await this.fetchCards();
        },

        async fetchCards() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                Object.entries(this.filters).forEach(([k, v]) => { if (v) params.set(k, v); });
                params.set('page', this.page);
                const r = await fetch('/api/marketplace/search?' + params.toString());
                const data = await r.json();
                if (data.success !== false) {
                    this.cards = data.cards || data.data || [];
                    this.totalResults = data.total || this.cards.length;
                    this.totalPages = data.total_pages || 1;
                }
            } catch (e) {
                console.error('Marketplace search error:', e);
            }
            this.loading = false;
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        async goPage(p) {
            if (p < 1 || p > this.totalPages) return;
            this.page = p;
            await this.fetchCards();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        get pageRange() {
            const range = [];
            const start = Math.max(1, this.page - 3);
            const end = Math.min(this.totalPages, this.page + 3);
            for (let i = start; i <= end; i++) range.push(i);
            return range;
        },

        resetFilters() {
            this.filters = { q: '', set_id: '', rarity: '', color: '', condition: '', price_min: '', price_max: '', sort: 'price_asc' };
            this.doSearch();
        },

        rarityClass(r) {
            const map = { SEC: 'bg-gradient-to-r from-gold-500 to-amber-600', SP: 'bg-gradient-to-r from-purple-500 to-pink-500', SR: 'bg-gradient-to-r from-blue-500 to-cyan-500', R: 'bg-gradient-to-r from-emerald-500 to-green-500', L: 'bg-gradient-to-r from-gold-500 to-amber-500' };
            return map[r] || 'bg-gradient-to-r from-gray-500 to-gray-600';
        },

        conditionClass(c) {
            const map = { NM: 'bg-green-500/20 text-green-400', LP: 'bg-blue-500/20 text-blue-400', MP: 'bg-yellow-500/20 text-yellow-400', HP: 'bg-orange-500/20 text-orange-400', DMG: 'bg-red-500/20 text-red-400' };
            return map[c] || 'bg-gray-500/20 text-gray-400';
        },

        formatDate(d) {
            if (!d) return '';
            const date = new Date((d + '').replace(' ', 'T'));
            if (isNaN(date)) return d;
            const now = new Date();
            const diff = (now - date) / 1000;
            if (diff < 60) return 'just now';
            if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
            if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
            if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
            return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }));

    /* ============================================================
     * cardMarketplace — Single card marketplace page
     * ============================================================ */
    Alpine.data('cardMarketplace', () => ({
        card: {},
        listings: [],
        stats: {},
        bids: [],
        recentSales: [],
        activeTab: 'listings',
        bidModalOpen: false,
        bidAmount: '',
        bidMessage: '',
        bidSubmitting: false,

        init() {
            const d = window.__PAGE_DATA || {};
            this.card = d.card || {};
            this.listings = d.listings || [];
            this.stats = d.stats || {};
            this.bids = d.bids || [];
            this.recentSales = d.recentSales || [];
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        async buyNow(listing) {
            if (!confirm('Buy this card for $' + parseFloat(listing.price).toFixed(2) + '?')) return;
            try {
                const data = await apiPost('/api/marketplace/buy', { listing_id: listing.id });
                if (data.success) {
                    showToast('Purchase successful! Redirecting to order...', 'success');
                    setTimeout(() => { window.location.href = '/orders/' + data.order_id; }, 1500);
                } else {
                    showToast(data.message || 'Purchase failed', 'error');
                }
            } catch (e) {
                showToast('An error occurred', 'error');
            }
        },

        async placeBid() {
            if (!this.bidAmount || this.bidAmount <= 0) return;
            this.bidSubmitting = true;
            try {
                const data = await apiPost('/api/marketplace/bids', {
                    listing_id: this.listings.length ? this.listings[0].id : null,
                    amount: this.bidAmount
                });
                if (data.success) {
                    showToast('Offer submitted successfully', 'success');
                    this.bidModalOpen = false;
                    this.bidAmount = '';
                    this.bidMessage = '';
                    if (data.bid) this.bids.unshift(data.bid);
                } else {
                    showToast(data.message || 'Failed to submit offer', 'error');
                }
            } catch (e) {
                showToast('An error occurred', 'error');
            }
            this.bidSubmitting = false;
        },

        rarityClass(r) {
            const map = { SEC: 'bg-gradient-to-r from-gold-500 to-amber-600', SP: 'bg-gradient-to-r from-purple-500 to-pink-500', SR: 'bg-gradient-to-r from-blue-500 to-cyan-500', R: 'bg-gradient-to-r from-emerald-500 to-green-500', L: 'bg-gradient-to-r from-gold-500 to-amber-500' };
            return map[r] || 'bg-gradient-to-r from-gray-500 to-gray-600';
        },

        conditionClass(c) {
            const map = { NM: 'bg-green-500/20 text-green-400', LP: 'bg-blue-500/20 text-blue-400', MP: 'bg-yellow-500/20 text-yellow-400', HP: 'bg-orange-500/20 text-orange-400', DMG: 'bg-red-500/20 text-red-400' };
            return map[c] || 'bg-gray-500/20 text-gray-400';
        },

        formatDate(d) {
            if (!d) return '';
            const date = new Date((d + '').replace(' ', 'T'));
            if (isNaN(date)) return d;
            return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }));

    /* ============================================================
     * listingDetail — Single listing detail page
     * ============================================================ */
    Alpine.data('listingDetail', () => ({
        listing: {},
        bids: [],
        sellerStats: {},
        images: [],
        activeImageIdx: 0,
        bidAmount: '',
        bidSubmitting: false,
        buying: false,

        init() {
            const d = window.__PAGE_DATA || {};
            this.listing = d.listing || {};
            this.bids = d.bids || [];
            this.sellerStats = d.sellerStats || {};
            this.images = this.listing.images || [];
            if (this.images.length === 0 && this.listing.card_image_url) {
                this.images = [typeof cardImgSrc === 'function' ? cardImgSrc(this.listing.card_image_url) : this.listing.card_image_url];
            }
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        get activeImage() {
            return this.images[this.activeImageIdx] || (typeof cardImgSrc === 'function' ? cardImgSrc(this.listing.card_image_url) : this.listing.card_image_url) || '';
        },

        get buyerFee() {
            return parseFloat(this.listing.price || 0) * 0.05;
        },

        get totalPrice() {
            return parseFloat(this.listing.price || 0) + this.buyerFee + parseFloat(this.listing.shipping_cost || 0);
        },

        async buyNow(listing) {
            if (!confirm('Buy this card for $' + this.totalPrice.toFixed(2) + ' (including fees and shipping)?')) return;
            this.buying = true;
            try {
                const data = await apiPost('/api/marketplace/buy', { listing_id: listing.id });
                if (data.success) {
                    showToast('Purchase successful!', 'success');
                    setTimeout(() => { window.location.href = '/orders/' + data.order_id; }, 1500);
                } else {
                    showToast(data.message || 'Purchase failed', 'error');
                }
            } catch (e) {
                showToast('An error occurred', 'error');
            }
            this.buying = false;
        },

        async placeBid() {
            if (!this.bidAmount || this.bidAmount <= 0) return;
            this.bidSubmitting = true;
            try {
                const data = await apiPost('/api/marketplace/bids', {
                    listing_id: this.listing.id,
                    amount: this.bidAmount
                });
                if (data.success) {
                    showToast('Offer submitted!', 'success');
                    this.bidAmount = '';
                    if (data.bid) this.bids.unshift(data.bid);
                } else {
                    showToast(data.message || 'Failed to submit offer', 'error');
                }
            } catch (e) {
                showToast('An error occurred', 'error');
            }
            this.bidSubmitting = false;
        },

        rarityClass(r) {
            const map = { SEC: 'bg-gradient-to-r from-gold-500 to-amber-600', SP: 'bg-gradient-to-r from-purple-500 to-pink-500', SR: 'bg-gradient-to-r from-blue-500 to-cyan-500', R: 'bg-gradient-to-r from-emerald-500 to-green-500', L: 'bg-gradient-to-r from-gold-500 to-amber-500' };
            return map[r] || 'bg-gradient-to-r from-gray-500 to-gray-600';
        },

        conditionClass(c) {
            const map = { NM: 'bg-green-500/20 text-green-400', LP: 'bg-blue-500/20 text-blue-400', MP: 'bg-yellow-500/20 text-yellow-400', HP: 'bg-orange-500/20 text-orange-400', DMG: 'bg-red-500/20 text-red-400' };
            return map[c] || 'bg-gray-500/20 text-gray-400';
        },

        conditionLabel(c) {
            const map = { NM: 'Near Mint', LP: 'Lightly Played', MP: 'Moderately Played', HP: 'Heavily Played', DMG: 'Damaged' };
            return map[c] || c;
        },

        formatDate(d) {
            if (!d) return '';
            const date = new Date((d + '').replace(' ', 'T'));
            if (isNaN(date)) return d;
            return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }));

    /* ============================================================
     * createListing — Sell page
     * ============================================================ */
    Alpine.data('createListing', () => ({
        cardQuery: '',
        cardResults: [],
        selectedCard: null,
        form: {
            card_id: null,
            condition: 'NM',
            price: '',
            quantity: 1,
            description: '',
            shipping_country: '',
            shipping_cost: 0,
            international_shipping: false
        },
        conditions: [
            { value: 'NM', label: 'Near Mint' },
            { value: 'LP', label: 'Lightly Played' },
            { value: 'MP', label: 'Moderately Played' },
            { value: 'HP', label: 'Heavily Played' },
            { value: 'DMG', label: 'Damaged' }
        ],
        imagePreviews: [],
        imageFiles: [],
        dragOver: false,
        submitting: false,

        init() {
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        async searchCards() {
            if (this.cardQuery.length < 2) { this.cardResults = []; return; }
            try {
                const r = await fetch('/api/cards/search?q=' + encodeURIComponent(this.cardQuery) + '&limit=10');
                const data = await r.json();
                this.cardResults = data.cards || data.data || [];
            } catch (e) {
                this.cardResults = [];
            }
        },

        selectCard(card) {
            this.selectedCard = card;
            this.form.card_id = card.id;
            this.cardQuery = card.card_name;
            this.cardResults = [];
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        clearCard() {
            this.selectedCard = null;
            this.form.card_id = null;
            this.cardQuery = '';
        },

        handleFiles(e) {
            const files = Array.from(e.target.files);
            this.addFiles(files);
        },

        handleDrop(e) {
            this.dragOver = false;
            const files = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
            this.addFiles(files);
        },

        addFiles(files) {
            const remaining = 4 - this.imagePreviews.length;
            files.slice(0, remaining).forEach(file => {
                if (file.size > 5 * 1024 * 1024) { showToast('File too large (max 5MB)', 'error'); return; }
                const url = URL.createObjectURL(file);
                this.imagePreviews.push({ url, file });
                this.imageFiles.push(file);
            });
        },

        removeImage(idx) {
            URL.revokeObjectURL(this.imagePreviews[idx].url);
            this.imagePreviews.splice(idx, 1);
            this.imageFiles.splice(idx, 1);
        },

        get sellerFee() { return parseFloat(this.form.price || 0) * 0.05; },
        get netEarnings() { return Math.max(0, parseFloat(this.form.price || 0) - this.sellerFee); },
        get buyerFeeCalc() { return parseFloat(this.form.price || 0) * 0.05; },
        get buyerTotal() { return parseFloat(this.form.price || 0) + this.buyerFeeCalc + parseFloat(this.form.shipping_cost || 0); },
        get canSubmit() { return this.form.card_id && this.form.condition && this.form.price > 0; },

        async submitListing() {
            if (!this.canSubmit) return;
            this.submitting = true;
            try {
                const fd = new FormData();
                fd.append('card_id', this.form.card_id);
                fd.append('condition', this.form.condition);
                fd.append('price', this.form.price);
                fd.append('quantity', this.form.quantity);
                fd.append('description', this.form.description);
                fd.append('shipping_country', this.form.shipping_country);
                fd.append('shipping_cost', this.form.shipping_cost);
                fd.append('international_shipping', this.form.international_shipping ? 1 : 0);
                this.imageFiles.forEach((f, i) => fd.append('images[' + i + ']', f));
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (csrf) fd.append('csrf_token', csrf);
                const r = await fetch('/api/marketplace/listings', { method: 'POST', body: fd });
                const data = await r.json();
                if (data.success) {
                    showToast('Listing published!', 'success');
                    setTimeout(() => { window.location.href = '/marketplace/listing/' + data.listing_id; }, 1500);
                } else {
                    showToast(data.message || 'Failed to create listing', 'error');
                }
            } catch (e) {
                showToast('An error occurred', 'error');
            }
            this.submitting = false;
        }
    }));

    /* ============================================================
     * myListings — My Listings page
     * ============================================================ */
    Alpine.data('myListings', () => ({
        allListings: [],
        filteredListings: [],
        statusFilter: '',
        tabs: [
            { value: '', label: 'All' },
            { value: 'active', label: 'Active' },
            { value: 'sold', label: 'Sold' },
            { value: 'cancelled', label: 'Cancelled' }
        ],

        init() {
            const d = window.__PAGE_DATA || {};
            this.allListings = d.listings || [];
            this.filteredListings = [...this.allListings];
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        filterListings() {
            if (!this.statusFilter) {
                this.filteredListings = [...this.allListings];
            } else {
                this.filteredListings = this.allListings.filter(l => l.status === this.statusFilter);
            }
        },

        countByStatus(status) {
            if (!status) return this.allListings.length;
            return this.allListings.filter(l => l.status === status).length;
        },

        async cancelListing(id) {
            if (!confirm('Cancel this listing?')) return;
            try {
                const data = await apiPost('/api/marketplace/listings/' + id + '/cancel', {});
                if (data.success) {
                    const listing = this.allListings.find(l => l.id === id);
                    if (listing) listing.status = 'cancelled';
                    this.filterListings();
                    showToast('Listing cancelled', 'info');
                } else {
                    showToast(data.message || 'Failed to cancel', 'error');
                }
            } catch (e) {
                showToast('An error occurred', 'error');
            }
        },

        conditionClass(c) {
            const map = { NM: 'bg-green-500/20 text-green-400', LP: 'bg-blue-500/20 text-blue-400', MP: 'bg-yellow-500/20 text-yellow-400', HP: 'bg-orange-500/20 text-orange-400', DMG: 'bg-red-500/20 text-red-400' };
            return map[c] || 'bg-gray-500/20 text-gray-400';
        },

        statusClass(s) {
            const map = { active: 'bg-green-500/20 text-green-400', sold: 'bg-gold-500/20 text-gold-400', cancelled: 'bg-gray-500/20 text-gray-400' };
            return map[s] || 'bg-gray-500/20 text-gray-400';
        },

        formatDate(d) {
            if (!d) return '';
            const date = new Date((d + '').replace(' ', 'T'));
            if (isNaN(date)) return d;
            return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }));

    /* ============================================================
     * myBids — My Offers page
     * ============================================================ */
    Alpine.data('myBids', () => ({
        allBids: [],
        filteredBids: [],
        statusFilter: '',
        tabs: [
            { value: '', label: 'All' },
            { value: 'pending', label: 'Pending' },
            { value: 'accepted', label: 'Accepted' },
            { value: 'rejected', label: 'Rejected' },
            { value: 'expired', label: 'Expired' }
        ],

        init() {
            const d = window.__PAGE_DATA || {};
            this.allBids = d.bids || [];
            this.filteredBids = [...this.allBids];
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        filterBids() {
            if (!this.statusFilter) {
                this.filteredBids = [...this.allBids];
            } else {
                this.filteredBids = this.allBids.filter(b => b.status === this.statusFilter);
            }
        },

        countByStatus(status) {
            if (!status) return this.allBids.length;
            return this.allBids.filter(b => b.status === status).length;
        },

        async cancelBid(id) {
            if (!confirm('Cancel this offer?')) return;
            try {
                const data = await apiPost('/api/marketplace/bids/' + id + '/cancel', {});
                if (data.success) {
                    const bid = this.allBids.find(b => b.id === id);
                    if (bid) bid.status = 'cancelled';
                    this.filterBids();
                    showToast('Offer cancelled', 'info');
                } else {
                    showToast(data.message || 'Failed to cancel', 'error');
                }
            } catch (e) {
                showToast('An error occurred', 'error');
            }
        },

        bidStatusClass(s) {
            const map = { pending: 'bg-yellow-500/20 text-yellow-400', accepted: 'bg-green-500/20 text-green-400', rejected: 'bg-red-500/20 text-red-400', expired: 'bg-gray-500/20 text-gray-400', cancelled: 'bg-gray-500/20 text-gray-400' };
            return map[s] || 'bg-gray-500/20 text-gray-400';
        },

        formatDate(d) {
            if (!d) return '';
            const date = new Date((d + '').replace(' ', 'T'));
            if (isNaN(date)) return d;
            return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }));

});
