<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

class SyncLogger
{
    private int $logId;
    private float $startTime;

    public function __construct(string $type, string $triggeredBy = 'cron')
    {
        $db = Database::getConnection();
        $this->startTime = microtime(true);

        $stmt = $db->prepare(
            "INSERT INTO sync_logs (type, status, triggered_by, started_at) VALUES (:type, 'running', :by, NOW())"
        );
        $stmt->execute(['type' => $type, 'by' => $triggeredBy]);
        $this->logId = (int)$db->lastInsertId();
    }

    public function success(string $message, array $details = []): void
    {
        $this->finish('success', $message, $details);
    }

    public function fail(string $message, array $details = []): void
    {
        $this->finish('failed', $message, $details);
    }

    private function finish(string $status, string $message, array $details): void
    {
        $db = Database::getConnection();
        $duration = (int)((microtime(true) - $this->startTime) * 1000);

        $stmt = $db->prepare(
            "UPDATE sync_logs SET status = :status, message = :msg, details = :details,
             finished_at = NOW(), duration_ms = :dur WHERE id = :id"
        );
        $stmt->execute([
            'status' => $status,
            'msg' => $message,
            'details' => json_encode($details),
            'dur' => $duration,
            'id' => $this->logId,
        ]);
    }

    public static function getRecent(int $limit = 50): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "SELECT * FROM sync_logs ORDER BY id DESC LIMIT :lim"
        );
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
