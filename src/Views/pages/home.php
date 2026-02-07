<?php
$cards = $showcaseCards ?? [];
$row1 = array_slice($cards, 0, 12);
$row2 = array_slice($cards, 12, 12);
$row3 = array_slice($cards, 24, 12);
$row4 = array_slice($cards, 36, 12);
?>

<style>
    .home-hero {
        position: relative;
        min-height: 100vh;
        width: 100%;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .carousel-bg {
        position: absolute;
        inset: 0;
        opacity: 0.35;
        pointer-events: none;
        z-index: 1;
    }

    .carousel-row {
        position: absolute;
        left: 0;
        display: flex;
        width: max-content;
    }
    .carousel-row-1 { top: 3%; }
    .carousel-row-2 { top: 28%; }
    .carousel-row-3 { top: 53%; }
    .carousel-row-4 { top: 78%; }

    .carousel-row .carousel-track {
        display: flex;
        gap: 1.25rem;
        will-change: transform;
    }

    .carousel-track-left  { animation: drift-left  var(--speed, 60s) linear infinite; }
    .carousel-track-right { animation: drift-right var(--speed, 60s) linear infinite; }

    @keyframes drift-left  { from { transform: translateX(0); }  to { transform: translateX(-50%); } }
    @keyframes drift-right { from { transform: translateX(-50%); } to { transform: translateX(0); } }

    .carousel-track img {
        width: 130px;
        height: 182px;
        object-fit: cover;
        border-radius: 10px;
        flex-shrink: 0;
        box-shadow: 0 6px 24px rgba(0,0,0,0.6);
    }

    .carousel-row-1 .carousel-track { --speed: 50s; }
    .carousel-row-2 .carousel-track { --speed: 65s; }
    .carousel-row-3 .carousel-track { --speed: 42s; }
    .carousel-row-4 .carousel-track { --speed: 58s; }

    .carousel-row-1 .carousel-track img:nth-child(odd)  { transform: rotate(-4deg) translateY(4px); }
    .carousel-row-1 .carousel-track img:nth-child(even) { transform: rotate(3deg) translateY(-6px); }
    .carousel-row-2 .carousel-track img:nth-child(odd)  { transform: rotate(5deg) translateY(-4px); }
    .carousel-row-2 .carousel-track img:nth-child(even) { transform: rotate(-3deg) translateY(8px); }
    .carousel-row-3 .carousel-track img:nth-child(3n)   { transform: rotate(-6deg) translateY(6px); }
    .carousel-row-3 .carousel-track img:nth-child(3n+1) { transform: rotate(2deg) translateY(-3px); }
    .carousel-row-3 .carousel-track img:nth-child(3n+2) { transform: rotate(-2deg) translateY(10px); }
    .carousel-row-4 .carousel-track img:nth-child(odd)  { transform: rotate(4deg) translateY(-5px); }
    .carousel-row-4 .carousel-track img:nth-child(even) { transform: rotate(-5deg) translateY(8px); }

    .home-overlay {
        position: absolute;
        inset: 0;
        z-index: 2;
        pointer-events: none;
        background:
            radial-gradient(ellipse 80% 50% at 50% 45%, rgba(6,8,13,0.5) 0%, rgba(6,8,13,0.78) 60%, rgba(6,8,13,0.97) 100%),
            linear-gradient(180deg, rgba(6,8,13,0.4) 0%, rgba(6,8,13,0.05) 35%, rgba(6,8,13,0.05) 65%, rgba(6,8,13,0.97) 100%);
    }

    .home-glow {
        position: absolute;
        width: 500px;
        height: 500px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(212,168,83,0.12), transparent 70%);
        top: 35%;
        left: 50%;
        transform: translate(-50%, -50%);
        pointer-events: none;
        z-index: 2;
        animation: glow-pulse 4s ease-in-out infinite;
    }
    @keyframes glow-pulse {
        0%, 100% { opacity: 0.5; transform: translate(-50%, -50%) scale(1); }
        50%      { opacity: 1;   transform: translate(-50%, -50%) scale(1.2); }
    }

    .home-content {
        position: relative;
        z-index: 10;
    }

    .home-title {
        font-size: clamp(3rem, 8vw, 7rem);
        line-height: 1;
        letter-spacing: -0.03em;
    }

    .stat-pill {
        background: rgba(17,24,39,0.65);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(212,168,83,0.12);
        transition: all 0.3s;
    }
    .stat-pill:hover {
        border-color: rgba(212,168,83,0.35);
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(212,168,83,0.08);
    }

    .feat-card {
        background: rgba(17,24,39,0.55);
        backdrop-filter: blur(16px);
        border: 1px solid rgba(74,100,128,0.12);
        transition: all 0.35s cubic-bezier(.4,0,.2,1);
    }
    .feat-card:hover {
        border-color: rgba(212,168,83,0.25);
        transform: translateY(-5px);
        box-shadow: 0 16px 48px rgba(0,0,0,0.25);
    }

    @media (max-width: 640px) {
        .carousel-track img { width: 90px; height: 126px; }
        .carousel-track     { gap: 0.75rem; }
    }
</style>

<!-- ============ HERO ============ -->
<div class="home-hero">

    <!-- Animated card rows -->
    <div class="carousel-bg" aria-hidden="true">
        <?php if (!empty($row1)): ?>
        <div class="carousel-row carousel-row-1">
            <div class="carousel-track carousel-track-left">
                <?php foreach ($row1 as $img): ?><img src="<?= htmlspecialchars($img) ?>" alt="" loading="lazy"><?php endforeach; ?>
                <?php foreach ($row1 as $img): ?><img src="<?= htmlspecialchars($img) ?>" alt="" loading="lazy"><?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($row2)): ?>
        <div class="carousel-row carousel-row-2">
            <div class="carousel-track carousel-track-right">
                <?php foreach ($row2 as $img): ?><img src="<?= htmlspecialchars($img) ?>" alt="" loading="lazy"><?php endforeach; ?>
                <?php foreach ($row2 as $img): ?><img src="<?= htmlspecialchars($img) ?>" alt="" loading="lazy"><?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($row3)): ?>
        <div class="carousel-row carousel-row-3">
            <div class="carousel-track carousel-track-left">
                <?php foreach ($row3 as $img): ?><img src="<?= htmlspecialchars($img) ?>" alt="" loading="lazy"><?php endforeach; ?>
                <?php foreach ($row3 as $img): ?><img src="<?= htmlspecialchars($img) ?>" alt="" loading="lazy"><?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($row4)): ?>
        <div class="carousel-row carousel-row-4">
            <div class="carousel-track carousel-track-right">
                <?php foreach ($row4 as $img): ?><img src="<?= htmlspecialchars($img) ?>" alt="" loading="lazy"><?php endforeach; ?>
                <?php foreach ($row4 as $img): ?><img src="<?= htmlspecialchars($img) ?>" alt="" loading="lazy"><?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="home-glow"></div>
    <div class="home-overlay"></div>

    <!-- Foreground content -->
    <div class="home-content flex flex-col items-center justify-center min-h-screen px-4 text-center">

        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border border-gold-500/20 bg-gold-500/5 text-gold-400 text-xs font-bold uppercase tracking-widest mb-8">
            <i data-lucide="crown" class="w-3.5 h-3.5"></i> One Piece TCG Collection Manager
        </div>

        <h1 class="home-title font-display font-black">
            <span class="text-white">My</span><span class="gradient-text">OP</span><span class="text-white">Cards</span>
        </h1>

        <p class="mt-6 text-lg md:text-xl text-dark-200/80 max-w-2xl leading-relaxed font-light">
            The ultimate tool to track, manage, and value your One Piece Trading Card Game collection.
            Real-time prices, deep analytics, and a collector community.
        </p>

        <div class="flex flex-col sm:flex-row gap-4 mt-10">
            <a href="/register"
               class="group px-10 py-4 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-2xl text-lg font-display font-bold hover:from-gold-400 hover:to-amber-500 transition-all shadow-2xl shadow-gold-500/20 hover:shadow-gold-500/40 hover:-translate-y-0.5 flex items-center gap-2">
                Start Collecting
                <i data-lucide="arrow-right" class="w-5 h-5 group-hover:translate-x-1 transition-transform"></i>
            </a>
            <a href="/cards"
               class="px-10 py-4 rounded-2xl text-lg font-medium text-dark-200 border border-dark-500/50 hover:border-gold-500/30 hover:text-gold-400 hover:bg-gold-500/5 transition-all flex items-center gap-2">
                <i data-lucide="layers" class="w-5 h-5"></i> Browse Cards
            </a>
        </div>

        <div class="grid grid-cols-3 gap-4 sm:gap-6 mt-16 max-w-lg mx-auto w-full">
            <div class="stat-pill rounded-2xl p-4 sm:p-5 text-center">
                <p class="text-2xl sm:text-3xl font-display font-bold text-white"><?= number_format($totalCards) ?></p>
                <p class="text-[11px] sm:text-xs text-dark-400 mt-1 uppercase tracking-wider font-medium">Cards</p>
            </div>
            <div class="stat-pill rounded-2xl p-4 sm:p-5 text-center">
                <p class="text-2xl sm:text-3xl font-display font-bold text-white">2</p>
                <p class="text-[11px] sm:text-xs text-dark-400 mt-1 uppercase tracking-wider font-medium">Price Sources</p>
            </div>
            <div class="stat-pill rounded-2xl p-4 sm:p-5 text-center">
                <p class="text-2xl sm:text-3xl font-display font-bold text-white"><?= $userCount ?? 0 ?></p>
                <p class="text-[11px] sm:text-xs text-dark-400 mt-1 uppercase tracking-wider font-medium">Collectors</p>
            </div>
        </div>
    </div>
</div>

<!-- ============ FEATURES ============ -->
<div class="relative z-10 pb-16 max-w-6xl mx-auto px-4">

    <div class="text-center mb-12 mt-4">
        <h2 class="text-3xl md:text-4xl font-display font-bold text-white">Everything You Need</h2>
        <p class="text-dark-400 mt-3 max-w-xl mx-auto">A complete toolkit built by collectors, for collectors.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="feat-card rounded-2xl p-8">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-500/20 to-blue-600/10 flex items-center justify-center mb-5">
                <i data-lucide="database" class="w-7 h-7 text-blue-400"></i>
            </div>
            <h3 class="text-xl font-display font-bold text-white mb-2">Complete Database</h3>
            <p class="text-dark-300 text-sm leading-relaxed">All <?= number_format($totalCards) ?> cards from every booster, starter deck, and promo release, with high-res images and full stats.</p>
        </div>
        <div class="feat-card rounded-2xl p-8">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-gold-500/20 to-amber-600/10 flex items-center justify-center mb-5">
                <i data-lucide="line-chart" class="w-7 h-7 text-gold-400"></i>
            </div>
            <h3 class="text-xl font-display font-bold text-white mb-2">Dual Price Tracking</h3>
            <p class="text-dark-300 text-sm leading-relaxed">Live prices from TCGPlayer (USD) and Cardmarket (EUR). Price history charts and daily smart updates on high-value cards.</p>
        </div>
        <div class="feat-card rounded-2xl p-8">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-500/20 to-purple-600/10 flex items-center justify-center mb-5">
                <i data-lucide="bar-chart-3" class="w-7 h-7 text-purple-400"></i>
            </div>
            <h3 class="text-xl font-display font-bold text-white mb-2">Deep Analytics</h3>
            <p class="text-dark-300 text-sm leading-relaxed">Track your collection value over time, see distribution charts, set completion heatmaps, and identify price movers.</p>
        </div>
        <div class="feat-card rounded-2xl p-8">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-green-500/20 to-green-600/10 flex items-center justify-center mb-5">
                <i data-lucide="globe" class="w-7 h-7 text-green-400"></i>
            </div>
            <h3 class="text-xl font-display font-bold text-white mb-2">Multi-Language</h3>
            <p class="text-dark-300 text-sm leading-relaxed">Browse cards in English, French, Japanese, Korean, Thai, or Chinese. Switch languages instantly from any page.</p>
        </div>
        <div class="feat-card rounded-2xl p-8">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-red-500/20 to-red-600/10 flex items-center justify-center mb-5">
                <i data-lucide="users" class="w-7 h-7 text-red-400"></i>
            </div>
            <h3 class="text-xl font-display font-bold text-white mb-2">Social &amp; Friends</h3>
            <p class="text-dark-300 text-sm leading-relaxed">Add friends, browse their collections, compare completion rates. Public profiles and collection sharing.</p>
        </div>
        <div class="feat-card rounded-2xl p-8">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-cyan-500/20 to-cyan-600/10 flex items-center justify-center mb-5">
                <i data-lucide="trending-up" class="w-7 h-7 text-cyan-400"></i>
            </div>
            <h3 class="text-xl font-display font-bold text-white mb-2">Market Insights</h3>
            <p class="text-dark-300 text-sm leading-relaxed">Top gainers, losers, most expensive cards, and set value summaries. Stay ahead of the market with daily updates.</p>
        </div>
    </div>

    <!-- CTA -->
    <div class="mt-16 text-center">
        <div class="feat-card rounded-3xl p-10 sm:p-14 max-w-3xl mx-auto relative overflow-hidden">
            <div class="absolute -top-20 -right-20 w-60 h-60 bg-gold-500/5 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-20 -left-20 w-60 h-60 bg-blue-500/5 rounded-full blur-3xl pointer-events-none"></div>
            <div class="relative z-10">
                <h3 class="text-2xl md:text-3xl font-display font-bold text-white mb-3">Ready to Track Your Collection?</h3>
                <p class="text-dark-300 mb-8 max-w-lg mx-auto">Join the community of One Piece TCG collectors. Free forever, no hidden costs.</p>
                <a href="/register"
                   class="inline-flex items-center gap-2 px-10 py-4 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-2xl text-lg font-display font-bold hover:from-gold-400 hover:to-amber-500 transition-all shadow-2xl shadow-gold-500/20">
                    Create Free Account <i data-lucide="arrow-right" class="w-5 h-5"></i>
                </a>
            </div>
        </div>
    </div>
</div>
