<div class="max-w-3xl mx-auto space-y-6">
    <div class="glass rounded-2xl p-8">
        <div class="flex items-start gap-6">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-gold-500 to-amber-600 flex items-center justify-center text-dark-900 text-3xl font-display font-bold flex-shrink-0">
                <?php if ($user['avatar']): ?>
                    <img src="<?= htmlspecialchars($user['avatar']) ?>" class="w-full h-full rounded-2xl object-cover" alt="">
                <?php else: ?>
                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div class="flex-1 min-w-0">
                <h1 class="text-2xl font-display font-bold text-white"><?= htmlspecialchars($user['username']) ?></h1>
                <p class="text-sm text-dark-400 mt-1"><?= htmlspecialchars($user['email']) ?></p>
                <p class="text-sm text-dark-400">Joined <?= date('M Y', strtotime($user['created_at'])) ?></p>
                <?php if ($user['is_public'] ?? false): ?>
                    <div class="flex items-center gap-2 mt-3" x-data="{ copied: false }">
                        <a href="/user/<?= htmlspecialchars($user['username']) ?>" class="text-xs text-gold-400 hover:text-gold-300 transition flex items-center gap-1">
                            <i data-lucide="external-link" class="w-3 h-3"></i> View public profile
                        </a>
                        <span class="text-dark-600">|</span>
                        <button @click="navigator.clipboard.writeText('<?= ($_ENV['APP_URL'] ?? 'https://myopcards.com') ?>/user/<?= htmlspecialchars($user['username']) ?>'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="text-xs text-dark-400 hover:text-gold-400 transition flex items-center gap-1">
                            <i data-lucide="copy" class="w-3 h-3"></i> <span x-text="copied ? 'Copied' : 'Copy link'"></span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
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
            <p class="text-2xl font-display font-bold text-white"><?= number_format($viewCounts['profile'] ?? 0) ?></p>
            <p class="text-xs text-dark-400 mt-1 flex items-center justify-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i> Profile</p>
        </div>
        <div class="glass rounded-xl p-5 text-center">
            <p class="text-2xl font-display font-bold text-white"><?= number_format($viewCounts['collection'] ?? 0) ?></p>
            <p class="text-xs text-dark-400 mt-1 flex items-center justify-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i> Collection</p>
        </div>
    </div>

    <?php if (!empty($recentViewers)): ?>
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="users" class="w-5 h-5 text-gold-400"></i> Recent Visitors
        </h2>
        <div class="space-y-3">
            <?php foreach ($recentViewers as $v): ?>
                <div class="flex items-center gap-3">
                    <?php if (!empty($v['avatar'])): ?>
                        <img src="<?= htmlspecialchars($v['avatar']) ?>" class="w-8 h-8 rounded-full" alt="">
                    <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600"><?= strtoupper(substr($v['username'] ?? '?', 0, 1)) ?></div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <a href="/user/<?= htmlspecialchars($v['username'] ?? '') ?>" class="text-sm font-medium text-white hover:text-gold-400 transition"><?= htmlspecialchars($v['username'] ?? 'Anonymous') ?></a>
                        <p class="text-[10px] text-dark-400">viewed your <?= $v['page_type'] ?></p>
                    </div>
                    <span class="text-[10px] text-dark-500"><?= date('M j, H:i', strtotime($v['viewed_at'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4"></i> Profile updated successfully.
        </div>
    <?php endif; ?>

    <!-- Edit Profile -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="edit" class="w-5 h-5 text-gold-400"></i> Edit Profile
        </h2>
        <form method="POST" action="/profile/update" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-dark-300 mb-1">Bio</label>
                <textarea name="bio" rows="3" class="w-full px-4 py-3 bg-dark-800 border border-dark-600 rounded-lg text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 transition text-sm" placeholder="Tell us about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
            </div>
            <div class="flex items-center gap-3">
                <input type="checkbox" name="is_public" id="is_public" value="1" <?= ($user['is_public'] ?? 0) ? 'checked' : '' ?>
                    class="w-4 h-4 rounded border-dark-600 bg-dark-800 text-gold-500 focus:ring-gold-500/50">
                <label for="is_public" class="text-sm text-dark-300">Make my profile and collection public</label>
            </div>
            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg font-bold text-sm hover:from-gold-400 hover:to-amber-500 transition">Save Changes</button>
        </form>
    </div>
</div>
