<?php
$isLoggedIn = \App\Core\Auth::check();
$priceUsd = $card['market_price'] ? number_format((float)$card['market_price'], 2) : null;
$priceEn = $card['price_en'] ? number_format((float)$card['price_en'], 2) : ($card['cardmarket_price'] ? number_format((float)$card['cardmarket_price'], 2) : null);
$priceFr = $card['price_fr'] ? number_format((float)$card['price_fr'], 2) : null;
$priceJp = $card['price_jp'] ? number_format((float)$card['price_jp'], 2) : null;
$rarityColors = ['SEC' => 'from-gold-500 to-amber-600', 'SP' => 'from-purple-500 to-pink-500', 'SR' => 'from-blue-500 to-cyan-500', 'R' => 'from-emerald-500 to-green-500', 'L' => 'from-gold-500 to-amber-500'];
$rarityBg = $rarityColors[$card['rarity']] ?? 'from-gray-500 to-gray-600';
?>

<div class="mb-4">
    <a href="/cards" class="inline-flex items-center gap-1 text-sm text-dark-400 hover:text-gold-400 transition">
        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Cards
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Card Image -->
    <div class="lg:col-span-1">
        <div class="glass rounded-2xl p-4 sticky top-24">
            <div class="aspect-[5/7] rounded-xl overflow-hidden bg-dark-700">
                <img src="<?= htmlspecialchars($card['card_image_url'] ?? '') ?>" alt="<?= htmlspecialchars($card['card_name']) ?>" class="w-full h-full object-cover">
            </div>

            <?php if ($isLoggedIn): ?>
                <div class="mt-4" x-data="{ qty: <?= $userOwns ?>, updating: false }">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-dark-300">In your collection</span>
                        <span class="text-sm font-bold text-white" x-text="qty + 'x'"></span>
                    </div>
                    <div class="flex gap-2">
                        <button @click="if(qty > 0) { qty--; updating = true; apiPost(qty === 0 ? '/collection/remove' : '/collection/update', { card_id: <?= $card['id'] ?>, quantity: qty }).then(() => { updating = false; showToast('Updated'); }); }"
                            :disabled="qty === 0 || updating"
                            class="flex-1 py-2 glass rounded-lg text-sm font-medium text-dark-300 hover:text-white transition disabled:opacity-30">
                            <i data-lucide="minus" class="w-4 h-4 mx-auto"></i>
                        </button>
                        <button @click="qty++; updating = true; apiPost('/collection/add', { card_id: <?= $card['id'] ?>, quantity: 1 }).then(() => { updating = false; showToast('Added to collection'); });"
                            :disabled="updating"
                            class="flex-1 py-2 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition disabled:opacity-50">
                            <i data-lucide="plus" class="w-4 h-4 mx-auto"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Card Info + Chart -->
    <div class="lg:col-span-2 space-y-6">
        <div class="glass rounded-2xl p-6">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h1 class="text-3xl font-display font-bold text-white"><?= htmlspecialchars($card['card_name']) ?></h1>
                    <p class="text-dark-400 mt-1"><?= htmlspecialchars($card['card_set_id']) ?> &middot; <?= htmlspecialchars($card['set_name'] ?? '') ?></p>
                </div>
                <span class="px-3 py-1.5 text-sm font-bold text-white bg-gradient-to-r <?= $rarityBg ?> rounded-lg"><?= htmlspecialchars($card['rarity'] ?? 'N/A') ?></span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <?php
                $attrs = [
                    ['Color', $card['card_color'] ?? '', 'palette'],
                    ['Type', $card['card_type'] ?? '', 'tag'],
                    ['Cost', $card['card_cost'] ?? '', 'coins'],
                    ['Power', $card['power'] ?? '', 'zap'],
                ];
                foreach ($attrs as [$label, $value, $icon]):
                    if (empty($value)) continue;
                ?>
                    <div class="bg-gray-50 border border-gray-100 rounded-xl p-3">
                        <div class="flex items-center gap-1.5 text-gray-400 mb-1">
                            <i data-lucide="<?= $icon ?>" class="w-3.5 h-3.5"></i>
                            <span class="text-xs font-bold uppercase tracking-wider"><?= $label ?></span>
                        </div>
                        <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($value) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($card['card_text'])): ?>
                <div class="mt-4 p-4 bg-gray-50 border border-gray-100 rounded-xl">
                    <p class="text-sm text-gray-700 leading-relaxed"><?= nl2br(htmlspecialchars($card['card_text'])) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Prices -->
        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-white mb-4 flex items-center gap-2">
                <i data-lucide="banknote" class="w-5 h-5 text-gold-400"></i> Market Prices
            </h2>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                    <div class="flex items-center gap-1.5 mb-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">TCGPlayer (USD)</span>
                    </div>
                    <p class="text-2xl font-display font-bold <?= $priceUsd ? 'text-emerald-600' : 'text-gray-300' ?>">
                        <?= $priceUsd ? '$' . $priceUsd : 'N/A' ?>
                    </p>
                </div>
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                    <div class="flex items-center gap-1.5 mb-2">
                        <span class="inline-block w-4 h-3 rounded-sm bg-gradient-to-r from-blue-600 via-white to-red-500 mr-1 border border-gray-200"></span>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">EN (EUR)</span>
                    </div>
                    <p class="text-2xl font-display font-bold <?= $priceEn ? 'text-blue-600' : 'text-gray-300' ?>">
                        <?= $priceEn ? '&euro;' . $priceEn : 'N/A' ?>
                    </p>
                </div>
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                    <div class="flex items-center gap-1.5 mb-2">
                        <span class="inline-block w-4 h-3 rounded-sm bg-gradient-to-b from-blue-700 via-white to-red-600 mr-1 border border-gray-200"></span>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">FR (EUR)</span>
                    </div>
                    <p class="text-2xl font-display font-bold <?= $priceFr ? 'text-indigo-600' : 'text-gray-300' ?>">
                        <?= $priceFr ? '&euro;' . $priceFr : 'N/A' ?>
                    </p>
                </div>
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                    <div class="flex items-center gap-1.5 mb-2">
                        <span class="inline-block w-4 h-3 rounded-sm bg-white mr-1 relative border border-gray-200"><span class="absolute inset-0 flex items-center justify-center"><span class="w-2 h-2 rounded-full bg-red-600"></span></span></span>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">JP (EUR)</span>
                    </div>
                    <p class="text-2xl font-display font-bold <?= $priceJp ? 'text-red-600' : 'text-gray-300' ?>">
                        <?= $priceJp ? '&euro;' . $priceJp : 'N/A' ?>
                    </p>
                </div>
            </div>
            <?php if (!empty($card['cardmarket_url'])): ?>
                <a href="<?= htmlspecialchars($card['cardmarket_url']) ?>" target="_blank" class="text-xs text-dark-400 hover:text-gold-400 mt-3 inline-flex items-center gap-1">
                    View on Cardmarket <i data-lucide="external-link" class="w-3 h-3"></i>
                </a>
            <?php endif; ?>
        </div>

        <!-- Price Chart -->
        <div class="glass rounded-2xl p-6" x-data="priceChart()" x-init="loadChart()">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-display font-bold text-white flex items-center gap-2">
                    <i data-lucide="line-chart" class="w-5 h-5 text-gold-400"></i> Price History
                </h2>
                <div class="flex gap-1">
                    <template x-for="d in [30, 90, 365]" :key="d">
                        <button @click="days = d; loadChart()" :class="days === d ? 'bg-gold-500 text-dark-900' : 'glass text-dark-300'"
                            class="px-3 py-1 rounded text-xs font-bold transition" x-text="d + 'd'"></button>
                    </template>
                </div>
            </div>
            <div class="h-64"><canvas id="priceHistoryChart"></canvas></div>
        </div>
    </div>
</div>

<script>window.__PAGE_DATA = { cardSetId: <?= json_encode($card['card_set_id']) ?> };</script>
<script src="/assets/js/pages/card-detail.js"></script>
