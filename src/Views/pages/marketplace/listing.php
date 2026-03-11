<?php
$listingJson = json_encode($listing ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$bidsJson = json_encode($bids ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$sellerStatsJson = json_encode($sellerStats ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$isLoggedIn = \App\Core\Auth::check();
$currentUserId = \App\Core\Auth::id();
?>

<div class="space-y-6" x-data="listingDetail">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-dark-400">
        <a href="/marketplace" class="hover:text-gold-400 transition"><?= t('marketplace.title', 'Marketplace') ?></a>
        <i data-lucide="chevron-right" class="w-3 h-3"></i>
        <a :href="'/marketplace/card/' + encodeURIComponent(listing.card_set_id)" class="hover:text-gold-400 transition" x-text="listing.card_name"></a>
        <i data-lucide="chevron-right" class="w-3 h-3"></i>
        <span class="text-white"><?= t('marketplace.listing_detail', 'Listing') ?></span>
    </nav>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Left: Image Gallery -->
        <div class="lg:w-96 flex-shrink-0">
            <div class="glass rounded-2xl p-4 sticky top-24">
                <!-- Main Image (clickable to zoom) -->
                <div class="relative aspect-[5/7] bg-dark-700 rounded-xl overflow-hidden mb-3 cursor-zoom-in group"
                     @click="openLightbox(activeImageIdx)">
                    <img :src="activeImage" alt="" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" onerror="cardImgErr(this)">
                    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition flex items-center justify-center">
                        <div class="opacity-0 group-hover:opacity-100 transition bg-black/60 rounded-full p-2">
                            <i data-lucide="zoom-in" class="w-6 h-6 text-white"></i>
                        </div>
                    </div>
                    <template x-if="listing.condition">
                        <span class="absolute top-2 right-2 px-2.5 py-1 text-xs font-bold rounded-full" :class="conditionClass(listing.condition)" x-text="listing.condition"></span>
                    </template>
                </div>
                <!-- Thumbnail Strip -->
                <div class="flex gap-2 overflow-x-auto" x-show="images.length > 1">
                    <template x-for="(img, idx) in images" :key="idx">
                        <button @click="activeImageIdx = idx" :class="activeImageIdx === idx ? 'ring-2 ring-gold-400' : 'opacity-60 hover:opacity-100'"
                            class="w-16 h-22 flex-shrink-0 rounded-lg overflow-hidden transition">
                            <img :src="img" alt="" class="w-full h-full object-cover">
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Right: Details -->
        <div class="flex-1 min-w-0 space-y-6">
            <!-- Card Info -->
            <div>
                <h1 class="text-2xl font-display font-bold text-white" x-text="listing.card_name"></h1>
                <p class="text-sm text-dark-400 mt-1" x-text="listing.card_set_id"></p>
                <div class="flex flex-wrap items-center gap-2 mt-3">
                    <template x-if="listing.rarity">
                        <span class="px-2 py-0.5 text-xs font-bold text-white rounded" :class="rarityClass(listing.rarity)" x-text="listing.rarity"></span>
                    </template>
                    <template x-if="listing.condition">
                        <span class="px-2.5 py-0.5 text-xs font-bold rounded-full" :class="conditionClass(listing.condition)" x-text="conditionLabel(listing.condition)"></span>
                    </template>
                    <template x-if="listing.card_color">
                        <span class="px-2 py-0.5 bg-blue-500/20 text-blue-400 text-xs font-medium rounded" x-text="listing.card_color"></span>
                    </template>
                </div>
            </div>

            <!-- Price Breakdown -->
            <div class="glass rounded-2xl p-5">
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-dark-400"><?= t('marketplace.item_price', 'Item Price') ?></span>
                        <span class="text-white font-medium" x-text="'$' + parseFloat(listing.price).toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-dark-400"><?= t('marketplace.shipping', 'Shipping') ?></span>
                        <span class="text-white font-medium" x-text="listing.shipping_cost > 0 ? '$' + parseFloat(listing.shipping_cost).toFixed(2) : 'Free'"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-dark-400"><?= t('marketplace.buyer_fee', 'Buyer Fee (5%)') ?></span>
                        <span class="text-white font-medium" x-text="'$' + buyerFee.toFixed(2)"></span>
                    </div>
                    <div class="border-t border-dark-600 pt-3 flex justify-between">
                        <span class="text-white font-bold"><?= t('marketplace.total', 'Total') ?></span>
                        <span class="text-gold-400 font-bold text-xl" x-text="'$' + totalPrice.toFixed(2)"></span>
                    </div>
                </div>

                <?php if ($isLoggedIn): ?>
                <div class="mt-5 space-y-3" x-show="listing.seller_id != <?= (int)$currentUserId ?>">
                    <button @click="showBuyModal = true" :disabled="buying"
                        class="w-full py-3 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-xl text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition shadow-lg shadow-gold-500/10 disabled:opacity-50 flex items-center justify-center gap-2">
                        <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                        <?= t('marketplace.buy_now', 'Buy Now') ?>
                    </button>
                </div>
                <?php else: ?>
                <div class="mt-5">
                    <a href="/login" class="w-full py-3 bg-dark-700 border border-dark-600 text-dark-300 rounded-xl text-sm font-bold flex items-center justify-center gap-2 hover:border-gold-500/50 transition">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                        Login to Purchase
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Seller Card -->
            <div class="glass rounded-2xl p-5">
                <h3 class="text-sm font-bold text-dark-400 uppercase tracking-wider mb-3"><?= t('marketplace.seller', 'Seller') ?></h3>
                <a :href="'/user/' + listing.seller_username" class="flex items-center gap-4 hover:bg-dark-800/30 p-2 rounded-lg transition -m-2">
                    <div class="w-12 h-12 rounded-full flex-shrink-0 overflow-hidden flex items-center justify-center bg-dark-700">
                        <img x-show="listing.seller_avatar" :src="listing.seller_avatar" class="w-full h-full object-cover" alt="">
                        <span x-show="!listing.seller_avatar" class="font-bold text-lg text-dark-300" x-text="(listing.seller_username || '?').charAt(0).toUpperCase()"></span>
                    </div>
                    <div class="flex-1">
                        <p class="text-white font-bold" x-text="listing.seller_username"></p>
                        <div class="flex items-center gap-2 mt-0.5">
                            <div class="rating-stars" x-show="sellerStats.avg_rating > 0">
                                <template x-for="i in 5" :key="i">
                                    <i data-lucide="star" class="w-3 h-3 star" :class="i <= Math.round(sellerStats.avg_rating) ? 'filled' : ''"></i>
                                </template>
                            </div>
                            <span class="text-xs text-dark-400" x-text="sellerStats.total_sales + ' sales'"></span>
                        </div>
                    </div>
                    <i data-lucide="chevron-right" class="w-5 h-5 text-dark-400"></i>
                </a>
            </div>

            <!-- Make Offer Section -->
            <?php if ($isLoggedIn): ?>
            <div class="glass rounded-2xl p-5" x-show="listing.seller_id != <?= (int)$currentUserId ?>">
                <h3 class="text-sm font-bold text-dark-400 uppercase tracking-wider mb-3"><?= t('marketplace.make_offer', 'Make an Offer') ?></h3>
                <div class="flex gap-3">
                    <div class="relative flex-1">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-dark-400 font-bold">$</span>
                        <input type="number" x-model="bidAmount" min="0.01" step="0.01" placeholder="0.00"
                            class="w-full pl-8 pr-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition">
                    </div>
                    <button @click="placeBid()" :disabled="bidSubmitting || !bidAmount || bidAmount <= 0"
                        class="px-6 py-2.5 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition disabled:opacity-50">
                        <span x-show="!bidSubmitting"><?= t('marketplace.submit_offer', 'Submit Offer') ?></span>
                        <span x-show="bidSubmitting"><?= t('marketplace.submitting', 'Submitting...') ?></span>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Existing Bids -->
            <div class="glass rounded-2xl p-5" x-show="bids.length > 0">
                <h3 class="text-sm font-bold text-dark-400 uppercase tracking-wider mb-3"><?= t('marketplace.current_offers', 'Current Offers') ?></h3>
                <div class="space-y-2">
                    <template x-for="bid in bids" :key="bid.id">
                        <div class="flex items-center justify-between p-3 bg-dark-800/30 rounded-lg gap-3">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div class="w-7 h-7 rounded-full bg-dark-700 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-dark-300" x-text="(bid.buyer_username || '?').charAt(0).toUpperCase()"></span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm text-white font-medium" x-text="bid.buyer_username"></p>
                                    <p class="text-[10px] text-dark-400" x-text="formatDate(bid.created_at)"></p>
                                </div>
                            </div>
                            <span class="text-gold-400 font-bold text-sm flex-shrink-0" x-text="'$' + parseFloat(bid.amount).toFixed(2)"></span>
                            <?php if ($isLoggedIn): ?>
                            <div class="flex gap-2 flex-shrink-0" x-show="listing.seller_id == <?= (int)$currentUserId ?> && bid.status === 'pending'">
                                <button @click="acceptBid(bid.id)"
                                    class="px-3 py-1.5 bg-green-500/20 hover:bg-green-500/30 text-green-400 rounded-lg text-xs font-bold transition flex items-center gap-1">
                                    <i data-lucide="check" class="w-3 h-3"></i> Accept
                                </button>
                                <button @click="rejectBid(bid.id)"
                                    class="px-3 py-1.5 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg text-xs font-bold transition flex items-center gap-1">
                                    <i data-lucide="x" class="w-3 h-3"></i> Reject
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Description -->
            <div class="glass rounded-2xl p-5" x-show="listing.description">
                <h3 class="text-sm font-bold text-dark-400 uppercase tracking-wider mb-3"><?= t('marketplace.description', 'Description') ?></h3>
                <p class="text-sm text-dark-300 whitespace-pre-wrap" x-text="listing.description"></p>
            </div>
        </div>
    </div>

    <!-- ===== Buy Confirmation Modal ===== -->
    <div x-show="showBuyModal" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         style="display:none">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="showBuyModal = false"></div>
        <div class="relative glass rounded-2xl p-6 w-full max-w-sm shadow-2xl"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <button @click="showBuyModal = false" class="absolute top-4 right-4 text-dark-400 hover:text-white transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
            <div class="flex items-center gap-3 mb-5">
                <div class="w-10 h-10 rounded-xl bg-gold-500/20 flex items-center justify-center">
                    <i data-lucide="shopping-cart" class="w-5 h-5 text-gold-400"></i>
                </div>
                <div>
                    <h3 class="text-white font-bold text-lg">Confirm Purchase</h3>
                    <p class="text-dark-400 text-xs" x-text="listing.card_name"></p>
                </div>
            </div>

            <!-- Card preview -->
            <div class="flex gap-3 mb-5 p-3 bg-dark-800/50 rounded-xl">
                <img :src="activeImage" class="w-14 h-20 object-cover rounded-lg flex-shrink-0" onerror="cardImgErr(this)">
                <div class="flex-1 min-w-0">
                    <p class="text-white text-sm font-medium truncate" x-text="listing.card_name"></p>
                    <p class="text-dark-400 text-xs" x-text="listing.card_set_id"></p>
                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-bold rounded-full" :class="conditionClass(listing.condition)" x-text="conditionLabel(listing.condition)"></span>
                </div>
            </div>

            <!-- Price breakdown -->
            <div class="space-y-2 mb-5">
                <div class="flex justify-between text-sm">
                    <span class="text-dark-400">Item Price</span>
                    <span class="text-white" x-text="'$' + parseFloat(listing.price).toFixed(2)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-dark-400">Shipping</span>
                    <span class="text-white" x-text="listing.shipping_cost > 0 ? '$' + parseFloat(listing.shipping_cost).toFixed(2) : 'Free'"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-dark-400">Buyer Fee (5%)</span>
                    <span class="text-white" x-text="'$' + buyerFee.toFixed(2)"></span>
                </div>
                <div class="border-t border-dark-600 pt-2 flex justify-between font-bold">
                    <span class="text-white">Total</span>
                    <span class="text-gold-400 text-lg" x-text="'$' + totalPrice.toFixed(2)"></span>
                </div>
            </div>

            <p class="text-xs text-dark-400 mb-4 text-center">Funds will be held in escrow until you confirm delivery.</p>

            <div class="flex gap-3">
                <button @click="showBuyModal = false" class="flex-1 py-2.5 bg-dark-700 hover:bg-dark-600 text-white rounded-xl text-sm font-medium transition">
                    Cancel
                </button>
                <button @click="confirmBuy()" :disabled="buying"
                    class="flex-1 py-2.5 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-xl text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition disabled:opacity-50 flex items-center justify-center gap-2">
                    <template x-if="!buying">
                        <span>Buy Now</span>
                    </template>
                    <template x-if="buying">
                        <span class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                            Processing...
                        </span>
                    </template>
                </button>
            </div>
        </div>
    </div>

    <!-- ===== Lightbox / Image Zoom ===== -->
    <div x-show="showLightbox" x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center"
         style="display:none"
         @keydown.escape.window="showLightbox = false"
         @keydown.arrow-left.window="lightboxPrev()"
         @keydown.arrow-right.window="lightboxNext()">
        <div class="absolute inset-0 bg-black/95 backdrop-blur-sm" @click="showLightbox = false"></div>

        <!-- Close -->
        <button @click="showLightbox = false" class="absolute top-4 right-4 z-10 text-white/70 hover:text-white transition bg-white/10 rounded-full p-2">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>

        <!-- Counter -->
        <div class="absolute top-4 left-1/2 -translate-x-1/2 z-10 text-white/60 text-sm" x-show="images.length > 1">
            <span x-text="lightboxIdx + 1"></span> / <span x-text="images.length"></span>
        </div>

        <!-- Prev -->
        <button x-show="images.length > 1" @click.stop="lightboxPrev()"
            class="absolute left-4 z-10 text-white/70 hover:text-white transition bg-white/10 hover:bg-white/20 rounded-full p-3">
            <i data-lucide="chevron-left" class="w-6 h-6"></i>
        </button>

        <!-- Image -->
        <div class="relative z-10 max-w-4xl max-h-screen p-16 flex items-center justify-center">
            <img :src="images[lightboxIdx]" alt="" class="max-w-full max-h-[80vh] object-contain rounded-xl shadow-2xl" onerror="cardImgErr(this)">
        </div>

        <!-- Next -->
        <button x-show="images.length > 1" @click.stop="lightboxNext()"
            class="absolute right-4 z-10 text-white/70 hover:text-white transition bg-white/10 hover:bg-white/20 rounded-full p-3">
            <i data-lucide="chevron-right" class="w-6 h-6"></i>
        </button>

        <!-- Thumbnail strip -->
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-10 flex gap-2" x-show="images.length > 1">
            <template x-for="(img, idx) in images" :key="idx">
                <button @click.stop="lightboxIdx = idx"
                    :class="lightboxIdx === idx ? 'ring-2 ring-gold-400 opacity-100' : 'opacity-50 hover:opacity-80'"
                    class="w-12 h-16 rounded-md overflow-hidden transition flex-shrink-0">
                    <img :src="img" class="w-full h-full object-cover">
                </button>
            </template>
        </div>
    </div>
</div>

<script>
window.__PAGE_DATA = {
    listing: <?= $listingJson ?>,
    bids: <?= $bidsJson ?>,
    sellerStats: <?= $sellerStatsJson ?>
};
</script>
<script src="<?= asset_v('/assets/js/pages/marketplace.js') ?>"></script>
