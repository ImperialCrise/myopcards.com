<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900">Sync Logs</h1>
            <p class="text-sm text-gray-500 mt-1"><?= count($logs) ?> recent operations</p>
        </div>
        <a href="/admin" class="px-3 py-2 glass rounded-lg text-sm text-gray-600 hover:text-gray-900 transition flex items-center gap-1.5"><i data-lucide="arrow-left" class="w-4 h-4"></i> Admin</a>
    </div>

    <div class="glass rounded-2xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b" style="border-color:var(--nav-border)">
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Type</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase hidden sm:table-cell">Message</th>
                    <th class="text-right px-4 py-3 text-xs font-bold text-gray-400 uppercase hidden md:table-cell">Duration</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase hidden md:table-cell">Triggered By</th>
                    <th class="text-right px-4 py-3 text-xs font-bold text-gray-400 uppercase">Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No sync logs yet. Run a sync from the Prices panel.</td></tr>
                <?php endif; ?>
                <?php foreach ($logs as $l): ?>
                <?php
                    $statusColors = [
                        'running' => 'bg-blue-100 text-blue-700',
                        'success' => 'bg-green-100 text-green-700',
                        'failed'  => 'bg-red-100 text-red-700',
                    ];
                    $sc = $statusColors[$l['status']] ?? 'bg-gray-100 text-gray-700';
                ?>
                <tr class="border-b hover:bg-gray-50 transition" style="border-color:var(--nav-border)">
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs font-bold rounded"><?= htmlspecialchars($l['type']) ?></span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 text-xs font-bold rounded <?= $sc ?>"><?= htmlspecialchars($l['status']) ?></span>
                    </td>
                    <td class="px-4 py-3 text-gray-600 max-w-xs truncate hidden sm:table-cell" title="<?= htmlspecialchars($l['message'] ?? '') ?>">
                        <?= htmlspecialchars($l['message'] ?? '-') ?>
                    </td>
                    <td class="px-4 py-3 text-right text-gray-500 hidden md:table-cell">
                        <?php if ($l['duration_ms'] !== null): ?>
                            <?php
                                $ms = (int)$l['duration_ms'];
                                echo $ms >= 60000 ? round($ms / 60000, 1) . 'min' : ($ms >= 1000 ? round($ms / 1000, 1) . 's' : $ms . 'ms');
                            ?>
                        <?php else: ?>
                            <span class="text-blue-400">running</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-gray-500 hidden md:table-cell"><?= htmlspecialchars($l['triggered_by'] ?? '') ?></td>
                    <td class="px-4 py-3 text-right text-gray-400 text-xs whitespace-nowrap">
                        <?= $l['started_at'] ? date('M j H:i:s', strtotime($l['started_at'])) : '' ?>
                    </td>
                </tr>
                <?php if (!empty($l['details'])): ?>
                <tr class="border-b" style="border-color:var(--nav-border)">
                    <td colspan="6" class="px-4 py-2 bg-gray-50">
                        <pre class="text-[10px] text-gray-500 font-mono overflow-x-auto max-w-full"><?= htmlspecialchars(json_encode(json_decode($l['details'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
