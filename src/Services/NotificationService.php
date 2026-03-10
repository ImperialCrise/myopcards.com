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

    public static function createFriendRequest(int $recipientId, int $senderId, string $senderUsername): void
    {
        if ($recipientId === $senderId) {
            return;
        }

        $db = Database::getConnection();
        $settings = $db->prepare("SELECT friend_requests FROM notification_settings WHERE user_id = ?");
        $settings->execute([$recipientId]);
        $setting = $settings->fetch();
        if (!$setting || ($setting['friend_requests'] ?? true) === false) {
            return;
        }

        $stmt = $db->prepare(
            "INSERT INTO notifications (user_id, type, title, message, data) VALUES (?, 'friend_request', ?, ?, ?)"
        );
        $stmt->execute([
            $recipientId,
            'Friend request',
            $senderUsername . ' sent you a friend request',
            json_encode(['sender_id' => $senderId, 'sender_username' => $senderUsername])
        ]);
    }

    public static function createFriendAccepted(int $userId, int $accepterId, string $accepterUsername): void
    {
        if ($userId === $accepterId) {
            return;
        }

        $db = Database::getConnection();
        $settings = $db->prepare("SELECT friend_accepted FROM notification_settings WHERE user_id = ?");
        $settings->execute([$userId]);
        $setting = $settings->fetch();
        if (!$setting || ($setting['friend_accepted'] ?? true) === false) {
            return;
        }

        $stmt = $db->prepare(
            "INSERT INTO notifications (user_id, type, title, message, data) VALUES (?, 'friend_accepted', ?, ?, ?)"
        );
        $stmt->execute([
            $userId,
            'Friend request accepted',
            $accepterUsername . ' accepted your friend request',
            json_encode(['accepter_id' => $accepterId, 'accepter_username' => $accepterUsername])
        ]);
    }

    public static function createPrivateMessage(int $recipientId, int $senderId, string $senderUsername, ?int $conversationId = null): void
    {
        if ($recipientId === $senderId) {
            return;
        }

        $db = Database::getConnection();
        $settings = $db->prepare("SELECT private_messages FROM notification_settings WHERE user_id = ?");
        $settings->execute([$recipientId]);
        $setting = $settings->fetch();
        if (!$setting || ($setting['private_messages'] ?? true) === false) {
            return;
        }

        // If there is already an unread notification for this conversation, update it instead of creating a new one
        if ($conversationId) {
            $existing = $db->prepare(
                "SELECT id FROM notifications
                 WHERE user_id = ? AND type = 'private_message' AND is_read = 0
                   AND JSON_EXTRACT(data, '$.conversation_id') = ?
                 LIMIT 1"
            );
            $existing->execute([$recipientId, $conversationId]);
            $row = $existing->fetch();
            if ($row) {
                $db->prepare(
                    "UPDATE notifications SET
                        message = ?,
                        data    = ?,
                        created_at = NOW()
                     WHERE id = ?"
                )->execute([
                    $senderUsername . ' sent you a message',
                    json_encode(['sender_id' => $senderId, 'sender_username' => $senderUsername, 'conversation_id' => $conversationId]),
                    $row['id'],
                ]);
                return;
            }
        }

        $db->prepare(
            "INSERT INTO notifications (user_id, type, title, message, data) VALUES (?, 'private_message', ?, ?, ?)"
        )->execute([
            $recipientId,
            'New message',
            $senderUsername . ' sent you a message',
            json_encode(['sender_id' => $senderId, 'sender_username' => $senderUsername, 'conversation_id' => $conversationId]),
        ]);
    }

    public static function createForumMention(int $mentionedUserId, int $authorId, string $authorUsername, int $topicId, ?int $postId = null): void
    {
        if ($mentionedUserId === $authorId) {
            return;
        }

        $db = Database::getConnection();
        $settings = $db->prepare("SELECT forum_mentions FROM notification_settings WHERE user_id = ?");
        $settings->execute([$mentionedUserId]);
        $setting = $settings->fetch();
        if (!$setting || !$setting['forum_mentions']) {
            return;
        }

        $stmt = $db->prepare(
            "INSERT INTO notifications (user_id, type, title, message, data) VALUES (?, 'forum_mention', ?, ?, ?)"
        );
        $stmt->execute([
            $mentionedUserId,
            'You were mentioned',
            $authorUsername . ' mentioned you in a forum post',
            json_encode(['topic_id' => $topicId, 'post_id' => $postId, 'author_username' => $authorUsername])
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

    public static function getRecentWithUrls(int $userId, int $limit = 10): array
    {
        $notifications = self::getNotifications($userId, $limit);
        $db = Database::getConnection();

        foreach ($notifications as &$n) {
            $data = $n['data'] ?? [];
            $url = '/notifications';
            switch ($n['type']) {
                case 'forum_reply':
                case 'forum_like':
                case 'forum_mention':
                    if (!empty($data['topic_id'])) {
                        $t = $db->prepare("SELECT t.slug as topic_slug, c.slug as category_slug FROM forum_topics t JOIN forum_categories c ON c.id = t.category_id WHERE t.id = ?");
                        $t->execute([$data['topic_id']]);
                        $row = $t->fetch();
                        if ($row) {
                            $url = '/forum/' . $row['category_slug'] . '/' . $data['topic_id'] . '-' . $row['topic_slug'];
                            if (!empty($data['post_id'])) {
                                $url .= '#post-' . $data['post_id'];
                            }
                        }
                    }
                    break;
                case 'friend_request':
                case 'friend_accepted':
                    $url = '/friends';
                    break;
                case 'private_message':
                    $url = !empty($data['conversation_id']) ? '/messages/' . $data['conversation_id'] : '/messages';
                    break;
            }
            $n['url'] = $url;
        }
        unset($n);
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

    public static function markConversationNotificationsRead(int $userId, int $conversationId): void
    {
        $db = Database::getConnection();
        $db->prepare(
            "UPDATE notifications SET is_read = TRUE
             WHERE user_id = ? AND type = 'private_message'
               AND JSON_EXTRACT(data, '$.conversation_id') = ?"
        )->execute([$userId, $conversationId]);
    }

    public static function isRecipientActiveInConversation(int $recipientId, int $conversationId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT last_read_at FROM conversation_participants
             WHERE conversation_id = ? AND user_id = ?"
        );
        $stmt->execute([$conversationId, $recipientId]);
        $row = $stmt->fetch();
        if (!$row || !$row['last_read_at']) {
            return false;
        }
        $diff = time() - strtotime($row['last_read_at']);
        return $diff < 15;
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