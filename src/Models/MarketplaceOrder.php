<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class MarketplaceOrder
{
    public static function create(array $data): int
    {
        $db = Database::getConnection();
        $orderNumber = self::generateOrderNumber();
        $itemPrice = (float) $data['item_price'];
        $buyerFee = (float) ($data['buyer_fee'] ?? 0);
        $sellerFee = (float) ($data['seller_fee'] ?? 0);
        $shippingCost = (float) ($data['shipping_cost'] ?? 0);
        $subtotal = $itemPrice + $shippingCost;
        $totalPaid = (float) ($data['total_paid'] ?? ($itemPrice + $buyerFee + $shippingCost));
        $sellerPayout = (float) ($data['seller_payout'] ?? ($itemPrice + $shippingCost - $sellerFee));
        $platformRevenue = $buyerFee + $sellerFee;

        $stmt = $db->prepare(
            'INSERT INTO marketplace_orders (order_number, listing_id, bid_id, buyer_id, seller_id, card_id,
                quantity, item_price, shipping_cost, buyer_fee, seller_fee, subtotal, total_paid, seller_payout, platform_revenue,
                escrow_status, notes, created_at, updated_at)
             VALUES (:order_number, :listing_id, :bid_id, :buyer_id, :seller_id, :card_id,
                :quantity, :item_price, :shipping_cost, :buyer_fee, :seller_fee, :subtotal, :total_paid, :seller_payout, :platform_revenue,
                :escrow_status, :notes, NOW(), NOW())'
        );
        $stmt->execute([
            'order_number' => $orderNumber,
            'listing_id' => $data['listing_id'],
            'bid_id' => $data['bid_id'] ?? null,
            'buyer_id' => $data['buyer_id'],
            'seller_id' => $data['seller_id'],
            'card_id' => $data['card_id'],
            'quantity' => $data['quantity'] ?? 1,
            'item_price' => $itemPrice,
            'shipping_cost' => $shippingCost,
            'buyer_fee' => $buyerFee,
            'seller_fee' => $sellerFee,
            'subtotal' => $subtotal,
            'total_paid' => $totalPaid,
            'seller_payout' => $sellerPayout,
            'platform_revenue' => $platformRevenue,
            'escrow_status' => $data['escrow_status'] ?? 'pending_payment',
            'notes' => $data['notes'] ?? null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function generateOrderNumber(): string
    {
        return 'ORD-' . strtoupper(bin2hex(random_bytes(4))) . '-' . time();
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM marketplace_orders WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByOrderNumber(string $orderNumber): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM marketplace_orders WHERE order_number = :order_number');
        $stmt->execute(['order_number' => $orderNumber]);
        return $stmt->fetch() ?: null;
    }

    public static function findByIdWithDetails(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT mo.*, ml.title AS listing_title, ml.`condition` AS listing_condition, ml.images AS listing_images,
                    c.card_name, c.card_set_id, c.card_image_url, c.set_name, c.rarity,
                    buyer.username AS buyer_username, buyer.avatar AS buyer_avatar, buyer.custom_avatar AS buyer_custom_avatar,
                    seller.username AS seller_username, seller.avatar AS seller_avatar, seller.custom_avatar AS seller_custom_avatar
             FROM marketplace_orders mo
             JOIN marketplace_listings ml ON ml.id = mo.listing_id
             JOIN cards c ON c.id = mo.card_id
             JOIN users buyer ON buyer.id = mo.buyer_id
             JOIN users seller ON seller.id = mo.seller_id
             WHERE mo.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function getByBuyer(int $buyerId, int $page = 1, int $perPage = 20): array
    {
        $db = Database::getConnection();
        $offset = ($page - 1) * $perPage;

        $countStmt = $db->prepare('SELECT COUNT(*) FROM marketplace_orders WHERE buyer_id = :buyer_id');
        $countStmt->execute(['buyer_id' => $buyerId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $db->prepare(
            'SELECT mo.*, c.card_name, c.card_set_id, c.card_image_url,
                    seller.username AS seller_username
             FROM marketplace_orders mo
             JOIN cards c ON c.id = mo.card_id
             JOIN users seller ON seller.id = mo.seller_id
             WHERE mo.buyer_id = :buyer_id
             ORDER BY mo.created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('buyer_id', $buyerId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'orders' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'total_pages' => (int) ceil($total / max($perPage, 1)),
        ];
    }

    public static function getBySeller(int $sellerId, int $page = 1, int $perPage = 20): array
    {
        $db = Database::getConnection();
        $offset = ($page - 1) * $perPage;

        $countStmt = $db->prepare('SELECT COUNT(*) FROM marketplace_orders WHERE seller_id = :seller_id');
        $countStmt->execute(['seller_id' => $sellerId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $db->prepare(
            'SELECT mo.*, c.card_name, c.card_set_id, c.card_image_url,
                    buyer.username AS buyer_username
             FROM marketplace_orders mo
             JOIN cards c ON c.id = mo.card_id
             JOIN users buyer ON buyer.id = mo.buyer_id
             WHERE mo.seller_id = :seller_id
             ORDER BY mo.created_at DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('seller_id', $sellerId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'orders' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'total_pages' => (int) ceil($total / max($perPage, 1)),
        ];
    }

    public static function updateEscrowStatus(int $id, string $escrowStatus): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'UPDATE marketplace_orders SET escrow_status = :escrow_status, updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['escrow_status' => $escrowStatus, 'id' => $id]);
    }

    public static function markShipped(int $id, ?string $trackingNumber = null, ?string $carrier = null): void
    {
        $db = Database::getConnection();
        $autoCompleteAt = date('Y-m-d H:i:s', strtotime('+14 days'));
        $stmt = $db->prepare(
            'UPDATE marketplace_orders
             SET escrow_status = \'shipped\', shipping_tracking_number = :tracking_number, shipping_carrier = :carrier,
                 shipped_at = NOW(), auto_complete_at = :auto_complete, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'tracking_number' => $trackingNumber,
            'carrier' => $carrier,
            'auto_complete' => $autoCompleteAt,
            'id' => $id,
        ]);
    }

    public static function markDelivered(int $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'UPDATE marketplace_orders SET escrow_status = \'delivered\', delivered_at = NOW(), updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public static function complete(int $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'UPDATE marketplace_orders SET escrow_status = \'completed\', completed_at = NOW(), updated_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public static function getOrdersForAutoComplete(int $daysSinceDelivery = 14): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT * FROM marketplace_orders
             WHERE escrow_status = \'shipped\'
               AND auto_complete_at IS NOT NULL
               AND auto_complete_at <= NOW()'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getSalesStats(int $sellerId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT
                COUNT(*) AS total_orders,
                SUM(CASE WHEN escrow_status = \'completed\' THEN 1 ELSE 0 END) AS completed_orders,
                SUM(CASE WHEN escrow_status = \'completed\' THEN seller_payout ELSE 0 END) AS total_revenue,
                AVG(CASE WHEN escrow_status = \'completed\' THEN seller_payout ELSE NULL END) AS avg_order_value
             FROM marketplace_orders
             WHERE seller_id = :seller_id'
        );
        $stmt->execute(['seller_id' => $sellerId]);
        $row = $stmt->fetch();

        return [
            'total_orders' => (int) ($row['total_orders'] ?? 0),
            'completed_orders' => (int) ($row['completed_orders'] ?? 0),
            'total_revenue' => round((float) ($row['total_revenue'] ?? 0), 2),
            'avg_order_value' => $row['avg_order_value'] !== null ? round((float) $row['avg_order_value'], 2) : 0.00,
        ];
    }

    public static function getRecentCompleted(int $limit = 20): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT mo.*, c.card_name, c.card_set_id, c.card_image_url,
                    buyer.username AS buyer_username, seller.username AS seller_username
             FROM marketplace_orders mo
             JOIN cards c ON c.id = mo.card_id
             JOIN users buyer ON buyer.id = mo.buyer_id
             JOIN users seller ON seller.id = mo.seller_id
             WHERE mo.escrow_status = \'completed\'
             ORDER BY mo.completed_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
