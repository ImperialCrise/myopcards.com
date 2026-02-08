<?php
$currentUser = \App\Core\Auth::user();
$isLoggedIn = \App\Core\Auth::check();
$pendingCount = 0;
if ($isLoggedIn) {
    $pendingCount = count(\App\Models\Friendship::getPendingRequests(\App\Core\Auth::id()));
}
$currentLang = 'en';
if ($isLoggedIn && $currentUser) {
    $currentLang = $currentUser['preferred_lang'] ?? 'en';
} elseif (isset($_COOKIE['lang'])) {
    $currentLang = $_COOKIE['lang'];
}
$langs = \App\Services\OfficialSiteScraper::getAvailableLanguages();

$_bgCards = [];
try {
    $_cacheFile = sys_get_temp_dir() . '/myopcards_carousel_v2.json';
    if (file_exists($_cacheFile) && filemtime($_cacheFile) > time() - 3600) {
        $_bgCards = json_decode(file_get_contents($_cacheFile), true) ?: [];
    } else {
        $_db = \App\Core\Database::getConnection();
        $_bgCards = $_db->query("SELECT card_image_url FROM cards WHERE rarity IN ('SEC','SP','SR','L') AND card_image_url IS NOT NULL AND card_image_url != '' ORDER BY RAND() LIMIT 48")->fetchAll(\PDO::FETCH_COLUMN);
        file_put_contents($_cacheFile, json_encode($_bgCards));
    }
} catch (\Throwable $e) {}
$_r1 = array_slice($_bgCards, 0, 12);
$_r2 = array_slice($_bgCards, 12, 12);
$_r3 = array_slice($_bgCards, 24, 12);
$_r4 = array_slice($_bgCards, 36, 12);
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'MyOPCards - One Piece TCG Collection Tracker') ?></title>
    <link rel="icon" href="/assets/img/favicon.ico" type="image/x-icon">
    <link rel="canonical" href="<?= htmlspecialchars(($seoCanonical ?? null) ?: ('https://myopcards.com' . strtok($_SERVER['REQUEST_URI'], '?'))) ?>">
    <?php
        $seoDesc = $seoDescription ?? 'Track, manage, and share your One Piece TCG card collection. Browse card prices, market trends, and connect with other collectors on MyOPCards.';
        $seoImage = $seoImage ?? 'https://myopcards.com/assets/img/og-default.png';
        $seoUrl = $seoCanonical ?? 'https://myopcards.com' . strtok($_SERVER['REQUEST_URI'], '?');
        $seoTitle = $title ?? 'MyOPCards - One Piece TCG Collection Tracker';
    ?>
    <meta name="description" content="<?= htmlspecialchars($seoDesc) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($seoKeywords ?? 'One Piece TCG, card collection, OPTCG, trading cards, card tracker, card prices, Cardmarket, TCGPlayer, One Piece cards, collection manager') ?>">
    <meta name="author" content="MyOPCards">
    <meta name="robots" content="<?= htmlspecialchars($seoRobots ?? 'index, follow') ?>">
    <meta name="theme-color" content="#ffffff" id="tc-meta">
    <meta property="og:type" content="<?= htmlspecialchars($seoOgType ?? 'website') ?>">
    <meta property="og:site_name" content="MyOPCards">
    <meta property="og:title" content="<?= htmlspecialchars($seoTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($seoDesc) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($seoUrl) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($seoImage) ?>">
    <meta property="og:locale" content="<?= $currentLang === 'fr' ? 'fr_FR' : 'en_US' ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($seoTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($seoDesc) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($seoImage) ?>">
    <?php if (!empty($seoJsonLd)): ?>
    <script type="application/ld+json"><?= json_encode($seoJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
    <?php endif; ?>

    <script>if(localStorage.getItem('darkMode')==='true')document.documentElement.classList.add('dark')</script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/lucide@0.344.0/dist/umd/lucide.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: {
                            950: 'var(--d950)', 900: 'var(--d900)', 800: 'var(--d800)',
                            700: 'var(--d700)', 600: 'var(--d600)', 500: 'var(--d500)',
                            400: 'var(--d400)', 300: 'var(--d300)', 200: 'var(--d200)',
                        },
                        gold: { 500: 'var(--g500)', 400: 'var(--g400)', 300: 'var(--g300)' },
                        accent: { red: '#dc2626', blue: '#3b82f6', green: '#22c55e', purple: '#a855f7' }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['Playfair Display', 'Georgia', 'serif'],
                    }
                }
            }
        }
    </script>
    <style>
        /* ===== THEME VARIABLES ===== */
        :root {
            --d950:#ffffff; --d900:#f9fafb; --d800:#f3f4f6; --d700:#e5e7eb;
            --d600:#d1d5db; --d500:#9ca3af; --d400:#6b7280; --d300:#374151; --d200:#1f2937;
            --g500:#111827; --g400:#1f2937; --g300:#374151;
            --body-bg:#ffffff; --body-color:#374151;
            --glass-bg:rgba(255,255,255,0.65); --glass-border:rgba(0,0,0,0.06); --glass-shadow:rgba(0,0,0,0.04);
            --glass-s-bg:rgba(255,255,255,0.88); --glass-s-border:rgba(0,0,0,0.08); --glass-s-shadow:rgba(0,0,0,0.06);
            --tp:#111827;
            --overlay:rgba(255,255,255,0.86); --carousel-op:0.18;
            --sb-track:#f9fafb; --sb-thumb:#d1d5db; --sb-hover:#9ca3af;
            --skel-a:#f3f4f6; --skel-b:#e5e7eb;
            --grad-a:#111827; --grad-b:#6b7280;
            --search-active:rgba(0,0,0,0.04);
            --nav-border:rgba(0,0,0,0.06);
            --g500-20:rgba(17,24,39,0.08); --g500-10:rgba(17,24,39,0.05);
            --g500-30:rgba(17,24,39,0.12); --g500-50:rgba(17,24,39,0.2);
            --g500-shadow:rgba(17,24,39,0.05);
            --d900-80:rgba(255,255,255,0.85); --d900-90:rgba(255,255,255,0.9);
            --amber600:#374151; --amber500:#4b5563;
            --carousel-shadow:rgba(0,0,0,0.10); --carousel-filter:grayscale(30%);
            --stat-bg:rgba(255,255,255,0.7); --stat-border:rgba(0,0,0,0.06);
            --feat-bg:rgba(255,255,255,0.65); --feat-border:rgba(0,0,0,0.06);
        }
        html.dark {
            --d950:#06080d; --d900:#0a0e17; --d800:#111827; --d700:#1a2332;
            --d600:#243044; --d500:#2d3d56; --d400:#4a6480; --d300:#8ba4c0; --d200:#b8cfe0;
            --g500:#d4a853; --g400:#e4be6a; --g300:#f0d48a;
            --body-bg:#06080d; --body-color:#b8cfe0;
            --glass-bg:rgba(17,24,39,0.7); --glass-border:rgba(74,100,128,0.2); --glass-shadow:rgba(0,0,0,0.2);
            --glass-s-bg:rgba(17,24,39,0.85); --glass-s-border:rgba(74,100,128,0.3); --glass-s-shadow:rgba(0,0,0,0.3);
            --tp:#ffffff;
            --overlay:rgba(6,8,13,0.82); --carousel-op:0.35;
            --sb-track:#0a0e17; --sb-thumb:#4a6480; --sb-hover:#8ba4c0;
            --skel-a:#1a2332; --skel-b:#243044;
            --grad-a:#d4a853; --grad-b:#ef4444;
            --search-active:rgba(212,168,83,0.1);
            --nav-border:rgba(74,100,128,0.2);
            --g500-20:rgba(212,168,83,0.2); --g500-10:rgba(212,168,83,0.1);
            --g500-30:rgba(212,168,83,0.3); --g500-50:rgba(212,168,83,0.5);
            --g500-shadow:rgba(212,168,83,0.1);
            --d900-80:rgba(10,14,23,0.8); --d900-90:rgba(10,14,23,0.9);
            --amber600:#d97706; --amber500:#f59e0b;
            --carousel-shadow:rgba(0,0,0,0.6); --carousel-filter:none;
            --stat-bg:rgba(17,24,39,0.65); --stat-border:rgba(212,168,83,0.12);
            --feat-bg:rgba(17,24,39,0.55); --feat-border:rgba(74,100,128,0.12);
        }

        /* ===== BASE ===== */
        body { background: var(--body-bg); color: var(--body-color); transition: background 0.3s, color 0.3s; }
        .glass { background:var(--glass-bg); backdrop-filter:blur(20px); -webkit-backdrop-filter:blur(20px); border:1px solid var(--glass-border); box-shadow:0 1px 3px var(--glass-shadow); }
        .glass-strong { background:var(--glass-s-bg); backdrop-filter:blur(28px); -webkit-backdrop-filter:blur(28px); border:1px solid var(--glass-s-border); box-shadow:0 4px 24px var(--glass-s-shadow); }
        .card-hover { transition: transform 0.3s cubic-bezier(.4,0,.2,1), box-shadow 0.3s; }
        .card-hover:hover { transform: translateY(-6px) scale(1.02); box-shadow: 0 25px 60px rgba(0,0,0,0.10); }
        .gradient-text { background:linear-gradient(135deg, var(--grad-a) 0%, var(--grad-b) 50%, var(--grad-a) 100%); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-size:200% 200%; animation:shimmer 4s ease-in-out infinite; }
        @keyframes shimmer { 0%,100%{background-position:0% 50%} 50%{background-position:100% 50%} }
        .skeleton { background:linear-gradient(90deg, var(--skel-a) 25%, var(--skel-b) 50%, var(--skel-a) 75%); background-size:200% 100%; animation:skel 1.5s ease-in-out infinite; }
        @keyframes skel { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
        .toast-enter { animation: slideIn 0.3s ease-out; } .toast-exit { animation: slideOut 0.3s ease-in forwards; }
        @keyframes slideIn { from{transform:translateX(100%);opacity:0} to{transform:translateX(0);opacity:1} }
        @keyframes slideOut { from{opacity:1} to{opacity:0;transform:translateX(100%)} }
        ::-webkit-scrollbar { width:6px; height:6px; }
        ::-webkit-scrollbar-track { background:var(--sb-track); }
        ::-webkit-scrollbar-thumb { background:var(--sb-thumb); border-radius:3px; }
        ::-webkit-scrollbar-thumb:hover { background:var(--sb-hover); }
        .search-result-active { background:var(--search-active); }

        /* ===== TEXT-WHITE OVERRIDE ===== */
        .text-white { color: var(--tp) !important; }
        .text-white[class*="bg-gradient"],[class*="bg-gradient"] .text-white,
        [class*="bg-red"] .text-white,[class*="bg-green-5"] .text-white,[class*="bg-green-6"] .text-white,
        [class*="bg-blue-5"] .text-white,[class*="bg-blue-6"] .text-white,
        [class*="bg-gray-8"] .text-white,[class*="bg-gray-9"] .text-white,
        .bg-gold-500 .text-white { color:#ffffff !important; }

        /* ===== GOLD / GRADIENT OVERRIDES ===== */
        .bg-gold-500 { background:var(--g500) !important; }
        .bg-gold-500\/20 { background:var(--g500-20) !important; }
        .bg-gold-500\/10 { background:var(--g500-10) !important; }
        .text-gold-400 { color:var(--g400) !important; }
        .text-gold-300 { color:var(--g300) !important; }
        .hover\:text-gold-400:hover { color:var(--g400) !important; }
        .hover\:text-gold-300:hover { color:var(--g300) !important; }
        .hover\:bg-gold-500\/30:hover { background:var(--g500-30) !important; }
        .border-gold-500\/50 { border-color:var(--g500-50) !important; }
        .border-gold-500\/30 { border-color:var(--g500-30) !important; }
        .focus\:border-gold-500\/50:focus { border-color:var(--g500-50) !important; }
        .focus\:ring-gold-500\/20:focus { --tw-ring-color:var(--g500-20) !important; }
        .shadow-gold-500\/10 { --tw-shadow-color:var(--g500-shadow) !important; }
        .from-gold-500 { --tw-gradient-from:var(--g500) !important; }
        .from-gold-400 { --tw-gradient-from:var(--g400) !important; }
        .to-amber-600 { --tw-gradient-to:var(--amber600) !important; }
        .to-amber-500 { --tw-gradient-to:var(--amber500) !important; }
        .bg-gradient-to-r.from-gold-500 { color:#ffffff; }
        .bg-dark-900\/80,.bg-dark-900\/90 { background:var(--d900-80) !important; }

        /* ===== LAYOUT ===== */
        nav.glass-strong { border-bottom:1px solid var(--nav-border); }
        footer { border-top:1px solid var(--nav-border) !important; }
        #site-wrapper { position:relative; z-index:1; }

        /* ===== SEMANTIC COLOR OVERRIDES ===== */
        .text-green-400 { color:#059669 !important; } html.dark .text-green-400 { color:#22c55e !important; }
        .text-blue-400 { color:#2563eb !important; }  html.dark .text-blue-400 { color:#3b82f6 !important; }
        .text-cyan-400 { color:#0891b2 !important; }  html.dark .text-cyan-400 { color:#22d3ee !important; }
        .text-red-400 { color:#dc2626 !important; }   html.dark .text-red-400 { color:#f87171 !important; }
        .text-purple-400 { color:#9333ea !important; } html.dark .text-purple-400 { color:#a855f7 !important; }

        /* ===== DARK MODE: GRAY OVERRIDES ===== */
        html.dark .text-gray-900 { color:#f3f4f6 !important; }
        html.dark .text-gray-700 { color:#b8cfe0 !important; }
        html.dark .text-gray-600 { color:#9ca3af !important; }
        html.dark .text-gray-500 { color:#6b7280 !important; }
        html.dark .text-gray-400 { color:#4a6480 !important; }
        html.dark .text-gray-300 { color:#2d3d56 !important; }
        html.dark .bg-gray-900 { background-color:var(--g500) !important; }
        html.dark .bg-gray-100 { background-color:#1a2332 !important; }
        html.dark .bg-gray-50 { background-color:#111827 !important; }
        html.dark .bg-white { background-color:var(--body-bg) !important; }
        html.dark .border-gray-200 { border-color:#243044 !important; }
        html.dark .border-gray-100 { border-color:#1a2332 !important; }
        html.dark .border-gray-300 { border-color:#243044 !important; }
        html.dark .hover\:text-gray-900:hover { color:#f3f4f6 !important; }
        html.dark .hover\:bg-gray-100:hover { background-color:#1a2332 !important; }
        html.dark .hover\:bg-gray-50:hover { background-color:#111827 !important; }
        html.dark .hover\:bg-white\/50:hover { background-color:rgba(17,24,39,0.5) !important; }
        html.dark .placeholder-gray-400::placeholder { color:#4a6480 !important; }
        html.dark .text-emerald-600 { color:#22c55e !important; }
        html.dark .text-blue-600 { color:#3b82f6 !important; }
        html.dark .text-indigo-600 { color:#818cf8 !important; }
        html.dark .text-red-600 { color:#ef4444 !important; }
        html.dark .hover\:border-gray-400:hover { border-color:#4a6480 !important; }
        html.dark .focus\:border-gray-400:focus { border-color:#4a6480 !important; }
        html.dark .focus\:ring-gray-300\/30:focus { --tw-ring-color:rgba(74,100,128,0.2) !important; }

        /* ===== CAROUSEL BACKGROUND ===== */
        #bg-carousel {
            position:fixed; inset:0; z-index:0; pointer-events:none;
            overflow:hidden; opacity:var(--carousel-op);
        }
        #bg-overlay {
            position:fixed; inset:0; z-index:0; pointer-events:none;
            background:var(--overlay);
            transition: background 0.3s;
        }
        .carousel-row { position:absolute; left:0; display:flex; width:max-content; }
        .carousel-row-1 { top:3%; } .carousel-row-2 { top:28%; } .carousel-row-3 { top:53%; } .carousel-row-4 { top:78%; }
        .carousel-row .carousel-track { display:flex; gap:1.25rem; will-change:transform; }
        .carousel-track-left  { animation:drift-left  var(--speed,60s) linear infinite; }
        .carousel-track-right { animation:drift-right var(--speed,60s) linear infinite; }
        @keyframes drift-left  { from{transform:translateX(0)} to{transform:translateX(-50%)} }
        @keyframes drift-right { from{transform:translateX(-50%)} to{transform:translateX(0)} }
        .carousel-track img {
            width:130px; height:182px; object-fit:cover; border-radius:10px; flex-shrink:0;
            box-shadow:0 6px 24px var(--carousel-shadow); filter:var(--carousel-filter);
        }
        .carousel-row-1 .carousel-track { --speed:50s; } .carousel-row-2 .carousel-track { --speed:65s; }
        .carousel-row-3 .carousel-track { --speed:42s; } .carousel-row-4 .carousel-track { --speed:58s; }
        .carousel-row-1 .carousel-track img:nth-child(odd)  { transform:rotate(-4deg) translateY(4px); }
        .carousel-row-1 .carousel-track img:nth-child(even) { transform:rotate(3deg) translateY(-6px); }
        .carousel-row-2 .carousel-track img:nth-child(odd)  { transform:rotate(5deg) translateY(-4px); }
        .carousel-row-2 .carousel-track img:nth-child(even) { transform:rotate(-3deg) translateY(8px); }
        .carousel-row-3 .carousel-track img:nth-child(3n)   { transform:rotate(-6deg) translateY(6px); }
        .carousel-row-3 .carousel-track img:nth-child(3n+1) { transform:rotate(2deg) translateY(-3px); }
        .carousel-row-3 .carousel-track img:nth-child(3n+2) { transform:rotate(-2deg) translateY(10px); }
        .carousel-row-4 .carousel-track img:nth-child(odd)  { transform:rotate(4deg) translateY(-5px); }
        .carousel-row-4 .carousel-track img:nth-child(even) { transform:rotate(-5deg) translateY(8px); }
        @media (max-width:640px) { .carousel-track img{width:90px;height:126px} .carousel-track{gap:0.75rem} }

        [x-cloak] { display:none !important; }
    </style>
</head>
<body class="font-sans min-h-screen" x-data="{ mobileMenu: false, mobileSearch: false }">

    <!-- Global card carousel background -->
    <div id="bg-carousel" aria-hidden="true">
        <?php if (!empty($_r1)): ?>
        <div class="carousel-row carousel-row-1"><div class="carousel-track carousel-track-left">
            <?php foreach ($_r1 as $_i): ?><img src="<?= htmlspecialchars($_i) ?>" alt="" loading="lazy"><?php endforeach; ?>
            <?php foreach ($_r1 as $_i): ?><img src="<?= htmlspecialchars($_i) ?>" alt="" loading="lazy"><?php endforeach; ?>
        </div></div>
        <?php endif; ?>
        <?php if (!empty($_r2)): ?>
        <div class="carousel-row carousel-row-2"><div class="carousel-track carousel-track-right">
            <?php foreach ($_r2 as $_i): ?><img src="<?= htmlspecialchars($_i) ?>" alt="" loading="lazy"><?php endforeach; ?>
            <?php foreach ($_r2 as $_i): ?><img src="<?= htmlspecialchars($_i) ?>" alt="" loading="lazy"><?php endforeach; ?>
        </div></div>
        <?php endif; ?>
        <?php if (!empty($_r3)): ?>
        <div class="carousel-row carousel-row-3"><div class="carousel-track carousel-track-left">
            <?php foreach ($_r3 as $_i): ?><img src="<?= htmlspecialchars($_i) ?>" alt="" loading="lazy"><?php endforeach; ?>
            <?php foreach ($_r3 as $_i): ?><img src="<?= htmlspecialchars($_i) ?>" alt="" loading="lazy"><?php endforeach; ?>
        </div></div>
        <?php endif; ?>
        <?php if (!empty($_r4)): ?>
        <div class="carousel-row carousel-row-4"><div class="carousel-track carousel-track-right">
            <?php foreach ($_r4 as $_i): ?><img src="<?= htmlspecialchars($_i) ?>" alt="" loading="lazy"><?php endforeach; ?>
            <?php foreach ($_r4 as $_i): ?><img src="<?= htmlspecialchars($_i) ?>" alt="" loading="lazy"><?php endforeach; ?>
        </div></div>
        <?php endif; ?>
    </div>
    <div id="bg-overlay"></div>

    <div id="site-wrapper">

    <div id="toast-container" class="fixed top-4 right-4 z-[60] space-y-2"></div>

    <nav class="glass-strong sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-2">
                    <a href="/" class="flex items-center gap-2.5 mr-6 group">
                        <svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" class="flex-shrink-0">
                            <defs><linearGradient id="logoGrad" x1="0" y1="0" x2="36" y2="36" gradientUnits="userSpaceOnUse">
                                <stop offset="0%" stop-color="var(--g500)"/><stop offset="100%" stop-color="var(--g300)"/>
                            </linearGradient></defs>
                            <rect width="36" height="36" rx="10" fill="url(#logoGrad)"/>
                            <rect x="8" y="7" width="15" height="21" rx="2.5" fill="white" fill-opacity="0.25" transform="rotate(-8 8 7)"/>
                            <rect x="12" y="8" width="15" height="21" rx="2.5" fill="white" fill-opacity="0.9" transform="rotate(5 20 18)"/>
                            <text x="18" y="22" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="10" fill="var(--d800)" letter-spacing="-0.5">OP</text>
                        </svg>
                        <span class="text-lg font-display font-bold hidden sm:flex items-baseline gap-0.5 tracking-tight">
                            <span class="text-gray-400 group-hover:text-gray-500 transition">My</span><span class="text-gray-900 font-extrabold">OP</span><span class="text-gray-400 group-hover:text-gray-500 transition">Cards</span>
                        </span>
                    </a>
                    <div class="hidden md:flex items-center gap-0.5">
                        <a href="/cards" class="flex items-center gap-1.5 px-2.5 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition">
                            <i data-lucide="layers" class="w-4 h-4"></i> Cards
                        </a>
                        <a href="/market" class="flex items-center gap-1.5 px-2.5 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition">
                            <i data-lucide="trending-up" class="w-4 h-4"></i> Market
                        </a>
                        <?php if ($isLoggedIn): ?>
                        <div class="relative" x-data="{ navDrop: false }" @click.outside="navDrop = false">
                            <button @click="navDrop = !navDrop" class="flex items-center gap-1.5 px-2.5 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition">
                                <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard <i data-lucide="chevron-down" class="w-3 h-3 transition" :class="navDrop && 'rotate-180'"></i>
                            </button>
                            <div x-show="navDrop" x-transition.opacity x-cloak class="absolute top-full left-0 mt-1 glass-strong rounded-xl shadow-2xl py-1 w-44 z-50">
                                <a href="/dashboard" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition"><i data-lucide="home" class="w-4 h-4"></i> Overview</a>
                                <a href="/analytics" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition"><i data-lucide="bar-chart-3" class="w-4 h-4"></i> Analytics</a>
                            </div>
                        </div>
                        <a href="/collection" class="flex items-center gap-1.5 px-2.5 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition">
                            <i data-lucide="folder-open" class="w-4 h-4"></i> Collection
                        </a>
                        <a href="/friends" class="flex items-center gap-1.5 px-2.5 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition relative">
                            <i data-lucide="users" class="w-4 h-4"></i> Friends
                            <?php if ($pendingCount > 0): ?>
                                <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center" style="color:#fff !important;font-size:10px;font-weight:700"><?= $pendingCount ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div class="hidden md:block relative" x-data="globalSearch()" @click.outside="close()">
                        <div class="relative">
                            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text" x-model="query" @input.debounce.250ms="search()" @keydown.escape="close()" @keydown.arrow-down.prevent="moveDown()" @keydown.arrow-up.prevent="moveUp()" @keydown.enter.prevent="go()" @focus="open = query.length >= 2"
                                placeholder="Search cards, users, sets..."
                                class="w-56 lg:w-72 pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:border-gray-400 focus:ring-1 focus:ring-gray-300/30 transition">
                        </div>
                        <div x-show="open && query.length >= 2" x-transition class="absolute top-full left-0 right-0 mt-2 glass-strong rounded-xl shadow-2xl overflow-hidden max-h-96 overflow-y-auto z-50">
                            <div x-show="loading" class="p-4 text-center"><i data-lucide="loader-2" class="w-5 h-5 animate-spin text-gray-400 mx-auto"></i></div>
                            <div x-show="!loading && results.cards.length === 0 && results.users.length === 0 && results.sets.length === 0" class="p-4 text-center text-gray-400 text-sm">No results found</div>
                            <template x-if="results.cards.length > 0"><div>
                                <div class="px-3 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider">Cards</div>
                                <template x-for="(card, i) in results.cards" :key="'c'+card.id">
                                    <a :href="'/cards/' + card.card_set_id" class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 transition" :class="activeIdx === i ? 'search-result-active' : ''">
                                        <img :src="card.card_image_url" class="w-8 h-11 rounded object-cover bg-gray-100" onerror="this.style.display='none'">
                                        <div class="flex-1 min-w-0"><p class="text-sm text-gray-900 truncate" x-text="card.display_name || card.card_name"></p><p class="text-xs text-gray-400" x-text="card.card_set_id + ' · ' + card.rarity"></p></div>
                                        <span x-show="card.market_price" class="text-xs font-bold text-gray-900" x-text="'$' + parseFloat(card.market_price).toFixed(2)"></span>
                                    </a>
                                </template>
                            </div></template>
                            <template x-if="results.users.length > 0"><div>
                                <div class="px-3 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider border-t border-gray-200">Users</div>
                                <template x-for="u in results.users" :key="'u'+u.id">
                                    <a :href="'/user/' + u.username" class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 transition">
                                        <div class="w-8 h-8 rounded-full bg-gray-900 flex items-center justify-center font-bold text-xs" style="color:#fff !important" x-text="u.username.charAt(0).toUpperCase()"></div>
                                        <span class="text-sm text-gray-900" x-text="u.username"></span>
                                    </a>
                                </template>
                            </div></template>
                            <template x-if="results.sets.length > 0"><div>
                                <div class="px-3 py-2 text-xs font-bold text-gray-400 uppercase tracking-wider border-t border-gray-200">Sets</div>
                                <template x-for="s in results.sets" :key="'s'+s.set_id">
                                    <a :href="'/cards?set_id=' + s.set_id" class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 transition">
                                        <div class="w-8 h-8 rounded bg-gray-100 flex items-center justify-center"><i data-lucide="package" class="w-4 h-4 text-gray-500"></i></div>
                                        <div class="flex-1 min-w-0"><p class="text-sm text-gray-900 truncate" x-text="s.set_name"></p><p class="text-xs text-gray-400" x-text="s.set_id + ' · ' + s.card_count + ' cards'"></p></div>
                                    </a>
                                </template>
                            </div></template>
                        </div>
                    </div>

                    <div class="relative hidden md:block" x-data="{ langOpen: false }">
                        <button @click="langOpen = !langOpen" class="px-2 py-1.5 rounded-lg text-xs font-bold text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition uppercase"><?= $currentLang ?></button>
                        <div x-show="langOpen" @click.outside="langOpen = false" x-transition class="absolute right-0 mt-1 glass-strong rounded-lg shadow-xl py-1 min-w-[120px] z-50">
                            <?php foreach ($langs as $code => $name): ?>
                                <button onclick="setLanguage('<?= $code ?>')" class="block w-full text-left px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition <?= $currentLang === $code ? 'font-bold text-gray-900' : '' ?>"><?= $name ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button onclick="toggleDark()" class="p-2 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition" title="Toggle dark mode" id="dm-btn">
                        <svg id="dm-moon" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z"></path></svg>
                        <svg id="dm-sun" class="w-4 h-4 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5"></circle><path stroke-linecap="round" d="M12 1v2m0 18v2M4.22 4.22l1.42 1.42m12.73 12.73l1.42 1.42M1 12h2m18 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"></path></svg>
                    </button>

                    <?php if ($isLoggedIn): ?>
                        <a href="/profile" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-gray-100 transition">
                            <?php if ($currentUser['avatar']): ?>
                                <img src="<?= htmlspecialchars($currentUser['avatar']) ?>" class="w-7 h-7 rounded-full border border-gray-200" alt="">
                            <?php else: ?>
                                <div class="w-7 h-7 rounded-full bg-gray-900 flex items-center justify-center font-bold text-xs" style="color:#fff !important"><?= strtoupper(substr($currentUser['username'], 0, 1)) ?></div>
                            <?php endif; ?>
                            <span class="text-sm font-medium text-gray-600 hidden lg:block"><?= htmlspecialchars($currentUser['username']) ?></span>
                        </a>
                        <a href="/logout" class="p-2 text-gray-400 hover:text-red-500 transition" title="Logout"><i data-lucide="log-out" class="w-4 h-4"></i></a>
                    <?php else: ?>
                        <a href="/login" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition hidden sm:block">Login</a>
                        <a href="/register" class="px-4 py-2 bg-gray-900 rounded-lg text-sm font-bold transition hover:bg-gray-800 shadow-sm" style="color:#fff !important">Sign Up</a>
                    <?php endif; ?>

                    <button @click="mobileSearch = !mobileSearch" class="md:hidden p-2 text-gray-500 hover:text-gray-900"><i data-lucide="search" class="w-5 h-5"></i></button>
                    <button @click="mobileMenu = !mobileMenu" class="md:hidden p-2 text-gray-500 hover:text-gray-900">
                        <i x-show="!mobileMenu" data-lucide="menu" class="w-5 h-5"></i><i x-show="mobileMenu" data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>

        <div x-show="mobileSearch" x-transition class="md:hidden px-4 pb-3" x-data="globalSearch()" @click.outside="mobileSearch = false">
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" x-model="query" @input.debounce.250ms="search()" placeholder="Search..."
                    class="w-full pl-9 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:border-gray-400">
            </div>
            <div x-show="open && results.cards.length > 0" class="mt-2 glass rounded-lg max-h-60 overflow-y-auto">
                <template x-for="card in results.cards" :key="'mc'+card.id">
                    <a :href="'/cards/' + card.card_set_id" class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 transition">
                        <img :src="card.card_image_url" class="w-6 h-8 rounded object-cover" onerror="this.style.display='none'">
                        <span class="text-sm text-gray-900 truncate" x-text="card.display_name || card.card_name"></span>
                    </a>
                </template>
            </div>
        </div>

        <div x-show="mobileMenu" x-transition class="md:hidden border-t border-gray-200 px-4 py-3 space-y-1">
            <a href="/cards" class="flex items-center gap-2 px-3 py-2 rounded text-gray-600 hover:text-gray-900 hover:bg-gray-50 text-sm"><i data-lucide="layers" class="w-4 h-4"></i> Cards</a>
            <a href="/market" class="flex items-center gap-2 px-3 py-2 rounded text-gray-600 hover:text-gray-900 hover:bg-gray-50 text-sm"><i data-lucide="trending-up" class="w-4 h-4"></i> Market</a>
            <?php if ($isLoggedIn): ?>
                <a href="/dashboard" class="flex items-center gap-2 px-3 py-2 rounded text-gray-600 hover:text-gray-900 hover:bg-gray-50 text-sm"><i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard</a>
                <a href="/collection" class="flex items-center gap-2 px-3 py-2 rounded text-gray-600 hover:text-gray-900 hover:bg-gray-50 text-sm"><i data-lucide="folder-open" class="w-4 h-4"></i> Collection</a>
                <a href="/analytics" class="flex items-center gap-2 px-3 py-2 rounded text-gray-600 hover:text-gray-900 hover:bg-gray-50 text-sm"><i data-lucide="bar-chart-3" class="w-4 h-4"></i> Analytics</a>
                <a href="/friends" class="flex items-center gap-2 px-3 py-2 rounded text-gray-600 hover:text-gray-900 hover:bg-gray-50 text-sm"><i data-lucide="users" class="w-4 h-4"></i> Friends</a>
                <a href="/profile" class="flex items-center gap-2 px-3 py-2 rounded text-gray-600 hover:text-gray-900 hover:bg-gray-50 text-sm"><i data-lucide="user" class="w-4 h-4"></i> Profile</a>
                <a href="/logout" class="flex items-center gap-2 px-3 py-2 rounded text-red-500 text-sm"><i data-lucide="log-out" class="w-4 h-4"></i> Logout</a>
            <?php else: ?>
                <a href="/login" class="flex items-center gap-2 px-3 py-2 rounded text-gray-600 text-sm">Login</a>
                <a href="/register" class="flex items-center gap-2 px-3 py-2 rounded text-gray-900 font-bold text-sm">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <main id="main-content">
        <?php if (!empty($fullWidth)): ?>
            <?= $content ?>
        <?php else: ?>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <?= $content ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="mt-16" style="border-top:1px solid var(--nav-border)">
        <div class="max-w-7xl mx-auto px-4 py-8 text-center text-gray-400 text-sm space-y-2">
            <p class="font-display text-gray-500">&copy; <?= date('Y') ?> MyOPCards</p>
            <p>Not affiliated with Bandai or One Piece. Card data via OPTCG API. Prices from TCGPlayer &amp; Cardmarket.</p>
            <p class="text-gray-300 text-xs max-w-xl mx-auto">
                Prices displayed may not reflect current market values. MyOPCards is a recently launched platform
                and price data is still being collected. Always verify on official marketplaces before purchasing.
            </p>
        </div>
    </footer>

    </div>

    <script>
        function toggleDark() {
            const on = document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', on);
            document.getElementById('dm-moon').classList.toggle('hidden', on);
            document.getElementById('dm-sun').classList.toggle('hidden', !on);
            document.getElementById('tc-meta').content = on ? '#06080d' : '#ffffff';
        }
        (function(){
            const on = document.documentElement.classList.contains('dark');
            document.getElementById('dm-moon').classList.toggle('hidden', on);
            document.getElementById('dm-sun').classList.toggle('hidden', !on);
            if(on) document.getElementById('tc-meta').content = '#06080d';
        })();

        function cleanSubmit(form) {
            const action = form.getAttribute('action') || window.location.pathname;
            const params = new URLSearchParams();
            for (const el of form.elements) { if (!el.name) continue; if (el.value && !(el.name === 'sort' && el.value === 'set')) params.set(el.name, el.value); }
            const qs = params.toString();
            window.location.href = action + (qs ? '?' + qs : '');
        }

        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const colors = { success: 'bg-green-600', error: 'bg-red-600', info: 'bg-gray-800' };
            toast.className = `${colors[type] || colors.info} px-5 py-3 rounded-xl shadow-2xl toast-enter text-sm font-medium flex items-center gap-2`;
            toast.style.color = '#fff';
            toast.innerHTML = `<svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${type === 'success' ? 'M5 13l4 4L19 7' : type === 'error' ? 'M6 18L18 6M6 6l12 12' : 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'}"></path></svg>${message}`;
            container.appendChild(toast);
            setTimeout(() => { toast.classList.add('toast-exit'); setTimeout(() => toast.remove(), 300); }, 3000);
        }

        async function apiPost(url, data) {
            const formData = new FormData();
            Object.entries(data).forEach(([k, v]) => formData.append(k, String(v)));
            const res = await fetch(url, { method: 'POST', body: formData });
            return res.json();
        }

        function globalSearch() {
            return {
                query: '', open: false, loading: false, activeIdx: -1,
                results: { cards: [], users: [], sets: [] },
                async search() {
                    if (this.query.length < 2) { this.open = false; this.results = { cards: [], users: [], sets: [] }; return; }
                    this.loading = true; this.open = true;
                    try { const res = await fetch('/api/search?q=' + encodeURIComponent(this.query)); this.results = await res.json(); } catch(e) { this.results = { cards: [], users: [], sets: [] }; }
                    this.loading = false; this.activeIdx = -1;
                },
                close() { this.open = false; },
                moveDown() { this.activeIdx = Math.min(this.activeIdx + 1, this.results.cards.length - 1); },
                moveUp() { this.activeIdx = Math.max(this.activeIdx - 1, 0); },
                go() { if (this.activeIdx >= 0 && this.results.cards[this.activeIdx]) window.location = '/cards/' + this.results.cards[this.activeIdx].card_set_id; }
            }
        }

        async function setLanguage(lang) { await apiPost('/settings/language', { lang: lang }); location.reload(); }
    </script>
    <script src="/assets/js/app.js"></script>
</body>
</html>
