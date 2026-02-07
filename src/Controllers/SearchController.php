<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use PDO;

class SearchController
{
    public function search(): void
    {
        header('Content-Type: application/json');

        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) {
            echo json_encode(['cards' => [], 'users' => [], 'sets' => []]);
            return;
        }

        $lang = 'en';
        if (Auth::check()) {
            $user = Auth::user();
            $lang = $user['preferred_lang'] ?? 'en';
        } elseif (isset($_COOKIE['lang'])) {
            $lang = $_COOKIE['lang'];
        }

        $db = Database::getConnection();
        $like = '%' . $q . '%';

        $cardStmt = $db->prepare(
            "SELECT c.id, c.card_set_id, c.card_name, c.card_image_url, c.rarity, c.set_id, c.market_price,
                    COALESCE(ct.card_name, c.card_name) as display_name
             FROM cards c
             LEFT JOIN card_translations ct ON ct.card_id = c.id AND ct.lang = :lang
             WHERE c.card_name LIKE :q1 OR c.card_set_id LIKE :q2 OR ct.card_name LIKE :q3
             ORDER BY c.market_price DESC
             LIMIT 5"
        );
        $cardStmt->execute(['lang' => $lang, 'q1' => $like, 'q2' => $like, 'q3' => $like]);
        $cards = $cardStmt->fetchAll();

        $userStmt = $db->prepare(
            "SELECT id, username, avatar FROM users WHERE username LIKE :q LIMIT 5"
        );
        $userStmt->execute(['q' => $like]);
        $users = $userStmt->fetchAll();

        $setStmt = $db->prepare(
            "SELECT set_id, set_name, card_count FROM sets WHERE set_name LIKE :q1 OR set_id LIKE :q2 LIMIT 5"
        );
        $setStmt->execute(['q1' => $like, 'q2' => $like]);
        $sets = $setStmt->fetchAll();

        echo json_encode([
            'cards' => $cards,
            'users' => $users,
            'sets' => $sets,
        ]);
    }
}
