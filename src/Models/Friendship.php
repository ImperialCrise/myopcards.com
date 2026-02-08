<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Friendship
{
    public static function sendRequest(int $userId, int $friendId): bool
    {
        if ($userId === $friendId) {
            return false;
        }

        $existing = self::getRelationship($userId, $friendId);
        if ($existing) {
            return false;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO friendships (user_id, friend_id, status) VALUES (:user_id, :friend_id, :status)'
        );
        $stmt->execute(['user_id' => $userId, 'friend_id' => $friendId, 'status' => 'pending']);
        return true;
    }

    public static function acceptRequest(int $userId, int $friendId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'UPDATE friendships SET status = :status WHERE user_id = :friend_id AND friend_id = :user_id AND status = :pending'
        );
        $stmt->execute([
            'status' => 'accepted',
            'friend_id' => $friendId,
            'user_id' => $userId,
            'pending' => 'pending',
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function declineRequest(int $userId, int $friendId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'DELETE FROM friendships WHERE user_id = :friend_id AND friend_id = :user_id AND status = :pending'
        );
        $stmt->execute(['friend_id' => $friendId, 'user_id' => $userId, 'pending' => 'pending']);
    }

    public static function removeFriend(int $userId, int $friendId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'DELETE FROM friendships WHERE (user_id = :uid AND friend_id = :fid) OR (user_id = :fid2 AND friend_id = :uid2)'
        );
        $stmt->execute(['uid' => $userId, 'fid' => $friendId, 'fid2' => $friendId, 'uid2' => $userId]);
    }

    public static function getFriends(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT u.id, u.username, u.avatar,
                    CASE WHEN f.user_id = :uid THEN f.friend_id ELSE f.user_id END as friend_user_id
             FROM friendships f
             JOIN users u ON u.id = CASE WHEN f.user_id = :uid2 THEN f.friend_id ELSE f.user_id END
             WHERE (f.user_id = :uid3 OR f.friend_id = :uid4) AND f.status = :accepted'
        );
        $stmt->execute([
            'uid' => $userId,
            'uid2' => $userId,
            'uid3' => $userId,
            'uid4' => $userId,
            'accepted' => 'accepted',
        ]);
        return $stmt->fetchAll();
    }

    public static function getPendingRequests(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT f.*, u.username, u.avatar
             FROM friendships f
             JOIN users u ON u.id = f.user_id
             WHERE f.friend_id = :user_id AND f.status = :pending'
        );
        $stmt->execute(['user_id' => $userId, 'pending' => 'pending']);
        return $stmt->fetchAll();
    }

    public static function getSentRequests(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT f.*, u.username, u.avatar
             FROM friendships f
             JOIN users u ON u.id = f.friend_id
             WHERE f.user_id = :user_id AND f.status = :pending'
        );
        $stmt->execute(['user_id' => $userId, 'pending' => 'pending']);
        return $stmt->fetchAll();
    }

    private static function getRelationship(int $userId, int $friendId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT * FROM friendships WHERE (user_id = :uid AND friend_id = :fid) OR (user_id = :fid2 AND friend_id = :uid2)'
        );
        $stmt->execute(['uid' => $userId, 'fid' => $friendId, 'fid2' => $friendId, 'uid2' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function hasPendingRequest(int $fromUserId, int $toUserId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM friendships WHERE user_id = :from AND friend_id = :to AND status = :pending'
        );
        $stmt->execute(['from' => $fromUserId, 'to' => $toUserId, 'pending' => 'pending']);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function areFriends(int $userId, int $friendId): bool
    {
        $rel = self::getRelationship($userId, $friendId);
        return $rel && $rel['status'] === 'accepted';
    }

    public static function getFriendCount(int $userId): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM friendships WHERE (user_id = :uid OR friend_id = :uid2) AND status = :accepted'
        );
        $stmt->execute(['uid' => $userId, 'uid2' => $userId, 'accepted' => 'accepted']);
        return (int)$stmt->fetchColumn();
    }
}
