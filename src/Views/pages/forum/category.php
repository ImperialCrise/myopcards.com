<?php $isLoggedIn = \App\Core\Auth::check(); ?>

<div class="min-h-screen bg-gray-50 dark:bg-dark-900 py-8">
    <div class="max-w-6xl mx-auto px-4">

        <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
            <a href="/forum" class="hover:text-gray-900 dark:hover:text-white transition">Forum</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 dark:text-white"><?= htmlspecialchars($category['name']) ?></span>
        </nav>

        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-xl flex items-center justify-center" style="background: <?= htmlspecialchars($category['color']) ?>20">
                    <i data-lucide="<?= htmlspecialchars($category['icon'] ?? 'message-square') ?>" class="w-7 h-7" style="color: <?= htmlspecialchars($category['color']) ?>"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($category['name']) ?></h1>
                    <p class="text-gray-600 dark:text-gray-400"><?= htmlspecialchars($category['description'] ?? '') ?></p>
                </div>
            </div>
            <?php if ($isLoggedIn && !$category['is_locked']): ?>
            <a href="/forum/<?= htmlspecialchars($category['slug']) ?>/new" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition flex items-center gap-2">
                <i data-lucide="plus" class="w-4 h-4"></i>
                New Topic
            </a>
            <?php endif; ?>
        </div>

        <?php if ($category['is_locked']): ?>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 mb-6">
            <div class="flex items-center gap-2 text-yellow-800 dark:text-yellow-200">
                <i data-lucide="lock" class="w-5 h-5"></i>
                <span>This category is locked. Only administrators can post here.</span>
            </div>
        </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-dark-800 rounded-2xl shadow-sm border border-gray-100 dark:border-dark-700 overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 dark:bg-dark-700 border-b border-gray-100 dark:border-dark-600">
                <div class="grid grid-cols-12 gap-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    <div class="col-span-6">Topic</div>
                    <div class="col-span-2 text-center">Replies</div>
                    <div class="col-span-2 text-center">Views</div>
                    <div class="col-span-2">Last Reply</div>
                </div>
            </div>

            <?php if (empty($topics)): ?>
            <div class="px-6 py-12 text-center">
                <i data-lucide="message-square-off" class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">No topics yet. Be the first to start a discussion!</p>
                <?php if ($isLoggedIn && !$category['is_locked']): ?>
                <a href="/forum/<?= htmlspecialchars($category['slug']) ?>/new" class="inline-block mt-4 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                    Create Topic
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <?php foreach ($topics as $topic): ?>
            <a href="/forum/<?= htmlspecialchars($category['slug']) ?>/<?= $topic['id'] ?>-<?= htmlspecialchars($topic['slug']) ?>" class="block px-6 py-4 hover:bg-gray-50 dark:hover:bg-dark-700/50 transition border-b border-gray-100 dark:border-dark-700 last:border-0">
                <div class="grid grid-cols-12 gap-4 items-center">
                    <div class="col-span-6">
                        <div class="flex items-start gap-3">
                            <?php if ($topic['avatar']): ?>
                            <img src="<?= htmlspecialchars($topic['avatar']) ?>" alt="" class="w-10 h-10 rounded-full flex-shrink-0">
                            <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold flex-shrink-0">
                                <?= strtoupper(substr($topic['username'], 0, 1)) ?>
                            </div>
                            <?php endif; ?>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <?php if ($topic['is_pinned']): ?>
                                    <span class="px-2 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-xs font-medium rounded">Pinned</span>
                                    <?php endif; ?>
                                    <?php if ($topic['is_announcement']): ?>
                                    <span class="px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-xs font-medium rounded">Announcement</span>
                                    <?php endif; ?>
                                    <?php if ($topic['is_locked']): ?>
                                    <i data-lucide="lock" class="w-3.5 h-3.5 text-gray-400"></i>
                                    <?php endif; ?>
                                    <h3 class="font-semibold text-gray-900 dark:text-white truncate"><?= htmlspecialchars($topic['title']) ?></h3>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                    by <span class="text-gray-700 dark:text-gray-300"><?= htmlspecialchars($topic['username']) ?></span>
                                    &middot; <?= date('M j, Y', strtotime($topic['created_at'])) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-2 text-center">
                        <span class="text-lg font-semibold text-gray-900 dark:text-white"><?= number_format($topic['reply_count']) ?></span>
                    </div>
                    <div class="col-span-2 text-center">
                        <span class="text-lg font-semibold text-gray-900 dark:text-white"><?= number_format($topic['views']) ?></span>
                    </div>
                    <div class="col-span-2">
                        <?php if ($topic['last_reply_at']): ?>
                        <p class="text-sm text-gray-900 dark:text-white"><?= htmlspecialchars($topic['last_reply_username'] ?? '') ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400"><?= date('M j, g:i a', strtotime($topic['last_reply_at'])) ?></p>
                        <?php else: ?>
                        <span class="text-sm text-gray-400">No replies</span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center gap-2 mt-6">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="px-4 py-2 bg-white dark:bg-dark-800 border border-gray-200 dark:border-dark-600 rounded-lg hover:bg-gray-50 dark:hover:bg-dark-700 transition">
                <i data-lucide="chevron-left" class="w-4 h-4"></i>
            </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <a href="?page=<?= $i ?>" class="px-4 py-2 rounded-lg transition <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white dark:bg-dark-800 border border-gray-200 dark:border-dark-600 hover:bg-gray-50 dark:hover:bg-dark-700' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>" class="px-4 py-2 bg-white dark:bg-dark-800 border border-gray-200 dark:border-dark-600 rounded-lg hover:bg-gray-50 dark:hover:bg-dark-700 transition">
                <i data-lucide="chevron-right" class="w-4 h-4"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
