<div class="min-h-screen bg-gray-50 dark:bg-dark-900 py-8">
    <div class="max-w-4xl mx-auto px-4">

        <nav class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-6">
            <a href="/forum" class="hover:text-gray-900 dark:hover:text-white transition">Forum</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <a href="/forum/<?= htmlspecialchars($category['slug']) ?>" class="hover:text-gray-900 dark:hover:text-white transition"><?= htmlspecialchars($category['name']) ?></a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900 dark:text-white">New Topic</span>
        </nav>

        <?php if (isset($_SESSION['forum_error'])): ?>
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 mb-6">
            <p class="text-red-800 dark:text-red-200"><?= htmlspecialchars($_SESSION['forum_error']) ?></p>
        </div>
        <?php unset($_SESSION['forum_error']); endif; ?>

        <div class="bg-white dark:bg-dark-800 rounded-2xl shadow-sm border border-gray-100 dark:border-dark-700 p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Create New Topic</h1>

            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4 mb-6">
                <h3 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">Posting Guidelines</h3>
                <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                    <li>&bull; Use a clear, descriptive title</li>
                    <li>&bull; Be respectful and constructive</li>
                    <li>&bull; No profanity, hate speech, or personal attacks</li>
                    <li>&bull; Search before posting to avoid duplicates</li>
                    <li>&bull; You can include images and videos in your post</li>
                </ul>
            </div>

            <form action="/forum/<?= htmlspecialchars($category['slug']) ?>/create" method="POST" enctype="multipart/form-data">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Topic Title</label>
                    <input type="text" name="title" required minlength="5" maxlength="255" 
                           class="w-full px-4 py-3 border border-gray-200 dark:border-dark-600 rounded-xl bg-white dark:bg-dark-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                           placeholder="Enter a descriptive title for your topic...">
                </div>

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
                            <button type="button" onclick="formatText('strikeThrough')" class="p-2 hover:bg-gray-200 dark:hover:bg-dark-600 rounded transition" title="Strikethrough">
                                <i data-lucide="strikethrough" class="w-4 h-4"></i>
                            </button>
                            <div class="w-px h-6 bg-gray-300 dark:bg-dark-500 mx-1"></div>
                            <button type="button" onclick="formatText('formatBlock', '<h3>')" class="p-2 hover:bg-gray-200 dark:hover:bg-dark-600 rounded transition" title="Heading">
                                <i data-lucide="heading" class="w-4 h-4"></i>
                            </button>
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
                        <div id="editor" contenteditable="true" class="min-h-[300px] p-4 focus:outline-none bg-white dark:bg-dark-800 text-gray-900 dark:text-white" onpaste="handlePaste(event)"></div>
                    </div>
                    <input type="hidden" name="content" id="content-input">
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Attachments (optional)</label>
                    <label class="flex items-center justify-center gap-2 px-4 py-8 border-2 border-dashed border-gray-300 dark:border-dark-600 rounded-xl cursor-pointer hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/10 transition">
                        <input type="file" name="attachments[]" multiple accept="image/*" class="hidden" onchange="showAttachments(this)">
                        <i data-lucide="upload-cloud" class="w-6 h-6 text-gray-400"></i>
                        <span class="text-gray-600 dark:text-gray-400">Drop images here or click to upload (max 5MB each)</span>
                    </label>
                    <div id="attachment-preview" class="flex gap-2 mt-3 flex-wrap"></div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a href="/forum/<?= htmlspecialchars($category['slug']) ?>" class="px-6 py-2.5 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-dark-700 rounded-xl transition">
                        Cancel
                    </a>
                    <button type="submit" onclick="prepareSubmit()" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition">
                        Create Topic
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<style>
#editor:empty:before { content: 'Write your topic content here... You can use formatting, add images, embed videos, and more.'; color: #9CA3AF; }
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
    const url = prompt('Enter image URL (or use the upload button below):');
    if (url) document.execCommand('insertImage', false, url);
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
    } else {
        alert('Please enter a valid YouTube or Vimeo URL');
    }
}

function handlePaste(e) {
    for (let item of e.clipboardData.items) {
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
</script>
