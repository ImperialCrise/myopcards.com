<?php
$isLoggedIn    = \App\Core\Auth::check();
$currentUserId = \App\Core\Auth::id();
$isOwnProfile  = $isLoggedIn && $currentUserId === (int)$profileUser['id'];
$accentColor   = htmlspecialchars($profileUser['profile_accent_color'] ?? '#d4a853');

// Banner styles
$bannerStyle = '';
if (!empty($profileUser['banner_image'])) {
    $bannerStyle = 'background-image:url(/uploads/banners/' . htmlspecialchars($profileUser['banner_image']) . ');background-size:cover;background-position:center;';
} else {
    $gradientMap = [
        'ocean'  => 'linear-gradient(135deg,#0f2027,#203a43,#2c5364)',
        'fire'   => 'linear-gradient(135deg,#7f0000,#c0392b,#e74c3c)',
        'gold'   => 'linear-gradient(135deg,#3d2c00,#8B6914,#d4a853)',
        'nami'   => 'linear-gradient(135deg,#ff8c00,#e65c00,#f9d423)',
        'zoro'   => 'linear-gradient(135deg,#004d00,#1a7a1a,#52c41a)',
        'robin'  => 'linear-gradient(135deg,#1a0033,#4b0082,#7b2fff)',
        'law'    => 'linear-gradient(135deg,#111,#2c2c54,#f9ca24)',
        'shanks' => 'linear-gradient(135deg,#4a0000,#8b0000,#c0392b)',
        'default'=> 'linear-gradient(135deg,#0a0e17,#111827,#1a2332)',
    ];
    $g = $profileUser['banner_gradient'] ?? 'default';
    $bannerStyle = 'background:' . ($gradientMap[$g] ?? $gradientMap['default']) . ';';
}

// Completion %
$totalDbCards  = $totalCards ?? 0;
$uniqueCards   = (int)($stats['unique_cards'] ?? 0);
$completionPct = $totalDbCards > 0 ? round($uniqueCards / $totalDbCards * 100, 1) : 0;

// Rarity order & colors
$rarityOrder  = ['SEC','SP','L','SR','R','UC','C','P'];
$rarityColors = [
    'SEC' => '#f59e0b',
    'SP'  => '#a855f7',
    'L'   => '#f59e0b',
    'SR'  => '#3b82f6',
    'R'   => '#22c55e',
    'UC'  => '#64748b',
    'C'   => '#475569',
    'P'   => '#ec4899',
];
$rarityTotal = array_sum($rarityDist ?? []);

// Earned badge count
$earnedCount = count(array_filter($earnedBadges ?? [], fn($v) => $v !== null));

// Block / card themes
$blockThemes = [
    'default'  => ['bg'=>'rgba(17,24,39,0.72)',  'border'=>'rgba(74,100,128,0.22)'],
    'ocean'    => ['bg'=>'rgba(8,28,52,0.78)',   'border'=>'rgba(30,100,200,0.3)'],
    'fire'     => ['bg'=>'rgba(52,8,8,0.78)',    'border'=>'rgba(200,40,30,0.32)'],
    'gold'     => ['bg'=>'rgba(36,24,4,0.78)',   'border'=>'rgba(200,160,40,0.35)'],
    'emerald'  => ['bg'=>'rgba(4,28,16,0.78)',   'border'=>'rgba(20,160,80,0.3)'],
    'purple'   => ['bg'=>'rgba(20,4,40,0.78)',   'border'=>'rgba(120,30,210,0.35)'],
    'midnight' => ['bg'=>'rgba(6,6,20,0.82)',    'border'=>'rgba(50,50,130,0.28)'],
    'crimson'  => ['bg'=>'rgba(36,4,12,0.78)',   'border'=>'rgba(180,20,50,0.32)'],
    'slate'    => ['bg'=>'rgba(16,24,36,0.78)',  'border'=>'rgba(100,130,160,0.28)'],
    'rose'     => ['bg'=>'rgba(36,8,20,0.78)',   'border'=>'rgba(200,50,100,0.3)'],
    'teal'     => ['bg'=>'rgba(4,28,28,0.78)',   'border'=>'rgba(20,160,150,0.3)'],
    'amber'    => ['bg'=>'rgba(36,20,4,0.78)',   'border'=>'rgba(210,130,20,0.32)'],
];
$activeTheme = $blockThemes[$profileUser['card_style'] ?? 'default'] ?? $blockThemes['default'];
?>

<style>
.profile-banner { position:relative; height:220px; overflow:hidden; border-radius:0 0 24px 24px; }
.profile-banner::after { content:''; position:absolute; inset:0; background:linear-gradient(to bottom, transparent 30%, rgba(0,0,0,0.55) 100%); }
.profile-avatar-wrap { position:relative; display:inline-block; }
.profile-avatar-wrap img, .profile-avatar-wrap .avatar-placeholder {
    width:88px; height:88px; border-radius:50%;
    border:4px solid <?= $accentColor ?>;
    object-fit:cover; flex-shrink:0;
    box-shadow: 0 0 20px <?= $accentColor ?>55;
}
.badge-coin { display:inline-block; cursor:pointer; position:relative; transition:filter 0.2s; }
.badge-coin.locked { filter:grayscale(1) opacity(0.45); cursor:default; }
.badge-coin.earned { animation:floatBadge 3.5s ease-in-out infinite; }
.badge-coin-inner { display:block; width:72px; height:72px; transition:transform 0.1s ease-out; transform-style:preserve-3d; will-change:transform; position:relative; }
.badge-coin-inner svg { width:72px; height:72px; border-radius:50%; display:block; }
.badge-shine { position:absolute; inset:0; border-radius:50%; pointer-events:none; transition:opacity 0.2s; opacity:0; }
@keyframes floatBadge {
    0%,100% { transform:translateY(0); }
    50% { transform:translateY(-5px); }
}
.rarity-bar-segment { display:inline-block; height:12px; transition:width 0.6s ease; }
.stat-tile { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:14px 10px; border-radius:16px; min-width:88px; }
.progress-bar { height:8px; border-radius:4px; background:#1a2332; overflow:hidden; }
.progress-fill { height:100%; border-radius:4px; background:linear-gradient(90deg,<?= $accentColor ?>99,<?= $accentColor ?>); transition:width 0.7s ease; }
.card-showcase-item { position:relative; overflow:hidden; border-radius:10px; aspect-ratio:5/7; transition:transform 0.2s,box-shadow 0.2s; cursor:pointer; }
.card-showcase-item:hover { transform:translateY(-4px) scale(1.04); box-shadow:0 12px 28px rgba(0,0,0,0.5); }
.badge-tooltip { position:absolute; bottom:110%; left:50%; transform:translateX(-50%); background:#111827ee; color:#fff; font-size:11px; padding:6px 10px; border-radius:8px; white-space:nowrap; pointer-events:none; opacity:0; transition:opacity 0.15s; z-index:20; line-height:1.4; text-align:center; min-width:110px; }
.badge-coin:hover .badge-tooltip { opacity:1; }
</style>

<?php
$tierColors = ['bronze'=>'#cd7f32','silver'=>'#c0c0c0','gold'=>'#ffd700','diamond'=>'#b9f2ff'];
$categories = ['collection'=>'Collection','value'=>'Value','completion'=>'Completion','forum'=>'Forum','game'=>'Game','elo'=>'ELO Rank','social'=>'Social','profile'=>'Profile'];
?>
<style>
.pf-modal-overlay{position:fixed;inset:0;z-index:200;background:rgba(0,0,0,.75);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:16px;}
.pf-modal{background:#0f1623;border:1px solid #243044;border-radius:20px;width:100%;max-width:780px;max-height:90vh;overflow-y:auto;padding:28px;}
.badge-tip{position:absolute;bottom:110%;left:50%;transform:translateX(-50%);background:#111827ee;color:#fff;font-size:11px;padding:6px 10px;border-radius:8px;white-space:nowrap;pointer-events:none;opacity:0;transition:opacity .15s;z-index:30;line-height:1.4;text-align:center;min-width:110px;}
.badge-coin:hover .badge-tip{opacity:1;}
#pub-profile-root .glass{background:<?= $activeTheme['bg'] ?>;border-color:<?= $activeTheme['border'] ?>;}
</style>
<div id="pub-profile-root" class="max-w-5xl mx-auto px-4 pb-16" x-data="{ badgesOpen: false, ...publicProfile() }" @keydown.escape.window="badgesOpen=false">

    <!-- Banner + Hero -->
    <div class="profile-banner mb-0" style="<?= $bannerStyle ?>">
        <div class="absolute bottom-0 left-0 right-0 z-10 px-6 pb-0 flex items-end gap-4" style="padding-bottom:0">
        </div>
    </div>

    <!-- Avatar + name row (overlapping banner) -->
    <div class="relative flex flex-col sm:flex-row items-start sm:items-end gap-4 px-6 -mt-10 mb-6 z-10">
        <div class="profile-avatar-wrap flex-shrink-0">
            <?php if (\App\Models\User::getAvatarUrl($profileUser)): ?>
                <img src="<?= htmlspecialchars(\App\Models\User::getAvatarUrl($profileUser)) ?>" alt="">
            <?php else: ?>
                <div class="avatar-placeholder flex items-center justify-center text-3xl font-display font-bold" style="background:linear-gradient(135deg,<?= $accentColor ?>,<?= $accentColor ?>88);color:#fff">
                    <?= strtoupper(substr($profileUser['username'], 0, 1)) ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="flex-1 min-w-0 pt-2">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-display font-bold text-white"><?= htmlspecialchars($profileUser['username']) ?></h1>
                <?php if ($earnedCount > 0): ?>
                <button @click="badgesOpen=true" class="px-2 py-0.5 text-xs font-bold rounded-full hover:opacity-80 transition" style="background:<?= $accentColor ?>22;color:<?= $accentColor ?>;border:1px solid <?= $accentColor ?>44">
                    <?= $earnedCount ?> badges
                </button>
                <?php endif; ?>
            </div>
            <?php if (!empty($profileUser['bio'])): ?>
            <p class="text-sm text-dark-300 mt-1 line-clamp-2"><?= htmlspecialchars($profileUser['bio']) ?></p>
            <?php endif; ?>
            <p class="text-xs text-dark-400 mt-1">Joined <?= date('F Y', strtotime($profileUser['created_at'])) ?></p>
        </div>
        <!-- Friend actions -->
        <div class="flex-shrink-0 flex items-center gap-2 pt-2">
            <?php if ($isLoggedIn && !$isOwnProfile): ?>
                <div x-show="relation === 'friend'" class="relative" x-data="{ dropOpen: false }" @click.outside="dropOpen = false">
                    <button @click="dropOpen = !dropOpen" class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-500/10 text-green-400 rounded-lg text-sm font-bold hover:bg-green-500/20 transition">
                        <i data-lucide="user-check" class="w-4 h-4"></i> Friends <i data-lucide="chevron-down" class="w-3 h-3"></i>
                    </button>
                    <div x-show="dropOpen" x-transition x-cloak class="absolute right-0 mt-1 glass-strong rounded-xl shadow-2xl py-1 w-48 z-50">
                        <a :href="'/messages/new/' + username" class="flex items-center gap-2 w-full px-3 py-2 text-sm text-dark-200 hover:bg-white/5 transition text-left">
                            <i data-lucide="mail" class="w-4 h-4"></i> <?= t('friends.message', 'Message') ?>
                        </a>
                        <button @click="removeFriend()" class="flex items-center gap-2 w-full px-3 py-2 text-sm text-red-400 hover:bg-red-900/20 transition text-left">
                            <i data-lucide="user-minus" class="w-4 h-4"></i> <?= t('friends.remove') ?>
                        </button>
                        <button @click="openReportModal()" class="flex items-center gap-2 w-full px-3 py-2 text-sm text-amber-400 hover:bg-amber-900/20 transition text-left">
                            <i data-lucide="flag" class="w-4 h-4"></i> <?= t('friends.report', 'Report') ?>
                        </button>
                        <button @click="blockUser()" class="flex items-center gap-2 w-full px-3 py-2 text-sm text-red-400 hover:bg-red-900/20 transition text-left">
                            <i data-lucide="ban" class="w-4 h-4"></i> <?= t('friends.block', 'Block') ?>
                        </button>
                    </div>
                </div>
                <div x-show="relation === 'pending_sent'" x-cloak>
                    <span class="inline-flex items-center gap-1 px-3 py-1.5 bg-dark-700 text-dark-300 rounded-lg text-sm font-bold">
                        <i data-lucide="clock" class="w-4 h-4"></i> Request Sent
                    </span>
                </div>
                <div x-show="relation === 'pending_received'" x-cloak class="flex gap-2">
                    <button @click="acceptRequest()" class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-500 text-white rounded-lg text-sm font-bold hover:bg-green-600 transition">
                        <i data-lucide="check" class="w-4 h-4"></i> Accept
                    </button>
                    <button @click="declineRequest()" class="inline-flex items-center gap-1 px-3 py-1.5 bg-dark-700 text-dark-300 rounded-lg text-sm font-bold hover:bg-dark-600 transition">
                        <i data-lucide="x" class="w-4 h-4"></i> Decline
                    </button>
                </div>
                <div x-show="relation === 'none'" x-cloak class="flex items-center gap-2">
                    <button @click="addFriend()" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-bold hover:opacity-90 transition" style="background:<?= $accentColor ?>22;color:<?= $accentColor ?>;border:1px solid <?= $accentColor ?>44">
                        <i data-lucide="user-plus" class="w-4 h-4"></i> Add Friend
                    </button>
                    <a :href="'/messages/new/' + username" class="p-2 text-dark-400 hover:text-blue-400 rounded-lg transition" title="<?= t('friends.message', 'Message') ?>">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                    </a>
                    <button @click="openReportModal()" class="p-2 text-dark-400 hover:text-amber-500 transition" title="<?= t('friends.report', 'Report') ?>">
                        <i data-lucide="flag" class="w-4 h-4"></i>
                    </button>
                    <button @click="blockUser()" class="p-2 text-dark-400 hover:text-red-500 transition" title="<?= t('friends.block', 'Block') ?>">
                        <i data-lucide="ban" class="w-4 h-4"></i>
                    </button>
                </div>
            <?php elseif ($isOwnProfile): ?>
                <a href="/settings" class="inline-flex items-center gap-1 px-3 py-1.5 bg-dark-700 text-dark-300 hover:text-white rounded-lg text-sm transition">
                    <i data-lucide="settings" class="w-4 h-4"></i> Settings
                </a>
                <a href="/profile" class="inline-flex items-center gap-1 px-3 py-1.5 bg-dark-700 text-dark-300 hover:text-white rounded-lg text-sm transition">
                    <i data-lucide="edit" class="w-4 h-4"></i> Edit Profile
                </a>
            <?php endif; ?>
            <?php if ($profileUser['is_public'] ?? false): ?>
            <div x-data="{ copied: false }">
                <button @click="navigator.clipboard.writeText('<?= ($_ENV['APP_URL'] ?? 'https://myopcards.com') ?>/user/<?= htmlspecialchars($profileUser['username']) ?>'); copied = true; setTimeout(()=>copied=false,2000)"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-dark-700 text-dark-400 hover:text-white rounded-lg text-xs transition">
                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                    <span x-text="copied ? 'Copied!' : 'Copy link'"></span>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="flex gap-3 overflow-x-auto pb-2 mb-6 scrollbar-hide">
        <div class="stat-tile glass flex-shrink-0">
            <p class="text-xl font-display font-bold text-white"><?= number_format($uniqueCards) ?></p>
            <p class="text-[10px] text-dark-400 uppercase tracking-wider mt-1">Cards</p>
            <p class="text-[10px] font-bold mt-0.5" style="color:<?= $accentColor ?>"><?= $completionPct ?>% of DB</p>
        </div>
        <div class="stat-tile glass flex-shrink-0">
            <p class="text-xl font-display font-bold text-white"><?= \App\Core\Currency::format((float)($stats['total_value'] ?? 0)) ?></p>
            <p class="text-[10px] text-dark-400 uppercase tracking-wider mt-1">Value (<?= \App\Core\Currency::label() ?>)</p>
        </div>
        <div class="stat-tile glass flex-shrink-0">
            <p class="text-xl font-display font-bold text-white"><?= $friendCount ?></p>
            <p class="text-[10px] text-dark-400 uppercase tracking-wider mt-1">Friends</p>
        </div>
        <div class="stat-tile glass flex-shrink-0">
            <p class="text-xl font-display font-bold text-white"><?= number_format(($forumStats['post_count'] ?? 0) + ($forumStats['topic_count'] ?? 0)) ?></p>
            <p class="text-[10px] text-dark-400 uppercase tracking-wider mt-1">Forum</p>
            <p class="text-[10px] text-dark-500 mt-0.5"><?= $forumStats['topic_count'] ?? 0 ?> topics</p>
        </div>
        <?php $lb = $leaderboard ?? null; ?>
        <div class="stat-tile glass flex-shrink-0">
            <p class="text-xl font-display font-bold text-white"><?= $lb ? number_format((int)$lb['elo_rating']) : '–' ?></p>
            <p class="text-[10px] text-dark-400 uppercase tracking-wider mt-1">ELO</p>
            <?php if ($lbRank): ?><p class="text-[10px] font-bold mt-0.5" style="color:<?= $accentColor ?>">#<?= $lbRank ?></p><?php endif; ?>
        </div>
        <div class="stat-tile glass flex-shrink-0">
            <p class="text-xl font-display font-bold text-white"><?= $deckCount ?></p>
            <p class="text-[10px] text-dark-400 uppercase tracking-wider mt-1">Decks</p>
        </div>
        <div class="stat-tile glass flex-shrink-0">
            <p class="text-xl font-display font-bold text-white"><?= number_format($viewCounts['total'] ?? 0) ?></p>
            <p class="text-[10px] text-dark-400 uppercase tracking-wider mt-1 flex items-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i> Views</p>
        </div>
    </div>


    <!-- Featured Card + Collection row -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        <!-- Collection Showcase -->
        <div class="lg:col-span-2 glass rounded-2xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-display font-bold text-white flex items-center gap-2">
                    <i data-lucide="folder-open" class="w-5 h-5" style="color:<?= $accentColor ?>"></i>
                    <?= t('profile.showcase') ?>
                </h2>
                <?php if ($profileUser['is_public'] ?? false): ?>
                <a href="/collection/<?= htmlspecialchars($profileUser['username']) ?>" class="text-xs text-dark-400 hover:text-white transition"><?= t('profile.view_all') ?> →</a>
                <?php endif; ?>
            </div>
            <?php if (empty($recentCards)): ?>
            <p class="text-sm text-dark-400 text-center py-8"><?= t('profile.no_collection') ?></p>
            <?php else: ?>
            <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                <?php foreach (array_slice($recentCards, 0, 12) as $card): ?>
                <a href="/cards/<?= htmlspecialchars($card['card_set_id']) ?>" class="card-showcase-item group" title="<?= htmlspecialchars($card['card_name']) ?>">
                    <img src="<?= htmlspecialchars(card_img_url($card)) ?: 'about:blank' ?>" data-ext-src="<?= htmlspecialchars($card['card_image_url'] ?? '') ?>" alt="<?= htmlspecialchars($card['card_name']) ?>"
                         class="w-full h-full object-cover" loading="lazy" onerror="cardImgErr(this)">
                    <?php if (($card['quantity'] ?? 0) > 1): ?>
                    <span class="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 bg-black/70 rounded-full flex items-center justify-center text-[10px] font-bold text-white"><?= $card['quantity'] ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Featured Card -->
        <div class="glass rounded-2xl p-6 flex flex-col">
            <?php if ($featuredCard): ?>
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="star" class="w-5 h-5 fill-current" style="color:<?= $accentColor ?>"></i>
                <h2 class="text-base font-display font-bold text-white">Featured Card</h2>
            </div>
            <div class="flex flex-col items-center text-center flex-1">
                <div class="relative mb-4">
                    <img src="<?= htmlspecialchars(card_img_url($featuredCard)) ?>"
                         alt="<?= htmlspecialchars($featuredCard['card_name']) ?>"
                         class="w-28 rounded-xl shadow-xl border-2" style="border-color:<?= $accentColor ?>55">
                    <div class="absolute -top-2 -right-2 w-7 h-7 rounded-full flex items-center justify-center shadow" style="background:<?= $accentColor ?>">
                        <i data-lucide="star" class="w-3.5 h-3.5 text-dark-900 fill-current"></i>
                    </div>
                </div>
                <h3 class="text-sm font-bold text-white mb-1"><?= htmlspecialchars($featuredCard['card_name']) ?></h3>
                <p class="text-xs text-dark-400 mb-3"><?= htmlspecialchars($featuredCard['card_set_id']) ?> · <?= htmlspecialchars($featuredCard['set_name'] ?? '') ?></p>
                <div class="flex items-center justify-center gap-2 flex-wrap text-xs mb-4">
                    <?php if ($featuredCard['rarity']): ?><span class="px-2 py-0.5 rounded-full bg-purple-900/30 text-purple-300"><?= htmlspecialchars($featuredCard['rarity']) ?></span><?php endif; ?>
                    <?php if ($featuredCard['card_color']): ?><span class="px-2 py-0.5 rounded-full bg-blue-900/30 text-blue-300"><?= htmlspecialchars($featuredCard['card_color']) ?></span><?php endif; ?>
                    <?php if ($featuredCard['market_price']): ?><span class="px-2 py-0.5 rounded-full bg-green-900/30 text-green-300 font-bold">$<?= number_format((float)$featuredCard['market_price'], 2) ?></span><?php endif; ?>
                </div>
                <a href="/cards/<?= htmlspecialchars($featuredCard['card_set_id']) ?>" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold transition hover:opacity-90" style="background:<?= $accentColor ?>;color:#111">
                    <i data-lucide="external-link" class="w-3.5 h-3.5"></i> View Card
                </a>
            </div>
            <?php else: ?>
            <div class="flex flex-col items-center justify-center flex-1 text-center py-8">
                <i data-lucide="star" class="w-10 h-10 text-dark-600 mb-2"></i>
                <p class="text-sm text-dark-400">No featured card</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Game Stats + Rarity + Set Completion + Forum Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        <!-- Left 2/3 -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Game Stats -->
            <?php if ($lb && (int)$lb['games_played'] > 0): ?>
            <div class="glass rounded-2xl p-6">
                <h2 class="text-base font-display font-bold text-white flex items-center gap-2 mb-4">
                    <i data-lucide="gamepad-2" class="w-5 h-5" style="color:<?= $accentColor ?>"></i>
                    <?= t('profile.game_stats') ?>
                    <?php if ($lbRank): ?><span class="ml-auto text-xs font-bold px-2 py-0.5 rounded-full" style="background:<?= $accentColor ?>22;color:<?= $accentColor ?>">#<?= $lbRank ?> Ranked</span><?php endif; ?>
                </h2>
                <div class="grid grid-cols-3 sm:grid-cols-6 gap-3">
                    <?php
                    $gameStats = [
                        ['label'=>'ELO',    'value'=>number_format((int)$lb['elo_rating'])],
                        ['label'=>'Wins',   'value'=>number_format((int)$lb['wins'])],
                        ['label'=>'Losses', 'value'=>number_format((int)$lb['losses'])],
                        ['label'=>'Draws',  'value'=>number_format((int)($lb['draws']??0))],
                        ['label'=>'Games',  'value'=>number_format((int)$lb['games_played'])],
                        ['label'=>'Best Streak','value'=>(int)($lb['best_streak']??0).'🔥'],
                    ];
                    foreach ($gameStats as $gs):
                    ?>
                    <div class="text-center p-3 rounded-xl" style="background:rgba(255,255,255,0.04)">
                        <p class="text-lg font-display font-bold text-white"><?= $gs['value'] ?></p>
                        <p class="text-[10px] text-dark-400 uppercase tracking-wider mt-0.5"><?= $gs['label'] ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php
                $g = max(1, (int)$lb['games_played']);
                $winRate = round((int)$lb['wins'] / $g * 100);
                ?>
                <div class="mt-4">
                    <div class="flex justify-between text-xs text-dark-400 mb-1">
                        <span>Win rate</span><span><?= $winRate ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width:<?= $winRate ?>%"></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Rarity Distribution -->
            <?php if ($rarityTotal > 0): ?>
            <div class="glass rounded-2xl p-6">
                <h2 class="text-base font-display font-bold text-white flex items-center gap-2 mb-4">
                    <i data-lucide="layers" class="w-5 h-5" style="color:<?= $accentColor ?>"></i>
                    <?= t('profile.rarity_dist') ?>
                </h2>
                <div class="flex rounded-lg overflow-hidden mb-3 h-3">
                    <?php foreach ($rarityOrder as $rarity):
                        $cnt = $rarityDist[$rarity] ?? 0;
                        if ($cnt === 0) continue;
                        $pct = round($cnt / $rarityTotal * 100, 1);
                        $col = $rarityColors[$rarity] ?? '#888';
                    ?>
                    <div class="rarity-bar-segment" style="width:<?= $pct ?>%;background:<?= $col ?>" title="<?= $rarity ?>: <?= $cnt ?>"></div>
                    <?php endforeach; ?>
                </div>
                <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2">
                    <?php foreach ($rarityOrder as $rarity):
                        $cnt = $rarityDist[$rarity] ?? 0;
                        if ($cnt === 0) continue;
                        $col = $rarityColors[$rarity] ?? '#888';
                    ?>
                    <div class="flex items-center gap-1.5 text-xs">
                        <span class="w-2.5 h-2.5 rounded-sm flex-shrink-0" style="background:<?= $col ?>"></span>
                        <span class="text-dark-300"><?= $rarity ?></span>
                        <span class="text-dark-400"><?= number_format($cnt) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Set Completion -->
            <?php
            $topSets = array_filter($setCompletion ?? [], fn($s) => (int)$s['owned'] > 0);
            usort($topSets, fn($a,$b) => $b['owned'] - $a['owned']);
            $topSets = array_slice($topSets, 0, 5);
            if (!empty($topSets)):
            ?>
            <div class="glass rounded-2xl p-6">
                <h2 class="text-base font-display font-bold text-white flex items-center gap-2 mb-4">
                    <i data-lucide="package" class="w-5 h-5" style="color:<?= $accentColor ?>"></i>
                    <?= t('profile.set_completion') ?>
                </h2>
                <div class="space-y-3">
                    <?php foreach ($topSets as $set):
                        $total = (int)($set['card_count'] ?? 0);
                        $owned = (int)($set['owned'] ?? 0);
                        $pct   = $total > 0 ? min(100, round($owned / $total * 100)) : 0;
                    ?>
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-dark-200 truncate max-w-[200px]"><?= htmlspecialchars($set['set_name'] ?? $set['set_id']) ?></span>
                            <span class="text-dark-400 flex-shrink-0 ml-2"><?= $owned ?>/<?= $total ?> · <span style="color:<?= $accentColor ?>"><?= $pct ?>%</span></span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Forum Activity -->
            <?php if (!empty($recentActivity)): ?>
            <div class="glass rounded-2xl p-6">
                <h2 class="text-base font-display font-bold text-white flex items-center gap-2 mb-4">
                    <i data-lucide="message-square" class="w-5 h-5" style="color:<?= $accentColor ?>"></i>
                    Recent Forum Activity
                </h2>
                <div class="space-y-2">
                    <?php foreach ($recentActivity as $activity): ?>
                    <a href="/forum/<?= htmlspecialchars($activity['category_slug']) ?>/<?= $activity['type'] === 'topic' ? $activity['id'] : $activity['topic_id'] ?>-<?= htmlspecialchars($activity['slug']) ?>"
                       class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-white/5 transition group">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 <?= $activity['type'] === 'topic' ? 'bg-blue-900/30 text-blue-400' : 'bg-green-900/30 text-green-400' ?>">
                            <i data-lucide="<?= $activity['type'] === 'topic' ? 'plus' : 'message-circle' ?>" class="w-3.5 h-3.5"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-dark-200 group-hover:text-white transition truncate"><?= htmlspecialchars($activity['title']) ?></p>
                            <p class="text-xs text-dark-500"><?= $activity['type'] === 'topic' ? 'Created topic' : 'Replied' ?> · <?= date('M j, H:i', strtotime($activity['created_at'])) ?></p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-dark-600 group-hover:text-dark-400 transition flex-shrink-0"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right sidebar -->
        <div class="space-y-6">

            <!-- Badges sidebar widget -->
            <?php if (!empty($allBadges)): ?>
            <div class="glass rounded-2xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-display font-bold text-white flex items-center gap-2">
                        <i data-lucide="award" class="w-4 h-4" style="color:<?= $accentColor ?>"></i>
                        Achievements <span class="text-dark-400 font-normal text-xs">(<?= $earnedCount ?>/<?= count($allBadges) ?>)</span>
                    </h2>
                    <button @click="badgesOpen=true" class="text-xs text-dark-400 hover:text-white transition">View all →</button>
                </div>
                <?php
                $earned9 = array_filter($allBadges, fn($b) => ($earnedBadges[$b['id']] ?? null) !== null);
                $earned9 = array_slice(array_values($earned9), 0, 9);
                if (empty($earned9)):
                ?>
                <p class="text-xs text-dark-500 text-center py-4">No badges yet.</p>
                <?php else: ?>
                <div class="flex flex-wrap gap-3 justify-center">
                    <?php foreach ($earned9 as $badge):
                        $tc = $tierColors[$badge['tier']] ?? '#999';
                    ?>
                    <div class="badge-coin earned">
                        <div class="badge-coin-inner"><?= $badge['icon'] ?></div>
                        <div class="badge-tip"><strong style="color:<?= $tc ?>"><?= htmlspecialchars($badge['name']) ?></strong><br><?= htmlspecialchars($badge['description']) ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($earnedCount > 9): ?>
                <button @click="badgesOpen=true" class="mt-4 w-full text-xs text-dark-400 hover:text-white transition text-center py-2 rounded-lg hover:bg-white/5">
                    +<?= $earnedCount - 9 ?> more badges →
                </button>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Friends -->
            <div class="glass rounded-2xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-display font-bold text-white flex items-center gap-2">
                        <i data-lucide="users" class="w-4 h-4" style="color:<?= $accentColor ?>"></i>
                        Friends <span class="text-dark-400 text-xs font-normal">(<?= $friendCount ?>)</span>
                    </h2>
                </div>
                <?php if (empty($friends)): ?>
                <p class="text-xs text-dark-500 text-center py-4"><?= t('profile.no_friends') ?></p>
                <?php else: ?>
                <div class="space-y-1.5">
                    <?php foreach (array_slice($friends, 0, 8) as $f): ?>
                    <a href="/user/<?= htmlspecialchars($f['username']) ?>" class="flex items-center gap-2.5 p-1.5 rounded-lg hover:bg-white/5 transition group">
                        <?php if (\App\Models\User::getAvatarUrl($f)): ?>
                        <img src="<?= htmlspecialchars(\App\Models\User::getAvatarUrl($f)) ?>" class="w-7 h-7 rounded-full flex-shrink-0" alt="">
                        <?php else: ?>
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" style="background:<?= $accentColor ?>33;color:<?= $accentColor ?>"><?= strtoupper(substr($f['username'], 0, 1)) ?></div>
                        <?php endif; ?>
                        <span class="text-sm text-dark-200 group-hover:text-white transition truncate"><?= htmlspecialchars($f['username']) ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Forum summary -->
            <?php if (($forumStats['post_count'] ?? 0) > 0 || ($forumStats['topic_count'] ?? 0) > 0): ?>
            <div class="glass rounded-2xl p-5">
                <h2 class="text-sm font-display font-bold text-white flex items-center gap-2 mb-3">
                    <i data-lucide="message-circle" class="w-4 h-4" style="color:<?= $accentColor ?>"></i>
                    Forum
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <div class="text-center p-3 rounded-xl" style="background:rgba(255,255,255,0.04)">
                        <p class="text-xl font-bold text-white"><?= number_format($forumStats['topic_count'] ?? 0) ?></p>
                        <p class="text-[10px] text-dark-400 uppercase tracking-wider mt-0.5">Topics</p>
                    </div>
                    <div class="text-center p-3 rounded-xl" style="background:rgba(255,255,255,0.04)">
                        <p class="text-xl font-bold text-white"><?= number_format($forumStats['post_count'] ?? 0) ?></p>
                        <p class="text-[10px] text-dark-400 uppercase tracking-wider mt-0.5">Replies</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== BADGES POPUP ===== -->
    <div class="pf-modal-overlay" x-show="badgesOpen" x-cloak @click.self="badgesOpen=false"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="pf-modal" @click.stop>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-display font-bold text-white flex items-center gap-2">
                    <i data-lucide="award" class="w-6 h-6" style="color:<?= $accentColor ?>"></i>
                    <?= htmlspecialchars($profileUser['username']) ?>'s Achievements
                    <span class="text-sm font-normal text-dark-400"><?= $earnedCount ?>/<?= count($allBadges) ?></span>
                </h2>
                <button @click="badgesOpen=false" class="text-dark-400 hover:text-white transition p-1 rounded-lg hover:bg-dark-700">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <?php foreach ($categories as $catId => $catLabel):
                $catBadges = array_filter($allBadges, fn($b) => $b['category'] === $catId);
                if (empty($catBadges)) continue;
            ?>
            <div class="mb-6">
                <p class="text-xs font-bold uppercase tracking-widest text-dark-400 mb-3 border-b border-dark-700 pb-2"><?= $catLabel ?></p>
                <div class="flex flex-wrap gap-4">
                    <?php foreach ($catBadges as $badge):
                        $earned = ($earnedBadges[$badge['id']] ?? null) !== null;
                        $tc = $tierColors[$badge['tier']] ?? '#999';
                    ?>
                    <div class="badge-coin <?= $earned ? 'earned' : 'locked' ?>">
                        <div class="badge-coin-inner"><?= $badge['icon'] ?></div>
                        <div class="badge-tip">
                            <strong style="color:<?= $tc ?>"><?= htmlspecialchars($badge['name']) ?></strong><br>
                            <?= htmlspecialchars($badge['description']) ?>
                            <?php if ($earned): ?><br><span style="color:<?= $tc ?>">✓ Earned</span><?php else: ?><br><span style="color:#666">🔒 Locked</span><?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Report Modal -->
    <div x-show="reportModalOpen" x-cloak x-transition class="fixed inset-0 z-[210] flex items-center justify-center p-4 bg-black/50" @keydown.escape.window="reportModalOpen = false" @click.self="reportModalOpen = false">
        <div class="bg-white dark:bg-dark-800 rounded-2xl shadow-xl max-w-md w-full p-6" @click.stop>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4"><?= t('friends.report_user', 'Report user') ?></h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4" x-show="username">Reporting <span class="font-medium text-gray-900 dark:text-white" x-text="username"></span></p>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('friends.report_reason', 'Reason') ?></label>
                    <select x-model="reportReason" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-dark-700 border border-gray-200 dark:border-dark-600 rounded-lg text-gray-900 dark:text-white">
                        <option value="spam"><?= t('friends.reason_spam', 'Spam') ?></option>
                        <option value="harassment"><?= t('friends.reason_harassment', 'Harassment') ?></option>
                        <option value="inappropriate_content"><?= t('friends.reason_inappropriate', 'Inappropriate content') ?></option>
                        <option value="cheating"><?= t('friends.reason_cheating', 'Cheating') ?></option>
                        <option value="other"><?= t('friends.reason_other', 'Other') ?></option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"><?= t('friends.report_details', 'Details (optional)') ?></label>
                    <textarea x-model="reportDetails" rows="3" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-dark-700 border border-gray-200 dark:border-dark-600 rounded-lg text-gray-900 dark:text-white" placeholder="<?= htmlspecialchars(t('friends.report_details_placeholder', 'Provide additional context...')) ?>"></textarea>
                </div>
            </div>
            <div class="flex gap-2 mt-6">
                <button @click="submitReport()" class="flex-1 px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-lg font-bold text-sm"><?= t('friends.submit_report', 'Submit Report') ?></button>
                <button @click="reportModalOpen = false" class="px-4 py-2.5 bg-gray-200 dark:bg-dark-600 text-gray-800 dark:text-white rounded-lg font-medium text-sm"><?= t('friends.cancel', 'Cancel') ?></button>
            </div>
            <p x-show="reportError" x-text="reportError" class="text-red-500 text-sm mt-2"></p>
        </div>
    </div>
</div>

<script>
window.__PAGE_DATA = {
    userId: <?= (int)$profileUser['id'] ?>,
    username: <?= json_encode($profileUser['username']) ?>,
    relation: <?= json_encode(
        $isFriend ? 'friend' : (
            ($pendingSent ?? false) ? 'pending_sent' : (
                ($pendingReceived ?? false) ? 'pending_received' : 'none'
            )
        )
    ) ?>
};
</script>
<script src="<?= asset_v('/assets/js/pages/public-profile.js') ?>"></script>
<script src="/assets/js/badge-3d.js"></script>
