<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-display font-bold text-white">Market Overview</h1>
        <p class="text-sm text-dark-400 mt-1">One Piece TCG market trends and rankings</p>
    </div>

    <!-- Price Movers (fetched async) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-data="priceMovers()" x-init="load()">
        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
                <i data-lucide="trending-up" class="w-5 h-5 text-green-400"></i> Top Gainers (7d)
            </h2>
            <div class="space-y-2">
                <template x-if="gainers.length === 0"><p class="text-sm text-dark-400 text-center py-4">No price data yet. Check back after price sync runs.</p></template>
                <template x-for="card in gainers" :key="card.id">
                    <a :href="'/cards/' + card.card_set_id" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition">
                        <img :src="card.card_image_url" class="w-8 h-11 rounded object-cover bg-dark-700" onerror="this.style.display='none'">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-white truncate" x-text="card.card_name"></p>
                            <p class="text-xs text-dark-400" x-text="card.card_set_id"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-green-400" x-text="'+$' + parseFloat(card.price_change).toFixed(2)"></p>
                            <p class="text-xs text-green-400/70" x-text="'+' + card.pct_change + '%'"></p>
                        </div>
                    </a>
                </template>
            </div>
        </div>
        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
                <i data-lucide="trending-down" class="w-5 h-5 text-red-400"></i> Top Losers (7d)
            </h2>
            <div class="space-y-2">
                <template x-if="losers.length === 0"><p class="text-sm text-dark-400 text-center py-4">No price data yet.</p></template>
                <template x-for="card in losers" :key="card.id">
                    <a :href="'/cards/' + card.card_set_id" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition">
                        <img :src="card.card_image_url" class="w-8 h-11 rounded object-cover bg-dark-700" onerror="this.style.display='none'">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-white truncate" x-text="card.card_name"></p>
                            <p class="text-xs text-dark-400" x-text="card.card_set_id"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-red-400" x-text="'$' + parseFloat(card.price_change).toFixed(2)"></p>
                            <p class="text-xs text-red-400/70" x-text="card.pct_change + '%'"></p>
                        </div>
                    </a>
                </template>
            </div>
        </div>
    </div>

    <!-- Most Expensive -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="gem" class="w-5 h-5 text-gold-400"></i> Most Expensive Cards
        </h2>
        <?php if (empty($expensive)): ?>
            <p class="text-sm text-dark-400 text-center py-4">No price data available yet.</p>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <?php foreach (array_slice($expensive, 0, 10) as $i => $card): ?>
                    <a href="/cards/<?= urlencode($card['card_set_id']) ?>" class="group card-hover">
                        <div class="glass rounded-xl overflow-hidden">
                            <div class="relative aspect-[5/7] bg-dark-700">
                                <img src="<?= htmlspecialchars($card['card_image_url'] ?? '') ?>" alt="" class="w-full h-full object-cover" loading="lazy" onerror="this.parentElement.classList.add('skeleton');this.style.display='none'">
                                <span class="absolute top-1.5 left-1.5 px-2 py-0.5 bg-dark-900/80 text-gold-400 text-xs font-bold rounded">#<?= $i + 1 ?></span>
                            </div>
                            <div class="p-2.5">
                                <p class="text-xs font-bold text-white truncate"><?= htmlspecialchars($card['card_name']) ?></p>
                                <div class="flex items-center justify-between mt-1">
                                    <span class="text-[10px] text-dark-400"><?= htmlspecialchars($card['card_set_id']) ?></span>
                                    <span class="text-xs font-bold text-gold-400">$<?= number_format((float)$card['market_price'], 2) ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Most Collected -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="heart" class="w-5 h-5 text-red-400"></i> Most Collected Cards
        </h2>
        <?php if (empty($collected)): ?>
            <p class="text-sm text-dark-400 text-center py-4">No collection data yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-xs text-dark-400 uppercase border-b border-dark-600">
                        <th class="text-left pb-3 pr-4">#</th><th class="text-left pb-3 pr-4">Card</th><th class="text-right pb-3 pr-4">Collectors</th><th class="text-right pb-3 pr-4">Total Owned</th><th class="text-right pb-3">Price</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($collected as $i => $c): ?>
                        <tr class="border-b border-dark-700/50">
                            <td class="py-3 pr-4 text-dark-400 font-bold"><?= $i + 1 ?></td>
                            <td class="py-3 pr-4">
                                <a href="/cards/<?= urlencode($c['card_set_id']) ?>" class="flex items-center gap-3 hover:text-gold-400 transition">
                                    <img src="<?= htmlspecialchars($c['card_image_url'] ?? '') ?>" class="w-7 h-10 rounded object-cover bg-dark-700" onerror="this.style.display='none'" loading="lazy">
                                    <div><p class="text-white font-medium"><?= htmlspecialchars($c['card_name']) ?></p><p class="text-xs text-dark-400"><?= htmlspecialchars($c['card_set_id']) ?></p></div>
                                </a>
                            </td>
                            <td class="py-3 pr-4 text-right text-blue-400 font-bold"><?= $c['collector_count'] ?></td>
                            <td class="py-3 pr-4 text-right text-dark-300"><?= number_format($c['total_owned']) ?></td>
                            <td class="py-3 text-right text-gold-400 font-bold"><?= $c['market_price'] ? '$' . number_format((float)$c['market_price'], 2) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Set Value Summary -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="package" class="w-5 h-5 text-purple-400"></i> Set Value Summary
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="text-xs text-dark-400 uppercase border-b border-dark-600">
                    <th class="text-left pb-3 pr-4">Set</th><th class="text-right pb-3 pr-4">Cards</th><th class="text-right pb-3 pr-4">Total (USD)</th><th class="text-right pb-3 pr-4">Total (EUR)</th><th class="text-right pb-3">Avg Price</th>
                </tr></thead>
                <tbody>
                <?php foreach ($setSummary as $ss): ?>
                    <tr class="border-b border-dark-700/50">
                        <td class="py-3 pr-4">
                            <a href="/cards?set_id=<?= urlencode($ss['set_id']) ?>" class="text-white hover:text-gold-400 transition">
                                <?= htmlspecialchars($ss['set_name'] ?: $ss['set_id']) ?>
                            </a>
                            <span class="text-xs text-dark-500 ml-1"><?= htmlspecialchars($ss['set_id']) ?></span>
                        </td>
                        <td class="py-3 pr-4 text-right text-dark-300"><?= $ss['card_count_actual'] ?></td>
                        <td class="py-3 pr-4 text-right text-gold-400 font-bold">$<?= number_format((float)$ss['total_value_usd'], 2) ?></td>
                        <td class="py-3 pr-4 text-right text-blue-400 font-bold">&euro;<?= number_format((float)$ss['total_value_eur'], 2) ?></td>
                        <td class="py-3 text-right text-dark-300">$<?= number_format((float)$ss['avg_price_usd'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recently Synced -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="sparkles" class="w-5 h-5 text-cyan-400"></i> Recently Added Cards
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php foreach ($recentCards as $card): ?>
                <a href="/cards/<?= urlencode($card['card_set_id']) ?>" class="group card-hover">
                    <div class="glass rounded-xl overflow-hidden">
                        <div class="aspect-[5/7] bg-dark-700">
                            <img src="<?= htmlspecialchars($card['card_image_url'] ?? '') ?>" class="w-full h-full object-cover" loading="lazy" onerror="this.parentElement.classList.add('skeleton');this.style.display='none'">
                        </div>
                        <div class="p-2">
                            <p class="text-xs font-bold text-white truncate"><?= htmlspecialchars($card['card_name']) ?></p>
                            <p class="text-[10px] text-dark-400"><?= htmlspecialchars($card['card_set_id']) ?></p>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="/assets/js/pages/market.js"></script>
