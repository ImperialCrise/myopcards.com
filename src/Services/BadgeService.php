<?php

declare(strict_types=1);

namespace App\Services;

class BadgeService
{
    /**
     * Returns all badge definitions with their pixel-art SVG coin icons.
     * Each badge: id, name, description, category, tier (bronze/silver/gold/diamond), icon (SVG string)
     */
    public static function getAllBadges(): array
    {
        return [
            // ===== COLLECTION =====
            [
                'id' => 'first_card',
                'name' => 'First Card',
                'description' => 'Added your first card to the collection',
                'category' => 'collection',
                'tier' => 'bronze',
                'icon' => self::svgCard('#cd7f32'),
            ],
            [
                'id' => 'collector_10',
                'name' => 'Novice Collector',
                'description' => 'Collected 10 different cards',
                'category' => 'collection',
                'tier' => 'bronze',
                'icon' => self::svgCards('#cd7f32', 10),
            ],
            [
                'id' => 'collector_50',
                'name' => 'Collector',
                'description' => 'Collected 50 different cards',
                'category' => 'collection',
                'tier' => 'silver',
                'icon' => self::svgCards('#c0c0c0', 50),
            ],
            [
                'id' => 'collector_100',
                'name' => 'Centurion',
                'description' => 'Collected 100 different cards',
                'category' => 'collection',
                'tier' => 'silver',
                'icon' => self::svgCards('#c0c0c0', 100),
            ],
            [
                'id' => 'collector_500',
                'name' => 'Hoarder',
                'description' => 'Collected 500 different cards',
                'category' => 'collection',
                'tier' => 'gold',
                'icon' => self::svgCards('#ffd700', 500),
            ],
            [
                'id' => 'collector_1000',
                'name' => 'Treasure Hunter',
                'description' => 'Collected 1000 different cards',
                'category' => 'collection',
                'tier' => 'diamond',
                'icon' => self::svgTreasure('#b9f2ff'),
            ],
            [
                'id' => 'parallel_owner',
                'name' => 'Parallel Universe',
                'description' => 'Own at least one parallel card',
                'category' => 'collection',
                'tier' => 'gold',
                'icon' => self::svgParallel('#ffd700'),
            ],
            [
                'id' => 'sec_owner',
                'name' => 'Secret Finder',
                'description' => 'Own at least one SEC rarity card',
                'category' => 'collection',
                'tier' => 'diamond',
                'icon' => self::svgSecret('#b9f2ff'),
            ],

            // ===== VALUE =====
            [
                'id' => 'value_100',
                'name' => 'Bronze Vault',
                'description' => 'Collection worth $100 or more',
                'category' => 'value',
                'tier' => 'bronze',
                'icon' => self::svgVault('#cd7f32'),
            ],
            [
                'id' => 'value_500',
                'name' => 'Silver Vault',
                'description' => 'Collection worth $500 or more',
                'category' => 'value',
                'tier' => 'silver',
                'icon' => self::svgVault('#c0c0c0'),
            ],
            [
                'id' => 'value_1000',
                'name' => 'Gold Vault',
                'description' => 'Collection worth $1,000 or more',
                'category' => 'value',
                'tier' => 'gold',
                'icon' => self::svgVault('#ffd700'),
            ],
            [
                'id' => 'value_5000',
                'name' => 'Platinum Vault',
                'description' => 'Collection worth $5,000 or more',
                'category' => 'value',
                'tier' => 'diamond',
                'icon' => self::svgVault('#e5e4e2'),
            ],
            [
                'id' => 'value_10000',
                'name' => 'Diamond Vault',
                'description' => 'Collection worth $10,000 or more',
                'category' => 'value',
                'tier' => 'diamond',
                'icon' => self::svgVault('#b9f2ff'),
            ],

            // ===== COMPLETION =====
            [
                'id' => 'set_complete',
                'name' => 'Completionist',
                'description' => 'Completed at least one full set',
                'category' => 'completion',
                'tier' => 'gold',
                'icon' => self::svgStar('#ffd700'),
            ],
            [
                'id' => 'set_master',
                'name' => 'Set Master',
                'description' => 'Completed 3 or more full sets',
                'category' => 'completion',
                'tier' => 'diamond',
                'icon' => self::svgStars('#b9f2ff'),
            ],
            [
                'id' => 'all_rarities',
                'name' => 'Rainbow',
                'description' => 'Own cards of every rarity type',
                'category' => 'completion',
                'tier' => 'gold',
                'icon' => self::svgRainbow('#ffd700'),
            ],

            // ===== FORUM =====
            [
                'id' => 'first_post',
                'name' => 'First Words',
                'description' => 'Made your first forum post',
                'category' => 'forum',
                'tier' => 'bronze',
                'icon' => self::svgBubble('#cd7f32'),
            ],
            [
                'id' => 'forum_10',
                'name' => 'Chatterbox',
                'description' => 'Made 10 forum posts',
                'category' => 'forum',
                'tier' => 'bronze',
                'icon' => self::svgBubble('#cd7f32'),
            ],
            [
                'id' => 'forum_50',
                'name' => 'Regular',
                'description' => 'Made 50 forum posts',
                'category' => 'forum',
                'tier' => 'silver',
                'icon' => self::svgBubble('#c0c0c0'),
            ],
            [
                'id' => 'forum_100',
                'name' => 'Veteran Voice',
                'description' => 'Made 100 forum posts',
                'category' => 'forum',
                'tier' => 'gold',
                'icon' => self::svgBubble('#ffd700'),
            ],
            [
                'id' => 'first_topic',
                'name' => 'Conversation Starter',
                'description' => 'Created your first forum topic',
                'category' => 'forum',
                'tier' => 'bronze',
                'icon' => self::svgTopic('#cd7f32'),
            ],
            [
                'id' => 'topic_10',
                'name' => 'Discussion Leader',
                'description' => 'Created 10 forum topics',
                'category' => 'forum',
                'tier' => 'silver',
                'icon' => self::svgTopic('#c0c0c0'),
            ],

            // ===== GAME =====
            [
                'id' => 'first_game',
                'name' => 'First Battle',
                'description' => 'Played your first online game',
                'category' => 'game',
                'tier' => 'bronze',
                'icon' => self::svgSword('#cd7f32'),
            ],
            [
                'id' => 'gamer_10',
                'name' => 'Fighter',
                'description' => 'Played 10 games',
                'category' => 'game',
                'tier' => 'bronze',
                'icon' => self::svgSword('#cd7f32'),
            ],
            [
                'id' => 'gamer_50',
                'name' => 'Warrior',
                'description' => 'Played 50 games',
                'category' => 'game',
                'tier' => 'silver',
                'icon' => self::svgSword('#c0c0c0'),
            ],
            [
                'id' => 'gamer_100',
                'name' => 'Gladiator',
                'description' => 'Played 100 games',
                'category' => 'game',
                'tier' => 'gold',
                'icon' => self::svgSword('#ffd700'),
            ],
            [
                'id' => 'first_win',
                'name' => 'First Victory',
                'description' => 'Won your first online game',
                'category' => 'game',
                'tier' => 'bronze',
                'icon' => self::svgCrown('#cd7f32'),
            ],
            [
                'id' => 'streak_3',
                'name' => 'Hot Streak',
                'description' => 'Won 3 games in a row',
                'category' => 'game',
                'tier' => 'silver',
                'icon' => self::svgFlame('#c0c0c0'),
            ],
            [
                'id' => 'streak_5',
                'name' => 'On Fire',
                'description' => 'Won 5 games in a row',
                'category' => 'game',
                'tier' => 'gold',
                'icon' => self::svgFlame('#ffd700'),
            ],
            [
                'id' => 'streak_10',
                'name' => 'Unstoppable',
                'description' => 'Won 10 games in a row',
                'category' => 'game',
                'tier' => 'diamond',
                'icon' => self::svgFlame('#b9f2ff'),
            ],

            // ===== ELO =====
            [
                'id' => 'elo_bronze',
                'name' => 'Bronze Rank',
                'description' => 'Reached 1100+ ELO rating',
                'category' => 'elo',
                'tier' => 'bronze',
                'icon' => self::svgShield('#cd7f32'),
            ],
            [
                'id' => 'elo_silver',
                'name' => 'Silver Rank',
                'description' => 'Reached 1200+ ELO rating',
                'category' => 'elo',
                'tier' => 'silver',
                'icon' => self::svgShield('#c0c0c0'),
            ],
            [
                'id' => 'elo_gold',
                'name' => 'Gold Rank',
                'description' => 'Reached 1300+ ELO rating',
                'category' => 'elo',
                'tier' => 'gold',
                'icon' => self::svgShield('#ffd700'),
            ],
            [
                'id' => 'elo_diamond',
                'name' => 'Diamond Rank',
                'description' => 'Reached 1500+ ELO rating',
                'category' => 'elo',
                'tier' => 'diamond',
                'icon' => self::svgShield('#b9f2ff'),
            ],

            // ===== SOCIAL =====
            [
                'id' => 'first_friend',
                'name' => 'First Mate',
                'description' => 'Made your first friend',
                'category' => 'social',
                'tier' => 'bronze',
                'icon' => self::svgFriends('#cd7f32'),
            ],
            [
                'id' => 'social_5',
                'name' => 'Crew Member',
                'description' => 'Have 5 friends',
                'category' => 'social',
                'tier' => 'silver',
                'icon' => self::svgFriends('#c0c0c0'),
            ],
            [
                'id' => 'social_10',
                'name' => 'Popular',
                'description' => 'Have 10 friends',
                'category' => 'social',
                'tier' => 'gold',
                'icon' => self::svgFriends('#ffd700'),
            ],
            [
                'id' => 'social_25',
                'name' => 'Celebrity',
                'description' => 'Have 25 friends',
                'category' => 'social',
                'tier' => 'diamond',
                'icon' => self::svgFriends('#b9f2ff'),
            ],

            // ===== PROFILE =====
            [
                'id' => 'profile_complete',
                'name' => 'All Set',
                'description' => 'Has bio, avatar, and a featured card',
                'category' => 'profile',
                'tier' => 'silver',
                'icon' => self::svgProfile('#c0c0c0'),
            ],
            [
                'id' => 'deck_builder',
                'name' => 'Deck Architect',
                'description' => 'Created at least one deck',
                'category' => 'profile',
                'tier' => 'bronze',
                'icon' => self::svgDeck('#cd7f32'),
            ],
        ];
    }

    /**
     * Compute which badges the user has earned based on their stats.
     * Returns array with badge_id => earned_at (null = not earned)
     */
    public static function computeBadges(array $data): array
    {
        $cards       = (int)($data['stats']['unique_cards'] ?? 0);
        $value       = (float)($data['stats']['total_value_usd'] ?? $data['stats']['total_value'] ?? 0);
        $friends     = (int)($data['friendCount'] ?? 0);
        $posts       = (int)($data['forumStats']['post_count'] ?? 0);
        $topics      = (int)($data['forumStats']['topic_count'] ?? 0);
        $games       = (int)($data['leaderboard']['games_played'] ?? 0);
        $wins        = (int)($data['leaderboard']['wins'] ?? 0);
        $streak      = (int)($data['leaderboard']['best_streak'] ?? 0);
        $elo         = (int)($data['leaderboard']['elo_rating'] ?? 1000);
        $decks       = (int)($data['deckCount'] ?? 0);
        $parallelCnt = (int)($data['parallelCount'] ?? 0);
        $secCnt      = (int)($data['secCount'] ?? 0);
        $user        = $data['user'] ?? $data['profileUser'] ?? [];
        $setCompletion = $data['setCompletion'] ?? [];
        $rarityDist  = $data['rarityDist'] ?? [];

        $completedSets = 0;
        foreach ($setCompletion as $s) {
            if ((int)($s['card_count'] ?? 0) > 0 && (int)($s['owned'] ?? 0) >= (int)($s['card_count'] ?? 0)) {
                $completedSets++;
            }
        }

        $allRarities = ['SEC', 'SP', 'SR', 'R', 'UC', 'C'];
        $hasAllRarities = !array_diff($allRarities, array_keys($rarityDist));

        $now = date('Y-m-d');

        $checks = [
            'first_card'       => $cards >= 1,
            'collector_10'     => $cards >= 10,
            'collector_50'     => $cards >= 50,
            'collector_100'    => $cards >= 100,
            'collector_500'    => $cards >= 500,
            'collector_1000'   => $cards >= 1000,
            'parallel_owner'   => $parallelCnt >= 1,
            'sec_owner'        => $secCnt >= 1,
            'value_100'        => $value >= 100,
            'value_500'        => $value >= 500,
            'value_1000'       => $value >= 1000,
            'value_5000'       => $value >= 5000,
            'value_10000'      => $value >= 10000,
            'set_complete'     => $completedSets >= 1,
            'set_master'       => $completedSets >= 3,
            'all_rarities'     => $hasAllRarities && $cards > 0,
            'first_post'       => $posts >= 1,
            'forum_10'         => $posts >= 10,
            'forum_50'         => $posts >= 50,
            'forum_100'        => $posts >= 100,
            'first_topic'      => $topics >= 1,
            'topic_10'         => $topics >= 10,
            'first_game'       => $games >= 1,
            'gamer_10'         => $games >= 10,
            'gamer_50'         => $games >= 50,
            'gamer_100'        => $games >= 100,
            'first_win'        => $wins >= 1,
            'streak_3'         => $streak >= 3,
            'streak_5'         => $streak >= 5,
            'streak_10'        => $streak >= 10,
            'elo_bronze'       => $elo >= 1100,
            'elo_silver'       => $elo >= 1200,
            'elo_gold'         => $elo >= 1300,
            'elo_diamond'      => $elo >= 1500,
            'first_friend'     => $friends >= 1,
            'social_5'         => $friends >= 5,
            'social_10'        => $friends >= 10,
            'social_25'        => $friends >= 25,
            'profile_complete' => !empty($user['bio']) && (!empty($user['custom_avatar']) || !empty($user['avatar'])) && !empty($user['featured_card_id']),
            'deck_builder'     => $decks >= 1,
        ];

        $result = [];
        foreach ($checks as $id => $earned) {
            $result[$id] = $earned ? $now : null;
        }
        return $result;
    }

    // ============================
    // PIXEL-ART SVG COIN ICONS
    // Each returns a 64x64 SVG with pixel-art imagery on a metallic base
    // ============================

    private static function coin(string $rimColor, string $content): string
    {
        // Lighter/darker variants of rim for metallic effect
        [$r, $g, $b] = self::hexToRgb($rimColor);
        $light = self::lighten($rimColor, 40);
        $dark  = self::darken($rimColor, 40);
        $mid   = self::lighten($rimColor, 20);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64" shape-rendering="crispEdges">
  <defs>
    <radialGradient id="cg" cx="38%" cy="35%" r="60%">
      <stop offset="0%" stop-color="{$light}"/>
      <stop offset="45%" stop-color="{$mid}"/>
      <stop offset="100%" stop-color="{$dark}"/>
    </radialGradient>
    <radialGradient id="shine" cx="35%" cy="30%" r="40%">
      <stop offset="0%" stop-color="rgba(255,255,255,0.55)"/>
      <stop offset="100%" stop-color="rgba(255,255,255,0)"/>
    </radialGradient>
    <clipPath id="cc">
      <circle cx="32" cy="32" r="30"/>
    </clipPath>
  </defs>
  <!-- Outer rim -->
  <circle cx="32" cy="32" r="31" fill="{$dark}"/>
  <!-- Coin face -->
  <circle cx="32" cy="32" r="29" fill="url(#cg)"/>
  <!-- Pixel art content -->
  <g clip-path="url(#cc)">
    {$content}
  </g>
  <!-- Shine overlay -->
  <circle cx="32" cy="32" r="29" fill="url(#shine)"/>
  <!-- Inner rim highlight -->
  <circle cx="32" cy="32" r="29" fill="none" stroke="{$light}" stroke-width="1.5" stroke-opacity="0.6"/>
</svg>
SVG;
    }

    private static function svgCard(string $color): string
    {
        $c = self::darken($color, 50);
        $h = self::lighten($color, 30);
        $content = <<<PIXELS
    <!-- Card shape pixel art -->
    <rect x="20" y="16" width="24" height="32" fill="{$h}"/>
    <rect x="21" y="17" width="22" height="30" fill="{$c}"/>
    <rect x="23" y="19" width="18" height="12" fill="{$h}" fill-opacity="0.6"/>
    <rect x="23" y="33" width="12" height="2" fill="{$h}" fill-opacity="0.5"/>
    <rect x="23" y="36" width="16" height="2" fill="{$h}" fill-opacity="0.5"/>
    <rect x="23" y="39" width="10" height="2" fill="{$h}" fill-opacity="0.5"/>
    <!-- Star on card -->
    <rect x="30" y="20" width="2" height="2" fill="#fff8"/>
    <rect x="28" y="22" width="6" height="2" fill="#fff8"/>
    <rect x="27" y="24" width="8" height="2" fill="#fff8"/>
    <rect x="28" y="26" width="6" height="2" fill="#fff8"/>
    <rect x="30" y="28" width="2" height="2" fill="#fff8"/>
PIXELS;
        return self::coin($color, $content);
    }

    private static function svgCards(string $color, int $num): string
    {
        $c = self::darken($color, 50);
        $h = self::lighten($color, 35);
        $txt = $num >= 1000 ? (string)$num : ($num >= 100 ? (string)$num : ($num >= 10 ? (string)$num : (string)$num));
        $content = <<<PIXELS
    <!-- Stacked cards pixel art -->
    <rect x="16" y="22" width="20" height="26" fill="{$c}" fill-opacity="0.7"/>
    <rect x="19" y="19" width="20" height="26" fill="{$c}" fill-opacity="0.85"/>
    <rect x="22" y="16" width="20" height="26" fill="{$h}"/>
    <rect x="23" y="17" width="18" height="24" fill="{$c}"/>
    <rect x="25" y="19" width="14" height="8" fill="{$h}" fill-opacity="0.5"/>
    <rect x="25" y="29" width="10" height="2" fill="{$h}" fill-opacity="0.4"/>
    <rect x="25" y="32" width="12" height="2" fill="{$h}" fill-opacity="0.4"/>
PIXELS;
        return self::coin($color, $content);
    }

    private static function svgTreasure(string $color): string
    {
        $c = '#8B4513';
        $g = '#FFD700';
        $content = <<<PIXELS
    <!-- Treasure chest pixel art -->
    <rect x="14" y="30" width="36" height="20" fill="{$c}"/>
    <rect x="14" y="30" width="36" height="10" fill="#a0522d"/>
    <rect x="16" y="32" width="32" height="6" fill="#8B4513"/>
    <!-- Chest lid curved pixel -->
    <rect x="14" y="24" width="36" height="8" fill="#a0522d"/>
    <rect x="15" y="22" width="34" height="4" fill="#cd853f"/>
    <!-- Gold coins spilling -->
    <rect x="20" y="46" width="4" height="4" fill="{$g}"/>
    <rect x="26" y="48" width="4" height="4" fill="{$g}"/>
    <rect x="38" y="44" width="4" height="4" fill="{$g}"/>
    <!-- Lock -->
    <rect x="28" y="35" width="8" height="6" fill="{$g}"/>
    <rect x="30" y="31" width="4" height="6" fill="{$g}"/>
    <rect x="29" y="31" width="6" height="2" fill="{$g}"/>
    <!-- Metal strips -->
    <rect x="14" y="30" width="36" height="2" fill="{$g}"/>
    <rect x="14" y="38" width="36" height="2" fill="{$g}"/>
PIXELS;
        return self::coin($color, $content);
    }

    private static function svgSecret(string $color): string
    {
        $content = <<<PIXELS
    <!-- Question mark / secret pixel art -->
    <rect x="28" y="14" width="8" height="2" fill="#fff"/>
    <rect x="26" y="16" width="12" height="2" fill="#fff"/>
    <rect x="24" y="18" width="4" height="2" fill="#fff"/>
    <rect x="36" y="18" width="4" height="2" fill="#fff"/>
    <rect x="36" y="20" width="4" height="4" fill="#fff"/>
    <rect x="32" y="24" width="4" height="4" fill="#fff"/>
    <rect x="30" y="28" width="4" height="4" fill="#fff"/>
    <rect x="30" y="36" width="4" height="4" fill="#fff"/>
    <!-- Stars around -->
    <rect x="16" y="16" width="2" height="2" fill="#fff" fill-opacity="0.6"/>
    <rect x="46" y="20" width="2" height="2" fill="#fff" fill-opacity="0.6"/>
    <rect x="18" y="42" width="2" height="2" fill="#fff" fill-opacity="0.6"/>
    <rect x="44" y="44" width="2" height="2" fill="#fff" fill-opacity="0.6"/>
PIXELS;
        return self::coin($color, $content);
    }

    private static function svgParallel(string $color): string
    {
        $c = self::darken($color, 40);
        $content = <<<PIXELS
    <!-- Holographic parallel lines -->
    <rect x="16" y="16" width="32" height="32" fill="{$c}" fill-opacity="0.4"/>
    <!-- Diagonal rainbow stripes pixel art -->
    <rect x="16" y="16" width="4" height="32" fill="#ff6b6b" fill-opacity="0.7"/>
    <rect x="20" y="16" width="4" height="32" fill="#ffd93d" fill-opacity="0.7"/>
    <rect x="24" y="16" width="4" height="32" fill="#6bcb77" fill-opacity="0.7"/>
    <rect x="28" y="16" width="4" height="32" fill="#4d96ff" fill-opacity="0.7"/>
    <rect x="32" y="16" width="4" height="32" fill="#c77dff" fill-opacity="0.7"/>
    <rect x="36" y="16" width="4" height="32" fill="#ff6b6b" fill-opacity="0.7"/>
    <rect x="40" y="16" width="4" height="32" fill="#ffd93d" fill-opacity="0.7"/>
    <rect x="44" y="16" width="4" height="32" fill="#6bcb77" fill-opacity="0.7"/>
    <!-- Card outline overlay -->
    <rect x="18" y="14" width="28" height="36" fill="none" stroke="#fff" stroke-width="2"/>
    <rect x="20" y="20" width="24" height="12" fill="none" stroke="#fff" stroke-width="1" stroke-opacity="0.5"/>
PIXELS;
        return self::coin($color, $content);
    }

    private static function svgVault(string $color): string
    {
        $dark = self::darken($color, 30);
        $content = <<<PIXELS
    <!-- Vault door pixel art -->
    <rect x="16" y="14" width="32" height="36" fill="{$dark}"/>
    <rect x="18" y="16" width="28" height="32" fill="#2c2c2c"/>
    <!-- Vault wheel -->
    <rect x="26" y="24" width="12" height="2" fill="{$color}"/>
    <rect x="30" y="20" width="4" height="10" fill="{$color}"/>
    <circle cx="32" cy="29" r="6" fill="none" stroke="{$color}" stroke-width="2"/>
    <!-- Bolts -->
    <rect x="20" y="18" width="4" height="4" fill="{$color}"/>
    <rect x="40" y="18" width="4" height="4" fill="{$color}"/>
    <rect x="20" y="42" width="4" height="4" fill="{$color}"/>
    <rect x="40" y="42" width="4" height="4" fill="{$color}"/>
    <!-- Door edge -->
    <rect x="16" y="14" width="2" height="36" fill="{$color}" fill-opacity="0.4"/>
    <!-- Dollar sign -->
    <rect x="30" y="36" width="4" height="2" fill="{$color}"/>
    <rect x="28" y="38" width="8" height="2" fill="{$color}"/>
    <rect x="30" y="40" width="4" height="2" fill="{$color}"/>
PIXELS;
        return self::coin($color, $content);
    }

    private static function svgStar(string $color): string
    {
        $dark = self::darken($color, 30);
        $content = <<<PIXELS
    <!-- Big 5-point star pixel art -->
    <rect x="30" y="12" width="4" height="4" fill="{$color}"/>
    <rect x="28" y="16" width="8" height="4" fill="{$color}"/>
    <rect x="14" y="22" width="36" height="4" fill="{$color}"/>
    <rect x="16" y="26" width="32" height="4" fill="{$color}"/>
    <rect x="18" y="30" width="12" height="4" fill="{$color}"/>
    <rect x="34" y="30" width="12" height="4" fill="{$color}"/>
    <rect x="14" y="34" width="10" height="4" fill="{$color}"/>
    <rect x="40" y="34" width="10" height="4" fill="{$color}"/>
    <rect x="12" y="38" width="8" height="4" fill="{$color}"/>
    <rect x="44" y="38" width="8" height="4" fill="{$color}"/>
    <rect x="24" y="34" width="16" height="4" fill="{$color}"/>
    <rect x="26" y="38" width="12" height="8" fill="{$color}"/>
PIXELS;
        return self::coin($color, $content);
    }

    private static function svgStars(string $color): string
    {
        $dark = self::darken($color, 30);
        $content = <<<PIXELS
    <!-- Three stars pixel art -->
    <!-- Small star left -->
    <rect x="12" y="28" width="2" height="2" fill="{$color}"/>
    <rect x="10" y="30" width="6" height="2" fill="{$color}"/>
    <rect x="10" y="32" width="6" height="2" fill="{$color}"/>
    <rect x="8" y="34" width="10" height="2" fill="{$color}"/>
    <rect x="10" y="36" width="6" height="4" fill="{$color}"/>
    <!-- Big star center -->
    <rect x="30" y="14" width="4" height="4" fill="{$color}"/>
    <rect x="26" y="18" width="12" height="4" fill="{$color}"/>
    <rect x="18" y="24" width="28" height="4" fill="{$color}"/>
    <rect x="20" y="28" width="24" height="4" fill="{$color}"/>
    <rect x="22" y="32" width="20" height="4" fill="{$color}"/>
    <rect x="24" y="36" width="16" height="8" fill="{$color}"/>
    <!-- Small star right -->
    <rect x="50" y="28" width="2" height="2" fill="{$color}"/>
    <rect x="48" y="30" width="6" height="2" fill="{$color}"/>
    <rect x="48" y="32" width="6" height="2" fill="{$color}"/>
    <rect x="46" y="34" width="10" height="2" fill="{$color}"/>
    <rect x="48" y="36" width="6" height="4" fill="{$color}"/>
PIXELS;
        return self::coin($color, $content);
    }

    private static function svgRainbow(string $color): string
    {
        $content = <<<PIXELS
    <!-- Rainbow arc pixel art -->
    <rect x="14" y="38" width="36" height="4" fill="#ff0000"/>
    <rect x="12" y="34" width="40" height="4" fill="#ff7f00"/>
    <rect x="10" y="30" width="44" height="4" fill="#ffff00"/>
    <rect x="10" y="26" width="20" height="4" fill="#00ff00"/>
    <rect x="34" y="26" width="20" height="4" fill="#00ff00"/>
    <rect x="12" y="22" width="16" height="4" fill="#0000ff"/>
    <rect x="36" y="22" width="16" height="4" fill="#0000ff"/>
    <rect x="16" y="18" width="12" height="4" fill="#8b00ff"/>
    <rect x="36" y="18" width="12" height="4" fill="#8b00ff"/>
    <rect x="22" y="14" width="20" height="6" fill="none"/>
    <!-- Cloud base -->
    <rect x="14" y="42" width="36" height="8" fill="#ffffff" fill-opacity="0.5"/>
PIXELS;
        return self::coin($color, $content);
    }

    private static function svgBubble(string $color): string
    {
        $dark = self::darken($color, 30);
        $content = <<<PIXELS
    <!-- Speech bubble pixel art -->
    <rect x="14" y="14" width="36" height="28" fill="{$color}"/>
    <rect x="12" y="16" width="40" height="24" fill="{$color}"/>
    <rect x="10" y="18" width="44" height="20" fill="{$color}"/>
    <rect x="12" y="42" width="8" height="4" fill="{$color}"/>
    <rect x="14" y="46" width="6" height="4" fill="{$color}"/>
    <!-- Text lines -->
    <rect x="18" y="22" width="28" height="3" fill="{$dark}"/>
    <rect x="18" y="28" width="20" height="3" fill="{$dark}"/>
    <rect x="18" y="34" width="24" height="3" fill="{$dark}"/>
PIXELS;
        return self::coin($color, $content);
    }

    private static function svgTopic(string $color): string
    {
        $dark = self::darken($color, 35);
        $content = <<<PIXELS
    <!-- Newspaper / topic pixel art -->
    <rect x="14" y="14" width="36" height="36" fill="{$color}"/>
    <rect x="15" y="15" width="34" height="34" fill="{$dark}"/>
    <!-- Header area -->
    <rect x="16" y="16" width="32" height="8" fill="{$color}"/>
    <!-- Text columns -->
    <rect x="16" y="26" width="14" height="3" fill="{$color}" fill-opacity="0.6"/>
    <rect x="32" y="26" width="14" height="3" fill="{$color}" fill-opacity="0.6"/>
    <rect x="16" y="31" width="14" height="3" fill="{$color}" fill-opacity="0.6"/>
    <rect x="32" y="31" width="14" height="3" fill="{$color}" fill-opacity="0.6"/>
    <rect x="16" y="36" width="14" height="3" fill="{$color}" fill-opacity="0.6"/>
    <rect x="32" y="36" width="14" height="3" fill="{$color}" fill-opacity="0.6"/>
    <rect x="16" y="41" width="30" height="3" fill="{$color}" fill-opacity="0.6"/>
    <!-- Divider -->
    <rect x="30" y="26" width="2" height="20" fill="{$color}" fill-opacity="0.4"/>
PIXELS;
        return self::coin($color, $content);
    }

    private static function svgSword(string $color): string
    {
        $dark = self::darken($color, 40);
        $content = <<<PIXELS
    <!-- Sword pixel art -->
    <rect x="30" y="10" width="4" height="4" fill="{$color}"/>
    <rect x="30" y="14" width="4" height="4" fill="{$color}"/>
    <rect x="30" y="18" width="4" height="4" fill="{$color}"/>
    <rect x="30" y="22" width="4" height="4" fill="{$color}"/>
    <rect x="30" y="26" width="4" height="4" fill="{$color}"/>
    <rect x="30" y="30" width="4" height="4" fill="{$color}"/>
    <!-- Crossguard -->
    <rect x="20" y="34" width="24" height="4" fill="{$dark}"/>
    <rect x="22" y="32" width="20" height="8" fill="{$color}"/>
    <!-- Handle -->
    <rect x="28" y="40" width="8" height="12" fill="{$dark}"/>
    <rect x="28" y="50" width="8" height="4" fill="{$color}"/>
    <!-- Blade shine -->
    <rect x="32" y="10" width="2" height="24" fill="#fff" fill-opacity="0.4"/>
PIXELS;
        return self::coin($color, $content);
    }

    private static function svgCrown(string $color): string
    {
        $dark = self::darken($color, 30);
        $content = <<<PIXELS
    <!-- Crown pixel art -->
    <rect x="12" y="36" width="40" height="14" fill="{$color}"/>
    <rect x="10" y="34" width="44" height="4" fill="{$color}"/>
    <!-- Crown points -->
    <rect x="12" y="24" width="4" height="12" fill="{$color}"/>
    <rect x="22" y="18" width="4" height="18" fill="{$color}"/>
    <rect x="30" y="14" width="4" height="22" fill="{$color}"/>
    <rect x="38" y="18" width="4" height="18" fill="{$color}"/>
    <rect x="48" y="24" width="4" height="12" fill="{$color}"/>
    <!-- Jewels -->
    <rect x="26" y="38" width="4" height="4" fill="#ff0000"/>
    <rect x="34" y="38" width="4" height="4" fill="#0000ff"/>
    <rect x="18" y="40" width="4" height="4" fill="#00aa00"/>
    <rect x="42" y="40" width="4" height="4" fill="#aa00aa"/>
    <!-- Crown shine -->
    <rect x="10" y="34" width="44" height="2" fill="#fff" fill-opacity="0.3"/>
PIXELS;
        return self::coin($color, $content);
    }

    private static function svgFlame(string $color): string
    {
        $content = <<<PIXELS
    <!-- Flame pixel art -->
    <!-- Outer flame (large) -->
    <rect x="28" y="10" width="8" height="4" fill="#ff4500"/>
    <rect x="26" y="14" width="12" height="4" fill="#ff6600"/>
    <rect x="24" y="18" width="16" height="4" fill="#ff8c00"/>
    <rect x="22" y="22" width="20" height="4" fill="#ffa500"/>
    <!-- Left flame -->
    <rect x="18" y="20" width="8" height="4" fill="#ff4500"/>
    <rect x="16" y="24" width="10" height="4" fill="#ff6600"/>
    <!-- Right flame -->
    <rect x="38" y="18" width="8" height="4" fill="#ff4500"/>
    <rect x="38" y="22" width="10" height="4" fill="#ff6600"/>
    <!-- Main body -->
    <rect x="20" y="26" width="24" height="4" fill="#ffb300"/>
    <rect x="18" y="30" width="28" height="4" fill="#ffc200"/>
    <rect x="16" y="34" width="32" height="4" fill="#ffd700"/>
    <rect x="14" y="38" width="36" height="4" fill="#ffe066"/>
    <!-- Inner glow -->
    <rect x="26" y="22" width="12" height="12" fill="#fff700" fill-opacity="0.5"/>
    <rect x="28" y="26" width="8" height="8" fill="#ffffff" fill-opacity="0.3"/>
    <!-- Base -->
    <rect x="14" y="42" width="36" height="4" fill="{$color}" fill-opacity="0.5"/>
PIXELS;
        return self::coin($color, $content);
    }

    private static function svgShield(string $color): string
    {
        $dark = self::darken($color, 40);
        $content = <<<PIXELS
    <!-- Shield pixel art -->
    <rect x="16" y="14" width="32" height="2" fill="{$color}"/>
    <rect x="14" y="16" width="36" height="20" fill="{$color}"/>
    <rect x="16" y="36" width="32" height="4" fill="{$color}"/>
    <rect x="18" y="40" width="28" height="4" fill="{$color}"/>
    <rect x="20" y="44" width="24" height="4" fill="{$color}"/>
    <rect x="24" y="48" width="16" height="4" fill="{$color}"/>
    <rect x="28" y="52" width="8" height="2" fill="{$color}"/>
    <!-- Shield inner design -->
    <rect x="18" y="18" width="28" height="14" fill="{$dark}"/>
    <!-- Diagonal cross -->
    <rect x="18" y="18" width="28" height="3" fill="{$color}" fill-opacity="0.5"/>
    <rect x="30" y="18" width="3" height="14" fill="{$color}" fill-opacity="0.5"/>
    <!-- Star emblem -->
    <rect x="30" y="22" width="4" height="2" fill="#fff" fill-opacity="0.7"/>
    <rect x="28" y="24" width="8" height="2" fill="#fff" fill-opacity="0.7"/>
    <rect x="30" y="26" width="4" height="2" fill="#fff" fill-opacity="0.7"/>
    <!-- Shine -->
    <rect x="16" y="14" width="36" height="2" fill="#fff" fill-opacity="0.3"/>
PIXELS;
        return self::coin($color, $content);
    }

    private static function svgFriends(string $color): string
    {
        $dark = self::darken($color, 30);
        $content = <<<PIXELS
    <!-- Two people pixel art -->
    <!-- Left person -->
    <rect x="14" y="16" width="8" height="8" fill="{$color}"/>
    <rect x="12" y="24" width="12" height="14" fill="{$color}"/>
    <rect x="10" y="38" width="6" height="10" fill="{$dark}"/>
    <rect x="18" y="38" width="6" height="10" fill="{$dark}"/>
    <!-- Right person -->
    <rect x="42" y="16" width="8" height="8" fill="{$color}"/>
    <rect x="40" y="24" width="12" height="14" fill="{$color}"/>
    <rect x="38" y="38" width="6" height="10" fill="{$dark}"/>
    <rect x="46" y="38" width="6" height="10" fill="{$dark}"/>
    <!-- Heart between them -->
    <rect x="28" y="22" width="4" height="4" fill="#ff4444"/>
    <rect x="24" y="24" width="4" height="4" fill="#ff4444"/>
    <rect x="36" y="24" width="4" height="4" fill="#ff4444"/>
    <rect x="24" y="26" width="16" height="4" fill="#ff4444"/>
    <rect x="26" y="30" width="12" height="4" fill="#ff4444"/>
    <rect x="28" y="34" width="8" height="4" fill="#ff4444"/>
    <rect x="30" y="38" width="4" height="4" fill="#ff4444"/>
PIXELS;
        return self::coin($color, $content);
    }

    private static function svgProfile(string $color): string
    {
        $dark = self::darken($color, 35);
        $content = <<<PIXELS
    <!-- User profile pixel art -->
    <!-- Head -->
    <rect x="24" y="10" width="16" height="16" fill="{$color}"/>
    <rect x="22" y="12" width="20" height="12" fill="{$color}"/>
    <!-- Neck -->
    <rect x="28" y="26" width="8" height="4" fill="{$color}"/>
    <!-- Body / shirt -->
    <rect x="18" y="30" width="28" height="20" fill="{$dark}"/>
    <rect x="16" y="32" width="32" height="16" fill="{$dark}"/>
    <!-- Checkmark for complete -->
    <rect x="24" y="44" width="4" height="4" fill="#00ff88"/>
    <rect x="28" y="46" width="4" height="4" fill="#00ff88"/>
    <rect x="32" y="40" width="4" height="8" fill="#00ff88"/>
    <!-- Eyes -->
    <rect x="26" y="16" width="4" height="4" fill="{$dark}"/>
    <rect x="34" y="16" width="4" height="4" fill="{$dark}"/>
    <!-- Smile -->
    <rect x="26" y="22" width="4" height="2" fill="{$dark}"/>
    <rect x="34" y="22" width="4" height="2" fill="{$dark}"/>
    <rect x="30" y="24" width="4" height="2" fill="{$dark}"/>
PIXELS;
        return self::coin($color, $content);
    }

    private static function svgDeck(string $color): string
    {
        $dark = self::darken($color, 40);
        $content = <<<PIXELS
    <!-- Deck of cards pixel art -->
    <!-- Bottom card -->
    <rect x="12" y="26" width="22" height="30" fill="{$dark}"/>
    <!-- Middle card -->
    <rect x="18" y="20" width="22" height="30" fill="{$color}" fill-opacity="0.8"/>
    <!-- Top card -->
    <rect x="24" y="14" width="22" height="30" fill="{$color}"/>
    <rect x="25" y="15" width="20" height="28" fill="{$dark}"/>
    <!-- Card face details -->
    <rect x="27" y="17" width="16" height="10" fill="{$color}" fill-opacity="0.5"/>
    <rect x="27" y="29" width="10" height="3" fill="{$color}" fill-opacity="0.4"/>
    <rect x="27" y="33" width="14" height="3" fill="{$color}" fill-opacity="0.4"/>
    <rect x="27" y="37" width="8" height="3" fill="{$color}" fill-opacity="0.4"/>
    <!-- Plus sign for "add" -->
    <rect x="30" y="42" width="8" height="3" fill="{$color}"/>
    <rect x="33" y="39" width="3" height="9" fill="{$color}"/>
PIXELS;
        return self::coin($color, $content);
    }

    // ==================
    // Color helpers
    // ==================

    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    private static function lighten(string $hex, int $amount): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        $r = min(255, $r + $amount);
        $g = min(255, $g + $amount);
        $b = min(255, $b + $amount);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    private static function darken(string $hex, int $amount): string
    {
        [$r, $g, $b] = self::hexToRgb($hex);
        $r = max(0, $r - $amount);
        $g = max(0, $g - $amount);
        $b = max(0, $b - $amount);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
