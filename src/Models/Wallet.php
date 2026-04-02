<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Wallet
{
    public static function findByUserId(int $userId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT * FROM wallets WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function ensureWallet(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'INSERT IGNORE INTO wallets (user_id, available_balance, reserved_balance, pending_balance, total_deposited, total_withdrawn, currency, created_at, updated_at)
             VALUES (:user_id, 0.00, 0.00, 0.00, 0.00, 0.00, \'USD\', NOW(), NOW())'
        );
        $stmt->execute(['user_id' => $userId]);

        $stmt = $db->prepare('SELECT * FROM wallets WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch();
    }

    public static function getAvailableBalance(int $userId): float
    {
        $wallet = self::findByUserId($userId);
        return $wallet ? (float) $wallet['available_balance'] : 0.00;
    }

    public static function lockFunds(int $userId, float $amount, string $reason, ?int $referenceId = null): bool
    {
        $db = Database::getConnection();
        try {
            $db->beginTransaction();

            $stmt = $db->prepare('SELECT * FROM wallets WHERE user_id = :user_id FOR UPDATE');
            $stmt->execute(['user_id' => $userId]);
            $wallet = $stmt->fetch();

            if (!$wallet || (float) $wallet['available_balance'] < $amount) {
                $db->rollBack();
                return false;
            }

            $newAvailable = (float) $wallet['available_balance'] - $amount;
            $newReserved = (float) $wallet['reserved_balance'] + $amount;

            $stmt = $db->prepare(
                'UPDATE wallets SET available_balance = :available, reserved_balance = :reserved, updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                'available' => $newAvailable,
                'reserved' => $newReserved,
                'id' => $wallet['id'],
            ]);

            self::logTransaction($db, $wallet['id'], $userId, 'bid_lock', $amount, $newAvailable, 'bid', $referenceId, $reason);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function releaseFunds(int $userId, float $amount, string $reason, ?int $referenceId = null): bool
    {
        $db = Database::getConnection();
        try {
            $db->beginTransaction();

            $stmt = $db->prepare('SELECT * FROM wallets WHERE user_id = :user_id FOR UPDATE');
            $stmt->execute(['user_id' => $userId]);
            $wallet = $stmt->fetch();

            if (!$wallet || (float) $wallet['reserved_balance'] < $amount) {
                $db->rollBack();
                return false;
            }

            $newReserved = (float) $wallet['reserved_balance'] - $amount;
            $newAvailable = (float) $wallet['available_balance'] + $amount;

            $stmt = $db->prepare(
                'UPDATE wallets SET available_balance = :available, reserved_balance = :reserved, updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                'available' => $newAvailable,
                'reserved' => $newReserved,
                'id' => $wallet['id'],
            ]);

            self::logTransaction($db, $wallet['id'], $userId, 'bid_release', $amount, $newAvailable, 'bid', $referenceId, $reason);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function escrowFunds(int $buyerId, int $sellerId, float $amount, int $orderId): bool
    {
        $db = Database::getConnection();
        try {
            $db->beginTransaction();

            // Lock buyer wallet
            $stmt = $db->prepare('SELECT * FROM wallets WHERE user_id = :user_id FOR UPDATE');
            $stmt->execute(['user_id' => $buyerId]);
            $buyerWallet = $stmt->fetch();

            if (!$buyerWallet) {
                $db->rollBack();
                return false;
            }

            // Try reserved first, then available
            $fromReserved = false;
            if ((float) $buyerWallet['reserved_balance'] >= $amount) {
                $newReserved = (float) $buyerWallet['reserved_balance'] - $amount;
                $stmt = $db->prepare(
                    'UPDATE wallets SET reserved_balance = :reserved, updated_at = NOW() WHERE id = :id'
                );
                $stmt->execute(['reserved' => $newReserved, 'id' => $buyerWallet['id']]);
                $balanceAfter = (float) $buyerWallet['available_balance'];
                $fromReserved = true;
            } elseif ((float) $buyerWallet['available_balance'] >= $amount) {
                $newAvailable = (float) $buyerWallet['available_balance'] - $amount;
                $stmt = $db->prepare(
                    'UPDATE wallets SET available_balance = :available, updated_at = NOW() WHERE id = :id'
                );
                $stmt->execute(['available' => $newAvailable, 'id' => $buyerWallet['id']]);
                $balanceAfter = $newAvailable;
            } else {
                $db->rollBack();
                return false;
            }

            self::logTransaction($db, $buyerWallet['id'], $buyerId, 'escrow_lock', $amount, $balanceAfter, 'order', $orderId, 'Funds locked in escrow for order #' . $orderId);

            // Lock seller wallet and credit pending
            $stmt = $db->prepare('SELECT * FROM wallets WHERE user_id = :user_id FOR UPDATE');
            $stmt->execute(['user_id' => $sellerId]);
            $sellerWallet = $stmt->fetch();

            if (!$sellerWallet) {
                // Auto-create seller wallet
                self::ensureWallet($sellerId);
                $stmt = $db->prepare('SELECT * FROM wallets WHERE user_id = :user_id FOR UPDATE');
                $stmt->execute(['user_id' => $sellerId]);
                $sellerWallet = $stmt->fetch();
            }

            $newPending = (float) $sellerWallet['pending_balance'] + $amount;
            $stmt = $db->prepare(
                'UPDATE wallets SET pending_balance = :pending, updated_at = NOW() WHERE id = :id'
            );
            $stmt->execute(['pending' => $newPending, 'id' => $sellerWallet['id']]);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function completePayout(int $sellerId, float $amount, int $orderId): bool
    {
        $db = Database::getConnection();
        try {
            $db->beginTransaction();

            $stmt = $db->prepare('SELECT * FROM wallets WHERE user_id = :user_id FOR UPDATE');
            $stmt->execute(['user_id' => $sellerId]);
            $wallet = $stmt->fetch();

            if (!$wallet || (float) $wallet['pending_balance'] < $amount) {
                $db->rollBack();
                return false;
            }

            $newPending = (float) $wallet['pending_balance'] - $amount;
            $newAvailable = (float) $wallet['available_balance'] + $amount;

            $stmt = $db->prepare(
                'UPDATE wallets SET available_balance = :available, pending_balance = :pending, updated_at = NOW()
                 WHERE id = :id'
            );
            $stmt->execute([
                'available' => $newAvailable,
                'pending' => $newPending,
                'id' => $wallet['id'],
            ]);

            self::logTransaction($db, $wallet['id'], $sellerId, 'escrow_release', $amount, $newAvailable, 'order', $orderId, 'Escrow released for order #' . $orderId);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function debitAvailable(int $userId, float $amount, string $type, ?string $referenceType = null, ?int $referenceId = null, ?string $description = null): bool
    {
        $db = Database::getConnection();
        try {
            $db->beginTransaction();

            $stmt = $db->prepare('SELECT * FROM wallets WHERE user_id = :user_id FOR UPDATE');
            $stmt->execute(['user_id' => $userId]);
            $wallet = $stmt->fetch();

            if (!$wallet || (float) $wallet['available_balance'] < $amount) {
                $db->rollBack();
                return false;
            }

            $newAvailable = (float) $wallet['available_balance'] - $amount;

            $stmt = $db->prepare(
                'UPDATE wallets SET available_balance = :available, updated_at = NOW() WHERE id = :id'
            );
            $stmt->execute(['available' => $newAvailable, 'id' => $wallet['id']]);

            self::logTransaction($db, $wallet['id'], $userId, $type, $amount, $newAvailable, $referenceType, $referenceId, $description);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function creditAvailable(int $userId, float $amount, string $type, ?string $referenceType = null, ?int $referenceId = null, ?string $description = null, ?string $stripePaymentIntentId = null): bool
    {
        $db = Database::getConnection();
        try {
            $db->beginTransaction();

            $stmt = $db->prepare('SELECT * FROM wallets WHERE user_id = :user_id FOR UPDATE');
            $stmt->execute(['user_id' => $userId]);
            $wallet = $stmt->fetch();

            if (!$wallet) {
                $db->rollBack();
                return false;
            }

            $newAvailable = (float) $wallet['available_balance'] + $amount;

            $updateFields = ['available_balance = :available', 'updated_at = NOW()'];
            $updateParams = ['available' => $newAvailable, 'id' => $wallet['id']];

            if ($type === 'deposit') {
                $updateFields[] = 'total_deposited = total_deposited + :deposited';
                $updateParams['deposited'] = $amount;
            }

            $stmt = $db->prepare(
                'UPDATE wallets SET ' . implode(', ', $updateFields) . ' WHERE id = :id'
            );
            $stmt->execute($updateParams);

            self::logTransaction($db, $wallet['id'], $userId, $type, $amount, $newAvailable, $referenceType, $referenceId, $description, $stripePaymentIntentId);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public static function getTransactionHistory(int $userId, int $page = 1, int $perPage = 20): array
    {
        $db = Database::getConnection();
        $offset = ($page - 1) * $perPage;

        $countStmt = $db->prepare('SELECT COUNT(*) FROM wallet_transactions WHERE user_id = :user_id');
        $countStmt->execute(['user_id' => $userId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $db->prepare(
            'SELECT * FROM wallet_transactions WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'transactions' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    private static function logTransaction(
        PDO $db,
        int $walletId,
        int $userId,
        string $type,
        float $amount,
        float $balanceAfter,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $description = null,
        ?string $stripePaymentIntentId = null,
        ?string $metadata = null
    ): void {
        $stmt = $db->prepare(
            'INSERT INTO wallet_transactions (wallet_id, user_id, type, amount, balance_after, reference_type, reference_id, description, stripe_payment_intent_id, metadata, created_at)
             VALUES (:wallet_id, :user_id, :type, :amount, :balance_after, :reference_type, :reference_id, :description, :stripe_payment_intent_id, :metadata, NOW())'
        );
        $stmt->execute([
            'wallet_id' => $walletId,
            'user_id' => $userId,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description,
            'stripe_payment_intent_id' => $stripePaymentIntentId,
            'metadata' => $metadata,
        ]);
    }
}
