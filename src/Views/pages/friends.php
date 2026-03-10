<div class="space-y-6" x-data="friendsPage()" x-init="initReportModal()">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900"><?= t('friends.title') ?></h1>
            <p class="text-sm text-gray-500 mt-1"><span x-text="friendCount"><?= count($friends) ?></span> friend<span x-show="friendCount !== 1">s</span></p>
        </div>
    </div>

    <!-- Search Users -->
    <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-gray-900 flex items-center gap-2 mb-4">
                <i data-lucide="user-search" class="w-5 h-5 text-blue-400"></i> <?= t('friends.find') ?>
            </h2>
        <div class="relative">
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
            <input type="text" x-model="searchQuery" @input.debounce.300ms="searchUsers()" placeholder="<?= htmlspecialchars(t('friends.search_placeholder')) ?>"
                class="w-full pl-9 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:border-gray-400 transition">
        </div>
        <div x-show="searchResults.length > 0" class="mt-3 space-y-2">
            <template x-for="u in searchResults" :key="u.id">
                <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-100 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full overflow-hidden flex-shrink-0 flex items-center justify-center bg-gray-200">
                            <img x-show="u.avatar_url" :src="u.avatar_url" class="w-full h-full object-cover" alt="">
                            <span x-show="!u.avatar_url" class="font-bold text-sm text-gray-600" x-text="(u.username || '').charAt(0).toUpperCase()"></span>
                        </div>
                        <a :href="'/user/' + u.username" class="text-sm text-gray-900 hover:text-gold-400 transition font-medium" x-text="u.username"></a>
                    </div>
                    <button @click="sendRequest(u.id)" class="px-3 py-1.5 bg-gold-500/20 text-gold-400 rounded-lg text-xs font-bold hover:bg-gold-500/30 transition flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                        <?= t('friends.add') ?>
                    </button>
                </div>
            </template>
        </div>
    </div>

    <!-- Pending Requests -->
    <div x-show="pendingRequests.length > 0" x-transition class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-gray-900 flex items-center gap-2 mb-4">
            <i data-lucide="inbox" class="w-5 h-5 text-amber-500"></i> <?= t('friends.pending_requests') ?>
            <span class="ml-1 px-2 py-0.5 bg-red-500 text-xs font-bold rounded-full" style="color:#fff" x-text="pendingRequests.length"></span>
        </h2>
        <div class="space-y-2">
            <template x-for="req in pendingRequests" :key="req.id">
                <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-100 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full overflow-hidden flex-shrink-0 flex items-center justify-center bg-blue-500/20">
                            <img x-show="req.avatar_url" :src="req.avatar_url" class="w-full h-full object-cover" alt="">
                            <span x-show="!req.avatar_url" class="font-bold text-sm text-blue-600" x-text="(req.username || '').charAt(0).toUpperCase()"></span>
                        </div>
                        <a :href="'/user/' + req.username" class="text-sm text-gray-900 hover:text-gold-400 transition font-medium" x-text="req.username"></a>
                    </div>
                    <div class="flex gap-2">
                        <button @click="acceptRequest(req.user_id, req.id)" class="px-3 py-1.5 bg-green-500/20 text-green-600 rounded-lg text-xs font-bold hover:bg-green-500/30 transition"><?= t('friends.accept') ?></button>
                        <button @click="declineRequest(req.user_id, req.id)" class="px-3 py-1.5 bg-red-500/20 text-red-600 rounded-lg text-xs font-bold hover:bg-red-500/30 transition"><?= t('friends.decline') ?></button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Sent Requests -->
    <div x-show="sentRequests.length > 0" x-transition class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-gray-900 flex items-center gap-2 mb-4">
            <i data-lucide="send" class="w-5 h-5 text-gray-400"></i> <?= t('friends.sent_requests') ?>
            <span class="ml-1 px-2 py-0.5 bg-gray-200 text-gray-600 text-xs font-bold rounded-full" x-text="sentRequests.length"></span>
        </h2>
        <div class="space-y-2">
            <template x-for="req in sentRequests" :key="req.id">
                <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-100 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full overflow-hidden flex-shrink-0 flex items-center justify-center bg-gray-200">
                            <img x-show="req.avatar_url" :src="req.avatar_url" class="w-full h-full object-cover" alt="">
                            <span x-show="!req.avatar_url" class="font-bold text-sm text-gray-600" x-text="(req.username || '').charAt(0).toUpperCase()"></span>
                        </div>
                        <div>
                            <a :href="'/user/' + req.username" class="text-sm text-gray-900 hover:text-gold-400 transition font-medium" x-text="req.username"></a>
                            <p class="text-xs text-gray-400"><?= t('friends.pending') ?></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Friends List -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-gray-900 flex items-center gap-2 mb-4">
            <i data-lucide="users" class="w-5 h-5 text-green-400"></i> <?= t('friends.your_friends') ?>
        </h2>
        <template x-if="friendsList.length === 0">
            <p class="text-sm text-gray-400 text-center py-8"><?= t('friends.no_friends') ?></p>
        </template>
        <div x-show="friendsList.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <template x-for="f in friendsList" :key="f.id">
                <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-100 rounded-xl">
                    <a :href="'/user/' + f.username" class="flex items-center gap-3 flex-1 min-w-0">
                        <div class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0 flex items-center justify-center bg-gray-200">
                            <img x-show="f.avatar_url" :src="f.avatar_url" class="w-full h-full object-cover" alt="">
                            <span x-show="!f.avatar_url" class="font-bold text-gray-600" x-text="(f.username || '').charAt(0).toUpperCase()"></span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-900 truncate" x-text="f.username"></p>
                            <p x-show="f.card_count > 0" class="text-xs text-gray-400" x-text="Number(f.card_count).toLocaleString() + ' cards'"></p>
                        </div>
                    </a>
                    <div class="flex items-center gap-1 ml-2">
                        <a :href="'/messages/new/' + f.username" class="p-2 text-gray-400 hover:text-blue-500 transition" title="<?= htmlspecialchars(t('friends.message', 'Message')) ?>">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                        </a>
                        <button @click="blockUser(f.id, f.username)" class="p-2 text-gray-400 hover:text-amber-500 transition" title="<?= htmlspecialchars(t('friends.block', 'Block')) ?>">
                            <i data-lucide="ban" class="w-4 h-4"></i>
                        </button>
                        <button @click="openReportModal(f.id, f.username)" class="p-2 text-gray-400 hover:text-red-500 transition" title="<?= htmlspecialchars(t('friends.report', 'Report')) ?>">
                            <i data-lucide="flag" class="w-4 h-4"></i>
                        </button>
                        <button @click="removeFriend(f.id, f.username)" class="p-2 text-gray-400 hover:text-red-500 transition" title="<?= htmlspecialchars(t('friends.remove')) ?>">
                            <i data-lucide="user-minus" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Blocked Users -->
    <div x-show="blockedList.length > 0" x-transition class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-gray-900 flex items-center gap-2 mb-4">
            <i data-lucide="ban" class="w-5 h-5 text-amber-500"></i> <?= t('friends.blocked', 'Blocked users') ?>
        </h2>
        <div class="space-y-2">
            <template x-for="b in blockedList" :key="b.id">
                <div class="flex items-center justify-between p-3 bg-gray-50 border border-gray-100 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full overflow-hidden flex-shrink-0 flex items-center justify-center bg-gray-200">
                            <img x-show="b.avatar_url" :src="b.avatar_url" class="w-full h-full object-cover" alt="">
                            <span x-show="!b.avatar_url" class="font-bold text-sm text-gray-600" x-text="(b.username || '').charAt(0).toUpperCase()"></span>
                        </div>
                        <a :href="'/user/' + b.username" class="text-sm text-gray-900 hover:text-gold-400 transition font-medium" x-text="b.username"></a>
                    </div>
                    <button @click="unblockUser(b.id)" class="px-3 py-1.5 bg-gray-200 text-gray-600 rounded-lg text-xs font-bold hover:bg-gray-300 transition"><?= t('friends.unblock', 'Unblock') ?></button>
                </div>
            </template>
        </div>
    </div>

    <!-- Report Modal -->
    <div x-show="reportModalOpen" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @keydown.escape.window="reportModalOpen = false" @click.self="reportModalOpen = false">
    <div class="bg-white dark:bg-dark-800 rounded-2xl shadow-xl max-w-md w-full p-6" @click.stop>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4"><?= t('friends.report_user', 'Report user') ?></h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4" x-show="reportTargetUsername">Reporting <span class="font-medium text-gray-900 dark:text-white" x-text="reportTargetUsername"></span></p>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('friends.report_reason', 'Reason') ?></label>
                <select x-model="reportReason" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-dark-700 border border-gray-200 dark:border-dark-600 rounded-lg text-gray-900 dark:text-white">
                    <option value="spam"><?= t('friends.reason_spam', 'Spam') ?></option>
                    <option value="harassment"><?= t('friends.reason_harassment', 'Harassment') ?></option>
                    <option value="inappropriate_content"><?= t('friends.reason_inappropriate', 'Inappropriate content') ?></option>
                    <option value="cheating"><?= t('friends.reason_cheating', 'Cheating') ?></option>
                    <option value="other"><?= t('friends.reason_other', 'Other') ?></option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('friends.report_details', 'Details (optional)') ?></label>
                <textarea x-model="reportDetails" rows="3" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-dark-700 border border-gray-200 dark:border-dark-600 rounded-lg text-gray-900 dark:text-white" placeholder="<?= htmlspecialchars(t('friends.report_details_placeholder', 'Provide additional context...')) ?>"></textarea>
            </div>
        </div>
        <div class="flex gap-2 mt-6">
            <button @click="submitReport()" class="flex-1 px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-lg font-bold text-sm"><?= t('friends.submit_report', 'Submit Report') ?></button>
            <button @click="reportModalOpen = false" class="px-4 py-2.5 bg-gray-200 dark:bg-dark-600 text-gray-800 dark:text-white rounded-lg font-medium text-sm"><?= t('friends.cancel', 'Cancel') ?></button>
        </div>
        <p x-show="reportError" x-text="reportError" class="text-red-500 text-sm mt-2"></p>
    </div>
    </div>
</div>

<script>
window.__PAGE_DATA = {
    pendingRequests: <?= json_encode(array_values($pendingRequests ?? [])) ?>,
    sentRequests: <?= json_encode(array_values($sentRequests ?? [])) ?>,
    friends: <?= json_encode(array_values($friends ?? [])) ?>,
    blockedUsers: <?= json_encode(array_values($blockedUsers ?? [])) ?>
};
</script>
<script src="/assets/js/pages/friends.js"></script>
