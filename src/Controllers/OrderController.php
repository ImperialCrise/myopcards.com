<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use PDO;

class OrderController
{
    public function index(): void
    {
        Auth::requireAuth();
        $db = Database::getConnection();
        $userId = Auth::id();

        // Orders as buyer
        $buyerOrders = $db->prepare(
            "SELECT mo.*, c.card_name, c.card_image_url, c.card_set_id, c.rarity,
                    seller.username as seller_username
             FROM marketplace_orders mo
             JOIN marketplace_listings ml ON ml.id = mo.listing_id
             JOIN cards c ON c.id = ml.card_id
             JOIN users seller ON seller.id = mo.seller_id
             WHERE mo.buyer_id = :uid
             ORDER BY mo.created_at DESC"
        );
        $buyerOrders->execute(['uid' => $userId]);
        $buyerOrders = $buyerOrders->fetchAll();

        // Orders as seller
        $sellerOrders = $db->prepare(
            "SELECT mo.*, c.card_name, c.card_image_url, c.card_set_id, c.rarity,
                    buyer.username as buyer_username
             FROM marketplace_orders mo
             JOIN marketplace_listings ml ON ml.id = mo.listing_id
             JOIN cards c ON c.id = ml.card_id
             JOIN users buyer ON buyer.id = mo.buyer_id
             WHERE mo.seller_id = :uid
             ORDER BY mo.created_at DESC"
        );
        $sellerOrders->execute(['uid' => $userId]);
        $sellerOrders = $sellerOrders->fetchAll();

        View::render('pages/orders/index', [
            'title' => 'My Orders',
            'buyerOrders' => $buyerOrders,
            'sellerOrders' => $sellerOrders,
        ]);
    }

    public function show(int $id): void
    {
        Auth::requireAuth();
        $db = Database::getConnection();
        $userId = Auth::id();

        $order = $db->prepare(
            "SELECT mo.*, c.card_name, c.card_image_url, c.card_set_id, c.rarity, c.set_name,
                    ml.price as listing_price, ml.description as listing_description, ml.`condition` as card_condition,
                    ml.images as listing_images,
                    seller.username as seller_username, buyer.username as buyer_username
             FROM marketplace_orders mo
             JOIN marketplace_listings ml ON ml.id = mo.listing_id
             JOIN cards c ON c.id = ml.card_id
             JOIN users seller ON seller.id = mo.seller_id
             JOIN users buyer ON buyer.id = mo.buyer_id
             WHERE mo.id = :id"
        );
        $order->execute(['id' => $id]);
        $order = $order->fetch();

        if (!$order) {
            http_response_code(404);
            View::render('pages/404');
            return;
        }

        // Validate buyer or seller
        if ($order['buyer_id'] !== $userId && $order['seller_id'] !== $userId) {
            http_response_code(403);
            View::render('pages/404');
            return;
        }

        // Reviews
        $reviews = $db->prepare(
            "SELECT mr.*, u.username as reviewer_username
             FROM marketplace_reviews mr
             JOIN users u ON u.id = mr.reviewer_id
             WHERE mr.order_id = :oid
             ORDER BY mr.created_at ASC"
        );
        $reviews->execute(['oid' => $id]);
        $reviews = $reviews->fetchAll();

        // Dispute
        $dispute = $db->prepare(
            "SELECT md.*, opener.username as opener_username
             FROM marketplace_disputes md
             JOIN users opener ON opener.id = md.opened_by
             WHERE md.order_id = :oid"
        );
        $dispute->execute(['oid' => $id]);
        $dispute = $dispute->fetch();

        // Check if current user already reviewed
        $hasReviewed = false;
        foreach ($reviews as $review) {
            if ((int)$review['reviewer_id'] === $userId) {
                $hasReviewed = true;
                break;
            }
        }

        $isBuyer = (int)$order['buyer_id'] === $userId;
        $isSeller = (int)$order['seller_id'] === $userId;

        View::render('pages/orders/show', [
            'title' => 'Order #' . $id,
            'order' => $order,
            'reviews' => $reviews,
            'dispute' => $dispute,
            'hasReviewed' => $hasReviewed,
            'isBuyer' => $isBuyer,
            'isSeller' => $isSeller,
        ]);
    }

    public function markShipped(int $id): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $input = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') ? (json_decode(file_get_contents('php://input'), true) ?? []) : $_POST;
        $trackingNumber = trim($input['tracking_number'] ?? '');
        $carrier = trim($input['carrier'] ?? '');

        try {
            $result = \App\Services\MarketplaceService::markShipped(Auth::id(), $id, $trackingNumber, $carrier);
            if (!($result['success'] ?? false)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Failed to mark as shipped']);
                return;
            }
            echo json_encode(['success' => true, 'message' => 'Order marked as shipped']);
        } catch (\Throwable $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function confirmDelivery(int $id): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        try {
            $result = \App\Services\MarketplaceService::confirmDelivery(Auth::id(), $id);
            if (!($result['success'] ?? false)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Failed to confirm delivery']);
                return;
            }
            echo json_encode(['success' => true, 'message' => 'Delivery confirmed']);
        } catch (\Throwable $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function openDispute(int $id): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $input = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') ? (json_decode(file_get_contents('php://input'), true) ?? []) : $_POST;
        $reason = trim($input['reason'] ?? '');
        $description = trim($input['description'] ?? '');

        if (empty($reason)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Reason is required']);
            return;
        }

        $validReasons = ['item_not_received', 'item_not_as_described', 'wrong_item', 'damaged_in_shipping', 'counterfeit', 'other'];
        if (!in_array($reason, $validReasons)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid reason']);
            return;
        }

        try {
            $result = \App\Services\MarketplaceService::openDispute(Auth::id(), $id, $reason, $description);
            if (!($result['success'] ?? false)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Failed to open dispute']);
                return;
            }
            echo json_encode(['success' => true, 'message' => 'Dispute opened']);
        } catch (\Throwable $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function submitReview(int $id): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $input = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') ? (json_decode(file_get_contents('php://input'), true) ?? []) : $_POST;
        $rating = (int)($input['rating'] ?? 0);
        $comment = trim($input['comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
            return;
        }

        $db = Database::getConnection();
        $userId = Auth::id();

        // Verify order exists and user is buyer or seller
        $order = $db->prepare("SELECT * FROM marketplace_orders WHERE id = :id");
        $order->execute(['id' => $id]);
        $order = $order->fetch();

        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            return;
        }

        if ((int)$order['buyer_id'] !== $userId && (int)$order['seller_id'] !== $userId) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Not authorized to review this order']);
            return;
        }

        if (!in_array($order['escrow_status'], ['completed', 'delivered'])) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Can only review completed orders']);
            return;
        }

        // Check if already reviewed
        $existing = $db->prepare(
            "SELECT id FROM marketplace_reviews WHERE order_id = :oid AND reviewer_id = :uid"
        );
        $existing->execute(['oid' => $id, 'uid' => $userId]);
        if ($existing->fetch()) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'You have already reviewed this order']);
            return;
        }

        // Determine who is being reviewed and the reviewer's role
        $isBuyer = (int)$order['buyer_id'] === $userId;
        $revieweeId = $isBuyer ? (int)$order['seller_id'] : (int)$order['buyer_id'];
        $role = $isBuyer ? 'buyer' : 'seller';

        try {
            $db->prepare(
                "INSERT INTO marketplace_reviews (order_id, reviewer_id, reviewed_user_id, rating, review_text, role, created_at)
                 VALUES (:oid, :rid, :rvid, :rating, :review_text, :role, NOW())"
            )->execute([
                'oid' => $id,
                'rid' => $userId,
                'rvid' => $revieweeId,
                'rating' => $rating,
                'review_text' => $comment,
                'role' => $role,
            ]);

            echo json_encode(['success' => true, 'message' => 'Review submitted']);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
        }
    }
}
