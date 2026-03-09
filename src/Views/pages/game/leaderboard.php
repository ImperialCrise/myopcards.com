<?php
$top = $top ?? [];
$me = $me ?? null;
$myRank = $myRank ?? null;
$currentUserId = $currentUserId ?? null;
$rankColors = [
    1 => ['row' => 'bg-yellow-500/10 border-yellow-500/30', 'num' => 'text-yellow-400', 'medal' => '🥇'],
    2 => ['row' => 'bg-slate-400/10 border-slate-400/30', 'num' => 'text-slate-300', 'medal' => '🥈'],
    3 => ['row' => 'bg-amber-700/10 border-amber-700/30', 'num' => 'text-amber-600', 'medal' => '🥉'],
];
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

    <div class="glass rounded-2xl overflow-hidden">
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
                <?php foreach (array_values($top) as $i => $row): $rank = $i + 1;
                    $isMe = $currentUserId && (int)($row['user_id'] ?? 0) === $currentUserId;
                    $rc = $rankColors[$rank] ?? null;
                    $rowClass = $rc ? 'border-t ' . $rc['row'] : 'border-t border-dark-700 hover:bg-dark-800/30';
                ?>
                <tr class="<?= $rowClass ?>">
                    <td class="py-3 px-4 font-bold <?= $rc ? $rc['num'] : 'text-dark-400' ?>">
                        <?= $rc ? $rc['medal'] . ' ' . $rank : $rank ?>
                    </td>
                    <td class="py-3 px-4 text-white">
                        <?php if ($isMe): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md border border-blue-500/70 bg-blue-500/15 text-blue-400 font-semibold"><?= htmlspecialchars($row['username'] ?? '') ?></span>
                            <span class="ml-1 text-xs text-blue-400/60">(<?= t('leaderboard.you') ?>)</span>
                        <?php else: ?>
                            <?= htmlspecialchars($row['username'] ?? '') ?>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4 text-right text-gold-400 font-semibold"><?= (int)($row['elo_rating'] ?? 0) ?></td>
                    <td class="py-3 px-4 text-right text-white"><?= (int)($row['wins'] ?? 0) ?></td>
                    <td class="py-3 px-4 text-right text-white"><?= (int)($row['losses'] ?? 0) ?></td>
                    <td class="py-3 px-4 text-right text-white"><?= (int)($row['streak'] ?? 0) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if (empty($top)): ?>
        <p class="p-8 text-center text-dark-400"><?= t('leaderboard.empty') ?></p>
        <?php endif; ?>
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
document.addEventListener('alpine:init', function () {
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
