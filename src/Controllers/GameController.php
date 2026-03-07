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
            'title' => 'Play Online',
            'user' => $user,
            'decks' => $decks,
            'elo' => $lbRow,
            'myRank' => $myRank,
        ]);
    }

    public function board(int $id): void
    {
        Auth::requireAuth();
        View::render('pages/game/board', ['title' => 'Game', 'gameId' => $id, 'userId' => Auth::id(), 'fullWidth' => true]);
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
        View::render('pages/game/leaderboard', [
            'title' => 'Leaderboard',
            'top' => $top,
            'me' => $me,
            'myRank' => $myRank,
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
             u1.username AS player1_name, u2.username AS player2_name
             FROM games g
             LEFT JOIN users u1 ON u1.id = g.player1_id
             LEFT JOIN users u2 ON u2.id = g.player2_id
             WHERE g.player1_id = :uid OR g.player2_id = :uid
             ORDER BY g.finished_at DESC, g.started_at DESC
             LIMIT 50'
        );
        $stmt->execute(['uid' => Auth::id()]);
        $games = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        echo json_encode(['games' => $games]);
    }
}
