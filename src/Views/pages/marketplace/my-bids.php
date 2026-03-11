<?php
$bidsJson = json_encode($bids ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
?>

<div class="space-y-6" x-data="myBids">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-display font-bold text-white flex items-center gap-3">
            <i data-lucide="gavel" class="w-7 h-7 text-gold-400"></i> <?= t('marketplace.my_offers', 'My Offers') ?>
        </h1>
        <p class="text-sm text-dark-400 mt-1"><?= t('marketplace.manage_offers', 'Track and manage your marketplace offers') ?></p>
    </div>

    <!-- Status Tabs -->
    <div class="flex gap-1 glass rounded-xl p-1 overflow-x-auto">
        <template x-for="tab in tabs" :key="tab.value">
            <button @click="statusFilter = tab.value; filterBids()"
                :class="statusFilter === tab.value ? 'bg-gold-500/20 text-gold-400' : 'text-dark-400 hover:text-white'"
                class="flex-1 px-3 py-2 rounded-lg text-sm font-medium transition whitespace-nowrap" x-text="tab.label + ' (' + countByStatus(tab.value) + ')'">
            </button>
        </template>
    </div>

    <!-- Bids List -->
    <div class="space-y-3">
        <template x-for="bid in filteredBids" :key="bid.id">
            <div class="glass rounded-xl p-4 flex items-center gap-4">
                <a :href="'/marketplace/listing/' + bid.listing_id" class="flex-shrink-0">
                    <img :src="cardImgSrc(bid.card_image_url)" :data-ext-src="bid.card_image_url" class="w-14 h-20 rounded-lg object-cover bg-dark-700" onerror="cardImgErr(this)" loading="lazy">
                </a>
                <div class="flex-1 min-w-0">
                    <a :href="'/marketplace/listing/' + bid.listing_id" class="hover:text-gold-400 transition">
                        <p class="text-white font-bold truncate" x-text="bid.card_name"></p>
                    </a>
                    <p class="text-xs text-dark-400 mt-0.5">
                        <?= t('marketplace.listed_by', 'Listed by') ?> <span class="text-white" x-text="bid.seller_username"></span>
                        &middot; <span x-text="'$' + parseFloat(bid.listing_price).toFixed(2)"></span>
                    </p>
                    <div class="flex items-center gap-3 mt-1.5">
                        <span class="text-gold-400 font-bold text-sm"><?= t('marketplace.your_offer', 'Your offer:') ?> <span x-text="'$' + parseFloat(bid.amount).toFixed(2)"></span></span>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full" :class="bidStatusClass(bid.status)" x-text="bid.status"></span>
                    </div>
                    <p class="text-[10px] text-dark-500 mt-1">
                        <span x-text="'Placed ' + formatDate(bid.created_at)"></span>
                        <template x-if="bid.expires_at">
                            <span x-text="' · Expires ' + formatDate(bid.expires_at)"></span>
                        </template>
                    </p>
                </div>
                <div class="flex-shrink-0">
                    <template x-if="bid.status === 'pending'">
                        <button @click="cancelBid(bid.id)" class="px-3 py-1.5 glass rounded-lg text-xs font-medium text-red-400 hover:text-red-300 hover:bg-red-500/10 transition">
                            <?= t('marketplace.cancel', 'Cancel') ?>
                        </button>
                    </template>
                    <template x-if="bid.status === 'accepted'">
                        <a :href="'/orders/' + bid.order_id" class="px-3 py-1.5 bg-green-500/20 rounded-lg text-xs font-bold text-green-400 hover:bg-green-500/30 transition">
                            <?= t('marketplace.view_order', 'View Order') ?>
                        </a>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="filteredBids.length === 0" class="text-center py-16">
        <div class="w-16 h-16 rounded-2xl bg-dark-700/50 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="gavel" class="w-8 h-8 text-dark-400"></i>
        </div>
        <h3 class="text-lg font-display font-bold text-dark-300"><?= t('marketplace.no_offers_yet', 'No offers yet') ?></h3>
        <p class="text-sm text-dark-400 mt-2"><?= t('marketplace.browse_marketplace', 'Browse the marketplace to find cards and make offers') ?></p>
        <a href="/marketplace" class="inline-flex items-center gap-2 mt-4 px-6 py-2.5 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition">
            <i data-lucide="store" class="w-4 h-4"></i> <?= t('marketplace.browse', 'Browse Marketplace') ?>
        </a>
    </div>
</div>

<script>
window.__PAGE_DATA = { bids: <?= $bidsJson ?> };
</script>
<script src="<?= asset_v('/assets/js/pages/marketplace.js') ?>"></script>
