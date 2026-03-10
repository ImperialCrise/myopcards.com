/* Orders Alpine.js Components */

document.addEventListener('alpine:init', () => {

    /* ============================================================
     * ordersList — Orders index page
     * ============================================================ */
    Alpine.data('ordersList', () => ({
        buyerOrders: [],
        sellerOrders: [],
        mainTab: 'purchases',
        statusFilter: '',
        statuses: ['paid', 'shipped', 'completed', 'disputed', 'cancelled'],

        init() {
            const d = window.__PAGE_DATA || {};
            this.buyerOrders = d.buyerOrders || [];
            this.sellerOrders = d.sellerOrders || [];
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        get filteredOrders() {
            const orders = this.mainTab === 'purchases' ? this.buyerOrders : this.sellerOrders;
            if (!this.statusFilter) return orders;
            return orders.filter(o => o.escrow_status === this.statusFilter);
        },

        formatDate(d) {
            if (!d) return '';
            const date = new Date((d + '').replace(' ', 'T'));
            if (isNaN(date)) return d;
            return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }));

    /* ============================================================
     * orderDetail — Order detail page
     * ============================================================ */
    Alpine.data('orderDetail', () => ({
        order: {},
        reviews: [],
        dispute: null,
        isBuyer: false,
        isSeller: false,
        actionLoading: false,
        shippingCarrier: '',
        trackingNumber: '',
        reviewRating: 0,
        reviewComment: '',
        reviewSubmitting: false,
        disputeModalOpen: false,
        disputeReason: '',
        disputeDescription: '',
        disputeSubmitting: false,
        autoCompleteCountdown: '',
        countdownTimer: null,

        init() {
            const d = window.__PAGE_DATA || {};
            this.order = d.order || {};
            this.reviews = d.reviews || [];
            this.dispute = d.dispute || null;
            this.isBuyer = d.isBuyer || false;
            this.isSeller = d.isSeller || false;
            if (this.order.auto_complete_at) this.startCountdown();
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        get hasReviewed() {
            return this.reviews.some(r => r.is_own);
        },

        get escrowSteps() {
            const statusOrder = ['paid', 'shipped', 'delivered', 'completed'];
            const currentIdx = statusOrder.indexOf(this.order.escrow_status);
            return [
                { key: 'paid', label: 'Paid', date: this.order.paid_at ? this.formatDate(this.order.paid_at) : '' },
                { key: 'shipped', label: 'Shipped', date: this.order.shipped_at ? this.formatDate(this.order.shipped_at) : '' },
                { key: 'delivered', label: 'Delivered', date: this.order.delivered_at ? this.formatDate(this.order.delivered_at) : '' },
                { key: 'completed', label: 'Completed', date: this.order.completed_at ? this.formatDate(this.order.completed_at) : '' }
            ].map((step, idx) => {
                const completed = idx < currentIdx || this.order.escrow_status === 'completed';
                const active = idx === currentIdx && this.order.escrow_status !== 'completed';
                return {
                    ...step,
                    completed,
                    active,
                    dotClass: completed ? 'completed' : active ? 'active' : 'pending',
                    lineClass: completed ? 'completed' : ''
                };
            });
        },

        startCountdown() {
            const update = () => {
                const target = new Date((this.order.auto_complete_at + '').replace(' ', 'T'));
                const now = new Date();
                const diff = Math.max(0, target - now);
                if (diff <= 0) {
                    this.autoCompleteCountdown = 'Auto-completing...';
                    clearInterval(this.countdownTimer);
                    return;
                }
                const days = Math.floor(diff / 86400000);
                const hours = Math.floor((diff % 86400000) / 3600000);
                const mins = Math.floor((diff % 3600000) / 60000);
                const parts = [];
                if (days > 0) parts.push(days + 'd');
                if (hours > 0) parts.push(hours + 'h');
                parts.push(mins + 'm');
                this.autoCompleteCountdown = parts.join(' ');
            };
            update();
            this.countdownTimer = setInterval(update, 60000);
        },

        async markShipped() {
            if (!this.trackingNumber && !confirm('Ship without a tracking number?')) return;
            this.actionLoading = true;
            try {
                const data = await apiPost('/api/orders/' + this.order.id + '/ship', {
                    carrier: this.shippingCarrier,
                    tracking_number: this.trackingNumber
                });
                if (data.success) {
                    this.order.escrow_status = 'shipped';
                    this.order.shipped_at = new Date().toISOString();
                    this.order.shipping_carrier = this.shippingCarrier;
                    this.order.tracking_number = this.trackingNumber;
                    if (data.auto_complete_at) this.order.auto_complete_at = data.auto_complete_at;
                    showToast('Order marked as shipped!', 'success');
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                } else {
                    showToast(data.message || 'Failed to mark as shipped', 'error');
                }
            } catch (e) {
                showToast('An error occurred', 'error');
            }
            this.actionLoading = false;
        },

        async confirmDelivery() {
            if (!confirm('Confirm you received the item? Funds will be released to the seller.')) return;
            this.actionLoading = true;
            try {
                const data = await apiPost('/api/orders/' + this.order.id + '/confirm-delivery', {});
                if (data.success) {
                    this.order.escrow_status = 'completed';
                    this.order.completed_at = new Date().toISOString();
                    showToast('Order completed! Funds released to seller.', 'success');
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                } else {
                    showToast(data.message || 'Failed to confirm delivery', 'error');
                }
            } catch (e) {
                showToast('An error occurred', 'error');
            }
            this.actionLoading = false;
        },

        async submitReview() {
            if (this.reviewRating === 0) { showToast('Please select a rating', 'error'); return; }
            this.reviewSubmitting = true;
            try {
                const data = await apiPost('/api/orders/' + this.order.id + '/review', {
                    rating: this.reviewRating,
                    comment: this.reviewComment
                });
                if (data.success) {
                    if (data.review) {
                        data.review.is_own = true;
                        this.reviews.push(data.review);
                    }
                    showToast('Review submitted!', 'success');
                    this.reviewRating = 0;
                    this.reviewComment = '';
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                } else {
                    showToast(data.message || 'Failed to submit review', 'error');
                }
            } catch (e) {
                showToast('An error occurred', 'error');
            }
            this.reviewSubmitting = false;
        },

        async openDispute() {
            if (!this.disputeReason || !this.disputeDescription) return;
            this.disputeSubmitting = true;
            try {
                const data = await apiPost('/api/orders/' + this.order.id + '/dispute', {
                    reason: this.disputeReason,
                    description: this.disputeDescription
                });
                if (data.success) {
                    this.order.escrow_status = 'disputed';
                    this.dispute = {
                        reason: this.disputeReason,
                        description: this.disputeDescription,
                        status: 'open'
                    };
                    this.disputeModalOpen = false;
                    showToast('Dispute opened. Our team will review it shortly.', 'info');
                    this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
                } else {
                    showToast(data.message || 'Failed to open dispute', 'error');
                }
            } catch (e) {
                showToast('An error occurred', 'error');
            }
            this.disputeSubmitting = false;
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

});
