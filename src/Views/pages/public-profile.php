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

    <!-- Featured Card -->
    <?php if ($featuredCard): ?>
    <div class="glass rounded-2xl p-6">
        <div class="flex items-center gap-3 mb-4">
            <i data-lucide="star" class="w-6 h-6 text-yellow-400 fill-current"></i>
            <h2 class="text-xl font-display font-bold text-gray-900 dark:text-white">Featured Card</h2>
        </div>
        <div class="flex items-center gap-6 p-4 bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-xl border border-yellow-200/50 dark:border-yellow-400/20">
            <div class="relative flex-shrink-0">
                <img src="<?= htmlspecialchars($featuredCard['card_image_url']) ?>" 
                     alt="<?= htmlspecialchars($featuredCard['card_name']) ?>"
                     class="w-20 h-28 object-cover rounded-lg shadow-lg border-2 border-yellow-300/50">
                <div class="absolute -top-2 -right-2 w-8 h-8 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-full flex items-center justify-center shadow-lg">
                    <i data-lucide="star" class="w-4 h-4 text-white fill-current"></i>
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1"><?= htmlspecialchars($featuredCard['card_name']) ?></h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-2"><?= htmlspecialchars($featuredCard['card_set_id']) ?> • <?= htmlspecialchars($featuredCard['set_name'] ?? 'Unknown Set') ?></p>
                
                <div class="flex items-center gap-4 text-xs">
                    <?php if ($featuredCard['rarity']): ?>
                    <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full font-medium">
                        <?= htmlspecialchars($featuredCard['rarity']) ?>
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($featuredCard['card_color']): ?>
                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full font-medium">
                        <?= htmlspecialchars($featuredCard['card_color']) ?>
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($featuredCard['market_price']): ?>
                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full font-bold">
                        $<?= number_format((float)$featuredCard['market_price'], 2) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex-shrink-0">
                <a href="/cards/<?= htmlspecialchars($featuredCard['card_set_id']) ?>" 
                   class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-400 hover:bg-yellow-500 text-yellow-900 font-medium rounded-lg transition">
                    <i data-lucide="external-link" class="w-4 h-4"></i>
                    View Card
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Forum Activity -->
    <?php if (!empty($recentActivity)): ?>
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
            <i data-lucide="message-square" class="w-5 h-5 text-gold-400"></i> Recent Forum Activity
        </h2>
        <div class="space-y-3">
            <?php foreach ($recentActivity as $activity): ?>
            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold <?= $activity['type'] === 'topic' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300' : 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-300' ?>">
                    <?php if ($activity['type'] === 'topic'): ?>
                        <i data-lucide="plus" class="w-4 h-4"></i>
                    <?php else: ?>
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                    <?php endif; ?>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-medium px-2 py-0.5 rounded <?= $activity['type'] === 'topic' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' : 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' ?>">
                            <?= $activity['type'] === 'topic' ? 'Created topic' : 'Replied to' ?>
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400"><?= date('M j, H:i', strtotime($activity['created_at'])) ?></span>
                    </div>
                    <a href="/forum/<?= htmlspecialchars($activity['category_slug']) ?>/<?= $activity['type'] === 'topic' ? $activity['id'] : $activity['topic_id'] ?>-<?= htmlspecialchars($activity['slug']) ?>" 
                       class="text-sm font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition line-clamp-1">
                        <?= htmlspecialchars($activity['title']) ?>
                    </a>
                </div>
                <i data-lucide="chevron-right" class="w-4 h-4 text-gray-400 dark:text-gray-500"></i>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="/forum" class="text-sm text-gold-400 hover:text-gold-500 transition flex items-center gap-2">
                <i data-lucide="arrow-right" class="w-4 h-4"></i>
                Visit Forum
            </a>
        </div>
    </div>
    <?php endif; ?>

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
