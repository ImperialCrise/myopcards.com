<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class CardSet
{
    public static function getAll(): array
    {
        $db = Database::getConnection();
        return $db->query('SELECT * FROM sets ORDER BY set_id ASC')->fetchAll();
    }

    public static function upsert(string $setId, string $setName, string $setType = 'booster'): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO sets (set_id, set_name, set_type, last_synced_at)
             VALUES (:set_id, :set_name, :set_type, NOW())
             ON DUPLICATE KEY UPDATE set_name = VALUES(set_name), last_synced_at = NOW()'
        );
        $stmt->execute(['set_id' => $setId, 'set_name' => $setName, 'set_type' => $setType]);
    }

    public static function updateCardCount(string $setId, int $count): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE sets SET card_count = :count WHERE set_id = :set_id');
        $stmt->execute(['count' => $count, 'set_id' => $setId]);
    }

    public static function getCompletionForUser(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT s.set_id, s.set_name, s.card_count,
                    COUNT(DISTINCT uc.card_id) as owned
             FROM sets s
             LEFT JOIN cards c ON c.set_id = s.set_id
             LEFT JOIN user_cards uc ON uc.card_id = c.id AND uc.user_id = :user_id AND uc.is_wishlist = 0
             GROUP BY s.set_id, s.set_name, s.card_count
             ORDER BY s.set_id ASC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
