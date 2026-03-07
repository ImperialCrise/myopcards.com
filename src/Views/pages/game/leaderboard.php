<?php
$top = $top ?? [];
$me = $me ?? null;
$myRank = $myRank ?? null;
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
                <?php foreach (array_values($top) as $i => $row): $rank = $i + 1; ?>
                <tr class="border-t border-dark-700 hover:bg-dark-800/30">
                    <td class="py-3 px-4 text-white font-medium"><?= $rank ?></td>
                    <td class="py-3 px-4 text-white"><?= htmlspecialchars($row['username'] ?? '') ?></td>
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
</div>
