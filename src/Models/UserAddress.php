<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class UserAddress
{
    public static function getByUser(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function getDefault(int $userId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM user_addresses WHERE user_id = :user_id AND is_default = 1 LIMIT 1');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function findById(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM user_addresses WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getConnection();

        // If this is the first address or set as default, unset other defaults
        if (!empty($data['is_default'])) {
            $stmt = $db->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id');
            $stmt->execute(['user_id' => $data['user_id']]);
        }

        $stmt = $db->prepare(
            'INSERT INTO user_addresses (user_id, label, full_name, address_line1, address_line2, city, state, postal_code, country, phone, is_default, created_at, updated_at)
             VALUES (:user_id, :label, :full_name, :address_line1, :address_line2, :city, :state, :postal_code, :country, :phone, :is_default, NOW(), NOW())'
        );
        $stmt->execute([
            'user_id' => $data['user_id'],
            'label' => $data['label'] ?? null,
            'full_name' => $data['full_name'],
            'address_line1' => $data['address_line1'],
            'address_line2' => $data['address_line2'] ?? null,
            'city' => $data['city'],
            'state' => $data['state'] ?? null,
            'postal_code' => $data['postal_code'],
            'country' => $data['country'],
            'phone' => $data['phone'] ?? null,
            'is_default' => $data['is_default'] ?? 0,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $db = Database::getConnection();

        if (!empty($data['is_default'])) {
            $address = self::findById($id);
            if ($address) {
                $stmt = $db->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id');
                $stmt->execute(['user_id' => $address['user_id']]);
            }
        }

        $fields = [];
        $params = ['id' => $id];
        $allowed = ['label', 'full_name', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country', 'phone', 'is_default'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if (empty($fields)) {
            return;
        }

        $fields[] = 'updated_at = NOW()';
        $stmt = $db->prepare('UPDATE user_addresses SET ' . implode(', ', $fields) . ' WHERE id = :id');
        $stmt->execute($params);
    }

    public static function delete(int $id): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM user_addresses WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function setDefault(int $id, int $userId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('UPDATE user_addresses SET is_default = 0 WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);

        $stmt = $db->prepare('UPDATE user_addresses SET is_default = 1, updated_at = NOW() WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
    }
}
