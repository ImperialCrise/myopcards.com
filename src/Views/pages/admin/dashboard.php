<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900">Admin Dashboard</h1>
            <p class="text-sm text-gray-500 mt-1">Site overview and management</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="/admin/users" class="px-3 py-2 glass rounded-lg text-sm text-gray-600 hover:text-gray-900 transition flex items-center gap-1.5"><i data-lucide="users" class="w-4 h-4"></i> Users</a>
            <a href="/admin/cards" class="px-3 py-2 glass rounded-lg text-sm text-gray-600 hover:text-gray-900 transition flex items-center gap-1.5"><i data-lucide="layers" class="w-4 h-4"></i> Cards</a>
            <a href="/admin/prices" class="px-3 py-2 glass rounded-lg text-sm text-gray-600 hover:text-gray-900 transition flex items-center gap-1.5"><i data-lucide="banknote" class="w-4 h-4"></i> Prices</a>
            <a href="/admin/logs" class="px-3 py-2 glass rounded-lg text-sm text-gray-600 hover:text-gray-900 transition flex items-center gap-1.5"><i data-lucide="file-text" class="w-4 h-4"></i> Logs</a>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <?php
        $tiles = [
            ['Users', $stats['total_users'], 'users', 'blue'],
            ['Cards', $stats['total_cards'], 'layers', 'green'],
            ['Sets', $stats['total_sets'], 'package', 'purple'],
            ['Cards Owned', $stats['total_cards_owned'], 'folder-open', 'amber'],
            ['USD Prices', $stats['cards_with_price'], 'dollar-sign', 'emerald'],
            ['EUR Prices', $stats['cards_with_eur'], 'euro', 'blue'],
            ['Price Records', $stats['price_history_count'], 'bar-chart-3', 'indigo'],
            ['New Today', $stats['new_users_today'], 'user-plus', 'rose'],
        ];
        foreach ($tiles as [$label, $value, $icon, $color]): ?>
        <div class="glass rounded-xl p-4">
            <div class="flex items-center gap-2 mb-2">
                <i data-lucide="<?= $icon ?>" class="w-4 h-4 text-<?= $color ?>-500"></i>
                <span class="text-xs font-bold text-gray-400 uppercase tracking-wider"><?= $label ?></span>
            </div>
            <p class="text-2xl font-display font-bold text-gray-900"><?= number_format($value) ?></p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="clock" class="w-5 h-5 text-gray-400"></i> Sync Status
            </h2>
            <div class="space-y-3 text-sm">
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-600">Last Card Sync</span>
                    <span class="font-bold text-gray-900"><?= $stats['last_sync'] ? date('M j, H:i', strtotime($stats['last_sync'])) : 'Never' ?></span>
                </div>
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-600">Last Price Update</span>
                    <span class="font-bold text-gray-900"><?= $stats['last_price_update'] ? date('M j, H:i', strtotime($stats['last_price_update'])) : 'Never' ?></span>
                </div>
                <?php foreach ($priceSources as $ps): ?>
                <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                    <span class="text-gray-600"><?= htmlspecialchars($ps['source']) ?>/<?= htmlspecialchars($ps['edition']) ?></span>
                    <span class="font-bold text-gray-900"><?= number_format((int)$ps['cnt']) ?> records (latest: <?= $ps['latest'] ?>)</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i data-lucide="user-plus" class="w-5 h-5 text-gray-400"></i> Recent Users
            </h2>
            <div class="space-y-2">
                <?php foreach ($recentUsers as $u): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-gray-900 flex items-center justify-center text-xs font-bold" style="color:#fff !important"><?= strtoupper(substr($u['username'], 0, 1)) ?></div>
                        <div>
                            <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($u['username']) ?></p>
                            <p class="text-xs text-gray-400"><?= htmlspecialchars($u['email']) ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-400"><?= date('M j', strtotime($u['created_at'])) ?></p>
                        <?php if ($u['is_admin']): ?><span class="text-xs font-bold text-red-500">Admin</span><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
