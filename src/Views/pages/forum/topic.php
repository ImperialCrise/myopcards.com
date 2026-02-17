<?php
$isLoggedIn = \App\Core\Auth::check();
$currentUserId = \App\Core\Auth::id();
$isAdmin = \App\Core\Auth::isAdmin();
?>

<div class="min-h-screen bg-gray-50 dark:bg-dark-900 py-8">
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

        <div class="bg-white dark:bg-dark-800 rounded-2xl shadow-sm border border-gray-100 dark:border-dark-700 overflow-hidden mb-6">
            <div class="p-6 border-b border-gray-100 dark:border-dark-700">
                <div class="flex items-start gap-4">
                    <?php if ($topic['avatar']): ?>
                    <img src="<?= htmlspecialchars($topic['avatar']) ?>" alt="" class="w-12 h-12 rounded-full flex-shrink-0">
                    <?php else: ?>
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-xl font-bold flex-shrink-0">
                        <?= strtoupper(substr($topic['username'], 0, 1)) ?>
                    </div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-2">
                            <?php if ($topic['is_pinned']): ?>
                            <span class="px-2 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-xs font-medium rounded">Pinned</span>
                            <?php endif; ?>
                            <?php if ($topic['is_locked']): ?>
                            <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs font-medium rounded flex items-center gap-1">
                                <i data-lucide="lock" class="w-3 h-3"></i> Locked
                            </span>
                            <?php endif; ?>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($topic['title']) ?></h1>
                        <div class="flex items-center gap-4 mt-2 text-sm text-gray-500 dark:text-gray-400">
                            <a href="/user/<?= htmlspecialchars($topic['username']) ?>" class="hover:text-blue-600 transition">
                                <?= htmlspecialchars($topic['username']) ?>
                            </a>
                            <span>&middot;</span>
                            <span><?= date('F j, Y \a\t g:i a', strtotime($topic['created_at'])) ?></span>
                            <span>&middot;</span>
                            <span class="flex items-center gap-1"><i data-lucide="eye" class="w-4 h-4"></i> <?= number_format($topic['views']) ?> views</span>
                            <span>&middot;</span>
                            <span class="flex items-center gap-1"><i data-lucide="message-circle" class="w-4 h-4"></i> <?= number_format($topic['reply_count']) ?> replies</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="prose dark:prose-invert max-w-none forum-content">
                    <?= $topic['content'] ?>
                </div>
            </div>
        </div>

        <?php if (!empty($posts)): ?>
        <div class="space-y-4 mb-6">
            <?php foreach ($posts as $post): ?>
            <div id="post-<?= $post['id'] ?>" class="bg-white dark:bg-dark-800 rounded-2xl shadow-sm border border-gray-100 dark:border-dark-700 overflow-hidden">
                <div class="flex">
                    <div class="w-48 flex-shrink-0 bg-gray-50 dark:bg-dark-700 p-4 text-center border-r border-gray-100 dark:border-dark-600 hidden md:block">
                        <a href="/user/<?= htmlspecialchars($post['username']) ?>">
                            <?php if ($post['avatar']): ?>
                            <img src="<?= htmlspecialchars($post['avatar']) ?>" alt="" class="w-16 h-16 rounded-full mx-auto mb-2">
                            <?php else: ?>
                            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white text-2xl font-bold mx-auto mb-2">
                                <?= strtoupper(substr($post['username'], 0, 1)) ?>
                            </div>
                            <?php endif; ?>
                            <p class="font-semibold text-gray-900 dark:text-white hover:text-blue-600 transition"><?= htmlspecialchars($post['username']) ?></p>
                        </a>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><?= number_format($post['user_post_count']) ?> posts</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Joined <?= date('M Y', strtotime($post['user_joined'])) ?></p>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="px-4 py-2 bg-gray-50 dark:bg-dark-700 border-b border-gray-100 dark:border-dark-600 flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                <?= date('F j, Y \a\t g:i a', strtotime($post['created_at'])) ?>
                                <?php if ($post['edited_at']): ?>
                                <span class="italic">(edited)</span>
                                <?php endif; ?>
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
                                    <button type="submit" class="text-gray-400 hover:text-red-500 transition">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="p-4 md:hidden flex items-center gap-3 border-b border-gray-100 dark:border-dark-600">
                            <?php if ($post['avatar']): ?>
                            <img src="<?= htmlspecialchars($post['avatar']) ?>" alt="" class="w-8 h-8 rounded-full">
                            <?php else: ?>
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold">
                                <?= strtoupper(substr($post['username'], 0, 1)) ?>
                            </div>
                            <?php endif; ?>
                            <a href="/user/<?= htmlspecialchars($post['username']) ?>" class="font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($post['username']) ?></a>
                        </div>
                        <div class="p-4">
                            <div class="prose dark:prose-invert max-w-none forum-content">
                                <?= $post['content'] ?>
                            </div>
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
</div>

<style>
.forum-content img { max-width: 100%; height: auto; border-radius: 8px; margin: 1rem 0; }
.forum-content iframe { max-width: 100%; border-radius: 8px; margin: 1rem 0; }
.forum-content blockquote { border-left: 4px solid #3B82F6; padding-left: 1rem; margin: 1rem 0; font-style: italic; color: #6B7280; }
.forum-content pre { background: #1F2937; color: #E5E7EB; padding: 1rem; border-radius: 8px; overflow-x: auto; }
.forum-content a { color: #3B82F6; text-decoration: underline; }
#editor:empty:before { content: 'Write your reply...'; color: #9CA3AF; }
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
    const url = prompt('Enter image URL (or upload below):');
    if (url) {
        document.execCommand('insertImage', false, url);
    }
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
    formData.append('image', file);
    
    fetch('/forum/upload-image', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.execCommand('insertImage', false, data.url);
        } else {
            alert(data.error || 'Upload failed');
        }
    });
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
    fetch('/forum/react', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `post_id=${postId}&reaction=like`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const el = document.getElementById('likes-' + postId);
            el.textContent = parseInt(el.textContent) + (data.action === 'added' ? 1 : -1);
        }
    });
}
</script>
