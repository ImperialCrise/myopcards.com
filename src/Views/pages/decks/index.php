<?php
$decks = $decks ?? [];
?>
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-display font-bold text-white">My Decks</h1>
            <p class="text-sm text-dark-400 mt-1">Build and manage decks for online play</p>
        </div>
        <a href="/decks/create" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gold-500 text-dark-900 rounded-lg font-semibold hover:bg-gold-400 transition">
            <i data-lucide="plus" class="w-4 h-4"></i> New Deck
        </a>
    </div>

    <?php if (empty($decks)): ?>
    <div class="glass rounded-2xl p-12 text-center">
        <div class="w-16 h-16 rounded-2xl bg-dark-700 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="layers" class="w-8 h-8 text-dark-400"></i>
        </div>
        <h2 class="text-lg font-display font-bold text-white mb-2">No decks yet</h2>
        <p class="text-dark-400 text-sm mb-6 max-w-sm mx-auto">Create a deck with 1 Leader and 50 cards to play online.</p>
        <a href="/decks/create" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gold-500 text-dark-900 rounded-lg font-semibold hover:bg-gold-400 transition">
            <i data-lucide="plus" class="w-4 h-4"></i> Create your first deck
        </a>
    </div>
    <?php else: ?>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($decks as $d): ?>
        <div class="glass rounded-2xl overflow-hidden hover:border-dark-500 transition">
            <div class="flex gap-4 p-5">
                <div class="flex-shrink-0">
                    <?php if (!empty($d['leader_image_url'])): ?>
                    <img src="<?= htmlspecialchars($d['leader_image_url']) ?>" alt="" class="w-14 h-20 object-cover rounded-lg border border-dark-600">
                    <?php else: ?>
                    <div class="w-14 h-20 rounded-lg bg-dark-700 border border-dark-600 flex items-center justify-center">
                        <i data-lucide="image" class="w-6 h-6 text-dark-500"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="font-display font-bold text-white truncate"><?= htmlspecialchars($d['name']) ?></h2>
                    <p class="text-xs text-dark-400 mt-0.5"><?= htmlspecialchars($d['leader_name'] ?? '') ?></p>
                    <p class="text-xs text-dark-500 mt-1"><?= htmlspecialchars($d['leader_color'] ?? '') ?></p>
                </div>
            </div>
            <div class="flex border-t border-dark-700">
                <a href="/decks/<?= (int)$d['id'] ?>/edit" class="flex-1 flex items-center justify-center gap-2 py-3 text-sm text-gold-400 hover:bg-dark-800/50 transition">
                    <i data-lucide="pencil" class="w-4 h-4"></i> Edit
                </a>
                <a href="/play" class="flex-1 flex items-center justify-center gap-2 py-3 text-sm text-dark-300 hover:bg-dark-800/50 hover:text-white transition">
                    <i data-lucide="gamepad-2" class="w-4 h-4"></i> Play
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
