<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public static function findByUsername(string $username): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = :username');
        $stmt->execute(['username' => $username]);
        return $stmt->fetch() ?: null;
    }

    public static function findByProvider(string $provider, string $providerId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM users WHERE provider = :provider AND provider_id = :provider_id');
        $stmt->execute(['provider' => $provider, 'provider_id' => $providerId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO users (username, email, password_hash, avatar, provider, provider_id, bio)
             VALUES (:username, :email, :password_hash, :avatar, :provider, :provider_id, :bio)'
        );
        $stmt->execute([
            'username' => $data['username'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'] ?? null,
            'avatar' => $data['avatar'] ?? null,
            'provider' => $data['provider'] ?? 'local',
            'provider_id' => $data['provider_id'] ?? null,
            'bio' => $data['bio'] ?? null,
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $db = Database::getConnection();
        $sets = [];
        $params = ['id' => $id];

        foreach ($data as $key => $value) {
            $sets[] = "$key = :$key";
            $params[$key] = $value;
        }

        $stmt = $db->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id');
        $stmt->execute($params);
    }

    public static function searchByUsername(string $query, int $excludeUserId, int $limit = 20): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT id, username, avatar FROM users WHERE username LIKE :query AND id != :exclude LIMIT :limit'
        );
        $stmt->bindValue('query', '%' . $query . '%');
        $stmt->bindValue('exclude', $excludeUserId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getCollectionStats(int $userId): array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare(
            'SELECT
                COUNT(DISTINCT uc.card_id) as unique_cards,
                SUM(uc.quantity) as total_cards,
                COALESCE(SUM(c.market_price * uc.quantity), 0) as total_value
             FROM user_cards uc
             JOIN cards c ON c.id = uc.card_id
             WHERE uc.user_id = :user_id AND uc.is_wishlist = 0'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: ['unique_cards' => 0, 'total_cards' => 0, 'total_value' => 0];
    }
}
