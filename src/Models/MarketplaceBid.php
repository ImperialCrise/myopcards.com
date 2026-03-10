<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class MarketplaceBid
{
    public static function create(array $data): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO marketplace_bids (listing_id, bidder_id, amount, buyer_fee, total_amount, message, status, counter_amount, expires_at, created_at, updated_at)
             VALUES (:listing_id, :bidder_id, :amount, :buyer_fee, :total_amount, :message, :status, :counter_amount, :expires_at, NOW(), NOW())'
        );
        $stmt->execute([
            'listing_id' => $data['listing_id'],
            'bidder_id' => $data['bidder_id'],
            'amount' => $data['amount'],
            'buyer_fee' => $data['buyer_fee'] ?? 0.00,
            'total_amount' => $data['total_amount'] ?? $data['amount'],
            'message' => $data['message'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'counter_amount' => $data['counter_amount'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM marketplace_bids WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByIdWithDetails(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT mb.*, ml.title AS listing_title, ml.price AS listing_price, ml.seller_id, ml.card_id,
                    c.card_name, c.card_set_id, c.card_image_url,
                    u.username AS bidder_username, u.avatar AS bidder_avatar, u.custom_avatar AS bidder_custom_avatar,
                    seller.username AS seller_username
             FROM marketplace_bids mb
             JOIN marketplace_listings ml ON ml.id = mb.listing_id
             JOIN cards c ON c.id = ml.card_id
             JOIN users u ON u.id = mb.bidder_id
             JOIN users seller ON seller.id = ml.seller_id
             WHERE mb.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function getForListing(int $listingId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT mb.*, u.username AS bidder_username, u.avatar AS bidder_avatar, u.custom_avatar AS bidder_custom_avatar
             FROM marketplace_bids mb
             JOIN users u ON u.id = mb.bidder_id
             WHERE mb.listing_id = :listing_id
             ORDER BY mb.amount DESC, mb.created_at ASC'
        );
        $stmt->execute(['listing_id' => $listingId]);
        return $stmt->fetchAll();
    }

    public static function getByBidder(int $bidderId, int $page = 1, int $perPage = 20): array
    {
        $db = Database::getConnection();
        $offset = ($page - 1) * $perPage;

        $countStmt = $db->prepare('SELECT COUNT(*) FROM marketplace_bids WHERE bidder_id = :bidder_id');
        $countStmt->execute(['bidder_id' => $bidderId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $db->prepare(
            'SELECT mb.*, ml.title AS listing_title, ml.price AS listing_price, ml.status AS listing_status,
                    c.card_name, c.card_set_id, c.card_image_url,
                    seller.username AS seller_username
             FROM marketplace_bids mb
             JOIN marketplace_listings ml ON ml.id = mb.listing_id
             JOIN cards c ON c.id = ml.card_id
             JOIN users seller ON seller.id = ml.seller_id
             WHERE mb.bidder_id = :bidder_id
             ORDER BY mb.created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('bidder_id', $bidderId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'bids' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'total_pages' => (int) ceil($total / max($perPage, 1)),
        ];
    }

    public static function getForSeller(int $sellerId, int $page = 1, int $perPage = 20): array
    {
        $db = Database::getConnection();
        $offset = ($page - 1) * $perPage;

        $countStmt = $db->prepare(
            'SELECT COUNT(*) FROM marketplace_bids mb JOIN marketplace_listings ml ON ml.id = mb.listing_id WHERE ml.seller_id = :seller_id'
        );
        $countStmt->execute(['seller_id' => $sellerId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $db->prepare(
            'SELECT mb.*, ml.title AS listing_title, ml.price AS listing_price, ml.card_id,
                    c.card_name, c.card_set_id, c.card_image_url,
                    u.username AS bidder_username, u.avatar AS bidder_avatar, u.custom_avatar AS bidder_custom_avatar
             FROM marketplace_bids mb
             JOIN marketplace_listings ml ON ml.id = mb.listing_id
             JOIN cards c ON c.id = ml.card_id
             JOIN users u ON u.id = mb.bidder_id
             WHERE ml.seller_id = :seller_id
             ORDER BY mb.created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('seller_id', $sellerId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'bids' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'total_pages' => (int) ceil($total / max($perPage, 1)),
        ];
    }

    public static function accept(int $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'UPDATE marketplace_bids SET status = \'accepted\', responded_at = NOW(), updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public static function reject(int $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'UPDATE marketplace_bids SET status = \'rejected\', responded_at = NOW(), updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public static function cancel(int $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'UPDATE marketplace_bids SET status = \'cancelled\', updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public static function rejectAllPending(int $listingId, ?int $excludeBidId = null): array
    {
        $db = Database::getConnection();

        $where = 'listing_id = :listing_id AND status = \'pending\'';
        $params = ['listing_id' => $listingId];
        if ($excludeBidId !== null) {
            $where .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeBidId;
        }

        // Get IDs before updating
        $stmt = $db->prepare("SELECT id FROM marketplace_bids WHERE $where");
        $stmt->execute($params);
        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $db->prepare(
                "UPDATE marketplace_bids SET status = 'rejected', responded_at = NOW(), updated_at = NOW() WHERE id IN ($placeholders)"
            );
            $stmt->execute($ids);
        }

        return $ids;
    }

    public static function getExpiredBids(): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT * FROM marketplace_bids WHERE status = \'pending\' AND expires_at IS NOT NULL AND expires_at < NOW()'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getHighestBid(int $listingId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT * FROM marketplace_bids WHERE listing_id = :listing_id AND status = \'pending\' ORDER BY amount DESC LIMIT 1'
        );
        $stmt->execute(['listing_id' => $listingId]);
        return $stmt->fetch() ?: null;
    }
}
