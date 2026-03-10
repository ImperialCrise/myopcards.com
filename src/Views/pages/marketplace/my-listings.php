<?php
$listingsJson = json_encode($listings ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
?>

<div class="space-y-6" x-data="myListings">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-display font-bold text-white flex items-center gap-3">
                <i data-lucide="list" class="w-7 h-7 text-gold-400"></i> <?= t('marketplace.my_listings', 'My Listings') ?>
            </h1>
            <p class="text-sm text-dark-400 mt-1"><?= t('marketplace.manage_listings', 'Manage your marketplace listings') ?></p>
        </div>
        <a href="/marketplace/sell" class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition shadow-lg shadow-gold-500/10">
            <i data-lucide="plus" class="w-4 h-4"></i> <?= t('marketplace.new_listing', 'New Listing') ?>
        </a>
    </div>

    <!-- Status Tabs -->
    <div class="flex gap-1 glass rounded-xl p-1">
        <template x-for="tab in tabs" :key="tab.value">
            <button @click="statusFilter = tab.value; filterListings()"
                :class="statusFilter === tab.value ? 'bg-gold-500/20 text-gold-400' : 'text-dark-400 hover:text-white'"
                class="flex-1 px-3 py-2 rounded-lg text-sm font-medium transition" x-text="tab.label + ' (' + countByStatus(tab.value) + ')'">
            </button>
        </template>
    </div>

    <!-- Listings -->
    <div class="space-y-3">
        <template x-for="listing in filteredListings" :key="listing.id">
            <div class="glass rounded-xl p-4 flex items-center gap-4">
                <a :href="'/marketplace/listing/' + listing.id" class="flex-shrink-0">
                    <img :src="cardImgSrc(listing.card_image_url)" :data-ext-src="listing.card_image_url" class="w-14 h-20 rounded-lg object-cover bg-dark-700" onerror="cardImgErr(this)" loading="lazy">
                </a>
                <div class="flex-1 min-w-0">
                    <a :href="'/marketplace/listing/' + listing.id" class="hover:text-gold-400 transition">
                        <p class="text-white font-bold truncate" x-text="listing.card_name"></p>
                    </a>
                    <p class="text-xs text-dark-400 mt-0.5" x-text="listing.card_set_id"></p>
                    <div class="flex items-center gap-3 mt-1.5">
                        <span class="text-gold-400 font-bold text-sm" x-text="'$' + parseFloat(listing.price).toFixed(2)"></span>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full" :class="conditionClass(listing.condition)" x-text="listing.condition"></span>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full" :class="statusClass(listing.status)" x-text="listing.status"></span>
                    </div>
                    <p class="text-[10px] text-dark-500 mt-1" x-text="'Listed ' + formatDate(listing.created_at)"></p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <template x-if="listing.status === 'active'">
                        <a :href="'/marketplace/sell?edit=' + listing.id" class="p-2 glass rounded-lg text-dark-300 hover:text-gold-400 transition" title="Edit">
                            <i data-lucide="edit-2" class="w-4 h-4"></i>
                        </a>
                    </template>
                    <template x-if="listing.status === 'active'">
                        <button @click="cancelListing(listing.id)" class="p-2 glass rounded-lg text-dark-300 hover:text-red-400 transition" title="Cancel">
                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                        </button>
                    </template>
                    <a :href="'/marketplace/listing/' + listing.id" class="p-2 glass rounded-lg text-dark-300 hover:text-white transition" title="View">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="filteredListings.length === 0" class="text-center py-16">
        <div class="w-16 h-16 rounded-2xl bg-dark-700/50 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="package-open" class="w-8 h-8 text-dark-400"></i>
        </div>
        <h3 class="text-lg font-display font-bold text-dark-300"><?= t('marketplace.no_listings_yet', 'No listings yet') ?></h3>
        <p class="text-sm text-dark-400 mt-2"><?= t('marketplace.start_selling', 'Start selling your cards on the marketplace') ?></p>
        <a href="/marketplace/sell" class="inline-flex items-center gap-2 mt-4 px-6 py-2.5 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition">
            <i data-lucide="plus" class="w-4 h-4"></i> <?= t('marketplace.create_first', 'Create Your First Listing') ?>
        </a>
    </div>
</div>

<script>
window.__PAGE_DATA = { listings: <?= $listingsJson ?> };
</script>
<script src="/assets/js/pages/marketplace.js"></script>
