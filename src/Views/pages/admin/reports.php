<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900">User Reports</h1>
            <p class="text-sm text-gray-500 mt-1"><?= $pendingCount ?> pending</p>
        </div>
        <a href="/admin" class="px-3 py-2 glass rounded-lg text-sm text-gray-600 hover:text-gray-900 transition flex items-center gap-1.5"><i data-lucide="arrow-left" class="w-4 h-4"></i> Admin</a>
    </div>

    <div class="flex gap-2">
        <a href="/admin/reports?status=all" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $filter === 'all' ? 'bg-gray-900 text-white' : 'glass text-gray-600 hover:text-gray-900' ?>">All</a>
        <a href="/admin/reports?status=pending" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $filter === 'pending' ? 'bg-red-500 text-white' : 'glass text-gray-600 hover:text-gray-900' ?>">Pending</a>
        <a href="/admin/reports?status=reviewed" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $filter === 'reviewed' ? 'bg-gray-900 text-white' : 'glass text-gray-600 hover:text-gray-900' ?>">Reviewed</a>
        <a href="/admin/reports?status=dismissed" class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $filter === 'dismissed' ? 'bg-gray-900 text-white' : 'glass text-gray-600 hover:text-gray-900' ?>">Dismissed</a>
    </div>

    <div class="glass rounded-2xl overflow-hidden">
        <?php if (empty($reports)): ?>
        <div class="p-12 text-center text-gray-500">
            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-4 opacity-50"></i>
            <p>No reports found</p>
        </div>
        <?php else: ?>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b" style="border-color:var(--nav-border)">
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Reporter</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Reported</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Reason</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Details</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Date</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">Status</th>
                    <th class="text-right px-4 py-3 text-xs font-bold text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $r): ?>
                <tr class="border-b hover:bg-gray-50 transition" style="border-color:var(--nav-border)" id="report-row-<?= $r['id'] ?>">
                    <td class="px-4 py-3">
                        <a href="/user/<?= htmlspecialchars($r['reporter_username']) ?>" class="font-bold text-gray-900 hover:text-gold-400"><?= htmlspecialchars($r['reporter_username']) ?></a>
                    </td>
                    <td class="px-4 py-3">
                        <a href="/user/<?= htmlspecialchars($r['reported_username']) ?>" class="font-bold text-gray-900 hover:text-gold-400"><?= htmlspecialchars($r['reported_username']) ?></a>
                    </td>
                    <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars(str_replace('_', ' ', $r['reason'])) ?></td>
                    <td class="px-4 py-3 text-gray-500 max-w-xs truncate"><?= htmlspecialchars(substr($r['details'] ?? '', 0, 80)) ?><?= strlen($r['details'] ?? '') > 80 ? '...' : '' ?></td>
                    <td class="px-4 py-3 text-gray-400"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                    <td class="px-4 py-3">
                        <span class="px-1.5 py-0.5 rounded text-xs font-bold <?= $r['status'] === 'pending' ? 'bg-amber-100 text-amber-700' : ($r['status'] === 'dismissed' ? 'bg-gray-200 text-gray-600' : 'bg-green-100 text-green-700') ?>"><?= htmlspecialchars($r['status']) ?></span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <?php if ($r['status'] === 'pending'): ?>
                        <div class="flex items-center justify-end gap-1">
                            <button onclick="reviewReport(<?= $r['id'] ?>, 'dismiss')" class="px-2 py-1 bg-gray-200 text-gray-700 rounded text-xs font-bold hover:bg-gray-300">Dismiss</button>
                            <?php if ((int)$r['reported_id'] !== \App\Core\Auth::id()): ?>
                            <button onclick="reviewReport(<?= $r['id'] ?>, 'delete_user')" class="px-2 py-1 bg-red-100 text-red-600 rounded text-xs font-bold hover:bg-red-200">Delete User</button>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());

function reviewReport(reportId, action) {
    if (action === 'delete_user' && !confirm('Permanently delete this user? This cannot be undone.')) return;
    var fd = new FormData();
    fd.append('report_id', reportId);
    fd.append('action', action);
    var token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) fd.append('csrf_token', token);
    fetch('/admin/reports/review', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('report-row-' + reportId)?.remove();
            }
        });
}
</script>
