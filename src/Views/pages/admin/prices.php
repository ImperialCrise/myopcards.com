<div class="space-y-6" x-data="adminPrices()">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-display font-bold text-gray-900">Price Management</h1>
            <p class="text-sm text-gray-500 mt-1">Manage price sources and sync operations</p>
        </div>
        <div class="flex gap-2">
            <a href="/admin/logs" class="px-3 py-2 glass rounded-lg text-sm text-gray-600 hover:text-gray-900 transition flex items-center gap-1.5"><i data-lucide="file-text" class="w-4 h-4"></i> Logs</a>
            <a href="/admin" class="px-3 py-2 glass rounded-lg text-sm text-gray-600 hover:text-gray-900 transition flex items-center gap-1.5"><i data-lucide="arrow-left" class="w-4 h-4"></i> Admin</a>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="glass rounded-xl p-4 text-center">
            <p class="text-2xl font-display font-bold text-emerald-600"><?= number_format($stats['usd_count']) ?></p>
            <p class="text-xs text-gray-400 mt-1">USD Prices</p>
            <p class="text-[10px] text-gray-300"><?= round($stats['usd_count'] / max($stats['total_cards'],1) * 100) ?>% coverage</p>
        </div>
        <div class="glass rounded-xl p-4 text-center">
            <p class="text-2xl font-display font-bold text-blue-600"><?= number_format($stats['eur_en_count']) ?></p>
            <p class="text-xs text-gray-400 mt-1">EUR EN Prices</p>
            <p class="text-[10px] text-gray-300"><?= round($stats['eur_en_count'] / max($stats['total_cards'],1) * 100) ?>% coverage</p>
        </div>
        <div class="glass rounded-xl p-4 text-center">
            <p class="text-2xl font-display font-bold text-indigo-600"><?= number_format($stats['eur_fr_count']) ?></p>
            <p class="text-xs text-gray-400 mt-1">EUR FR Prices</p>
            <p class="text-[10px] text-gray-300"><?= round($stats['eur_fr_count'] / max($stats['total_cards'],1) * 100) ?>% coverage</p>
        </div>
        <div class="glass rounded-xl p-4 text-center">
            <p class="text-2xl font-display font-bold text-red-600"><?= number_format($stats['eur_jp_count']) ?></p>
            <p class="text-xs text-gray-400 mt-1">EUR JP Prices</p>
            <p class="text-[10px] text-gray-300"><?= round($stats['eur_jp_count'] / max($stats['total_cards'],1) * 100) ?>% coverage</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-gray-900 mb-4">Sync Actions</h2>
            <div class="space-y-3">
                <button @click="runSync('cards')" :disabled="syncing" class="w-full flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-xl hover:bg-gray-100 transition disabled:opacity-50">
                    <div class="flex items-center gap-3">
                        <i data-lucide="refresh-cw" class="w-5 h-5 text-blue-500" :class="syncing === 'cards' && 'animate-spin'"></i>
                        <div class="text-left">
                            <p class="text-sm font-bold text-gray-900">Sync Cards</p>
                            <p class="text-xs text-gray-400">Re-fetch all card data from OPTCG API</p>
                        </div>
                    </div>
                    <i data-lucide="play" class="w-4 h-4 text-gray-400"></i>
                </button>
                <button @click="runSync('tcg')" :disabled="syncing" class="w-full flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-xl hover:bg-gray-100 transition disabled:opacity-50">
                    <div class="flex items-center gap-3">
                        <i data-lucide="dollar-sign" class="w-5 h-5 text-emerald-500" :class="syncing === 'tcg' && 'animate-spin'"></i>
                        <div class="text-left">
                            <p class="text-sm font-bold text-gray-900">Sync TCGPlayer Prices</p>
                            <p class="text-xs text-gray-400">Update USD prices from OPTCG API</p>
                        </div>
                    </div>
                    <i data-lucide="play" class="w-4 h-4 text-gray-400"></i>
                </button>
                <div class="p-4 bg-gray-50 border border-gray-200 rounded-xl space-y-3">
                    <div class="flex items-center gap-3">
                        <i data-lucide="euro" class="w-5 h-5 text-blue-500" :class="syncing === 'cm' && 'animate-spin'"></i>
                        <div class="text-left">
                            <p class="text-sm font-bold text-gray-900">Scrape Cardmarket Prices</p>
                            <p class="text-xs text-gray-400">Fetch EUR prices from Cardmarket</p>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <select x-model="cmEdition" class="flex-1 px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm">
                            <option value="en">English</option>
                            <option value="fr">French</option>
                            <option value="jp">Japanese</option>
                        </select>
                        <input type="number" x-model="cmLimit" min="1" max="500" class="w-20 px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm text-center" placeholder="Limit">
                        <button @click="runSync('cm')" :disabled="syncing" class="px-4 py-2 bg-blue-600 rounded-lg text-sm font-bold transition hover:bg-blue-700 disabled:opacity-50" style="color:#fff !important">Run</button>
                    </div>
                </div>
                <button @click="runSync('snapshot')" :disabled="syncing" class="w-full flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-xl hover:bg-gray-100 transition disabled:opacity-50">
                    <div class="flex items-center gap-3">
                        <i data-lucide="camera" class="w-5 h-5 text-purple-500" :class="syncing === 'snapshot' && 'animate-spin'"></i>
                        <div class="text-left">
                            <p class="text-sm font-bold text-gray-900">Take Collection Snapshots</p>
                            <p class="text-xs text-gray-400">Record current collection values for all users</p>
                        </div>
                    </div>
                    <i data-lucide="play" class="w-4 h-4 text-gray-400"></i>
                </button>
            </div>
        </div>

        <div class="glass rounded-2xl p-6">
            <h2 class="text-lg font-display font-bold text-gray-900 mb-4">Price History Sources</h2>
            <?php if (empty($history)): ?>
                <p class="text-sm text-gray-400 text-center py-8">No price history recorded yet</p>
            <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($history as $h): ?>
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($h['source']) ?> / <?= htmlspecialchars($h['edition']) ?></p>
                        <p class="text-xs text-gray-400"><?= htmlspecialchars($h['earliest']) ?> to <?= htmlspecialchars($h['latest']) ?></p>
                    </div>
                    <span class="text-sm font-bold text-gray-900"><?= number_format((int)$h['cnt']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="mt-6 p-4 rounded-xl <?= $flaresolverrOk ? 'bg-emerald-50 border border-emerald-200' : 'bg-amber-50 border border-amber-200' ?>">
                <p class="text-sm font-bold <?= $flaresolverrOk ? 'text-emerald-800' : 'text-amber-800' ?>">FlareSolverr Status</p>
                <?php if ($flaresolverrOk): ?>
                    <p class="text-xs text-emerald-600 mt-1">FlareSolverr is running and ready. Cardmarket scraping via Cloudflare bypass is operational.</p>
                <?php else: ?>
                    <p class="text-xs text-amber-600 mt-1">FlareSolverr is not reachable at <code class="text-[10px]"><?= htmlspecialchars($_ENV['FLARESOLVERR_URL'] ?? 'N/A') ?></code>. Run <code class="text-[10px]">docker compose up -d</code> in the project root. Without it, Cardmarket scraping will fail.</p>
                <?php endif; ?>
            </div>

            <div x-show="syncResult" x-cloak class="mt-4 p-4 rounded-lg text-sm" :class="syncResult?.success ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                <p class="font-bold" x-text="syncResult?.message"></p>
            </div>
        </div>

        <div class="glass rounded-2xl p-6 lg:col-span-2">
            <h2 class="text-lg font-display font-bold text-gray-900 mb-4">Import Prices (CSV)</h2>
            <p class="text-sm text-gray-500 mb-4">Upload a CSV with columns: <code class="text-xs bg-gray-100 px-1 rounded">card_set_id,price_en,price_fr,price_jp</code> (leave columns empty to skip)</p>
            <form action="/admin/prices/import" method="POST" enctype="multipart/form-data" class="flex flex-wrap gap-3 items-center">
                <input type="file" name="csv" accept=".csv,.txt" class="flex-1 text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-gray-900 file:text-white file:font-bold file:text-sm file:cursor-pointer hover:file:bg-gray-800 transition">
                <button type="submit" class="px-4 py-2 bg-blue-600 rounded-lg text-sm font-bold transition hover:bg-blue-700" style="color:#fff !important">Import</button>
            </form>
        </div>
    </div>
</div>

<script>
function adminPrices() {
    return {
        syncing: false,
        cmEdition: 'en',
        cmLimit: 50,
        syncResult: null,
        async runSync(type) {
            this.syncing = type;
            this.syncResult = null;
            try {
                var url, data = {};
                if (type === 'cards') url = '/admin/sync/cards';
                else if (type === 'tcg') url = '/admin/sync/prices-tcg';
                else if (type === 'snapshot') url = '/admin/sync/snapshot';
                else { url = '/admin/sync/prices-cardmarket'; data = { edition: this.cmEdition, limit: this.cmLimit }; }
                var res = await apiPost(url, data);
                this.syncResult = res;
                if (res.success) showToast(res.message);
                else showToast(res.message || 'Sync failed', 'error');
            } catch(e) {
                this.syncResult = { success: false, message: 'Network error' };
                showToast('Network error', 'error');
            }
            this.syncing = false;
        }
    }
}
</script>
