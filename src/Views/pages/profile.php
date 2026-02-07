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
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-4">
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
    </div>

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
