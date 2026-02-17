<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;
use App\Services\NotificationService;

class Auth
{
    private const REMEMBER_COOKIE = 'remember_token';
    private const REMEMBER_DAYS = 30;
    private const SESSION_LIFETIME = 86400 * 7;

    public static function check(): bool
    {
        if (isset($_SESSION['user_id'])) {
            self::updateActivity();
            return true;
        }

        return self::checkRememberToken();
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return User::findById((int)$_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return self::check() ? (int)$_SESSION['user_id'] : null;
    }

    public static function login(int $userId, bool $remember = false): void
    {
        $_SESSION['user_id'] = $userId;
        $_SESSION['last_activity'] = time();
        session_regenerate_id(true);

        if ($remember) {
            self::createRememberToken($userId);
        }

        self::createSession($userId);
        NotificationService::ensureUserSettings($userId);
    }

    public static function logout(): void
    {
        $userId = self::id();
        
        if ($userId && isset($_COOKIE[self::REMEMBER_COOKIE])) {
            self::deleteRememberToken($_COOKIE[self::REMEMBER_COOKIE]);
        }

        setcookie(self::REMEMBER_COOKIE, '', time() - 3600, '/', '', true, true);
        
        $_SESSION = [];
        session_destroy();
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: /login');
            exit;
        }
    }

    public static function requireGuest(): void
    {
        if (self::check()) {
            header('Location: /dashboard');
            exit;
        }
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user && !empty($user['is_admin']);
    }

    public static function requireAdmin(): void
    {
        self::requireAuth();
        if (!self::isAdmin()) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
    }

    private static function createRememberToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', time() + 86400 * self::REMEMBER_DAYS);

        $db = Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO user_sessions (user_id, token_hash, ip_address, user_agent, expires_at)
             VALUES (:uid, :hash, :ip, :ua, :exp)"
        );
        $stmt->execute([
            'uid' => $userId,
            'hash' => $tokenHash,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'exp' => $expires,
        ]);

        setcookie(
            self::REMEMBER_COOKIE,
            $token,
            time() + 86400 * self::REMEMBER_DAYS,
            '/',
            '',
            true,
            true
        );
    }

    private static function checkRememberToken(): bool
    {
        if (!isset($_COOKIE[self::REMEMBER_COOKIE])) {
            return false;
        }

        $token = $_COOKIE[self::REMEMBER_COOKIE];
        $tokenHash = hash('sha256', $token);

        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT user_id FROM user_sessions 
             WHERE token_hash = :hash AND expires_at > NOW()"
        );
        $stmt->execute(['hash' => $tokenHash]);
        $session = $stmt->fetch();

        if (!$session) {
            setcookie(self::REMEMBER_COOKIE, '', time() - 3600, '/', '', true, true);
            return false;
        }

        $_SESSION['user_id'] = $session['user_id'];
        $_SESSION['last_activity'] = time();
        session_regenerate_id(true);

        $db->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE token_hash = :hash")
           ->execute(['hash' => $tokenHash]);

        return true;
    }

    private static function deleteRememberToken(string $token): void
    {
        $tokenHash = hash('sha256', $token);
        $db = Database::getConnection();
        $db->prepare("DELETE FROM user_sessions WHERE token_hash = :hash")
           ->execute(['hash' => $tokenHash]);
    }

    private static function createSession(int $userId): void
    {
        $db = Database::getConnection();
        $db->prepare(
            "DELETE FROM user_sessions WHERE user_id = :uid AND expires_at < NOW()"
        )->execute(['uid' => $userId]);
    }

    private static function updateActivity(): void
    {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return;
        }

        if (time() - $_SESSION['last_activity'] > 300) {
            $_SESSION['last_activity'] = time();
            
            if (isset($_COOKIE[self::REMEMBER_COOKIE])) {
                try {
                    $tokenHash = hash('sha256', $_COOKIE[self::REMEMBER_COOKIE]);
                    $db = Database::getConnection();
                    $db->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE token_hash = :hash")
                       ->execute(['hash' => $tokenHash]);
                } catch (\Throwable $e) {
                }
            }
        }
    }

    public static function getOnlineUsers(int $minutes = 15): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare(
                "SELECT DISTINCT u.id, u.username, u.avatar, us.last_activity
                 FROM user_sessions us
                 JOIN users u ON u.id = us.user_id
                 WHERE us.last_activity > DATE_SUB(NOW(), INTERVAL :mins MINUTE)
                 ORDER BY us.last_activity DESC"
            );
            $stmt->execute(['mins' => $minutes]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function getOnlineCount(int $minutes = 15): int
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare(
                "SELECT COUNT(DISTINCT user_id) as cnt FROM user_sessions 
                 WHERE last_activity > DATE_SUB(NOW(), INTERVAL :mins MINUTE)"
            );
            $stmt->execute(['mins' => $minutes]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
