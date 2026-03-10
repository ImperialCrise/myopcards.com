<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class MarketplaceListing
{
    public static function create(array $data): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO marketplace_listings (seller_id, card_id, title, description, `condition`, price, quantity, quantity_sold, shipping_from_country, shipping_cost, ships_internationally, status, images, views_count, expires_at, created_at, updated_at)
             VALUES (:seller_id, :card_id, :title, :description, :cond, :price, :quantity, 0, :shipping_from_country, :shipping_cost, :ships_internationally, :status, :images, 0, :expires_at, NOW(), NOW())'
        );
        $stmt->execute([
            'seller_id' => $data['seller_id'],
            'card_id' => $data['card_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'cond' => $data['condition'],
            'price' => $data['price'],
            'quantity' => $data['quantity'] ?? 1,
            'shipping_from_country' => $data['shipping_from_country'] ?? null,
            'shipping_cost' => $data['shipping_cost'] ?? 0.00,
            'ships_internationally' => $data['ships_internationally'] ?? 0,
            'status' => $data['status'] ?? 'active',
            'images' => is_string($data['images'] ?? null) ? $data['images'] : (isset($data['images']) ? json_encode($data['images']) : null),
            'expires_at' => $data['expires_at'] ?? null,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM marketplace_listings WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByIdWithDetails(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT ml.*, c.card_name, c.card_set_id, c.card_image_url, c.set_name, c.rarity, c.card_color, c.card_type,
                    u.username AS seller_username, u.avatar AS seller_avatar, u.custom_avatar AS seller_custom_avatar
             FROM marketplace_listings ml
             JOIN cards c ON c.id = ml.card_id
             JOIN users u ON u.id = ml.seller_id
             WHERE ml.id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function search(array $filters, int $page = 1, int $perPage = 40): array
    {
        $db = Database::getConnection();
        $where = ['ml.status = \'active\''];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(ml.title LIKE :q OR c.card_name LIKE :q2 OR c.card_set_id LIKE :q3)';
            $params['q'] = '%' . $filters['q'] . '%';
            $params['q2'] = '%' . $filters['q'] . '%';
            $params['q3'] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['set_id'])) {
            $where[] = 'c.set_id = :set_id';
            $params['set_id'] = $filters['set_id'];
        }

        if (!empty($filters['color'])) {
            $where[] = 'c.card_color = :color';
            $params['color'] = $filters['color'];
        }

        if (!empty($filters['rarity'])) {
            $where[] = 'c.rarity = :rarity';
            $params['rarity'] = $filters['rarity'];
        }

        if (!empty($filters['condition'])) {
            $where[] = 'ml.`condition` = :cond';
            $params['cond'] = $filters['condition'];
        }

        if (isset($filters['price_min'])) {
            $where[] = 'ml.price >= :price_min';
            $params['price_min'] = (float) $filters['price_min'];
        }

        if (isset($filters['price_max'])) {
            $where[] = 'ml.price <= :price_max';
            $params['price_max'] = (float) $filters['price_max'];
        }

        if (!empty($filters['seller_id'])) {
            $where[] = 'ml.seller_id = :seller_id';
            $params['seller_id'] = (int) $filters['seller_id'];
        }

        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $sortMap = [
            'price_asc' => 'ml.price ASC',
            'price_desc' => 'ml.price DESC',
            'newest' => 'ml.created_at DESC',
            'popular' => 'ml.views_count DESC',
            'ending_soon' => 'ml.expires_at ASC',
        ];
        $sortKey = $filters['sort'] ?? 'newest';
        $orderBy = $sortMap[$sortKey] ?? $sortMap['newest'];

        $countStmt = $db->prepare(
            "SELECT COUNT(*) FROM marketplace_listings ml JOIN cards c ON c.id = ml.card_id WHERE $whereClause"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT ml.*, c.card_name, c.card_set_id, c.card_image_url, c.set_name, c.rarity, c.card_color,
                       u.username AS seller_username
                FROM marketplace_listings ml
                JOIN cards c ON c.id = ml.card_id
                JOIN users u ON u.id = ml.seller_id
                WHERE $whereClause
                ORDER BY $orderBy
                LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'listings' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / max($perPage, 1)),
        ];
    }

    public static function getListingsForCard(int $cardId, string $sort = 'price_asc', int $page = 1, int $perPage = 20): array
    {
        $db = Database::getConnection();
        $offset = ($page - 1) * $perPage;

        $sortMap = [
            'price_asc' => 'ml.price ASC',
            'price_desc' => 'ml.price DESC',
            'newest' => 'ml.created_at DESC',
            'condition' => "FIELD(ml.`condition`,'NM','LP','MP','HP','DMG') ASC, ml.price ASC",
        ];
        $orderBy = $sortMap[$sort] ?? $sortMap['price_asc'];

        $countStmt = $db->prepare(
            'SELECT COUNT(*) FROM marketplace_listings ml WHERE ml.card_id = :card_id AND ml.status = \'active\' AND ml.quantity > ml.quantity_sold'
        );
        $countStmt->execute(['card_id' => $cardId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT ml.*, u.username AS seller_username, u.avatar AS seller_avatar, u.custom_avatar AS seller_custom_avatar
             FROM marketplace_listings ml
             JOIN users u ON u.id = ml.seller_id
             WHERE ml.card_id = :card_id AND ml.status = 'active' AND ml.quantity > ml.quantity_sold
             ORDER BY $orderBy
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue('card_id', $cardId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'listings' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / max($perPage, 1)),
        ];
    }

    public static function getFloorPrice(int $cardId): ?float
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT MIN(price) FROM marketplace_listings WHERE card_id = :card_id AND status = \'active\' AND quantity > quantity_sold'
        );
        $stmt->execute(['card_id' => $cardId]);
        $result = $stmt->fetchColumn();
        return $result !== false && $result !== null ? (float) $result : null;
    }

    public static function getFloorPrices(array $cardIds): array
    {
        if (empty($cardIds)) {
            return [];
        }

        $db = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($cardIds), '?'));
        $stmt = $db->prepare(
            "SELECT card_id, MIN(price) AS floor_price, COUNT(*) AS listing_count
             FROM marketplace_listings
             WHERE card_id IN ($placeholders) AND status = 'active' AND quantity > quantity_sold
             GROUP BY card_id"
        );
        $stmt->execute(array_values($cardIds));
        $rows = $stmt->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['card_id']] = [
                'floor_price' => (float) $row['floor_price'],
                'listing_count' => (int) $row['listing_count'],
            ];
        }
        return $result;
    }

    public static function getCardMarketStats(int $cardId): array
    {
        $db = Database::getConnection();

        // Active listing stats
        $stmt = $db->prepare(
            'SELECT COUNT(*) AS listing_count, MIN(price) AS floor_price, MAX(price) AS ceiling_price, AVG(price) AS avg_price
             FROM marketplace_listings
             WHERE card_id = :card_id AND status = \'active\' AND quantity > quantity_sold'
        );
        $stmt->execute(['card_id' => $cardId]);
        $activeStats = $stmt->fetch();

        // Recent sales stats
        $stmt = $db->prepare(
            'SELECT COUNT(*) AS total_sold, AVG(ml.price) AS avg_sale_price, MAX(ml.price) AS highest_sale, MIN(ml.price) AS lowest_sale
             FROM marketplace_listings ml
             WHERE ml.card_id = :card_id AND ml.status = \'sold\''
        );
        $stmt->execute(['card_id' => $cardId]);
        $salesStats = $stmt->fetch();

        return [
            'listing_count' => (int) ($activeStats['listing_count'] ?? 0),
            'floor_price' => $activeStats['floor_price'] !== null ? (float) $activeStats['floor_price'] : null,
            'ceiling_price' => $activeStats['ceiling_price'] !== null ? (float) $activeStats['ceiling_price'] : null,
            'avg_price' => $activeStats['avg_price'] !== null ? round((float) $activeStats['avg_price'], 2) : null,
            'total_sold' => (int) ($salesStats['total_sold'] ?? 0),
            'avg_sale_price' => $salesStats['avg_sale_price'] !== null ? round((float) $salesStats['avg_sale_price'], 2) : null,
            'highest_sale' => $salesStats['highest_sale'] !== null ? (float) $salesStats['highest_sale'] : null,
            'lowest_sale' => $salesStats['lowest_sale'] !== null ? (float) $salesStats['lowest_sale'] : null,
        ];
    }

    public static function getByUser(int $userId, ?string $status = null, int $page = 1, int $perPage = 20): array
    {
        $db = Database::getConnection();
        $offset = ($page - 1) * $perPage;

        $where = 'ml.seller_id = :user_id';
        $params = ['user_id' => $userId];
        if ($status !== null) {
            $where .= ' AND ml.status = :status';
            $params['status'] = $status;
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM marketplace_listings ml WHERE $where");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT ml.*, c.card_name, c.card_set_id, c.card_image_url
             FROM marketplace_listings ml
             JOIN cards c ON c.id = ml.card_id
             WHERE $where
             ORDER BY ml.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'listings' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / max($perPage, 1)),
        ];
    }

    public static function updateStatus(int $id, string $status): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE marketplace_listings SET status = :status, updated_at = NOW() WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public static function cancel(int $id, int $sellerId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'UPDATE marketplace_listings SET status = \'cancelled\', updated_at = NOW()
             WHERE id = :id AND seller_id = :seller_id AND status = \'active\''
        );
        $stmt->execute(['id' => $id, 'seller_id' => $sellerId]);
        return $stmt->rowCount() > 0;
    }

    public static function incrementViews(int $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE marketplace_listings SET views_count = views_count + 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function getPopularCards(int $limit = 20): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT c.*, COUNT(ml.id) AS listing_count, MIN(ml.price) AS floor_price
             FROM marketplace_listings ml
             JOIN cards c ON c.id = ml.card_id
             WHERE ml.status = \'active\' AND ml.quantity > ml.quantity_sold
             GROUP BY c.id
             ORDER BY listing_count DESC, ml.views_count DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getRecentSales(int $limit = 20): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT ml.*, c.card_name, c.card_set_id, c.card_image_url, c.rarity,
                    u.username AS seller_username
             FROM marketplace_listings ml
             JOIN cards c ON c.id = ml.card_id
             JOIN users u ON u.id = ml.seller_id
             WHERE ml.status = \'sold\'
             ORDER BY ml.updated_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function decrementQuantity(int $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'UPDATE marketplace_listings
             SET quantity_sold = quantity_sold + 1,
                 status = CASE WHEN quantity_sold + 1 >= quantity THEN \'sold\' ELSE status END,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }
}
