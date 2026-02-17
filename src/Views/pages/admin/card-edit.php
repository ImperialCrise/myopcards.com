<div class="space-y-6" x-data="cardEditor()">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900">Edit Card</h1>
            <p class="text-sm text-gray-500 mt-1"><?= htmlspecialchars($card['card_set_id']) ?> &mdash; <?= htmlspecialchars($card['card_name']) ?></p>
        </div>
        <a href="/admin/cards" class="px-3 py-2 glass rounded-lg text-sm text-gray-600 hover:text-gray-900 transition flex items-center gap-1.5"><i data-lucide="arrow-left" class="w-4 h-4"></i> Cards</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="glass rounded-2xl p-4">
            <div class="aspect-[5/7] rounded-xl overflow-hidden bg-gray-100">
                <img src="<?= htmlspecialchars($card['card_image_url'] ?? '') ?: 'about:blank' ?>" alt="" class="w-full h-full object-cover" onerror="cardImgErr(this)">
            </div>
            <div class="mt-3 text-center">
                <a href="/cards/<?= urlencode($card['card_set_id']) ?>" class="text-sm text-blue-600 hover:underline">View public page</a>
            </div>
        </div>

        <div class="lg:col-span-2 glass rounded-2xl p-6 space-y-6">
            <div>
                <h2 class="text-lg font-display font-bold text-gray-900 mb-4">Card Info</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Card Set ID</label>
                        <input type="text" value="<?= htmlspecialchars($card['card_set_id']) ?>" disabled class="w-full px-3 py-2 bg-gray-100 border border-gray-200 rounded-lg text-sm text-gray-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Name</label>
                        <input type="text" x-model="form.card_name" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 focus:outline-none focus:border-gray-400 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Rarity</label>
                        <select x-model="form.rarity" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900">
                            <?php foreach (['SEC','SP','L','SR','R','UC','C','P'] as $r): ?>
                                <option value="<?= $r ?>" <?= ($card['rarity'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Color</label>
                        <input type="text" x-model="form.card_color" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 focus:outline-none focus:border-gray-400 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Type</label>
                        <input type="text" x-model="form.card_type" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 focus:outline-none focus:border-gray-400 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Parallel</label>
                        <input type="text" value="<?= $card['is_parallel'] ? 'Yes' : 'No' ?>" disabled class="w-full px-3 py-2 bg-gray-100 border border-gray-200 rounded-lg text-sm text-gray-500">
                    </div>
                </div>
            </div>

            <div>
                <h2 class="text-lg font-display font-bold text-gray-900 mb-4">Prices</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">USD (TCGPlayer)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">$</span>
                            <input type="number" step="0.01" x-model="form.market_price" class="w-full pl-7 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 focus:outline-none focus:border-gray-400 transition">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">EUR EN (Cardmarket)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">&euro;</span>
                            <input type="number" step="0.01" x-model="form.price_en" class="w-full pl-7 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 focus:outline-none focus:border-gray-400 transition">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">EUR FR (Cardmarket)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">&euro;</span>
                            <input type="number" step="0.01" x-model="form.price_fr" class="w-full pl-7 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 focus:outline-none focus:border-gray-400 transition">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase mb-1">EUR JP (Cardmarket)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">&euro;</span>
                            <input type="number" step="0.01" x-model="form.price_jp" class="w-full pl-7 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 focus:outline-none focus:border-gray-400 transition">
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-xs font-bold text-gray-400 uppercase mb-1">Cardmarket URL</label>
                    <input type="text" x-model="form.cardmarket_url" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 focus:outline-none focus:border-gray-400 transition" placeholder="https://www.cardmarket.com/...">
                </div>
            </div>

            <div class="flex items-center justify-between pt-4 border-t" style="border-color:var(--nav-border)">
                <p class="text-xs text-gray-400">Last updated: <?= $card['price_updated_at'] ? date('M j, Y H:i', strtotime($card['price_updated_at'])) : 'Never' ?></p>
                <button @click="save()" :disabled="saving" class="px-6 py-2.5 bg-gray-900 rounded-lg text-sm font-bold transition hover:bg-gray-800 disabled:opacity-50" style="color:#fff !important">
                    <span x-show="!saving">Save Changes</span>
                    <span x-show="saving">Saving...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function cardEditor() {
    return {
        saving: false,
        form: {
            card_name: <?= json_encode($card['card_name']) ?>,
            rarity: <?= json_encode($card['rarity']) ?>,
            card_color: <?= json_encode($card['card_color']) ?>,
            card_type: <?= json_encode($card['card_type']) ?>,
            market_price: <?= json_encode($card['market_price']) ?>,
            price_en: <?= json_encode($card['price_en']) ?>,
            price_fr: <?= json_encode($card['price_fr']) ?>,
            price_jp: <?= json_encode($card['price_jp']) ?>,
            cardmarket_url: <?= json_encode($card['cardmarket_url']) ?>
        },
        async save() {
            this.saving = true;
            var data = Object.assign({ card_id: <?= $card['id'] ?> }, this.form);
            var res = await apiPost('/admin/card-update', data);
            if (res.success) showToast('Card updated');
            else showToast(res.message || 'Error', 'error');
            this.saving = false;
        }
    }
}
</script>
