<?php
$buyerOrdersJson = json_encode($buyerOrders ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$sellerOrdersJson = json_encode($sellerOrders ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
?>

<div class="space-y-6" x-data="ordersList">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-display font-bold text-white flex items-center gap-3">
            <i data-lucide="package" class="w-7 h-7 text-gold-400"></i> <?= t('orders.title', 'Orders') ?>
        </h1>
        <p class="text-sm text-dark-400 mt-1"><?= t('orders.subtitle', 'Track your purchases and sales') ?></p>
    </div>

    <!-- Main Tabs: Purchases / Sales -->
    <div class="flex gap-1 glass rounded-xl p-1">
        <button @click="mainTab = 'purchases'" :class="mainTab === 'purchases' ? 'bg-gold-500/20 text-gold-400' : 'text-dark-400 hover:text-white'"
            class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2">
            <i data-lucide="shopping-bag" class="w-4 h-4"></i> <?= t('orders.my_purchases', 'My Purchases') ?>
            <span class="px-1.5 py-0.5 bg-dark-800 rounded-full text-[10px] font-bold" x-text="buyerOrders.length"></span>
        </button>
        <button @click="mainTab = 'sales'" :class="mainTab === 'sales' ? 'bg-gold-500/20 text-gold-400' : 'text-dark-400 hover:text-white'"
            class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2">
            <i data-lucide="tag" class="w-4 h-4"></i> <?= t('orders.my_sales', 'My Sales') ?>
            <span class="px-1.5 py-0.5 bg-dark-800 rounded-full text-[10px] font-bold" x-text="sellerOrders.length"></span>
        </button>
    </div>

    <!-- Status Filter -->
    <div class="flex gap-2 overflow-x-auto pb-1">
        <button @click="statusFilter = ''" :class="statusFilter === '' ? 'bg-gold-500/20 text-gold-400 border-gold-500/30' : 'text-dark-400 hover:text-white'"
            class="px-3 py-1.5 glass rounded-lg text-xs font-medium transition whitespace-nowrap border border-transparent">
            <?= t('orders.all', 'All') ?>
        </button>
        <template x-for="status in statuses" :key="status">
            <button @click="statusFilter = status" :class="statusFilter === status ? 'bg-gold-500/20 text-gold-400 border-gold-500/30' : 'text-dark-400 hover:text-white'"
                class="px-3 py-1.5 glass rounded-lg text-xs font-medium transition whitespace-nowrap border border-transparent capitalize" x-text="status">
            </button>
        </template>
    </div>

    <!-- Orders Grid -->
    <div class="space-y-3">
        <template x-for="order in filteredOrders" :key="order.id">
            <a :href="'/orders/' + order.id" class="block glass rounded-xl p-4 hover:bg-dark-800/30 transition">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <img :src="cardImgSrc(order.card_image_url)" :data-ext-src="order.card_image_url" class="w-14 h-20 rounded-lg object-cover bg-dark-700" onerror="cardImgErr(this)" loading="lazy">
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <p class="text-white font-bold truncate" x-text="order.card_name"></p>
                            <span class="status-badge" :class="'status-' + order.escrow_status" x-text="order.escrow_status"></span>
                        </div>
                        <p class="text-xs text-dark-400 mt-0.5">
                            <?= t('orders.order', 'Order') ?> #<span x-text="order.id"></span>
                            &middot; <span x-text="formatDate(order.created_at)"></span>
                        </p>
                        <p class="text-xs text-dark-400 mt-0.5">
                            <template x-if="mainTab === 'purchases'">
                                <span><?= t('orders.seller', 'Seller:') ?> <span class="text-white" x-text="order.seller_username"></span></span>
                            </template>
                            <template x-if="mainTab === 'sales'">
                                <span><?= t('orders.buyer', 'Buyer:') ?> <span class="text-white" x-text="order.buyer_username"></span></span>
                            </template>
                        </p>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="text-gold-400 font-bold" x-text="'$' + parseFloat(order.total_paid).toFixed(2)"></p>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-dark-400 mt-1 ml-auto"></i>
                    </div>
                </div>
            </a>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="filteredOrders.length === 0" class="text-center py-16">
        <div class="w-16 h-16 rounded-2xl bg-dark-700/50 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="package" class="w-8 h-8 text-dark-400"></i>
        </div>
        <h3 class="text-lg font-display font-bold text-dark-300"><?= t('orders.no_orders', 'No orders yet') ?></h3>
        <p class="text-sm text-dark-400 mt-2"><?= t('orders.start_trading', 'Start trading on the marketplace') ?></p>
        <a href="/marketplace" class="inline-flex items-center gap-2 mt-4 px-6 py-2.5 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition">
            <i data-lucide="store" class="w-4 h-4"></i> <?= t('marketplace.browse', 'Browse Marketplace') ?>
        </a>
    </div>
</div>

<script>
window.__PAGE_DATA = {
    buyerOrders: <?= $buyerOrdersJson ?>,
    sellerOrders: <?= $sellerOrdersJson ?>
};
</script>
<script src="/assets/js/pages/orders.js"></script>
