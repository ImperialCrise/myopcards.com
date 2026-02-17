<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Card
{
    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM cards WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findBySetCardId(string $cardSetId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM cards WHERE card_set_id = :card_set_id');
        $stmt->execute(['card_set_id' => $cardSetId]);
        return $stmt->fetch() ?: null;
    }

    public static function search(array $filters = [], int $page = 1, int $perPage = 40): array
    {
        $db = Database::getConnection();
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['q'])) {
            $where[] = '(card_name LIKE :q OR card_set_id LIKE :q2)';
            $params['q'] = '%' . $filters['q'] . '%';
            $params['q2'] = '%' . $filters['q'] . '%';
        }

        if (!empty($filters['set_id'])) {
            $where[] = 'set_id = :set_id';
            $params['set_id'] = $filters['set_id'];
        }

        if (!empty($filters['color'])) {
            $where[] = 'card_color = :color';
            $params['color'] = $filters['color'];
        }

        if (!empty($filters['rarity'])) {
            $where[] = 'rarity = :rarity';
            $params['rarity'] = $filters['rarity'];
        }

        if (!empty($filters['type'])) {
            $where[] = 'card_type = :type';
            $params['type'] = $filters['type'];
        }

        if (isset($filters['parallel'])) {
            $where[] = 'is_parallel = :parallel';
            $params['parallel'] = (int)$filters['parallel'];
        }

        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $priceCol = \App\Core\Currency::column();
        $sortMap = [
            'set'        => 'set_id ASC, card_set_id ASC',
            'name'       => 'card_name ASC',
            'name_desc'  => 'card_name DESC',
            'price'      => "$priceCol DESC",
            'price_asc'  => "$priceCol ASC",
            'rarity'     => "FIELD(rarity,'SEC','SP','L','SR','R','UC','C','P') ASC",
            'newest'     => 'id DESC',
        ];
        $sortKey = $filters['sort'] ?? 'set';
        $orderBy = $sortMap[$sortKey] ?? $sortMap['set'];

        $countStmt = $db->prepare("SELECT COUNT(*) FROM cards WHERE $whereClause");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $sql = "SELECT * FROM cards WHERE $whereClause ORDER BY $orderBy LIMIT :limit OFFSET :offset";
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
        ];
    }

    public static function getDistinctValues(string $column): array
    {
        $db = Database::getConnection();
        $allowed = ['set_id', 'card_color', 'rarity', 'card_type'];
        if (!in_array($column, $allowed)) {
            return [];
        }
        $stmt = $db->query("SELECT DISTINCT $column FROM cards WHERE $column IS NOT NULL AND $column != '' ORDER BY $column");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function upsert(array $data): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO cards (card_set_id, card_name, set_name, set_id, rarity, card_color, card_type,
                card_power, card_cost, life, sub_types, counter_amount, attribute, card_text,
                card_image_url, market_price, inventory_price, is_parallel, last_synced_at)
             VALUES (:card_set_id, :card_name, :set_name, :set_id, :rarity, :card_color, :card_type,
                :card_power, :card_cost, :life, :sub_types, :counter_amount, :attribute, :card_text,
                :card_image_url, :market_price, :inventory_price, :is_parallel, NOW())
             ON DUPLICATE KEY UPDATE
                card_name = VALUES(card_name), set_name = VALUES(set_name),
                market_price = VALUES(market_price), inventory_price = VALUES(inventory_price),
                card_image_url = VALUES(card_image_url), last_synced_at = NOW()'
        );
        $stmt->execute([
            'card_set_id' => $data['card_set_id'],
            'card_name' => $data['card_name'],
            'set_name' => $data['set_name'],
            'set_id' => $data['set_id'],
            'rarity' => $data['rarity'] ?? '',
            'card_color' => $data['card_color'] ?? '',
            'card_type' => $data['card_type'] ?? '',
            'card_power' => $data['card_power'] ?? null,
            'card_cost' => $data['card_cost'] ?? null,
            'life' => $data['life'] ?? null,
            'sub_types' => $data['sub_types'] ?? null,
            'counter_amount' => $data['counter_amount'] ?? null,
            'attribute' => $data['attribute'] ?? null,
            'card_text' => $data['card_text'] ?? null,
            'card_image_url' => $data['card_image_url'] ?? null,
            'market_price' => $data['market_price'] ?? null,
            'inventory_price' => $data['inventory_price'] ?? null,
            'is_parallel' => $data['is_parallel'] ?? 0,
        ]);
    }

    public static function getTotalCount(): int
    {
        $db = Database::getConnection();
        return (int)$db->query('SELECT COUNT(*) FROM cards')->fetchColumn();
    }
}
