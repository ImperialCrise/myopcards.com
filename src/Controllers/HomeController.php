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
            'title' => 'MyOPCards - One Piece TCG Collection Tracker',
            'fullWidth' => true,
            'totalCards' => $totalCards,
            'showcaseCards' => $showcaseCards,
            'userCount' => (int)$userCount,
        ]);
    }
}
