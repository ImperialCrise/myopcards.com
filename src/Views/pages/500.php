<div class="flex flex-col items-center justify-center min-h-[60vh] text-center">
    <div class="w-20 h-20 rounded-2xl bg-red-500/10 flex items-center justify-center mb-6">
        <i data-lucide="alert-triangle" class="w-10 h-10 text-red-500"></i>
    </div>
    <h1 class="text-7xl font-display font-bold gradient-text mb-4"><?= t('500.title', 'Something went wrong') ?></h1>
    <p class="text-xl text-gray-500 mb-8"><?= htmlspecialchars($message ?? t('500.message', 'Please try again later.')) ?></p>
    <a href="/" class="px-6 py-3 bg-gray-900 rounded-xl font-display font-bold hover:bg-gray-800 transition" style="color:#fff !important">
        <?= t('500.return', 'Return home') ?>
    </a>
</div>
