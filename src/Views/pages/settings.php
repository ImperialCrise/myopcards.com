<?php
$currentUser = \App\Core\Auth::user();
$langs = \App\Services\OfficialSiteScraper::getAvailableLanguages();
?>
<div class="max-w-2xl mx-auto space-y-6">
    <h1 class="text-2xl font-display font-bold text-white">Settings</h1>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm">
            <ul class="list-disc list-inside space-y-1">
                <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4"></i> Settings saved.
        </div>
    <?php endif; ?>

    <!-- Language & Currency -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="globe" class="w-5 h-5 text-blue-400"></i> Preferences
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-dark-300 mb-1">Card Language</label>
                <select onchange="apiPost('/settings/language', { lang: this.value }).then(() => showToast('Language updated'))"
                    class="w-full px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <?php foreach ($langs as $code => $name): ?>
                        <option value="<?= $code ?>" <?= ($currentUser['preferred_lang'] ?? 'en') === $code ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-dark-300 mb-1">Preferred Currency</label>
                <select onchange="apiPost('/settings/currency', { currency: this.value }).then(() => showToast('Currency updated'))"
                    class="w-full px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <option value="usd" <?= ($currentUser['preferred_currency'] ?? 'usd') === 'usd' ? 'selected' : '' ?>>USD (TCGPlayer)</option>
                    <option value="eur" <?= ($currentUser['preferred_currency'] ?? 'usd') === 'eur' ? 'selected' : '' ?>>EUR (Cardmarket)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="lock" class="w-5 h-5 text-amber-400"></i> Change Password
        </h2>
        <form method="POST" action="/settings/password" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-dark-300 mb-1">Current Password</label>
                <input type="password" name="current_password" class="w-full px-4 py-3 bg-dark-800 border border-dark-600 rounded-lg text-white focus:outline-none focus:border-gold-500/50 transition text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-dark-300 mb-1">New Password</label>
                <input type="password" name="new_password" required minlength="8" class="w-full px-4 py-3 bg-dark-800 border border-dark-600 rounded-lg text-white focus:outline-none focus:border-gold-500/50 transition text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-dark-300 mb-1">Confirm New Password</label>
                <input type="password" name="confirm_password" required class="w-full px-4 py-3 bg-dark-800 border border-dark-600 rounded-lg text-white focus:outline-none focus:border-gold-500/50 transition text-sm">
            </div>
            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg font-bold text-sm hover:from-gold-400 hover:to-amber-500 transition">Update Password</button>
        </form>
    </div>
</div>
