<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class MarketplaceReview
{
    public static function create(array $data): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO marketplace_reviews (order_id, reviewer_id, reviewed_user_id, rating, title, comment, role, created_at, updated_at)
             VALUES (:order_id, :reviewer_id, :reviewed_user_id, :rating, :title, :comment, :role, NOW(), NOW())'
        );
        $stmt->execute([
            'order_id' => $data['order_id'],
            'reviewer_id' => $data['reviewer_id'],
            'reviewed_user_id' => $data['reviewed_user_id'],
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'comment' => $data['comment'] ?? null,
            'role' => $data['role'] ?? 'buyer',
        ]);
        return (int) $db->lastInsertId();
    }

    public static function getForUser(int $userId, int $page = 1, int $perPage = 20): array
    {
        $db = Database::getConnection();
        $offset = ($page - 1) * $perPage;

        $countStmt = $db->prepare('SELECT COUNT(*) FROM marketplace_reviews WHERE reviewed_user_id = :user_id');
        $countStmt->execute(['user_id' => $userId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $db->prepare(
            'SELECT mr.*, u.username AS reviewer_username, u.avatar AS reviewer_avatar, u.custom_avatar AS reviewer_custom_avatar
             FROM marketplace_reviews mr
             JOIN users u ON u.id = mr.reviewer_id
             WHERE mr.reviewed_user_id = :user_id
             ORDER BY mr.created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'reviews' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'total_pages' => (int) ceil($total / max($perPage, 1)),
        ];
    }

    public static function getAverageRating(int $userId): ?float
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT AVG(rating) FROM marketplace_reviews WHERE reviewed_user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetchColumn();
        return $result !== false && $result !== null ? round((float) $result, 2) : null;
    }

    public static function getReviewStats(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT
                COUNT(*) AS total_reviews,
                AVG(rating) AS avg_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) AS five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) AS four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) AS three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) AS two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) AS one_star
             FROM marketplace_reviews
             WHERE reviewed_user_id = :user_id'
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();

        return [
            'total_reviews' => (int) ($row['total_reviews'] ?? 0),
            'avg_rating' => $row['avg_rating'] !== null ? round((float) $row['avg_rating'], 2) : null,
            'five_star' => (int) ($row['five_star'] ?? 0),
            'four_star' => (int) ($row['four_star'] ?? 0),
            'three_star' => (int) ($row['three_star'] ?? 0),
            'two_star' => (int) ($row['two_star'] ?? 0),
            'one_star' => (int) ($row['one_star'] ?? 0),
        ];
    }

    public static function getForOrder(int $orderId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT mr.*, u.username AS reviewer_username
             FROM marketplace_reviews mr
             JOIN users u ON u.id = mr.reviewer_id
             WHERE mr.order_id = :order_id'
        );
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetchAll();
    }

    public static function hasReviewed(int $orderId, int $reviewerId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM marketplace_reviews WHERE order_id = :order_id AND reviewer_id = :reviewer_id'
        );
        $stmt->execute(['order_id' => $orderId, 'reviewer_id' => $reviewerId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
