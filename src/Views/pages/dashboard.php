<?php
$user = \App\Core\Auth::user();
?>
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-display font-bold text-white">Dashboard</h1>
        <p class="text-sm text-dark-400 mt-1">Welcome back, <?= htmlspecialchars($user['username']) ?></p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="glass rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center">
                    <i data-lucide="layers" class="w-5 h-5 text-blue-400"></i>
                </div>
            </div>
            <p class="text-2xl font-display font-bold text-white"><?= number_format($stats['unique_cards'] ?? 0) ?></p>
            <p class="text-xs text-dark-400 mt-1">Unique Cards</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-green-500/10 flex items-center justify-center">
                    <i data-lucide="copy" class="w-5 h-5 text-green-400"></i>
                </div>
            </div>
            <p class="text-2xl font-display font-bold text-white"><?= number_format($stats['total_cards'] ?? 0) ?></p>
            <p class="text-xs text-dark-400 mt-1">Total Cards</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-gold-500/10 flex items-center justify-center">
                    <i data-lucide="dollar-sign" class="w-5 h-5 text-gold-400"></i>
                </div>
            </div>
            <p class="text-2xl font-display font-bold text-white">$<?= number_format($stats['total_value'] ?? 0, 2) ?></p>
            <p class="text-xs text-dark-400 mt-1">Collection Value (USD)</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center">
                    <i data-lucide="percent" class="w-5 h-5 text-purple-400"></i>
                </div>
            </div>
            <?php
                $totalInDb = \App\Models\Card::getTotalCount();
                $pct = $totalInDb > 0 ? round(($stats['unique_cards'] ?? 0) / $totalInDb * 100, 1) : 0;
            ?>
            <p class="text-2xl font-display font-bold text-white"><?= $pct ?>%</p>
            <p class="text-xs text-dark-400 mt-1">Completion</p>
        </div>
        <div class="glass rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-500/10 flex items-center justify-center">
                    <i data-lucide="eye" class="w-5 h-5 text-cyan-400"></i>
                </div>
            </div>
            <p class="text-2xl font-display font-bold text-white"><?= number_format($viewCounts['total'] ?? 0) ?></p>
            <p class="text-xs text-dark-400 mt-1">Profile Views</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Value Timeline -->
        <div class="glass rounded-2xl p-6" x-data="dashValueChart()" x-init="load()">
            <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
                <i data-lucide="trending-up" class="w-5 h-5 text-gold-400"></i> Value Over Time
            </h2>
            <div class="h-48"><canvas id="dashValueChart"></canvas></div>
        </div>

        <!-- Color Distribution -->
        <div class="glass rounded-2xl p-6" x-data="dashColorChart()" x-init="load()">
            <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
                <i data-lucide="pie-chart" class="w-5 h-5 text-purple-400"></i> Color Distribution
            </h2>
            <div class="h-48 flex items-center justify-center"><canvas id="dashColorChart"></canvas></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Set Completion -->
        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
                <i data-lucide="grid-3x3" class="w-5 h-5 text-blue-400"></i> Set Completion
            </h2>
            <div class="space-y-3 max-h-80 overflow-y-auto pr-2">
                <?php foreach ($setCompletion as $sc): ?>
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1">
                            <span class="text-dark-300 truncate"><?= htmlspecialchars($sc['set_name'] ?? $sc['set_id']) ?></span>
                            <span class="text-dark-400 flex-shrink-0 ml-2">
                                <?= $sc['owned'] ?>/<?= $sc['card_count'] ?>
                                <span class="text-xs">(<?= $sc['card_count'] > 0 ? round($sc['owned'] / $sc['card_count'] * 100) : 0 ?>%)</span>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5">
                            <div class="bg-gradient-to-r from-gold-500 to-amber-500 h-1.5 rounded-full transition-all" style="width: <?= $sc['card_count'] > 0 ? round($sc['owned'] / $sc['card_count'] * 100, 1) : 0 ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Additions -->
        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
                <i data-lucide="clock" class="w-5 h-5 text-green-400"></i> Recent Additions
            </h2>
            <div class="space-y-2 max-h-80 overflow-y-auto pr-2">
                <?php foreach ($recentCards as $rc): ?>
                    <a href="/cards/<?= urlencode($rc['card_set_id']) ?>" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition">
                        <img src="<?= htmlspecialchars($rc['card_image_url'] ?? '') ?>" class="w-8 h-11 rounded object-cover bg-dark-700" onerror="this.style.display='none'" loading="lazy">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-white truncate"><?= htmlspecialchars($rc['card_name']) ?></p>
                            <p class="text-xs text-dark-400"><?= htmlspecialchars($rc['card_set_id']) ?></p>
                        </div>
                        <span class="text-xs text-dark-400"><?= $rc['quantity'] ?>x</span>
                    </a>
                <?php endforeach; ?>
                <?php if (empty($recentCards)): ?>
                    <p class="text-sm text-dark-400 text-center py-4">No cards added yet. Start building your collection!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function dashValueChart() {
    return {
        async load() {
            const res = await fetch('/api/analytics/value-history?days=30');
            const data = await res.json();
            const ctx = document.getElementById('dashValueChart').getContext('2d');
            if (data.length === 0) { ctx.font = '13px Inter'; ctx.fillStyle = '#9ca3af'; ctx.textAlign = 'center'; ctx.fillText('No data yet', ctx.canvas.width/2, ctx.canvas.height/2); return; }
            new Chart(ctx, {
                type: 'line',
                data: { labels: data.map(d => d.snapshot_date), datasets: [{ label: 'Value (USD)', data: data.map(d => parseFloat(d.total_value_usd)), borderColor: '#374151', backgroundColor: 'rgba(55,65,81,0.06)', borderWidth: 2, tension: 0.3, pointRadius: 0, fill: true }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: '#9ca3af', maxTicksLimit: 6 }, grid: { display: false } }, y: { ticks: { color: '#9ca3af' }, grid: { color: 'rgba(0,0,0,0.06)' } } } }
            });
        }
    }
}
function dashColorChart() {
    return {
        async load() {
            const res = await fetch('/api/analytics/distribution');
            const data = await res.json();
            const ctx = document.getElementById('dashColorChart').getContext('2d');
            const colors = data.colors || [];
            if (colors.length === 0) { ctx.font = '13px Inter'; ctx.fillStyle = '#9ca3af'; ctx.textAlign = 'center'; ctx.fillText('No data yet', ctx.canvas.width/2, ctx.canvas.height/2); return; }
            const palette = ['#ef4444','#3b82f6','#22c55e','#a855f7','#eab308','#06b6d4','#f97316','#ec4899','#6366f1','#14b8a6'];
            new Chart(ctx, {
                type: 'doughnut',
                data: { labels: colors.map(c => c.label), datasets: [{ data: colors.map(c => parseInt(c.value)), backgroundColor: palette.slice(0, colors.length), borderWidth: 0 }] },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: '#6b7280', boxWidth: 12, padding: 8, font: { size: 11 } } } } }
            });
        }
    }
}
</script>
