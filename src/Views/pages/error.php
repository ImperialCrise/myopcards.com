<?php $title = $title ?? 'Error'; $message = $message ?? 'An error occurred.'; ?>
<div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
    <div class="w-20 h-20 rounded-2xl bg-red-100 dark:bg-red-900/20 flex items-center justify-center mb-6">
        <i data-lucide="alert-circle" class="w-10 h-10 text-red-500"></i>
    </div>
    <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white mb-4"><?= htmlspecialchars($title) ?></h1>
    <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-md"><?= htmlspecialchars($message) ?></p>
    <a href="/play" class="px-6 py-3 bg-gray-900 rounded-xl font-display font-bold hover:bg-gray-800 transition" style="color:#fff !important"><?= t('404.return') ?></a>
</div>
<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
