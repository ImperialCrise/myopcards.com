<div class="min-h-screen bg-gray-50 dark:bg-dark-900 py-8">
    <div class="max-w-4xl mx-auto px-4">

        <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
            <a href="/forum" class="hover:text-gray-900 dark:hover:text-white transition">Forum</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 dark:text-white">Search</span>
        </nav>

        <div class="bg-white dark:bg-dark-800 rounded-2xl shadow-sm border border-gray-100 dark:border-dark-700 p-6 mb-6">
            <form action="/forum/search" method="GET" class="flex gap-3">
                <div class="flex-1 relative">
                    <i data-lucide="search" class="w-5 h-5 absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" 
                           placeholder="Search topics and posts..." 
                           class="w-full pl-12 pr-4 py-3 border border-gray-200 dark:border-dark-600 rounded-xl bg-white dark:bg-dark-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                           minlength="3" required>
                </div>
                <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition">
                    Search
                </button>
            </form>
        </div>

        <?php if ($q): ?>
        <div class="mb-4">
            <p class="text-gray-600 dark:text-gray-400">
                Found <strong class="text-gray-900 dark:text-white"><?= number_format($totalTopics) ?></strong> result<?= $totalTopics !== 1 ? 's' : '' ?> for "<strong class="text-gray-900 dark:text-white"><?= htmlspecialchars($q) ?></strong>"
            </p>
        </div>
        <?php endif; ?>

        <?php if (empty($topics) && $q): ?>
        <div class="bg-white dark:bg-dark-800 rounded-2xl shadow-sm border border-gray-100 dark:border-dark-700 p-12 text-center">
            <i data-lucide="search-x" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4"></i>
            <p class="text-gray-600 dark:text-gray-400 mb-4">No topics found matching your search.</p>
            <p class="text-sm text-gray-500 dark:text-gray-500">Try different keywords or browse the <a href="/forum" class="text-blue-600 hover:underline">forum categories</a>.</p>
        </div>
        <?php elseif (!empty($topics)): ?>
        <div class="bg-white dark:bg-dark-800 rounded-2xl shadow-sm border border-gray-100 dark:border-dark-700 overflow-hidden">
            <?php foreach ($topics as $topic): ?>
            <a href="/forum/<?= htmlspecialchars($topic['category_slug']) ?>/<?= $topic['id'] ?>-<?= htmlspecialchars($topic['slug']) ?>" class="block px-6 py-4 hover:bg-gray-50 dark:hover:bg-dark-700/50 transition border-b border-gray-100 dark:border-dark-700 last:border-0">
                <div class="flex items-start gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-dark-600 text-gray-600 dark:text-gray-400 text-xs font-medium rounded">
                                <?= htmlspecialchars($topic['category_name']) ?>
                            </span>
                        </div>
                        <h3 class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($topic['title']) ?></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            by <span class="text-gray-700 dark:text-gray-300"><?= htmlspecialchars($topic['username']) ?></span>
                            &middot; <?= date('M j, Y', strtotime($topic['created_at'])) ?>
                            &middot; <?= number_format($topic['reply_count']) ?> replies
                        </p>
                    </div>
                    <i data-lucide="chevron-right" class="w-5 h-5 text-gray-400 flex-shrink-0"></i>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center gap-2 mt-6">
            <?php if ($page > 1): ?>
            <a href="?q=<?= urlencode($q) ?>&page=<?= $page - 1 ?>" class="px-4 py-2 bg-white dark:bg-dark-800 border border-gray-200 dark:border-dark-600 rounded-lg hover:bg-gray-50 dark:hover:bg-dark-700 transition">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
            </a>
            <?php endif; ?>
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a href="?q=<?= urlencode($q) ?>&page=<?= $i ?>" class="px-4 py-2 rounded-lg transition <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white dark:bg-dark-800 border border-gray-200 dark:border-dark-600 hover:bg-gray-50 dark:hover:bg-dark-700' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?q=<?= urlencode($q) ?>&page=<?= $page + 1 ?>" class="px-4 py-2 bg-white dark:bg-dark-800 border border-gray-200 dark:border-dark-600 rounded-lg hover:bg-gray-50 dark:hover:bg-dark-700 transition">
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
