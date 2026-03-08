<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="mb-8">
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="/admin" class="hover:text-gray-900 transition">Admin</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <a href="/admin/forum-categories" class="hover:text-gray-900 transition">Forum Categories</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900">Edit Category</span>
        </nav>
        
        <h1 class="text-3xl font-bold text-gray-900">Edit Forum Category</h1>
        <p class="text-gray-600 mt-2">Update category settings and information</p>
    </div>

    <?php if (isset($_SESSION['admin_error'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm flex items-center gap-2 mb-6">
        <i data-lucide="alert-circle" class="w-4 h-4"></i> 
        <?= htmlspecialchars($_SESSION['admin_error']) ?>
    </div>
    <?php unset($_SESSION['admin_error']); endif; ?>

    <div class="bg-white rounded-lg shadow p-6">
        <form method="POST" action="/admin/forum-categories/<?= $category['id'] ?>/edit" class="space-y-6">
            <?= csrf_field() ?>
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Category Name *</label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       required 
                       class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                       placeholder="e.g. General Discussion"
                       value="<?= htmlspecialchars($_POST['name'] ?? $category['name']) ?>">
                <p class="text-sm text-gray-500 mt-1">The display name for this category</p>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" 
                          id="description" 
                          rows="3" 
                          class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                          placeholder="Brief description of what this category is for..."><?= htmlspecialchars($_POST['description'] ?? $category['description'] ?? '') ?></textarea>
                <p class="text-sm text-gray-500 mt-1">Optional description to help users understand the category's purpose</p>
            </div>

            <div>
                <label for="sort_order" class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                <input type="number" 
                       name="sort_order" 
                       id="sort_order" 
                       min="0"
                       class="w-full px-4 py-3 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                       placeholder="0"
                       value="<?= htmlspecialchars($_POST['sort_order'] ?? $category['sort_order'] ?? '0') ?>">
                <p class="text-sm text-gray-500 mt-1">Lower numbers appear first. Use 0 for default ordering.</p>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-medium text-gray-900 mb-2">Current URL Slug</h3>
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-gray-500">https://myopcards.com/forum/</span>
                    <span class="font-mono text-blue-600"><?= htmlspecialchars($category['slug']) ?></span>
                </div>
                <p class="text-xs text-gray-500 mt-1">The slug will be updated if you change the category name</p>
            </div>

            <div class="bg-blue-50 rounded-lg p-4">
                <h3 class="font-medium text-gray-900 mb-2 flex items-center gap-2">
                    <i data-lucide="info" class="w-4 h-4 text-blue-500"></i>
                    Category Statistics
                </h3>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Topics:</span>
                        <span class="font-semibold ml-2">0</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Posts:</span>
                        <span class="font-semibold ml-2">0</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200">
                <a href="/admin/forum-categories" class="px-6 py-2.5 text-gray-700 hover:bg-gray-100 rounded-lg transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
                    Update Category
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>