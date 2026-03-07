<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Forum Categories</h1>
            <p class="text-gray-600 mt-2">Manage forum categories and their settings</p>
        </div>
        <a href="/admin/forum-categories/create" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition">
            <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
            Create Category
        </a>
    </div>

    <?php if (isset($_SESSION['admin_error'])): ?>
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm flex items-center gap-2 mb-6">
        <i data-lucide="alert-circle" class="w-4 h-4"></i> 
        <?= htmlspecialchars($_SESSION['admin_error']) ?>
    </div>
    <?php unset($_SESSION['admin_error']); endif; ?>

    <?php if (isset($_SESSION['admin_success'])): ?>
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm flex items-center gap-2 mb-6">
        <i data-lucide="check-circle" class="w-4 h-4"></i> 
        <?= htmlspecialchars($_SESSION['admin_success']) ?>
    </div>
    <?php unset($_SESSION['admin_success']); endif; ?>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Topics</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posts</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i data-lucide="folder-open" class="w-12 h-12 mx-auto mb-4 text-gray-300"></i>
                            <p class="text-lg font-medium">No categories found</p>
                            <p class="text-sm">Create your first forum category to get started.</p>
                            <a href="/admin/forum-categories/create" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition mt-4">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                Create Category
                            </a>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($category['name']) ?></div>
                                <?php if ($category['description']): ?>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($category['description']) ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-mono text-gray-600 bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($category['slug']) ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?= $category['sort_order'] ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                <?= number_format($category['topic_count'] ?? 0) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                <?= number_format($category['post_count'] ?? 0) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?= date('M j, Y', strtotime($category['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a href="/admin/forum-categories/<?= $category['id'] ?>/edit" class="text-indigo-600 hover:text-indigo-900 transition">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                <?php if (($category['topic_count'] ?? 0) == 0): ?>
                                <form action="/admin/forum-categories/<?= $category['id'] ?>/delete" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this category?')">
                                    <button type="submit" class="text-red-600 hover:text-red-900 transition">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-gray-300" title="Cannot delete category with topics">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => lucide.createIcons());
</script>