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
$appUrl = $_ENV['APP_URL'] ?? 'https://myopcards.com';
?>
<div class="space-y-6" x-data="collectionPage()">
    <!-- Header with value + share -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-display font-bold text-white"><?= ($wishlist ?? false) ? 'My Wishlist' : 'My Collection' ?></h1>
            <p class="text-sm text-dark-400 mt-1"><?= number_format($result['total'] ?? 0) ?> unique cards</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="/collection/export" class="flex items-center gap-2 px-4 py-2 glass rounded-lg text-sm text-dark-300 hover:text-white transition">
                <i data-lucide="download" class="w-4 h-4"></i> Export
            </a>
            <button @click="shareCollection()" class="flex items-center gap-2 px-4 py-2 glass rounded-lg text-sm text-gold-400 hover:text-gold-300 hover:border-gold-500/30 transition">
                <i data-lucide="share-2" class="w-4 h-4"></i> Share
            </button>
        </div>
    </div>

    <!-- Share panel (hidden by default) -->
    <div x-show="shareOpen" x-transition x-cloak class="glass rounded-2xl p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-display font-bold text-white flex items-center gap-2">
                <i data-lucide="link" class="w-4 h-4 text-gold-400"></i> Share Link
            </h3>
            <button @click="shareOpen = false" class="text-dark-400 hover:text-white"><i data-lucide="x" class="w-4 h-4"></i></button>
        </div>
        <template x-if="shareUrl">
            <div>
                <div class="flex gap-2">
                    <input type="text" :value="shareUrl" readonly class="flex-1 px-4 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white font-mono select-all" @click="$el.select()">
                    <button @click="copyShare()" class="px-4 py-2.5 bg-gold-500 text-dark-900 rounded-lg text-sm font-bold hover:bg-gold-400 transition flex items-center gap-1.5">
                        <i data-lucide="copy" class="w-4 h-4"></i> <span x-text="copied ? 'Copied' : 'Copy'"></span>
                    </button>
                </div>
                <p class="text-xs text-dark-400 mt-2">Anyone with this link can view your collection and its value. This works even if your profile is private.</p>
                <button @click="revokeShare()" class="text-xs text-red-400 hover:text-red-300 mt-2 flex items-center gap-1">
                    <i data-lucide="trash-2" class="w-3 h-3"></i> Revoke share link
                </button>
            </div>
        </template>
        <template x-if="!shareUrl && !shareLoading">
            <div class="text-center py-4">
                <p class="text-sm text-dark-300 mb-3">Generate a shareable link to your collection</p>
                <button @click="generateShare()" class="px-6 py-2.5 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition">Generate Link</button>
            </div>
        </template>
        <template x-if="shareLoading">
            <div class="text-center py-4 text-dark-400 text-sm">Generating...</div>
        </template>
    </div>

    <!-- Value Summary -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="glass rounded-xl p-4 text-center">
            <p class="text-xs text-dark-400 uppercase tracking-wider font-bold mb-1">Cards</p>
            <p class="text-xl font-display font-bold text-white"><?= number_format($result['total'] ?? 0) ?></p>
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
        <form method="GET" action="/collection" onsubmit="event.preventDefault(); cleanSubmit(this);" class="flex flex-wrap gap-3 items-center">
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
                <a href="/collection" class="text-xs text-dark-400 hover:text-gold-400 transition">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Cards Grid -->
    <?php if (!empty($cards)): ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
        <?php foreach ($cards as $card): ?>
            <?php $cardId = $card['card_id'] ?? $card['id'] ?? 0; ?>
            <div class="group card-hover" x-data="{ qty: <?= (int)($card['quantity'] ?? 1) ?>, confirmDel: false }">
                <div class="glass rounded-xl overflow-hidden relative">
                    <a href="/cards/<?= urlencode($card['card_set_id']) ?>">
                        <div class="relative aspect-[5/7] bg-dark-700">
                            <img src="<?= htmlspecialchars($card['card_image_url'] ?? '') ?>" alt="" class="w-full h-full object-cover" loading="lazy" onerror="this.parentElement.classList.add('skeleton');this.style.display='none'">
                            <span class="absolute top-1.5 right-1.5 px-2 py-0.5 bg-dark-900/80 text-white text-xs font-bold rounded-full" x-text="qty + 'x'"></span>
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
                        <div class="flex gap-1 mt-2">
                            <button @click="if(qty <= 1) { confirmDel = true; } else { qty--; apiPost('/collection/update', { card_id: <?= $cardId ?>, quantity: qty }).then(() => showToast('Updated')); }"
                                class="flex-1 py-1 glass rounded text-xs text-dark-300 hover:text-white transition">
                                <i data-lucide="minus" class="w-3 h-3 mx-auto"></i>
                            </button>
                            <button @click="qty++; apiPost('/collection/add', { card_id: <?= $cardId ?>, quantity: 1 }).then(() => showToast('Added'));"
                                class="flex-1 py-1 bg-gold-500/20 rounded text-xs text-gold-400 hover:bg-gold-500/30 transition">
                                <i data-lucide="plus" class="w-3 h-3 mx-auto"></i>
                            </button>
                        </div>
                    </div>
                    <!-- Confirm delete overlay -->
                    <div x-show="confirmDel" x-transition.opacity x-cloak
                         class="absolute inset-0 bg-dark-900/90 backdrop-blur-sm flex flex-col items-center justify-center p-3 z-10 rounded-xl">
                        <i data-lucide="alert-triangle" class="w-6 h-6 text-red-400 mb-2"></i>
                        <p class="text-xs text-white text-center font-medium leading-snug mb-3">Remove this card from your collection?</p>
                        <div class="flex gap-2 w-full">
                            <button @click="confirmDel = false"
                                class="flex-1 py-1.5 glass rounded-lg text-xs text-dark-300 hover:text-white transition font-medium">Cancel</button>
                            <button @click="qty = 0; confirmDel = false; apiPost('/collection/remove', { card_id: <?= $cardId ?> }).then(() => { showToast('Card removed', 'info'); $el.closest('.card-hover').remove(); });"
                                class="flex-1 py-1.5 bg-red-500/80 hover:bg-red-500 rounded-lg text-xs text-white transition font-medium">Remove</button>
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

    <!-- Public views stats -->
    <div class="glass rounded-xl p-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-4 text-sm text-dark-400">
            <span class="flex items-center gap-1.5"><i data-lucide="eye" class="w-4 h-4"></i> <?= number_format($viewCounts['profile'] ?? 0) ?> profile views</span>
            <span class="flex items-center gap-1.5"><i data-lucide="layout-grid" class="w-4 h-4"></i> <?= number_format($viewCounts['collection'] ?? 0) ?> collection views</span>
        </div>
        <?php if ($user['is_public'] ?? false): ?>
        <a href="/user/<?= htmlspecialchars($user['username']) ?>" class="text-xs text-gold-400 hover:text-gold-300 transition flex items-center gap-1">
            <i data-lucide="external-link" class="w-3 h-3"></i> View my public profile
        </a>
        <?php endif; ?>
    </div>
</div>

<script>window.__PAGE_DATA = { shareUrl: <?= json_encode($shareToken ? $appUrl . '/s/' . $shareToken : '') ?> };</script>
<script src="/assets/js/pages/collection.js"></script>
