/* Wallet Alpine.js Components */

document.addEventListener('alpine:init', () => {

    Alpine.data('walletDashboard', () => ({
        wallet: { available: 0, reserved: 0, pending: 0 },
        transactions: [],
        depositModalOpen: false,
        withdrawModalOpen: false,
        depositAmount: '',
        withdrawAmount: '',
        depositProcessing: false,
        withdrawProcessing: false,
        stripeError: '',
        stripe: null,
        cardElement: null,

        init() {
            const d = window.__PAGE_DATA || {};
            this.wallet = d.wallet || { available: 0, reserved: 0, pending: 0 };
            this.transactions = d.transactions || [];
            this.initStripe();
            this.$nextTick(() => { if (window.lucide) lucide.createIcons(); });
        },

        get totalBalance() {
            return parseFloat(this.wallet.available || 0) + parseFloat(this.wallet.reserved || 0) + parseFloat(this.wallet.pending || 0);
        },

        initStripe() {
            const key = document.getElementById('stripe-key')?.value;
            if (!key || typeof Stripe === 'undefined') return;
            this.stripe = Stripe(key);
            const elements = this.stripe.elements({
                appearance: {
                    theme: 'night',
                    variables: {
                        colorPrimary: '#d4a853',
                        colorBackground: '#111827',
                        colorText: '#b8cfe0',
                        colorDanger: '#f87171',
                        fontFamily: 'Inter, system-ui, sans-serif',
                        borderRadius: '8px'
                    }
                }
            });
            this.cardElement = elements.create('card', {
                style: {
                    base: {
                        fontSize: '14px',
                        color: '#b8cfe0',
                        '::placeholder': { color: '#4a6480' }
                    }
                }
            });
            this.$watch('depositModalOpen', (open) => {
                if (open) {
                    this.$nextTick(() => {
                        const el = document.getElementById('stripe-card-element');
                        if (el && this.cardElement) {
                            this.cardElement.mount('#stripe-card-element');
                        }
                    });
                }
            });
        },

        async processDeposit() {
            if (!this.depositAmount || this.depositAmount <= 0) return;
            if (!this.stripe || !this.cardElement) {
                showToast('Payment system not initialized', 'error');
                return;
            }
            this.depositProcessing = true;
            this.stripeError = '';
            try {
                // Create payment intent on server
                const intentData = await apiPost('/api/wallet/deposit', {
                    amount: this.depositAmount
                });
                if (!intentData.success || !intentData.client_secret) {
                    this.stripeError = intentData.message || 'Failed to create payment';
                    this.depositProcessing = false;
                    return;
                }
                // Confirm payment with Stripe
                const { error, paymentIntent } = await this.stripe.confirmCardPayment(intentData.client_secret, {
                    payment_method: { card: this.cardElement }
                });
                if (error) {
                    this.stripeError = error.message;
                    this.depositProcessing = false;
                    return;
                }
                if (paymentIntent.status === 'succeeded') {
                    // Confirm deposit on server
                    const confirmData = await apiPost('/api/wallet/deposit/confirm', {
                        payment_intent_id: paymentIntent.id
                    });
                    if (confirmData.success) {
                        this.wallet.available = parseFloat(this.wallet.available) + parseFloat(this.depositAmount);
                        if (confirmData.transaction) this.transactions.unshift(confirmData.transaction);
                        showToast('Deposit successful! $' + parseFloat(this.depositAmount).toFixed(2) + ' added to your wallet.', 'success');
                        this.depositModalOpen = false;
                        this.depositAmount = '';
                    } else {
                        showToast(confirmData.message || 'Deposit confirmation failed', 'error');
                    }
                }
            } catch (e) {
                this.stripeError = 'Payment failed. Please try again.';
                console.error('Deposit error:', e);
            }
            this.depositProcessing = false;
        },

        async requestWithdraw() {
            if (!this.withdrawAmount || this.withdrawAmount < 20) {
                showToast('Minimum withdrawal is $20.00', 'error');
                return;
            }
            if (this.withdrawAmount > parseFloat(this.wallet.available)) {
                showToast('Insufficient available balance', 'error');
                return;
            }
            this.withdrawProcessing = true;
            try {
                const data = await apiPost('/api/wallet/withdraw', {
                    amount: this.withdrawAmount
                });
                if (data.success) {
                    this.wallet.available = parseFloat(this.wallet.available) - parseFloat(this.withdrawAmount);
                    this.wallet.pending = parseFloat(this.wallet.pending) + parseFloat(this.withdrawAmount);
                    if (data.transaction) this.transactions.unshift(data.transaction);
                    showToast('Withdrawal request submitted for $' + parseFloat(this.withdrawAmount).toFixed(2), 'success');
                    this.withdrawModalOpen = false;
                    this.withdrawAmount = '';
                } else {
                    showToast(data.message || 'Withdrawal failed', 'error');
                }
            } catch (e) {
                showToast('An error occurred', 'error');
            }
            this.withdrawProcessing = false;
        },

        txTypeClass(type) {
            const map = {
                deposit: 'bg-green-500/20 text-green-400',
                withdrawal: 'bg-blue-500/20 text-blue-400',
                purchase: 'bg-red-500/20 text-red-400',
                sale: 'bg-gold-500/20 text-gold-400',
                escrow_hold: 'bg-yellow-500/20 text-yellow-400',
                escrow_release: 'bg-green-500/20 text-green-400',
                refund: 'bg-purple-500/20 text-purple-400',
                fee: 'bg-gray-500/20 text-gray-400'
            };
            return map[type] || 'bg-gray-500/20 text-gray-400';
        },

        formatDate(d) {
            if (!d) return '';
            const date = new Date((d + '').replace(' ', 'T'));
            if (isNaN(date)) return d;
            return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        }
    }));

});
