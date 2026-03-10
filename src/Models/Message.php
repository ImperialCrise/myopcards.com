<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Services\NotificationService;
use App\Services\StorageService;
use PDO;

class Message
{
    private static ?int $systemUserId = null;

    public static function getSystemUserId(): int
    {
        if (self::$systemUserId === null) {
            $db = Database::getConnection();
            $row = $db->query("SELECT id FROM users WHERE is_system = 1 LIMIT 1")->fetch();
            self::$systemUserId = $row ? (int)$row['id'] : 0;
        }
        return self::$systemUserId;
    }

    public static function canSendMessage(int $senderId, int $recipientId): bool
    {
        if ($senderId === $recipientId) {
            return false;
        }
        // System user can always message anyone
        if ($senderId === self::getSystemUserId()) {
            return true;
        }
        if (Friendship::isBlocked($senderId, $recipientId)) {
            return false;
        }
        $recipient = User::findById($recipientId);
        if (!$recipient) {
            return false;
        }
        $allowNonFriends = (bool)($recipient['allow_messages_from_non_friends'] ?? false);
        if ($allowNonFriends) {
            return true;
        }
        return Friendship::areFriends($senderId, $recipientId);
    }

    public static function getConversations(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT c.id, c.created_at,
                    (SELECT user_id FROM conversation_participants WHERE conversation_id = c.id AND user_id != :uid LIMIT 1) as other_user_id
             FROM conversations c
             JOIN conversation_participants cp ON cp.conversation_id = c.id
             WHERE cp.user_id = :uid2
             ORDER BY c.id DESC"
        );
        $stmt->execute(['uid' => $userId, 'uid2' => $userId]);
        $convs = $stmt->fetchAll();
        $result = [];
        foreach ($convs as $conv) {
            $otherId = (int)$conv['other_user_id'];
            $other = User::findById($otherId);
            $lastMsg = $db->prepare(
                "SELECT body, created_at, sender_id, type, media_url, is_deleted FROM messages
                 WHERE conversation_id = ? ORDER BY created_at DESC LIMIT 1"
            );
            $lastMsg->execute([$conv['id']]);
            $last = $lastMsg->fetch();
            $lastRead = $db->prepare("SELECT last_read_at FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
            $lastRead->execute([$conv['id'], $userId]);
            $lr = $lastRead->fetch();
            $unreadStmt = $db->prepare(
                "SELECT COUNT(*) FROM messages WHERE conversation_id = ? AND sender_id != ? AND is_deleted = 0 AND (? IS NULL OR created_at > ?)"
            );
            $unreadStmt->execute([$conv['id'], $userId, $lr['last_read_at'] ?? null, $lr['last_read_at'] ?? null]);

            $lastBody = '';
            if ($last) {
                if ($last['is_deleted']) {
                    $lastBody = '(message deleted)';
                } elseif ($last['type'] === 'image') {
                    $lastBody = '📷 Image';
                } elseif ($last['type'] === 'gif') {
                    $lastBody = '🎞 GIF';
                } else {
                    $lastBody = $last['body'];
                }
            }

            $result[] = [
                'id' => (int)$conv['id'],
                'other_user' => $other ? [
                    'id' => $other['id'],
                    'username' => $other['username'],
                    'avatar_url' => User::getAvatarUrl($other),
                    'is_system' => (bool)($other['is_system'] ?? false),
                ] : null,
                'last_message' => $last ? [
                    'body' => $lastBody,
                    'created_at' => $last['created_at'],
                    'sender_id' => (int)$last['sender_id'],
                ] : null,
                'unread_count' => (int)$unreadStmt->fetchColumn(),
            ];
        }
        usort($result, function ($a, $b) {
            $dateA = ($a['last_message'] ?? [])['created_at'] ?? (string)($a['id'] ?? '');
            $dateB = ($b['last_message'] ?? [])['created_at'] ?? (string)($b['id'] ?? '');
            return strcmp($dateB, $dateA);
        });
        return $result;
    }

    public static function getOrCreateConversation(int $userId, int $otherUserId): ?int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT c.id FROM conversations c
             JOIN conversation_participants cp1 ON cp1.conversation_id = c.id AND cp1.user_id = :uid
             JOIN conversation_participants cp2 ON cp2.conversation_id = c.id AND cp2.user_id = :oid"
        );
        $stmt->execute(['uid' => $userId, 'oid' => $otherUserId]);
        $row = $stmt->fetch();
        if ($row) {
            return (int)$row['id'];
        }
        $db->beginTransaction();
        try {
            $db->prepare("INSERT INTO conversations (created_at) VALUES (NOW())")->execute();
            $convId = (int)$db->lastInsertId();
            $ins = $db->prepare("INSERT INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)");
            $ins->execute([$convId, $userId]);
            $ins->execute([$convId, $otherUserId]);
            $db->commit();
            return $convId;
        } catch (\Throwable $e) {
            $db->rollBack();
            return null;
        }
    }

    public static function getConversationForUser(int $conversationId, int $userId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT c.*,
                    (SELECT user_id FROM conversation_participants WHERE conversation_id = c.id AND user_id != :uid LIMIT 1) as other_user_id
             FROM conversations c
             JOIN conversation_participants cp ON cp.conversation_id = c.id AND cp.user_id = :uid2
             WHERE c.id = :cid"
        );
        $stmt->execute(['uid' => $userId, 'uid2' => $userId, 'cid' => $conversationId]);
        return $stmt->fetch() ?: null;
    }

    public static function getMessages(int $conversationId, int $userId, int $limit = 50): array
    {
        $conv = self::getConversationForUser($conversationId, $userId);
        if (!$conv) {
            return [];
        }
        $db = Database::getConnection();
        $db->prepare("UPDATE conversation_participants SET last_read_at = NOW() WHERE conversation_id = ? AND user_id = ?")
           ->execute([$conversationId, $userId]);
        $stmt = $db->prepare(
            "SELECT m.id, m.conversation_id, m.sender_id, m.body, m.type, m.media_url,
                    m.edited_at, m.is_deleted, m.created_at,
                    u.username as sender_username, u.avatar as sender_avatar, u.custom_avatar as sender_custom_avatar,
                    u.is_system as sender_is_system
             FROM messages m
             JOIN users u ON u.id = m.sender_id
             WHERE m.conversation_id = :cid
             ORDER BY m.created_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue('cid', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_reverse($stmt->fetchAll());
    }

    public static function pollMessages(int $conversationId, int $userId, int $afterId): array
    {
        $conv = self::getConversationForUser($conversationId, $userId);
        if (!$conv) {
            return [];
        }
        $db = Database::getConnection();
        $db->prepare("UPDATE conversation_participants SET last_read_at = NOW() WHERE conversation_id = ? AND user_id = ?")
           ->execute([$conversationId, $userId]);
        $stmt = $db->prepare(
            "SELECT m.id, m.conversation_id, m.sender_id, m.body, m.type, m.media_url,
                    m.edited_at, m.is_deleted, m.created_at,
                    u.username as sender_username, u.avatar as sender_avatar, u.custom_avatar as sender_custom_avatar,
                    u.is_system as sender_is_system
             FROM messages m
             JOIN users u ON u.id = m.sender_id
             WHERE m.conversation_id = :cid AND m.id > :after
             ORDER BY m.created_at ASC
             LIMIT 50"
        );
        $stmt->bindValue('cid', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue('after', $afterId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function sendMessage(int $conversationId, int $senderId, string $body, string $type = 'text', ?string $mediaUrl = null): ?int
    {
        if ($type === 'text') {
            $body = trim($body);
            if ($body === '') {
                return null;
            }
        }
        $conv = self::getConversationForUser($conversationId, $senderId);
        if (!$conv) {
            return null;
        }
        $recipientId = (int)$conv['other_user_id'];
        // System user bypass
        if ($senderId !== self::getSystemUserId() && !self::canSendMessage($senderId, $recipientId)) {
            return null;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO messages (conversation_id, sender_id, body, type, media_url) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$conversationId, $senderId, $body, $type, $mediaUrl]);
        return (int)$db->lastInsertId();
    }

    public static function editMessage(int $msgId, int $userId, string $body): bool
    {
        $body = trim($body);
        if ($body === '') {
            return false;
        }
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "UPDATE messages SET body = :body, edited_at = NOW()
             WHERE id = :id AND sender_id = :uid AND is_deleted = 0 AND type = 'text'"
        );
        $stmt->execute(['body' => $body, 'id' => $msgId, 'uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function softDelete(int $msgId, int $userId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "UPDATE messages SET is_deleted = 1 WHERE id = :id AND sender_id = :uid"
        );
        $stmt->execute(['id' => $msgId, 'uid' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function setTyping(int $conversationId, int $userId): void
    {
        $conv = self::getConversationForUser($conversationId, $userId);
        if (!$conv) {
            return;
        }
        $db = Database::getConnection();
        $db->prepare(
            "INSERT INTO message_typing (conversation_id, user_id, updated_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE updated_at = NOW()"
        )->execute([$conversationId, $userId]);
    }

    public static function getTypingUsers(int $conversationId, int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT u.username FROM message_typing mt
             JOIN users u ON u.id = mt.user_id
             WHERE mt.conversation_id = :cid AND mt.user_id != :uid
             AND mt.updated_at >= DATE_SUB(NOW(), INTERVAL 3 SECOND)"
        );
        $stmt->execute(['cid' => $conversationId, 'uid' => $userId]);
        return array_column($stmt->fetchAll(), 'username');
    }

    public static function uploadMedia(array $file, int $conversationId): ?string
    {
        if (!StorageService::isConfigured()) {
            return null;
        }
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed, true)) {
            return null;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            return null;
        }
        $ext = match($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
            default      => 'bin',
        };
        $filename = bin2hex(random_bytes(12)) . '.' . $ext;
        $key = 'messages/' . $conversationId . '/' . $filename;
        $content = file_get_contents($file['tmp_name']);
        if (!StorageService::put($key, $content, $mime)) {
            return null;
        }
        // Serve via the PHP proxy (MinIO is not publicly accessible)
        return '/uploads/messages/' . $conversationId . '/' . $filename;
    }

    public static function sendWelcomeMessage(int $userId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT welcome_sent FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row || $row['welcome_sent']) {
            return;
        }

        $systemId = self::getSystemUserId();
        if ($systemId === 0) {
            return;
        }

        $convId = self::getOrCreateConversation($systemId, $userId);
        if (!$convId) {
            return;
        }

        $welcome  = "👋 Welcome to MyOPCards!\n\n";
        $welcome .= "We're thrilled to have you join our One Piece TCG community. ";
        $welcome .= "Here's everything you can do:\n\n";
        $welcome .= "🃏 Browse all cards → https://myopcards.com/cards\n";
        $welcome .= "📦 Manage your collection → https://myopcards.com/collection\n";
        $welcome .= "⚔️ Play ranked matches → https://myopcards.com/play\n";
        $welcome .= "🏆 Climb the leaderboard → https://myopcards.com/leaderboard\n";
        $welcome .= "💬 Join the community forum → https://myopcards.com/forum\n\n";
        $welcome .= "Make friends, build your best deck, and climb the ELO ranks. ";
        $welcome .= "Good luck and have fun! 🏴‍☠️";

        $ins = $db->prepare(
            "INSERT INTO messages (conversation_id, sender_id, body, type) VALUES (?, ?, ?, 'text')"
        );
        $ins->execute([$convId, $systemId, $welcome]);

        $gifUrl = self::fetchKlipyGif('one piece luffy');
        if ($gifUrl) {
            $db->prepare(
                "INSERT INTO messages (conversation_id, sender_id, body, type, media_url) VALUES (?, ?, '', 'gif', ?)"
            )->execute([$convId, $systemId, $gifUrl]);
        }

        $db->prepare("UPDATE users SET welcome_sent = 1 WHERE id = ?")->execute([$userId]);

        NotificationService::ensureUserSettings($userId);
        NotificationService::createPrivateMessage($userId, $systemId, 'MyOPCards', $convId);
    }

    private static function fetchKlipyGif(string $query): ?string
    {
        $key = $_ENV['KLIPY_API_KEY'] ?? '';
        if (!$key) {
            return null;
        }
        $url = 'https://api.klipy.com/api/v1/' . $key . '/gifs/search?'
             . http_build_query(['q' => $query, 'page' => 1, 'per_page' => 5, 'customer_id' => 1]);
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'timeout'       => 6,
                'header'        => "Content-Type: application/json\r\nUser-Agent: MyOPCards/1.0\r\n",
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        if (!$body) {
            return null;
        }
        $data = json_decode($body, true);
        $items = $data['data']['data'] ?? [];
        if (empty($items)) {
            return null;
        }
        $idx  = array_rand($items);
        return $items[$idx]['file']['hd']['gif']['url']
            ?? $items[$idx]['file']['md']['gif']['url']
            ?? null;
    }

    public static function startConversation(int $senderId, int $recipientId, string $body): ?array
    {
        if (!self::canSendMessage($senderId, $recipientId)) {
            return null;
        }
        $convId = self::getOrCreateConversation($senderId, $recipientId);
        if (!$convId) {
            return null;
        }
        $body = trim($body);
        if ($body === '') {
            return ['conversation_id' => $convId, 'message_id' => null];
        }
        $msgId = self::sendMessage($convId, $senderId, $body);
        return ['conversation_id' => $convId, 'message_id' => $msgId];
    }
}
