<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\Card;
use App\Models\Leaderboard;
use PDO;

class HomeController
{
    public function index(): void
    {
        $totalCards = Card::getTotalCount();
        $db = Database::getConnection();

        $showcaseCards = $db->query(
            "SELECT card_image_url FROM cards
             WHERE rarity IN ('SEC','SP','SR','L')
               AND card_image_url IS NOT NULL AND card_image_url != ''
             ORDER BY RAND() LIMIT 48"
        )->fetchAll(PDO::FETCH_COLUMN);

        $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $totalMatches = (int) $db->query("SELECT COUNT(*) FROM games WHERE status = 'finished'")->fetchColumn();
        $totalFromLeaderboard = (int) $db->query("SELECT COALESCE(SUM(games_played), 0) FROM leaderboard")->fetchColumn();
        if ($totalFromLeaderboard > $totalMatches) {
            $totalMatches = $totalFromLeaderboard;
        }
        $activeGames = (int) $db->query("SELECT COUNT(*) FROM games WHERE status = 'active'")->fetchColumn();
        $leaderboardTop = Leaderboard::getTop(5);

        View::render('pages/home', [
            'title' => 'MyOPCards - One Piece TCG Collection Tracker & Price Guide',
            'fullWidth' => true,
            'totalCards' => $totalCards,
            'showcaseCards' => $showcaseCards,
            'userCount' => (int)$userCount,
            'totalMatches' => $totalMatches,
            'activeGames' => $activeGames,
            'leaderboardTop' => $leaderboardTop,
            'seoDescription' => 'The ultimate One Piece TCG platform. Browse ' . number_format($totalCards) . ' cards, track market prices, manage your collection, and play the One Piece card game online with ranked matchmaking. Join ' . number_format((int)$userCount) . ' collectors today.',
            'seoKeywords' => 'One Piece TCG, OPTCG, card collection tracker, One Piece trading card game, card prices, Cardmarket, TCGPlayer, collection manager, One Piece cards database, play OPTCG online, One Piece TCG simulator',
            'seoCanonical' => 'https://myopcards.com/',
            'seoJsonLd' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebSite',
                'name' => 'MyOPCards',
                'url' => 'https://myopcards.com',
                'description' => 'One Piece TCG Collection Tracker & Price Guide',
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => 'https://myopcards.com/cards?q={search_term_string}',
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ]);
    }
}
