<?php if (isset($_GET['updated'])): ?>
    <div class="bg-green-500/10 border border-green-500/30 text-green-600 px-4 py-3 rounded-lg text-sm flex items-center gap-2 mb-6">
        <i data-lucide="check-circle" class="w-4 h-4"></i> <?= t('profile.updated') ?>
    </div>
<?php endif; ?>

<div class="flex flex-col lg:flex-row gap-6">

    <!-- Main Content -->
    <div class="flex-1 min-w-0 space-y-6">

        <!-- Profile Card -->
        <div class="glass rounded-2xl p-8">
            <div class="flex items-start gap-6">
                <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-gold-500 to-amber-600 flex items-center justify-center text-3xl font-display font-bold flex-shrink-0" style="color:#fff">
                    <?php if ($user['avatar']): ?>
                        <img src="<?= htmlspecialchars($user['avatar']) ?>" class="w-full h-full rounded-2xl object-cover" alt="">
                    <?php else: ?>
                        <?= strtoupper(substr($user['username'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl font-display font-bold text-gray-900"><?= htmlspecialchars($user['username']) ?></h1>
                    <p class="text-sm text-gray-400 mt-1"><?= htmlspecialchars($user['email']) ?></p>
                    <p class="text-sm text-gray-400"><?= t('profile.joined') ?> <?= date('M Y', strtotime($user['created_at'])) ?></p>
                    <?php if ($user['is_public'] ?? false): ?>
                        <div class="flex items-center gap-2 mt-3" x-data="{ copied: false }">
                            <a href="/user/<?= htmlspecialchars($user['username']) ?>" class="text-xs text-gold-400 hover:text-gold-300 transition flex items-center gap-1">
                                <i data-lucide="external-link" class="w-3 h-3"></i> <?= t('profile.public_profile') ?>
                            </a>
                            <span class="text-gray-300">|</span>
                            <button @click="navigator.clipboard.writeText('<?= ($_ENV['APP_URL'] ?? 'https://myopcards.com') ?>/user/<?= htmlspecialchars($user['username']) ?>'); copied = true; setTimeout(() => copied = false, 2000)"
                                class="text-xs text-gray-400 hover:text-gold-400 transition flex items-center gap-1">
                                <i data-lucide="copy" class="w-3 h-3"></i> <span x-text="copied ? '<?= htmlspecialchars(t('profile.copied')) ?>' : '<?= htmlspecialchars(t('profile.copy_link')) ?>'"></span>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Featured Card -->
        <?php if ($featuredCard): ?>
        <div class="glass rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <i data-lucide="star" class="w-6 h-6 text-yellow-400 fill-current"></i>
                <h2 class="text-xl font-display font-bold text-gray-900"><?= t('profile.featured_card') ?></h2>
            </div>
            <div class="flex items-center gap-6 p-4 bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-900/20 dark:to-amber-900/20 rounded-xl border border-yellow-200/50 dark:border-yellow-600/30">
                <div class="relative flex-shrink-0">
                    <img src="<?= htmlspecialchars($featuredCard['card_image_url']) ?>" 
                         alt="<?= htmlspecialchars($featuredCard['card_name']) ?>"
                         class="w-20 h-28 object-cover rounded-lg shadow-lg border-2 border-yellow-300/50 dark:border-yellow-500/50">
                    <div class="absolute -top-2 -right-2 w-8 h-8 bg-gradient-to-br from-yellow-400 to-amber-500 rounded-full flex items-center justify-center shadow-lg">
                        <i data-lucide="star" class="w-4 h-4 text-white fill-current"></i>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 mb-1"><?= htmlspecialchars($featuredCard['card_name']) ?></h3>
                    <p class="text-sm text-gray-600 dark:text-gray-300 mb-2"><?= htmlspecialchars($featuredCard['card_set_id']) ?> • <?= htmlspecialchars($featuredCard['set_name'] ?? t('profile.unknown_set')) ?></p>
                    
                    <div class="flex items-center gap-4 text-xs">
                        <?php if ($featuredCard['rarity']): ?>
                        <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full font-medium">
                            <?= htmlspecialchars($featuredCard['rarity']) ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($featuredCard['card_color']): ?>
                        <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full font-medium">
                            <?= htmlspecialchars($featuredCard['card_color']) ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($featuredCard['market_price']): ?>
                        <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full font-bold">
                            $<?= number_format((float)$featuredCard['market_price'], 2) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex-shrink-0">
                    <a href="/cards/<?= htmlspecialchars($featuredCard['card_set_id']) ?>" 
                       class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-400 hover:bg-yellow-500 dark:bg-yellow-600 dark:hover:bg-yellow-500 text-yellow-900 dark:text-yellow-100 font-medium rounded-lg transition">
                        <i data-lucide="external-link" class="w-4 h-4"></i>
                        <?= t('profile.view_card') ?>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="grid grid-cols-3 sm:grid-cols-5 gap-3">
            <div class="glass rounded-xl p-4 text-center">
                <p class="text-xl font-display font-bold text-gray-900"><?= number_format($stats['unique_cards'] ?? 0) ?></p>
                <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-wider"><?= t('profile.cards') ?></p>
            </div>
            <div class="glass rounded-xl p-4 text-center">
                <p class="text-xl font-display font-bold text-gray-900"><?= \App\Core\Currency::format((float)($stats['total_value'] ?? 0)) ?></p>
                <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-wider"><?= t('profile.value') ?><?= \App\Core\Currency::label() ?>)</p>
            </div>
            <div class="glass rounded-xl p-4 text-center">
                <p class="text-xl font-display font-bold text-gray-900"><?= $friendCount ?></p>
                <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-wider"><?= t('profile.friends') ?></p>
            </div>
            <div class="glass rounded-xl p-4 text-center">
                <p class="text-xl font-display font-bold text-gray-900"><?= number_format($viewCounts['profile'] ?? 0) ?></p>
                <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-wider flex items-center justify-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i> <?= t('profile.profile') ?></p>
            </div>
            <div class="glass rounded-xl p-4 text-center">
                <p class="text-xl font-display font-bold text-gray-900"><?= number_format($viewCounts['collection'] ?? 0) ?></p>
                <p class="text-[10px] text-gray-400 mt-1 uppercase tracking-wider flex items-center justify-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i> <?= t('profile.collection') ?></p>
            </div>
        </div>

        <?php if (!empty($recentViewers)): ?>
        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-gray-900 flex items-center gap-2 mb-4">
                <i data-lucide="users" class="w-5 h-5 text-gold-400"></i> <?= t('profile.recent_visitors') ?>
            </h2>
            <div class="space-y-3">
                <?php foreach ($recentViewers as $v): ?>
                    <div class="flex items-center gap-3">
                        <?php if (!empty($v['avatar'])): ?>
                            <img src="<?= htmlspecialchars($v['avatar']) ?>" class="w-8 h-8 rounded-full" alt="">
                        <?php else: ?>
                            <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600"><?= strtoupper(substr($v['username'] ?? '?', 0, 1)) ?></div>
                        <?php endif; ?>
                        <div class="flex-1 min-w-0">
                            <a href="/user/<?= htmlspecialchars($v['username'] ?? '') ?>" class="text-sm font-medium text-gray-900 hover:text-gold-400 transition"><?= htmlspecialchars($v['username'] ?? t('profile.anonymous')) ?></a>
                            <p class="text-[10px] text-gray-400"><?= t('profile.viewed_your') ?> <?= $v['page_type'] ?></p>
                        </div>
                        <span class="text-[10px] text-gray-400"><?= date('M j, H:i', strtotime($v['viewed_at'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Forum Activity -->
        <?php if (!empty($recentActivity)): ?>
        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-gray-900 flex items-center gap-2 mb-4">
                <i data-lucide="message-square" class="w-5 h-5 text-gold-400"></i> <?= t('profile.recent_forum') ?>
            </h2>
            <div class="space-y-3">
                <?php foreach ($recentActivity as $activity): ?>
                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold <?= $activity['type'] === 'topic' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600' ?>">
                        <?php if ($activity['type'] === 'topic'): ?>
                            <i data-lucide="plus" class="w-4 h-4"></i>
                        <?php else: ?>
                            <i data-lucide="message-circle" class="w-4 h-4"></i>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-xs font-medium px-2 py-0.5 rounded <?= $activity['type'] === 'topic' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' ?>">
                                <?= $activity['type'] === 'topic' ? t('profile.created_topic') : t('profile.replied_to') ?>
                            </span>
                            <span class="text-xs text-gray-500"><?= date('M j, H:i', strtotime($activity['created_at'])) ?></span>
                        </div>
                        <a href="/forum/<?= htmlspecialchars($activity['category_slug']) ?>/<?= $activity['type'] === 'topic' ? $activity['id'] : $activity['topic_id'] ?>-<?= htmlspecialchars($activity['slug']) ?>" 
                           class="text-sm font-medium text-gray-900 hover:text-blue-600 transition line-clamp-1">
                            <?= htmlspecialchars($activity['title']) ?>
                        </a>
                    </div>
                    <i data-lucide="chevron-right" class="w-4 h-4 text-gray-400"></i>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-200">
                <a href="/forum" class="text-sm text-gold-400 hover:text-gold-500 transition flex items-center gap-2">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    <?= t('profile.visit_forum') ?>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- Edit Profile -->
        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-gray-900 flex items-center gap-2 mb-4">
                <i data-lucide="edit" class="w-5 h-5 text-gold-400"></i> <?= t('profile.edit') ?>
            </h2>
            <form method="POST" action="/profile/update" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1"><?= t('profile.bio') ?></label>
                    <textarea name="bio" rows="3" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg text-gray-900 placeholder-gray-400 focus:outline-none focus:border-gray-400 transition text-sm" placeholder="<?= htmlspecialchars(t('profile.bio_placeholder')) ?>"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_public" id="is_public" value="1" <?= ($user['is_public'] ?? 0) ? 'checked' : '' ?>
                        class="w-4 h-4 rounded border-gray-300 bg-gray-50 text-gold-500 focus:ring-gold-500/50">
                    <label for="is_public" class="text-sm text-gray-600"><?= t('profile.public_toggle') ?></label>
                </div>
                <button type="submit" class="px-6 py-2.5 bg-gray-900 rounded-lg font-bold text-sm hover:bg-gray-800 transition" style="color:#fff !important"><?= t('profile.save') ?></button>
            </form>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="w-full lg:w-80 flex-shrink-0 space-y-6">

        <!-- Friends -->
        <div class="glass rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-display font-bold text-gray-900 flex items-center gap-2">
                    <i data-lucide="users" class="w-4 h-4 text-gold-400"></i> <?= t('profile.friends') ?>
                </h2>
                <a href="/friends" class="text-xs text-gray-400 hover:text-gold-400 transition"><?= t('profile.view_all') ?></a>
            </div>
            <?php if (empty($friends)): ?>
                <p class="text-sm text-gray-400 text-center py-4"><?= t('profile.no_friends') ?></p>
                <a href="/friends" class="block text-center text-xs text-gold-400 hover:text-gold-300 transition mt-1"><?= t('profile.find_people') ?></a>
            <?php else: ?>
                <div class="space-y-2">
                    <?php foreach (array_slice($friends, 0, 8) as $f): ?>
                        <a href="/user/<?= htmlspecialchars($f['username']) ?>" class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition group">
                            <?php if (!empty($f['avatar'])): ?>
                                <img src="<?= htmlspecialchars($f['avatar']) ?>" class="w-8 h-8 rounded-full" alt="">
                            <?php else: ?>
                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gold-500 to-gold-300 flex items-center justify-center font-bold text-xs" style="color:#fff"><?= strtoupper(substr($f['username'], 0, 1)) ?></div>
                            <?php endif; ?>
                            <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900 transition truncate"><?= htmlspecialchars($f['username']) ?></span>
                        </a>
                    <?php endforeach; ?>
                    <?php if (count($friends) > 8): ?>
                        <a href="/friends" class="block text-center text-xs text-gray-400 hover:text-gold-400 transition pt-2">
                            +<?= count($friends) - 8 ?> more
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Collection -->
        <div class="glass rounded-2xl p-5">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-display font-bold text-gray-900 flex items-center gap-2">
                    <i data-lucide="folder-open" class="w-4 h-4 text-gold-400"></i> <?= t('profile.collection') ?>
                </h2>
                <a href="/collection" class="text-xs text-gray-400 hover:text-gold-400 transition"><?= t('profile.view_all') ?></a>
            </div>
            <?php if (empty($recentCards)): ?>
                <p class="text-sm text-gray-400 text-center py-4"><?= t('profile.no_cards') ?></p>
                <a href="/cards" class="block text-center text-xs text-gold-400 hover:text-gold-300 transition mt-1"><?= t('profile.browse_cards') ?></a>
            <?php else: ?>
                <div class="grid grid-cols-3 gap-2">
                    <?php foreach ($recentCards as $card): ?>
                        <a href="/cards/<?= htmlspecialchars($card['card_set_id']) ?>" class="group relative" title="<?= htmlspecialchars($card['card_name']) ?>">
                            <img src="<?= htmlspecialchars($card['card_image_url'] ?? '') ?: 'about:blank' ?>" alt="<?= htmlspecialchars($card['card_name']) ?>"
                                 class="w-full aspect-[5/7] object-cover rounded-lg border border-gray-100 group-hover:border-gold-500/50 transition group-hover:shadow-lg" loading="lazy" onerror="cardImgErr(this)">
                            <?php if ($card['quantity'] > 1): ?>
                                <span class="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 bg-gray-900 rounded-full flex items-center justify-center text-[10px] font-bold" style="color:#fff !important"><?= $card['quantity'] ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php if (($stats['unique_cards'] ?? 0) > 12): ?>
                    <a href="/collection" class="block text-center text-xs text-gray-400 hover:text-gold-400 transition mt-3">
                        <?= t('profile.view_all') ?> <?= number_format($stats['unique_cards']) ?> <?= t('profile.cards_count') ?>
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

</div>
