<?php
$accentColor = htmlspecialchars($user['profile_accent_color'] ?? '#d4a853');

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

if (!empty($user['banner_image'])) {
    $bannerStyle = 'background-image:url(/uploads/banners/' . htmlspecialchars($user['banner_image']) . ');background-size:cover;background-position:center;';
} else {
    $g = $user['banner_gradient'] ?? 'default';
    $bannerStyle = 'background:' . ($gradientMap[$g] ?? $gradientMap['default']) . ';';
}

$totalDbCards  = $totalCards ?? 0;
$uniqueCards   = (int)($stats['unique_cards'] ?? 0);
$completionPct = $totalDbCards > 0 ? round($uniqueCards / $totalDbCards * 100, 1) : 0;
$rarityOrder   = ['SEC','SP','L','SR','R','UC','C','P'];
$rarityColors  = ['SEC'=>'#f59e0b','SP'=>'#a855f7','L'=>'#f59e0b','SR'=>'#3b82f6','R'=>'#22c55e','UC'=>'#64748b','C'=>'#475569','P'=>'#ec4899'];
$rarityTotal   = array_sum($rarityDist ?? []);
$earnedCount   = count(array_filter($earnedBadges ?? [], fn($v) => $v !== null));

$gradientOptions = [
    'default' => ['label'=>'Default Dark',    'preview'=>'linear-gradient(135deg,#0a0e17,#111827,#1a2332)'],
    'ocean'   => ['label'=>'Ocean',           'preview'=>'linear-gradient(135deg,#0f2027,#203a43,#2c5364)'],
    'fire'    => ['label'=>'Fire (Ace)',       'preview'=>'linear-gradient(135deg,#7f0000,#c0392b,#e74c3c)'],
    'gold'    => ['label'=>'Gold (Luffy)',     'preview'=>'linear-gradient(135deg,#3d2c00,#8B6914,#d4a853)'],
    'nami'    => ['label'=>'Orange (Nami)',    'preview'=>'linear-gradient(135deg,#ff8c00,#e65c00,#f9d423)'],
    'zoro'    => ['label'=>'Green (Zoro)',     'preview'=>'linear-gradient(135deg,#004d00,#1a7a1a,#52c41a)'],
    'robin'   => ['label'=>'Purple (Robin)',   'preview'=>'linear-gradient(135deg,#1a0033,#4b0082,#7b2fff)'],
    'law'     => ['label'=>'Law Style',        'preview'=>'linear-gradient(135deg,#111,#2c2c54,#f9ca24)'],
    'shanks'  => ['label'=>'Crimson (Shanks)', 'preview'=>'linear-gradient(135deg,#4a0000,#8b0000,#c0392b)'],
];

// Pass gradient data to JS
$gradientMapJson = json_encode(array_map(fn($v) => $v['preview'], $gradientOptions));
$currentGradient = $user['banner_gradient'] ?? 'default';
$hasBannerImage  = !empty($user['banner_image']);

// Badges grouped by category
$categories = [
    'collection' => 'Collection',
    'value'      => 'Value',
    'completion' => 'Completion',
    'forum'      => 'Forum',
    'game'       => 'Game',
    'elo'        => 'ELO Rank',
    'social'     => 'Social',
    'profile'    => 'Profile',
];
$tierColors = ['bronze'=>'#cd7f32','silver'=>'#c0c0c0','gold'=>'#ffd700','diamond'=>'#b9f2ff'];

$lb = $leaderboard ?? null;

// Block / card themes
$blockThemes = [
    'default'  => ['label'=>'Dark (Default)',  'bg'=>'rgba(17,24,39,0.72)',   'border'=>'rgba(74,100,128,0.22)',  'preview'=>'#111827'],
    'ocean'    => ['label'=>'Ocean Blue',      'bg'=>'rgba(8,28,52,0.78)',    'border'=>'rgba(30,100,200,0.3)',   'preview'=>'#0c1c34'],
    'fire'     => ['label'=>'Fire Red',        'bg'=>'rgba(52,8,8,0.78)',     'border'=>'rgba(200,40,30,0.32)',   'preview'=>'#340808'],
    'gold'     => ['label'=>'Gold',            'bg'=>'rgba(36,24,4,0.78)',    'border'=>'rgba(200,160,40,0.35)',  'preview'=>'#241804'],
    'emerald'  => ['label'=>'Emerald',         'bg'=>'rgba(4,28,16,0.78)',    'border'=>'rgba(20,160,80,0.3)',    'preview'=>'#041c10'],
    'purple'   => ['label'=>'Deep Purple',     'bg'=>'rgba(20,4,40,0.78)',    'border'=>'rgba(120,30,210,0.35)', 'preview'=>'#140428'],
    'midnight' => ['label'=>'Midnight',        'bg'=>'rgba(6,6,20,0.82)',     'border'=>'rgba(50,50,130,0.28)',   'preview'=>'#060614'],
    'crimson'  => ['label'=>'Crimson',         'bg'=>'rgba(36,4,12,0.78)',    'border'=>'rgba(180,20,50,0.32)',   'preview'=>'#24040c'],
    'slate'    => ['label'=>'Steel Slate',     'bg'=>'rgba(16,24,36,0.78)',   'border'=>'rgba(100,130,160,0.28)', 'preview'=>'#101824'],
    'rose'     => ['label'=>'Rose',            'bg'=>'rgba(36,8,20,0.78)',    'border'=>'rgba(200,50,100,0.3)',   'preview'=>'#240814'],
    'teal'     => ['label'=>'Teal',            'bg'=>'rgba(4,28,28,0.78)',    'border'=>'rgba(20,160,150,0.3)',   'preview'=>'#041c1c'],
    'amber'    => ['label'=>'Amber',           'bg'=>'rgba(36,20,4,0.78)',    'border'=>'rgba(210,130,20,0.32)',  'preview'=>'#241404'],
];
$currentCardStyle = $user['card_style'] ?? 'default';
$activeTheme = $blockThemes[$currentCardStyle] ?? $blockThemes['default'];
?>
<?php if (isset($_GET['updated'])): ?>
<div class="max-w-5xl mx-auto px-4 mb-4">
    <div class="bg-green-500/10 border border-green-500/30 text-green-400 px-4 py-3 rounded-xl text-sm flex items-center gap-2">
        <i data-lucide="check-circle" class="w-4 h-4"></i> <?= t('profile.updated') ?>
    </div>
</div>
<?php endif; ?>

<style>
.profile-banner{position:relative;height:220px;overflow:hidden;border-radius:0 0 24px 24px;}
.profile-banner::after{content:'';position:absolute;inset:0;background:linear-gradient(to bottom,transparent 30%,rgba(0,0,0,.6) 100%);pointer-events:none;}
.pf-avatar img,.pf-avatar .pf-avatar-ph{width:88px;height:88px;border-radius:50%;border:4px solid <?= $accentColor ?>;object-fit:cover;flex-shrink:0;box-shadow:0 0 20px <?= $accentColor ?>55;}
.badge-coin{display:inline-block;cursor:pointer;position:relative;transition:filter .2s;}
.badge-coin.locked{filter:grayscale(1) opacity(.4);cursor:default;}
.badge-coin.earned{animation:floatBadge 3.5s ease-in-out infinite;}
.badge-coin-inner{display:block;width:72px;height:72px;transition:transform .08s ease-out;transform-style:preserve-3d;will-change:transform;position:relative;}
.badge-coin-inner svg{width:72px;height:72px;border-radius:50%;display:block;}
.badge-shine{position:absolute;inset:0;border-radius:50%;pointer-events:none;opacity:0;}
@keyframes floatBadge{0%,100%{transform:translateY(0)}50%{transform:translateY(-5px)}}
.rarity-seg{display:inline-block;height:12px;transition:width .6s ease;}
.stat-tile{display:flex;flex-direction:column;align-items:center;justify-content:center;padding:14px 10px;border-radius:16px;min-width:88px;}
.pf-progress{height:8px;border-radius:4px;background:#1a2332;overflow:hidden;}
.pf-fill{height:100%;border-radius:4px;background:linear-gradient(90deg,<?= $accentColor ?>99,<?= $accentColor ?>);transition:width .7s ease;}
.card-show{position:relative;overflow:hidden;border-radius:10px;aspect-ratio:5/7;transition:transform .2s,box-shadow .2s;}
.card-show:hover{transform:translateY(-4px) scale(1.04);box-shadow:0 12px 28px rgba(0,0,0,.5);}
.badge-tip{position:absolute;bottom:110%;left:50%;transform:translateX(-50%);background:#111827ee;color:#fff;font-size:11px;padding:6px 10px;border-radius:8px;white-space:nowrap;pointer-events:none;opacity:0;transition:opacity .15s;z-index:30;line-height:1.4;text-align:center;min-width:110px;}
.badge-coin:hover .badge-tip{opacity:1;}
.grad-swatch{width:38px;height:38px;border-radius:9px;cursor:pointer;border:3px solid transparent;transition:border-color .2s,transform .15s;flex-shrink:0;}
.grad-swatch:hover{transform:scale(1.12);}
.grad-swatch.sel{border-color:<?= $accentColor ?>;}
.pf-modal-overlay{position:fixed;inset:0;z-index:200;background:rgba(0,0,0,.75);backdrop-filter:blur(6px);display:flex;align-items:center;justify-content:center;padding:16px;}
.pf-modal{background:#0f1623;border:1px solid #243044;border-radius:20px;width:100%;max-width:780px;max-height:90vh;overflow-y:auto;padding:28px;}
.banner-edit-btn{position:absolute;top:12px;right:12px;z-index:10;padding:6px 12px;background:rgba(0,0,0,.55);color:#fff;border-radius:8px;font-size:12px;border:1px solid rgba(255,255,255,.15);cursor:pointer;display:flex;align-items:center;gap:6px;backdrop-filter:blur(4px);transition:background .2s;}
.banner-edit-btn:hover{background:rgba(0,0,0,.8);}
.card-theme-swatch{width:36px;height:36px;border-radius:9px;cursor:pointer;border:3px solid transparent;transition:border-color .2s,transform .15s;flex-shrink:0;}
.card-theme-swatch:hover{transform:scale(1.12);}
.card-theme-swatch.sel{border-color:<?= $accentColor ?>;}
#profile-root .glass{background:<?= $activeTheme['bg'] ?>;border-color:<?= $activeTheme['border'] ?>;}
</style>

<!-- Root Alpine scope (simple flags only, no functions with special chars) -->
<div class="max-w-5xl mx-auto px-4 pb-16" id="profile-root"
     x-data="profilePage()"
     @keydown.escape.window="badgesOpen=false;editOpen=false;bannerOpen=false">

    <!-- ===== BANNER ===== -->
    <div class="profile-banner relative" id="profile-banner" style="<?= $bannerStyle ?>">
        <button class="banner-edit-btn" @click="bannerOpen=true">
            <i data-lucide="image" class="w-4 h-4"></i> <?= t('profile.edit_banner') ?>
        </button>
    </div>

    <!-- ===== AVATAR + NAME ROW ===== -->
    <div class="relative flex flex-col sm:flex-row items-start sm:items-end gap-4 px-4 sm:px-6 -mt-10 mb-6 z-10">
        <div class="pf-avatar flex-shrink-0">
            <?php if (\App\Models\User::getAvatarUrl($user)): ?>
                <img src="<?= htmlspecialchars(\App\Models\User::getAvatarUrl($user)) ?>" alt="">
            <?php else: ?>
                <div class="pf-avatar-ph flex items-center justify-center text-3xl font-display font-bold" style="background:linear-gradient(135deg,<?= $accentColor ?>,<?= $accentColor ?>88);color:#fff">
                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="flex-1 min-w-0 pt-2">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-display font-bold text-white"><?= htmlspecialchars($user['username']) ?></h1>
                <?php if ($earnedCount > 0): ?>
                <button @click="badgesOpen=true" class="px-2 py-0.5 text-xs font-bold rounded-full hover:opacity-80 transition" style="background:<?= $accentColor ?>22;color:<?= $accentColor ?>;border:1px solid <?= $accentColor ?>44">
                    <?= $earnedCount ?> badges
                </button>
                <?php endif; ?>
            </div>
            <p class="text-xs text-dark-400 mt-0.5"><?= htmlspecialchars($user['email']) ?></p>
            <?php if (!empty($user['bio'])): ?>
            <p class="text-sm text-dark-300 mt-1 line-clamp-2"><?= htmlspecialchars($user['bio']) ?></p>
            <?php endif; ?>
            <p class="text-xs text-dark-500 mt-0.5">Joined <?= date('F Y', strtotime($user['created_at'])) ?></p>
        </div>
        <div class="flex-shrink-0 flex flex-wrap items-center gap-2 pt-2">
            <button @click="editOpen=true" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-sm font-bold transition hover:opacity-90" style="background:<?= $accentColor ?>;color:#111">
                <i data-lucide="edit-3" class="w-4 h-4"></i> Edit Profile
            </button>
            <a href="/settings" class="inline-flex items-center gap-1 px-3 py-1.5 bg-dark-700 text-dark-300 hover:text-white rounded-lg text-sm transition">
                <i data-lucide="settings" class="w-4 h-4"></i> Settings
            </a>
            <?php if ($user['is_public'] ?? false): ?>
            <a href="/user/<?= htmlspecialchars($user['username']) ?>" class="inline-flex items-center gap-1 px-3 py-1.5 bg-dark-700 text-dark-300 hover:text-white rounded-lg text-sm transition" target="_blank">
                <i data-lucide="external-link" class="w-4 h-4"></i> Public
            </a>
            <div x-data="{ copied: false }">
                <button @click="navigator.clipboard.writeText('<?= ($_ENV['APP_URL'] ?? 'https://myopcards.com') ?>/user/<?= htmlspecialchars($user['username']) ?>');copied=true;setTimeout(()=>copied=false,2000)"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-dark-700 text-dark-400 hover:text-white rounded-lg text-xs transition">
                    <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                    <span x-text="copied?'Copied!':'Copy link'"></span>
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== STATS BAR ===== -->
    <div class="flex gap-3 overflow-x-auto pb-2 mb-6" style="scrollbar-width:none">
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
            <p class="text-xl font-display font-bold text-white"><?= number_format($viewCounts['profile'] ?? 0) ?></p>
            <p class="text-[10px] text-dark-400 uppercase tracking-wider mt-1 flex items-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i> Profile</p>
        </div>
        <div class="stat-tile glass flex-shrink-0">
            <p class="text-xl font-display font-bold text-white"><?= number_format($viewCounts['collection'] ?? 0) ?></p>
            <p class="text-[10px] text-dark-400 uppercase tracking-wider mt-1 flex items-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i> Coll.</p>
        </div>
    </div>

    <!-- ===== MAIN 2-COL ===== -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- LEFT -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Collection Showcase -->
            <div class="glass rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-display font-bold text-white flex items-center gap-2">
                        <i data-lucide="folder-open" class="w-5 h-5" style="color:<?= $accentColor ?>"></i> Collection Showcase
                    </h2>
                    <a href="/collection" class="text-xs text-dark-400 hover:text-white transition">View all →</a>
                </div>
                <?php if (empty($recentCards)): ?>
                <div class="text-center py-8">
                    <p class="text-sm text-dark-400 mb-3">No cards yet.</p>
                    <a href="/cards" class="text-xs text-dark-400 hover:text-white">Browse cards</a>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                    <?php foreach (array_slice($recentCards, 0, 12) as $card): ?>
                    <a href="/cards/<?= htmlspecialchars($card['card_set_id']) ?>" class="card-show" title="<?= htmlspecialchars($card['card_name']) ?>">
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

            <!-- Game Stats -->
            <?php if ($lb && (int)$lb['games_played'] > 0): ?>
            <div class="glass rounded-2xl p-6">
                <h2 class="text-base font-display font-bold text-white flex items-center gap-2 mb-4">
                    <i data-lucide="gamepad-2" class="w-5 h-5" style="color:<?= $accentColor ?>"></i> Game Stats
                    <?php if ($lbRank): ?><span class="ml-auto text-xs px-2 py-0.5 rounded-full font-bold" style="background:<?= $accentColor ?>22;color:<?= $accentColor ?>">#<?= $lbRank ?> Ranked</span><?php endif; ?>
                </h2>
                <div class="grid grid-cols-3 sm:grid-cols-6 gap-3">
                    <?php foreach ([
                        ['ELO',number_format((int)$lb['elo_rating'])],
                        ['Wins',number_format((int)$lb['wins'])],
                        ['Losses',number_format((int)$lb['losses'])],
                        ['Draws',number_format((int)($lb['draws']??0))],
                        ['Games',number_format((int)$lb['games_played'])],
                        ['Best Streak',(int)($lb['best_streak']??0).'🔥'],
                    ] as [$lbl,$val]): ?>
                    <div class="text-center p-3 rounded-xl" style="background:rgba(255,255,255,.04)">
                        <p class="text-lg font-display font-bold text-white"><?= $val ?></p>
                        <p class="text-[10px] text-dark-400 uppercase tracking-wider mt-0.5"><?= $lbl ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php $wr = round((int)$lb['wins'] / max(1,(int)$lb['games_played']) * 100); ?>
                <div class="mt-4">
                    <div class="flex justify-between text-xs text-dark-400 mb-1"><span>Win rate</span><span><?= $wr ?>%</span></div>
                    <div class="pf-progress"><div class="pf-fill" style="width:<?= $wr ?>%"></div></div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Rarity Distribution -->
            <?php if ($rarityTotal > 0): ?>
            <div class="glass rounded-2xl p-6">
                <h2 class="text-base font-display font-bold text-white flex items-center gap-2 mb-4">
                    <i data-lucide="layers" class="w-5 h-5" style="color:<?= $accentColor ?>"></i> Rarity Breakdown
                </h2>
                <div class="flex rounded-lg overflow-hidden mb-3 h-3">
                    <?php foreach ($rarityOrder as $rarity):
                        $cnt = $rarityDist[$rarity] ?? 0;
                        if (!$cnt) continue;
                        $pct = round($cnt/$rarityTotal*100,1);
                    ?>
                    <div class="rarity-seg" style="width:<?= $pct ?>%;background:<?= $rarityColors[$rarity]??'#888' ?>" title="<?= $rarity ?>: <?= $cnt ?>"></div>
                    <?php endforeach; ?>
                </div>
                <div class="flex flex-wrap gap-x-4 gap-y-1">
                    <?php foreach ($rarityOrder as $rarity):
                        $cnt = $rarityDist[$rarity] ?? 0;
                        if (!$cnt) continue;
                    ?>
                    <div class="flex items-center gap-1.5 text-xs">
                        <span class="w-2.5 h-2.5 rounded-sm" style="background:<?= $rarityColors[$rarity]??'#888' ?>"></span>
                        <span class="text-dark-300"><?= $rarity ?></span>
                        <span class="text-dark-500"><?= number_format($cnt) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Set Completion -->
            <?php
            $topSets = array_filter($setCompletion ?? [], fn($s) => (int)$s['owned'] > 0);
            usort($topSets, fn($a,$b) => $b['owned']-$a['owned']);
            $topSets = array_slice($topSets, 0, 5);
            if ($topSets):
            ?>
            <div class="glass rounded-2xl p-6">
                <h2 class="text-base font-display font-bold text-white flex items-center gap-2 mb-4">
                    <i data-lucide="package" class="w-5 h-5" style="color:<?= $accentColor ?>"></i> Set Completion
                </h2>
                <div class="space-y-3">
                    <?php foreach ($topSets as $set):
                        $tot = (int)($set['card_count']??0);
                        $own = (int)($set['owned']??0);
                        $pct = $tot>0 ? min(100,round($own/$tot*100)) : 0;
                    ?>
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-dark-200 truncate max-w-[200px]"><?= htmlspecialchars($set['set_name']??$set['set_id']) ?></span>
                            <span class="text-dark-400 flex-shrink-0 ml-2"><?= $own ?>/<?= $tot ?> · <span style="color:<?= $accentColor ?>"><?= $pct ?>%</span></span>
                        </div>
                        <div class="pf-progress"><div class="pf-fill" style="width:<?= $pct ?>%"></div></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Forum Activity -->
            <?php if (!empty($recentActivity)): ?>
            <div class="glass rounded-2xl p-6">
                <h2 class="text-base font-display font-bold text-white flex items-center gap-2 mb-4">
                    <i data-lucide="message-square" class="w-5 h-5" style="color:<?= $accentColor ?>"></i> Recent Forum Activity
                </h2>
                <div class="space-y-2">
                    <?php foreach ($recentActivity as $act): ?>
                    <a href="/forum/<?= htmlspecialchars($act['category_slug']) ?>/<?= $act['type']==='topic' ? $act['id'] : $act['topic_id'] ?>-<?= htmlspecialchars($act['slug']) ?>"
                       class="flex items-center gap-3 p-2.5 rounded-xl hover:bg-white/5 transition group">
                        <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 <?= $act['type']==='topic' ? 'bg-blue-900/30 text-blue-400' : 'bg-green-900/30 text-green-400' ?>">
                            <i data-lucide="<?= $act['type']==='topic' ? 'plus' : 'message-circle' ?>" class="w-3.5 h-3.5"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-dark-200 group-hover:text-white transition truncate"><?= htmlspecialchars($act['title']) ?></p>
                            <p class="text-xs text-dark-500"><?= $act['type']==='topic' ? 'Created' : 'Replied' ?> · <?= date('M j, H:i', strtotime($act['created_at'])) ?></p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-dark-600 group-hover:text-dark-400 flex-shrink-0"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Visitors -->
            <?php if (!empty($recentViewers)): ?>
            <div class="glass rounded-2xl p-6">
                <h2 class="text-base font-display font-bold text-white flex items-center gap-2 mb-4">
                    <i data-lucide="eye" class="w-5 h-5" style="color:<?= $accentColor ?>"></i> Recent Visitors
                </h2>
                <div class="space-y-2">
                    <?php foreach ($recentViewers as $v): ?>
                    <div class="flex items-center gap-3 p-2 rounded-xl">
                        <?php if (\App\Models\User::getAvatarUrl($v)): ?>
                            <img src="<?= htmlspecialchars(\App\Models\User::getAvatarUrl($v)) ?>" class="w-8 h-8 rounded-full flex-shrink-0" alt="">
                        <?php else: ?>
                            <div class="w-8 h-8 rounded-full bg-dark-700 flex items-center justify-center text-xs font-bold text-dark-300 flex-shrink-0"><?= strtoupper(substr($v['username']??'?',0,1)) ?></div>
                        <?php endif; ?>
                        <div class="flex-1 min-w-0">
                            <a href="/user/<?= htmlspecialchars($v['username']??'') ?>" class="text-sm font-medium text-dark-200 hover:text-white transition"><?= htmlspecialchars($v['username']??'Anonymous') ?></a>
                            <p class="text-[10px] text-dark-500">viewed your <?= htmlspecialchars($v['page_type']) ?></p>
                        </div>
                        <span class="text-[10px] text-dark-500 flex-shrink-0"><?= date('M j, H:i', strtotime($v['viewed_at'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /LEFT -->

        <!-- RIGHT SIDEBAR -->
        <div class="space-y-6">

            <!-- Badges sidebar widget -->
            <div class="glass rounded-2xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-display font-bold text-white flex items-center gap-2">
                        <i data-lucide="award" class="w-4 h-4" style="color:<?= $accentColor ?>"></i>
                        Achievements <span class="text-dark-400 font-normal text-xs">(<?= $earnedCount ?>/<?= count($allBadges) ?>)</span>
                    </h2>
                    <button @click="badgesOpen=true" class="text-xs text-dark-400 hover:text-white transition">View all →</button>
                </div>
                <!-- Show first 9 earned badges -->
                <?php
                $earned9 = array_filter($allBadges, fn($b) => ($earnedBadges[$b['id']] ?? null) !== null);
                $earned9 = array_slice(array_values($earned9), 0, 9);
                if (empty($earned9)):
                ?>
                <p class="text-xs text-dark-500 text-center py-4">No badges yet — keep playing!</p>
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

            <!-- Friends -->
            <div class="glass rounded-2xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-display font-bold text-white flex items-center gap-2">
                        <i data-lucide="users" class="w-4 h-4" style="color:<?= $accentColor ?>"></i>
                        Friends <span class="text-dark-400 font-normal text-xs">(<?= $friendCount ?>)</span>
                    </h2>
                    <a href="/friends" class="text-xs text-dark-500 hover:text-white transition">View all</a>
                </div>
                <?php if (empty($friends)): ?>
                <p class="text-xs text-dark-500 text-center py-4">No friends yet.</p>
                <a href="/friends" class="block text-center text-xs text-dark-500 hover:text-white mt-1">Find people</a>
                <?php else: ?>
                <div class="space-y-1.5">
                    <?php foreach (array_slice($friends, 0, 8) as $f): ?>
                    <a href="/user/<?= htmlspecialchars($f['username']) ?>" class="flex items-center gap-2.5 p-1.5 rounded-lg hover:bg-white/5 transition group">
                        <?php if (\App\Models\User::getAvatarUrl($f)): ?>
                        <img src="<?= htmlspecialchars(\App\Models\User::getAvatarUrl($f)) ?>" class="w-7 h-7 rounded-full flex-shrink-0" alt="">
                        <?php else: ?>
                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0" style="background:<?= $accentColor ?>33;color:<?= $accentColor ?>"><?= strtoupper(substr($f['username'],0,1)) ?></div>
                        <?php endif; ?>
                        <span class="text-sm text-dark-200 group-hover:text-white truncate"><?= htmlspecialchars($f['username']) ?></span>
                    </a>
                    <?php endforeach; ?>
                    <?php if (count($friends) > 8): ?>
                    <a href="/friends" class="block text-center text-xs text-dark-500 hover:text-white pt-2">+<?= count($friends)-8 ?> more</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Forum mini stats -->
            <?php if (($forumStats['post_count']??0) > 0 || ($forumStats['topic_count']??0) > 0): ?>
            <div class="glass rounded-2xl p-5">
                <h2 class="text-sm font-display font-bold text-white flex items-center gap-2 mb-3">
                    <i data-lucide="message-circle" class="w-4 h-4" style="color:<?= $accentColor ?>"></i> Forum
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <div class="text-center p-3 rounded-xl" style="background:rgba(255,255,255,.04)">
                        <p class="text-xl font-bold text-white"><?= number_format($forumStats['topic_count']??0) ?></p>
                        <p class="text-[10px] text-dark-400 uppercase tracking-wider mt-0.5">Topics</p>
                    </div>
                    <div class="text-center p-3 rounded-xl" style="background:rgba(255,255,255,.04)">
                        <p class="text-xl font-bold text-white"><?= number_format($forumStats['post_count']??0) ?></p>
                        <p class="text-[10px] text-dark-400 uppercase tracking-wider mt-0.5">Posts</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /SIDEBAR -->
    </div><!-- /grid -->

    <!-- ===== BADGES POPUP ===== -->
    <div class="pf-modal-overlay" x-show="badgesOpen" x-cloak @click.self="badgesOpen=false"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="pf-modal" @click.stop>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-display font-bold text-white flex items-center gap-2">
                    <i data-lucide="award" class="w-6 h-6" style="color:<?= $accentColor ?>"></i>
                    All Achievements
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
                    <div class="badge-coin <?= $earned ? 'earned' : 'locked' ?>" title="<?= htmlspecialchars($badge['name']) ?>">
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

    <!-- ===== EDIT PROFILE POPUP ===== -->
    <div class="pf-modal-overlay" x-show="editOpen" x-cloak @click.self="editOpen=false"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="pf-modal" @click.stop style="max-width:560px">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-display font-bold text-white flex items-center gap-2">
                    <i data-lucide="edit-3" class="w-6 h-6" style="color:<?= $accentColor ?>"></i> Edit Profile
                </h2>
                <button @click="editOpen=false" class="text-dark-400 hover:text-white transition p-1 rounded-lg hover:bg-dark-700">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" action="/profile/update" class="space-y-5">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-dark-400 mb-2">Bio</label>
                    <textarea name="bio" rows="3" maxlength="300"
                        class="w-full px-4 py-3 bg-dark-800 border border-dark-600 rounded-xl text-white placeholder-dark-500 focus:outline-none focus:border-dark-400 transition text-sm resize-none"
                        placeholder="Tell us about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_public" id="is_public2" value="1" <?= ($user['is_public']??0) ? 'checked' : '' ?> class="w-4 h-4 rounded">
                    <label for="is_public2" class="text-sm text-dark-300">Make my profile and collection public</label>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-dark-400 mb-2">Accent Color</label>
                    <div class="flex items-center gap-3">
                        <input type="color" name="profile_accent_color" value="<?= htmlspecialchars($user['profile_accent_color'] ?? '#d4a853') ?>"
                            class="w-10 h-10 rounded-lg border border-dark-600 cursor-pointer bg-dark-800">
                        <span class="text-xs text-dark-400">Color for your profile highlights and badges ring</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-dark-400 mb-2">Banner Gradient</label>
                    <div class="flex flex-wrap gap-2 mb-1">
                        <?php foreach ($gradientOptions as $gKey => $gOpt): ?>
                        <label class="relative cursor-pointer" title="<?= htmlspecialchars($gOpt['label']) ?>">
                            <input type="radio" name="banner_gradient" value="<?= $gKey ?>" class="sr-only peer"
                                <?= $currentGradient === $gKey ? 'checked' : '' ?>>
                            <div class="grad-swatch peer-checked:sel" style="background:<?= $gOpt['preview'] ?>;border-color:<?= $currentGradient === $gKey ? $accentColor : 'transparent' ?>"
                                 onclick="this.parentElement.querySelectorAll('.grad-swatch').forEach(s=>s.style.borderColor='transparent');this.style.borderColor='<?= $accentColor ?>'"></div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-xs text-dark-500">Only applies when no banner image is uploaded</p>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-dark-400 mb-2">Block Style</label>
                    <p class="text-xs text-dark-500 mb-3">Color theme for your profile cards and panels</p>
                    <div class="grid grid-cols-6 gap-2">
                        <?php foreach ($blockThemes as $key => $theme): ?>
                        <label class="relative cursor-pointer group" title="<?= htmlspecialchars($theme['label']) ?>">
                            <input type="radio" name="card_style" value="<?= $key ?>" class="sr-only pf-card-style-radio"
                                <?= $currentCardStyle === $key ? 'checked' : '' ?>>
                            <div class="card-theme-swatch <?= $currentCardStyle === $key ? 'sel' : '' ?>"
                                 style="background:<?= $theme['preview'] ?>;border:3px solid <?= $currentCardStyle === $key ? $accentColor : 'rgba(255,255,255,0.08)' ?>;box-shadow:inset 0 0 0 1px <?= $theme['border'] ?>"></div>
                            <p class="text-[9px] text-dark-500 mt-1 text-center leading-tight truncate"><?= htmlspecialchars($theme['label']) ?></p>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-dark-400 mb-2">Profile Photo</label>
                    <a href="/settings" class="inline-flex items-center gap-2 text-xs text-dark-400 hover:text-white transition">
                        <i data-lucide="camera" class="w-3.5 h-3.5"></i> Change photo in Settings →
                    </a>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="submit" class="px-6 py-2.5 rounded-xl font-bold text-sm transition hover:opacity-90 flex-1" style="background:<?= $accentColor ?>;color:#111">Save Changes</button>
                    <button type="button" @click="editOpen=false" class="px-4 py-2.5 rounded-xl text-sm bg-dark-700 text-dark-300 hover:text-white transition">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== BANNER EDIT POPUP ===== -->
    <div class="pf-modal-overlay" x-show="bannerOpen" x-cloak @click.self="bannerOpen=false"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="pf-modal" @click.stop style="max-width:540px">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-display font-bold text-white flex items-center gap-2">
                    <i data-lucide="image" class="w-6 h-6" style="color:<?= $accentColor ?>"></i> Customize Banner
                </h2>
                <button @click="bannerOpen=false" class="text-dark-400 hover:text-white transition p-1 rounded-lg hover:bg-dark-700">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Upload banner -->
            <div class="mb-6">
                <p class="text-xs font-bold uppercase tracking-wider text-dark-400 mb-3">Upload Image</p>
                <label class="flex items-center justify-center gap-3 p-6 border-2 border-dashed border-dark-600 rounded-xl cursor-pointer hover:border-dark-400 transition" id="banner-upload-label">
                    <i data-lucide="upload-cloud" class="w-6 h-6 text-dark-400"></i>
                    <span class="text-sm text-dark-300" id="banner-upload-text">Click to upload (JPG, PNG, WebP · max 5MB)</span>
                    <input type="file" id="banner-file-input" accept="image/jpeg,image/png,image/webp" class="hidden">
                </label>
                <?php if (!empty($user['banner_image'])): ?>
                <form method="POST" action="/profile/banner/remove" class="mt-2">
                    <?= csrf_field() ?>
                    <button type="submit" class="text-xs text-red-400 hover:text-red-300 transition flex items-center gap-1">
                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Remove current banner image
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <!-- Gradient presets -->
            <div class="mb-6">
                <p class="text-xs font-bold uppercase tracking-wider text-dark-400 mb-3">Or Choose a Gradient</p>
                <form method="POST" action="/profile/update" id="banner-gradient-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="bio" value="<?= htmlspecialchars($user['bio'] ?? '') ?>">
                    <input type="hidden" name="is_public" value="<?= ($user['is_public']??0) ? '1' : '' ?>">
                    <input type="hidden" name="profile_accent_color" value="<?= htmlspecialchars($user['profile_accent_color'] ?? '#d4a853') ?>">
                    <div class="flex flex-wrap gap-3 mb-4">
                        <?php foreach ($gradientOptions as $gKey => $gOpt): ?>
                        <label class="relative cursor-pointer group" title="<?= htmlspecialchars($gOpt['label']) ?>">
                            <input type="radio" name="banner_gradient" value="<?= $gKey ?>" class="sr-only" <?= $currentGradient === $gKey ? 'checked' : '' ?>>
                            <div class="grad-swatch <?= $currentGradient === $gKey ? 'sel' : '' ?>"
                                 style="background:<?= $gOpt['preview'] ?>;<?= $currentGradient === $gKey ? 'border-color:'.$accentColor.';' : '' ?>"></div>
                            <p class="text-[9px] text-dark-500 mt-1 text-center max-w-[38px] leading-tight"><?= htmlspecialchars($gOpt['label']) ?></p>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="px-5 py-2 rounded-xl font-bold text-sm transition hover:opacity-90" style="background:<?= $accentColor ?>;color:#111">Apply Gradient</button>
                </form>
            </div>
        </div>
    </div>

</div><!-- /profile-root -->

<script>
// Alpine component
document.addEventListener('alpine:init', function() {
    Alpine.data('profilePage', function() {
        return {
            badgesOpen: false,
            editOpen: false,
            bannerOpen: false,
        };
    });
});

// Badge 3D script
document.addEventListener('DOMContentLoaded', function() {
    if (typeof initBadge3D === 'function') initBadge3D();

    // Gradient swatch radio sync
    document.querySelectorAll('input[name="banner_gradient"]').forEach(function(r) {
        r.addEventListener('change', function() {
            document.querySelectorAll('.grad-swatch').forEach(function(s) {
                s.style.borderColor = 'transparent';
                s.classList.remove('sel');
            });
            var swatch = r.closest('label').querySelector('.grad-swatch');
            if (swatch) {
                swatch.style.borderColor = '<?= $accentColor ?>';
                swatch.classList.add('sel');
            }
        });
    });

    // Card style swatch radio sync
    document.querySelectorAll('.pf-card-style-radio').forEach(function(r) {
        r.addEventListener('change', function() {
            document.querySelectorAll('.card-theme-swatch').forEach(function(s) {
                s.style.borderColor = 'rgba(255,255,255,0.08)';
                s.classList.remove('sel');
            });
            var swatch = r.closest('label').querySelector('.card-theme-swatch');
            if (swatch) {
                swatch.style.borderColor = '<?= $accentColor ?>';
                swatch.classList.add('sel');
            }
        });
    });

    // Banner file upload via JS fetch
    var bannerInput = document.getElementById('banner-file-input');
    if (bannerInput) {
        bannerInput.addEventListener('change', function() {
            var file = this.files[0];
            if (!file) return;
            var label = document.getElementById('banner-upload-label');
            var text = document.getElementById('banner-upload-text');
            if (text) text.textContent = 'Uploading...';
            if (label) label.style.opacity = '0.6';
            var fd = new FormData();
            fd.append('banner', file);
            var token = document.querySelector('meta[name="csrf-token"]');
            if (token) fd.append('csrf_token', token.getAttribute('content'));
            fetch('/profile/banner', {method:'POST', body: fd})
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d.success) {
                        location.reload();
                    } else {
                        alert(d.error || 'Upload failed');
                        if (text) text.textContent = 'Click to upload (JPG, PNG, WebP · max 5MB)';
                        if (label) label.style.opacity = '1';
                    }
                })
                .catch(function() {
                    alert('Upload failed');
                    if (text) text.textContent = 'Click to upload (JPG, PNG, WebP · max 5MB)';
                    if (label) label.style.opacity = '1';
                });
        });
    }
});
</script>
<script src="/assets/js/badge-3d.js"></script>
