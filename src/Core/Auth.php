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
        
        try {
            NotificationService::ensureUserSettings($userId);
        } catch (\Throwable $e) {
            error_log('Failed to create notification settings for user ' . $userId . ': ' . $e->getMessage());
        }
    }

    public static function logout(): void
    {
        $userId = self::id();
        $sessionId = session_id();
        
        if ($userId && isset($_COOKIE[self::REMEMBER_COOKIE])) {
            self::deleteRememberToken($_COOKIE[self::REMEMBER_COOKIE]);
        }
        
        // Clean up session record
        if ($userId) {
            try {
                $db = Database::getConnection();
                $db->prepare("DELETE FROM user_sessions WHERE user_id = :uid AND session_id = :sid")
                   ->execute(['uid' => $userId, 'sid' => $sessionId]);
            } catch (\Throwable $e) {
            }
        }

        setcookie(self::REMEMBER_COOKIE, '', time() - 3600, '/', '', true, true);
        
        $_SESSION = [];
        session_destroy();
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $_SESSION['redirect_after_login'] = self::validateRedirectUrl($uri) ? $uri : '/dashboard';
            header('Location: /login');
            exit;
        }
    }

    /**
     * Validate redirect URL to prevent open redirect attacks.
     * Only allow relative paths starting with / (no //, no protocol).
     */
    public static function validateRedirectUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '' || $url[0] !== '/') {
            return false;
        }
        if (str_starts_with($url, '//')) {
            return false;
        }
        return (bool) preg_match('/^\/(?!\/)[a-zA-Z0-9\/\-_?=&.%]*$/', $url);
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrf(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return $token !== '' && hash_equals(self::csrfToken(), $token);
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
            [
                'expires' => time() + 86400 * self::REMEMBER_DAYS,
                'path' => '/',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Lax',
            ]
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
        try {
            $db = Database::getConnection();
            $sessionId = session_id();
            $db->prepare(
                "DELETE FROM user_sessions WHERE user_id = :uid AND expires_at < NOW()"
            )->execute(['uid' => $userId]);
            $db->prepare(
                "INSERT INTO user_sessions (user_id, session_id, last_activity, expires_at) 
                 VALUES (:uid, :sid, NOW(), DATE_ADD(NOW(), INTERVAL :days DAY))
                 ON DUPLICATE KEY UPDATE last_activity = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL :days2 DAY)"
            )->execute(['uid' => $userId, 'sid' => $sessionId, 'days' => self::REMEMBER_DAYS, 'days2' => self::REMEMBER_DAYS]);
        } catch (\Throwable $e) {
            error_log('Auth createSession: ' . $e->getMessage());
        }
    }

    private static function updateActivity(): void
    {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return;
        }

        if (time() - $_SESSION['last_activity'] > 300) {
            $_SESSION['last_activity'] = time();
            
            // Update activity for all logged-in users (not just those with remember tokens)
            $userId = self::id();
            if ($userId) {
                try {
                    $db = Database::getConnection();
                    $sessionId = session_id();
                    
                    // Update session record by session_id or user_id
                    $updated = $db->prepare(
                        "UPDATE user_sessions SET last_activity = NOW() WHERE session_id = :sid"
                    )->execute(['sid' => $sessionId]);
                    
                    // If no session record exists, create one
                    if (!$updated) {
                        $db->prepare(
                            "INSERT INTO user_sessions (user_id, session_id, last_activity, expires_at) 
                             VALUES (:uid, :sid, NOW(), DATE_ADD(NOW(), INTERVAL :days DAY))
                             ON DUPLICATE KEY UPDATE last_activity = NOW()"
                        )->execute([
                            'uid' => $userId,
                            'sid' => $sessionId,
                            'days' => self::REMEMBER_DAYS
                        ]);
                    }
                    
                    // Also update remember token sessions if they exist
                    if (isset($_COOKIE[self::REMEMBER_COOKIE])) {
                        $tokenHash = hash('sha256', $_COOKIE[self::REMEMBER_COOKIE]);
                        $db->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE token_hash = :hash")
                           ->execute(['hash' => $tokenHash]);
                    }
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
                "SELECT u.id, u.username, u.avatar, u.custom_avatar, MAX(us.last_activity) as last_activity
                 FROM user_sessions us
                 JOIN users u ON u.id = us.user_id
                 WHERE us.last_activity > DATE_SUB(NOW(), INTERVAL :mins MINUTE)
                 GROUP BY u.id, u.username, u.avatar, u.custom_avatar
                 ORDER BY last_activity DESC"
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
