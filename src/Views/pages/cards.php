<div class="flex flex-col lg:flex-row gap-6">
    <!-- Sidebar Filters -->
    <aside class="lg:w-64 flex-shrink-0 lg:sticky lg:top-24 lg:self-start" x-data="{ filtersOpen: window.innerWidth >= 1024 }">
        <button @click="filtersOpen = !filtersOpen" class="lg:hidden w-full flex items-center justify-between glass rounded-xl px-4 py-3 mb-2">
            <span class="text-sm font-medium text-white flex items-center gap-2"><i data-lucide="sliders-horizontal" class="w-4 h-4"></i> Filters</span>
            <i data-lucide="chevron-down" class="w-4 h-4 text-dark-400 transition" :class="filtersOpen && 'rotate-180'"></i>
        </button>
        <form method="GET" action="/cards" x-show="filtersOpen" x-transition class="glass rounded-xl p-5 space-y-4">
            <div>
                <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5">Search</label>
                <div class="relative">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-dark-400"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Card name or ID..."
                        class="w-full pl-9 pr-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5">Set</label>
                <select name="set_id" class="w-full px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <option value="">All Sets</option>
                    <?php foreach ($sets as $s): ?><option value="<?= htmlspecialchars($s) ?>" <?= $filters['set_id'] === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5">Color</label>
                <select name="color" class="w-full px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <option value="">All Colors</option>
                    <?php foreach ($colors as $c): ?><option value="<?= htmlspecialchars($c) ?>" <?= $filters['color'] === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5">Rarity</label>
                <select name="rarity" class="w-full px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <option value="">All Rarities</option>
                    <?php foreach ($rarities as $r): ?><option value="<?= htmlspecialchars($r) ?>" <?= $filters['rarity'] === $r ? 'selected' : '' ?>><?= htmlspecialchars($r) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-dark-400 uppercase tracking-wider mb-1.5">Type</label>
                <select name="type" class="w-full px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <option value="">All Types</option>
                    <?php foreach ($types as $t): ?><option value="<?= htmlspecialchars($t) ?>" <?= $filters['type'] === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option><?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="w-full py-2.5 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition">
                Apply Filters
            </button>
            <a href="/cards" class="block text-center text-xs text-dark-400 hover:text-dark-300 transition">Reset filters</a>
        </form>
    </aside>

    <!-- Card Grid -->
    <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-display font-bold text-white">Card Database</h1>
                <p class="text-sm text-dark-400 mt-1"><?= number_format($result['total']) ?> cards found</p>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-4">
            <?php foreach ($result['cards'] as $card): ?>
                <a href="/cards/<?= urlencode($card['card_set_id']) ?>" class="group card-hover">
                    <div class="glass rounded-xl overflow-hidden">
                        <div class="relative aspect-[5/7] bg-dark-700">
                            <img src="<?= htmlspecialchars($card['card_image_url'] ?? '') ?>" alt="<?= htmlspecialchars($card['card_name']) ?>"
                                class="w-full h-full object-cover" loading="lazy"
                                onerror="this.parentElement.classList.add('skeleton');this.style.display='none'">
                            <?php if (isset($userCards[$card['id']])): ?>
                                <div class="absolute top-1.5 right-1.5 w-6 h-6 bg-green-500 rounded-full flex items-center justify-center shadow-lg">
                                    <i data-lucide="check" class="w-3 h-3 text-white"></i>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($card['rarity'])): ?>
                                <?php
                                    $rarityColors = ['SEC' => 'from-gold-500 to-amber-600', 'SP' => 'from-purple-500 to-pink-500', 'SR' => 'from-blue-500 to-cyan-500', 'R' => 'from-emerald-500 to-green-500', 'L' => 'from-gold-500 to-amber-500'];
                                    $rarityBg = $rarityColors[$card['rarity']] ?? 'from-gray-500 to-gray-600';
                                ?>
                                <span class="absolute top-1.5 left-1.5 px-1.5 py-0.5 text-[10px] font-bold text-white bg-gradient-to-r <?= $rarityBg ?> rounded shadow"><?= htmlspecialchars($card['rarity']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="p-2.5">
                            <p class="text-xs font-bold text-white truncate"><?= htmlspecialchars($card['card_name']) ?></p>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-[10px] text-dark-400"><?= htmlspecialchars($card['card_set_id']) ?></span>
                                <?php if (!empty($card['market_price'])): ?>
                                    <span class="text-[10px] font-bold text-gold-400">$<?= number_format((float)$card['market_price'], 2) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($result['total'] > $result['per_page']): ?>
            <div class="mt-8 flex justify-center gap-2">
                <?php
                    $totalPages = (int)ceil($result['total'] / $result['per_page']);
                    $current = $result['page'];
                    $qs = $_GET; unset($qs['page']);
                    $base = '/cards?' . http_build_query($qs);
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
    </div>
</div>
