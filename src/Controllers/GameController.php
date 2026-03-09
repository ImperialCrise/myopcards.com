<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\Deck;
use App\Models\Leaderboard;

class GameController
{
    public function lobby(): void
    {
        Auth::requireAuth();
        $user = Auth::user();
        $decks = Deck::getByUserId(Auth::id());
        Leaderboard::ensureUser(Auth::id());
        $lbRow = Leaderboard::getByUserId(Auth::id());
        $myRank = $lbRow ? Leaderboard::getRankForUser(Auth::id()) : null;
        View::render('pages/game/lobby', [
            'title' => 'Play One Piece TCG Online - MyOPCards',
            'seoDescription' => 'Play the One Piece Trading Card Game online for free. Ranked matchmaking, casual matches, bot opponents with multiple difficulty levels. Build your deck and climb the leaderboard.',
            'seoKeywords' => 'One Piece TCG online, play OPTCG, One Piece card game online, OPTCG simulator, One Piece TCG matchmaking, play One Piece cards free, OPTCG ranked',
            'seoCanonical' => 'https://myopcards.com/play',
            'seoJsonLd' => [
                '@context' => 'https://schema.org',
                '@type' => 'VideoGame',
                'name' => 'MyOPCards - One Piece TCG Online',
                'description' => 'Play the One Piece Trading Card Game online with ranked matchmaking, bot opponents, and custom rooms.',
                'url' => 'https://myopcards.com/play',
                'genre' => ['Card Game', 'Strategy'],
                'gamePlatform' => 'Web Browser',
                'applicationCategory' => 'Game',
                'operatingSystem' => 'Any',
                'offers' => [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'USD',
                    'availability' => 'https://schema.org/InStock',
                ],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => 'MyOPCards',
                    'url' => 'https://myopcards.com',
                ],
            ],
            'user' => $user,
            'decks' => $decks,
            'elo' => $lbRow,
            'myRank' => $myRank,
        ]);
    }

    public function board(int $id): void
    {
        Auth::requireAuth();
        View::render('pages/game/board', [
            'title' => 'Game #' . $id . ' - MyOPCards',
            'seoRobots' => 'noindex, nofollow',
            'gameId' => $id,
            'userId' => Auth::id(),
            'fullWidth' => true,
        ]);
    }

    public function leaderboard(): void
    {
        $top = Leaderboard::getTop(100);
        $me = null;
        $myRank = null;
        if (Auth::check()) {
            Leaderboard::ensureUser(Auth::id());
            $me = Leaderboard::getByUserId(Auth::id());
            $myRank = $me ? Leaderboard::getRankForUser(Auth::id()) : null;
        }

        $jsonLdItems = [];
        foreach (array_slice($top, 0, 50) as $i => $player) {
            $jsonLdItems[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'item' => [
                    '@type' => 'Person',
                    'name' => $player['username'] ?? ('Player #' . ($i + 1)),
                    'url' => 'https://myopcards.com/user/' . rawurlencode($player['username'] ?? ''),
                ],
            ];
        }

        View::render('pages/game/leaderboard', [
            'title' => 'Leaderboard - One Piece TCG Rankings - MyOPCards',
            'seoDescription' => 'View the top One Piece TCG players ranked by ELO rating. Compete in ranked matches to climb the leaderboard and prove your skills.',
            'seoKeywords' => 'One Piece TCG leaderboard, OPTCG rankings, One Piece card game ELO, top OPTCG players, One Piece TCG competitive, OPTCG ranking ladder',
            'seoCanonical' => 'https://myopcards.com/leaderboard',
            'seoJsonLd' => [
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'name' => 'One Piece TCG Leaderboard',
                'description' => 'Top ranked One Piece Trading Card Game players by ELO rating on MyOPCards.',
                'numberOfItems' => count($top),
                'itemListElement' => $jsonLdItems,
            ],
            'top' => $top,
            'me' => $me,
            'myRank' => $myRank,
            'currentUserId' => Auth::check() ? Auth::id() : null,
        ]);
    }

    public function leaderboardApi(): void
    {
        header('Content-Type: application/json');
        $top = Leaderboard::getTop(100);
        echo json_encode(['leaderboard' => $top, 'total' => count($top)]);
    }

    public function history(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT g.id, g.game_type, g.status, g.winner_id, g.started_at, g.finished_at,
             g.duration_seconds, g.player1_time_remaining, g.player2_time_remaining,
             u1.username AS player1_name, u2.username AS player2_name
             FROM games g
             LEFT JOIN users u1 ON u1.id = g.player1_id
             LEFT JOIN users u2 ON u2.id = g.player2_id
             WHERE g.player1_id = :uid1 OR g.player2_id = :uid2
             ORDER BY g.finished_at DESC, g.started_at DESC
             LIMIT 50'
        );
        $uid = Auth::id();
        $stmt->execute(['uid1' => $uid, 'uid2' => $uid]);
        $games = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        echo json_encode(['games' => $games]);
    }

    public function historyPage(): void
    {
        Auth::requireAuth();
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT g.id, g.player1_id, g.player2_id, g.game_type, g.status, g.winner_id, g.started_at, g.finished_at,
             g.turn_count, g.duration_seconds, g.player1_time_remaining, g.player2_time_remaining,
             u1.username AS player1_name, u2.username AS player2_name
             FROM games g
             LEFT JOIN users u1 ON u1.id = g.player1_id
             LEFT JOIN users u2 ON u2.id = g.player2_id
             WHERE (g.player1_id = :uid1 OR g.player2_id = :uid2) AND g.status = \'finished\'
             ORDER BY g.finished_at DESC, g.started_at DESC
             LIMIT 50'
        );
        $uid = Auth::id();
        $stmt->execute(['uid1' => $uid, 'uid2' => $uid]);
        $games = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        View::render('pages/game/history', [
            'title' => t('game.history') . ' - MyOPCards',
            'games' => $games,
        ]);
    }

    public function replayPage(int $id): void
    {
        Auth::requireAuth();
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT g.id, g.player1_id, g.player2_id, u1.username AS player1_name, u2.username AS player2_name
             FROM games g
             LEFT JOIN users u1 ON u1.id = g.player1_id
             LEFT JOIN users u2 ON u2.id = g.player2_id
             WHERE g.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $game = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$game || ((int)$game['player1_id'] !== Auth::id() && (int)$game['player2_id'] !== Auth::id())) {
            http_response_code(403);
            View::render('pages/error', ['title' => 'Forbidden', 'message' => 'You do not have access to this game.']);
            return;
        }
        $from = $_GET['from'] ?? '';
        $backUrl = $from === 'leaderboard' ? '/leaderboard' : '/history';

        View::render('pages/game/replay', [
            'title' => t('game.replay') . ' #' . $id . ' - MyOPCards',
            'gameId' => $id,
            'game' => $game,
            'backUrl' => $backUrl,
        ]);
    }

    public function gameMoves(int $id): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT player1_id, player2_id FROM games WHERE id = ?');
        $stmt->execute([$id]);
        $game = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$game) {
            http_response_code(404);
            echo json_encode(['error' => 'Game not found']);
            return;
        }
        $uid = Auth::id();
        if ((int)$game['player1_id'] !== $uid && (int)$game['player2_id'] !== $uid) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }
        $stmt = $db->prepare(
            'SELECT id, game_id, player_id, move_type, move_data, created_at
             FROM game_moves WHERE game_id = ? ORDER BY created_at ASC'
        );
        $stmt->execute([$id]);
        $moves = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($moves as &$m) {
            if (!empty($m['move_data'])) {
                $m['move_data'] = json_decode($m['move_data'], true);
            }
        }
        echo json_encode(['moves' => $moves]);
    }

    public function globalHistory(): void
    {
        header('Content-Type: application/json');
        $db = Database::getConnection();
        $stmt = $db->query(
            'SELECT g.id, g.player1_id, g.player2_id, g.game_type, g.winner_id, g.turn_count, g.duration_seconds, g.finished_at,
             u1.username AS player1_name, u2.username AS player2_name
             FROM games g
             LEFT JOIN users u1 ON u1.id = g.player1_id
             LEFT JOIN users u2 ON u2.id = g.player2_id
             WHERE g.status = \'finished\'
             ORDER BY g.finished_at DESC
             LIMIT 100'
        );
        $games = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($games as &$g) {
            if (($g['game_type'] ?? '') === 'bot' || empty($g['player2_name'])) {
                $g['player2_name'] = $g['player2_name'] ?: 'Bot';
            }
        }
        echo json_encode(['games' => $games]);
    }
}
