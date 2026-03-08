<?php
$langs = \App\Services\OfficialSiteScraper::getAvailableLanguages();
?>
<div class="max-w-2xl mx-auto space-y-6">
    <h1 class="text-2xl font-display font-bold text-white"><?= t('settings.title') ?></h1>

    <?php if (!empty($errors)): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-lg text-sm">
            <ul class="list-disc list-inside space-y-1">
                <?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
        <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4"></i> <?= t('settings.saved') ?>
        </div>
    <?php endif; ?>

    <!-- Profile Photo -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="user" class="w-5 h-5 text-blue-400"></i> <?= t('settings.profile_photo') ?>
        </h2>
        <div class="flex items-center gap-6">
            <div id="avatar-preview" class="w-20 h-20 rounded-2xl bg-dark-700 flex items-center justify-center overflow-hidden flex-shrink-0">
                <?php if (avatar_url($user)): ?>
                    <img src="<?= htmlspecialchars(avatar_url($user)) ?>" alt="" class="w-full h-full object-cover" id="avatar-img">
                <?php else: ?>
                    <span class="text-2xl font-bold text-dark-400" id="avatar-initial"><?= strtoupper(substr($user['username'] ?? '', 0, 1)) ?></span>
                <?php endif; ?>
            </div>
            <div class="flex-1 space-y-2">
                <form id="avatar-form" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                    <label class="cursor-pointer px-4 py-2 bg-dark-600 hover:bg-dark-500 rounded-lg text-sm font-medium transition">
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" id="avatar-input">
                        <?= t('settings.upload_photo') ?>
                    </label>
                    <?php if ($user['custom_avatar'] ?? null): ?>
                    <button type="button" onclick="removeAvatar()" class="px-4 py-2 text-sm text-red-400 hover:text-red-300 hover:bg-red-500/10 rounded-lg transition">
                        <?= t('settings.remove_photo') ?>
                    </button>
                    <?php endif; ?>
                    <span class="text-xs text-dark-400"><?= t('settings.photo_max_size') ?></span>
                </form>
            </div>
        </div>
    </div>

    <!-- Language & Currency -->
    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="globe" class="w-5 h-5 text-blue-400"></i> <?= t('settings.preferences') ?>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-dark-300 mb-1"><?= t('settings.card_language') ?></label>
                <select onchange="apiPost('/settings/language', { lang: this.value }).then(() => location.reload())"
                    class="w-full px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <?php foreach ($langs as $code => $name): ?>
                        <option value="<?= $code ?>" <?= ($user['preferred_lang'] ?? 'en') === $code ? 'selected' : '' ?>><?= $name ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-dark-300 mb-1"><?= t('settings.currency') ?></label>
                <select onchange="apiPost('/settings/currency', { currency: this.value }).then(() => location.reload())"
                    class="w-full px-3 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white focus:outline-none focus:border-gold-500/50 transition">
                    <option value="usd" <?= ($user['preferred_currency'] ?? 'usd') === 'usd' ? 'selected' : '' ?>><?= t('settings.usd') ?></option>
                    <option value="eur" <?= ($user['preferred_currency'] ?? 'usd') === 'eur' ? 'selected' : '' ?>><?= t('settings.eur') ?></option>
                </select>
            </div>
        </div>
    </div>

    <div class="glass rounded-2xl p-6">
        <h2 class="text-lg font-display font-bold text-white flex items-center gap-2 mb-4">
            <i data-lucide="lock" class="w-5 h-5 text-amber-400"></i> <?= t('settings.change_password') ?>
        </h2>
        <form method="POST" action="/settings/password" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-dark-300 mb-1"><?= t('settings.current_password') ?></label>
                <input type="password" name="current_password" class="w-full px-4 py-3 bg-dark-800 border border-dark-600 rounded-lg text-white focus:outline-none focus:border-gold-500/50 transition text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-dark-300 mb-1"><?= t('settings.new_password') ?></label>
                <input type="password" name="new_password" required minlength="8" class="w-full px-4 py-3 bg-dark-800 border border-dark-600 rounded-lg text-white focus:outline-none focus:border-gold-500/50 transition text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-dark-300 mb-1"><?= t('settings.confirm_password') ?></label>
                <input type="password" name="confirm_password" required class="w-full px-4 py-3 bg-dark-800 border border-dark-600 rounded-lg text-white focus:outline-none focus:border-gold-500/50 transition text-sm">
            </div>
            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg font-bold text-sm hover:from-gold-400 hover:to-amber-500 transition"><?= t('settings.update_password') ?></button>
        </form>
    </div>
</div>

<script>
document.getElementById('avatar-input')?.addEventListener('change', function(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) {
        alert('<?= htmlspecialchars(t('settings.photo_too_large')) ?>');
        return;
    }
    const formData = new FormData();
    formData.append('avatar', file);
    fetch('/settings/avatar', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const preview = document.getElementById('avatar-preview');
                const initial = document.getElementById('avatar-initial');
                preview.innerHTML = '<img src="' + data.url + '?t=' + Date.now() + '" alt="" class="w-full h-full object-cover" id="avatar-img">';
                if (initial) initial.remove();
                location.reload();
            } else {
                alert(data.error || 'Upload failed');
            }
        });
    e.target.value = '';
});

function removeAvatar() {
    if (!confirm('<?= htmlspecialchars(t('settings.remove_photo_confirm')) ?>')) return;
    fetch('/settings/avatar/remove', { method: 'POST' })
        .then(r => r.json())
        .then(data => { if (data.success) location.reload(); });
}
</script>
