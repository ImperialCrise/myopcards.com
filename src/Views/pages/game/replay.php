<?php
$gameId = (int)($gameId ?? 0);
$game = $game ?? [];
$player1Name = $game['player1_name'] ?? 'Player 1';
$player2Name = $game['player2_name'] ?? 'Bot';
$backUrl = $backUrl ?? '/history';
?>
<link rel="stylesheet" href="/assets/css/game.css">
<div class="replay-wrap" x-data="replayViewer(<?= $gameId ?>)" x-init="load()">
    <div class="replay-header">
        <a href="<?= htmlspecialchars($backUrl) ?>" class="replay-back"><?= $backUrl === '/leaderboard' ? t('game.back_leaderboard') : t('game.back_history') ?></a>
        <h1 class="replay-title"><?= t('game.replay') ?> #<?= $gameId ?></h1>
        <p class="replay-matchup" x-text="(snapshots[currentIndex] && snapshots[currentIndex].move_data) ? (snapshots[currentIndex].move_data.p2?.username || '<?= htmlspecialchars($player2Name) ?>') + ' vs ' + (snapshots[currentIndex].move_data.p1?.username || '<?= htmlspecialchars($player1Name) ?>') : '<?= htmlspecialchars($player2Name) ?> vs <?= htmlspecialchars($player1Name) ?>'"></p>
    </div>

    <div class="replay-controls">
        <button type="button" class="replay-btn" @click="prev()" :disabled="currentIndex <= 0"><?= t('game.replay_prev') ?></button>
        <span class="replay-counter" x-text="(currentIndex + 1) + ' / ' + (snapshots.length || 1)"></span>
        <button type="button" class="replay-btn" @click="next()" :disabled="currentIndex >= (snapshots.length - 1) || !snapshots.length"><?= t('game.replay_next') ?></button>
    </div>

    <div class="replay-board" x-show="snapshots.length > 0">
        <template x-if="currentSnapshot()">
            <div class="game-board replay-board-inner">
                <div class="opp-info-bar">
                    <span class="pname" x-text="currentSnapshot()?.move_data?.p2?.username || 'Opponent'"></span>
                    <div class="life-pips">
                        <template x-for="i in Array(currentSnapshot()?.move_data?.p2?.life || 0).fill(0)"><span class="pip"></span></template>
                        <template x-for="i in Array(Math.max(0, (currentSnapshot()?.move_data?.p2?.lifeStartCount || 5) - (currentSnapshot()?.move_data?.p2?.life || 0))).fill(0)"><span class="pip lost"></span></template>
                    </div>
                    <span class="stat" x-text="(currentSnapshot()?.move_data?.p2?.handCount || 0) + ' cards'"></span>
                    <span class="stat" x-text="'DON!! ' + (currentSnapshot()?.move_data?.p2?.donCount || 0)"></span>
                    <span class="stat" x-text="'Deck ' + (currentSnapshot()?.move_data?.p2?.deckCount || 0)"></span>
                </div>
                <div class="field opp-field">
                    <div class="zone leader-zone">
                        <template x-if="currentSnapshot()?.move_data?.p2?.leader">
                            <div class="bcard" :class="{ 'rested': currentSnapshot()?.move_data?.p2?.leader?.rested }">
                                <img :src="cardImg(currentSnapshot()?.move_data?.p2?.leader?.card_image_url)" />
                                <span class="pwr" x-text="(parseInt(currentSnapshot()?.move_data?.p2?.leader?.card_power) || 0) + ((currentSnapshot()?.move_data?.p2?.leader?.attachedDon || 0) * 1000)"></span>
                            </div>
                        </template>
                        <template x-if="!currentSnapshot()?.move_data?.p2?.leader"><div class="empty-slot">Leader</div></template>
                    </div>
                    <div class="zone stage-zone">
                        <template x-if="currentSnapshot()?.move_data?.p2?.stage">
                            <div class="bcard"><img :src="cardImg(currentSnapshot()?.move_data?.p2?.stage?.card_image_url)" /></div>
                        </template>
                        <template x-if="!currentSnapshot()?.move_data?.p2?.stage"><div class="empty-slot">Stage</div></template>
                    </div>
                    <div class="chars-zone">
                        <template x-for="(c, ci) in (currentSnapshot()?.move_data?.p2?.characters || [])" :key="'oc'+ci">
                            <div class="zone">
                                <div class="bcard" :class="{ 'rested': c.rested }">
                                    <img :src="cardImg(c.card_image_url)" />
                                    <span class="pwr" x-text="(parseInt(c.card_power) || 0) + ((c.attachedDon || 0) * 1000)"></span>
                                    <span class="don-tag" x-show="c.attachedDon > 0" x-text="'+' + c.attachedDon"></span>
                                </div>
                            </div>
                        </template>
                        <template x-for="i in Math.max(0, 5 - (currentSnapshot()?.move_data?.p2?.characters?.length || 0))" :key="'oe'+i">
                            <div class="zone"><div class="empty-slot"></div></div>
                        </template>
                    </div>
                </div>
                <div class="center-bar">
                    <span class="turn-num" x-text="'Turn ' + (currentSnapshot()?.move_data?.turn || 0)"></span>
                </div>
                <div class="field my-field">
                    <div class="zone leader-zone">
                        <template x-if="currentSnapshot()?.move_data?.p1?.leader">
                            <div class="bcard" :class="{ 'rested': currentSnapshot()?.move_data?.p1?.leader?.rested }">
                                <img :src="cardImg(currentSnapshot()?.move_data?.p1?.leader?.card_image_url)" />
                                <span class="pwr" x-text="(parseInt(currentSnapshot()?.move_data?.p1?.leader?.card_power) || 0) + ((currentSnapshot()?.move_data?.p1?.leader?.attachedDon || 0) * 1000)"></span>
                                <span class="don-tag" x-show="(currentSnapshot()?.move_data?.p1?.leader?.attachedDon || 0) > 0" x-text="'+' + (currentSnapshot()?.move_data?.p1?.leader?.attachedDon || 0)"></span>
                            </div>
                        </template>
                        <template x-if="!currentSnapshot()?.move_data?.p1?.leader"><div class="empty-slot">Leader</div></template>
                    </div>
                    <div class="zone stage-zone">
                        <template x-if="currentSnapshot()?.move_data?.p1?.stage">
                            <div class="bcard"><img :src="cardImg(currentSnapshot()?.move_data?.p1?.stage?.card_image_url)" /></div>
                        </template>
                        <template x-if="!currentSnapshot()?.move_data?.p1?.stage"><div class="empty-slot">Stage</div></template>
                    </div>
                    <div class="chars-zone">
                        <template x-for="(c, ci) in (currentSnapshot()?.move_data?.p1?.characters || [])" :key="'mc'+ci">
                            <div class="zone">
                                <div class="bcard" :class="{ 'rested': c.rested }">
                                    <img :src="cardImg(c.card_image_url)" />
                                    <span class="pwr" x-text="(parseInt(c.card_power) || 0) + ((c.attachedDon || 0) * 1000)"></span>
                                    <span class="don-tag" x-show="c.attachedDon > 0" x-text="'+' + c.attachedDon"></span>
                                </div>
                            </div>
                        </template>
                        <template x-for="i in Math.max(0, 5 - (currentSnapshot()?.move_data?.p1?.characters?.length || 0))" :key="'me'+i">
                            <div class="zone"><div class="empty-slot"></div></div>
                        </template>
                    </div>
                </div>
                <div class="my-info-bar">
                    <div class="life-pips">
                        <template x-for="i in Array(currentSnapshot()?.move_data?.p1?.life || 0).fill(0)"><span class="pip"></span></template>
                        <template x-for="i in Array(Math.max(0, (currentSnapshot()?.move_data?.p1?.lifeStartCount || 5) - (currentSnapshot()?.move_data?.p1?.life || 0))).fill(0)"><span class="pip lost"></span></template>
                    </div>
                    <span class="stat" x-text="(currentSnapshot()?.move_data?.p1?.handCount || 0) + ' cards'"></span>
                    <span class="stat" x-text="'DON!! ' + (currentSnapshot()?.move_data?.p1?.donCount || 0)"></span>
                    <span class="stat" x-text="'Deck ' + (currentSnapshot()?.move_data?.p1?.deckCount || 0)"></span>
                </div>
            </div>
        </template>
    </div>

    <div class="replay-loading" x-show="loading"><?= t('game.replay_loading') ?></div>
    <div class="replay-empty" x-show="!loading && snapshots.length === 0"><?= t('game.replay_no_data') ?></div>
</div>

<style>
.replay-wrap { max-width: 900px; margin: 0 auto; padding: 24px; }
.replay-header { margin-bottom: 24px; }
.replay-back { color: rgba(255,255,255,0.6); text-decoration: none; font-size: 0.9rem; display: inline-block; margin-bottom: 8px; }
.replay-back:hover { color: #f59e0b; }
.replay-title { font-size: 1.75rem; font-weight: 700; color: #fff; margin: 0 0 4px 0; }
.replay-matchup { color: rgba(255,255,255,0.5); font-size: 0.95rem; margin: 0; }
.replay-controls { display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 24px; }
.replay-btn { padding: 10px 24px; background: rgba(245,158,11,0.2); color: #f59e0b; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; }
.replay-btn:hover:not(:disabled) { background: rgba(245,158,11,0.35); }
.replay-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.replay-counter { color: #fff; font-weight: 600; min-width: 80px; text-align: center; }
.replay-board-inner { pointer-events: none; }
.replay-loading, .replay-empty { text-align: center; color: rgba(255,255,255,0.5); padding: 48px; }
</style>

<script>
document.addEventListener('alpine:init', function () {
  Alpine.data('replayViewer', function (gameId) {
    return {
      gameId: gameId,
      moves: [],
      snapshots: [],
      currentIndex: 0,
      loading: true,
      load: function () {
        var self = this;
        fetch('/api/game/' + gameId + '/moves')
          .then(function (r) { return r.json(); })
          .then(function (data) {
            self.moves = data.moves || [];
            self.snapshots = self.moves.filter(function (m) { return m.move_type === 'snapshot'; });
            self.currentIndex = 0;
          })
          .catch(function () { self.snapshots = []; })
          .finally(function () { self.loading = false; });
      },
      currentSnapshot: function () {
        return this.snapshots[this.currentIndex] || null;
      },
      prev: function () {
        if (this.currentIndex > 0) this.currentIndex--;
      },
      next: function () {
        if (this.currentIndex < this.snapshots.length - 1) this.currentIndex++;
      },
      cardImg: function (url) {
        if (!url || typeof url !== 'string') return '/assets/img/card-back.png';
        if (url.indexOf('optcgapi.com') !== -1) return '/uploads/cards/' + url.split('/').pop();
        return url;
      }
    };
  });
});
</script>
