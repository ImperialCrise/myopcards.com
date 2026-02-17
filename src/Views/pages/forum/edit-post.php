<div class="min-h-screen bg-gray-50 dark:bg-dark-900 py-8">
    <div class="max-w-4xl mx-auto px-4">

        <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
            <a href="/forum" class="hover:text-gray-900 dark:hover:text-white transition">Forum</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <a href="/forum/<?= htmlspecialchars($post['category_slug']) ?>" class="hover:text-gray-900 dark:hover:text-white transition"><?= htmlspecialchars($post['category_slug']) ?></a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 dark:text-white">Edit Post</span>
        </nav>

        <?php if (isset($_SESSION['forum_error'])): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-6">
            <p class="text-red-800 dark:text-red-200"><?= htmlspecialchars($_SESSION['forum_error']) ?></p>
        </div>
        <?php unset($_SESSION['forum_error']); endif; ?>

        <div class="bg-white dark:bg-dark-800 rounded-2xl shadow-sm border border-gray-100 dark:border-dark-700 p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Edit Post</h1>

            <form action="/forum/post/<?= $post['id'] ?>/edit" method="POST">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Content</label>
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
                            <button type="button" onclick="insertLink()" class="p-2 hover:bg-gray-200 dark:hover:bg-dark-600 rounded transition" title="Insert Link">
                                <i data-lucide="link" class="w-4 h-4"></i>
                            </button>
                            <button type="button" onclick="insertImage()" class="p-2 hover:bg-gray-200 dark:hover:bg-dark-600 rounded transition" title="Insert Image">
                                <i data-lucide="image" class="w-4 h-4"></i>
                            </button>
                            <button type="button" onclick="insertVideo()" class="p-2 hover:bg-gray-200 dark:hover:bg-dark-600 rounded transition" title="Embed Video">
                                <i data-lucide="video" class="w-4 h-4"></i>
                            </button>
                        </div>
                        <div id="editor" contenteditable="true" class="min-h-[200px] p-4 focus:outline-none bg-white dark:bg-dark-800 text-gray-900 dark:text-white" onpaste="handlePaste(event)"><?= $post['content'] ?></div>
                    </div>
                    <input type="hidden" name="content" id="content-input">
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="/forum/<?= htmlspecialchars($post['category_slug']) ?>/<?= $post['topic_id'] ?>-<?= htmlspecialchars($post['topic_slug']) ?>" class="px-6 py-2.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-dark-700 rounded-xl transition">
                        Cancel
                    </a>
                    <button type="submit" onclick="prepareSubmit()" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<style>
#editor:empty:before { content: 'Edit your post content here...'; color: #9CA3AF; }
#editor img { max-width: 100%; height: auto; border-radius: 8px; margin: 1rem 0; }
#editor iframe { max-width: 100%; border-radius: 8px; margin: 1rem 0; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());

function formatText(cmd, value = null) {
    document.execCommand(cmd, false, value);
    document.getElementById('editor').focus();
}

function insertLink() {
    const url = prompt('Enter URL:');
    if (url) document.execCommand('createLink', false, url);
}

function insertImage() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.onchange = (e) => {
        if (e.target.files[0]) uploadImage(e.target.files[0]);
    };
    input.click();
}

function insertVideo() {
    const url = prompt('Enter YouTube or Vimeo URL:');
    if (!url) return;
    let embedUrl = '';
    if (url.includes('youtube.com/watch')) {
        embedUrl = `https://www.youtube.com/embed/${new URL(url).searchParams.get('v')}`;
    } else if (url.includes('youtu.be/')) {
        embedUrl = `https://www.youtube.com/embed/${url.split('youtu.be/')[1].split('?')[0]}`;
    } else if (url.includes('vimeo.com/')) {
        embedUrl = `https://player.vimeo.com/video/${url.split('vimeo.com/')[1].split('?')[0]}`;
    }
    if (embedUrl) {
        document.execCommand('insertHTML', false, `<iframe width="560" height="315" src="${embedUrl}" frameborder="0" allowfullscreen></iframe>`);
    }
}

function handlePaste(e) {
    const items = (e.clipboardData || e.originalEvent.clipboardData).items;
    for (let item of items) {
        if (item.type.indexOf('image') !== -1) {
            e.preventDefault();
            uploadImage(item.getAsFile());
            return;
        }
    }
}

function uploadImage(file) {
    const formData = new FormData();
    formData.append('image', file);
    fetch('/forum/upload-image', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) document.execCommand('insertImage', false, data.url);
            else alert(data.error || 'Upload failed');
        });
}

function prepareSubmit() {
    document.getElementById('content-input').value = document.getElementById('editor').innerHTML;
}
</script>
