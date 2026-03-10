<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Message;
use App\Models\User;
use App\Services\NotificationService;

class MessageController
{
    public function inbox(): void
    {
        Auth::requireAuth();
        try {
            $conversations = Message::getConversations(Auth::id());
            View::render('pages/messages/inbox', [
                'title' => 'Messages',
                'conversations' => $conversations,
                'noTicker' => true,
                'fullWidth' => true,
            ]);
        } catch (\Throwable $e) {
            error_log('[MessageController::inbox] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            http_response_code(500);
            $msg = 'Unable to load messages. Please try again later.';
            if (str_contains($e->getMessage(), "doesn't exist")) {
                $msg = 'Messages feature requires a database migration. Please run: php bin/migrate-messages-only.php';
            }
            View::render('pages/500', ['title' => 'Error', 'message' => $msg]);
        }
    }

    public function conversation(int $id): void
    {
        Auth::requireAuth();
        $conv = Message::getConversationForUser($id, Auth::id());
        if (!$conv) {
            http_response_code(404);
            View::render('pages/404', ['title' => 'Not Found']);
            return;
        }
        $messages = Message::getMessages($id, Auth::id());
        $otherUser = User::findById((int)$conv['other_user_id']);
        NotificationService::markConversationNotificationsRead(Auth::id(), $id);
        View::render('pages/messages/conversation', [
            'title' => 'Chat with ' . ($otherUser['username'] ?? 'User'),
            'conversation' => $conv,
            'messages' => $messages,
            'otherUser' => $otherUser,
            'noTicker' => true,
            'fullWidth' => true,
        ]);
    }

    public function newConversation(string $username): void
    {
        Auth::requireAuth();
        $other = User::findByUsername($username);
        if (!$other || (int)$other['id'] === Auth::id()) {
            http_response_code(404);
            View::render('pages/404', ['title' => 'Not Found']);
            return;
        }
        $convId = Message::getOrCreateConversation(Auth::id(), (int)$other['id']);
        if ($convId) {
            header('Location: /messages/' . $convId);
            exit;
        }
        View::render('pages/404', ['title' => 'Not Found']);
    }

    public function sendMessage(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');
        $conversationId = (int)($_POST['conversation_id'] ?? 0);
        $body = trim($_POST['body'] ?? '');
        $type = $_POST['type'] ?? 'text';
        $mediaUrl = $_POST['media_url'] ?? null;

        if (!in_array($type, ['text', 'image', 'gif'], true)) {
            $type = 'text';
        }
        if ($type === 'text' && $body === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        if ($conversationId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        $msgId = Message::sendMessage($conversationId, Auth::id(), $body, $type, $mediaUrl ?: null);
        if (!$msgId) {
            echo json_encode(['success' => false, 'message' => 'Could not send message']);
            return;
        }
        $conv = Message::getConversationForUser($conversationId, Auth::id());
        $recipientId = $conv ? (int)$conv['other_user_id'] : 0;
        if ($recipientId && $recipientId !== Message::getSystemUserId()
            && !NotificationService::isRecipientActiveInConversation($recipientId, $conversationId)) {
            $me = User::findById(Auth::id());
            if ($me) {
                NotificationService::createPrivateMessage($recipientId, Auth::id(), $me['username'], $conversationId);
            }
        }
        echo json_encode(['success' => true, 'message_id' => $msgId]);
    }

    public function poll(int $id): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');
        $afterId = (int)($_GET['after'] ?? 0);
        $conv = Message::getConversationForUser($id, Auth::id());
        if (!$conv) {
            echo json_encode(['success' => false, 'messages' => [], 'typing' => []]);
            return;
        }
        $messages = Message::pollMessages($id, Auth::id(), $afterId);
        $typing = Message::getTypingUsers($id, Auth::id());
        NotificationService::markConversationNotificationsRead(Auth::id(), $id);
        $unreadCount = NotificationService::getUnreadCount(Auth::id());

        $formatted = array_map(fn($m) => self::formatMessage($m), $messages);
        echo json_encode([
            'success'      => true,
            'messages'     => $formatted,
            'typing'       => $typing,
            'unread_count' => $unreadCount,
        ]);
    }

    public function setTyping(int $id): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');
        Message::setTyping($id, Auth::id());
        echo json_encode(['success' => true]);
    }

    public function editMessage(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');
        $msgId = (int)($_POST['message_id'] ?? 0);
        $body = trim($_POST['body'] ?? '');
        if ($msgId <= 0 || $body === '') {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        $ok = Message::editMessage($msgId, Auth::id(), $body);
        echo json_encode(['success' => $ok]);
    }

    public function deleteMessage(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');
        $msgId = (int)($_POST['message_id'] ?? 0);
        if ($msgId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        $ok = Message::softDelete($msgId, Auth::id());
        echo json_encode(['success' => $ok]);
    }

    public function uploadMedia(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');
        $conversationId = (int)($_POST['conversation_id'] ?? 0);
        if ($conversationId <= 0 || empty($_FILES['file'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        $conv = Message::getConversationForUser($conversationId, Auth::id());
        if (!$conv) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        $url = Message::uploadMedia($_FILES['file'], $conversationId);
        if (!$url) {
            echo json_encode(['success' => false, 'message' => 'Upload failed. Check file type (jpg/png/gif/webp) and size (max 5MB).']);
            return;
        }
        echo json_encode(['success' => true, 'url' => $url]);
    }

    public function startConversation(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');
        $recipientId = (int)($_POST['user_id'] ?? 0);
        $body = trim($_POST['body'] ?? '');
        if ($recipientId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user']);
            return;
        }
        $result = Message::startConversation(Auth::id(), $recipientId, $body);
        if (!$result) {
            echo json_encode(['success' => false, 'message' => 'Cannot message this user']);
            return;
        }
        $recipient = User::findById($recipientId);
        if ($recipient && $result['message_id']) {
            $me = User::findById(Auth::id());
            if ($me) {
                NotificationService::createPrivateMessage($recipientId, Auth::id(), $me['username'], $result['conversation_id']);
            }
        }
        echo json_encode(['success' => true, 'conversation_id' => $result['conversation_id'], 'message_id' => $result['message_id']]);
    }

    private static function formatMessage(array $m): array
    {
        return [
            'id'                => (int)$m['id'],
            'conversation_id'   => (int)$m['conversation_id'],
            'sender_id'         => (int)$m['sender_id'],
            'sender_username'   => $m['sender_username'],
            'sender_is_system'  => (bool)($m['sender_is_system'] ?? false),
            'sender_avatar'     => User::getAvatarUrl([
                'avatar'        => $m['sender_avatar'] ?? null,
                'custom_avatar' => $m['sender_custom_avatar'] ?? null,
            ]),
            'body'       => $m['body'],
            'type'       => $m['type'] ?? 'text',
            'media_url'  => $m['media_url'] ?? null,
            'edited_at'  => $m['edited_at'] ?? null,
            'is_deleted' => (bool)($m['is_deleted'] ?? false),
            'created_at' => $m['created_at'],
        ];
    }

    public static function getFormattedMessages(array $messages): array
    {
        return array_map([self::class, 'formatMessage'], $messages);
    }
}
