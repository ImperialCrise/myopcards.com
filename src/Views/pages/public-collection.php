<?php $cards = $result['cards'] ?? []; $collectionUser = $owner ?? []; ?>
<div class="space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="/user/<?= htmlspecialchars($collectionUser['username'] ?? '') ?>" class="text-gold-400 hover:text-gold-300 transition">
                    <i data-lucide="arrow-left" class="w-4 h-4 inline"></i> <?= htmlspecialchars($collectionUser['username'] ?? '') ?>
                </a>
            </div>
            <h1 class="text-2xl font-display font-bold text-white mt-1"><?= htmlspecialchars($collectionUser['username'] ?? '') ?>'s Collection</h1>
            <p class="text-sm text-dark-400 mt-1"><?= number_format($result['total'] ?? 0) ?> unique cards</p>
        </div>
    </div>

    <?php if (!empty($cards)): ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
        <?php foreach ($cards as $card): ?>
            <a href="/cards/<?= urlencode($card['card_set_id']) ?>" class="group card-hover">
                <div class="glass rounded-xl overflow-hidden">
                    <div class="relative aspect-[5/7] bg-dark-700">
                        <img src="<?= htmlspecialchars($card['card_image_url'] ?? '') ?>" alt="" class="w-full h-full object-cover" loading="lazy" onerror="this.parentElement.classList.add('skeleton');this.style.display='none'">
                        <span class="absolute top-1.5 right-1.5 px-2 py-0.5 bg-dark-900/80 text-white text-xs font-bold rounded-full"><?= $card['quantity'] ?>x</span>
                    </div>
                    <div class="p-2">
                        <p class="text-xs font-bold text-white truncate"><?= htmlspecialchars($card['card_name']) ?></p>
                        <p class="text-[10px] text-dark-400"><?= htmlspecialchars($card['card_set_id']) ?></p>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <div class="text-center py-16">
            <div class="w-16 h-16 rounded-2xl bg-dark-700/50 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="package-open" class="w-8 h-8 text-dark-400"></i>
            </div>
            <h3 class="text-lg font-display font-bold text-dark-300">Empty collection</h3>
            <p class="text-sm text-dark-400 mt-2">This user hasn't added any cards yet.</p>
        </div>
    <?php endif; ?>
</div>
