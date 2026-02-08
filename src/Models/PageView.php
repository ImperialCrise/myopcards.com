<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class PageView
{
    public static function record(int $userId, string $pageType): void
    {
        $viewerIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
        $viewerIp = explode(',', $viewerIp)[0];
        $viewerUserId = \App\Core\Auth::check() ? \App\Core\Auth::id() : null;

        if ($viewerUserId === $userId) {
            return;
        }

        $db = Database::getConnection();

        $stmt = $db->prepare(
            "SELECT id FROM page_views
             WHERE user_id = :uid AND page_type = :pt AND viewer_ip = :ip
               AND viewed_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
             LIMIT 1"
        );
        $stmt->execute(['uid' => $userId, 'pt' => $pageType, 'ip' => $viewerIp]);

        if ($stmt->fetch()) {
            return;
        }

        $db->prepare(
            "INSERT INTO page_views (user_id, page_type, viewer_ip, viewer_user_id)
             VALUES (:uid, :pt, :ip, :vid)"
        )->execute([
            'uid' => $userId,
            'pt' => $pageType,
            'ip' => $viewerIp,
            'vid' => $viewerUserId,
        ]);

        $col = $pageType === 'profile' ? 'profile_views' : 'collection_views';
        $db->prepare("UPDATE users SET $col = $col + 1 WHERE id = :uid")->execute(['uid' => $userId]);
    }

    public static function getCounts(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT profile_views, collection_views FROM users WHERE id = :uid"
        );
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch();
        return [
            'profile' => (int)($row['profile_views'] ?? 0),
            'collection' => (int)($row['collection_views'] ?? 0),
            'total' => (int)($row['profile_views'] ?? 0) + (int)($row['collection_views'] ?? 0),
        ];
    }

    public static function getRecentViewers(int $userId, int $limit = 10): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT pv.page_type, pv.viewed_at, u.username, u.avatar
             FROM page_views pv
             LEFT JOIN users u ON u.id = pv.viewer_user_id
             WHERE pv.user_id = :uid AND pv.viewer_user_id IS NOT NULL
             ORDER BY pv.viewed_at DESC
             LIMIT :lim"
        );
        $stmt->bindValue('uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getDailyStats(int $userId, int $days = 30): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT DATE(viewed_at) as day, page_type, COUNT(*) as views
             FROM page_views
             WHERE user_id = :uid AND viewed_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
             GROUP BY day, page_type
             ORDER BY day ASC"
        );
        $stmt->bindValue('uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
