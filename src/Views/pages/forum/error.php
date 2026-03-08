<div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
    <div class="w-20 h-20 rounded-2xl bg-red-100 dark:bg-red-900/20 flex items-center justify-center mb-6">
        <i data-lucide="alert-circle" class="w-10 h-10 text-red-500"></i>
    </div>
    <h1 class="text-2xl font-display font-bold text-gray-900 dark:text-white mb-4">Something went wrong</h1>
    <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-md"><?= htmlspecialchars($message ?? 'Unable to load this topic. Please try again later.') ?></p>
    <div class="flex gap-4">
        <a href="/forum" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition">Back to Forum</a>
        <a href="/" class="px-6 py-3 bg-gray-200 dark:bg-dark-600 hover:bg-gray-300 dark:hover:bg-dark-500 text-gray-900 dark:text-white font-medium rounded-xl transition">Home</a>
    </div>
</div>
<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
