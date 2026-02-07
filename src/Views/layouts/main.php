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
?>
<!DOCTYPE html>
<html lang="<?= $currentLang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'MyOPCards') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/lucide@0.344.0/dist/umd/lucide.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        dark: { 950: '#06080d', 900: '#0a0e17', 800: '#111827', 700: '#1a2332', 600: '#243044', 500: '#2d3d56', 400: '#4a6480', 300: '#8ba4c0', 200: '#b8cfe0' },
                        gold: { 500: '#d4a853', 400: '#e4be6a', 300: '#f0d48a' },
                        accent: { red: '#dc2626', blue: '#3b82f6', green: '#22c55e', purple: '#a855f7' }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                        display: ['Space Grotesk', 'Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body { background: #06080d; color: #b8cfe0; }
        .glass { background: rgba(17,24,39,0.7); backdrop-filter: blur(16px); border: 1px solid rgba(74,100,128,0.2); }
        .glass-strong { background: rgba(17,24,39,0.85); backdrop-filter: blur(24px); border: 1px solid rgba(74,100,128,0.3); }
        .card-hover { transition: transform 0.25s cubic-bezier(.4,0,.2,1), box-shadow 0.25s; }
        .card-hover:hover { transform: translateY(-6px) scale(1.02); box-shadow: 0 20px 50px rgba(212,168,83,0.12); }
        .gradient-text { background: linear-gradient(135deg, #d4a853 0%, #ef4444 50%, #d4a853 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-size: 200% 200%; animation: shimmer 3s ease-in-out infinite; }
        @keyframes shimmer { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
        .skeleton { background: linear-gradient(90deg, #1a2332 25%, #243044 50%, #1a2332 75%); background-size: 200% 100%; animation: skeleton-pulse 1.5s ease-in-out infinite; }
        @keyframes skeleton-pulse { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
        .toast-enter { animation: slideIn 0.3s ease-out; }
        .toast-exit { animation: slideOut 0.3s ease-in forwards; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOut { from { opacity: 1; } to { opacity: 0; transform: translateX(100%); } }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0a0e17; }
        ::-webkit-scrollbar-thumb { background: #4a6480; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #8ba4c0; }
        .search-result-active { background: rgba(212,168,83,0.1); }
    </style>
</head>
<body class="font-sans min-h-screen" x-data="{ mobileMenu: false, mobileSearch: false }">

    <div id="toast-container" class="fixed top-4 right-4 z-[60] space-y-2"></div>

    <nav class="glass-strong sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-2">
                    <a href="/" class="flex items-center gap-2 mr-6">
                        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-gold-500 to-red-600 flex items-center justify-center">
                            <i data-lucide="crown" class="w-4 h-4 text-white"></i>
                        </div>
                        <span class="text-lg font-display font-bold text-white hidden sm:block">MyOPCards</span>
                    </a>
                    <div class="hidden md:flex items-center gap-1">
                        <a href="/cards" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-dark-300 hover:text-gold-400 hover:bg-dark-700/50 transition">
                            <i data-lucide="layers" class="w-4 h-4"></i> Cards
                        </a>
                        <a href="/market" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-dark-300 hover:text-gold-400 hover:bg-dark-700/50 transition">
                            <i data-lucide="trending-up" class="w-4 h-4"></i> Market
                        </a>
                        <?php if ($isLoggedIn): ?>
                        <a href="/dashboard" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-dark-300 hover:text-gold-400 hover:bg-dark-700/50 transition">
                            <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard
                        </a>
                        <a href="/collection" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-dark-300 hover:text-gold-400 hover:bg-dark-700/50 transition">
                            <i data-lucide="folder-open" class="w-4 h-4"></i> Collection
                        </a>
                        <a href="/analytics" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-dark-300 hover:text-gold-400 hover:bg-dark-700/50 transition">
                            <i data-lucide="bar-chart-3" class="w-4 h-4"></i> Analytics
                        </a>
                        <a href="/friends" class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-dark-300 hover:text-gold-400 hover:bg-dark-700/50 transition relative">
                            <i data-lucide="users" class="w-4 h-4"></i> Friends
                            <?php if ($pendingCount > 0): ?>
                                <span class="absolute -top-0.5 -right-0.5 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center"><?= $pendingCount ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Global Search -->
                    <div class="hidden md:block relative" x-data="globalSearch()" @click.outside="close()">
                        <div class="relative">
                            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-dark-400"></i>
                            <input type="text" x-model="query" @input.debounce.250ms="search()" @keydown.escape="close()" @keydown.arrow-down.prevent="moveDown()" @keydown.arrow-up.prevent="moveUp()" @keydown.enter.prevent="go()" @focus="open = query.length >= 2"
                                placeholder="Search cards, users, sets..."
                                class="w-64 lg:w-80 pl-9 pr-4 py-2 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50 focus:ring-1 focus:ring-gold-500/20 transition">
                        </div>
                        <div x-show="open && query.length >= 2" x-transition class="absolute top-full left-0 right-0 mt-2 glass-strong rounded-xl shadow-2xl overflow-hidden max-h-96 overflow-y-auto z-50">
                            <div x-show="loading" class="p-4 text-center"><i data-lucide="loader-2" class="w-5 h-5 animate-spin text-dark-400 mx-auto"></i></div>
                            <div x-show="!loading && results.cards.length === 0 && results.users.length === 0 && results.sets.length === 0" class="p-4 text-center text-dark-400 text-sm">No results found</div>

                            <template x-if="results.cards.length > 0">
                                <div>
                                    <div class="px-3 py-2 text-xs font-bold text-dark-400 uppercase tracking-wider">Cards</div>
                                    <template x-for="(card, i) in results.cards" :key="'c'+card.id">
                                        <a :href="'/cards/' + card.card_set_id" class="flex items-center gap-3 px-3 py-2 hover:bg-dark-700/50 transition" :class="activeIdx === i ? 'search-result-active' : ''">
                                            <img :src="card.card_image_url" class="w-8 h-11 rounded object-cover bg-dark-700" onerror="this.style.display='none'">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm text-white truncate" x-text="card.display_name || card.card_name"></p>
                                                <p class="text-xs text-dark-400" x-text="card.card_set_id + ' · ' + card.rarity"></p>
                                            </div>
                                            <span x-show="card.market_price" class="text-xs font-bold text-gold-400" x-text="'$' + parseFloat(card.market_price).toFixed(2)"></span>
                                        </a>
                                    </template>
                                </div>
                            </template>

                            <template x-if="results.users.length > 0">
                                <div>
                                    <div class="px-3 py-2 text-xs font-bold text-dark-400 uppercase tracking-wider border-t border-dark-600">Users</div>
                                    <template x-for="u in results.users" :key="'u'+u.id">
                                        <a :href="'/user/' + u.username" class="flex items-center gap-3 px-3 py-2 hover:bg-dark-700/50 transition">
                                            <div class="w-8 h-8 rounded-full bg-gold-500 flex items-center justify-center text-dark-900 font-bold text-xs" x-text="u.username.charAt(0).toUpperCase()"></div>
                                            <span class="text-sm text-white" x-text="u.username"></span>
                                        </a>
                                    </template>
                                </div>
                            </template>

                            <template x-if="results.sets.length > 0">
                                <div>
                                    <div class="px-3 py-2 text-xs font-bold text-dark-400 uppercase tracking-wider border-t border-dark-600">Sets</div>
                                    <template x-for="s in results.sets" :key="'s'+s.set_id">
                                        <a :href="'/cards?set_id=' + s.set_id" class="flex items-center gap-3 px-3 py-2 hover:bg-dark-700/50 transition">
                                            <div class="w-8 h-8 rounded bg-dark-600 flex items-center justify-center"><i data-lucide="package" class="w-4 h-4 text-dark-300"></i></div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm text-white truncate" x-text="s.set_name"></p>
                                                <p class="text-xs text-dark-400" x-text="s.set_id + ' · ' + s.card_count + ' cards'"></p>
                                            </div>
                                        </a>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Language Selector -->
                    <div class="relative hidden md:block" x-data="{ langOpen: false }">
                        <button @click="langOpen = !langOpen" class="px-2 py-1.5 rounded-lg text-xs font-bold text-dark-300 hover:text-gold-400 hover:bg-dark-700/50 transition uppercase">
                            <?= $currentLang ?>
                        </button>
                        <div x-show="langOpen" @click.outside="langOpen = false" x-transition class="absolute right-0 mt-1 glass-strong rounded-lg shadow-xl py-1 min-w-[120px] z-50">
                            <?php foreach ($langs as $code => $name): ?>
                                <button onclick="setLanguage('<?= $code ?>')" class="block w-full text-left px-3 py-1.5 text-sm text-dark-300 hover:text-gold-400 hover:bg-dark-700/50 transition <?= $currentLang === $code ? 'text-gold-400' : '' ?>">
                                    <?= $name ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($isLoggedIn): ?>
                        <a href="/profile" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-dark-700/50 transition">
                            <?php if ($currentUser['avatar']): ?>
                                <img src="<?= htmlspecialchars($currentUser['avatar']) ?>" class="w-7 h-7 rounded-full" alt="">
                            <?php else: ?>
                                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-gold-500 to-gold-300 flex items-center justify-center text-dark-900 font-bold text-xs">
                                    <?= strtoupper(substr($currentUser['username'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <span class="text-sm font-medium text-dark-300 hidden lg:block"><?= htmlspecialchars($currentUser['username']) ?></span>
                        </a>
                        <a href="/logout" class="p-2 text-dark-400 hover:text-red-400 transition" title="Logout">
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                        </a>
                    <?php else: ?>
                        <a href="/login" class="px-4 py-2 text-sm font-medium text-dark-300 hover:text-white transition">Login</a>
                        <a href="/register" class="px-4 py-2 bg-gradient-to-r from-gold-500 to-amber-600 text-dark-900 rounded-lg text-sm font-bold hover:from-gold-400 hover:to-amber-500 transition shadow-lg shadow-gold-500/10">Sign Up</a>
                    <?php endif; ?>

                    <!-- Mobile search + menu -->
                    <button @click="mobileSearch = !mobileSearch" class="md:hidden p-2 text-dark-300 hover:text-white">
                        <i data-lucide="search" class="w-5 h-5"></i>
                    </button>
                    <button @click="mobileMenu = !mobileMenu" class="md:hidden p-2 text-dark-300 hover:text-white">
                        <i x-show="!mobileMenu" data-lucide="menu" class="w-5 h-5"></i>
                        <i x-show="mobileMenu" data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Search Overlay -->
        <div x-show="mobileSearch" x-transition class="md:hidden px-4 pb-3" x-data="globalSearch()" @click.outside="mobileSearch = false">
            <div class="relative">
                <i data-lucide="search" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-dark-400"></i>
                <input type="text" x-model="query" @input.debounce.250ms="search()" placeholder="Search..."
                    class="w-full pl-9 pr-4 py-2.5 bg-dark-800 border border-dark-600 rounded-lg text-sm text-white placeholder-dark-400 focus:outline-none focus:border-gold-500/50">
            </div>
            <div x-show="open && results.cards.length > 0" class="mt-2 glass rounded-lg max-h-60 overflow-y-auto">
                <template x-for="card in results.cards" :key="'mc'+card.id">
                    <a :href="'/cards/' + card.card_set_id" class="flex items-center gap-3 px-3 py-2 hover:bg-dark-700/50 transition">
                        <img :src="card.card_image_url" class="w-6 h-8 rounded object-cover" onerror="this.style.display='none'">
                        <span class="text-sm text-white truncate" x-text="card.display_name || card.card_name"></span>
                    </a>
                </template>
            </div>
        </div>

        <!-- Mobile Nav -->
        <div x-show="mobileMenu" x-transition class="md:hidden border-t border-dark-600 px-4 py-3 space-y-1">
            <a href="/cards" class="flex items-center gap-2 px-3 py-2 rounded text-dark-300 hover:text-gold-400 text-sm"><i data-lucide="layers" class="w-4 h-4"></i> Cards</a>
            <a href="/market" class="flex items-center gap-2 px-3 py-2 rounded text-dark-300 hover:text-gold-400 text-sm"><i data-lucide="trending-up" class="w-4 h-4"></i> Market</a>
            <?php if ($isLoggedIn): ?>
                <a href="/dashboard" class="flex items-center gap-2 px-3 py-2 rounded text-dark-300 hover:text-gold-400 text-sm"><i data-lucide="layout-dashboard" class="w-4 h-4"></i> Dashboard</a>
                <a href="/collection" class="flex items-center gap-2 px-3 py-2 rounded text-dark-300 hover:text-gold-400 text-sm"><i data-lucide="folder-open" class="w-4 h-4"></i> Collection</a>
                <a href="/analytics" class="flex items-center gap-2 px-3 py-2 rounded text-dark-300 hover:text-gold-400 text-sm"><i data-lucide="bar-chart-3" class="w-4 h-4"></i> Analytics</a>
                <a href="/friends" class="flex items-center gap-2 px-3 py-2 rounded text-dark-300 hover:text-gold-400 text-sm"><i data-lucide="users" class="w-4 h-4"></i> Friends</a>
                <a href="/profile" class="flex items-center gap-2 px-3 py-2 rounded text-dark-300 hover:text-gold-400 text-sm"><i data-lucide="user" class="w-4 h-4"></i> Profile</a>
                <a href="/logout" class="flex items-center gap-2 px-3 py-2 rounded text-red-400 text-sm"><i data-lucide="log-out" class="w-4 h-4"></i> Logout</a>
            <?php else: ?>
                <a href="/login" class="flex items-center gap-2 px-3 py-2 rounded text-dark-300 text-sm">Login</a>
                <a href="/register" class="flex items-center gap-2 px-3 py-2 rounded text-gold-400 font-bold text-sm">Sign Up</a>
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

    <footer class="border-t border-dark-800 mt-16">
        <div class="max-w-7xl mx-auto px-4 py-8 text-center text-dark-400 text-sm">
            <p>&copy; <?= date('Y') ?> MyOPCards. Not affiliated with Bandai or One Piece.</p>
            <p class="mt-1">Card data via OPTCG API. Prices are indicative and may differ from actual market values.</p>
        </div>
    </footer>

    <script>
        function showToast(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const colors = { success: 'bg-green-600', error: 'bg-red-600', info: 'bg-blue-600' };
            toast.className = `${colors[type] || colors.info} text-white px-5 py-3 rounded-xl shadow-2xl toast-enter text-sm font-medium flex items-center gap-2`;
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
                    try {
                        const res = await fetch('/api/search?q=' + encodeURIComponent(this.query));
                        this.results = await res.json();
                    } catch(e) { this.results = { cards: [], users: [], sets: [] }; }
                    this.loading = false; this.activeIdx = -1;
                },
                close() { this.open = false; },
                moveDown() { this.activeIdx = Math.min(this.activeIdx + 1, this.results.cards.length - 1); },
                moveUp() { this.activeIdx = Math.max(this.activeIdx - 1, 0); },
                go() {
                    if (this.activeIdx >= 0 && this.results.cards[this.activeIdx]) {
                        window.location = '/cards/' + this.results.cards[this.activeIdx].card_set_id;
                    }
                }
            }
        }

        async function setLanguage(lang) {
            await apiPost('/settings/language', { lang: lang });
            location.reload();
        }

    </script>
    <script src="/assets/js/app.js"></script>
</body>
</html>
