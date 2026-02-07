<?php
$isLoggedIn = \App\Core\Auth::check();
$priceUsd = $card['market_price'] ? number_format((float)$card['market_price'], 2) : null;
$priceEur = $card['cardmarket_price'] ? number_format((float)$card['cardmarket_price'], 2) : null;
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
                    <div class="bg-dark-800/50 rounded-xl p-3">
                        <div class="flex items-center gap-1.5 text-dark-400 mb-1">
                            <i data-lucide="<?= $icon ?>" class="w-3.5 h-3.5"></i>
                            <span class="text-xs font-bold uppercase tracking-wider"><?= $label ?></span>
                        </div>
                        <p class="text-sm font-bold text-white"><?= htmlspecialchars($value) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($card['card_text'])): ?>
                <div class="mt-4 p-4 bg-dark-800/50 rounded-xl">
                    <p class="text-sm text-dark-200 leading-relaxed"><?= nl2br(htmlspecialchars($card['card_text'])) ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Prices -->
        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-white mb-4 flex items-center gap-2">
                <i data-lucide="banknote" class="w-5 h-5 text-gold-400"></i> Market Prices
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-dark-800/50 rounded-xl p-5">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-bold text-dark-400 uppercase">TCGPlayer (USD)</span>
                    </div>
                    <p class="text-3xl font-display font-bold <?= $priceUsd ? 'text-green-400' : 'text-dark-500' ?>">
                        <?= $priceUsd ? '$' . $priceUsd : 'N/A' ?>
                    </p>
                </div>
                <div class="bg-dark-800/50 rounded-xl p-5">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-bold text-dark-400 uppercase">Cardmarket (EUR)</span>
                    </div>
                    <p class="text-3xl font-display font-bold <?= $priceEur ? 'text-blue-400' : 'text-dark-500' ?>">
                        <?= $priceEur ? '&euro;' . $priceEur : 'N/A' ?>
                    </p>
                    <?php if (!empty($card['cardmarket_url'])): ?>
                        <a href="<?= htmlspecialchars($card['cardmarket_url']) ?>" target="_blank" class="text-xs text-dark-400 hover:text-gold-400 mt-2 inline-flex items-center gap-1">
                            View on Cardmarket <i data-lucide="external-link" class="w-3 h-3"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
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

<script>
function priceChart() {
    return {
        chart: null, days: 90,
        async loadChart() {
            const res = await fetch('/api/cards/price-history/<?= urlencode($card['card_set_id']) ?>?days=' + this.days);
            const data = await res.json();
            const ctx = document.getElementById('priceHistoryChart').getContext('2d');
            if (this.chart) this.chart.destroy();

            const tcgDates = data.tcgplayer.map(p => p.recorded_at);
            const tcgPrices = data.tcgplayer.map(p => parseFloat(p.price));
            const cmDates = data.cardmarket.map(p => p.recorded_at);
            const cmPrices = data.cardmarket.map(p => parseFloat(p.price));
            const allDates = [...new Set([...tcgDates, ...cmDates])].sort();

            if (allDates.length === 0) {
                ctx.font = '14px Inter'; ctx.fillStyle = '#4a6480'; ctx.textAlign = 'center';
                ctx.fillText('No price history available yet', ctx.canvas.width / 2, ctx.canvas.height / 2);
                return;
            }

            this.chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: allDates,
                    datasets: [
                        { label: 'TCGPlayer (USD)', data: tcgDates.map((d, i) => ({ x: d, y: tcgPrices[i] })), borderColor: '#22c55e', backgroundColor: 'rgba(34,197,94,0.05)', borderWidth: 2, tension: 0.3, pointRadius: 0, fill: true },
                        { label: 'Cardmarket (EUR)', data: cmDates.map((d, i) => ({ x: d, y: cmPrices[i] })), borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.05)', borderWidth: 2, tension: 0.3, pointRadius: 0, fill: true }
                    ]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' },
                    plugins: { legend: { labels: { color: '#8ba4c0', font: { size: 11 } } } },
                    scales: { x: { ticks: { color: '#4a6480', maxTicksLimit: 8 }, grid: { color: 'rgba(74,100,128,0.1)' } }, y: { ticks: { color: '#4a6480' }, grid: { color: 'rgba(74,100,128,0.1)' } } }
                }
            });
        }
    }
}
</script>
