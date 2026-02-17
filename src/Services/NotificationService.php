<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Auth;
use PDO;

class NotificationService
{
    public static function createForumReply(int $topicAuthorId, int $replyAuthorId, int $topicId, string $topicTitle): void
    {
        if ($topicAuthorId === $replyAuthorId) {
            return; // Don't notify yourself
        }

        $db = Database::getConnection();
        
        // Check if user wants forum reply notifications
        $settings = $db->prepare("SELECT forum_replies FROM notification_settings WHERE user_id = ?");
        $settings->execute([$topicAuthorId]);
        $setting = $settings->fetch();
        
        if (!$setting || !$setting['forum_replies']) {
            return;
        }

        $replyAuthor = $db->prepare("SELECT username FROM users WHERE id = ?");
        $replyAuthor->execute([$replyAuthorId]);
        $author = $replyAuthor->fetch();

        $stmt = $db->prepare(
            "INSERT INTO notifications (user_id, type, title, message, data) 
             VALUES (?, 'forum_reply', ?, ?, ?)"
        );
        
        $stmt->execute([
            $topicAuthorId,
            'New reply to your topic',
            $author['username'] . ' replied to your topic: "' . $topicTitle . '"',
            json_encode(['topic_id' => $topicId, 'reply_author' => $author['username']])
        ]);
    }

    public static function createForumLike(int $postAuthorId, int $likerUserId, int $postId, string $postType = 'post'): void
    {
        if ($postAuthorId === $likerUserId) {
            return; // Don't notify yourself
        }

        $db = Database::getConnection();
        
        // Check if user wants forum like notifications
        $settings = $db->prepare("SELECT forum_likes FROM notification_settings WHERE user_id = ?");
        $settings->execute([$postAuthorId]);
        $setting = $settings->fetch();
        
        if (!$setting || !$setting['forum_likes']) {
            return;
        }

        $liker = $db->prepare("SELECT username FROM users WHERE id = ?");
        $liker->execute([$likerUserId]);
        $user = $liker->fetch();

        $stmt = $db->prepare(
            "INSERT INTO notifications (user_id, type, title, message, data) 
             VALUES (?, 'forum_like', ?, ?, ?)"
        );
        
        $stmt->execute([
            $postAuthorId,
            'Someone liked your ' . $postType,
            $user['username'] . ' liked your forum ' . $postType,
            json_encode(['post_id' => $postId, 'liker' => $user['username']])
        ]);
    }

    public static function getUnreadCount(int $userId): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = FALSE");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public static function getNotifications(int $userId, int $limit = 20): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT * FROM notifications WHERE user_id = ? 
             ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $notifications = $stmt->fetchAll();
        foreach ($notifications as &$notif) {
            $notif['data'] = json_decode($notif['data'], true);
        }
        
        return $notifications;
    }

    public static function markAsRead(int $userId, int $notificationId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "UPDATE notifications SET is_read = TRUE 
             WHERE id = ? AND user_id = ?"
        );
        return $stmt->execute([$notificationId, $userId]);
    }

    public static function markAllAsRead(int $userId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "UPDATE notifications SET is_read = TRUE WHERE user_id = ?"
        );
        return $stmt->execute([$userId]);
    }

    public static function ensureUserSettings(int $userId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "INSERT IGNORE INTO notification_settings (user_id) VALUES (?)"
        );
        $stmt->execute([$userId]);
    }
}