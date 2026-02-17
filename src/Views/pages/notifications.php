<div class="min-h-screen bg-gray-50 dark:bg-dark-900 py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Notifications</h1>
            
            <?php if (!empty($notifications) && array_filter($notifications, fn($n) => !$n['is_read'])): ?>
            <button onclick="markAllAsRead()" class="px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition">
                Mark all as read
            </button>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>
        <div class="bg-white dark:bg-dark-800 rounded-2xl shadow-sm border border-gray-100 dark:border-dark-700 p-12 text-center">
            <i data-lucide="bell" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4"></i>
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">No notifications yet</h2>
            <p class="text-gray-500 dark:text-gray-400">When people interact with your posts, you'll see notifications here.</p>
        </div>
        <?php else: ?>
        
        <div class="space-y-4">
            <?php foreach ($notifications as $notification): ?>
            <div class="bg-white dark:bg-dark-800 rounded-2xl shadow-sm border border-gray-100 dark:border-dark-700 p-6 <?= $notification['is_read'] ? 'opacity-75' : '' ?>">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0">
                        <?php if ($notification['type'] === 'forum_reply'): ?>
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                            <i data-lucide="message-circle" class="w-5 h-5 text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <?php elseif ($notification['type'] === 'forum_like'): ?>
                        <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                            <i data-lucide="heart" class="w-5 h-5 text-red-600 dark:text-red-400"></i>
                        </div>
                        <?php else: ?>
                        <div class="w-10 h-10 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                            <i data-lucide="bell" class="w-5 h-5 text-gray-600 dark:text-gray-400"></i>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!$notification['is_read']): ?>
                        <div class="w-3 h-3 bg-blue-500 rounded-full -mt-2 ml-8"></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-1 min-w-0">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                            <?= htmlspecialchars($notification['title']) ?>
                        </h3>
                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                            <?= htmlspecialchars($notification['message']) ?>
                        </p>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-500">
                                <?= date('M j, Y \a\t g:i a', strtotime($notification['created_at'])) ?>
                            </span>
                            
                            <div class="flex items-center gap-2">
                                <?php if ($notification['type'] === 'forum_reply' && isset($notification['data']['topic_id'])): ?>
                                <a href="/forum/general/<?= $notification['data']['topic_id'] ?>-<?= urlencode(strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $notification['title']))) ?>" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                                    View Topic
                                </a>
                                <?php endif; ?>
                                
                                <?php if (!$notification['is_read']): ?>
                                <button onclick="markAsRead(<?= $notification['id'] ?>)" class="text-sm text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                                    Mark as read
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());

function markAsRead(notificationId) {
    fetch('/notifications/read', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'notification_id=' + notificationId
    }).then(() => location.reload());
}

function markAllAsRead() {
    fetch('/notifications/read-all', { method: 'POST' })
        .then(() => location.reload());
}
</script>