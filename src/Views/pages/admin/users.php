<div class="space-y-6" x-data="adminUsers()">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900">Manage Users</h1>
            <p class="text-sm text-gray-500 mt-1"><?= number_format($total) ?> users total</p>
        </div>
        <a href="/admin" class="px-3 py-2 glass rounded-lg text-sm text-gray-600 hover:text-gray-900 transition flex items-center gap-1.5"><i data-lucide="arrow-left" class="w-4 h-4"></i> Admin</a>
    </div>

    <div class="glass rounded-2xl p-4">
        <form action="/admin/users" method="GET" class="flex gap-2" onsubmit="cleanSubmit(this); return false;">
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search users..."
                    class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:border-gray-400 transition">
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-900 rounded-lg text-sm font-bold transition hover:bg-gray-800" style="color:#fff !important">Search</button>
        </form>
    </div>

    <div class="glass rounded-2xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b" style="border-color:var(--nav-border)">
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase">User</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase hidden sm:table-cell">Email</th>
                    <th class="text-right px-4 py-3 text-xs font-bold text-gray-400 uppercase">Cards</th>
                    <th class="text-right px-4 py-3 text-xs font-bold text-gray-400 uppercase hidden md:table-cell">Value</th>
                    <th class="text-right px-4 py-3 text-xs font-bold text-gray-400 uppercase">Joined</th>
                    <th class="text-right px-4 py-3 text-xs font-bold text-gray-400 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr class="border-b hover:bg-gray-50 transition" style="border-color:var(--nav-border)" id="user-row-<?= $u['id'] ?>">
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full bg-gray-900 flex items-center justify-center text-xs font-bold flex-shrink-0" style="color:#fff !important"><?= strtoupper(substr($u['username'], 0, 1)) ?></div>
                            <div>
                                <a href="/user/<?= htmlspecialchars($u['username']) ?>" class="font-bold text-gray-900 hover:text-gold-400 transition"><?= htmlspecialchars($u['username']) ?></a>
                                <?php if ($u['is_admin']): ?><span class="ml-1 px-1.5 py-0.5 bg-red-100 text-red-600 text-[10px] font-bold rounded">ADMIN</span><?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-gray-500 hidden sm:table-cell"><?= htmlspecialchars($u['email']) ?></td>
                    <td class="px-4 py-3 text-right font-bold text-gray-900"><?= number_format((int)$u['card_count']) ?></td>
                    <td class="px-4 py-3 text-right font-bold text-gray-900 hidden md:table-cell">$<?= number_format((float)$u['collection_value'], 2) ?></td>
                    <td class="px-4 py-3 text-right text-gray-400 text-xs"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <button @click="toggleAdmin(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')" class="p-1.5 rounded text-gray-400 hover:text-amber-500 hover:bg-amber-50 transition" title="Toggle admin">
                                <i data-lucide="shield" class="w-4 h-4"></i>
                            </button>
                            <?php if ($u['id'] !== \App\Core\Auth::id()): ?>
                            <button @click="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')" class="p-1.5 rounded text-gray-400 hover:text-red-500 hover:bg-red-50 transition" title="Delete user">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center gap-2">
        <?php for ($p = max(1, $page-3); $p <= min($totalPages, $page+3); $p++): ?>
        <a href="/admin/users?page=<?= $p ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
           class="px-3 py-2 rounded-lg text-sm font-medium transition <?= $p === $page ? 'bg-gray-900 font-bold' : 'glass text-gray-600 hover:text-gray-900' ?>"
           <?= $p === $page ? 'style="color:#fff !important"' : '' ?>><?= $p ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function adminUsers() {
    return {
        async toggleAdmin(id, username) {
            if (!confirm('Toggle admin for ' + username + '?')) return;
            var res = await apiPost('/admin/users/toggle-admin', { user_id: id });
            if (res.success) { showToast('Admin status toggled'); location.reload(); }
            else showToast(res.message || 'Error', 'error');
        },
        async deleteUser(id, username) {
            if (!confirm('DELETE user ' + username + '? This cannot be undone!')) return;
            if (!confirm('Are you really sure? All their data will be lost.')) return;
            var res = await apiPost('/admin/users/delete', { user_id: id });
            if (res.success) { showToast('User deleted'); document.getElementById('user-row-' + id)?.remove(); }
            else showToast(res.message || 'Error', 'error');
        }
    }
}
</script>
