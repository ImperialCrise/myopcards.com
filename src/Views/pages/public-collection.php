<?php
$cards = $result['cards'] ?? [];
$collectionUser = $owner ?? [];
$curInfo = \App\Core\Currency::info();
$curValues = [
    'usd' => (float)($result['total_value_usd'] ?? 0),
    'eur_en' => (float)($result['total_value_eur_en'] ?? 0),
    'eur_fr' => (float)($result['total_value_eur_fr'] ?? 0),
    'eur_jp' => (float)($result['total_value_eur_jp'] ?? 0),
];
$currentSort = $filters['sort'] ?? 'set';
$sortOptions = [
    'set' => 'Set / Number',
    'price' => 'Price (High)',
    'price_asc' => 'Price (Low)',
    'rarity' => 'Rarity',
    'name' => 'Name (A-Z)',
    'name_desc' => 'Name (Z-A)',
    'added' => 'Recently Added',
    'qty' => 'Quantity',
];
$baseUrl = '/collection/' . urlencode($collectionUser['username'] ?? '');
?>
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="/user/<?= htmlspecialchars($collectionUser['username'] ?? '') ?>" class="text-gold-400 hover:text-gold-300 transition">
                    <i data-lucide="arrow-left" class="w-4 h-4 inline"></i> <?= htmlspecialchars($collectionUser['username'] ?? '') ?>
                </a>
            </div>
            <h1 class="text-2xl font-display font-bold text-white mt-1"><?= htmlspecialchars($collectionUser['username'] ?? '') ?>'s Collection</h1>
            <p class="text-sm text-dark-400 mt-1"><?= number_format($result['total'] ?? 0) ?> unique cards</p>
        </div>
    </div>

    <!-- Value Summary -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="glass rounded-xl p-4 text-center">
            <p class="text-xs text-dark-400 uppercase tracking-wider font-bold mb-1">Unique Cards</p>
            <p class="text-xl font-display font-bold text-white"><?= number_format($stats['unique_cards'] ?? 0) ?></p>
        </div>
        <div class="glass rounded-xl p-4 text-center">
            <p class="text-xs text-dark-400 uppercase tracking-wider font-bold mb-1">Total Qty</p>
            <p class="text-xl font-display font-bold text-white"><?= number_format($stats['total_cards'] ?? 0) ?></p>
        </div>
        <div class="glass rounded-xl p-4 text-center">
            <p class="text-xs text-dark-400 uppercase tracking-wider font-bold mb-1">Value (<?= $curInfo['label'] ?>)</p>
            <p class="text-xl font-display font-bold text-gold-400"><?= $curInfo['symbol'] . number_format($curValues[$curInfo['key']] ?? 0, 2) ?></p>
        </div>
    </div>

    <!-- Sort -->
    <div class="glass rounded-xl p-4">
        <form method="GET" action="<?= $baseUrl ?>" onsubmit="event.preventDefault(); cleanSubmit(this);" class="flex flex-wrap gap-3 items-center">
            <div class="relative flex-1 min-w-[180px]">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-dark-400"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="Search name or ID..."
                    class="w-full pl-9 pr-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition"
                    onchange="cleanSubmit(this.form)">
            </div>
            <select name="set_id" onchange="cleanSubmit(this.form)" class="px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                <option value="">All Sets</option>
                <?php foreach ($sets as $s): ?>
                    <option value="<?= htmlspecialchars($s) ?>" <?= ($filters['set_id'] ?? '') === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="flex items-center gap-1.5 ml-auto">
                <i data-lucide="arrow-up-down" class="w-4 h-4 text-dark-400"></i>
                <select name="sort" onchange="cleanSubmit(this.form)" class="px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <?php foreach ($sortOptions as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $currentSort === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($filters['q']) || !empty($filters['set_id'])): ?>
                <a href="<?= $baseUrl ?>" class="text-xs text-dark-400 hover:text-gold-400 transition">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!empty($cards)): ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
        <?php foreach ($cards as $card): ?>
            <a href="/cards/<?= urlencode($card['card_set_id']) ?>" class="group card-hover">
                <div class="glass rounded-xl overflow-hidden">
                    <div class="relative aspect-[5/7] bg-dark-700">
                        <img src="<?= htmlspecialchars($card['card_image_url'] ?? '') ?: 'about:blank' ?>" alt="" class="w-full h-full object-cover" loading="lazy" onerror="cardImgErr(this)">
                        <span class="absolute top-1.5 right-1.5 px-2 py-0.5 bg-dark-900/80 text-white text-xs font-bold rounded-full"><?= (int)($card['quantity'] ?? 1) ?>x</span>
                        <?php if (!empty($card['rarity'])): ?>
                            <?php
                                $rc = ['SEC' => 'from-gold-500 to-amber-600', 'SP' => 'from-purple-500 to-pink-500', 'SR' => 'from-blue-500 to-cyan-500', 'R' => 'from-emerald-500 to-green-500', 'L' => 'from-gold-500 to-amber-500'];
                                $rb = $rc[$card['rarity']] ?? 'from-gray-500 to-gray-600';
                            ?>
                            <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 text-[10px] font-bold text-white bg-gradient-to-r <?= $rb ?> rounded shadow"><?= htmlspecialchars($card['rarity']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="p-2">
                        <p class="text-xs font-bold text-white truncate"><?= htmlspecialchars($card['card_name']) ?></p>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-[10px] text-dark-400"><?= htmlspecialchars($card['card_set_id']) ?></span>
                            <?php
                                $cardPrice = \App\Core\Currency::priceFromCard($card);
                                if ($cardPrice <= 0) $cardPrice = (float)($card['market_price'] ?? 0);
                            ?>
                            <?php if ($cardPrice > 0): ?>
                                <span class="text-[10px] font-bold text-gold-400"><?= \App\Core\Currency::format($cardPrice) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (($result['total_pages'] ?? 1) > 1): ?>
        <div class="mt-8 flex justify-center gap-2">
            <?php
                $totalPages = $result['total_pages'];
                $current = $result['page'];
                $qs = $_GET; unset($qs['page']);
                $base = $baseUrl . '?' . http_build_query($qs);
            ?>
            <?php if ($current > 1): ?>
                <a href="<?= $base ?>&page=<?= $current - 1 ?>" class="px-3 py-2 glass rounded-lg text-sm text-dark-300 hover:text-white transition"><i data-lucide="chevron-left" class="w-4 h-4"></i></a>
            <?php endif; ?>
            <?php for ($p = max(1, $current - 3); $p <= min($totalPages, $current + 3); $p++): ?>
                <a href="<?= $base ?>&page=<?= $p ?>"
                   class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $p === $current ? 'bg-gold-500 text-dark-900 font-bold' : 'glass text-dark-300 hover:text-white' ?>">
                    <?= $p ?>
                </a>
            <?php endfor; ?>
            <?php if ($current < $totalPages): ?>
                <a href="<?= $base ?>&page=<?= $current + 1 ?>" class="px-3 py-2 glass rounded-lg text-sm text-dark-300 hover:text-white transition"><i data-lucide="chevron-right" class="w-4 h-4"></i></a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php else: ?>
        <div class="text-center py-16">
            <div class="w-16 h-16 rounded-2xl bg-dark-700/50 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="package-open" class="w-8 h-8 text-dark-400"></i>
            </div>
            <h3 class="text-lg font-display font-bold text-dark-300">Empty collection</h3>
            <p class="text-sm text-dark-400 mt-2">This user hasn't added any cards yet.</p>
        </div>
    <?php endif; ?>
</div>
