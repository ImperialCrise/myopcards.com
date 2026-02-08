<div class="max-w-3xl mx-auto space-y-6">
    <div class="glass rounded-2xl p-8">
        <div class="flex items-start gap-6">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-gold-500 to-amber-600 flex items-center justify-center text-dark-900 text-3xl font-display font-bold flex-shrink-0">
                <?php if ($profileUser['avatar']): ?>
                    <img src="<?= htmlspecialchars($profileUser['avatar']) ?>" class="w-full h-full rounded-2xl object-cover" alt="">
                <?php else: ?>
                    <?= strtoupper(substr($profileUser['username'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div class="flex-1">
                <h1 class="text-2xl font-display font-bold text-white"><?= htmlspecialchars($profileUser['username']) ?></h1>
                <?php if (!empty($profileUser['bio'])): ?>
                    <p class="text-sm text-dark-300 mt-2"><?= htmlspecialchars($profileUser['bio']) ?></p>
                <?php endif; ?>
                <p class="text-sm text-dark-400 mt-1">Joined <?= date('M Y', strtotime($profileUser['created_at'])) ?></p>
            </div>
            <?php if (\App\Core\Auth::check() && \App\Core\Auth::id() !== $profileUser['id']): ?>
                <div>
                    <?php if ($isFriend): ?>
                        <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-500/10 text-green-400 rounded-lg text-sm font-bold">
                            <i data-lucide="user-check" class="w-4 h-4"></i> Friends
                        </span>
                    <?php else: ?>
                        <form method="POST" action="/friends/request">
                            <input type="hidden" name="user_id" value="<?= $profileUser['id'] ?>">
                            <button class="inline-flex items-center gap-1 px-3 py-1.5 bg-gold-500/20 text-gold-400 rounded-lg text-sm font-bold hover:bg-gold-500/30 transition">
                                <i data-lucide="user-plus" class="w-4 h-4"></i> Add Friend
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="glass rounded-xl p-5 text-center">
            <p class="text-2xl font-display font-bold text-white"><?= number_format($stats['unique_cards'] ?? 0) ?></p>
            <p class="text-xs text-dark-400 mt-1">Cards</p>
        </div>
        <div class="glass rounded-xl p-5 text-center">
            <p class="text-2xl font-display font-bold text-white">$<?= number_format($stats['total_value'] ?? 0, 2) ?></p>
            <p class="text-xs text-dark-400 mt-1">Value</p>
        </div>
        <div class="glass rounded-xl p-5 text-center">
            <p class="text-2xl font-display font-bold text-white"><?= $friendCount ?></p>
            <p class="text-xs text-dark-400 mt-1">Friends</p>
        </div>
        <div class="glass rounded-xl p-5 text-center">
            <p class="text-2xl font-display font-bold text-white"><?= number_format($viewCounts['total'] ?? 0) ?></p>
            <p class="text-xs text-dark-400 mt-1 flex items-center justify-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i> Views</p>
        </div>
    </div>

    <?php if ($profileUser['is_public']): ?>
        <a href="/collection/<?= htmlspecialchars($profileUser['username']) ?>" class="block glass rounded-2xl p-6 text-center hover:border-gold-500/30 transition">
            <i data-lucide="folder-open" class="w-8 h-8 text-gold-400 mx-auto mb-2"></i>
            <p class="text-sm font-medium text-white">View Collection</p>
        </a>
    <?php endif; ?>
</div>
