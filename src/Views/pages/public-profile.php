<div class="max-w-3xl mx-auto space-y-6" x-data="publicProfile()">
    <div class="glass rounded-2xl p-8">
        <div class="flex items-start gap-6">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-gold-500 to-amber-600 flex items-center justify-center text-3xl font-display font-bold flex-shrink-0" style="color:#fff">
                <?php if ($profileUser['avatar']): ?>
                    <img src="<?= htmlspecialchars($profileUser['avatar']) ?>" class="w-full h-full rounded-2xl object-cover" alt="">
                <?php else: ?>
                    <?= strtoupper(substr($profileUser['username'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div class="flex-1">
                <h1 class="text-2xl font-display font-bold text-gray-900"><?= htmlspecialchars($profileUser['username']) ?></h1>
                <?php if (!empty($profileUser['bio'])): ?>
                    <p class="text-sm text-gray-600 mt-2"><?= htmlspecialchars($profileUser['bio']) ?></p>
                <?php endif; ?>
                <p class="text-sm text-gray-400 mt-1">Joined <?= date('M Y', strtotime($profileUser['created_at'])) ?></p>
            </div>
            <?php if (\App\Core\Auth::check() && \App\Core\Auth::id() !== $profileUser['id']): ?>
                <div x-show="relation === 'friend'" class="relative" x-data="{ dropOpen: false }" @click.outside="dropOpen = false">
                    <button @click="dropOpen = !dropOpen" class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-500/10 text-green-600 rounded-lg text-sm font-bold hover:bg-green-500/20 transition">
                        <i data-lucide="user-check" class="w-4 h-4"></i> Friends <i data-lucide="chevron-down" class="w-3 h-3"></i>
                    </button>
                    <div x-show="dropOpen" x-transition x-cloak class="absolute right-0 mt-1 glass-strong rounded-xl shadow-2xl py-1 w-44 z-50">
                        <button @click="removeFriend()" class="flex items-center gap-2 w-full px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition text-left">
                            <i data-lucide="user-minus" class="w-4 h-4"></i> Remove Friend
                        </button>
                    </div>
                </div>
                <div x-show="relation === 'pending_sent'" x-cloak>
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 text-gray-500 rounded-lg text-sm font-bold">
                        <i data-lucide="clock" class="w-4 h-4"></i> Request Sent
                    </span>
                </div>
                <div x-show="relation === 'pending_received'" x-cloak class="flex gap-2">
                    <button @click="acceptRequest()" class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-500 rounded-lg text-sm font-bold hover:bg-green-600 transition" style="color:#fff !important">
                        <i data-lucide="check" class="w-4 h-4"></i> Accept
                    </button>
                    <button @click="declineRequest()" class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 text-gray-600 rounded-lg text-sm font-bold hover:bg-gray-200 transition">
                        <i data-lucide="x" class="w-4 h-4"></i> Decline
                    </button>
                </div>
                <div x-show="relation === 'none'" x-cloak>
                    <button @click="addFriend()" class="inline-flex items-center gap-1 px-3 py-1.5 bg-gold-500/20 text-gold-400 rounded-lg text-sm font-bold hover:bg-gold-500/30 transition">
                        <i data-lucide="user-plus" class="w-4 h-4"></i> Add Friend
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="glass rounded-xl p-5 text-center">
            <p class="text-2xl font-display font-bold text-gray-900"><?= number_format($stats['unique_cards'] ?? 0) ?></p>
            <p class="text-xs text-gray-400 mt-1">Cards</p>
        </div>
        <div class="glass rounded-xl p-5 text-center">
            <p class="text-2xl font-display font-bold text-gray-900"><?= \App\Core\Currency::format((float)($stats['total_value'] ?? 0)) ?></p>
            <p class="text-xs text-gray-400 mt-1">Value (<?= \App\Core\Currency::label() ?>)</p>
        </div>
        <div class="glass rounded-xl p-5 text-center">
            <p class="text-2xl font-display font-bold text-gray-900"><?= $friendCount ?></p>
            <p class="text-xs text-gray-400 mt-1">Friends</p>
        </div>
        <div class="glass rounded-xl p-5 text-center">
            <p class="text-2xl font-display font-bold text-gray-900"><?= number_format($viewCounts['total'] ?? 0) ?></p>
            <p class="text-xs text-gray-400 mt-1 flex items-center justify-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i> Views</p>
        </div>
    </div>

    <?php if ($profileUser['is_public']): ?>
        <a href="/collection/<?= htmlspecialchars($profileUser['username']) ?>" class="block glass rounded-2xl p-6 text-center hover:border-gold-500/30 transition">
            <i data-lucide="folder-open" class="w-8 h-8 text-gold-400 mx-auto mb-2"></i>
            <p class="text-sm font-medium text-gray-900">View Collection</p>
        </a>
    <?php endif; ?>
</div>

<script>
window.__PAGE_DATA = {
    userId: <?= (int)$profileUser['id'] ?>,
    username: <?= json_encode($profileUser['username']) ?>,
    relation: <?= json_encode(
        $isFriend ? 'friend' : (
            ($pendingSent ?? false) ? 'pending_sent' : (
                ($pendingReceived ?? false) ? 'pending_received' : 'none'
            )
        )
    ) ?>
};
</script>
<script src="/assets/js/pages/public-profile.js"></script>
