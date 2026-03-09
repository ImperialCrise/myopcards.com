<?php $gameId = (int)($gameId ?? 0); $userId = (int)($userId ?? 0); ?>
<link rel="stylesheet" href="/assets/css/game.css">

<div class="game-wrap" x-data="gameBoard(<?= $gameId ?>, <?= $userId ?>)" x-init="init()" @click="cancelAttack(); donAttachMode = false" @mousemove="previewMove($event)">

    <div class="game-notification" x-show="notification" x-transition>
        <span x-text="notification"></span>
    </div>

    <div class="game-tutorial-overlay" x-show="showTutorial" x-transition x-cloak>
        <div class="game-tutorial-card">
            <template x-if="tutorialStep === 1">
                <div class="tutorial-step">
                    <h3><?= htmlspecialchars(t('game.tutorial_welcome_title')) ?></h3>
                    <p><?= htmlspecialchars(t('game.tutorial_welcome')) ?></p>
                </div>
            </template>
            <template x-if="tutorialStep === 2">
                <div class="tutorial-step">
                    <h3><?= htmlspecialchars(t('game.tutorial_hand_title')) ?></h3>
                    <p><?= htmlspecialchars(t('game.tutorial_hand')) ?></p>
                </div>
            </template>
            <template x-if="tutorialStep === 3">
                <div class="tutorial-step">
                    <h3><?= htmlspecialchars(t('game.tutorial_attack_title')) ?></h3>
                    <p><?= htmlspecialchars(t('game.tutorial_attack')) ?></p>
                </div>
            </template>
            <template x-if="tutorialStep === 4">
                <div class="tutorial-step">
                    <h3><?= htmlspecialchars(t('game.tutorial_flow_title')) ?></h3>
                    <p><?= htmlspecialchars(t('game.tutorial_flow')) ?></p>
                </div>
            </template>
            <template x-if="tutorialStep === 5">
                <div class="tutorial-step">
                    <h3><?= htmlspecialchars(t('game.tutorial_first_turn_title')) ?></h3>
                    <p><?= htmlspecialchars(t('game.tutorial_first_turn')) ?></p>
                </div>
            </template>
            <div class="tutorial-dots">
                <span class="tutorial-dot" :class="{ active: tutorialStep === 1 }"></span>
                <span class="tutorial-dot" :class="{ active: tutorialStep === 2 }"></span>
                <span class="tutorial-dot" :class="{ active: tutorialStep === 3 }"></span>
                <span class="tutorial-dot" :class="{ active: tutorialStep === 4 }"></span>
                <span class="tutorial-dot" :class="{ active: tutorialStep === 5 }"></span>
            </div>
            <div class="tutorial-actions">
                <button type="button" class="tutorial-btn secondary" @click="closeTutorial()"><?= t('game.tutorial_skip') ?></button>
                <button type="button" class="tutorial-btn primary" @click="tutorialStep < 5 ? nextTutorial() : closeTutorial()" x-text="tutorialStep < 5 ? 'Next' : 'Got it'"></button>
            </div>
        </div>
    </div>

    <div class="card-preview-popup" x-show="hoveredCard" x-transition
        :style="'left:' + previewX + 'px; top:' + previewY + 'px'">
        <template x-if="hoveredCard">
            <div class="preview-inner">
                <img :src="cardImageSrc(hoveredCard.card_image_url)" class="preview-img" />
                <div class="preview-stats">
                    <div class="preview-name" x-text="hoveredCard.card_name || ''"></div>
                    <div class="preview-meta">
                        <span class="preview-type-badge" x-text="hoveredCard.card_type || ''"></span>
                        <span class="preview-color-badge" x-show="hoveredCard.card_color" x-text="hoveredCard.card_color"></span>
                    </div>
                    <div class="preview-nums">
                        <div class="preview-num" x-show="hoveredCard.card_cost != null">
                            <span class="pn-label">Cost</span>
                            <span class="pn-val cost-val" x-text="hoveredCard.card_cost"></span>
                        </div>
                        <div class="preview-num" x-show="hoveredCard.card_power != null">
                            <span class="pn-label">Power</span>
                            <span class="pn-val pow-val" x-text="cardPower(hoveredCard)"></span>
                        </div>
                        <div class="preview-num" x-show="hoveredCard.counter_amount > 0">
                            <span class="pn-label">Counter</span>
                            <span class="pn-val cnt-val" x-text="'+' + hoveredCard.counter_amount"></span>
                        </div>
                        <div class="preview-num" x-show="hoveredCard.attachedDon > 0">
                            <span class="pn-label">DON!!</span>
                            <span class="pn-val don-val" x-text="hoveredCard.attachedDon"></span>
                        </div>
                    </div>
                    <div class="preview-text" x-html="formatCardText(hoveredCard.card_text)"></div>
                </div>
            </div>
        </template>
    </div>

    <div class="game-layout">

    <aside class="game-sidebar">
        <div class="sb-section">
            <div class="sb-label">Game</div>
            <div class="sb-row"><span>ID</span><span x-text="'#' + gameId"></span></div>
            <div class="sb-row"><span>Turn</span><span x-text="turnCount()"></span></div>
            <div class="sb-row"><span>Duration</span><span x-text="gameDuration()"></span></div>
            <div class="sb-row"><span>Status</span><span class="sb-status" :class="isMyTurn() ? 'your-turn' : 'opp-turn'" x-text="isMyTurn() ? 'Your Turn' : 'Opponent'"></span></div>
        </div>
        <div class="sb-section">
            <div class="sb-label"><?= t('game.time_left') ?></div>
            <div class="sb-row sb-timer-row">
                <span class="sb-timer-name" x-text="me() && me().username ? me().username : 'You'"></span>
                <span class="sb-timer-val" :class="{ 'sb-timer-warn': timerWarning(0) }" x-text="playerTimeDisplay(0)"></span>
            </div>
            <div class="sb-row sb-timer-row">
                <span class="sb-timer-name" x-text="opp() && opp().username ? opp().username : 'Opponent'"></span>
                <span class="sb-timer-val" :class="{ 'sb-timer-warn': timerWarning(1) }" x-text="playerTimeDisplay(1)"></span>
            </div>
        </div>
        <div class="sb-section">
            <div class="sb-label" x-text="me() && me().username ? me().username : 'You'"></div>
            <div class="sb-row"><span>ELO</span><span style="color:#f59e0b;font-weight:700;" x-text="me() && me().elo ? me().elo : '—'"></span></div>
            <div class="sb-row"><span>Life</span><span class="sb-life" x-text="myLife()"></span></div>
            <div class="sb-row"><span>DON!!</span><span class="sb-don" x-text="myActiveDon() + ' / ' + myTotalDon()"></span></div>
            <div class="sb-row"><span>DON!! deck</span><span x-text="myDonDeck()"></span></div>
            <div class="sb-row"><span>Hand</span><span x-text="handList().length"></span></div>
            <div class="sb-row"><span>Deck</span><span x-text="myDeckCount()"></span></div>
            <div class="sb-row"><span>Trash</span><span x-text="myTrashCount()"></span></div>
            <div class="sb-row"><span>Characters</span><span x-text="myChars().length + ' / 5'"></span></div>
        </div>
        <div class="sb-section">
            <div class="sb-label" x-text="opp() && opp().username ? opp().username : 'Opponent'"></div>
            <div class="sb-row"><span>ELO</span><span style="color:#f59e0b;font-weight:700;" x-text="opp() && opp().elo ? opp().elo : '—'"></span></div>
            <div class="sb-row"><span>Life</span><span class="sb-life" x-text="oppLife()"></span></div>
            <div class="sb-row"><span>DON!!</span><span class="sb-don" x-text="oppActiveDon() + ' / ' + oppTotalDon()"></span></div>
            <div class="sb-row"><span>Hand</span><span x-text="oppHandCount()"></span></div>
            <div class="sb-row"><span>Deck</span><span x-text="oppDeckCount()"></span></div>
            <div class="sb-row"><span>Characters</span><span x-text="oppChars().length + ' / 5'"></span></div>
        </div>
        <div class="sb-section sb-log">
            <div class="sb-label">Action Log</div>
            <template x-for="(entry, li) in gameLog()" :key="'log'+li">
                <div class="sb-log-line" x-text="entry.msg"></div>
            </template>
        </div>
        <div class="sb-section">
            <button type="button" class="sb-help" @click="reopenTutorial()" title="Tutorial">?</button>
            <a href="/play" class="sb-link"><?= t('game.back_lobby') ?></a>
        </div>
    </aside>

    <div class="game-main">
    <div class="game-board">

        <div class="opp-info-bar">
            <span class="pname" x-text="opp() && opp().username ? opp().username : 'Opponent'"></span>
            <div class="life-pips">
                <template x-for="i in oppLife()"><span class="pip"></span></template>
                <template x-for="i in oppLostLife()"><span class="pip lost"></span></template>
            </div>
            <span class="stat" x-text="oppHandCount() + ' cards'"></span>
            <span class="stat" x-text="'DON!! ' + oppActiveDon() + '/' + oppTotalDon()"></span>
            <span class="stat" x-text="'Deck ' + oppDeckCount()"></span>
        </div>

        <div class="field opp-field">
            <div class="zone leader-zone" @click.stop="onOppLeaderClick()">
                <template x-if="oppLeader()">
                    <div class="bcard" id="opp-leader" :class="{ 'rested': oppLeader().rested, 'atk-target': attackMode }">
                        <img :src="cardImageSrc(oppLeader().card_image_url)" />
                        <span class="pwr" x-text="cardPower(oppLeader())"></span>
                    </div>
                </template>
                <template x-if="!oppLeader()"><div class="empty-slot">Leader</div></template>
            </div>

            <div class="zone stage-zone">
                <template x-if="oppStage()">
                    <div class="bcard"><img :src="cardImageSrc(oppStage().card_image_url)" /></div>
                </template>
                <template x-if="!oppStage()"><div class="empty-slot">Stage</div></template>
            </div>

            <div class="chars-zone">
                <template x-for="(c, ci) in oppChars()" :key="'oc'+ci">
                    <div class="zone" @click.stop="onOppCharClick(ci)">
                        <div class="bcard" :id="'opp-char-' + ci" :class="{ 'rested': c.rested, 'atk-target': attackMode && c.rested }"
                            @mouseenter="showPreview(c, $event)" @mouseleave="hidePreview()">
                            <img :src="cardImageSrc(c.card_image_url)" />
                            <span class="pwr" x-text="cardPower(c)"></span>
                            <span class="don-tag" x-show="c.attachedDon > 0" x-text="'+' + c.attachedDon"></span>
                        </div>
                    </div>
                </template>
                <template x-for="i in Math.max(0, 5 - oppChars().length)" :key="'oe'+i">
                    <div class="zone"><div class="empty-slot"></div></div>
                </template>
            </div>
        </div>

        <div class="center-bar">
            <span class="turn-num" x-text="'Turn ' + turnCount()"></span>
            <span class="phase-tag" :class="{ 'my-turn': isMyTurn() }"
                x-text="isMyTurn() ? 'Your Turn — Main Phase' : 'Opponent\'s Turn'"></span>
            <span class="log-msg" x-show="lastAction" x-text="lastAction"></span>
        </div>

        <div class="field my-field">
            <div class="zone leader-zone" @click.stop="onMyLeaderClick()">
                <template x-if="myLeader()">
                    <div class="bcard" id="my-leader" :class="{ 'rested': myLeader().rested, 'can-atk': canAttackWith('leader'), 'don-target': donAttachMode }"
                        @mouseenter="showPreview(myLeader(), $event)" @mouseleave="hidePreview()">
                        <img :src="cardImageSrc(myLeader().card_image_url)" />
                        <span class="pwr" x-text="cardPower(myLeader())"></span>
                        <span class="don-tag" x-show="myLeader().attachedDon > 0" x-text="'+' + myLeader().attachedDon"></span>
                    </div>
                </template>
            </div>

            <div class="zone stage-zone">
                <template x-if="myStage()">
                    <div class="bcard" @mouseenter="showPreview(myStage(), $event)" @mouseleave="hidePreview()">
                        <img :src="cardImageSrc(myStage().card_image_url)" /></div>
                </template>
                <template x-if="!myStage()"><div class="empty-slot">Stage</div></template>
            </div>

            <div class="chars-zone">
                <template x-for="(c, ci) in myChars()" :key="'mc'+ci">
                    <div class="zone" @click.stop="onMyCharClick(ci)">
                        <div class="bcard" :id="'my-char-' + ci" :class="{ 'rested': c.rested, 'can-atk': canAttackWithChar(ci), 'sick': c.summonSick, 'don-target': donAttachMode }"
                            @mouseenter="showPreview(c, $event)" @mouseleave="hidePreview()">
                            <img :src="cardImageSrc(c.card_image_url)" />
                            <span class="pwr" x-text="cardPower(c)"></span>
                            <span class="don-tag" x-show="c.attachedDon > 0" x-text="'+' + c.attachedDon"></span>
                            <span class="sick-tag" x-show="c.summonSick">NEW</span>
                        </div>
                    </div>
                </template>
                <template x-for="i in Math.max(0, 5 - myChars().length)" :key="'me'+i">
                    <div class="zone"><div class="empty-slot"></div></div>
                </template>
            </div>
        </div>

        <div class="my-info-bar">
            <div class="life-pips">
                <template x-for="i in myLife()"><span class="pip"></span></template>
                <template x-for="i in myLostLife()"><span class="pip lost"></span></template>
            </div>
            <span class="stat don-highlight" x-text="'DON!! ' + myActiveDon() + '/' + myTotalDon() + ' (' + myDonDeck() + ' left)'"></span>
            <button class="don-attach-btn" :class="{ active: donAttachMode }" x-show="isMyTurn() && myActiveDon() > 0 && !hasCombatPrompt()" @click.stop="toggleDonAttach()">
                <span x-text="donAttachMode ? 'Cancel DON!!' : 'Attach DON!!'"></span>
            </button>
            <span class="stat" x-text="'Deck ' + myDeckCount()"></span>
            <span class="stat" x-text="'Trash ' + myTrashCount()"></span>
        </div>
    </div>

    <div class="hand-area">
        <div class="hand-cards">
            <template x-for="(card, idx) in handList()" :key="'h'+idx">
                <div class="hcard" :class="{ 'playable': canPlayCard(card), 'dim': !canPlayCard(card) && isMyTurn() && !hasCombatPrompt() }"
                    @click.stop="onHandCardClick(idx)"
                    @mouseenter="showPreview(card, $event)" @mouseleave="hidePreview()">
                    <img :src="cardImageSrc(card.card_image_url)" :alt="card.card_name || ''" class="hcard-img" />
                    <span class="cost-badge" x-text="card.card_cost ?? '0'"></span>
                    <span class="counter-badge" x-show="card.counter_amount > 0" x-text="'+' + card.counter_amount"></span>
                </div>
            </template>
        </div>
        <div class="hand-actions">
            <button class="end-turn-btn" :class="{ 'active': isMyTurn() && !hasCombatPrompt() }"
                :disabled="!isMyTurn() || hasCombatPrompt()" @click.stop="endTurn()"
                x-text="isMyTurn() ? 'End Turn' : 'Waiting...'">
            </button>
        </div>
    </div>

    <div class="attack-overlay" x-show="attackMode" x-transition @click.stop>
        <div class="attack-msg">
            Click opponent's <b>Leader</b> or a <b>rested Character</b> to attack.
            <button @click.stop="cancelAttack()" class="cancel-atk">Cancel</button>
        </div>
    </div>

    <div class="combat-overlay" x-show="hasCombatPrompt()" x-transition x-cloak @click.stop>
        <div class="combat-panel">
            <h3 class="combat-title">You are under attack!</h3>
            <div class="combat-power-display">
                <div class="combat-side atk-side">
                    <span class="combat-label">Attacker</span>
                    <span class="combat-power" x-text="combatInfo() ? combatInfo().attackerPower : 0"></span>
                </div>
                <span class="combat-vs">VS</span>
                <div class="combat-side def-side">
                    <span class="combat-label">Defender</span>
                    <span class="combat-power" x-text="combatInfo() ? combatInfo().defenderPower : 0"></span>
                    <span class="combat-boost" x-show="combatInfo() && combatInfo().counterBoost > 0" x-text="'(+' + (combatInfo() ? combatInfo().counterBoost : 0) + ' counter)'"></span>
                </div>
            </div>

            <div class="combat-status" :class="combatInfo() && combatInfo().attackerPower < combatInfo().defenderPower ? 'safe' : 'danger'"
                x-text="combatInfo() && combatInfo().attackerPower < combatInfo().defenderPower ? 'Attack will fail — you are safe!' : 'Attack will hit!'"></div>

            <div class="combat-section" x-show="combatBlockers().length > 0">
                <h4>Use Blocker</h4>
                <p class="combat-hint">Rest a Blocker character to redirect the attack.</p>
                <div class="combat-options">
                    <template x-for="(b, bi) in combatBlockers()" :key="'blk'+bi">
                        <button class="combat-card-btn" @click.stop="onUseBlocker(b.charIndex)">
                            <img :src="cardImageSrc(b.card_image_url)" class="combat-card-img" />
                            <span class="combat-card-name" x-text="b.card_name"></span>
                            <span class="combat-card-pwr" x-text="(parseInt(b.card_power,10)||0) + (b.attachedDon||0)*1000"></span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="combat-section" x-show="combatCounters().length > 0">
                <h4>Play Counter Cards</h4>
                <p class="combat-hint">Discard cards to boost your defender's power.</p>
                <div class="combat-options">
                    <template x-for="(ct, ci) in combatCounters()" :key="'cnt'+ci">
                        <button class="combat-card-btn counter-btn" @click.stop="onPlayCounter(ct.handIndex)">
                            <img :src="cardImageSrc(ct.card_image_url)" class="combat-card-img" />
                            <span class="combat-card-name" x-text="ct.card_name"></span>
                            <span class="combat-counter-val" x-text="'+' + ct.counterValue"></span>
                        </button>
                    </template>
                </div>
            </div>

            <div class="combat-actions">
                <button class="combat-done-btn" @click.stop="onFinishDefense()">Done Defending</button>
            </div>
        </div>
    </div>

    <div class="atk-anim-layer" id="atkAnimLayer"></div>

    <div class="screen-flash" x-show="screenFlash" x-transition.opacity.duration.150ms></div>

    <div class="game-over-overlay" x-show="state && state.status === 'finished'" x-transition x-cloak>
        <div class="go-box" :class="gameOverData && gameOverData.won ? 'go-win' : 'go-lose'">
            <div class="go-result-icon" x-text="gameOverData && gameOverData.won ? '&#127942;' : '&#128148;'"></div>
            <h2 class="go-title" x-text="gameOverData && gameOverData.won ? 'VICTORY' : 'DEFEAT'"></h2>
            <div class="go-elo-section" x-show="gameOverData && gameOverData.you">
                <div class="go-elo-row">
                    <span class="go-elo-label">Your ELO</span>
                    <span class="go-elo-old" x-text="gameOverData && gameOverData.you ? gameOverData.you.oldElo : ''"></span>
                    <span class="go-elo-arrow">&rarr;</span>
                    <span class="go-elo-new" x-text="gameOverData && gameOverData.you ? gameOverData.you.newElo : ''"></span>
                    <span class="go-elo-change" :class="gameOverData && gameOverData.you && gameOverData.you.change >= 0 ? 'positive' : 'negative'"
                        x-text="gameOverData && gameOverData.you ? (gameOverData.you.change >= 0 ? '+' + gameOverData.you.change : gameOverData.you.change) : ''"></span>
                </div>
            </div>
            <div class="go-stats">
                <div class="go-stat"><span class="go-stat-val" x-text="gameOverData ? gameOverData.turns : turnCount()"></span><span class="go-stat-lbl">Turns</span></div>
                <div class="go-stat"><span class="go-stat-val" x-text="gameOverData && gameOverData.duration != null ? (Math.floor(gameOverData.duration/60) + ':' + (gameOverData.duration%60 < 10 ? '0' : '') + (gameOverData.duration%60)) : gameDuration()"></span><span class="go-stat-lbl"><?= t('game.duration') ?></span></div>
                <div class="go-stat"><span class="go-stat-val" x-text="gameOverData && gameOverData.you && gameOverData.you.timeRemaining != null ? (Math.floor(gameOverData.you.timeRemaining/60) + ':' + (gameOverData.you.timeRemaining%60 < 10 ? '0' : '') + (gameOverData.you.timeRemaining%60)) : '—'"></span><span class="go-stat-lbl"><?= t('game.your_time_left') ?></span></div>
                <div class="go-stat"><span class="go-stat-val" x-text="gameOverData && gameOverData.opponent && gameOverData.opponent.timeRemaining != null ? (Math.floor(gameOverData.opponent.timeRemaining/60) + ':' + (gameOverData.opponent.timeRemaining%60 < 10 ? '0' : '') + (gameOverData.opponent.timeRemaining%60)) : '—'"></span><span class="go-stat-lbl"><?= t('game.opp_time_left') ?></span></div>
                <div class="go-stat"><span class="go-stat-val" x-text="gameOverData && gameOverData.gameType ? gameOverData.gameType.charAt(0).toUpperCase() + gameOverData.gameType.slice(1) : 'Casual'"></span><span class="go-stat-lbl">Mode</span></div>
            </div>
            <div class="go-matchup" x-show="gameOverData && gameOverData.opponent">
                <div class="go-player go-you">
                    <span class="go-p-name" x-text="gameOverData && gameOverData.you ? gameOverData.you.username : 'You'"></span>
                    <span class="go-p-elo" x-text="gameOverData && gameOverData.you ? gameOverData.you.newElo + ' ELO' : ''"></span>
                </div>
                <span class="go-vs">vs</span>
                <div class="go-player go-opp">
                    <span class="go-p-name" x-text="gameOverData && gameOverData.opponent ? gameOverData.opponent.username : 'Opponent'"></span>
                    <span class="go-p-elo" x-text="gameOverData && gameOverData.opponent ? gameOverData.opponent.newElo + ' ELO' : ''"></span>
                </div>
            </div>
            <div class="go-actions">
                <a href="/play" class="go-btn go-btn-primary"><?= t('game.play_again') ?></a>
                <a href="/leaderboard" class="go-btn go-btn-secondary"><?= t('nav.leaderboard') ?></a>
            </div>
        </div>
    </div>

    </div>
    </div>
</div>

<script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
<script src="/assets/js/game/socket.js"></script>
<script>
(function () {
  var gameId = <?= $gameId ?>;
  var userId = <?= $userId ?>;
  if (typeof io === 'undefined' || !gameId) return;
  var url = (location.protocol === 'https:' ? 'wss:' : 'ws:') + '//' + location.host;
  var socket = io(url, { path: '/socket.io/', withCredentials: true });
  window.__gameSocket = { _raw: socket, on: socket.on.bind(socket), emit: socket.emit.bind(socket), off: socket.off.bind(socket) };
  window.__gameUserId = userId;
  socket.emit('joinGame', { gameId: gameId, userId: userId });
  socket.on('gameState', function (d) { window.__gameState = d; });
})();
</script>
<script src="/assets/js/game/board.js"></script>
