<?php
$cardJson = json_encode($card ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$listingsJson = json_encode($listings ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$statsJson = json_encode($stats ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$bidsJson = json_encode($bids ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$recentSalesJson = json_encode($recentSales ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$isLoggedIn = \App\Core\Auth::check();
?>

<div class="space-y-6" x-data="cardMarketplace">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-dark-400">
        <a href="/marketplace" class="hover:text-gold-400 transition"><?= t('marketplace.title', 'Marketplace') ?></a>
        <i data-lucide="chevron-right" class="w-3 h-3"></i>
        <span class="text-white" x-text="card.card_name"></span>
    </nav>

    <!-- Card Hero Section -->
    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Left: Card Image + Stats -->
        <div class="lg:w-80 flex-shrink-0">
            <div class="glass rounded-2xl p-4 sticky top-24">
                <!-- 3D Card -->
                <div id="card3d" class="mb-4" style="perspective:1200px;cursor:grab;">
                    <div id="card3dInner" class="relative aspect-[5/7]"
                         style="transition:transform 0.1s ease-out;will-change:transform;border-radius:12px;">
                        <img id="card3dImg" :src="cardImgSrc(card.card_image_url)" :data-ext-src="card.card_image_url" alt=""
                             class="w-full h-full object-cover select-none"
                             style="border-radius:12px;display:block;"
                             onerror="cardImgErr(this)" draggable="false">
                        <div id="card3dGlare" style="position:absolute;inset:0;border-radius:12px;pointer-events:none;opacity:0;transition:opacity 0.1s ease;"></div>
                        <template x-if="card.rarity">
                            <span class="absolute top-2 left-2 px-2 py-0.5 text-xs font-bold text-white rounded shadow"
                                :class="rarityClass(card.rarity)" x-text="card.rarity"></span>
                        </template>
                    </div>
                </div>
                <script>
                (function() {
                    var wrap = document.getElementById('card3d');
                    var inner = document.getElementById('card3dInner');
                    var img = document.getElementById('card3dImg');
                    var glare = document.getElementById('card3dGlare');
                    if (!wrap || !inner || !img) return;

                    var maxTilt = 20;
                    var edgeLayers = 10;

                    inner.style.boxShadow = '0 8px 30px rgba(0,0,0,0.35)';

                    function onMove(e) {
                        var rect = wrap.getBoundingClientRect();
                        var cx = rect.left + rect.width / 2;
                        var cy = rect.top + rect.height / 2;
                        var clientX = e.touches ? e.touches[0].clientX : e.clientX;
                        var clientY = e.touches ? e.touches[0].clientY : e.clientY;
                        var dx = Math.max(-1, Math.min(1, (clientX - cx) / (rect.width / 2)));
                        var dy = Math.max(-1, Math.min(1, (clientY - cy) / (rect.height / 2)));
                        var rotY = dx * maxTilt;
                        var rotX = -dy * maxTilt;

                        inner.style.transition = 'transform 0.1s ease-out';
                        inner.style.transform = 'rotateX(' + rotX + 'deg) rotateY(' + rotY + 'deg) scale3d(1.03,1.03,1.03)';

                        // Edge thickness via stacked box-shadows (follows border-radius perfectly)
                        var edgeShadows = [];
                        for (var i = 1; i <= edgeLayers; i++) {
                            var ox = (-rotY / maxTilt) * i * 0.6;
                            var oy = (rotX / maxTilt) * i * 0.6;
                            var b = Math.round(210 - (i / edgeLayers) * 80);
                            edgeShadows.push(ox.toFixed(1) + 'px ' + oy.toFixed(1) + 'px 0 rgb(' + b + ',' + (b - 8) + ',' + (b - 16) + ')');
                        }
                        // Ground shadow
                        var gsx = (-rotY / maxTilt) * 15;
                        var gsy = 10 + (rotX / maxTilt) * 10;
                        edgeShadows.push(gsx.toFixed(1) + 'px ' + gsy.toFixed(1) + 'px 30px rgba(0,0,0,0.45)');
                        inner.style.boxShadow = edgeShadows.join(', ');

                        // Glare
                        if (glare) {
                            var gx = ((dx + 1) / 2 * 100).toFixed(1);
                            var gy = ((dy + 1) / 2 * 100).toFixed(1);
                            var intensity = (Math.abs(dx) + Math.abs(dy)) / 2;
                            glare.style.background = 'radial-gradient(ellipse at ' + gx + '% ' + gy + '%, rgba(255,255,255,' + (0.12 + intensity * 0.3).toFixed(2) + ') 0%, transparent 70%)';
                            glare.style.opacity = '1';
                        }
                    }

                    function onLeave() {
                        inner.style.transition = 'transform 0.6s cubic-bezier(.03,.98,.52,.99), box-shadow 0.6s ease';
                        inner.style.transform = 'rotateX(0deg) rotateY(0deg) scale3d(1,1,1)';
                        inner.style.boxShadow = '0 8px 30px rgba(0,0,0,0.35)';
                        if (glare) glare.style.opacity = '0';
                    }

                    wrap.addEventListener('mousemove', onMove);
                    wrap.addEventListener('mouseleave', onLeave);
                    wrap.addEventListener('touchmove', function(e) { e.preventDefault(); onMove(e); }, { passive: false });
                    wrap.addEventListener('touchend', onLeave);
                })();
                </script>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-dark-400"><?= t('marketplace.set', 'Set') ?></span>
                        <span class="text-white font-medium" x-text="card.card_set_id"></span>
                    </div>
                    <div class="flex justify-between text-sm" x-show="card.rarity">
                        <span class="text-dark-400"><?= t('marketplace.rarity', 'Rarity') ?></span>
                        <span class="text-white font-medium" x-text="card.rarity"></span>
                    </div>
                    <div class="flex justify-between text-sm" x-show="card.card_color">
                        <span class="text-dark-400"><?= t('marketplace.color', 'Color') ?></span>
                        <span class="text-white font-medium" x-text="card.card_color"></span>
                    </div>
                    <div class="flex justify-between text-sm" x-show="card.card_type">
                        <span class="text-dark-400"><?= t('marketplace.type', 'Type') ?></span>
                        <span class="text-white font-medium" x-text="card.card_type"></span>
                    </div>
                </div>
                <a :href="'/cards/' + card.card_set_id" target="_blank" class="flex items-center justify-center gap-2 w-full mt-4 px-4 py-2 glass rounded-lg text-sm text-dark-300 hover:text-white transition">
                    <i data-lucide="external-link" class="w-4 h-4"></i> <?= t('marketplace.view_card_details', 'View Card Details') ?>
                </a>
            </div>
        </div>

        <!-- Right: Stats + Tabs -->
        <div class="flex-1 min-w-0 space-y-6">
            <!-- Title -->
            <div>
                <h1 class="text-2xl font-display font-bold text-white" x-text="card.card_name"></h1>
                <p class="text-sm text-dark-400 mt-1" x-text="card.card_set_id + (card.set_name ? ' - ' + card.set_name : '')"></p>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-3 gap-4">
                <div class="glass rounded-xl p-4 text-center">
                    <p class="text-xs text-dark-400 uppercase tracking-wider font-bold mb-1"><?= t('marketplace.floor_price', 'Floor Price') ?></p>
                    <p class="text-xl font-display font-bold text-gold-400" x-text="stats.floor_price > 0 ? '$' + parseFloat(stats.floor_price).toFixed(2) : '-'"></p>
                </div>
                <div class="glass rounded-xl p-4 text-center">
                    <p class="text-xs text-dark-400 uppercase tracking-wider font-bold mb-1"><?= t('marketplace.listings', 'Listings') ?></p>
                    <p class="text-xl font-display font-bold text-blue-400" x-text="stats.listing_count || 0"></p>
                </div>
                <div class="glass rounded-xl p-4 text-center">
                    <p class="text-xs text-dark-400 uppercase tracking-wider font-bold mb-1"><?= t('marketplace.volume', 'Volume') ?></p>
                    <p class="text-xl font-display font-bold text-green-400" x-text="stats.volume > 0 ? '$' + parseFloat(stats.volume).toFixed(2) : '-'"></p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="glass rounded-2xl overflow-hidden">
                <div class="flex border-b border-dark-600">
                    <button @click="activeTab = 'listings'" :class="activeTab === 'listings' ? 'text-gold-400 border-b-2 border-gold-400' : 'text-dark-400 hover:text-white'"
                        class="flex-1 px-4 py-3 text-sm font-medium transition">
                        <i data-lucide="tag" class="w-4 h-4 inline mr-1"></i> <?= t('marketplace.listings_tab', 'Listings') ?>
                        <span class="ml-1 text-xs opacity-70" x-text="'(' + listings.length + ')'"></span>
                    </button>
                    <button @click="activeTab = 'offers'" :class="activeTab === 'offers' ? 'text-gold-400 border-b-2 border-gold-400' : 'text-dark-400 hover:text-white'"
                        class="flex-1 px-4 py-3 text-sm font-medium transition">
                        <i data-lucide="gavel" class="w-4 h-4 inline mr-1"></i> <?= t('marketplace.offers_tab', 'Offers') ?>
                        <span class="ml-1 text-xs opacity-70" x-text="'(' + bids.length + ')'"></span>
                    </button>
                    <button @click="activeTab = 'activity'" :class="activeTab === 'activity' ? 'text-gold-400 border-b-2 border-gold-400' : 'text-dark-400 hover:text-white'"
                        class="flex-1 px-4 py-3 text-sm font-medium transition">
                        <i data-lucide="activity" class="w-4 h-4 inline mr-1"></i> <?= t('marketplace.activity_tab', 'Activity') ?>
                    </button>
                </div>

                <div class="p-4">
                    <!-- Listings Tab -->
                    <div x-show="activeTab === 'listings'">
                        <template x-if="listings.length === 0">
                            <div class="text-center py-8">
                                <i data-lucide="package-open" class="w-10 h-10 text-dark-400 mx-auto mb-3"></i>
                                <p class="text-sm text-dark-400"><?= t('marketplace.no_listings_card', 'No active listings for this card') ?></p>
                                <?php if ($isLoggedIn): ?>
                                <a href="/marketplace/sell" class="inline-flex items-center gap-2 mt-3 px-4 py-2 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition">
                                    <i data-lucide="plus" class="w-4 h-4"></i> <?= t('marketplace.be_first', 'Be the first to list') ?>
                                </a>
                                <?php endif; ?>
                            </div>
                        </template>
                        <div class="overflow-x-auto" x-show="listings.length > 0">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-xs text-dark-400 uppercase border-b border-dark-600">
                                        <th class="text-left pb-3 pr-4"><?= t('marketplace.price', 'Price') ?></th>
                                        <th class="text-center pb-3 pr-4"><?= t('marketplace.condition', 'Condition') ?></th>
                                        <th class="text-left pb-3 pr-4"><?= t('marketplace.seller', 'Seller') ?></th>
                                        <th class="text-right pb-3 pr-4"><?= t('marketplace.shipping', 'Shipping') ?></th>
                                        <th class="text-right pb-3"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="listing in listings" :key="listing.id">
                                        <tr class="border-b border-dark-700/50 hover:bg-dark-800/30 transition">
                                            <td class="py-3 pr-4">
                                                <span class="text-gold-400 font-bold text-base" x-text="'$' + parseFloat(listing.price).toFixed(2)"></span>
                                            </td>
                                            <td class="py-3 pr-4 text-center">
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full" :class="conditionClass(listing.condition)" x-text="listing.condition"></span>
                                            </td>
                                            <td class="py-3 pr-4">
                                                <a :href="'/user/' + listing.seller_username" class="flex items-center gap-2 hover:text-gold-400 transition">
                                                    <div class="w-7 h-7 rounded-full flex-shrink-0 overflow-hidden flex items-center justify-center bg-dark-700">
                                                        <img x-show="listing.seller_avatar" :src="listing.seller_avatar" class="w-full h-full object-cover" alt="">
                                                        <span x-show="!listing.seller_avatar" class="font-bold text-xs text-dark-300" x-text="(listing.seller_username || '?').charAt(0).toUpperCase()"></span>
                                                    </div>
                                                    <div>
                                                        <p class="text-white text-sm" x-text="listing.seller_username"></p>
                                                        <div class="flex items-center gap-1" x-show="listing.seller_rating > 0">
                                                            <div class="rating-stars">
                                                                <template x-for="i in 5" :key="i">
                                                                    <i data-lucide="star" class="w-3 h-3 star" :class="i <= Math.round(listing.seller_rating) ? 'filled' : ''"></i>
                                                                </template>
                                                            </div>
                                                            <span class="text-[10px] text-dark-400" x-text="'(' + listing.seller_sales + ')'"></span>
                                                        </div>
                                                    </div>
                                                </a>
                                            </td>
                                            <td class="py-3 pr-4 text-right text-dark-300 text-sm">
                                                <span x-text="listing.shipping_cost > 0 ? '$' + parseFloat(listing.shipping_cost).toFixed(2) : 'Free'"></span>
                                            </td>
                                            <td class="py-3 text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a :href="'/marketplace/listing/' + listing.id" class="px-3 py-1.5 glass rounded-lg text-xs font-medium text-dark-300 hover:text-white transition">
                                                        <?= t('marketplace.view', 'View') ?>
                                                    </a>
                                                    <?php if ($isLoggedIn): ?>
                                                    <button @click="buyNow(listing)" class="px-3 py-1.5 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-xs font-bold hover:from-gold-400 hover:to-amber-500 transition">
                                                        <?= t('marketplace.buy_now', 'Buy Now') ?>
                                                    </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Offers Tab -->
                    <div x-show="activeTab === 'offers'">
                        <template x-if="bids.length === 0">
                            <div class="text-center py-8">
                                <i data-lucide="gavel" class="w-10 h-10 text-dark-400 mx-auto mb-3"></i>
                                <p class="text-sm text-dark-400"><?= t('marketplace.no_offers', 'No active offers for this card') ?></p>
                            </div>
                        </template>
                        <div class="space-y-3" x-show="bids.length > 0">
                            <template x-for="bid in bids" :key="bid.id">
                                <div class="flex items-center justify-between p-3 glass rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full flex-shrink-0 overflow-hidden flex items-center justify-center bg-dark-700">
                                            <span class="font-bold text-xs text-dark-300" x-text="(bid.buyer_username || '?').charAt(0).toUpperCase()"></span>
                                        </div>
                                        <div>
                                            <p class="text-sm text-white font-medium" x-text="bid.buyer_username"></p>
                                            <p class="text-xs text-dark-400" x-text="'Expires ' + formatDate(bid.expires_at)"></p>
                                        </div>
                                    </div>
                                    <span class="text-gold-400 font-bold" x-text="'$' + parseFloat(bid.amount).toFixed(2)"></span>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Activity Tab -->
                    <div x-show="activeTab === 'activity'">
                        <template x-if="recentSales.length === 0">
                            <div class="text-center py-8">
                                <i data-lucide="activity" class="w-10 h-10 text-dark-400 mx-auto mb-3"></i>
                                <p class="text-sm text-dark-400"><?= t('marketplace.no_activity', 'No recent activity') ?></p>
                            </div>
                        </template>
                        <div class="space-y-3" x-show="recentSales.length > 0">
                            <template x-for="sale in recentSales" :key="sale.id">
                                <div class="flex items-center justify-between p-3 glass rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-green-500/20 flex items-center justify-center">
                                            <i data-lucide="check-circle" class="w-4 h-4 text-green-400"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm text-white">
                                                <span class="font-medium" x-text="sale.buyer_username"></span>
                                                <span class="text-dark-400"><?= t('marketplace.bought_from', 'bought from') ?></span>
                                                <span class="font-medium" x-text="sale.seller_username"></span>
                                            </p>
                                            <p class="text-xs text-dark-400">
                                                <span x-text="sale.condition"></span> &middot; <span x-text="formatDate(sale.sold_at)"></span>
                                            </p>
                                        </div>
                                    </div>
                                    <span class="text-gold-400 font-bold" x-text="'$' + parseFloat(sale.price).toFixed(2)"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Make Offer Button -->
            <?php if ($isLoggedIn): ?>
            <button @click="bidModalOpen = true" class="w-full py-3 glass rounded-xl text-sm font-bold text-gold-400 hover:text-gold-300 hover:border-gold-500/30 transition flex items-center justify-center gap-2">
                <i data-lucide="gavel" class="w-5 h-5"></i> <?= t('marketplace.make_offer', 'Make an Offer') ?>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bid Modal -->
    <?php if ($isLoggedIn): ?>
    <div x-show="bidModalOpen" x-transition.opacity x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-dark-900/80 backdrop-blur-sm" @click.self="bidModalOpen = false">
        <div class="glass rounded-2xl p-6 w-full max-w-md" @click.stop>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-display font-bold text-white"><?= t('marketplace.make_offer', 'Make an Offer') ?></h3>
                <button @click="bidModalOpen = false" class="text-dark-400 hover:text-white transition"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5"><?= t('marketplace.offer_amount', 'Offer Amount (USD)') ?></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-dark-400 font-bold">$</span>
                        <input type="number" x-model="bidAmount" min="0.01" step="0.01" placeholder="0.00"
                            class="w-full pl-8 pr-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition">
                    </div>
                    <p class="text-xs text-dark-400 mt-1" x-show="stats.floor_price > 0">
                        <?= t('marketplace.floor_note', 'Floor price:') ?> <span class="text-gold-400 font-bold" x-text="'$' + parseFloat(stats.floor_price).toFixed(2)"></span>
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5"><?= t('marketplace.message', 'Message (optional)') ?></label>
                    <textarea x-model="bidMessage" rows="3" placeholder="<?= htmlspecialchars(t('marketplace.message_placeholder', 'Add a note to the seller...')) ?>"
                        class="w-full px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition resize-none"></textarea>
                </div>
                <button @click="placeBid()" :disabled="bidSubmitting || !bidAmount || bidAmount <= 0"
                    class="w-full py-3 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition disabled:opacity-50">
                    <span x-show="!bidSubmitting"><?= t('marketplace.submit_offer', 'Submit Offer') ?></span>
                    <span x-show="bidSubmitting" class="flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        <?= t('marketplace.submitting', 'Submitting...') ?>
                    </span>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
window.__PAGE_DATA = {
    card: <?= $cardJson ?>,
    listings: <?= $listingsJson ?>,
    stats: <?= $statsJson ?>,
    bids: <?= $bidsJson ?>,
    recentSales: <?= $recentSalesJson ?>
};
</script>
<script src="<?= asset_v('/assets/js/pages/marketplace.js') ?>"></script>
