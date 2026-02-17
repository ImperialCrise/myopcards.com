<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900">Manage Cards</h1>
            <p class="text-sm text-gray-500 mt-1"><?= number_format($total) ?> cards<?= $filter ? " (filtered: $filter)" : '' ?></p>
        </div>
        <a href="/admin" class="px-3 py-2 glass rounded-lg text-sm text-gray-600 hover:text-gray-900 transition flex items-center gap-1.5"><i data-lucide="arrow-left" class="w-4 h-4"></i> Admin</a>
    </div>

    <div class="glass rounded-2xl p-4">
        <form action="/admin/cards" method="GET" class="flex flex-wrap gap-2" onsubmit="cleanSubmit(this); return false;">
            <div class="relative flex-1 min-w-[200px]">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Card name or ID..."
                    class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:border-gray-400 transition">
            </div>
            <select name="filter" class="px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900">
                <option value="">All Cards</option>
                <option value="no_price" <?= $filter === 'no_price' ? 'selected' : '' ?>>No USD Price</option>
                <option value="no_eur" <?= $filter === 'no_eur' ? 'selected' : '' ?>>No EUR Price</option>
                <option value="parallel" <?= $filter === 'parallel' ? 'selected' : '' ?>>Parallel Only</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-gray-900 rounded-lg text-sm font-bold transition hover:bg-gray-800" style="color:#fff !important">Filter</button>
        </form>
    </div>

    <div class="glass rounded-2xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b" style="border-color:var(--nav-border)">
                    <th class="text-left px-3 py-3 text-xs font-bold text-gray-400 uppercase">ID</th>
                    <th class="text-left px-3 py-3 text-xs font-bold text-gray-400 uppercase">Card</th>
                    <th class="text-left px-3 py-3 text-xs font-bold text-gray-400 uppercase">Rarity</th>
                    <th class="text-right px-3 py-3 text-xs font-bold text-gray-400 uppercase">USD</th>
                    <th class="text-right px-3 py-3 text-xs font-bold text-gray-400 uppercase">EUR EN</th>
                    <th class="text-right px-3 py-3 text-xs font-bold text-gray-400 uppercase">EUR FR</th>
                    <th class="text-right px-3 py-3 text-xs font-bold text-gray-400 uppercase">EUR JP</th>
                    <th class="text-right px-3 py-3 text-xs font-bold text-gray-400 uppercase">Par</th>
                    <th class="text-right px-3 py-3 text-xs font-bold text-gray-400 uppercase">Edit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cards as $c): ?>
                <tr class="border-b hover:bg-gray-50 transition" style="border-color:var(--nav-border)">
                    <td class="px-3 py-2"><a href="/cards/<?= htmlspecialchars($c['card_set_id']) ?>" class="text-xs text-blue-600 hover:underline"><?= htmlspecialchars($c['card_set_id']) ?></a></td>
                    <td class="px-3 py-2 text-gray-900 font-medium max-w-[200px] truncate"><?= htmlspecialchars($c['card_name']) ?></td>
                    <td class="px-3 py-2 text-gray-500"><?= htmlspecialchars($c['rarity']) ?></td>
                    <td class="px-3 py-2 text-right <?= ($c['market_price'] ?? 0) > 0 ? 'text-emerald-600 font-bold' : 'text-gray-300' ?>">
                        <?= ($c['market_price'] ?? 0) > 0 ? '$' . number_format((float)$c['market_price'], 2) : '-' ?>
                    </td>
                    <td class="px-3 py-2 text-right <?= ($c['price_en'] ?? 0) > 0 ? 'text-blue-600 font-bold' : 'text-gray-300' ?>">
                        <?= ($c['price_en'] ?? 0) > 0 ? '€' . number_format((float)$c['price_en'], 2) : '-' ?>
                    </td>
                    <td class="px-3 py-2 text-right <?= ($c['price_fr'] ?? 0) > 0 ? 'text-indigo-600 font-bold' : 'text-gray-300' ?>">
                        <?= ($c['price_fr'] ?? 0) > 0 ? '€' . number_format((float)$c['price_fr'], 2) : '-' ?>
                    </td>
                    <td class="px-3 py-2 text-right <?= ($c['price_jp'] ?? 0) > 0 ? 'text-red-600 font-bold' : 'text-gray-300' ?>">
                        <?= ($c['price_jp'] ?? 0) > 0 ? '€' . number_format((float)$c['price_jp'], 2) : '-' ?>
                    </td>
                    <td class="px-3 py-2 text-right"><?= $c['is_parallel'] ? '<span class="text-amber-500 font-bold text-xs">P</span>' : '' ?></td>
                    <td class="px-3 py-2 text-right">
                        <a href="/admin/card-edit?id=<?= urlencode($c['card_set_id']) ?>" class="p-1.5 rounded text-gray-400 hover:text-blue-500 hover:bg-blue-50 transition inline-block" title="Edit">
                            <i data-lucide="pencil" class="w-4 h-4"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center gap-2">
        <?php for ($p = max(1, $page-3); $p <= min($totalPages, $page+3); $p++): ?>
        <a href="/admin/cards?page=<?= $p ?><?= $search ? '&q=' . urlencode($search) : '' ?><?= $filter ? '&filter=' . urlencode($filter) : '' ?>"
           class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $p === $page ? 'bg-gray-900 font-bold' : 'glass text-gray-600 hover:text-gray-900' ?>"
           <?= $p === $page ? 'style="color:#fff !important"' : '' ?>><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
