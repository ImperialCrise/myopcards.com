<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-display font-bold text-white">Collection Analytics</h1>
        <p class="text-sm text-dark-400 mt-1">Deep dive into your collection stats</p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="glass rounded-2xl p-5">
            <div class="flex items-center gap-2 mb-2"><i data-lucide="layers" class="w-4 h-4 text-blue-400"></i><span class="text-xs font-bold text-dark-400 uppercase">Unique</span></div>
            <p class="text-2xl font-display font-bold text-white"><?= number_format($stats['unique_cards'] ?? 0) ?></p>
        </div>
        <div class="glass rounded-2xl p-5">
            <div class="flex items-center gap-2 mb-2"><i data-lucide="copy" class="w-4 h-4 text-green-400"></i><span class="text-xs font-bold text-dark-400 uppercase">Total</span></div>
            <p class="text-2xl font-display font-bold text-white"><?= number_format($stats['total_cards'] ?? 0) ?></p>
        </div>
        <div class="glass rounded-2xl p-5">
            <div class="flex items-center gap-2 mb-2"><i data-lucide="banknote" class="w-4 h-4 text-gold-400"></i><span class="text-xs font-bold text-dark-400 uppercase"><?= ($stats['total_value_label'] ?? 'USD') ?> Value</span></div>
            <p class="text-2xl font-display font-bold text-white"><?= ($stats['total_value_symbol'] ?? '$') . number_format((float)($stats['total_value'] ?? 0), 2) ?></p>
        </div>
        <div class="glass rounded-2xl p-5">
            <div class="flex items-center gap-2 mb-2"><i data-lucide="percent" class="w-4 h-4 text-purple-400"></i><span class="text-xs font-bold text-dark-400 uppercase">Completion</span></div>
            <?php $totalInDb = \App\Models\Card::getTotalCount(); $pct = $totalInDb > 0 ? round(($stats['unique_cards'] ?? 0) / $totalInDb * 100, 1) : 0; ?>
            <p class="text-2xl font-display font-bold text-white"><?= $pct ?>%</p>
        </div>
    </div>

    <!-- Value Timeline -->
    <div class="glass rounded-2xl p-6" x-data="valueTimeline()" x-init="load()">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-display font-bold text-white flex items-center gap-2">
                <i data-lucide="trending-up" class="w-5 h-5 text-gold-400"></i> Value Over Time
            </h2>
            <div class="flex gap-1">
                <template x-for="d in [30, 90, 365]" :key="d">
                    <button @click="days = d; load()" :class="days === d ? 'bg-gold-500 text-dark-900' : 'glass text-dark-300'"
                        class="px-3 py-1 rounded text-xs font-bold transition" x-text="d + 'd'"></button>
                </template>
            </div>
        </div>
        <div class="h-72"><canvas id="analyticsValueChart"></canvas></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Color Distribution -->
        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
                <i data-lucide="palette" class="w-5 h-5 text-red-400"></i> By Color
            </h2>
            <div class="h-56 flex items-center justify-center"><canvas id="analyticsColorChart"></canvas></div>
        </div>

        <!-- Rarity Distribution -->
        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
                <i data-lucide="gem" class="w-5 h-5 text-blue-400"></i> By Rarity
            </h2>
            <div class="h-56"><canvas id="analyticsRarityChart"></canvas></div>
        </div>

        <!-- Type Distribution -->
        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
                <i data-lucide="shapes" class="w-5 h-5 text-green-400"></i> By Type
            </h2>
            <div class="h-56 flex items-center justify-center"><canvas id="analyticsTypeChart"></canvas></div>
        </div>

        <!-- Set Distribution -->
        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
                <i data-lucide="package" class="w-5 h-5 text-amber-400"></i> By Set
            </h2>
            <div class="h-56"><canvas id="analyticsSetChart"></canvas></div>
        </div>
    </div>

    <!-- Top 10 Most Valuable -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="trophy" class="w-5 h-5 text-gold-400"></i> Top 10 Most Valuable Cards
        </h2>
        <?php if (empty($topCards)): ?>
            <p class="text-sm text-dark-400 text-center py-4">No valued cards in your collection yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="text-xs text-dark-400 uppercase border-b border-dark-600">
                        <th class="text-left pb-3 pr-4">#</th><th class="text-left pb-3 pr-4">Card</th><th class="text-right pb-3 pr-4">Qty</th><th class="text-right pb-3 pr-4">Price</th><th class="text-right pb-3">Total</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($topCards as $i => $tc): ?>
                        <tr class="border-b border-dark-700/50">
                            <td class="py-3 pr-4 text-dark-400 font-bold"><?= $i + 1 ?></td>
                            <td class="py-3 pr-4">
                                <a href="/cards/<?= urlencode($tc['card_set_id']) ?>" class="flex items-center gap-3 hover:text-gold-400 transition">
                                    <img src="<?= htmlspecialchars($tc['card_image_url'] ?? '') ?: 'about:blank' ?>" class="w-7 h-10 rounded object-cover bg-dark-700" onerror="cardImgErr(this)" loading="lazy">
                                    <div>
                                        <p class="text-white font-medium"><?= htmlspecialchars($tc['card_name']) ?></p>
                                        <p class="text-xs text-dark-400"><?= htmlspecialchars($tc['card_set_id']) ?> &middot; <?= htmlspecialchars($tc['rarity'] ?? '') ?></p>
                                    </div>
                                </a>
                            </td>
                            <td class="py-3 pr-4 text-right text-dark-300"><?= $tc['quantity'] ?>x</td>
                            <?php $cardP = \App\Core\Currency::priceFromCard($tc); if ($cardP <= 0) $cardP = (float)($tc['market_price'] ?? 0); ?>
                            <td class="py-3 pr-4 text-right text-gold-400 font-bold"><?= \App\Core\Currency::format($cardP) ?></td>
                            <td class="py-3 text-right text-white font-bold"><?= \App\Core\Currency::format((float)$tc['total_value']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Completion Matrix -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="grid-3x3" class="w-5 h-5 text-cyan-400"></i> Set Completion Matrix
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
            <?php foreach ($setCompletion as $sc): ?>
                <?php $pctComp = $sc['card_count'] > 0 ? round($sc['owned'] / $sc['card_count'] * 100) : 0; ?>
                <a href="/cards?set_id=<?= urlencode($sc['set_id']) ?>" class="block bg-gray-50 border border-gray-100 rounded-xl p-4 hover:bg-gray-100 transition text-center group">
                    <p class="text-3xl font-display font-bold <?= $pctComp >= 100 ? 'text-gold-400' : ($pctComp >= 50 ? 'text-green-400' : 'text-dark-300') ?>"><?= $pctComp ?>%</p>
                    <p class="text-xs text-dark-400 mt-1 truncate group-hover:text-gold-400 transition"><?= htmlspecialchars($sc['set_name'] ?? $sc['set_id']) ?></p>
                    <p class="text-[10px] text-dark-500 mt-0.5"><?= $sc['owned'] ?>/<?= $sc['card_count'] ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="/assets/js/pages/analytics.js"></script>
