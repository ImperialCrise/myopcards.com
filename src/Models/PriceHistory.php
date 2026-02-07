<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class PriceHistory
{
    public static function getForCard(int $cardId, string $source = 'tcgplayer', int $days = 90): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT price, recorded_at FROM price_history
             WHERE card_id = :card_id AND source = :source AND recorded_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
             ORDER BY recorded_at ASC"
        );
        $stmt->bindValue('card_id', $cardId, PDO::PARAM_INT);
        $stmt->bindValue('source', $source);
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getTopMovers(string $source = 'tcgplayer', int $days = 7, int $limit = 10, string $direction = 'up'): array
    {
        $db = Database::getConnection();
        $order = $direction === 'up' ? 'DESC' : 'ASC';

        $stmt = $db->prepare("
            SELECT c.id, c.card_set_id, c.card_name, c.card_image_url, c.rarity, c.set_name,
                   ph_new.price as current_price,
                   ph_old.price as old_price,
                   (ph_new.price - ph_old.price) as price_change,
                   CASE WHEN ph_old.price > 0
                       THEN ROUND(((ph_new.price - ph_old.price) / ph_old.price) * 100, 1)
                       ELSE 0
                   END as pct_change
            FROM cards c
            JOIN price_history ph_new ON ph_new.card_id = c.id AND ph_new.source = :src1 AND ph_new.recorded_at = CURDATE()
            JOIN price_history ph_old ON ph_old.card_id = c.id AND ph_old.source = :src2
                AND ph_old.recorded_at = (
                    SELECT MAX(recorded_at) FROM price_history
                    WHERE card_id = c.id AND source = :src3 AND recorded_at <= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                )
            WHERE ph_old.price > 0
            ORDER BY price_change $order
            LIMIT :lim
        ");
        $stmt->bindValue('src1', $source);
        $stmt->bindValue('src2', $source);
        $stmt->bindValue('src3', $source);
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getMostExpensive(int $limit = 20): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT id, card_set_id, card_name, card_image_url, rarity, set_name,
                    card_color, card_type, market_price, cardmarket_price
             FROM cards
             WHERE market_price IS NOT NULL AND market_price > 0
             ORDER BY market_price DESC
             LIMIT :lim"
        );
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getMostCollected(int $limit = 20): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT c.id, c.card_set_id, c.card_name, c.card_image_url, c.rarity, c.set_name,
                    c.market_price, COUNT(DISTINCT uc.user_id) as collector_count,
                    SUM(uc.quantity) as total_owned
             FROM cards c
             JOIN user_cards uc ON uc.card_id = c.id AND uc.is_wishlist = 0
             GROUP BY c.id
             ORDER BY collector_count DESC, total_owned DESC
             LIMIT :lim"
        );
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getSetValueSummary(): array
    {
        $db = Database::getConnection();
        return $db->query(
            "SELECT s.set_id, s.set_name, s.card_count,
                    COALESCE(SUM(c.market_price), 0) as total_value_usd,
                    COALESCE(SUM(c.cardmarket_price), 0) as total_value_eur,
                    COALESCE(AVG(NULLIF(c.market_price, 0)), 0) as avg_price_usd,
                    COUNT(c.id) as card_count_actual
             FROM sets s
             LEFT JOIN cards c ON c.set_id = s.set_id
             GROUP BY s.set_id, s.set_name, s.card_count
             ORDER BY s.set_id ASC"
        )->fetchAll();
    }

    public static function getRecentlyAdded(int $limit = 20): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT id, card_set_id, card_name, card_image_url, rarity, set_name,
                    card_color, market_price, last_synced_at
             FROM cards
             ORDER BY last_synced_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
