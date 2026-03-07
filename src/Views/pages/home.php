<div class="home-hero">
    <div class="home-content flex flex-col items-center justify-center min-h-screen px-4 text-center">

        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full glass text-gray-500 text-xs font-bold uppercase tracking-widest mb-8">
            One Piece TCG Collection Manager
        </div>

        <h1 class="home-title font-display font-black">
            <span class="text-gray-300">My</span><span class="gradient-text">OP</span><span class="text-gray-300">Cards</span>
        </h1>

        <p class="mt-6 text-lg md:text-xl text-gray-500 max-w-2xl leading-relaxed font-light">
            Track your collection and play online. Real-time prices, deck builder, ranked matches, and a collector community.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 mt-10">
            <a href="<?= \App\Core\Auth::check() ? '/collection' : '/register' ?>"
               class="group px-10 py-4 bg-gray-900 rounded-2xl text-lg font-display font-bold hover:bg-gray-800 transition-all shadow-xl hover:shadow-2xl hover:-translate-y-0.5 flex items-center gap-2" style="color:#fff !important">
                Track Your Collection
                <i data-lucide="layers" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
            </a>
            <a href="/play"
               class="group px-10 py-4 rounded-2xl text-lg font-display font-bold border-2 border-amber-500 text-amber-600 hover:bg-amber-500 hover:text-gray-900 transition-all flex items-center gap-2">
                Play Online
                <i data-lucide="gamepad-2" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 mt-16 max-w-2xl mx-auto w-full">
            <div class="stat-pill rounded-2xl p-4 sm:p-5 text-center">
                <p class="text-2xl sm:text-3xl font-display font-bold text-gray-900"><?= number_format($totalCards ?? 0) ?></p>
                <p class="text-[11px] sm:text-xs text-gray-400 mt-1 uppercase tracking-wider font-medium">Cards</p>
            </div>
            <div class="stat-pill rounded-2xl p-4 sm:p-5 text-center">
                <p class="text-2xl sm:text-3xl font-display font-bold text-gray-900"><?= number_format($userCount ?? 0) ?></p>
                <p class="text-[11px] sm:text-xs text-gray-400 mt-1 uppercase tracking-wider font-medium">Collectors</p>
            </div>
            <div class="stat-pill rounded-2xl p-4 sm:p-5 text-center">
                <p class="text-2xl sm:text-3xl font-display font-bold text-gray-900"><?= number_format($totalMatches ?? 0) ?></p>
                <p class="text-[11px] sm:text-xs text-gray-400 mt-1 uppercase tracking-wider font-medium">Matches played</p>
            </div>
            <div class="stat-pill rounded-2xl p-4 sm:p-5 text-center">
                <p class="text-2xl sm:text-3xl font-display font-bold text-gray-900"><?= number_format($activeGames ?? 0) ?></p>
                <p class="text-[11px] sm:text-xs text-gray-400 mt-1 uppercase tracking-wider font-medium">Active games</p>
            </div>
        </div>
    </div>
</div>

<div class="relative z-10 pb-16 max-w-6xl mx-auto px-4">

    <div class="text-center mb-12 mt-4">
        <h2 class="text-3xl md:text-4xl font-display font-bold text-gray-900">Everything You Need</h2>
        <p class="text-gray-500 mt-3 max-w-xl mx-auto">A complete toolkit built by collectors, for collectors.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="feat-card rounded-2xl p-8">
            <h3 class="text-xl font-display font-bold text-gray-900 mb-2">Complete Database</h3>
            <p class="text-gray-500 text-sm leading-relaxed">All <?= number_format($totalCards ?? 0) ?> cards from every booster, starter deck, and promo release, with high-res images and full stats.</p>
        </div>
        <div class="feat-card rounded-2xl p-8">
            <h3 class="text-xl font-display font-bold text-gray-900 mb-2">Dual Price Tracking</h3>
            <p class="text-gray-500 text-sm leading-relaxed">Live prices from TCGPlayer (USD) and Cardmarket (EUR). Price history charts and daily smart updates on high-value cards.</p>
        </div>
        <div class="feat-card rounded-2xl p-8">
            <h3 class="text-xl font-display font-bold text-gray-900 mb-2">Deep Analytics</h3>
            <p class="text-gray-500 text-sm leading-relaxed">Track your collection value over time, see distribution charts, set completion heatmaps, and identify price movers.</p>
        </div>
        <div class="feat-card rounded-2xl p-8">
            <h3 class="text-xl font-display font-bold text-gray-900 mb-2">Play Online</h3>
            <p class="text-gray-500 text-sm leading-relaxed">Queue for ranked or casual matches, play vs bot, or create custom rooms. Full OPTCG rules and card effects.</p>
        </div>
        <div class="feat-card rounded-2xl p-8">
            <h3 class="text-xl font-display font-bold text-gray-900 mb-2">Deck Builder &amp; Leaderboard</h3>
            <p class="text-gray-500 text-sm leading-relaxed">Build decks with 1 Leader and 50 cards. Climb the ELO leaderboard and see your wins, losses, and streak.</p>
        </div>
        <div class="feat-card rounded-2xl p-8">
            <h3 class="text-xl font-display font-bold text-gray-900 mb-2">Market Insights</h3>
            <p class="text-gray-500 text-sm leading-relaxed">Top gainers, losers, most expensive cards, and set value summaries. Stay ahead of the market with daily updates.</p>
        </div>
    </div>

    <?php if (!empty($leaderboardTop)): ?>
    <div class="mt-16">
        <h2 class="text-2xl font-display font-bold text-gray-900 text-center mb-6">Top players</h2>
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
            <a href="/leaderboard" class="block text-center mt-4 text-amber-600 hover:text-amber-700 font-medium text-sm">View full leaderboard</a>
        </div>
    </div>
    <?php endif; ?>

    <div class="mt-16 text-center">
        <div class="feat-card rounded-3xl p-10 sm:p-14 max-w-3xl mx-auto relative overflow-hidden">
            <div class="relative z-10">
                <h3 class="text-2xl md:text-3xl font-display font-bold text-gray-900 mb-3">Ready to start?</h3>
                <p class="text-gray-500 mb-8 max-w-lg mx-auto">Track your collection and play online. Free forever, no hidden costs.</p>
                <a href="<?= \App\Core\Auth::check() ? '/dashboard' : '/register' ?>"
                   class="inline-flex items-center gap-2 px-10 py-4 bg-gray-900 rounded-2xl text-lg font-display font-bold hover:bg-gray-800 transition-all shadow-xl" style="color:#fff !important">
                    <?= \App\Core\Auth::check() ? 'Go to Dashboard' : 'Create Free Account' ?>
                </a>
            </div>
        </div>
    </div>
</div>
