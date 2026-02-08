<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Collection
{
    public static function addCard(int $userId, int $cardId, int $quantity = 1, string $condition = 'NM', bool $isWishlist = false): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO user_cards (user_id, card_id, quantity, `condition`, is_wishlist)
             VALUES (:user_id, :card_id, :quantity, :cond, :is_wishlist)
             ON DUPLICATE KEY UPDATE quantity = quantity + :qty_add'
        );
        $stmt->execute([
            'user_id' => $userId,
            'card_id' => $cardId,
            'quantity' => $quantity,
            'cond' => $condition,
            'is_wishlist' => (int)$isWishlist,
            'qty_add' => $quantity,
        ]);
    }

    public static function removeCard(int $userId, int $cardId, string $condition = 'NM', bool $isWishlist = false): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'DELETE FROM user_cards WHERE user_id = :user_id AND card_id = :card_id AND `condition` = :cond AND is_wishlist = :is_wishlist'
        );
        $stmt->execute([
            'user_id' => $userId,
            'card_id' => $cardId,
            'cond' => $condition,
            'is_wishlist' => (int)$isWishlist,
        ]);
    }

    public static function updateQuantity(int $userId, int $cardId, int $quantity, string $condition = 'NM', bool $isWishlist = false): void
    {
        $db = Database::getConnection();
        if ($quantity <= 0) {
            self::removeCard($userId, $cardId, $condition, $isWishlist);
            return;
        }
        $stmt = $db->prepare(
            'UPDATE user_cards SET quantity = :quantity WHERE user_id = :user_id AND card_id = :card_id AND `condition` = :cond AND is_wishlist = :is_wishlist'
        );
        $stmt->execute([
            'quantity' => $quantity,
            'user_id' => $userId,
            'card_id' => $cardId,
            'cond' => $condition,
            'is_wishlist' => (int)$isWishlist,
        ]);
    }

    private const SORT_MAP = [
        'set'        => 'c.set_id ASC, c.card_set_id ASC',
        'name'       => 'c.card_name ASC',
        'name_desc'  => 'c.card_name DESC',
        'price'      => 'c.market_price DESC',
        'price_asc'  => 'c.market_price ASC',
        'rarity'     => "FIELD(c.rarity,'SEC','SP','L','SR','R','UC','C','P') ASC",
        'added'      => 'uc.added_at DESC',
        'qty'        => 'uc.quantity DESC',
    ];

    public static function getUserCollection(int $userId, bool $wishlist = false, array $filters = [], int $page = 1, int $perPage = 40): array
    {
        $db = Database::getConnection();
        $where = ['uc.user_id = :user_id', 'uc.is_wishlist = :is_wishlist'];
        $params = ['user_id' => $userId, 'is_wishlist' => (int)$wishlist];

        if (!empty($filters['set_id'])) {
            $where[] = 'c.set_id = :set_id';
            $params['set_id'] = $filters['set_id'];
        }

        if (!empty($filters['q'])) {
            $where[] = '(c.card_name LIKE :q OR c.card_set_id LIKE :q2)';
            $params['q'] = '%' . $filters['q'] . '%';
            $params['q2'] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['rarity'])) {
            $where[] = 'c.rarity = :rarity';
            $params['rarity'] = $filters['rarity'];
        }

        if (!empty($filters['color'])) {
            $where[] = 'c.card_color = :color';
            $params['color'] = $filters['color'];
        }

        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $sortKey = $filters['sort'] ?? 'set';
        $orderBy = self::SORT_MAP[$sortKey] ?? self::SORT_MAP['set'];

        $countStmt = $db->prepare("SELECT COUNT(*) FROM user_cards uc JOIN cards c ON c.id = uc.card_id WHERE $whereClause");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $valueStmt = $db->prepare(
            "SELECT COALESCE(SUM(c.market_price * uc.quantity), 0) as total_usd,
                    COALESCE(SUM(COALESCE(c.price_en, c.cardmarket_price, 0) * uc.quantity), 0) as total_eur
             FROM user_cards uc JOIN cards c ON c.id = uc.card_id WHERE $whereClause"
        );
        $valueStmt->execute($params);
        $values = $valueStmt->fetch();

        $sql = "SELECT uc.*, c.card_set_id, c.card_name, c.set_name, c.set_id, c.rarity, c.card_color,
                       c.card_type, c.card_image_url, c.market_price, c.cardmarket_price,
                       c.price_en, c.price_fr, c.price_jp, c.inventory_price, c.is_parallel
                FROM user_cards uc
                JOIN cards c ON c.id = uc.card_id
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
            'cards' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
            'total_value_usd' => (float)($values['total_usd'] ?? 0),
            'total_value_eur' => (float)($values['total_eur'] ?? 0),
        ];
    }

    public static function getUserCardIds(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT card_id, quantity FROM user_cards WHERE user_id = :user_id AND is_wishlist = 0');
        $stmt->execute(['user_id' => $userId]);
        $result = [];
        foreach ($stmt->fetchAll() as $row) {
            $result[$row['card_id']] = $row['quantity'];
        }
        return $result;
    }

    public static function getRecentAdditions(int $userId, int $limit = 10): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT uc.*, c.card_set_id, c.card_name, c.card_image_url, c.market_price, c.set_name
             FROM user_cards uc
             JOIN cards c ON c.id = uc.card_id
             WHERE uc.user_id = :user_id AND uc.is_wishlist = 0
             ORDER BY uc.added_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
