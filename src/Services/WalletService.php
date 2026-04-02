<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Wallet;

class WalletService
{
    private const BUYER_FEE_RATE = 0.05;
    private const SELLER_FEE_RATE = 0.05;

    public static function deposit(int $userId, float $amount, ?string $stripePaymentIntentId = null): bool
    {
        Wallet::ensureWallet($userId);
        return Wallet::creditAvailable(
            $userId,
            $amount,
            'deposit',
            'stripe',
            null,
            'Wallet deposit via Stripe',
            $stripePaymentIntentId
        );
    }

    public static function lockForBid(int $userId, float $bidAmount, int $bidId): bool
    {
        $fees = self::calculateFees($bidAmount);
        $totalAmount = $bidAmount + $fees['buyer_fee'];

        Wallet::ensureWallet($userId);
        return Wallet::lockFunds(
            $userId,
            $totalAmount,
            'Funds locked for bid #' . $bidId,
            $bidId
        );
    }

    public static function releaseFromBid(int $userId, float $bidAmount, int $bidId): bool
    {
        $fees = self::calculateFees($bidAmount);
        $totalAmount = $bidAmount + $fees['buyer_fee'];

        return Wallet::releaseFunds(
            $userId,
            $totalAmount,
            'Funds released from bid #' . $bidId,
            $bidId
        );
    }

    public static function executeBuyNow(int $buyerId, int $sellerId, float $itemPrice, float $shippingCost, int $orderId): bool
    {
        $fees = self::calculateFees($itemPrice);
        $buyerTotal = $itemPrice + $fees['buyer_fee'] + $shippingCost;
        $sellerAmount = $itemPrice - $fees['seller_fee'];

        Wallet::ensureWallet($buyerId);
        Wallet::ensureWallet($sellerId);

        // Debit buyer
        $debited = Wallet::debitAvailable(
            $buyerId,
            $buyerTotal,
            'purchase',
            'order',
            $orderId,
            'Purchase for order #' . $orderId
        );

        if (!$debited) {
            return false;
        }

        // Log buyer fee
        if ($fees['buyer_fee'] > 0) {
            Wallet::debitAvailable(
                $buyerId,
                0, // Already debited as part of buyerTotal
                'buyer_fee',
                'order',
                $orderId,
                'Buyer fee for order #' . $orderId
            );
        }

        // Credit seller pending (escrow)
        return Wallet::escrowFunds($buyerId, $sellerId, $sellerAmount, $orderId);
    }

    public static function executeAcceptedBid(int $buyerId, int $sellerId, float $bidAmount, float $shippingCost, int $orderId, int $bidId): bool
    {
        $fees = self::calculateFees($bidAmount);
        $sellerAmount = $bidAmount - $fees['seller_fee'];

        Wallet::ensureWallet($buyerId);
        Wallet::ensureWallet($sellerId);

        // Release locked bid funds first (total = bidAmount + buyer_fee)
        $totalLocked = $bidAmount + $fees['buyer_fee'];
        $released = Wallet::releaseFunds(
            $buyerId,
            $totalLocked,
            'Bid accepted, funds released for order processing',
            $bidId
        );

        if (!$released) {
            return false;
        }

        // Debit buyer available (bidAmount + buyer_fee + shipping)
        $buyerTotal = $bidAmount + $fees['buyer_fee'] + $shippingCost;
        $debited = Wallet::debitAvailable(
            $buyerId,
            $buyerTotal,
            'purchase',
            'order',
            $orderId,
            'Purchase from accepted bid for order #' . $orderId
        );

        if (!$debited) {
            // Re-lock if debit fails
            Wallet::lockFunds($buyerId, $totalLocked, 'Re-lock after failed order', $bidId);
            return false;
        }

        // Escrow to seller
        return Wallet::escrowFunds($buyerId, $sellerId, $sellerAmount, $orderId);
    }

    public static function releaseEscrow(int $sellerId, float $sellerAmount, int $orderId): bool
    {
        return Wallet::completePayout($sellerId, $sellerAmount, $orderId);
    }

    public static function refundBuyer(int $buyerId, float $amount, int $orderId): bool
    {
        Wallet::ensureWallet($buyerId);
        return Wallet::creditAvailable(
            $buyerId,
            $amount,
            'refund',
            'order',
            $orderId,
            'Refund for order #' . $orderId
        );
    }

    public static function calculateFees(float $amount): array
    {
        $buyerFee = round($amount * self::BUYER_FEE_RATE, 2);
        $sellerFee = round($amount * self::SELLER_FEE_RATE, 2);

        return [
            'buyer_fee' => $buyerFee,
            'seller_fee' => $sellerFee,
            'buyer_total' => round($amount + $buyerFee, 2),
            'seller_receives' => round($amount - $sellerFee, 2),
        ];
    }
}
