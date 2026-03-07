<?php
$decks = $decks ?? [];
$user = $user ?? null;
$userId = (int)($user['id'] ?? 0);
$username = isset($user['username']) ? json_encode($user['username'], JSON_HEX_APOS) : '""';
$decksJson = json_encode($decks, JSON_HEX_APOS | JSON_HEX_TAG);
$safeUsername = str_replace("'", "&#39;", $username);
$safeDecks = str_replace("'", "&#39;", $decksJson);
$elo = $elo ?? null;
$myRank = $myRank ?? null;
$eloRating = (int)($elo['elo_rating'] ?? 1000);
$wins = (int)($elo['wins'] ?? 0);
$losses = (int)($elo['losses'] ?? 0);
$gamesPlayed = (int)($elo['games_played'] ?? 0);
$streak = (int)($elo['streak'] ?? 0);
?>
<style>
.lobby-wrap { max-width: 960px; margin: 0 auto; padding: 40px 24px; }
.lobby-hero { text-align: center; margin-bottom: 48px; }
.lobby-hero h1 { font-family: 'Playfair Display', serif; font-size: 2.8rem; font-weight: 800; color: #fff; letter-spacing: -0.02em; }
.lobby-hero p { color: rgba(255,255,255,0.45); font-size: 1.05rem; margin-top: 8px; }
.lobby-hero .status-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #22c55e; margin-right: 6px; animation: pulse-dot 2s infinite; }
@keyframes pulse-dot { 0%,100% { opacity: 1; } 50% { opacity: 0.4; } }

.no-deck-card {
  background: linear-gradient(135deg, rgba(245,158,11,0.08) 0%, rgba(245,158,11,0.02) 100%);
  border: 1px solid rgba(245,158,11,0.25);
  border-radius: 20px;
  padding: 40px;
  text-align: center;
  margin-bottom: 40px;
}
.no-deck-card h2 { font-size: 1.5rem; font-weight: 700; color: #fff; margin-bottom: 10px; }
.no-deck-card p { color: rgba(255,255,255,0.5); margin-bottom: 24px; max-width: 480px; margin-left: auto; margin-right: auto; }
.no-deck-card .cta { display: inline-flex; align-items: center; gap: 8px; padding: 14px 32px; background: #f59e0b; color: #000; border-radius: 14px; font-weight: 700; text-decoration: none; font-size: 1rem; transition: all 0.2s; }
.no-deck-card .cta:hover { background: #fbbf24; transform: translateY(-2px); box-shadow: 0 8px 24px rgba(245,158,11,0.3); }

.deck-picker { margin-bottom: 36px; }
.deck-picker label { display: block; font-size: 0.85rem; color: rgba(255,255,255,0.4); margin-bottom: 8px; font-weight: 500; }
.deck-picker select {
  width: 100%; max-width: 360px;
  padding: 12px 16px;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 12px;
  color: #fff;
  font-size: 1rem;
  appearance: none;
  cursor: pointer;
  transition: border-color 0.2s;
}
.deck-picker select:focus { outline: none; border-color: rgba(245,158,11,0.5); }
.deck-picker select option { background: #1a1a2e; color: #fff; }

.deck-cards-row { display: flex; flex-wrap: wrap; gap: 16px; margin-top: 12px; }
.deck-card {
  display: flex; flex-direction: column; align-items: center;
  width: 120px; padding: 12px; border-radius: 14px;
  background: rgba(255,255,255,0.04); border: 2px solid rgba(255,255,255,0.1);
  color: #fff; cursor: pointer; transition: all 0.2s; text-align: center;
}
.deck-card:hover { border-color: rgba(255,255,255,0.2); background: rgba(255,255,255,0.06); }
.deck-card.selected { border-color: #f59e0b; box-shadow: 0 0 16px rgba(245,158,11,0.35); background: rgba(245,158,11,0.08); }
.deck-card-img-wrap {
  width: 80px; height: 112px; border-radius: 8px; overflow: hidden; margin-bottom: 10px;
  background: rgba(0,0,0,0.3);
}
.deck-card-img-wrap img { width: 100%; height: 100%; object-fit: cover; display: block; }
.deck-card-name { font-weight: 600; font-size: 0.9rem; margin-bottom: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%; }
.deck-card-meta { font-size: 0.75rem; color: rgba(255,255,255,0.5); }

.mode-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 32px; }
@media (max-width: 768px) { .mode-grid { grid-template-columns: 1fr; } }

.mode-card {
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px;
  padding: 32px 24px;
  text-align: center;
  transition: all 0.25s;
  position: relative;
  overflow: hidden;
}
.mode-card::before {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 50% 0%, rgba(245,158,11,0.06) 0%, transparent 70%);
  opacity: 0;
  transition: opacity 0.3s;
}
.mode-card:hover { border-color: rgba(255,255,255,0.15); transform: translateY(-4px); }
.mode-card:hover::before { opacity: 1; }
.mode-card h2 { font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 6px; position: relative; }
.mode-card .mode-desc { color: rgba(255,255,255,0.4); font-size: 0.85rem; margin-bottom: 20px; position: relative; }
.mode-card .mode-icon { font-size: 2.5rem; margin-bottom: 16px; display: block; }

.mode-btn {
  display: block;
  width: 100%;
  padding: 12px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  position: relative;
  margin-bottom: 8px;
}
.mode-btn:last-child { margin-bottom: 0; }
.mode-btn:disabled { opacity: 0.35; cursor: not-allowed; }

.mode-btn.primary { background: #f59e0b; color: #000; }
.mode-btn.primary:hover:not(:disabled) { background: #fbbf24; }
.mode-btn.secondary { background: rgba(255,255,255,0.06); color: rgba(255,255,255,0.8); border: 1px solid rgba(255,255,255,0.1); }
.mode-btn.secondary:hover:not(:disabled) { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); }

.room-code-box { margin-top: 16px; padding: 14px; background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.25); border-radius: 12px; text-align: center; }
.room-code-label { display: block; font-size: 0.8rem; color: rgba(255,255,255,0.5); margin-bottom: 6px; }
.room-code-value { font-size: 1.5rem; font-weight: 800; letter-spacing: 0.2em; color: #f59e0b; margin-bottom: 10px; }
.custom-or {
  text-align: center; font-size: 0.8rem; color: rgba(255,255,255,0.3);
  padding: 10px 0 8px; text-transform: uppercase; letter-spacing: 0.1em; font-weight: 600;
  position: relative;
}
.custom-join-input {
  display: block; width: 100%; padding: 10px 14px;
  background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
  border-radius: 10px; color: #fff; font-size: 0.95rem; text-align: center;
  letter-spacing: 0.1em; margin-bottom: 8px;
}
.custom-join-input:focus { outline: none; border-color: rgba(245,158,11,0.5); }
.custom-join-full { margin-top: 0; }

.lobby-msg {
  text-align: center;
  padding: 12px 20px;
  border-radius: 12px;
  font-size: 0.9rem;
  font-weight: 500;
  animation: fade-in 0.3s;
}
.lobby-msg.info { background: rgba(255,255,255,0.04); color: rgba(255,255,255,0.6); }
.lobby-msg.error { background: rgba(239,68,68,0.08); color: #ef4444; }
@keyframes fade-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="lobby-wrap" x-data='lobbyPage(<?= $userId ?>, <?= $safeUsername ?>, <?= $safeDecks ?>)' x-init="init()">

    <div class="lobby-hero">
        <h1>Play Online</h1>
        <p><span class="status-dot"></span>Find a match, challenge a bot, or play with friends</p>
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:16px;align-items:center;justify-content:center;margin-bottom:36px;">
        <div style="display:flex;align-items:center;gap:10px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:14px;padding:12px 20px;">
            <span style="font-size:2rem;font-weight:800;color:#f59e0b;"><?= $eloRating ?></span>
            <span style="color:rgba(255,255,255,0.4);font-size:0.85rem;">ELO</span>
            <?php if ($myRank): ?>
            <span style="background:rgba(245,158,11,0.12);color:#f59e0b;padding:3px 8px;border-radius:8px;font-size:0.75rem;font-weight:700;">#<?= $myRank ?></span>
            <?php endif; ?>
        </div>
        <div style="display:flex;gap:16px;">
            <div style="text-align:center;"><span style="display:block;font-size:1.2rem;font-weight:700;color:#22c55e;"><?= $wins ?></span><span style="font-size:0.7rem;color:rgba(255,255,255,0.35);">Wins</span></div>
            <div style="text-align:center;"><span style="display:block;font-size:1.2rem;font-weight:700;color:#ef4444;"><?= $losses ?></span><span style="font-size:0.7rem;color:rgba(255,255,255,0.35);">Losses</span></div>
            <div style="text-align:center;"><span style="display:block;font-size:1.2rem;font-weight:700;color:#fff;"><?= $gamesPlayed ?></span><span style="font-size:0.7rem;color:rgba(255,255,255,0.35);">Games</span></div>
            <?php if ($streak !== 0): ?>
            <div style="text-align:center;"><span style="display:block;font-size:1.2rem;font-weight:700;color:<?= $streak > 0 ? '#22c55e' : '#ef4444' ?>;"><?= $streak > 0 ? '+' . $streak : $streak ?></span><span style="font-size:0.7rem;color:rgba(255,255,255,0.35);">Streak</span></div>
            <?php endif; ?>
        </div>
        <a href="/leaderboard" style="display:inline-flex;align-items:center;gap:6px;padding:10px 18px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.1);border-radius:12px;color:rgba(255,255,255,0.7);font-size:0.85rem;font-weight:600;text-decoration:none;transition:all 0.2s;" onmouseover="this.style.borderColor='rgba(245,158,11,0.4)';this.style.color='#f59e0b'" onmouseout="this.style.borderColor='rgba(255,255,255,0.1)';this.style.color='rgba(255,255,255,0.7)'">&#127942; Leaderboard</a>
    </div>

    <template x-if="!decks.length">
        <div class="no-deck-card">
            <h2>You need a deck to play</h2>
            <p>Build your first deck: pick a Leader and add 50 cards of matching colors. It only takes a minute.</p>
            <a href="/decks/create" class="cta">Create my first deck</a>
        </div>
    </template>

    <template x-if="decks.length">
        <div>
            <div class="deck-picker">
                <label>Select your deck</label>
                <div class="deck-cards-row">
                    <template x-for="d in decks" :key="d.id">
                        <button type="button" class="deck-card" :class="{ selected: selectedDeckId == d.id }" @click="selectedDeckId = String(d.id)">
                            <div class="deck-card-img-wrap">
                                <img :src="deckLeaderImage(d)" :alt="d.leader_name || ''" />
                            </div>
                            <div class="deck-card-name" x-text="d.name"></div>
                            <div class="deck-card-meta" x-text="(d.card_count || 50) + ' cards'"></div>
                        </button>
                    </template>
                </div>
            </div>

            <div class="mode-grid">
                <div class="mode-card">
                    <span class="mode-icon">&#9876;</span>
                    <h2>Ranked</h2>
                    <p class="mode-desc">Climb the ELO ladder against real players</p>
                    <button class="mode-btn primary" @click="findMatch('ranked')" :disabled="queueing || !selectedDeckId">Find Ranked Match</button>
                    <button class="mode-btn secondary" @click="findMatch('casual')" :disabled="queueing || !selectedDeckId">Casual Match</button>
                </div>

                <div class="mode-card">
                    <span class="mode-icon">&#129302;</span>
                    <h2>VS Bot</h2>
                    <p class="mode-desc">Practice and test your deck against AI</p>
                    <button class="mode-btn primary" @click="vsBot('easy')" :disabled="queueing || !selectedDeckId">Easy Bot</button>
                    <button class="mode-btn secondary" @click="vsBot('medium')" :disabled="queueing || !selectedDeckId">Medium Bot</button>
                    <button class="mode-btn secondary" @click="vsBot('hard')" :disabled="queueing || !selectedDeckId">Hard Bot</button>
                </div>

                <div class="mode-card">
                    <span class="mode-icon">&#128279;</span>
                    <h2>Custom Game</h2>
                    <p class="mode-desc">Create a room or join a friend's game</p>
                    <button class="mode-btn primary" @click="createCustom()" :disabled="queueing || !selectedDeckId">Create Room</button>
                    <div x-show="roomCode" class="room-code-box" x-transition>
                        <span class="room-code-label">Share this code:</span>
                        <div class="room-code-value" x-text="roomCode"></div>
                        <button type="button" class="mode-btn secondary" @click="navigator.clipboard && navigator.clipboard.writeText(roomCode)">Copy</button>
                    </div>
                    <div class="custom-or">or</div>
                    <input type="text" x-model="joinCode" placeholder="Enter room code..." maxlength="8" class="custom-join-input">
                    <button class="mode-btn primary custom-join-full" @click="joinCustom()" :disabled="!joinCode.trim() || !selectedDeckId">Join Room</button>
                </div>
            </div>

            <div x-show="message" class="lobby-msg" :class="messageType === 'error' ? 'error' : 'info'" x-text="message" x-transition></div>
        </div>
    </template>
</div>

<script src="https://cdn.socket.io/4.7.5/socket.io.min.js"></script>
<script src="/assets/js/game/lobby.js"></script>
