<?php
$popularCardsJson = json_encode($popularCards ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$recentSalesJson = json_encode($recentSales ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$setsJson = json_encode($sets ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$colorsJson = json_encode($colors ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$raritiesJson = json_encode($rarities ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
?>

<div class="space-y-6" x-data="marketplaceBrowse()" x-init="init()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-display font-bold text-white flex items-center gap-3">
                <i data-lucide="store" class="w-7 h-7 text-gold-400"></i> <?= t('marketplace.title', 'Marketplace') ?>
            </h1>
            <p class="text-sm text-dark-400 mt-1"><?= t('marketplace.subtitle', 'Buy and sell One Piece TCG cards with escrow protection') ?></p>
        </div>
        <?php if (\App\Core\Auth::check()): ?>
        <div class="flex gap-2">
            <a href="/marketplace/my-listings" class="flex items-center gap-2 px-4 py-2 glass rounded-lg text-sm text-dark-300 hover:text-white transition">
                <i data-lucide="list" class="w-4 h-4"></i> <?= t('marketplace.my_listings', 'My Listings') ?>
            </a>
            <a href="/marketplace/sell" class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition shadow-lg shadow-gold-500/10">
                <i data-lucide="plus" class="w-4 h-4"></i> <?= t('marketplace.sell_card', 'Sell a Card') ?>
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Search & Filters -->
    <div class="glass rounded-2xl p-5">
        <div class="flex flex-col lg:flex-row gap-4">
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-dark-400"></i>
                <input type="text" x-model="filters.q" @input.debounce.350ms="doSearch()"
                    placeholder="<?= htmlspecialchars(t('marketplace.search_placeholder', 'Search cards by name or set ID...')) ?>"
                    class="w-full pl-9 pr-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition">
            </div>
            <div class="flex flex-wrap gap-3">
                <select x-model="filters.set_id" @change="doSearch()" class="px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <option value=""><?= t('marketplace.all_sets', 'All Sets') ?></option>
                    <template x-for="s in sets" :key="s"><option :value="s" x-text="s"></option></template>
                </select>
                <select x-model="filters.rarity" @change="doSearch()" class="px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <option value=""><?= t('marketplace.all_rarities', 'All Rarities') ?></option>
                    <template x-for="r in rarities" :key="r"><option :value="r" x-text="r"></option></template>
                </select>
                <select x-model="filters.color" @change="doSearch()" class="px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <option value=""><?= t('marketplace.all_colors', 'All Colors') ?></option>
                    <template x-for="c in colors" :key="c"><option :value="c" x-text="c"></option></template>
                </select>
                <select x-model="filters.condition" @change="doSearch()" class="px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <option value=""><?= t('marketplace.all_conditions', 'All Conditions') ?></option>
                    <option value="NM">Near Mint</option>
                    <option value="LP">Lightly Played</option>
                    <option value="MP">Moderately Played</option>
                    <option value="HP">Heavily Played</option>
                    <option value="DMG">Damaged</option>
                </select>
                <div class="flex items-center gap-2">
                    <input type="number" x-model="filters.price_min" @change="doSearch()" placeholder="Min $" min="0" step="0.01"
                        class="w-20 px-2 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition">
                    <span class="text-dark-400 text-sm">-</span>
                    <input type="number" x-model="filters.price_max" @change="doSearch()" placeholder="Max $" min="0" step="0.01"
                        class="w-20 px-2 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition">
                </div>
                <select x-model="filters.sort" @change="doSearch()" class="px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <option value="price_asc"><?= t('marketplace.price_low_high', 'Price: Low to High') ?></option>
                    <option value="price_desc"><?= t('marketplace.price_high_low', 'Price: High to Low') ?></option>
                    <option value="newest"><?= t('marketplace.newest', 'Newest First') ?></option>
                    <option value="listings"><?= t('marketplace.most_listings', 'Most Listings') ?></option>
                </select>
            </div>
        </div>
        <div class="flex items-center justify-between mt-3">
            <p class="text-xs text-dark-400"><span x-text="totalResults"></span> <?= t('marketplace.results_found', 'results found') ?></p>
            <button @click="resetFilters()" class="text-xs text-dark-400 hover:text-gold-400 transition"><?= t('marketplace.reset_filters', 'Reset filters') ?></button>
        </div>
    </div>

    <!-- Loading Indicator -->
    <div x-show="loading" class="flex items-center justify-center py-12">
        <svg class="w-8 h-8 animate-spin text-gold-400" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
    </div>

    <!-- Card Grid -->
    <div x-show="!loading" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
        <template x-for="card in cards" :key="card.card_set_id">
            <a :href="'/marketplace/card/' + encodeURIComponent(card.card_set_id)" class="group card-hover">
                <div class="glass rounded-xl overflow-hidden">
                    <div class="relative aspect-[5/7] bg-dark-700">
                        <img :src="cardImgSrc(card.card_image_url)" :data-ext-src="card.card_image_url" alt="" class="w-full h-full object-cover" loading="lazy" onerror="cardImgErr(this)">
                        <template x-if="card.rarity">
                            <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 text-[10px] font-bold text-white rounded shadow"
                                :class="rarityClass(card.rarity)" x-text="card.rarity"></span>
                        </template>
                        <template x-if="card.listing_count > 0">
                            <span class="absolute top-1.5 right-1.5 px-1.5 py-0.5 bg-dark-900/80 text-dark-300 text-[10px] font-bold rounded"
                                x-text="card.listing_count + ' listed'"></span>
                        </template>
                    </div>
                    <div class="p-2.5">
                        <p class="text-xs font-bold text-white truncate" x-text="card.card_name"></p>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-[10px] text-dark-400" x-text="card.card_set_id"></span>
                            <template x-if="card.floor_price > 0">
                                <span class="text-xs font-bold text-gold-400" x-text="'$' + parseFloat(card.floor_price).toFixed(2)"></span>
                            </template>
                            <template x-if="!card.floor_price || card.floor_price <= 0">
                                <span class="text-[10px] text-dark-500"><?= t('marketplace.no_listings', 'No listings') ?></span>
                            </template>
                        </div>
                    </div>
                </div>
            </a>
        </template>
    </div>

    <!-- Empty State -->
    <div x-show="!loading && cards.length === 0" class="text-center py-16">
        <div class="w-16 h-16 rounded-2xl bg-dark-700/50 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="search-x" class="w-8 h-8 text-dark-400"></i>
        </div>
        <h3 class="text-lg font-display font-bold text-dark-300"><?= t('marketplace.no_results', 'No listings found') ?></h3>
        <p class="text-sm text-dark-400 mt-2"><?= t('marketplace.try_different', 'Try adjusting your filters or search terms') ?></p>
    </div>

    <!-- Pagination -->
    <div x-show="totalPages > 1" class="flex justify-center gap-2 mt-8">
        <button @click="goPage(page - 1)" :disabled="page <= 1" class="px-3 py-2 glass rounded-lg text-sm text-dark-300 hover:text-white transition disabled:opacity-30">
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
        </button>
        <template x-for="p in pageRange" :key="p">
            <button @click="goPage(p)" :class="p === page ? 'bg-gold-500 text-dark-900 font-bold' : 'glass text-dark-300 hover:text-white'"
                class="px-3 py-2 rounded-lg text-sm font-medium transition" x-text="p"></button>
        </template>
        <button @click="goPage(page + 1)" :disabled="page >= totalPages" class="px-3 py-2 glass rounded-lg text-sm text-dark-300 hover:text-white transition disabled:opacity-30">
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
        </button>
    </div>

    <!-- Recent Sales -->
    <div class="glass rounded-2xl p-6" x-show="recentSales.length > 0">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="clock" class="w-5 h-5 text-green-400"></i> <?= t('marketplace.recent_sales', 'Recent Sales') ?>
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-dark-400 uppercase border-b border-dark-600">
                        <th class="text-left pb-3 pr-4"><?= t('marketplace.card', 'Card') ?></th>
                        <th class="text-right pb-3 pr-4"><?= t('marketplace.price', 'Price') ?></th>
                        <th class="text-center pb-3 pr-4"><?= t('marketplace.condition', 'Condition') ?></th>
                        <th class="text-right pb-3"><?= t('marketplace.date', 'Date') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="sale in recentSales" :key="sale.id">
                        <tr class="border-b border-dark-700/50">
                            <td class="py-3 pr-4">
                                <a :href="'/marketplace/card/' + encodeURIComponent(sale.card_set_id)" class="flex items-center gap-3 hover:text-gold-400 transition">
                                    <img :src="cardImgSrc(sale.card_image_url)" :data-ext-src="sale.card_image_url" class="w-7 h-10 rounded object-cover bg-dark-700" onerror="cardImgErr(this)" loading="lazy">
                                    <div>
                                        <p class="text-white font-medium" x-text="sale.card_name"></p>
                                        <p class="text-xs text-dark-400" x-text="sale.card_set_id"></p>
                                    </div>
                                </a>
                            </td>
                            <td class="py-3 pr-4 text-right text-gold-400 font-bold" x-text="'$' + parseFloat(sale.price).toFixed(2)"></td>
                            <td class="py-3 pr-4 text-center">
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full" :class="conditionClass(sale.condition)" x-text="sale.condition"></span>
                            </td>
                            <td class="py-3 text-right text-dark-400 text-xs" x-text="formatDate(sale.sold_at)"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
window.__PAGE_DATA = {
    popularCards: <?= $popularCardsJson ?>,
    recentSales: <?= $recentSalesJson ?>,
    sets: <?= $setsJson ?>,
    colors: <?= $colorsJson ?>,
    rarities: <?= $raritiesJson ?>
};
</script>
<script src="/assets/js/pages/marketplace.js"></script>
