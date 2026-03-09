<?php
$games = $games ?? [];
$myId = (int)\App\Core\Auth::id();
?>
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-display font-bold text-white"><?= t('game.history') ?></h1>
            <p class="text-sm text-dark-400 mt-1"><?= t('game.history_subtitle') ?></p>
        </div>
        <a href="/play" class="px-4 py-2 rounded-xl bg-gold-500/20 text-gold-400 font-medium hover:bg-gold-500/30 transition"><?= t('game.back_lobby') ?></a>
    </div>

    <div class="glass rounded-2xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px]">
                <thead class="bg-dark-800/50">
                    <tr>
                        <th class="text-left py-3 px-4 text-dark-400 text-sm font-medium"><?= t('game.history_date') ?></th>
                        <th class="text-left py-3 px-4 text-dark-400 text-sm font-medium"><?= t('game.history_mode') ?></th>
                        <th class="text-left py-3 px-4 text-dark-400 text-sm font-medium"><?= t('game.history_vs') ?></th>
                        <th class="text-left py-3 px-4 text-dark-400 text-sm font-medium"><?= t('game.history_winner') ?></th>
                        <th class="text-right py-3 px-4 text-dark-400 text-sm font-medium"><?= t('game.history_turns') ?></th>
                        <th class="text-right py-3 px-4 text-dark-400 text-sm font-medium"><?= t('game.history_duration') ?></th>
                        <th class="text-right py-3 px-4 text-dark-400 text-sm font-medium"><?= t('game.your_time_left') ?></th>
                        <th class="text-center py-3 px-4 text-dark-400 text-sm font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($games as $g):
                        $opponent = ($g['player1_id'] ?? 0) == $myId ? ($g['player2_name'] ?? 'Bot') : ($g['player1_name'] ?? 'Bot');
                        if (empty($opponent)) $opponent = 'Bot';
                        $winnerId = (int)($g['winner_id'] ?? 0);
                        $winnerName = $winnerId === (int)($g['player1_id'] ?? 0) ? ($g['player1_name'] ?? 'Player 1') : ($g['player2_name'] ?? 'Player 2');
                        if ($winnerId === 0) $winnerName = '—';
                        $myTime = ($g['player1_id'] ?? 0) == $myId ? ($g['player1_time_remaining'] ?? null) : ($g['player2_time_remaining'] ?? null);
                        $duration = isset($g['duration_seconds']) ? gmdate('i:s', (int)$g['duration_seconds']) : '—';
                        $timeStr = $myTime !== null ? gmdate('i:s', (int)$myTime) : '—';
                        $dateStr = !empty($g['finished_at']) ? date('M j, H:i', strtotime($g['finished_at'])) : '—';
                    ?>
                    <tr class="border-t border-dark-700 hover:bg-dark-800/30">
                        <td class="py-3 px-4 text-white text-sm"><?= htmlspecialchars($dateStr) ?></td>
                        <td class="py-3 px-4 text-white text-sm capitalize"><?= htmlspecialchars($g['game_type'] ?? 'casual') ?></td>
                        <td class="py-3 px-4 text-white text-sm">vs <?= htmlspecialchars($opponent) ?></td>
                        <td class="py-3 px-4 text-white text-sm"><?= htmlspecialchars($winnerName) ?></td>
                        <td class="py-3 px-4 text-right text-white text-sm"><?= (int)($g['turn_count'] ?? 0) ?></td>
                        <td class="py-3 px-4 text-right text-white text-sm"><?= htmlspecialchars($duration) ?></td>
                        <td class="py-3 px-4 text-right text-white text-sm"><?= htmlspecialchars($timeStr) ?></td>
                        <td class="py-3 px-4 text-center">
                            <a href="/history/<?= (int)$g['id'] ?>" class="px-3 py-1.5 rounded-lg bg-gold-500/20 text-gold-400 text-sm font-medium hover:bg-gold-500/30 transition"><?= t('game.replay') ?></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (empty($games)): ?>
        <p class="p-8 text-center text-dark-400"><?= t('game.history_empty') ?></p>
        <?php endif; ?>
    </div>
</div>
