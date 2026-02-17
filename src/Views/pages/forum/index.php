<div class="min-h-screen bg-gray-50 dark:bg-dark-900 py-8">
    <div class="max-w-6xl mx-auto px-4">
        
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Community Forum</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">Discuss, trade, and share with fellow collectors</p>
            </div>
            <div class="flex gap-3">
                <a href="/forum/rules" class="px-4 py-2 bg-gray-100 dark:bg-dark-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-dark-600 transition flex items-center gap-2">
                    <i data-lucide="scroll-text" class="w-4 h-4"></i>
                    Rules
                </a>
                <a href="/forum/search" class="px-4 py-2 bg-gray-100 dark:bg-dark-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-dark-600 transition flex items-center gap-2">
                    <i data-lucide="search" class="w-4 h-4"></i>
                    Search
                </a>
            </div>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-4 mb-6">
            <div class="flex items-start gap-3">
                <i data-lucide="alert-triangle" class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5"></i>
                <div class="text-sm text-yellow-800 dark:text-yellow-200">
                    <strong>Community Guidelines:</strong> Be respectful, no profanity or hate speech. No spam or self-promotion. 
                    Keep discussions relevant. <a href="/forum/rules" class="underline font-medium">Read full rules</a>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-dark-800 rounded-2xl shadow-sm border border-gray-100 dark:border-dark-700 overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 dark:bg-dark-700 border-b border-gray-100 dark:border-dark-600">
                <div class="grid grid-cols-12 gap-4 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    <div class="col-span-6">Category</div>
                    <div class="col-span-2 text-center">Topics</div>
                    <div class="col-span-2 text-center">Posts</div>
                    <div class="col-span-2">Last Activity</div>
                </div>
            </div>

            <?php foreach ($categories as $cat): ?>
            <a href="/forum/<?= htmlspecialchars($cat['slug']) ?>" class="block px-6 py-5 hover:bg-gray-50 dark:hover:bg-dark-700/50 transition border-b border-gray-100 dark:border-dark-700 last:border-0">
                <div class="grid grid-cols-12 gap-4 items-center">
                    <div class="col-span-6 flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: <?= htmlspecialchars($cat['color']) ?>20">
                            <i data-lucide="<?= htmlspecialchars($cat['icon'] ?? 'message-square') ?>" class="w-6 h-6" style="color: <?= htmlspecialchars($cat['color']) ?>"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars($cat['name']) ?></h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5"><?= htmlspecialchars($cat['description'] ?? '') ?></p>
                        </div>
                    </div>
                    <div class="col-span-2 text-center">
                        <span class="text-lg font-semibold text-gray-900 dark:text-white"><?= number_format($cat['topic_count']) ?></span>
                    </div>
                    <div class="col-span-2 text-center">
                        <span class="text-lg font-semibold text-gray-900 dark:text-white"><?= number_format($cat['post_count']) ?></span>
                    </div>
                    <div class="col-span-2">
                        <?php if ($cat['last_topic_id']): ?>
                        <div class="text-sm">
                            <p class="text-gray-900 dark:text-white truncate"><?= htmlspecialchars($cat['last_topic_title'] ?? '') ?></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                by <?= htmlspecialchars($cat['last_user'] ?? 'Unknown') ?> &middot;
                                <?= $cat['last_activity'] ? date('M j, g:i a', strtotime($cat['last_activity'])) : '' ?>
                            </p>
                        </div>
                        <?php else: ?>
                        <span class="text-sm text-gray-400">No posts yet</span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="grid md:grid-cols-2 gap-6 mt-8">
            <div class="bg-white dark:bg-dark-800 rounded-2xl shadow-sm border border-gray-100 dark:border-dark-700 p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i data-lucide="bar-chart-3" class="w-5 h-5 text-blue-500"></i>
                    Forum Statistics
                </h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 dark:bg-dark-700 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($stats['total_topics'] ?? 0) ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total Topics</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-dark-700 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($stats['total_posts'] ?? 0) ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Total Posts</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-dark-700 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white"><?= number_format($stats['total_members'] ?? 0) ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Members</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-dark-700 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-blue-600"><?= htmlspecialchars($stats['newest_member'] ?? '-') ?></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Newest Member</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-dark-800 rounded-2xl shadow-sm border border-gray-100 dark:border-dark-700 p-6">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i data-lucide="users" class="w-5 h-5 text-green-500"></i>
                    Online Now
                    <span class="ml-auto bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 text-xs font-medium px-2 py-0.5 rounded-full"><?= $onlineCount ?> online</span>
                </h3>
                <?php if (empty($onlineUsers)): ?>
                <p class="text-gray-500 dark:text-gray-400 text-sm">No users currently online</p>
                <?php else: ?>
                <div class="flex flex-wrap gap-2">
                    <?php foreach (array_slice($onlineUsers, 0, 20) as $user): ?>
                    <a href="/user/<?= htmlspecialchars($user['username']) ?>" class="flex items-center gap-2 bg-gray-50 dark:bg-dark-700 rounded-full px-3 py-1.5 hover:bg-gray-100 dark:hover:bg-dark-600 transition">
                        <?php if ($user['avatar']): ?>
                        <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="" class="w-5 h-5 rounded-full">
                        <?php else: ?>
                        <div class="w-5 h-5 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-xs font-bold"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
                        <?php endif; ?>
                        <span class="text-sm text-gray-700 dark:text-gray-300"><?= htmlspecialchars($user['username']) ?></span>
                        <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                    </a>
                    <?php endforeach; ?>
                    <?php if ($onlineCount > 20): ?>
                    <span class="text-sm text-gray-500 dark:text-gray-400 px-3 py-1.5">+<?= $onlineCount - 20 ?> more</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>document.addEventListener('DOMContentLoaded', () => lucide.createIcons());</script>
