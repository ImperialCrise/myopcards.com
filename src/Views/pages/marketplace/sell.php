<?php
$setsJson = json_encode($sets ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$userAddressesJson = json_encode($userAddresses ?? [], JSON_HEX_APOS | JSON_HEX_TAG);
$editListingJson = json_encode($editListing ?? null, JSON_HEX_APOS | JSON_HEX_TAG);
$isEdit = !empty($editListing);
?>

<div class="max-w-3xl mx-auto space-y-6" x-data="createListing">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-display font-bold text-white flex items-center gap-3">
            <i data-lucide="tag" class="w-7 h-7 text-gold-400"></i>
            <?= $isEdit ? 'Edit Listing' : t('marketplace.create_listing', 'Create Listing') ?>
        </h1>
        <p class="text-sm text-dark-400 mt-1"><?= $isEdit ? 'Update your listing details' : t('marketplace.sell_subtitle', 'List your card for sale on the marketplace') ?></p>
    </div>

    <!-- Card Search -->
    <div class="glass rounded-2xl p-6 overflow-visible relative" style="z-index:10;">
        <h2 class="text-sm font-bold text-dark-400 uppercase tracking-wider mb-3"><?= t('marketplace.select_card', 'Select Card') ?></h2>
        <div class="relative">
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-dark-400"></i>
            <input type="text" x-model="cardQuery" @input.debounce.300ms="searchCards()"
                placeholder="<?= htmlspecialchars(t('marketplace.search_card_placeholder', 'Search by card name or set ID...')) ?>"
                class="w-full pl-9 pr-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition">
            <!-- Search Results Dropdown -->
            <div x-show="cardResults.length > 0 && !selectedCard" x-transition class="absolute top-full left-0 right-0 mt-1 glass-strong rounded-xl shadow-2xl max-h-72 overflow-y-auto z-50">
                <template x-for="card in cardResults" :key="card.id">
                    <button @click="selectCard(card)" class="w-full flex items-center gap-3 px-4 py-3 hover:bg-dark-800/50 transition text-left">
                        <img :src="cardImgSrc(card.card_image_url)" :data-ext-src="card.card_image_url" class="w-8 h-11 rounded object-cover bg-dark-700" onerror="cardImgErr(this)">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-white truncate" x-text="card.card_name"></p>
                            <p class="text-xs text-dark-400" x-text="card.card_set_id + (card.rarity ? ' - ' + card.rarity : '')"></p>
                        </div>
                        <span x-show="card.market_price > 0" class="text-xs font-bold text-gold-400" x-text="'$' + parseFloat(card.market_price).toFixed(2)"></span>
                    </button>
                </template>
            </div>
        </div>
        <!-- Selected Card Preview -->
        <div x-show="selectedCard" class="mt-4 flex items-center gap-4 p-4 bg-dark-800/30 rounded-xl">
            <img :src="selectedCard ? cardImgSrc(selectedCard.card_image_url) : ''" class="w-16 h-22 rounded-lg object-cover bg-dark-700" onerror="cardImgErr(this)">
            <div class="flex-1 min-w-0">
                <p class="text-white font-bold" x-text="selectedCard?.card_name"></p>
                <p class="text-sm text-dark-400" x-text="selectedCard?.card_set_id"></p>
                <p class="text-xs text-gold-400 mt-1" x-show="selectedCard?.market_price > 0" x-text="'Market: $' + parseFloat(selectedCard?.market_price || 0).toFixed(2)"></p>
            </div>
            <button @click="clearCard()" class="p-2 text-dark-400 hover:text-red-400 transition"><i data-lucide="x" class="w-5 h-5"></i></button>
        </div>
    </div>

    <!-- Condition -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-sm font-bold text-dark-400 uppercase tracking-wider mb-3"><?= t('marketplace.condition', 'Condition') ?></h2>
        <div class="grid grid-cols-5 gap-2">
            <template x-for="cond in conditions" :key="cond.value">
                <button @click="form.condition = cond.value" type="button"
                    :class="form.condition === cond.value ? 'ring-2 ring-gold-400 bg-gold-500/10' : 'bg-dark-800/50 hover:bg-dark-800'"
                    class="p-3 rounded-xl text-center transition">
                    <span class="block text-sm font-bold text-white" x-text="cond.value"></span>
                    <span class="block text-[10px] text-dark-400 mt-0.5" x-text="cond.label"></span>
                </button>
            </template>
        </div>
    </div>

    <!-- Price & Quantity -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-sm font-bold text-dark-400 uppercase tracking-wider mb-3"><?= t('marketplace.pricing', 'Pricing') ?></h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5"><?= t('marketplace.price_usd', 'Price (USD)') ?></label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-dark-400 font-bold">$</span>
                    <input type="number" x-model="form.price" min="0.01" step="0.01" placeholder="0.00" required
                        class="w-full pl-8 pr-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition">
                </div>
                <p class="text-xs text-dark-400 mt-1" x-show="selectedCard?.market_price > 0">
                    <?= t('marketplace.market_ref', 'Market price:') ?> <span class="text-gold-400 font-bold" x-text="'$' + parseFloat(selectedCard?.market_price || 0).toFixed(2)"></span>
                </p>
            </div>
            <div>
                <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5"><?= t('marketplace.quantity', 'Quantity') ?></label>
                <input type="number" x-model="form.quantity" min="1" max="99" placeholder="1"
                    class="w-full px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition">
            </div>
        </div>
    </div>

    <!-- Description -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-sm font-bold text-dark-400 uppercase tracking-wider mb-3"><?= t('marketplace.description', 'Description') ?></h2>
        <textarea x-model="form.description" rows="4" placeholder="<?= htmlspecialchars(t('marketplace.description_placeholder', 'Describe the condition, any notable features...')) ?>"
            class="w-full px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition resize-none"></textarea>
    </div>

    <!-- Image Upload -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-sm font-bold text-dark-400 uppercase tracking-wider mb-3"><?= t('marketplace.photos', 'Photos') ?> <span class="text-dark-500 normal-case font-normal">(<?= t('marketplace.optional', 'optional') ?>, max 4)</span></h2>
        <div class="space-y-4">
            <!-- Drop Zone -->
            <div @dragover.prevent="dragOver = true" @dragleave="dragOver = false" @drop.prevent="handleDrop($event)"
                :class="dragOver ? 'border-gold-400 bg-gold-500/5' : 'border-dark-600'"
                class="border-2 border-dashed rounded-xl p-8 text-center transition cursor-pointer"
                @click="$refs.fileInput.click()">
                <i data-lucide="upload-cloud" class="w-10 h-10 text-dark-400 mx-auto mb-3"></i>
                <p class="text-sm text-dark-300"><?= t('marketplace.drop_images', 'Drop images here or click to upload') ?></p>
                <p class="text-xs text-dark-500 mt-1"><?= t('marketplace.image_formats', 'JPG, PNG, WEBP up to 5MB each') ?></p>
            </div>
            <input type="file" x-ref="fileInput" @change="handleFiles($event)" accept="image/jpeg,image/png,image/webp" multiple class="hidden">
            <!-- Image Previews -->
            <div class="flex gap-3 flex-wrap" x-show="imagePreviews.length > 0">
                <template x-for="(img, idx) in imagePreviews" :key="idx">
                    <div class="relative group w-24 h-24 rounded-lg overflow-hidden bg-dark-700">
                        <img :src="img.url" class="w-full h-full object-cover">
                        <button @click="removeImage(idx)" class="absolute top-1 right-1 w-5 h-5 bg-red-500 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                            <i data-lucide="x" class="w-3 h-3 text-white"></i>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Shipping -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-sm font-bold text-dark-400 uppercase tracking-wider mb-3"><?= t('marketplace.shipping', 'Shipping') ?></h2>
        <div class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5"><?= t('marketplace.ships_from', 'Ships From') ?></label>
                    <select x-model="form.shipping_country"
                        class="w-full px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                        <option value=""><?= t('marketplace.select_country', 'Select country...') ?></option>
                        <template x-for="c in countries" :key="c.code">
                            <option :value="c.code" x-text="c.name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5"><?= t('marketplace.shipping_cost', 'Shipping Cost (USD)') ?></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-dark-400 font-bold">$</span>
                        <input type="number" x-model="form.shipping_cost" min="0" step="0.01" placeholder="0.00"
                            class="w-full pl-8 pr-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition">
                    </div>
                </div>
            </div>
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" x-model="form.international_shipping" class="w-4 h-4 rounded bg-dark-800 border-dark-600 text-gold-400 focus:ring-gold-500/30">
                <span class="text-sm text-dark-300"><?= t('marketplace.international', 'Available for international shipping') ?></span>
            </label>
        </div>
    </div>

    <!-- Fee Summary -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-sm font-bold text-dark-400 uppercase tracking-wider mb-3"><?= t('marketplace.fee_summary', 'Fee Summary') ?></h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="space-y-2">
                <h4 class="text-xs text-dark-400 font-bold"><?= t('marketplace.you_receive', "You'll receive") ?></h4>
                <div class="flex justify-between text-sm">
                    <span class="text-dark-400"><?= t('marketplace.price', 'Price') ?></span>
                    <span class="text-white" x-text="'$' + parseFloat(form.price || 0).toFixed(2)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-dark-400"><?= t('marketplace.seller_fee', 'Seller Fee (5%)') ?></span>
                    <span class="text-red-400" x-text="'-$' + sellerFee.toFixed(2)"></span>
                </div>
                <div class="flex justify-between text-sm font-bold border-t border-dark-600 pt-2">
                    <span class="text-white"><?= t('marketplace.net_earnings', 'Net Earnings') ?></span>
                    <span class="text-green-400" x-text="'$' + netEarnings.toFixed(2)"></span>
                </div>
            </div>
            <div class="space-y-2">
                <h4 class="text-xs text-dark-400 font-bold"><?= t('marketplace.buyer_pays', 'Buyer pays') ?></h4>
                <div class="flex justify-between text-sm">
                    <span class="text-dark-400"><?= t('marketplace.price', 'Price') ?></span>
                    <span class="text-white" x-text="'$' + parseFloat(form.price || 0).toFixed(2)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-dark-400"><?= t('marketplace.buyer_fee', 'Buyer Fee (5%)') ?></span>
                    <span class="text-white" x-text="'+$' + buyerFeeCalc.toFixed(2)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-dark-400"><?= t('marketplace.shipping', 'Shipping') ?></span>
                    <span class="text-white" x-text="'+$' + parseFloat(form.shipping_cost || 0).toFixed(2)"></span>
                </div>
                <div class="flex justify-between text-sm font-bold border-t border-dark-600 pt-2">
                    <span class="text-white"><?= t('marketplace.total', 'Total') ?></span>
                    <span class="text-gold-400" x-text="'$' + buyerTotal.toFixed(2)"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div class="flex items-center justify-between gap-4">
        <a href="/marketplace" class="px-6 py-3 glass rounded-lg text-sm text-dark-300 hover:text-white transition"><?= t('marketplace.cancel', 'Cancel') ?></a>
        <button @click="submitListing()" :disabled="submitting || !canSubmit"
            class="px-8 py-3 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition shadow-lg shadow-gold-500/10 disabled:opacity-50 flex items-center gap-2">
            <i data-lucide="check" class="w-5 h-5"></i>
            <span x-show="!submitting"><?= $isEdit ? 'Save Changes' : t('marketplace.publish_listing', 'Publish Listing') ?></span>
            <span x-show="submitting"><?= $isEdit ? 'Saving...' : t('marketplace.publishing', 'Publishing...') ?></span>
        </button>
    </div>
</div>

<script>
window.__PAGE_DATA = {
    sets: <?= $setsJson ?>,
    userAddresses: <?= $userAddressesJson ?>,
    editListing: <?= $editListingJson ?>
};
</script>
<script src="<?= asset_v('/assets/js/pages/marketplace.js') ?>"></script>
