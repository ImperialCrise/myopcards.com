<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Leaderboard
{
    public static function getTop(int $limit = 100): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT l.*, u.username FROM leaderboard l JOIN users u ON u.id = l.user_id ORDER BY l.elo_rating DESC, l.games_played DESC LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getByUserId(int $userId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM leaderboard WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function getRankForUser(int $userId): ?int
    {
        $row = self::getByUserId($userId);
        if (!$row) return null;
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COUNT(*) + 1 FROM leaderboard WHERE elo_rating > :elo');
        $stmt->execute(['elo' => $row['elo_rating']]);
        return (int) $stmt->fetchColumn();
    }

    public static function ensureUser(int $userId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('INSERT IGNORE INTO leaderboard (user_id) VALUES (:user_id)');
        $stmt->execute(['user_id' => $userId]);
    }
}
