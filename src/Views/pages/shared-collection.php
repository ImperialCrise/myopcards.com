<?php
$cards = $result['cards'] ?? [];
$totalValueUsd = $result['total_value_usd'] ?? 0;
$totalValueEur = $result['total_value_eur'] ?? 0;
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
$baseUrl = '/s/' . urlencode($token);
?>
<div class="space-y-6">
    <!-- Back to profile -->
    <div class="flex items-center gap-2">
        <a href="/user/<?= htmlspecialchars($owner['username'] ?? '') ?>" class="text-gold-400 hover:text-gold-300 transition flex items-center gap-1.5 text-sm">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> <?= htmlspecialchars($owner['username'] ?? '') ?>'s Profile
        </a>
    </div>

    <!-- Owner Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <?php if (!empty($owner['avatar'])): ?>
                <img src="<?= htmlspecialchars($owner['avatar']) ?>" alt="" class="w-14 h-14 rounded-full border-2 border-dark-600">
            <?php else: ?>
                <div class="w-14 h-14 rounded-full bg-gradient-to-br from-gold-500 to-amber-600 flex items-center justify-center text-2xl font-bold text-dark-900">
                    <?= strtoupper(substr($owner['username'] ?? '?', 0, 1)) ?>
                </div>
            <?php endif; ?>
            <div>
                <h1 class="text-2xl font-display font-bold text-white"><?= htmlspecialchars($owner['username']) ?>'s Collection</h1>
                <p class="text-sm text-dark-400 mt-0.5"><?= number_format($result['total'] ?? 0) ?> unique cards</p>
            </div>
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
            <p class="text-xs text-dark-400 uppercase tracking-wider font-bold mb-1">Value (USD)</p>
            <p class="text-xl font-display font-bold text-green-400">$<?= number_format($totalValueUsd, 2) ?></p>
        </div>
        <div class="glass rounded-xl p-4 text-center">
            <p class="text-xs text-dark-400 uppercase tracking-wider font-bold mb-1">Value (EUR)</p>
            <p class="text-xl font-display font-bold text-blue-400">&euro;<?= number_format($totalValueEur, 2) ?></p>
        </div>
    </div>

    <!-- Filters + Sort -->
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
            <select name="rarity" onchange="cleanSubmit(this.form)" class="px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                <option value="">All Rarities</option>
                <?php foreach ($rarities as $r): ?>
                    <option value="<?= htmlspecialchars($r) ?>" <?= ($filters['rarity'] ?? '') === $r ? 'selected' : '' ?>><?= htmlspecialchars($r) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="color" onchange="cleanSubmit(this.form)" class="px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                <option value="">All Colors</option>
                <?php foreach ($colors as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= ($filters['color'] ?? '') === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
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
            <?php if (!empty($filters['q']) || !empty($filters['set_id']) || !empty($filters['rarity']) || !empty($filters['color'])): ?>
                <a href="<?= $baseUrl ?>" class="text-xs text-dark-400 hover:text-gold-400 transition">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Cards Grid -->
    <?php if (!empty($cards)): ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
        <?php foreach ($cards as $card): ?>
            <div class="group card-hover">
                <div class="glass rounded-xl overflow-hidden">
                    <a href="/cards/<?= urlencode($card['card_set_id']) ?>">
                        <div class="relative aspect-[5/7] bg-dark-700">
                            <img src="<?= htmlspecialchars($card['card_image_url'] ?? '') ?>" alt="" class="w-full h-full object-cover" loading="lazy" onerror="this.parentElement.classList.add('skeleton');this.style.display='none'">
                            <span class="absolute top-1.5 right-1.5 px-2 py-0.5 bg-dark-900/80 text-white text-xs font-bold rounded-full"><?= (int)($card['quantity'] ?? 1) ?>x</span>
                            <?php if (!empty($card['rarity'])): ?>
                                <?php
                                    $rc = ['SEC' => 'from-gold-500 to-amber-600', 'SP' => 'from-purple-500 to-pink-500', 'SR' => 'from-blue-500 to-cyan-500', 'R' => 'from-emerald-500 to-green-500', 'L' => 'from-gold-500 to-amber-500'];
                                    $rb = $rc[$card['rarity']] ?? 'from-gray-500 to-gray-600';
                                ?>
                                <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 text-[10px] font-bold text-white bg-gradient-to-r <?= $rb ?> rounded shadow"><?= htmlspecialchars($card['rarity']) ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="p-2">
                        <p class="text-xs font-bold text-white truncate"><?= htmlspecialchars($card['card_name']) ?></p>
                        <div class="flex items-center justify-between mt-1">
                            <span class="text-[10px] text-dark-400"><?= htmlspecialchars($card['card_set_id']) ?></span>
                            <?php if (!empty($card['market_price'])): ?>
                                <span class="text-[10px] font-bold text-gold-400">$<?= number_format((float)$card['market_price'], 2) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
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
            <h3 class="text-lg font-display font-bold text-dark-300">This collection is empty</h3>
        </div>
    <?php endif; ?>

    <!-- View stats -->
    <div class="glass rounded-xl p-4 flex items-center justify-between">
        <span class="text-sm text-dark-400 flex items-center gap-1.5"><i data-lucide="eye" class="w-4 h-4"></i> <?= number_format($viewCounts['total'] ?? 0) ?> total views</span>
        <a href="/user/<?= htmlspecialchars($owner['username'] ?? '') ?>" class="text-xs text-gold-400 hover:text-gold-300 transition flex items-center gap-1">
            <i data-lucide="user" class="w-3 h-3"></i> View profile
        </a>
    </div>
</div>
