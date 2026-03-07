<div class="home-hero">
    <div class="home-content flex flex-col items-center justify-center min-h-screen px-4 text-center">

        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full glass text-gray-500 text-xs font-bold uppercase tracking-widest mb-8">
            <?= t('home.tagline') ?>
        </div>

        <h1 class="home-title font-display font-black">
            <span class="text-gray-300">My</span><span class="gradient-text">OP</span><span class="text-gray-300">Cards</span>
        </h1>

        <p class="mt-6 text-lg md:text-xl text-gray-500 max-w-2xl leading-relaxed font-light">
            <?= t('home.hero_subtitle') ?>
        </p>

        <div class="flex flex-col sm:flex-row gap-4 mt-10">
            <a href="<?= \App\Core\Auth::check() ? '/collection' : '/register' ?>"
               class="group px-10 py-4 bg-gray-900 rounded-2xl text-lg font-display font-bold hover:bg-gray-800 transition-all shadow-xl hover:shadow-2xl hover:-translate-y-0.5 flex items-center gap-2" style="color:#fff !important">
                <?= t('home.track_collection') ?>
                <i data-lucide="layers" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
            </a>
            <a href="/play"
               class="group px-10 py-4 rounded-2xl text-lg font-display font-bold border-2 border-amber-500 text-amber-600 hover:bg-amber-500 hover:text-gray-900 transition-all flex items-center gap-2">
                <?= t('home.play_online') ?>
                <i data-lucide="gamepad-2" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 mt-16 max-w-2xl mx-auto w-full">
            <div class="stat-pill rounded-2xl p-4 sm:p-5 text-center">
                <p class="text-2xl sm:text-3xl font-display font-bold text-gray-900"><?= number_format($totalCards ?? 0) ?></p>
                <p class="text-[11px] sm:text-xs text-gray-400 mt-1 uppercase tracking-wider font-medium"><?= t('home.cards') ?></p>
            </div>
            <div class="stat-pill rounded-2xl p-4 sm:p-5 text-center">
                <p class="text-2xl sm:text-3xl font-display font-bold text-gray-900"><?= number_format($userCount ?? 0) ?></p>
                <p class="text-[11px] sm:text-xs text-gray-400 mt-1 uppercase tracking-wider font-medium"><?= t('home.collectors') ?></p>
            </div>
            <div class="stat-pill rounded-2xl p-4 sm:p-5 text-center">
                <p class="text-2xl sm:text-3xl font-display font-bold text-gray-900"><?= number_format($totalMatches ?? 0) ?></p>
                <p class="text-[11px] sm:text-xs text-gray-400 mt-1 uppercase tracking-wider font-medium"><?= t('home.matches_played') ?></p>
            </div>
            <div class="stat-pill rounded-2xl p-4 sm:p-5 text-center">
                <p class="text-2xl sm:text-3xl font-display font-bold text-gray-900"><?= number_format($activeGames ?? 0) ?></p>
                <p class="text-[11px] sm:text-xs text-gray-400 mt-1 uppercase tracking-wider font-medium"><?= t('home.active_games') ?></p>
            </div>
        </div>
    </div>
</div>

<div class="relative z-10 pb-16 max-w-6xl mx-auto px-4">

    <div class="text-center mb-12 mt-4">
        <h2 class="text-3xl md:text-4xl font-display font-bold text-gray-900"><?= t('home.everything_you_need') ?></h2>
        <p class="text-gray-500 mt-3 max-w-xl mx-auto"><?= t('home.everything_subtitle') ?></p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="feat-card rounded-2xl p-8">
            <h3 class="text-xl font-display font-bold text-gray-900 mb-2"><?= t('home.feat_database') ?></h3>
            <p class="text-gray-500 text-sm leading-relaxed"><?= t('home.feat_database_desc', ['%count%' => number_format($totalCards ?? 0)]) ?></p>
        </div>
        <div class="feat-card rounded-2xl p-8">
            <h3 class="text-xl font-display font-bold text-gray-900 mb-2"><?= t('home.feat_prices') ?></h3>
            <p class="text-gray-500 text-sm leading-relaxed"><?= t('home.feat_prices_desc') ?></p>
        </div>
        <div class="feat-card rounded-2xl p-8">
            <h3 class="text-xl font-display font-bold text-gray-900 mb-2"><?= t('home.feat_analytics') ?></h3>
            <p class="text-gray-500 text-sm leading-relaxed"><?= t('home.feat_analytics_desc') ?></p>
        </div>
        <div class="feat-card rounded-2xl p-8">
            <h3 class="text-xl font-display font-bold text-gray-900 mb-2"><?= t('home.feat_play') ?></h3>
            <p class="text-gray-500 text-sm leading-relaxed"><?= t('home.feat_play_desc') ?></p>
        </div>
        <div class="feat-card rounded-2xl p-8">
            <h3 class="text-xl font-display font-bold text-gray-900 mb-2"><?= t('home.feat_deck') ?></h3>
            <p class="text-gray-500 text-sm leading-relaxed"><?= t('home.feat_deck_desc') ?></p>
        </div>
        <div class="feat-card rounded-2xl p-8">
            <h3 class="text-xl font-display font-bold text-gray-900 mb-2"><?= t('home.feat_market') ?></h3>
            <p class="text-gray-500 text-sm leading-relaxed"><?= t('home.feat_market_desc') ?></p>
        </div>
    </div>

    <?php if (!empty($leaderboardTop)): ?>
    <div class="mt-16">
        <h2 class="text-2xl font-display font-bold text-gray-900 text-center mb-6"><?= t('home.top_players') ?></h2>
        <div class="feat-card rounded-2xl p-6 max-w-md mx-auto">
            <ul class="space-y-2">
                <?php foreach (array_values($leaderboardTop) as $i => $row): ?>
                <li class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                    <span class="font-bold text-gray-500 w-8"><?= $i + 1 ?></span>
                    <span class="text-gray-900 font-medium flex-1"><?= htmlspecialchars($row['username'] ?? '') ?></span>
                    <span class="text-amber-600 font-semibold"><?= (int)($row['elo_rating'] ?? 0) ?> ELO</span>
                </li>
                <?php endforeach; ?>
            </ul>
            <a href="/leaderboard" class="block text-center mt-4 text-amber-600 hover:text-amber-700 font-medium text-sm"><?= t('home.view_full_leaderboard') ?></a>
        </div>
    </div>
    <?php endif; ?>

    <div class="mt-16 text-center">
        <div class="feat-card rounded-3xl p-10 sm:p-14 max-w-3xl mx-auto relative overflow-hidden">
            <div class="relative z-10">
                <h3 class="text-2xl md:text-3xl font-display font-bold text-gray-900 mb-3"><?= t('home.ready_title') ?></h3>
                <p class="text-gray-500 mb-8 max-w-lg mx-auto"><?= t('home.ready_subtitle') ?></p>
                <a href="<?= \App\Core\Auth::check() ? '/dashboard' : '/register' ?>"
                   class="inline-flex items-center gap-2 px-10 py-4 bg-gray-900 rounded-2xl text-lg font-display font-bold hover:bg-gray-800 transition-all shadow-xl" style="color:#fff !important">
                    <?= \App\Core\Auth::check() ? t('home.go_dashboard') : t('home.create_account') ?>
                </a>
            </div>
        </div>
    </div>
</div>
