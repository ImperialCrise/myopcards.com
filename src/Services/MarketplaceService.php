<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MarketplaceBid;
use App\Models\MarketplaceDispute;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceReview;
use App\Models\Wallet;

class MarketplaceService
{
    public static function createListing(array $data): int
    {
        return MarketplaceListing::create($data);
    }

    public static function buyNow(int $buyerId, int $listingId, ?int $shippingAddressId = null): array
    {
        $listing = MarketplaceListing::findByIdWithDetails($listingId);
        if (!$listing) {
            return ['success' => false, 'error' => 'Listing not found.'];
        }
        if ($listing['status'] !== 'active') {
            return ['success' => false, 'error' => 'Listing is no longer active.'];
        }
        if ((int) $listing['seller_id'] === $buyerId) {
            return ['success' => false, 'error' => 'You cannot buy your own listing.'];
        }
        if ((int) $listing['quantity_sold'] >= (int) $listing['quantity']) {
            return ['success' => false, 'error' => 'This listing is sold out.'];
        }

        $itemPrice = (float) $listing['price'];
        $shippingCost = (float) $listing['shipping_cost'];
        $fees = WalletService::calculateFees($itemPrice);
        $buyerTotal = $itemPrice + $fees['buyer_fee'] + $shippingCost;
        $sellerAmount = $itemPrice - $fees['seller_fee'];

        // Check buyer balance
        Wallet::ensureWallet($buyerId);
        $balance = Wallet::getAvailableBalance($buyerId);
        if ($balance < $buyerTotal) {
            return ['success' => false, 'error' => 'Insufficient balance. You need $' . number_format($buyerTotal, 2) . ' but have $' . number_format($balance, 2) . '.'];
        }

        // Create order
        $orderId = MarketplaceOrder::create([
            'listing_id' => $listingId,
            'buyer_id' => $buyerId,
            'seller_id' => (int) $listing['seller_id'],
            'card_id' => (int) $listing['card_id'],
            'item_price' => $itemPrice,
            'buyer_fee' => $fees['buyer_fee'],
            'seller_fee' => $fees['seller_fee'],
            'shipping_cost' => $shippingCost,
            'total_paid' => $buyerTotal,
            'seller_payout' => $sellerAmount,
            'condition' => $listing['condition'],
            'shipping_address_id' => $shippingAddressId,
            'shipping_from_country' => $listing['shipping_from_country'],
            'escrow_status' => 'paid',
        ]);

        // Execute payment
        $paid = WalletService::executeBuyNow($buyerId, (int) $listing['seller_id'], $itemPrice, $shippingCost, $orderId);
        if (!$paid) {
            return ['success' => false, 'error' => 'Payment failed. Please try again.'];
        }

        // Update listing
        MarketplaceListing::decrementQuantity($listingId);

        // Reject all pending bids on this listing
        $rejectedBidIds = MarketplaceBid::rejectAllPending($listingId);

        // Release locked funds for rejected bids
        foreach ($rejectedBidIds as $rejectedBidId) {
            $bid = MarketplaceBid::findById((int) $rejectedBidId);
            if ($bid) {
                WalletService::releaseFromBid((int) $bid['bidder_id'], (float) $bid['amount'], (int) $bid['id']);
            }
        }

        return ['success' => true, 'order_id' => $orderId];
    }

    public static function placeBid(int $bidderId, int $listingId, float $amount): array
    {
        $listing = MarketplaceListing::findById($listingId);
        if (!$listing) {
            return ['success' => false, 'error' => 'Listing not found.'];
        }
        if ($listing['status'] !== 'active') {
            return ['success' => false, 'error' => 'Listing is no longer active.'];
        }
        if ((int) $listing['seller_id'] === $bidderId) {
            return ['success' => false, 'error' => 'You cannot bid on your own listing.'];
        }

        $fees = WalletService::calculateFees($amount);
        $totalAmount = $amount + $fees['buyer_fee'];

        // Ensure wallet and check balance
        Wallet::ensureWallet($bidderId);
        $balance = Wallet::getAvailableBalance($bidderId);
        if ($balance < $totalAmount) {
            return ['success' => false, 'error' => 'Insufficient balance to cover bid and fees.'];
        }

        // Create bid
        $bidId = MarketplaceBid::create([
            'listing_id' => $listingId,
            'bidder_id' => $bidderId,
            'amount' => $amount,
            'buyer_fee' => $fees['buyer_fee'],
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'expires_at' => date('Y-m-d H:i:s', strtotime('+3 days')),
        ]);

        // Lock funds
        $locked = WalletService::lockForBid($bidderId, $amount, $bidId);
        if (!$locked) {
            MarketplaceBid::cancel($bidId);
            return ['success' => false, 'error' => 'Failed to lock funds for bid.'];
        }

        return ['success' => true, 'bid_id' => $bidId];
    }

    public static function acceptBid(int $sellerId, int $bidId, ?int $shippingAddressId = null): array
    {
        $bid = MarketplaceBid::findByIdWithDetails($bidId);
        if (!$bid) {
            return ['success' => false, 'error' => 'Bid not found.'];
        }
        if ((int) $bid['seller_id'] !== $sellerId) {
            return ['success' => false, 'error' => 'You are not the seller of this listing.'];
        }
        if ($bid['status'] !== 'pending') {
            return ['success' => false, 'error' => 'This bid is no longer pending.'];
        }

        $bidAmount = (float) $bid['amount'];
        $listing = MarketplaceListing::findById((int) $bid['listing_id']);
        $shippingCost = $listing ? (float) $listing['shipping_cost'] : 0.00;
        $fees = WalletService::calculateFees($bidAmount);
        $buyerTotal = $bidAmount + $fees['buyer_fee'] + $shippingCost;
        $sellerAmount = $bidAmount - $fees['seller_fee'];

        // Create order
        $orderId = MarketplaceOrder::create([
            'listing_id' => (int) $bid['listing_id'],
            'bid_id' => $bidId,
            'buyer_id' => (int) $bid['bidder_id'],
            'seller_id' => $sellerId,
            'card_id' => (int) $bid['card_id'],
            'item_price' => $bidAmount,
            'buyer_fee' => $fees['buyer_fee'],
            'seller_fee' => $fees['seller_fee'],
            'shipping_cost' => $shippingCost,
            'total_paid' => $buyerTotal,
            'seller_payout' => $sellerAmount,
            'condition' => $listing['condition'] ?? null,
            'shipping_address_id' => $shippingAddressId,
            'shipping_from_country' => $listing['shipping_from_country'] ?? null,
            'escrow_status' => 'paid',
        ]);

        // Execute payment from locked funds
        $paid = WalletService::executeAcceptedBid(
            (int) $bid['bidder_id'],
            $sellerId,
            $bidAmount,
            $shippingCost,
            $orderId,
            $bidId
        );

        if (!$paid) {
            return ['success' => false, 'error' => 'Payment processing failed.'];
        }

        // Mark bid as accepted
        MarketplaceBid::accept($bidId);

        // Decrement listing quantity
        MarketplaceListing::decrementQuantity((int) $bid['listing_id']);

        // Reject all other pending bids and release their funds
        $rejectedBidIds = MarketplaceBid::rejectAllPending((int) $bid['listing_id'], $bidId);
        foreach ($rejectedBidIds as $rejectedBidId) {
            $rejectedBid = MarketplaceBid::findById((int) $rejectedBidId);
            if ($rejectedBid) {
                WalletService::releaseFromBid((int) $rejectedBid['bidder_id'], (float) $rejectedBid['amount'], (int) $rejectedBid['id']);
            }
        }

        return ['success' => true, 'order_id' => $orderId];
    }

    public static function rejectBid(int $sellerId, int $bidId): array
    {
        $bid = MarketplaceBid::findByIdWithDetails($bidId);
        if (!$bid) {
            return ['success' => false, 'error' => 'Bid not found.'];
        }
        if ((int) $bid['seller_id'] !== $sellerId) {
            return ['success' => false, 'error' => 'You are not the seller of this listing.'];
        }
        if ($bid['status'] !== 'pending') {
            return ['success' => false, 'error' => 'This bid is no longer pending.'];
        }

        MarketplaceBid::reject($bidId);
        WalletService::releaseFromBid((int) $bid['bidder_id'], (float) $bid['amount'], $bidId);

        return ['success' => true];
    }

    public static function cancelBid(int $bidderId, int $bidId): array
    {
        $bid = MarketplaceBid::findById($bidId);
        if (!$bid) {
            return ['success' => false, 'error' => 'Bid not found.'];
        }
        if ((int) $bid['bidder_id'] !== $bidderId) {
            return ['success' => false, 'error' => 'You are not the bidder.'];
        }
        if ($bid['status'] !== 'pending') {
            return ['success' => false, 'error' => 'This bid cannot be cancelled.'];
        }

        MarketplaceBid::cancel($bidId);
        WalletService::releaseFromBid($bidderId, (float) $bid['amount'], $bidId);

        return ['success' => true];
    }

    public static function cancelListing(int $sellerId, int $listingId): array
    {
        $listing = MarketplaceListing::findById($listingId);
        if (!$listing) {
            return ['success' => false, 'error' => 'Listing not found.'];
        }
        if ((int) $listing['seller_id'] !== $sellerId) {
            return ['success' => false, 'error' => 'You are not the seller.'];
        }
        if ($listing['status'] !== 'active') {
            return ['success' => false, 'error' => 'Listing is not active.'];
        }

        // Cancel listing
        $cancelled = MarketplaceListing::cancel($listingId, $sellerId);
        if (!$cancelled) {
            return ['success' => false, 'error' => 'Failed to cancel listing.'];
        }

        // Reject all pending bids and release funds
        $rejectedBidIds = MarketplaceBid::rejectAllPending($listingId);
        foreach ($rejectedBidIds as $rejectedBidId) {
            $bid = MarketplaceBid::findById((int) $rejectedBidId);
            if ($bid) {
                WalletService::releaseFromBid((int) $bid['bidder_id'], (float) $bid['amount'], (int) $bid['id']);
            }
        }

        return ['success' => true];
    }

    public static function markShipped(int $sellerId, int $orderId, ?string $trackingNumber = null, ?string $carrier = null): array
    {
        $order = MarketplaceOrder::findById($orderId);
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found.'];
        }
        if ((int) $order['seller_id'] !== $sellerId) {
            return ['success' => false, 'error' => 'You are not the seller.'];
        }
        if (!in_array($order['escrow_status'], ['paid', 'pending_payment'], true)) {
            return ['success' => false, 'error' => 'Order cannot be marked as shipped in its current state.'];
        }

        MarketplaceOrder::markShipped($orderId, $trackingNumber, $carrier);

        return ['success' => true];
    }

    public static function confirmDelivery(int $buyerId, int $orderId): array
    {
        $order = MarketplaceOrder::findById($orderId);
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found.'];
        }
        if ((int) $order['buyer_id'] !== $buyerId) {
            return ['success' => false, 'error' => 'You are not the buyer.'];
        }
        if ($order['escrow_status'] !== 'shipped') {
            return ['success' => false, 'error' => 'Order has not been shipped yet.'];
        }

        MarketplaceOrder::markDelivered($orderId);

        // Release escrow to seller
        $released = WalletService::releaseEscrow((int) $order['seller_id'], (float) $order['seller_payout'], $orderId);
        if ($released) {
            MarketplaceOrder::complete($orderId);
        }

        return ['success' => true];
    }

    public static function openDispute(int $openedBy, int $orderId, string $reason, string $description): array
    {
        $order = MarketplaceOrder::findById($orderId);
        if (!$order) {
            return ['success' => false, 'error' => 'Order not found.'];
        }

        // Verify the opener is buyer or seller
        if ($openedBy !== (int) $order['buyer_id'] && $openedBy !== (int) $order['seller_id']) {
            return ['success' => false, 'error' => 'You are not part of this order.'];
        }

        if (!in_array($order['escrow_status'], ['shipped', 'delivered'], true)) {
            return ['success' => false, 'error' => 'Disputes can only be opened for shipped or delivered orders.'];
        }

        // Check if dispute already exists
        $existing = MarketplaceDispute::getForOrder($orderId);
        if ($existing && $existing['status'] === 'open') {
            return ['success' => false, 'error' => 'A dispute is already open for this order.'];
        }

        $disputeId = MarketplaceDispute::create([
            'order_id' => $orderId,
            'opened_by' => $openedBy,
            'reason' => $reason,
            'description' => $description,
        ]);

        // Update order escrow status
        MarketplaceOrder::updateEscrowStatus($orderId, 'disputed');

        return ['success' => true, 'dispute_id' => $disputeId];
    }

    public static function resolveDispute(int $disputeId, string $resolution, int $resolvedBy, string $winner): array
    {
        $dispute = MarketplaceDispute::findById($disputeId);
        if (!$dispute) {
            return ['success' => false, 'error' => 'Dispute not found.'];
        }
        if ($dispute['status'] !== 'open') {
            return ['success' => false, 'error' => 'Dispute is already resolved.'];
        }

        $order = MarketplaceOrder::findById((int) $dispute['order_id']);
        if (!$order) {
            return ['success' => false, 'error' => 'Associated order not found.'];
        }

        MarketplaceDispute::resolve($disputeId, $resolution, $resolvedBy);

        if ($winner === 'buyer') {
            // Refund the buyer
            WalletService::refundBuyer((int) $order['buyer_id'], (float) $order['total_paid'], (int) $order['id']);
            MarketplaceOrder::updateEscrowStatus((int) $order['id'], 'refunded');
        } elseif ($winner === 'seller') {
            // Release escrow to seller
            WalletService::releaseEscrow((int) $order['seller_id'], (float) $order['seller_payout'], (int) $order['id']);
            MarketplaceOrder::complete((int) $order['id']);
        }

        return ['success' => true];
    }

    public static function autoCompleteOrders(int $daysSinceDelivery = 3): int
    {
        $orders = MarketplaceOrder::getOrdersForAutoComplete($daysSinceDelivery);
        $completed = 0;

        foreach ($orders as $order) {
            $released = WalletService::releaseEscrow((int) $order['seller_id'], (float) $order['seller_payout'], (int) $order['id']);
            if ($released) {
                MarketplaceOrder::complete((int) $order['id']);
                $completed++;
            }
        }

        return $completed;
    }

    public static function expireBids(): int
    {
        $expiredBids = MarketplaceBid::getExpiredBids();
        $count = 0;

        foreach ($expiredBids as $bid) {
            MarketplaceBid::cancel((int) $bid['id']);
            WalletService::releaseFromBid((int) $bid['bidder_id'], (float) $bid['amount'], (int) $bid['id']);
            $count++;
        }

        return $count;
    }

    public static function expireListings(): int
    {
        $db = \App\Core\Database::getConnection();
        $stmt = $db->prepare(
            'SELECT id, seller_id FROM marketplace_listings
             WHERE status = \'active\' AND expires_at IS NOT NULL AND expires_at < NOW()'
        );
        $stmt->execute();
        $expired = $stmt->fetchAll();
        $count = 0;

        foreach ($expired as $listing) {
            MarketplaceListing::updateStatus((int) $listing['id'], 'expired');

            // Release funds for all pending bids
            $rejectedBidIds = MarketplaceBid::rejectAllPending((int) $listing['id']);
            foreach ($rejectedBidIds as $rejectedBidId) {
                $bid = MarketplaceBid::findById((int) $rejectedBidId);
                if ($bid) {
                    WalletService::releaseFromBid((int) $bid['bidder_id'], (float) $bid['amount'], (int) $bid['id']);
                }
            }

            $count++;
        }

        return $count;
    }
}
