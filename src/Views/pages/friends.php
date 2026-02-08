<div class="space-y-6" x-data="friendsPage()">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900">Friends</h1>
            <p class="text-sm text-gray-500 mt-1"><span x-text="friendCount"><?= count($friends) ?></span> friend<span x-show="friendCount !== 1">s</span></p>
        </div>
    </div>

    <!-- Search Users -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-gray-900 flex items-center gap-2 mb-4">
            <i data-lucide="user-search" class="w-5 h-5 text-blue-400"></i> Find Users
        </h2>
        <div class="relative">
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" x-model="searchQuery" @input.debounce.300ms="searchUsers()" placeholder="Search by username..."
                class="w-full pl-9 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:border-gray-400 transition">
        </div>
        <div x-show="searchResults.length > 0" class="mt-3 space-y-2">
            <template x-for="u in searchResults" :key="u.id">
                <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-100 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-gold-500 to-gold-300 flex items-center justify-center font-bold text-sm" style="color:#fff" x-text="u.username.charAt(0).toUpperCase()"></div>
                        <a :href="'/user/' + u.username" class="text-sm text-gray-900 hover:text-gold-400 transition font-medium" x-text="u.username"></a>
                    </div>
                    <button @click="sendRequest(u.id)" class="px-3 py-1.5 bg-gold-500/20 text-gold-400 rounded-lg text-xs font-bold hover:bg-gold-500/30 transition flex items-center gap-1">
                        <i data-lucide="user-plus" class="w-3 h-3"></i> Add
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Pending Requests -->
    <div x-show="pendingRequests.length > 0" x-transition class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-gray-900 flex items-center gap-2 mb-4">
            <i data-lucide="inbox" class="w-5 h-5 text-amber-500"></i> Pending Requests
            <span class="ml-1 px-2 py-0.5 bg-red-500 text-xs font-bold rounded-full" style="color:#fff" x-text="pendingRequests.length"></span>
        </h2>
        <div class="space-y-2">
            <template x-for="req in pendingRequests" :key="req.id">
                <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-100 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center text-white font-bold text-sm" x-text="req.username.charAt(0).toUpperCase()"></div>
                        <a :href="'/user/' + req.username" class="text-sm text-gray-900 hover:text-gold-400 transition font-medium" x-text="req.username"></a>
                    </div>
                    <div class="flex gap-2">
                        <button @click="acceptRequest(req.user_id, req.id)" class="px-3 py-1.5 bg-green-500/20 text-green-600 rounded-lg text-xs font-bold hover:bg-green-500/30 transition">Accept</button>
                        <button @click="declineRequest(req.user_id, req.id)" class="px-3 py-1.5 bg-red-500/20 text-red-600 rounded-lg text-xs font-bold hover:bg-red-500/30 transition">Decline</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Sent Requests -->
    <div x-show="sentRequests.length > 0" x-transition class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-gray-900 flex items-center gap-2 mb-4">
            <i data-lucide="send" class="w-5 h-5 text-gray-400"></i> Sent Requests
            <span class="ml-1 px-2 py-0.5 bg-gray-200 text-gray-600 text-xs font-bold rounded-full" x-text="sentRequests.length"></span>
        </h2>
        <div class="space-y-2">
            <template x-for="req in sentRequests" :key="req.id">
                <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-100 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full bg-gradient-to-br from-gold-500 to-gold-300 flex items-center justify-center font-bold text-sm" style="color:#fff" x-text="req.username.charAt(0).toUpperCase()"></div>
                        <div>
                            <a :href="'/user/' + req.username" class="text-sm text-gray-900 hover:text-gold-400 transition font-medium" x-text="req.username"></a>
                            <p class="text-xs text-gray-400">Pending</p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Friends List -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-gray-900 flex items-center gap-2 mb-4">
            <i data-lucide="users" class="w-5 h-5 text-green-400"></i> Your Friends
        </h2>
        <template x-if="friendsList.length === 0">
            <p class="text-sm text-gray-400 text-center py-8">No friends yet. Search for users above to connect!</p>
        </template>
        <div x-show="friendsList.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <template x-for="f in friendsList" :key="f.id">
                <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-100 rounded-xl">
                    <a :href="'/user/' + f.username" class="flex items-center gap-3 flex-1 min-w-0">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-gold-500 to-gold-300 flex items-center justify-center font-bold" style="color:#fff" x-text="f.username.charAt(0).toUpperCase()"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-900 truncate" x-text="f.username"></p>
                            <p x-show="f.card_count > 0" class="text-xs text-gray-400" x-text="Number(f.card_count).toLocaleString() + ' cards'"></p>
                        </div>
                    </a>
                    <button @click="removeFriend(f.id, f.username)" class="ml-2 p-2 text-gray-400 hover:text-red-500 transition" title="Remove friend">
                        <i data-lucide="user-minus" class="w-4 h-4"></i>
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function friendsPage() {
    return {
        searchQuery: '',
        searchResults: [],
        pendingRequests: <?= json_encode(array_values($pendingRequests ?? [])) ?>,
        sentRequests: <?= json_encode(array_values($sentRequests ?? [])) ?>,
        friendsList: <?= json_encode(array_values($friends ?? [])) ?>,
        get friendCount() { return this.friendsList.length; },

        async searchUsers() {
            if (this.searchQuery.length < 2) { this.searchResults = []; return; }
            const res = await fetch('/api/users/search?q=' + encodeURIComponent(this.searchQuery));
            this.searchResults = await res.json();
        },

        async sendRequest(userId) {
            const res = await apiPost('/friends/request', { user_id: userId });
            if (res.success) {
                showToast('Friend request sent');
                this.searchResults = this.searchResults.filter(u => u.id !== userId);
            } else {
                showToast(res.message || 'Could not send request', 'error');
            }
        },

        async acceptRequest(userId, reqId) {
            const res = await apiPost('/friends/accept', { user_id: userId });
            if (res.success) {
                showToast('Friend request accepted');
                const req = this.pendingRequests.find(r => r.id === reqId);
                this.pendingRequests = this.pendingRequests.filter(r => r.id !== reqId);
                if (req) this.friendsList.push({ id: req.user_id, username: req.username, avatar: req.avatar, card_count: 0 });
                updateNavBadge(this.pendingRequests.length);
            }
        },

        async declineRequest(userId, reqId) {
            const res = await apiPost('/friends/decline', { user_id: userId });
            if (res.success) {
                showToast('Request declined');
                this.pendingRequests = this.pendingRequests.filter(r => r.id !== reqId);
                updateNavBadge(this.pendingRequests.length);
            }
        },

        async removeFriend(friendId, username) {
            if (!confirm('Remove ' + username + ' from your friends?')) return;
            const res = await apiPost('/friends/remove', { user_id: friendId });
            if (res.success) {
                showToast('Friend removed');
                this.friendsList = this.friendsList.filter(f => f.id !== friendId);
            }
        }
    }
}

function updateNavBadge(count) {
    const badge = document.getElementById('nav-notif-count');
    const dot = document.getElementById('nav-notif-dot');
    if (badge) { badge.textContent = count; badge.style.display = count > 0 ? '' : 'none'; }
    if (dot) { dot.style.display = count > 0 ? '' : 'none'; }
}
</script>
