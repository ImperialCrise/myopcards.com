<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\Card;
use PDO;

class HomeController
{
    public function index(): void
    {
        if (Auth::check()) {
            header('Location: /dashboard');
            exit;
        }

        $totalCards = Card::getTotalCount();
        $db = Database::getConnection();

        $showcaseCards = $db->query(
            "SELECT card_image_url FROM cards
             WHERE rarity IN ('SEC','SP','SR','L')
               AND card_image_url IS NOT NULL AND card_image_url != ''
             ORDER BY RAND() LIMIT 48"
        )->fetchAll(PDO::FETCH_COLUMN);

        $userCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();

        View::render('pages/home', [
            'title' => 'MyOPCards - One Piece TCG Collection Tracker & Price Guide',
            'fullWidth' => true,
            'totalCards' => $totalCards,
            'showcaseCards' => $showcaseCards,
            'userCount' => (int)$userCount,
            'seoDescription' => 'The ultimate One Piece TCG collection tracker. Browse ' . number_format($totalCards) . ' cards, track market prices from TCGPlayer & Cardmarket, manage your collection, and share it with friends. Join ' . number_format((int)$userCount) . ' collectors today.',
            'seoKeywords' => 'One Piece TCG, OPTCG, card collection tracker, One Piece trading card game, card prices, Cardmarket, TCGPlayer, collection manager, One Piece cards database',
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
