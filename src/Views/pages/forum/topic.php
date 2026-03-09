<?php
$isLoggedIn = \App\Core\Auth::check();
$currentUserId = \App\Core\Auth::id();
$isAdmin = \App\Core\Auth::isAdmin();

// Card-style block theme bg/border map (matches profile.php)
$blockThemeBg = [
    'default'  => 'rgba(17,24,39,0.72)',
    'ocean'    => 'rgba(8,28,52,0.78)',
    'fire'     => 'rgba(52,8,8,0.78)',
    'gold'     => 'rgba(36,24,4,0.78)',
    'emerald'  => 'rgba(4,28,16,0.78)',
    'purple'   => 'rgba(20,4,40,0.78)',
    'midnight' => 'rgba(6,6,20,0.82)',
    'crimson'  => 'rgba(36,4,12,0.78)',
    'slate'    => 'rgba(16,24,36,0.78)',
    'rose'     => 'rgba(36,8,20,0.78)',
    'teal'     => 'rgba(4,28,28,0.78)',
    'amber'    => 'rgba(36,20,4,0.78)',
];
$blockThemeBorder = [
    'default'  => 'rgba(74,100,128,0.22)',
    'ocean'    => 'rgba(30,100,200,0.3)',
    'fire'     => 'rgba(200,40,30,0.32)',
    'gold'     => 'rgba(200,160,40,0.35)',
    'emerald'  => 'rgba(20,160,80,0.3)',
    'purple'   => 'rgba(120,30,210,0.35)',
    'midnight' => 'rgba(50,50,130,0.28)',
    'crimson'  => 'rgba(180,20,50,0.32)',
    'slate'    => 'rgba(100,130,160,0.28)',
    'rose'     => 'rgba(200,50,100,0.3)',
    'teal'     => 'rgba(20,160,150,0.3)',
    'amber'    => 'rgba(210,130,20,0.32)',
];
function forumAuthorCard(array $u, array $featuredCards, array $blockThemeBg, array $blockThemeBorder, bool $large = false): void {
    $uid        = (int)($u['user_id'] ?? $u['id'] ?? 0);
    $username   = htmlspecialchars($u['username'] ?? '');
    $accent     = htmlspecialchars($u['profile_accent_color'] ?? '#d4a853');
    $style      = $u['card_style'] ?? 'default';
    $panelBg    = $blockThemeBg[$style]   ?? $blockThemeBg['default'];
    $panelBord  = $blockThemeBorder[$style] ?? $blockThemeBorder['default'];
    $avatarSize = $large ? 'w-16 h-16' : 'w-12 h-12';
    $featured   = $featuredCards[$uid] ?? null;
    $postCount  = (int)($u['user_post_count'] ?? 0);
    $joined     = isset($u['user_joined']) ? date('M Y', strtotime($u['user_joined'])) : '';
    $avatarUrl  = \App\Models\User::getAvatarUrl($u);

    echo '<div class="forum-author-card">';

    // Avatar
    echo '<div class="relative inline-block mb-2">';
    echo '<a href="/user/' . $username . '">';
    if ($avatarUrl) {
        echo '<img src="' . htmlspecialchars($avatarUrl) . '" alt="" class="' . $avatarSize . ' rounded-full object-cover" style="border:3px solid ' . $accent . ';box-shadow:0 0 10px ' . $accent . '44">';
    } else {
        echo '<div class="' . $avatarSize . ' rounded-full flex items-center justify-center text-lg font-bold" style="background:linear-gradient(135deg,' . $accent . ',' . $accent . '88);color:#fff;border:3px solid ' . $accent . ';box-shadow:0 0 10px ' . $accent . '44">';
        echo strtoupper(substr($u['username'] ?? '?', 0, 1));
        echo '</div>';
    }
    echo '</a>';

    // Featured card badge
    if ($featured) {
        echo '<div class="absolute -bottom-1 -right-1 group z-10">';
        echo '<div class="relative">';
        echo '<img src="' . htmlspecialchars(card_img_url($featured)) . '" data-ext-src="' . htmlspecialchars($featured['card_image_url'] ?? '') . '" alt="' . htmlspecialchars($featured['card_name']) . '" class="' . ($large ? 'w-8 h-10' : 'w-6 h-8') . ' object-cover rounded border-2 border-white shadow-lg" onerror="cardImgErr(this)">';
        echo '<div class="absolute -top-1 -right-1 w-3.5 h-3.5 rounded-full flex items-center justify-center" style="background:' . $accent . '">';
        echo '<i data-lucide="star" class="w-2 h-2 text-white fill-current"></i>';
        echo '</div>';
        echo '</div>';
        echo '<div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 px-2 py-0.5 bg-gray-900 text-white text-[10px] rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none">';
        echo htmlspecialchars($featured['card_name']);
        echo '</div>';
        echo '</div>';
    }
    echo '</div>'; // /relative

    // Username
    echo '<a href="/user/' . $username . '" class="font-semibold text-sm hover:opacity-80 transition block leading-tight" style="color:' . $accent . '">' . $username . '</a>';

    // Stats
    echo '<p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">' . number_format($postCount) . ' posts</p>';
    if ($joined) {
        echo '<p class="text-[10px] text-gray-500 dark:text-gray-400">Joined ' . $joined . '</p>';
    }

    echo '</div>'; // /forum-author-card
}
?>

<div class="min-h-screen bg-gray-50 dark:bg-dark-900 py-8" x-data="{ lightboxSrc: null, lightboxAlt: '' }" @keydown.escape.window="lightboxSrc = null">
    <div class="max-w-5xl mx-auto px-4">

        <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6 flex-wrap">
            <a href="/forum" class="hover:text-gray-900 dark:hover:text-white transition">Forum</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <a href="/forum/<?= htmlspecialchars($topic['category_slug']) ?>" class="hover:text-gray-900 dark:hover:text-white transition"><?= htmlspecialchars($topic['category_name']) ?></a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 dark:text-white truncate max-w-xs"><?= htmlspecialchars($topic['title']) ?></span>
        </nav>

        <?php if (isset($_SESSION['forum_error'])): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-6">
            <p class="text-red-800 dark:text-red-200"><?= htmlspecialchars($_SESSION['forum_error']) ?></p>
        </div>
        <?php unset($_SESSION['forum_error']); endif; ?>

        <?php
        $topicAccent = htmlspecialchars($topic['profile_accent_color'] ?? '#d4a853');
        $topicStyle  = $topic['card_style'] ?? 'default';
        $topicPanelBg   = $blockThemeBg[$topicStyle]   ?? $blockThemeBg['default'];
        $topicPanelBord = $blockThemeBorder[$topicStyle] ?? $blockThemeBorder['default'];
        ?>
        <div class="bg-white dark:bg-dark-800 rounded-2xl shadow-sm border border-gray-100 dark:border-dark-700 overflow-hidden mb-6">
            <div class="border-b border-gray-100 dark:border-dark-700">
                <!-- Topic header: author strip + meta -->
                <div class="flex items-center gap-3 px-4 py-3" style="background:<?= $topicPanelBg ?>;border-bottom:1px solid <?= $topicPanelBord ?>">
                    <?php $topicAvatarUrl = \App\Models\User::getAvatarUrl($topic); ?>
                    <div class="relative flex-shrink-0">
                        <?php if ($topicAvatarUrl): ?>
                        <a href="/user/<?= htmlspecialchars($topic['username']) ?>">
                            <img src="<?= htmlspecialchars($topicAvatarUrl) ?>" alt="" class="w-9 h-9 rounded-full object-cover" style="border:2px solid <?= $topicAccent ?>;box-shadow:0 0 8px <?= $topicAccent ?>44">
                        </a>
                        <?php else: ?>
                        <a href="/user/<?= htmlspecialchars($topic['username']) ?>" class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold" style="background:linear-gradient(135deg,<?= $topicAccent ?>,<?= $topicAccent ?>88);color:#fff;border:2px solid <?= $topicAccent ?>;display:flex">
                            <?= strtoupper(substr($topic['username'], 0, 1)) ?>
                        </a>
                        <?php endif; ?>
                        <?php if (isset($featuredCards[$topic['user_id']])): ?>
                        <div class="absolute -bottom-1 -right-1 group">
                            <img src="<?= htmlspecialchars(card_img_url($featuredCards[$topic['user_id']])) ?>" data-ext-src="<?= htmlspecialchars($featuredCards[$topic['user_id']]['card_image_url'] ?? '') ?>" alt="" class="w-5 h-6 object-cover rounded border border-white shadow" title="Featured: <?= htmlspecialchars($featuredCards[$topic['user_id']]['card_name']) ?>" onerror="cardImgErr(this)">
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <a href="/user/<?= htmlspecialchars($topic['username']) ?>" class="text-sm font-semibold hover:opacity-80 transition" style="color:<?= $topicAccent ?>"><?= htmlspecialchars($topic['username']) ?></a>
                            <span class="text-xs text-gray-400 dark:text-gray-500"><?= date('M j, Y · g:i a', strtotime($topic['created_at'])) ?></span>
                            <span class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i><?= number_format($topic['views']) ?></span>
                            <span class="text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1"><i data-lucide="message-circle" class="w-3 h-3"></i><?= number_format($topic['reply_count']) ?></span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <?php if ($topic['is_pinned']): ?>
                        <span class="px-2 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-xs font-medium rounded">Pinned</span>
                        <?php endif; ?>
                        <?php if ($topic['is_locked']): ?>
                        <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs font-medium rounded flex items-center gap-1">
                            <i data-lucide="lock" class="w-3 h-3"></i> Locked
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="px-6 pt-4 pb-2">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($topic['title']) ?></h1>
                </div>
            </div>
            <div class="p-6">
                <div class="prose dark:prose-invert max-w-none forum-content forum-lightbox-content" @click="if ($event.target.tagName === 'IMG') { lightboxSrc = $event.target.src; lightboxAlt = $event.target.alt || ''; $event.preventDefault(); $event.stopPropagation(); }">
                    <?= $topic['content'] ?>
                </div>
                
                <?php if (!empty($topicAttachments)): ?>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-dark-600">
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Attachments</h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                        <?php foreach ($topicAttachments as $attachment): ?>
                        <div class="relative group cursor-pointer" @click.prevent="lightboxSrc = <?= htmlspecialchars(json_encode('/uploads/forum/' . $attachment['filename']), ENT_QUOTES, 'UTF-8') ?>; lightboxAlt = <?= htmlspecialchars(json_encode($attachment['original_name']), ENT_QUOTES, 'UTF-8') ?>">
                            <img src="/uploads/forum/<?= htmlspecialchars($attachment['filename']) ?>" 
                                 alt="<?= htmlspecialchars($attachment['original_name']) ?>"
                                 class="w-full h-24 object-cover rounded-lg border border-gray-200 dark:border-dark-600 hover:border-blue-500 dark:hover:border-blue-400 transition">
                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition rounded-lg flex items-center justify-center pointer-events-none">
                                <i data-lucide="expand" class="w-6 h-6 text-white opacity-0 group-hover:opacity-100 transition"></i>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($isLoggedIn && ($topic['user_id'] == $currentUserId || $isAdmin)): ?>
                <div class="flex items-center justify-end gap-2 mt-4 pt-4 border-t border-gray-100 dark:border-dark-600">
                    <a href="/forum/topic/<?= $topic['id'] ?>/edit" class="flex items-center gap-1 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
                        Edit Topic
                    </a>
                    <?php if ($isAdmin): ?>
                    <button onclick="deleteTopic(<?= $topic['id'] ?>)" class="flex items-center gap-1 px-3 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        Delete Topic
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($posts)): ?>
        <div class="space-y-4 mb-6">
            <?php foreach ($posts as $post): ?>
            <?php
            $postAccent = htmlspecialchars($post['profile_accent_color'] ?? '#d4a853');
            $postStyle  = $post['card_style'] ?? 'default';
            $postPanelBg   = $blockThemeBg[$postStyle]   ?? $blockThemeBg['default'];
            $postPanelBord = $blockThemeBorder[$postStyle] ?? $blockThemeBorder['default'];
            ?>
            <div id="post-<?= $post['id'] ?>" class="bg-white dark:bg-dark-800 rounded-2xl shadow-sm border border-gray-100 dark:border-dark-700 overflow-hidden">
                <div class="flex">
                    <!-- Desktop author sidebar -->
                    <div class="w-44 flex-shrink-0 border-r hidden md:flex flex-col items-center text-center p-4 gap-0" style="background:<?= $postPanelBg ?>;border-color:<?= $postPanelBord ?>">
                        <?php forumAuthorCard($post, $featuredCards, $blockThemeBg, $blockThemeBorder, true); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <!-- Post header bar -->
                        <div class="px-4 py-2 bg-gray-50 dark:bg-dark-700 border-b border-gray-100 dark:border-dark-600 flex items-center justify-between">
                            <!-- Mobile: author row -->
                            <div class="flex items-center gap-2 md:hidden">
                                <?php $postAv = \App\Models\User::getAvatarUrl($post); ?>
                                <?php if ($postAv): ?>
                                <img src="<?= htmlspecialchars($postAv) ?>" alt="" class="w-6 h-6 rounded-full object-cover" style="border:2px solid <?= $postAccent ?>">
                                <?php else: ?>
                                <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold" style="background:<?= $postAccent ?>;color:#fff"><?= strtoupper(substr($post['username'],0,1)) ?></div>
                                <?php endif; ?>
                                <a href="/user/<?= htmlspecialchars($post['username']) ?>" class="text-sm font-semibold" style="color:<?= $postAccent ?>"><?= htmlspecialchars($post['username']) ?></a>
                            </div>
                            <span class="text-xs text-gray-500 dark:text-gray-400 hidden md:inline">
                                <?= date('M j, Y · g:i a', strtotime($post['created_at'])) ?>
                                <?php if ($post['edited_at']): ?><span class="italic"> (edited)</span><?php endif; ?>
                            </span>
                            <div class="flex items-center gap-2">
                                <?php if ($isLoggedIn): ?>
                                <button onclick="reactToPost(<?= $post['id'] ?>)" class="flex items-center gap-1 text-sm <?= isset($userReactions[$post['id']]) ? 'text-red-500' : 'text-gray-400 hover:text-red-500' ?> transition">
                                    <i data-lucide="heart" class="w-4 h-4 <?= isset($userReactions[$post['id']]) ? 'fill-current' : '' ?>"></i>
                                    <span id="likes-<?= $post['id'] ?>"><?= $post['likes'] ?></span>
                                </button>
                                <?php endif; ?>
                                <?php if ($post['user_id'] == $currentUserId || $isAdmin): ?>
                                <a href="/forum/post/<?= $post['id'] ?>/edit" class="text-gray-400 hover:text-blue-500 transition">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                <form action="/forum/post/<?= $post['id'] ?>/delete" method="POST" class="inline" onsubmit="return confirm('Delete this post?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="text-gray-400 hover:text-red-500 transition">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="prose dark:prose-invert max-w-none forum-content forum-lightbox-content" @click="if ($event.target.tagName === 'IMG') { lightboxSrc = $event.target.src; lightboxAlt = $event.target.alt || ''; $event.preventDefault(); $event.stopPropagation(); }">
                                <?= $post['content'] ?>
                            </div>
                            
                            <?php if (isset($postAttachments[$post['id']]) && !empty($postAttachments[$post['id']])): ?>
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-dark-600">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Attachments</h4>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                    <?php foreach ($postAttachments[$post['id']] as $attachment): ?>
                                    <div class="relative group cursor-pointer" @click.prevent="lightboxSrc = <?= htmlspecialchars(json_encode('/uploads/forum/' . $attachment['filename']), ENT_QUOTES, 'UTF-8') ?>; lightboxAlt = <?= htmlspecialchars(json_encode($attachment['original_name']), ENT_QUOTES, 'UTF-8') ?>">
                                        <img src="/uploads/forum/<?= htmlspecialchars($attachment['filename']) ?>" 
                                             alt="<?= htmlspecialchars($attachment['original_name']) ?>"
                                             class="w-full h-20 object-cover rounded-lg border border-gray-200 dark:border-dark-600 hover:border-blue-500 dark:hover:border-blue-400 transition">
                                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition rounded-lg flex items-center justify-center pointer-events-none">
                                            <i data-lucide="expand" class="w-5 h-5 text-white opacity-0 group-hover:opacity-100 transition"></i>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center gap-2 mb-6">
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

        <?php if ($isLoggedIn && !$topic['is_locked']): ?>
        <div class="bg-white dark:bg-dark-800 rounded-2xl shadow-sm border border-gray-100 dark:border-dark-700 p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Post a Reply</h3>
            <form action="/forum/<?= htmlspecialchars($topic['category_slug']) ?>/<?= $topic['id'] ?>/reply" method="POST" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div id="editor-container" class="mb-4">
                    <div class="border border-gray-200 dark:border-dark-600 rounded-xl overflow-hidden">
                        <div class="bg-gray-50 dark:bg-dark-700 px-3 py-2 border-b border-gray-200 dark:border-dark-600 flex items-center gap-1 flex-wrap">
                            <button type="button" onclick="formatText('bold')" class="p-2 hover:bg-gray-200 dark:hover:bg-dark-600 rounded transition" title="Bold">
                                <i data-lucide="bold" class="w-4 h-4"></i>
                            </button>
                            <button type="button" onclick="formatText('italic')" class="p-2 hover:bg-gray-200 dark:hover:bg-dark-600 rounded transition" title="Italic">
                                <i data-lucide="italic" class="w-4 h-4"></i>
                            </button>
                            <button type="button" onclick="formatText('underline')" class="p-2 hover:bg-gray-200 dark:hover:bg-dark-600 rounded transition" title="Underline">
                                <i data-lucide="underline" class="w-4 h-4"></i>
                            </button>
                            <div class="w-px h-6 bg-gray-300 dark:bg-dark-500 mx-1"></div>
                            <button type="button" onclick="formatText('insertUnorderedList')" class="p-2 hover:bg-gray-200 dark:hover:bg-dark-600 rounded transition" title="Bullet List">
                                <i data-lucide="list" class="w-4 h-4"></i>
                            </button>
                            <button type="button" onclick="formatText('insertOrderedList')" class="p-2 hover:bg-gray-200 dark:hover:bg-dark-600 rounded transition" title="Numbered List">
                                <i data-lucide="list-ordered" class="w-4 h-4"></i>
                            </button>
                            <div class="w-px h-6 bg-gray-300 dark:bg-dark-500 mx-1"></div>
                            <button type="button" onclick="insertLink()" class="p-2 hover:bg-gray-200 dark:hover:bg-dark-600 rounded transition" title="Insert Link">
                                <i data-lucide="link" class="w-4 h-4"></i>
                            </button>
                            <button type="button" onclick="insertImage()" class="p-2 hover:bg-gray-200 dark:hover:bg-dark-600 rounded transition" title="Insert Image">
                                <i data-lucide="image" class="w-4 h-4"></i>
                            </button>
                            <button type="button" onclick="insertVideo()" class="p-2 hover:bg-gray-200 dark:hover:bg-dark-600 rounded transition" title="Embed Video">
                                <i data-lucide="video" class="w-4 h-4"></i>
                            </button>
                            <div class="w-px h-6 bg-gray-300 dark:bg-dark-500 mx-1"></div>
                            <button type="button" onclick="formatText('formatBlock', '<blockquote>')" class="p-2 hover:bg-gray-200 dark:hover:bg-dark-600 rounded transition" title="Quote">
                                <i data-lucide="quote" class="w-4 h-4"></i>
                            </button>
                            <button type="button" onclick="formatText('formatBlock', '<pre>')" class="p-2 hover:bg-gray-200 dark:hover:bg-dark-600 rounded transition" title="Code Block">
                                <i data-lucide="code" class="w-4 h-4"></i>
                            </button>
                        </div>
                        <div id="editor" contenteditable="true" class="min-h-[150px] p-4 focus:outline-none bg-white dark:bg-dark-800 text-gray-900 dark:text-white" onpaste="handlePaste(event)"></div>
                    </div>
                    <input type="hidden" name="content" id="content-input">
                </div>
                
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 cursor-pointer hover:text-gray-900 dark:hover:text-white transition">
                        <input type="file" name="attachments[]" multiple accept="image/*" class="hidden" onchange="showAttachments(this)">
                        <i data-lucide="paperclip" class="w-4 h-4"></i>
                        Attach Images
                    </label>
                    <button type="submit" onclick="prepareSubmit()" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition">
                        Post Reply
                    </button>
                </div>
                <div id="attachment-preview" class="flex gap-2 mt-3 flex-wrap"></div>
            </form>
        </div>
        <?php elseif ($topic['is_locked']): ?>
        <div class="bg-gray-100 dark:bg-dark-800 rounded-xl p-6 text-center">
            <i data-lucide="lock" class="w-8 h-8 text-gray-400 mx-auto mb-2"></i>
            <p class="text-gray-600 dark:text-gray-400">This topic is locked. No new replies can be posted.</p>
        </div>
        <?php else: ?>
        <div class="bg-gray-100 dark:bg-dark-800 rounded-xl p-6 text-center">
            <p class="text-gray-600 dark:text-gray-400">
                <a href="/login" class="text-blue-600 hover:underline">Log in</a> or 
                <a href="/register" class="text-blue-600 hover:underline">register</a> to reply.
            </p>
        </div>
        <?php endif; ?>

    </div>

    <!-- Lightbox overlay -->
    <div x-show="lightboxSrc" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-4 cursor-pointer"
         @click="lightboxSrc = null"
         title="Cliquer pour fermer (ou Échap)">
        <img :src="lightboxSrc" :alt="lightboxAlt"
             class="max-w-full max-h-full object-contain rounded-lg shadow-2xl"
             @click.stop
             loading="lazy">
    </div>
</div>

<style>
.forum-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 1rem 0; cursor: zoom-in; }
.forum-content iframe { max-width: 100%; border-radius: 8px; margin: 1rem 0; }
.forum-content blockquote { border-left: 4px solid #3B82F6; padding-left: 1rem; margin: 1rem 0; font-style: italic; color: #6B7280; }
.forum-content pre { background: #1F2937; color: #E5E7EB; padding: 1rem; border-radius: 8px; overflow-x: auto; }
.forum-content a { color: #3B82F6; text-decoration: underline; }
#editor:empty:before { content: 'Write your reply...'; color: #9CA3AF; }
.forum-author-card { display:flex; flex-direction:column; align-items:center; text-align:center; padding:12px 10px; }
.badge-coin-inner svg { border-radius:50%; display:block; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());

function formatText(cmd, value = null) {
    document.execCommand(cmd, false, value);
    document.getElementById('editor').focus();
}

function insertLink() {
    const url = prompt('Enter URL:');
    if (url) {
        document.execCommand('createLink', false, url);
    }
}

function insertImage() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = (e) => {
        if (e.target.files && e.target.files[0]) uploadImage(e.target.files[0]);
    };
    input.click();
}

function insertVideo() {
    const url = prompt('Enter YouTube or Vimeo URL:');
    if (!url) return;
    
    let embedUrl = '';
    if (url.includes('youtube.com/watch')) {
        const id = new URL(url).searchParams.get('v');
        embedUrl = `https://www.youtube.com/embed/${id}`;
    } else if (url.includes('youtu.be/')) {
        const id = url.split('youtu.be/')[1].split('?')[0];
        embedUrl = `https://www.youtube.com/embed/${id}`;
    } else if (url.includes('vimeo.com/')) {
        const id = url.split('vimeo.com/')[1].split('?')[0];
        embedUrl = `https://player.vimeo.com/video/${id}`;
    }
    
    if (embedUrl) {
        const iframe = `<iframe width="560" height="315" src="${embedUrl}" frameborder="0" allowfullscreen></iframe>`;
        document.execCommand('insertHTML', false, iframe);
    } else {
        alert('Please enter a valid YouTube or Vimeo URL');
    }
}

function handlePaste(e) {
    const items = e.clipboardData.items;
    for (let item of items) {
        if (item.type.indexOf('image') !== -1) {
            e.preventDefault();
            const file = item.getAsFile();
            uploadImage(file);
            return;
        }
    }
}

function uploadImage(file) {
    const formData = new FormData();
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) formData.append('csrf_token', token);
    formData.append('image', file);
    
    fetch('/forum/upload-image', { method: 'POST', body: formData })
    .then(r => {
        if (!r.ok) {
            throw new Error(r.status === 403 ? 'Session expired. Please refresh and try again.' : 'Upload failed (' + r.status + ')');
        }
        return r.json();
    })
    .then(data => {
        if (data.success) {
            document.execCommand('insertImage', false, data.url);
            document.getElementById('editor')?.focus();
        } else {
            alert(data.error || 'Upload failed');
        }
    })
    .catch(err => alert(err.message || 'Upload failed'));
}

function showAttachments(input) {
    const preview = document.getElementById('attachment-preview');
    preview.innerHTML = '';
    for (let file of input.files) {
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.innerHTML += `<img src="${e.target.result}" class="w-20 h-20 object-cover rounded-lg">`;
        };
        reader.readAsDataURL(file);
    }
}

function prepareSubmit() {
    document.getElementById('content-input').value = document.getElementById('editor').innerHTML;
}

function reactToPost(postId) {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const body = `post_id=${postId}&reaction=like` + (token ? `&csrf_token=${encodeURIComponent(token)}` : '');
    fetch('/forum/react', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const el = document.getElementById('likes-' + postId);
            el.textContent = parseInt(el.textContent) + (data.action === 'added' ? 1 : -1);
        }
    });
}

function deleteTopic(topicId) {
    if (confirm('Are you sure you want to delete this entire topic? This action cannot be undone.')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = `/forum/topic/${topicId}/delete`;
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'csrf_token';
            input.value = token;
            form.appendChild(input);
        }
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
