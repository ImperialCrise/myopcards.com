<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function getAvatarUrl(array $user): string
    {
        if (!empty($user['custom_avatar'])) {
            return '/uploads/avatars/' . ltrim($user['custom_avatar'], '/');
        }
        return (string)($user['avatar'] ?? '');
    }

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
            'SELECT id, username, avatar, custom_avatar FROM users WHERE username LIKE :query AND id != :exclude LIMIT :limit'
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
                COALESCE(SUM(c.market_price * uc.quantity), 0) as total_value_usd,
                COALESCE(SUM(COALESCE(c.price_en, c.cardmarket_price, 0) * uc.quantity), 0) as total_value_eur_en,
                COALESCE(SUM(COALESCE(c.price_fr, 0) * uc.quantity), 0) as total_value_eur_fr,
                COALESCE(SUM(COALESCE(c.price_jp, 0) * uc.quantity), 0) as total_value_eur_jp
             FROM user_cards uc
             JOIN cards c ON c.id = uc.card_id
             WHERE uc.user_id = :user_id AND uc.is_wishlist = 0'
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch() ?: ['unique_cards' => 0, 'total_cards' => 0, 'total_value_usd' => 0, 'total_value_eur_en' => 0, 'total_value_eur_fr' => 0, 'total_value_eur_jp' => 0];

        $cur = \App\Core\Currency::current();
        $valueMap = [
            'usd' => (float)$row['total_value_usd'],
            'eur_en' => (float)$row['total_value_eur_en'],
            'eur_fr' => (float)$row['total_value_eur_fr'],
            'eur_jp' => (float)$row['total_value_eur_jp'],
        ];
        $row['total_value'] = $valueMap[$cur] ?? $valueMap['usd'];
        $row['total_value_symbol'] = \App\Core\Currency::symbol();
        $row['total_value_label'] = \App\Core\Currency::label();

        return $row;
    }

    public static function getFeaturedCard(int $userId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT c.*, u.featured_card_id 
             FROM users u
             LEFT JOIN cards c ON c.id = u.featured_card_id
             WHERE u.id = :user_id AND u.featured_card_id IS NOT NULL'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function setFeaturedCard(int $userId, ?int $cardId): bool
    {
        $db = Database::getConnection();
        
        // Validate that the user owns the card if cardId is not null
        if ($cardId !== null) {
            $ownsCard = $db->prepare(
                'SELECT 1 FROM user_cards WHERE user_id = :user_id AND card_id = :card_id AND is_wishlist = 0'
            );
            $ownsCard->execute(['user_id' => $userId, 'card_id' => $cardId]);
            
            if (!$ownsCard->fetch()) {
                return false; // User doesn't own this card
            }
        }
        
        $stmt = $db->prepare('UPDATE users SET featured_card_id = :card_id WHERE id = :user_id');
        return $stmt->execute(['card_id' => $cardId, 'user_id' => $userId]);
    }

    public static function getRecentForumActivity(int $userId, int $limit = 10): array
    {
        $db = Database::getConnection();
        
        // Get recent topics created by user
        $topics = $db->prepare(
            "SELECT 'topic' as type, t.id, t.title, t.slug, t.created_at, c.slug as category_slug
             FROM forum_topics t
             JOIN forum_categories c ON c.id = t.category_id
             WHERE t.user_id = :user_id
             ORDER BY t.created_at DESC
             LIMIT :limit"
        );
        $topics->bindValue('user_id', $userId, PDO::PARAM_INT);
        $topics->bindValue('limit', $limit, PDO::PARAM_INT);
        $topics->execute();
        $topicResults = $topics->fetchAll();

        // Get recent posts by user
        $posts = $db->prepare(
            "SELECT 'post' as type, p.id, t.title, t.slug, t.id as topic_id, p.created_at, c.slug as category_slug
             FROM forum_posts p
             JOIN forum_topics t ON t.id = p.topic_id
             JOIN forum_categories c ON c.id = t.category_id
             WHERE p.user_id = :user_id
             ORDER BY p.created_at DESC
             LIMIT :limit"
        );
        $posts->bindValue('user_id', $userId, PDO::PARAM_INT);
        $posts->bindValue('limit', $limit, PDO::PARAM_INT);
        $posts->execute();
        $postResults = $posts->fetchAll();

        // Merge and sort by creation date
        $activities = array_merge($topicResults, $postResults);
        usort($activities, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return array_slice($activities, 0, $limit);
    }
}
