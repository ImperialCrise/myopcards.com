<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-display font-bold text-white">Friends</h1>
            <p class="text-sm text-dark-400 mt-1"><?= count($friends) ?> friend<?= count($friends) !== 1 ? 's' : '' ?></p>
        </div>
    </div>

    <!-- Search Users -->
    <div class="glass rounded-2xl p-6" x-data="friendSearch()">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="user-search" class="w-5 h-5 text-blue-400"></i> Find Users
        </h2>
        <div class="relative">
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-dark-400"></i>
            <input type="text" x-model="query" @input.debounce.300ms="search()" placeholder="Search by username..."
                class="w-full pl-9 pr-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition">
        </div>
        <div x-show="results.length > 0" class="mt-3 space-y-2">
            <template x-for="u in results" :key="u.id">
                <div class="flex items-center justify-between p-3 bg-dark-800/50 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-gold-500 to-gold-300 flex items-center justify-center text-dark-900 font-bold text-sm" x-text="u.username.charAt(0).toUpperCase()"></div>
                        <a :href="'/user/' + u.username" class="text-sm text-white hover:text-gold-400 transition" x-text="u.username"></a>
                    </div>
                    <button @click="sendRequest(u.id)" class="px-3 py-1.5 bg-gold-500/20 text-gold-400 rounded-lg text-xs font-bold hover:bg-gold-500/30 transition">
                        <i data-lucide="user-plus" class="w-3 h-3 inline mr-1"></i> Add
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Pending Requests -->
    <?php if (!empty($pendingRequests)): ?>
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="inbox" class="w-5 h-5 text-amber-400"></i> Pending Requests
            <span class="ml-1 px-2 py-0.5 bg-amber-500/20 text-amber-400 text-xs font-bold rounded-full"><?= count($pendingRequests) ?></span>
        </h2>
        <div class="space-y-2">
            <?php foreach ($pendingRequests as $req): ?>
                <div class="flex items-center justify-between p-3 bg-dark-800/50 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center text-white font-bold text-sm">
                            <?= strtoupper(substr($req['username'], 0, 1)) ?>
                        </div>
                        <a href="/user/<?= htmlspecialchars($req['username']) ?>" class="text-sm text-white hover:text-gold-400 transition"><?= htmlspecialchars($req['username']) ?></a>
                    </div>
                    <div class="flex gap-2">
                        <form method="POST" action="/friends/accept" class="inline">
                            <input type="hidden" name="friendship_id" value="<?= $req['id'] ?>">
                            <button class="px-3 py-1.5 bg-green-500/20 text-green-400 rounded-lg text-xs font-bold hover:bg-green-500/30 transition">Accept</button>
                        </form>
                        <form method="POST" action="/friends/decline" class="inline">
                            <input type="hidden" name="friendship_id" value="<?= $req['id'] ?>">
                            <button class="px-3 py-1.5 bg-red-500/20 text-red-400 rounded-lg text-xs font-bold hover:bg-red-500/30 transition">Decline</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Friends List -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="users" class="w-5 h-5 text-green-400"></i> Your Friends
        </h2>
        <?php if (empty($friends)): ?>
            <p class="text-sm text-dark-400 text-center py-8">No friends yet. Search for users above to connect!</p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <?php foreach ($friends as $f): ?>
                    <div class="flex items-center justify-between p-3 bg-dark-800/50 rounded-xl">
                        <a href="/user/<?= htmlspecialchars($f['username']) ?>" class="flex items-center gap-3 flex-1 min-w-0">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gold-500 to-gold-300 flex items-center justify-center text-dark-900 font-bold">
                                <?= strtoupper(substr($f['username'], 0, 1)) ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-white truncate"><?= htmlspecialchars($f['username']) ?></p>
                                <?php if ($f['card_count'] ?? 0): ?>
                                    <p class="text-xs text-dark-400"><?= number_format($f['card_count']) ?> cards</p>
                                <?php endif; ?>
                            </div>
                        </a>
                        <form method="POST" action="/friends/remove" class="ml-2">
                            <input type="hidden" name="friend_id" value="<?= $f['id'] ?>">
                            <button class="p-2 text-dark-400 hover:text-red-400 transition" title="Remove friend">
                                <i data-lucide="user-minus" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function friendSearch() {
    return {
        query: '', results: [],
        async search() {
            if (this.query.length < 2) { this.results = []; return; }
            const res = await fetch('/api/users/search?q=' + encodeURIComponent(this.query));
            this.results = await res.json();
        },
        async sendRequest(userId) {
            await apiPost('/friends/request', { user_id: userId });
            showToast('Friend request sent');
            this.results = this.results.filter(u => u.id !== userId);
        }
    }
}
</script>
