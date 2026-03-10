<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class MarketplaceDispute
{
    public static function create(array $data): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO marketplace_disputes (order_id, opened_by, reason, description, evidence, status, resolution, resolved_by, resolved_at, created_at, updated_at)
             VALUES (:order_id, :opened_by, :reason, :description, :evidence, :status, NULL, NULL, NULL, NOW(), NOW())'
        );
        $stmt->execute([
            'order_id' => $data['order_id'],
            'opened_by' => $data['opened_by'],
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'evidence' => isset($data['evidence']) ? json_encode($data['evidence']) : null,
            'status' => $data['status'] ?? 'open',
        ]);
        return (int) $db->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM marketplace_disputes WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function getForOrder(int $orderId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM marketplace_disputes WHERE order_id = :order_id ORDER BY created_at DESC LIMIT 1');
        $stmt->execute(['order_id' => $orderId]);
        return $stmt->fetch() ?: null;
    }

    public static function getOpen(int $page = 1, int $perPage = 20): array
    {
        $db = Database::getConnection();
        $offset = ($page - 1) * $perPage;

        $countStmt = $db->prepare('SELECT COUNT(*) FROM marketplace_disputes WHERE status = \'open\'');
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $stmt = $db->prepare(
            'SELECT md.*, mo.order_number, mo.total_amount,
                    buyer.username AS buyer_username, seller.username AS seller_username,
                    opener.username AS opened_by_username,
                    c.card_name, c.card_set_id
             FROM marketplace_disputes md
             JOIN marketplace_orders mo ON mo.id = md.order_id
             JOIN users buyer ON buyer.id = mo.buyer_id
             JOIN users seller ON seller.id = mo.seller_id
             JOIN users opener ON opener.id = md.opened_by
             JOIN cards c ON c.id = mo.card_id
             WHERE md.status = \'open\'
             ORDER BY md.created_at ASC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'disputes' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'total_pages' => (int) ceil($total / max($perPage, 1)),
        ];
    }

    public static function resolve(int $id, string $resolution, int $resolvedBy): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'UPDATE marketplace_disputes
             SET status = \'resolved\', resolution = :resolution, resolved_by = :resolved_by,
                 resolved_at = NOW(), updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'resolution' => $resolution,
            'resolved_by' => $resolvedBy,
            'id' => $id,
        ]);
    }
}
