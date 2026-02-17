<?php
$currentUser = \App\Core\Auth::user();
$isLoggedIn = \App\Core\Auth::check();
$_pendingReqs = [];
if ($isLoggedIn) {
    $_pendingReqs = \App\Models\Friendship::getPendingRequests(\App\Core\Auth::id());
}
$pendingCount = count($_pendingReqs);
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
    <link rel="stylesheet" href="/assets/css/app.css">
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
                        <a href="/forum" class="flex items-center gap-1.5 px-2.5 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition">
                            <i data-lucide="message-square" class="w-4 h-4"></i> Forum
                        </a>
                        <?php if ($isLoggedIn): ?>
                        <div class="relative" x-data="{ navDrop: false }" @click.outside="navDrop = false">
                            <button @click="navDrop = !navDrop" class="flex items-center gap-1.5 px-2.5 py-2 rounded-lg text-sm font-medium text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition">
                                <i data-lucide="compass" class="w-4 h-4"></i> My Space <i data-lucide="chevron-down" class="w-3 h-3 transition" :class="navDrop && 'rotate-180'"></i>
                            </button>
                            <div x-show="navDrop" x-transition.opacity x-cloak class="absolute top-full left-0 mt-1 glass-strong rounded-xl shadow-2xl py-1 w-48 z-50">
                                <a href="/dashboard" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition"><i data-lucide="home" class="w-4 h-4"></i> Dashboard</a>
                                <a href="/collection" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition"><i data-lucide="folder-open" class="w-4 h-4"></i> Collection</a>
                                <a href="/analytics" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition"><i data-lucide="bar-chart-3" class="w-4 h-4"></i> Analytics</a>
                                <a href="/friends" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition"><i data-lucide="users" class="w-4 h-4"></i> Friends</a>
                                <?php if ($currentUser && !empty($currentUser['is_admin'])): ?>
                                <div class="border-t my-1" style="border-color:var(--nav-border)"></div>
                                <a href="/admin" class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 transition"><i data-lucide="shield" class="w-4 h-4"></i> Admin</a>
                                <?php endif; ?>
                            </div>
                        </div>
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
                                        <img :src="card.card_image_url || __PLACEHOLDER" class="w-8 h-11 rounded object-cover bg-gray-100" onerror="cardImgErr(this)">
                                        <div class="flex-1 min-w-0"><p class="text-sm text-gray-900 truncate" x-text="card.display_name || card.card_name"></p><p class="text-xs text-gray-400" x-text="card.card_set_id + ' · ' + card.rarity"></p></div>
                                        <span x-show="getCardPrice(card) > 0" class="text-xs font-bold text-gray-900" x-text="formatCardPrice(card)"></span>
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

                    <?php
                    $currentCurrency = 'usd';
                    if ($isLoggedIn && $currentUser) {
                        $currentCurrency = $currentUser['preferred_currency'] ?? 'usd';
                    } elseif (isset($_COOKIE['currency'])) {
                        $currentCurrency = $_COOKIE['currency'];
                    }
                    $currencyMap = [
                        'usd' => ['$', 'USD', 'TCGPlayer'],
                        'eur_en' => ['€', 'EUR', 'EN Edition'],
                        'eur_fr' => ['€', 'EUR', 'FR Edition'],
                        'eur_jp' => ['€', 'EUR', 'JP Edition'],
                    ];
                    $curSymbol = $currencyMap[$currentCurrency][0] ?? '$';
                    $curLabel = $currencyMap[$currentCurrency][1] ?? 'USD';
                    ?>
                    <div class="relative hidden md:block" x-data="{ curOpen: false }">
                        <button @click="curOpen = !curOpen" class="px-2 py-1.5 rounded-lg text-xs font-bold text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition flex items-center gap-1">
                            <span class="text-sm"><?= $curSymbol ?></span> <?= $curLabel ?>
                        </button>
                        <div x-show="curOpen" @click.outside="curOpen = false" x-transition class="absolute right-0 mt-1 glass-strong rounded-lg shadow-xl py-1 min-w-[160px] z-50">
                            <button onclick="setCurrency('usd')" class="block w-full text-left px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition <?= $currentCurrency === 'usd' ? 'font-bold text-gray-900' : '' ?>">
                                <span class="inline-block w-5 text-center mr-1">$</span> USD <span class="text-xs text-gray-400">TCGPlayer</span>
                            </button>
                            <button onclick="setCurrency('eur_en')" class="block w-full text-left px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition <?= $currentCurrency === 'eur_en' ? 'font-bold text-gray-900' : '' ?>">
                                <span class="inline-block w-5 text-center mr-1">€</span> EUR <span class="text-xs text-gray-400">EN Edition</span>
                            </button>
                            <button onclick="setCurrency('eur_fr')" class="block w-full text-left px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition <?= $currentCurrency === 'eur_fr' ? 'font-bold text-gray-900' : '' ?>">
                                <span class="inline-block w-5 text-center mr-1">€</span> EUR <span class="text-xs text-gray-400">FR Edition</span>
                            </button>
                            <button onclick="setCurrency('eur_jp')" class="block w-full text-left px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition <?= $currentCurrency === 'eur_jp' ? 'font-bold text-gray-900' : '' ?>">
                                <span class="inline-block w-5 text-center mr-1">€</span> EUR <span class="text-xs text-gray-400">JP Edition</span>
                            </button>
                            <div class="border-t my-1" style="border-color:var(--nav-border)"></div>
                            <p class="px-3 py-1 text-[10px] text-gray-300 uppercase font-bold tracking-wider">Language</p>
                            <?php foreach ($langs as $code => $name): ?>
                                <button onclick="setLanguage('<?= $code ?>')" class="block w-full text-left px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 transition <?= $currentLang === $code ? 'font-bold text-gray-900' : '' ?>"><?= $name ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($isLoggedIn): ?>
                    <div class="relative hidden md:block" x-data="notifBell()" @click.outside="open = false">
                        <button @click="toggle()" class="relative p-2 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition">
                            <i data-lucide="bell" class="w-4 h-4"></i>
                            <span id="nav-notif-dot" class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full" <?= $pendingCount === 0 ? 'style="display:none"' : '' ?>></span>
                            <span id="nav-notif-count" class="absolute -top-0.5 -right-1 min-w-[16px] h-4 px-0.5 bg-red-500 rounded-full flex items-center justify-center" style="color:#fff !important;font-size:10px;font-weight:700;<?= $pendingCount === 0 ? 'display:none' : '' ?>"><?= $pendingCount ?></span>
                        </button>
                        <div x-show="open" x-transition x-cloak class="absolute top-full right-0 mt-1 glass-strong rounded-xl shadow-2xl w-80 z-50 overflow-hidden">
                            <div class="px-4 py-3 border-b" style="border-color:var(--nav-border)">
                                <p class="text-sm font-display font-bold text-gray-900">Notifications</p>
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                <template x-if="items.length === 0">
                                    <div class="px-4 py-6 text-center text-gray-400 text-sm">No pending requests</div>
                                </template>
                                <template x-for="req in items" :key="req.id">
                                    <div class="px-4 py-3 border-b last:border-0 hover:bg-gray-50 transition" style="border-color:var(--nav-border)">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center text-white font-bold text-xs flex-shrink-0" x-text="req.username.charAt(0).toUpperCase()"></div>
                                            <p class="text-sm text-gray-900 flex-1 min-w-0"><span class="font-bold" x-text="req.username"></span> <span class="text-gray-500">wants to be friends</span></p>
                                        </div>
                                        <div class="flex gap-2 mt-2 ml-11">
                                            <button @click="accept(req)" class="flex-1 px-3 py-1.5 bg-green-500 rounded-lg text-xs font-bold hover:bg-green-600 transition" style="color:#fff !important">Accept</button>
                                            <button @click="decline(req)" class="flex-1 px-3 py-1.5 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold hover:bg-gray-200 transition">Decline</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <a href="/friends" class="block px-4 py-2.5 text-center text-xs font-bold text-gray-500 hover:text-gray-900 hover:bg-gray-50 transition" style="border-top:1px solid var(--nav-border)">View all friends</a>
                        </div>
                    </div>
                    <?php endif; ?>

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
                        <div class="relative">
                            <a href="/notifications" class="p-2 text-gray-400 hover:text-gray-600 transition" title="Notifications">
                                <i data-lucide="bell" class="w-4 h-4"></i>
                                <span id="notification-badge" class="hidden absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-medium"></span>
                            </a>
                        </div>
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
                        <img :src="card.card_image_url || __PLACEHOLDER" class="w-6 h-8 rounded object-cover" onerror="cardImgErr(this)">
                        <span class="text-sm text-gray-900 truncate" x-text="card.display_name || card.card_name"></span>
                    </a>
                </template>
            </div>
        </div>

        <div x-show="mobileMenu" x-transition class="md:hidden border-t border-gray-200 px-4 py-3 space-y-1">
            <a href="/cards" class="flex items-center gap-2 px-3 py-2 rounded text-gray-600 hover:text-gray-900 hover:bg-gray-50 text-sm"><i data-lucide="layers" class="w-4 h-4"></i> Cards</a>
            <a href="/market" class="flex items-center gap-2 px-3 py-2 rounded text-gray-600 hover:text-gray-900 hover:bg-gray-50 text-sm"><i data-lucide="trending-up" class="w-4 h-4"></i> Market</a>
            <a href="/forum" class="flex items-center gap-2 px-3 py-2 rounded text-gray-600 hover:text-gray-900 hover:bg-gray-50 text-sm"><i data-lucide="message-square" class="w-4 h-4"></i> Forum</a>
            <?php if ($isLoggedIn): ?>
                <a href="/dashboard" class="flex items-center gap-2 px-3 py-2 rounded text-gray-600 hover:text-gray-900 hover:bg-gray-50 text-sm"><i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard</a>
                <a href="/collection" class="flex items-center gap-2 px-3 py-2 rounded text-gray-600 hover:text-gray-900 hover:bg-gray-50 text-sm"><i data-lucide="folder-open" class="w-4 h-4"></i> Collection</a>
                <a href="/analytics" class="flex items-center gap-2 px-3 py-2 rounded text-gray-600 hover:text-gray-900 hover:bg-gray-50 text-sm"><i data-lucide="bar-chart-3" class="w-4 h-4"></i> Analytics</a>
                <a href="/friends" class="flex items-center gap-2 px-3 py-2 rounded text-gray-600 hover:text-gray-900 hover:bg-gray-50 text-sm"><i data-lucide="users" class="w-4 h-4"></i> Friends <?php if ($pendingCount > 0): ?><span class="ml-auto px-1.5 py-0.5 bg-red-500 rounded-full text-xs font-bold" style="color:#fff !important"><?= $pendingCount ?></span><?php endif; ?></a>
                <a href="/profile" class="flex items-center gap-2 px-3 py-2 rounded text-gray-600 hover:text-gray-900 hover:bg-gray-50 text-sm"><i data-lucide="user" class="w-4 h-4"></i> Profile</a>
                <a href="/logout" class="flex items-center gap-2 px-3 py-2 rounded text-red-500 text-sm"><i data-lucide="log-out" class="w-4 h-4"></i> Logout</a>
            <?php else: ?>
                <a href="/login" class="flex items-center gap-2 px-3 py-2 rounded text-gray-600 text-sm">Login</a>
                <a href="/register" class="flex items-center gap-2 px-3 py-2 rounded text-gray-900 font-bold text-sm">Sign Up</a>
            <?php endif; ?>
        </div>
    </nav>

    <?php $__v = '20260209'; ?>
    <script>
    window.__NOTIF_ITEMS = <?= $isLoggedIn ? json_encode(array_values($_pendingReqs)) : '[]' ?>;
    window.__CURRENCY = <?= json_encode(\App\Core\Currency::info()) ?>;
    </script>
    <script src="/assets/js/main.js?v=<?= $__v ?>"></script>

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
            <p class="pt-2">
                <a href="https://discord.gg/m5k52GFQPQ" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0 12.64 12.64 0 0 0-.617-1.25.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057 19.9 19.9 0 0 0 5.993 3.03.078.078 0 0 0 .084-.028c.462-.63.874-1.295 1.226-1.994a.076.076 0 0 0-.041-.106 13.107 13.107 0 0 1-1.872-.892.077.077 0 0 1-.008-.128 10.2 10.2 0 0 0 .372-.292.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127 12.299 12.299 0 0 1-1.873.892.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028 19.839 19.839 0 0 0 6.002-3.03.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.956-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419 0-1.333.955-2.419 2.157-2.419 1.21 0 2.176 1.096 2.157 2.42 0 1.333-.946 2.418-2.157 2.418z"/></svg>
                    Join our Discord
                </a>
            </p>
        </div>
    </footer>

    </div>

    <script src="/assets/js/app.js?v=<?= $__v ?>"></script>
    
    <?php if ($isLoggedIn): ?>
    <script>
    // Check for unread notifications
    async function updateNotificationBadge() {
        try {
            const response = await fetch('/api/notifications/count');
            const data = await response.json();
            const badge = document.getElementById('notification-badge');
            
            if (data.count > 0) {
                badge.textContent = data.count > 99 ? '99+' : data.count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        } catch (error) {
            console.error('Failed to fetch notification count:', error);
        }
    }
    
    // Update badge on page load and every 30 seconds
    updateNotificationBadge();
    setInterval(updateNotificationBadge, 30000);
    </script>
    <?php endif; ?>
</body>
</html>
