<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class StripeAccount
{
    public static function findByUserId(int $userId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM stripe_accounts WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function createOrUpdate(int $userId, ?string $stripeCustomerId = null): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT INTO stripe_accounts (user_id, stripe_customer_id, metadata, created_at, updated_at)
             VALUES (:user_id, :stripe_customer_id, NULL, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                stripe_customer_id = COALESCE(:stripe_customer_id2, stripe_customer_id),
                updated_at = NOW()'
        );
        $stmt->execute([
            'user_id' => $userId,
            'stripe_customer_id' => $stripeCustomerId,
            'stripe_customer_id2' => $stripeCustomerId,
        ]);
    }

    public static function updateCustomerId(int $userId, string $customerId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'UPDATE stripe_accounts SET stripe_customer_id = :customer_id, updated_at = NOW() WHERE user_id = :user_id'
        );
        $stmt->execute(['customer_id' => $customerId, 'user_id' => $userId]);
    }
}
