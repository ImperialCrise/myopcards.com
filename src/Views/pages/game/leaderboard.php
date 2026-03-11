<?php
$top = $top ?? [];
$me = $me ?? null;
$myRank = $myRank ?? null;
$currentUserId = $currentUserId ?? null;
$topJson = json_encode(array_values($top), JSON_HEX_TAG | JSON_HEX_APOS);
?>
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-display font-bold text-white"><?= t('leaderboard.title') ?></h1>
        <p class="text-sm text-dark-400 mt-1"><?= t('leaderboard.subtitle') ?></p>
    </div>

    <?php if ($me): ?>
    <div class="glass rounded-2xl p-5">
        <h2 class="text-sm font-display font-bold text-white mb-3"><?= t('leaderboard.your_stats') ?></h2>
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            <div><p class="text-2xl font-bold text-gold-400"><?= (int)($myRank ?? 0) ?></p><p class="text-xs text-dark-400"><?= t('leaderboard.rank') ?></p></div>
            <div><p class="text-2xl font-bold text-white"><?= (int)($me['elo_rating'] ?? 1000) ?></p><p class="text-xs text-dark-400"><?= t('leaderboard.elo') ?></p></div>
            <div><p class="text-2xl font-bold text-white"><?= (int)($me['wins'] ?? 0) ?></p><p class="text-xs text-dark-400"><?= t('leaderboard.wins') ?></p></div>
            <div><p class="text-2xl font-bold text-white"><?= (int)($me['losses'] ?? 0) ?></p><p class="text-xs text-dark-400"><?= t('leaderboard.losses') ?></p></div>
            <div><p class="text-2xl font-bold text-white"><?= (int)($me['streak'] ?? 0) ?></p><p class="text-xs text-dark-400"><?= t('leaderboard.streak') ?></p></div>
        </div>
    </div>
    <?php endif; ?>

    <div class="glass rounded-2xl overflow-hidden" x-data="leaderboardTable()" x-init="init()">
        <table class="w-full">
            <thead class="bg-dark-800/50">
                <tr>
                    <th class="text-left py-3 px-4 text-dark-400 text-sm font-medium">#</th>
                    <th class="text-left py-3 px-4 text-dark-400 text-sm font-medium"><?= t('leaderboard.player') ?></th>
                    <th class="text-right py-3 px-4 text-dark-400 text-sm font-medium"><?= t('leaderboard.elo') ?></th>
                    <th class="text-right py-3 px-4 text-dark-400 text-sm font-medium"><?= t('leaderboard.w') ?></th>
                    <th class="text-right py-3 px-4 text-dark-400 text-sm font-medium"><?= t('leaderboard.l') ?></th>
                    <th class="text-right py-3 px-4 text-dark-400 text-sm font-medium"><?= t('leaderboard.streak') ?></th>
                </tr>
            </thead>
            <tbody>
                <template x-for="row in pageRows" :key="row.user_id">
                    <tr :class="rowClass(row)">
                        <td class="py-3 px-4 font-bold" :class="rankNumClass(row.rank)">
                            <span x-text="rankLabel(row.rank)"></span>
                        </td>
                        <td class="py-3 px-4 text-white">
                            <template x-if="row.is_me">
                                <span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md border border-blue-500/70 bg-blue-500/15 text-blue-400 font-semibold" x-text="row.username"></span>
                                    <span class="ml-1 text-xs text-blue-400/60">(<?= t('leaderboard.you') ?>)</span>
                                </span>
                            </template>
                            <template x-if="!row.is_me">
                                <a :href="'/user/' + row.username" class="hover:text-gold-400 transition" x-text="row.username"></a>
                            </template>
                        </td>
                        <td class="py-3 px-4 text-right text-gold-400 font-semibold" x-text="row.elo_rating"></td>
                        <td class="py-3 px-4 text-right text-white" x-text="row.wins"></td>
                        <td class="py-3 px-4 text-right text-white" x-text="row.losses"></td>
                        <td class="py-3 px-4 text-right text-white" x-text="row.streak"></td>
                    </tr>
                </template>
            </tbody>
        </table>
        <p class="p-8 text-center text-dark-400" x-show="allRows.length === 0"><?= t('leaderboard.empty') ?></p>
        <!-- Pagination -->
        <div class="flex items-center justify-between px-4 py-3 border-t border-dark-700" x-show="totalPages > 1">
            <p class="text-xs text-dark-400">
                <span x-text="(page - 1) * perPage + 1"></span>–<span x-text="Math.min(page * perPage, allRows.length)"></span>
                / <span x-text="allRows.length"></span>
            </p>
            <div class="flex gap-1">
                <button @click="page--" :disabled="page <= 1"
                    class="px-3 py-1.5 glass rounded-lg text-sm text-dark-300 hover:text-white transition disabled:opacity-30">
                    <i data-lucide="chevron-left" class="w-4 h-4"></i>
                </button>
                <template x-for="p in pageRange" :key="p">
                    <button @click="page = p"
                        :class="p === page ? 'bg-gold-500 text-dark-900 font-bold' : 'glass text-dark-300 hover:text-white'"
                        class="px-3 py-1.5 rounded-lg text-sm transition" x-text="p"></button>
                </template>
                <button @click="page++" :disabled="page >= totalPages"
                    class="px-3 py-1.5 glass rounded-lg text-sm text-dark-300 hover:text-white transition disabled:opacity-30">
                    <i data-lucide="chevron-right" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="glass rounded-2xl overflow-hidden" x-data="globalGameHistory()" x-init="load()">
        <h2 class="text-lg font-display font-bold text-white p-5 pb-3"><?= t('game.global_history') ?></h2>
        <div class="overflow-x-auto max-h-[400px] overflow-y-auto">
            <table class="w-full min-w-[500px]">
                <thead class="bg-dark-800/50 sticky top-0">
                    <tr>
                        <th class="text-left py-3 px-4 text-dark-400 text-sm font-medium"><?= t('game.history_date') ?></th>
                        <th class="text-left py-3 px-4 text-dark-400 text-sm font-medium"><?= t('game.history_mode') ?></th>
                        <th class="text-left py-3 px-4 text-dark-400 text-sm font-medium"><?= t('game.history_vs') ?></th>
                        <th class="text-left py-3 px-4 text-dark-400 text-sm font-medium"><?= t('game.history_winner') ?></th>
                        <th class="text-right py-3 px-4 text-dark-400 text-sm font-medium"><?= t('game.history_turns') ?></th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="g in games" :key="g.id">
                        <tr class="border-t border-dark-700 hover:bg-dark-800/30">
                            <td class="py-3 px-4 text-white text-sm" x-text="formatDate(g.finished_at)"></td>
                            <td class="py-3 px-4 text-white text-sm capitalize" x-text="g.game_type || 'casual'"></td>
                            <td class="py-3 px-4 text-white text-sm">
                                <span x-text="g.player1_name || 'Player 1'"></span> vs <span x-text="g.player2_name || 'Bot'"></span>
                            </td>
                            <td class="py-3 px-4 text-white text-sm" x-text="winnerName(g)"></td>
                            <td class="py-3 px-4 text-right text-white text-sm" x-text="g.turn_count || 0"></td>
                            <td class="py-3 px-4 text-right">
                                <a :href="'/history/' + g.id + '?from=leaderboard'" class="inline-block px-3 py-1.5 rounded-lg bg-gold-500/20 text-gold-400 text-sm font-medium hover:bg-gold-500/30 transition"><?= t('game.replay') ?></a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <p class="p-6 text-center text-dark-400" x-show="!loading && games.length === 0"><?= t('game.global_history_empty') ?></p>
        <p class="p-6 text-center text-dark-400" x-show="loading"><?= t('game.replay_loading') ?></p>
    </div>
</div>

<script>
window.__LEADERBOARD_DATA = {
    rows: <?= $topJson ?>,
    currentUserId: <?= $currentUserId ? (int)$currentUserId : 'null' ?>
};
</script>
<script>
document.addEventListener('alpine:init', function () {
  Alpine.data('leaderboardTable', function () {
    return {
      allRows: [],
      page: 1,
      perPage: 20,
      init() {
        const d = window.__LEADERBOARD_DATA || {};
        const uid = d.currentUserId;
        this.allRows = (d.rows || []).map((r, i) => ({
          ...r,
          rank: i + 1,
          is_me: uid && parseInt(r.user_id) === parseInt(uid),
        }));
        // Jump to page where current user appears
        if (uid) {
          const idx = this.allRows.findIndex(r => r.is_me);
          if (idx >= 0) this.page = Math.floor(idx / this.perPage) + 1;
        }
      },
      get pageRows() {
        const start = (this.page - 1) * this.perPage;
        return this.allRows.slice(start, start + this.perPage);
      },
      get totalPages() {
        return Math.ceil(this.allRows.length / this.perPage);
      },
      get pageRange() {
        const start = Math.max(1, this.page - 2);
        const end = Math.min(this.totalPages, this.page + 2);
        const r = [];
        for (let i = start; i <= end; i++) r.push(i);
        return r;
      },
      rowClass(row) {
        if (row.rank === 1) return 'border-t bg-yellow-500/10 border-yellow-500/30';
        if (row.rank === 2) return 'border-t bg-slate-400/10 border-slate-400/30';
        if (row.rank === 3) return 'border-t bg-amber-700/10 border-amber-700/30';
        return 'border-t border-dark-700 hover:bg-dark-800/30';
      },
      rankNumClass(rank) {
        if (rank === 1) return 'text-yellow-400';
        if (rank === 2) return 'text-slate-300';
        if (rank === 3) return 'text-amber-600';
        return 'text-dark-400';
      },
      rankLabel(rank) {
        if (rank === 1) return '🥇 1';
        if (rank === 2) return '🥈 2';
        if (rank === 3) return '🥉 3';
        return rank;
      },
    };
  });

  Alpine.data('globalGameHistory', function () {
    return {
      games: [],
      loading: true,
      load: function () {
        var self = this;
        fetch('/api/game/history/global').then(function (r) { return r.json(); })
          .then(function (data) { self.games = data.games || []; })
          .catch(function () { self.games = []; })
          .finally(function () { self.loading = false; });
      },
      formatDate: function (d) {
        if (!d) return '—';
        var dt = new Date(d);
        var m = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][dt.getMonth()];
        return m + ' ' + dt.getDate() + ', ' + String(dt.getHours()).padStart(2,'0') + ':' + String(dt.getMinutes()).padStart(2,'0');
      },
      winnerName: function (g) {
        if (!g.winner_id) return '—';
        return String(g.winner_id) === String(g.player1_id) ? (g.player1_name || 'Player 1') : (g.player2_name || 'Bot');
      }
    };
  });
});
</script>
