<?php
$isLoggedIn = \App\Core\Auth::check();
$cards = $result['cards'] ?? [];
?>
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-display font-bold text-white"><?= ($wishlist ?? false) ? 'My Wishlist' : 'My Collection' ?></h1>
            <p class="text-sm text-dark-400 mt-1"><?= number_format($result['total'] ?? count($cards)) ?> unique cards</p>
        </div>
        <div class="flex gap-2">
            <a href="/collection/export" class="flex items-center gap-2 px-4 py-2 glass rounded-lg text-sm text-dark-300 hover:text-white transition">
                <i data-lucide="download" class="w-4 h-4"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="glass rounded-xl p-4">
        <form method="GET" action="/collection" class="flex flex-wrap gap-3 items-center">
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-dark-400"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($filters['q'] ?? '') ?>" placeholder="Search your collection..."
                    class="w-full pl-9 pr-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition">
            </div>
            <select name="set_id" class="px-3 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                <option value="">All Sets</option>
                <?php foreach ($sets as $s): ?>
                    <option value="<?= htmlspecialchars($s) ?>" <?= ($filters['set_id'] ?? '') === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="px-4 py-2 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition">Filter</button>
        </form>
    </div>

    <!-- Cards Grid -->
    <?php if (!empty($cards)): ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
        <?php foreach ($cards as $card): ?>
            <?php $cardId = $card['card_id'] ?? $card['id'] ?? 0; ?>
            <div class="group card-hover" x-data="{ qty: <?= (int)($card['quantity'] ?? 1) ?> }">
                <div class="glass rounded-xl overflow-hidden">
                    <a href="/cards/<?= urlencode($card['card_set_id']) ?>">
                        <div class="relative aspect-[5/7] bg-dark-700">
                            <img src="<?= htmlspecialchars($card['card_image_url'] ?? '') ?>" alt="" class="w-full h-full object-cover" loading="lazy" onerror="this.parentElement.classList.add('skeleton');this.style.display='none'">
                            <span class="absolute top-1.5 right-1.5 px-2 py-0.5 bg-dark-900/80 text-white text-xs font-bold rounded-full" x-text="qty + 'x'"></span>
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
                        <div class="flex gap-1 mt-2">
                            <button @click="if(qty > 0) { qty--; apiPost(qty === 0 ? '/collection/remove' : '/collection/update', { card_id: <?= $cardId ?>, quantity: qty }).then(() => showToast('Updated')); }"
                                class="flex-1 py-1 glass rounded text-xs text-dark-300 hover:text-white transition">
                                <i data-lucide="minus" class="w-3 h-3 mx-auto"></i>
                            </button>
                            <button @click="qty++; apiPost('/collection/add', { card_id: <?= $cardId ?>, quantity: 1 }).then(() => showToast('Added'));"
                                class="flex-1 py-1 bg-gold-500/20 rounded text-xs text-gold-400 hover:bg-gold-500/30 transition">
                                <i data-lucide="plus" class="w-3 h-3 mx-auto"></i>
                            </button>
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
                $base = '/collection?' . http_build_query($qs);
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
            <h3 class="text-lg font-display font-bold text-dark-300">No cards in your collection</h3>
            <p class="text-sm text-dark-400 mt-2">Browse the <a href="/cards" class="text-gold-400 hover:text-gold-300">card database</a> to start adding cards.</p>
        </div>
    <?php endif; ?>
</div>
