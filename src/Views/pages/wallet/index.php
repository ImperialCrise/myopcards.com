<?php
$walletJson = json_encode($wallet ?? ['available' => 0, 'reserved' => 0, 'pending' => 0], JSON_HEX_APOS | JSON_HEX_TAG);
$transactionsJson = json_encode($transactions ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
?>

<div class="space-y-6" x-data="walletDashboard">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-display font-bold text-white flex items-center gap-3">
                <i data-lucide="wallet" class="w-7 h-7 text-gold-400"></i> <?= t('wallet.title', 'Wallet') ?>
            </h1>
            <p class="text-sm text-dark-400 mt-1"><?= t('wallet.subtitle', 'Manage your funds for marketplace transactions') ?></p>
        </div>
        <div class="flex gap-2">
            <button @click="depositModalOpen = true" class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition shadow-lg shadow-gold-500/10">
                <i data-lucide="plus-circle" class="w-4 h-4"></i> <?= t('wallet.deposit', 'Deposit') ?>
            </button>
            <button @click="withdrawModalOpen = true" class="flex items-center gap-2 px-4 py-2 glass rounded-lg text-sm text-dark-300 hover:text-white transition">
                <i data-lucide="minus-circle" class="w-4 h-4"></i> <?= t('wallet.withdraw', 'Withdraw') ?>
            </button>
        </div>
    </div>

    <!-- Balance Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass rounded-xl p-5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-green-500/20 flex items-center justify-center">
                    <i data-lucide="circle-dollar-sign" class="w-4 h-4 text-green-400"></i>
                </div>
                <span class="text-xs text-dark-400 font-bold uppercase tracking-wider"><?= t('wallet.available', 'Available') ?></span>
            </div>
            <p class="text-2xl font-display font-bold text-green-400" x-text="'$' + parseFloat(wallet.available).toFixed(2)"></p>
        </div>
        <div class="glass rounded-xl p-5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-yellow-500/20 flex items-center justify-center">
                    <i data-lucide="lock" class="w-4 h-4 text-yellow-400"></i>
                </div>
                <span class="text-xs text-dark-400 font-bold uppercase tracking-wider"><?= t('wallet.reserved', 'In Escrow') ?></span>
            </div>
            <p class="text-2xl font-display font-bold text-yellow-400" x-text="'$' + parseFloat(wallet.reserved).toFixed(2)"></p>
        </div>
        <div class="glass rounded-xl p-5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center">
                    <i data-lucide="clock" class="w-4 h-4 text-blue-400"></i>
                </div>
                <span class="text-xs text-dark-400 font-bold uppercase tracking-wider"><?= t('wallet.pending', 'Pending') ?></span>
            </div>
            <p class="text-2xl font-display font-bold text-blue-400" x-text="'$' + parseFloat(wallet.pending).toFixed(2)"></p>
        </div>
        <div class="glass rounded-xl p-5">
            <div class="flex items-center gap-2 mb-2">
                <div class="w-8 h-8 rounded-lg bg-gold-500/20 flex items-center justify-center">
                    <i data-lucide="coins" class="w-4 h-4 text-gold-400"></i>
                </div>
                <span class="text-xs text-dark-400 font-bold uppercase tracking-wider"><?= t('wallet.total', 'Total') ?></span>
            </div>
            <p class="text-2xl font-display font-bold text-gold-400" x-text="'$' + totalBalance.toFixed(2)"></p>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="receipt" class="w-5 h-5 text-dark-400"></i> <?= t('wallet.transaction_history', 'Transaction History') ?>
        </h2>
        <template x-if="transactions.length === 0">
            <div class="text-center py-12">
                <i data-lucide="receipt" class="w-10 h-10 text-dark-400 mx-auto mb-3"></i>
                <p class="text-sm text-dark-400"><?= t('wallet.no_transactions', 'No transactions yet') ?></p>
            </div>
        </template>
        <div class="overflow-x-auto" x-show="transactions.length > 0">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-dark-400 uppercase border-b border-dark-600">
                        <th class="text-left pb-3 pr-4"><?= t('wallet.date', 'Date') ?></th>
                        <th class="text-left pb-3 pr-4"><?= t('wallet.type', 'Type') ?></th>
                        <th class="text-left pb-3 pr-4"><?= t('wallet.description', 'Description') ?></th>
                        <th class="text-right pb-3 pr-4"><?= t('wallet.amount', 'Amount') ?></th>
                        <th class="text-right pb-3"><?= t('wallet.balance', 'Balance') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="tx in transactions" :key="tx.id">
                        <tr class="border-b border-dark-700/50">
                            <td class="py-3 pr-4 text-dark-400 text-xs whitespace-nowrap" x-text="formatDate(tx.created_at)"></td>
                            <td class="py-3 pr-4">
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full" :class="txTypeClass(tx.type)" x-text="tx.type"></span>
                            </td>
                            <td class="py-3 pr-4 text-dark-300" x-text="tx.description"></td>
                            <td class="py-3 pr-4 text-right font-bold" :class="tx.amount >= 0 ? 'text-green-400' : 'text-red-400'"
                                x-text="(tx.amount >= 0 ? '+' : '') + '$' + Math.abs(tx.amount).toFixed(2)"></td>
                            <td class="py-3 text-right text-dark-300" x-text="'$' + parseFloat(tx.balance_after).toFixed(2)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Deposit Modal -->
    <div x-show="depositModalOpen" x-transition.opacity x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-dark-900/80 backdrop-blur-sm" @click.self="depositModalOpen = false">
        <div class="glass rounded-2xl p-6 w-full max-w-md" @click.stop>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-display font-bold text-white"><?= t('wallet.deposit_funds', 'Deposit Funds') ?></h3>
                <button @click="depositModalOpen = false" class="text-dark-400 hover:text-white transition"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5"><?= t('wallet.amount', 'Amount') ?></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-dark-400 font-bold">$</span>
                        <input type="number" x-model="depositAmount" min="1" step="0.01" placeholder="0.00"
                            class="w-full pl-8 pr-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition">
                    </div>
                    <div class="flex gap-2 mt-2">
                        <button @click="depositAmount = 10" class="px-3 py-1 glass rounded text-xs text-dark-300 hover:text-white transition">$10</button>
                        <button @click="depositAmount = 25" class="px-3 py-1 glass rounded text-xs text-dark-300 hover:text-white transition">$25</button>
                        <button @click="depositAmount = 50" class="px-3 py-1 glass rounded text-xs text-dark-300 hover:text-white transition">$50</button>
                        <button @click="depositAmount = 100" class="px-3 py-1 glass rounded text-xs text-dark-300 hover:text-white transition">$100</button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5"><?= t('wallet.card_details', 'Card Details') ?></label>
                    <div id="stripe-card-element" class="px-3 py-3 bg-dark-800 border border-dark-600 rounded-lg"></div>
                    <p x-show="stripeError" class="text-xs text-red-400 mt-1" x-text="stripeError"></p>
                </div>
                <button @click="processDeposit()" :disabled="depositProcessing || !depositAmount || depositAmount <= 0"
                    class="w-full py-3 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition disabled:opacity-50 flex items-center justify-center gap-2">
                    <span x-show="!depositProcessing"><?= t('wallet.deposit_now', 'Deposit Now') ?></span>
                    <span x-show="depositProcessing" class="flex items-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <?= t('wallet.processing', 'Processing...') ?>
                    </span>
                </button>
                <p class="text-[10px] text-dark-500 text-center"><?= t('wallet.stripe_note', 'Payments processed securely by Stripe') ?></p>
            </div>
        </div>
    </div>

    <!-- Withdraw Modal -->
    <div x-show="withdrawModalOpen" x-transition.opacity x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-dark-900/80 backdrop-blur-sm" @click.self="withdrawModalOpen = false">
        <div class="glass rounded-2xl p-6 w-full max-w-md" @click.stop>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-display font-bold text-white"><?= t('wallet.withdraw_funds', 'Withdraw Funds') ?></h3>
                <button @click="withdrawModalOpen = false" class="text-dark-400 hover:text-white transition"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5"><?= t('wallet.amount', 'Amount') ?></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-dark-400 font-bold">$</span>
                        <input type="number" x-model="withdrawAmount" min="20" step="0.01" placeholder="20.00"
                            class="w-full pl-8 pr-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition">
                    </div>
                    <p class="text-xs text-dark-400 mt-1">
                        <?= t('wallet.available_balance', 'Available:') ?>
                        <span class="text-green-400 font-bold" x-text="'$' + parseFloat(wallet.available).toFixed(2)"></span>
                        &middot; <?= t('wallet.minimum', 'Minimum: $20.00') ?>
                    </p>
                </div>
                <div class="p-3 bg-blue-500/10 border border-blue-500/20 rounded-lg">
                    <p class="text-xs text-blue-400 flex items-start gap-2">
                        <i data-lucide="info" class="w-4 h-4 flex-shrink-0 mt-0.5"></i>
                        <?= t('wallet.withdraw_note', 'Withdrawals are processed manually within 1-3 business days. You will receive payment via your registered PayPal or bank account.') ?>
                    </p>
                </div>
                <button @click="requestWithdraw()" :disabled="withdrawProcessing || !withdrawAmount || withdrawAmount < 20 || withdrawAmount > parseFloat(wallet.available)"
                    class="w-full py-3 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition disabled:opacity-50 flex items-center justify-center gap-2">
                    <span x-show="!withdrawProcessing"><?= t('wallet.request_withdrawal', 'Request Withdrawal') ?></span>
                    <span x-show="withdrawProcessing"><?= t('wallet.processing', 'Processing...') ?></span>
                </button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="stripe-key" value="<?= htmlspecialchars($stripePublishableKey ?? '') ?>">
<script src="https://js.stripe.com/v3/"></script>
<script>
window.__PAGE_DATA = {
    wallet: <?= $walletJson ?>,
    transactions: <?= $transactionsJson ?>
};
</script>
<script src="/assets/js/pages/wallet.js"></script>
