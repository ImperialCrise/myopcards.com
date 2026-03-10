<?php
$currentUserId = \App\Core\Auth::id();
function msgTimeAgo(string $ts): string {
    $diff = time() - strtotime($ts);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm';
    if ($diff < 86400) return floor($diff / 3600) . 'h';
    return date('M j', strtotime($ts));
}
?>
<div class="flex justify-center h-[calc(100vh-4rem)] overflow-hidden" style="background:var(--bg-base,#0a0c12)">
<div class="flex w-full max-w-5xl h-full overflow-hidden">

    <!-- Sidebar: conversation list -->
    <aside class="w-full md:w-80 lg:w-96 flex-shrink-0 flex flex-col border-r" style="border-color:var(--nav-border);background:var(--d900,#0f1117)">
        <div class="px-4 py-4 flex items-center justify-between border-b" style="border-color:var(--nav-border)">
            <h1 class="text-lg font-display font-bold text-white"><?= t('messages.title') ?></h1>
            <a href="/friends" class="p-2 rounded-lg text-dark-400 hover:text-white hover:bg-dark-700 transition" title="<?= t('friends.title') ?>">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
            </a>
        </div>

        <div class="flex-1 overflow-y-auto">
            <?php if (empty($conversations)): ?>
            <div class="flex flex-col items-center justify-center h-full text-center p-8">
                <div class="w-16 h-16 rounded-2xl bg-dark-700 flex items-center justify-center mb-4">
                    <i data-lucide="message-circle" class="w-8 h-8 text-dark-400"></i>
                </div>
                <p class="text-dark-300 font-medium mb-1"><?= t('messages.no_conversations') ?></p>
                <p class="text-dark-500 text-sm"><?= t('messages.inbox_empty_desc') ?></p>
                <a href="/friends" class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-gold-500/20 text-gold-400 rounded-lg text-sm font-bold hover:bg-gold-500/30 transition">
                    <i data-lucide="users" class="w-4 h-4"></i> <?= t('friends.title') ?>
                </a>
            </div>
            <?php else: ?>
            <div class="py-2">
                <?php foreach ($conversations as $conv):
                    $ou = $conv['other_user'];
                    $isSystem = $ou['is_system'] ?? false;
                    $hasUnread = ($conv['unread_count'] ?? 0) > 0;
                    $lastMsg = $conv['last_message'];
                ?>
                <a href="/messages/<?= (int)$conv['id'] ?>"
                   class="flex items-center gap-3 px-4 py-3 hover:bg-dark-700/50 transition group cursor-pointer <?= $hasUnread ? 'bg-dark-800/60' : '' ?>">
                    <!-- Avatar -->
                    <div class="flex-shrink-0 relative">
                        <?php if ($isSystem): ?>
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-gold-500 to-amber-600 flex items-center justify-center flex-shrink-0 shadow-lg">
                            <svg width="22" height="22" viewBox="0 0 36 36" fill="none"><rect width="36" height="36" rx="9" fill="rgba(0,0,0,0.2)"/><rect x="8" y="7" width="15" height="21" rx="2.5" fill="white" fill-opacity="0.25" transform="rotate(-8 8 7)"/><rect x="12" y="8" width="15" height="21" rx="2.5" fill="white" fill-opacity="0.9" transform="rotate(5 20 18)"/><text x="18" y="22" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="10" fill="#92400e" letter-spacing="-0.5">OP</text></svg>
                        </div>
                        <?php elseif (!empty($ou['avatar_url'])): ?>
                        <img src="<?= htmlspecialchars($ou['avatar_url']) ?>" alt="" class="w-12 h-12 rounded-full object-cover">
                        <?php else: ?>
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-cyan-600 flex items-center justify-center font-bold text-white text-lg">
                            <?= strtoupper(substr($ou['username'] ?? '?', 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($hasUnread): ?>
                        <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center text-[9px] font-bold text-white leading-none"><?= min((int)$conv['unread_count'], 9) ?><?= (int)$conv['unread_count'] > 9 ? '+' : '' ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="font-semibold text-sm truncate <?= $hasUnread ? 'text-white' : 'text-dark-200' ?>">
                                <?= htmlspecialchars($ou['username'] ?? '') ?>
                                <?php if ($isSystem): ?><span class="ml-1 text-[9px] font-bold text-gold-400 bg-gold-500/10 px-1 rounded">SYSTEM</span><?php endif; ?>
                            </p>
                            <?php if ($lastMsg): ?>
                            <span class="text-[10px] text-dark-500 flex-shrink-0"><?= msgTimeAgo($lastMsg['created_at']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($lastMsg): ?>
                        <p class="text-xs truncate mt-0.5 <?= $hasUnread ? 'text-dark-300 font-medium' : 'text-dark-500' ?>">
                            <?php if ((int)$lastMsg['sender_id'] === $currentUserId): ?><span class="text-dark-500">You: </span><?php endif; ?>
                            <?= htmlspecialchars(mb_strimwidth($lastMsg['body'], 0, 60, '…')) ?>
                        </p>
                        <?php else: ?>
                        <p class="text-xs text-dark-600 mt-0.5 italic">No messages yet</p>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </aside>

    <!-- Main area: empty state on desktop -->
    <main class="hidden md:flex flex-1 flex-col items-center justify-center text-center border-l" style="background:var(--d950,#07090e);border-color:var(--nav-border)">
        <div class="w-20 h-20 rounded-2xl bg-dark-800 flex items-center justify-center mb-5">
            <i data-lucide="message-circle" class="w-10 h-10 text-dark-500"></i>
        </div>
        <p class="text-dark-300 font-semibold text-lg">Select a conversation</p>
        <p class="text-dark-600 text-sm mt-1">Pick someone from the left to start chatting</p>
    </main>

</div>
</div><!-- /max-width wrapper -->
</div><!-- /outer flex -->
<script>document.addEventListener('DOMContentLoaded', () => { if (window.lucide) lucide.createIcons(); });</script>
