<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Models\User;
use App\Models\CardSet;
use App\Models\Collection;
use PDO;

class AnalyticsController
{
    public function index(): void
    {
        Auth::requireAuth();

        $stats = User::getCollectionStats(Auth::id());
        $setCompletion = CardSet::getCompletionForUser(Auth::id());
        $topCards = $this->getTopValueCards(Auth::id(), 10);
        $distributions = $this->getDistributions(Auth::id());

        View::render('pages/analytics', [
            'title' => 'Collection Analytics',
            'stats' => $stats,
            'setCompletion' => $setCompletion,
            'topCards' => $topCards,
            'distributions' => $distributions,
        ]);
    }

    public function valueHistory(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $days = max(7, min(365, (int)($_GET['days'] ?? 90)));
        $db = Database::getConnection();

        $stmt = $db->prepare(
            "SELECT snapshot_date, total_value_usd, total_value_eur, unique_cards, total_cards
             FROM collection_snapshots
             WHERE user_id = :uid AND snapshot_date >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
             ORDER BY snapshot_date ASC"
        );
        $stmt->bindValue('uid', Auth::id(), PDO::PARAM_INT);
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode($stmt->fetchAll());
    }

    public function distribution(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        echo json_encode($this->getDistributions(Auth::id()));
    }

    private function getTopValueCards(int $userId, int $limit): array
    {
        $db = Database::getConnection();
        $priceCol = \App\Core\Currency::column();
        $stmt = $db->prepare(
            "SELECT c.id, c.card_set_id, c.card_name, c.card_image_url, c.rarity, c.set_name,
                    c.market_price, c.cardmarket_price, c.price_en, c.price_fr, c.price_jp, uc.quantity,
                    (c.$priceCol * uc.quantity) as total_value
             FROM user_cards uc
             JOIN cards c ON c.id = uc.card_id
             WHERE uc.user_id = :uid AND uc.is_wishlist = 0 AND c.$priceCol > 0
             ORDER BY total_value DESC
             LIMIT :lim"
        );
        $stmt->bindValue('uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function getDistributions(int $userId): array
    {
        $db = Database::getConnection();

        $colorStmt = $db->prepare(
            "SELECT c.card_color as label, COUNT(*) as value
             FROM user_cards uc JOIN cards c ON c.id = uc.card_id
             WHERE uc.user_id = :uid AND uc.is_wishlist = 0 AND c.card_color != ''
             GROUP BY c.card_color ORDER BY value DESC"
        );
        $colorStmt->execute(['uid' => $userId]);

        $rarityStmt = $db->prepare(
            "SELECT c.rarity as label, COUNT(*) as value
             FROM user_cards uc JOIN cards c ON c.id = uc.card_id
             WHERE uc.user_id = :uid AND uc.is_wishlist = 0 AND c.rarity != ''
             GROUP BY c.rarity ORDER BY value DESC"
        );
        $rarityStmt->execute(['uid' => $userId]);

        $typeStmt = $db->prepare(
            "SELECT c.card_type as label, COUNT(*) as value
             FROM user_cards uc JOIN cards c ON c.id = uc.card_id
             WHERE uc.user_id = :uid AND uc.is_wishlist = 0 AND c.card_type != ''
             GROUP BY c.card_type ORDER BY value DESC"
        );
        $typeStmt->execute(['uid' => $userId]);

        $setStmt = $db->prepare(
            "SELECT c.set_id as label, COUNT(*) as value
             FROM user_cards uc JOIN cards c ON c.id = uc.card_id
             WHERE uc.user_id = :uid AND uc.is_wishlist = 0
             GROUP BY c.set_id ORDER BY c.set_id ASC"
        );
        $setStmt->execute(['uid' => $userId]);

        return [
            'colors' => $colorStmt->fetchAll(),
            'rarities' => $rarityStmt->fetchAll(),
            'types' => $typeStmt->fetchAll(),
            'sets' => $setStmt->fetchAll(),
        ];
    }
}
