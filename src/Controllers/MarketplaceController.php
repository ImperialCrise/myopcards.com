<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;
use App\Services\StorageService;
use PDO;

class MarketplaceController
{
    public function index(): void
    {
        $db = Database::getConnection();

        // Popular cards (most listed)
        $popularCards = [];
        try {
            $popularCards = $db->query(
                "SELECT c.card_set_id, c.card_name, c.card_image_url, c.rarity, c.set_name,
                        COUNT(ml.id) as listing_count,
                        MIN(ml.price) as floor_price
                 FROM marketplace_listings ml
                 JOIN cards c ON c.id = ml.card_id
                 WHERE ml.status = 'active'
                 GROUP BY ml.card_id
                 ORDER BY listing_count DESC
                 LIMIT 12"
            )->fetchAll();
        } catch (\Throwable $e) {}

        // Recent sales
        $recentSales = [];
        try {
            $recentSales = $db->query(
                "SELECT mo.*, c.card_name, c.card_image_url, c.card_set_id, c.rarity,
                        seller.username as seller_username, buyer.username as buyer_username
                 FROM marketplace_orders mo
                 JOIN marketplace_listings ml ON ml.id = mo.listing_id
                 JOIN cards c ON c.id = ml.card_id
                 JOIN users seller ON seller.id = mo.seller_id
                 JOIN users buyer ON buyer.id = mo.buyer_id
                 WHERE mo.escrow_status IN ('completed', 'shipped', 'delivered')
                 ORDER BY mo.created_at DESC
                 LIMIT 12"
            )->fetchAll();
        } catch (\Throwable $e) {}

        View::render('pages/marketplace/index', [
            'title' => 'Marketplace - Buy & Sell One Piece TCG Cards',
            'popularCards' => $popularCards,
            'recentSales' => $recentSales,
        ]);
    }

    public function cardListings(string $id): void
    {
        $db = Database::getConnection();

        // Find card
        $cardStmt = $db->prepare("SELECT * FROM cards WHERE card_set_id = :csi");
        $cardStmt->execute(['csi' => $id]);
        $card = $cardStmt->fetch();

        if (!$card) {
            http_response_code(404);
            View::render('pages/404');
            return;
        }

        // Active listings
        $listings = $db->prepare(
            "SELECT ml.*, u.username as seller_username,
                    (SELECT AVG(mr.rating) FROM marketplace_reviews mr
                     JOIN marketplace_orders mo2 ON mo2.id = mr.order_id
                     WHERE mo2.seller_id = ml.seller_id) as seller_rating,
                    (SELECT COUNT(*) FROM marketplace_orders mo3
                     WHERE mo3.seller_id = ml.seller_id AND mo3.escrow_status = 'completed') as seller_sales
             FROM marketplace_listings ml
             JOIN users u ON u.id = ml.seller_id
             WHERE ml.card_id = :card_id AND ml.status = 'active'
             ORDER BY ml.price ASC"
        );
        $listings->execute(['card_id' => $card['id']]);
        $listings = $listings->fetchAll();

        // Stats
        $stats = $db->prepare(
            "SELECT COUNT(*) as total_listings,
                    MIN(price) as floor_price,
                    AVG(price) as avg_price,
                    MAX(price) as highest_price
             FROM marketplace_listings
             WHERE card_id = :card_id AND status = 'active'"
        );
        $stats->execute(['card_id' => $card['id']]);
        $stats = $stats->fetch();

        // Recent bids on this card's listings
        $bids = $db->prepare(
            "SELECT mb.*, u.username as bidder_username, ml.price as listing_price
             FROM marketplace_bids mb
             JOIN marketplace_listings ml ON ml.id = mb.listing_id
             JOIN users u ON u.id = mb.bidder_id
             WHERE ml.card_id = :card_id AND mb.status = 'pending'
             ORDER BY mb.amount DESC
             LIMIT 20"
        );
        $bids->execute(['card_id' => $card['id']]);
        $bids = $bids->fetchAll();

        View::render('pages/marketplace/card', [
            'title' => $card['card_name'] . ' - Marketplace',
            'card' => $card,
            'listings' => $listings,
            'stats' => $stats,
            'bids' => $bids,
        ]);
    }

    public function showListing(int $id): void
    {
        $db = Database::getConnection();

        $listing = $db->prepare(
            "SELECT ml.*, c.card_name, c.card_image_url, c.rarity, c.set_name, c.card_color, c.card_type,
                    u.username as seller_username
             FROM marketplace_listings ml
             JOIN cards c ON c.id = ml.card_id
             JOIN users u ON u.id = ml.seller_id
             WHERE ml.id = :id"
        );
        $listing->execute(['id' => $id]);
        $listing = $listing->fetch();

        if (!$listing) {
            http_response_code(404);
            View::render('pages/404');
            return;
        }

        // Bids
        $bids = $db->prepare(
            "SELECT mb.*, u.username as bidder_username
             FROM marketplace_bids mb
             JOIN users u ON u.id = mb.bidder_id
             WHERE mb.listing_id = :lid AND mb.status = 'pending'
             ORDER BY mb.amount DESC"
        );
        $bids->execute(['lid' => $id]);
        $bids = $bids->fetchAll();

        // Seller rating
        $sellerRating = $db->prepare(
            "SELECT AVG(mr.rating) as avg_rating, COUNT(mr.id) as review_count
             FROM marketplace_reviews mr
             JOIN marketplace_orders mo ON mo.id = mr.order_id
             WHERE mo.seller_id = :sid"
        );
        $sellerRating->execute(['sid' => $listing['seller_id']]);
        $sellerRating = $sellerRating->fetch();

        View::render('pages/marketplace/listing', [
            'title' => $listing['card_name'] . ' - Listing #' . $id,
            'listing' => $listing,
            'bids' => $bids,
            'sellerRating' => $sellerRating,
        ]);
    }

    public function search(): void
    {
        header('Content-Type: application/json');

        $filters = [
            'q' => trim($_GET['q'] ?? ''),
            'set_id' => trim($_GET['set_id'] ?? ''),
            'color' => trim($_GET['color'] ?? ''),
            'rarity' => trim($_GET['rarity'] ?? ''),
            'type' => trim($_GET['type'] ?? ''),
            'min_price' => $_GET['min_price'] ?? null,
            'max_price' => $_GET['max_price'] ?? null,
            'condition' => trim($_GET['condition'] ?? ''),
            'sort' => $_GET['sort'] ?? 'price_asc',
        ];
        $page = max(1, (int)($_GET['page'] ?? 1));

        try {
            $result = \App\Models\MarketplaceListing::search($filters, $page);
            echo json_encode(['success' => true, 'data' => $result]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Search failed']);
        }
    }

    public function floorPrices(): void
    {
        header('Content-Type: application/json');

        $cardIds = trim($_GET['card_ids'] ?? '');
        if (empty($cardIds)) {
            echo json_encode(['success' => true, 'prices' => []]);
            return;
        }

        $ids = array_filter(array_map('trim', explode(',', $cardIds)));
        if (empty($ids) || count($ids) > 100) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Provide 1-100 card IDs']);
            return;
        }

        $db = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare(
            "SELECT c.card_set_id, MIN(ml.price) as floor_price, COUNT(*) as listing_count
             FROM marketplace_listings ml
             JOIN cards c ON c.id = ml.card_id
             WHERE c.card_set_id IN ($placeholders) AND ml.status = 'active'
             GROUP BY c.card_set_id"
        );
        $stmt->execute(array_values($ids));
        $rows = $stmt->fetchAll();

        $prices = [];
        foreach ($rows as $row) {
            $prices[$row['card_set_id']] = [
                'floor_price' => number_format((float)$row['floor_price'], 2, '.', ''),
                'listing_count' => (int)$row['listing_count'],
            ];
        }

        echo json_encode(['success' => true, 'prices' => $prices]);
    }

    public function recentSales(): void
    {
        header('Content-Type: application/json');

        $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $db = Database::getConnection();

        try {
            $stmt = $db->prepare(
                "SELECT mo.id, mo.total_paid, mo.created_at,
                        c.card_set_id, c.card_name, c.card_image_url, c.rarity,
                        seller.username as seller_username, buyer.username as buyer_username
                 FROM marketplace_orders mo
                 JOIN marketplace_listings ml ON ml.id = mo.listing_id
                 JOIN cards c ON c.id = ml.card_id
                 JOIN users seller ON seller.id = mo.seller_id
                 JOIN users buyer ON buyer.id = mo.buyer_id
                 WHERE mo.escrow_status IN ('completed', 'shipped', 'delivered')
                 ORDER BY mo.created_at DESC
                 LIMIT :lim"
            );
            $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $sales = $stmt->fetchAll();

            echo json_encode(['success' => true, 'sales' => $sales]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch recent sales']);
        }
    }

    public function popularCards(): void
    {
        header('Content-Type: application/json');

        $limit = min(50, max(1, (int)($_GET['limit'] ?? 12)));
        $db = Database::getConnection();

        try {
            $stmt = $db->prepare(
                "SELECT c.card_set_id, c.card_name, c.card_image_url, c.rarity, c.set_name,
                        COUNT(ml.id) as listing_count,
                        MIN(ml.price) as floor_price
                 FROM marketplace_listings ml
                 JOIN cards c ON c.id = ml.card_id
                 WHERE ml.status = 'active'
                 GROUP BY ml.card_id
                 ORDER BY listing_count DESC
                 LIMIT :lim"
            );
            $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $cards = $stmt->fetchAll();

            echo json_encode(['success' => true, 'cards' => $cards]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch popular cards']);
        }
    }

    public function createListingForm(): void
    {
        Auth::requireAuth();

        View::render('pages/marketplace/sell', [
            'title' => 'Sell a Card - Marketplace',
        ]);
    }

    public function createListing(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        // Support both JSON and FormData (multipart)
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $input = $_POST;
        }

        $cardId = (int)($input['card_id'] ?? 0);
        $cardSetId = trim($input['card_set_id'] ?? '');
        $price = (float)($input['price'] ?? 0);
        $condition = trim($input['condition'] ?? 'NM');
        $description = trim($input['description'] ?? '');
        $quantity = max(1, (int)($input['quantity'] ?? 1));
        $shippingCountry = trim($input['shipping_country'] ?? '');
        $shippingCost = (float)($input['shipping_cost'] ?? 0);
        $shipsInternationally = (bool)($input['international_shipping'] ?? false);

        $db = Database::getConnection();

        // Resolve card_id: accept integer id directly, or look up from card_set_id
        if ($cardId <= 0 && !empty($cardSetId)) {
            $cardStmt = $db->prepare("SELECT id FROM cards WHERE card_set_id = :csi");
            $cardStmt->execute(['csi' => $cardSetId]);
            $cardRow = $cardStmt->fetch();
            if ($cardRow) $cardId = (int)$cardRow['id'];
        }

        if ($cardId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Card is required']);
            return;
        }

        // Verify card exists
        $cardCheck = $db->prepare("SELECT id FROM cards WHERE id = :id");
        $cardCheck->execute(['id' => $cardId]);
        if (!$cardCheck->fetch()) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Card not found']);
            return;
        }

        if ($price < 0.01) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Price must be at least $0.01']);
            return;
        }
        if ($price > 100000) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Price exceeds maximum allowed']);
            return;
        }
        $validConditions = ['NM', 'LP', 'MP', 'HP', 'DMG'];
        if (!in_array($condition, $validConditions)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid condition']);
            return;
        }

        // Handle image uploads
        $uploadedImages = [];
        if (!empty($_FILES['images'])) {
            $files = $_FILES['images'];
            $fileCount = is_array($files['name']) ? count($files['name']) : 1;
            for ($i = 0; $i < min($fileCount, 4); $i++) {
                $tmpName = is_array($files['tmp_name']) ? ($files['tmp_name'][$i] ?? '') : $files['tmp_name'];
                $fileName = is_array($files['name']) ? ($files['name'][$i] ?? '') : $files['name'];
                $error = is_array($files['error']) ? ($files['error'][$i] ?? 4) : $files['error'];
                if ($error !== UPLOAD_ERR_OK || empty($tmpName)) continue;
                try {
                    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                    $storageName = 'marketplace/' . Auth::id() . '/' . bin2hex(random_bytes(8)) . '.' . $ext;
                    StorageService::put($storageName, file_get_contents($tmpName), 'image/' . $ext);
                    $uploadedImages[] = $storageName;
                } catch (\Throwable $e) {
                    // Skip failed uploads
                }
            }
        }

        try {
            $listingId = \App\Services\MarketplaceService::createListing([
                'seller_id' => Auth::id(),
                'card_id' => $cardId,
                'price' => $price,
                'condition' => $condition,
                'description' => $description,
                'images' => !empty($uploadedImages) ? json_encode($uploadedImages) : null,
                'quantity' => $quantity,
                'shipping_from_country' => $shippingCountry ?: null,
                'shipping_cost' => $shippingCost,
                'ships_internationally' => $shipsInternationally ? 1 : 0,
            ]);
            echo json_encode(['success' => true, 'listing_id' => $listingId]);
        } catch (\Throwable $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function editListing(int $id): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $db = Database::getConnection();

        // Validate ownership
        $listing = $db->prepare("SELECT * FROM marketplace_listings WHERE id = :id AND seller_id = :uid");
        $listing->execute(['id' => $id, 'uid' => Auth::id()]);
        $listing = $listing->fetch();

        if (!$listing) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Listing not found or not owned by you']);
            return;
        }

        if ($listing['status'] !== 'active') {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Can only edit active listings']);
            return;
        }

        $updates = [];
        $params = ['id' => $id];

        if (isset($input['price'])) {
            $price = (float)$input['price'];
            if ($price < 0.01 || $price > 100000) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Invalid price']);
                return;
            }
            $updates[] = "price = :price";
            $params['price'] = $price;
        }

        if (isset($input['description'])) {
            $updates[] = "description = :description";
            $params['description'] = trim($input['description']);
        }

        if (isset($input['condition'])) {
            $validConditions = ['mint', 'near_mint', 'lightly_played', 'moderately_played', 'heavily_played', 'damaged'];
            if (!in_array($input['condition'], $validConditions)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => 'Invalid condition']);
                return;
            }
            $updates[] = "`condition` = :cond";
            $params['cond'] = $input['condition'];
        }

        if (empty($updates)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }

        $updates[] = "updated_at = NOW()";
        $sql = "UPDATE marketplace_listings SET " . implode(', ', $updates) . " WHERE id = :id";
        $db->prepare($sql)->execute($params);

        echo json_encode(['success' => true, 'message' => 'Listing updated']);
    }

    public function cancelListing(int $id): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        try {
            $result = \App\Services\MarketplaceService::cancelListing(Auth::id(), $id);
            if (!($result['success'] ?? false)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Failed to cancel listing']);
                return;
            }
            echo json_encode(['success' => true, 'message' => 'Listing cancelled']);
        } catch (\Throwable $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function myListings(): void
    {
        Auth::requireAuth();

        View::render('pages/marketplace/my-listings', [
            'title' => 'My Listings - Marketplace',
        ]);
    }

    public function placeBid(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? [];

        $listingId = (int)($input['listing_id'] ?? 0);
        $amount = (float)($input['amount'] ?? 0);

        if ($listingId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Listing ID is required']);
            return;
        }
        if ($amount < 0.01) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Bid amount must be at least $0.01']);
            return;
        }

        try {
            $result = \App\Services\MarketplaceService::placeBid(Auth::id(), $listingId, $amount);
            if (!($result['success'] ?? false)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Failed to place bid']);
                return;
            }
            echo json_encode(['success' => true, 'bid_id' => $result['bid_id']]);
        } catch (\Throwable $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function acceptBid(int $id): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        try {
            $result = \App\Services\MarketplaceService::acceptBid(Auth::id(), $id);
            if (!($result['success'] ?? false)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Failed to accept bid']);
                return;
            }
            echo json_encode(['success' => true, 'order_id' => $result['order_id']]);
        } catch (\Throwable $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function rejectBid(int $id): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        try {
            $result = \App\Services\MarketplaceService::rejectBid(Auth::id(), $id);
            if (!($result['success'] ?? false)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Failed to reject bid']);
                return;
            }
            echo json_encode(['success' => true, 'message' => 'Bid rejected']);
        } catch (\Throwable $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function cancelBid(int $id): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        try {
            $result = \App\Services\MarketplaceService::cancelBid(Auth::id(), $id);
            if (!($result['success'] ?? false)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Failed to cancel bid']);
                return;
            }
            echo json_encode(['success' => true, 'message' => 'Bid cancelled']);
        } catch (\Throwable $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function buyNow(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $listingId = (int)($input['listing_id'] ?? 0);

        if ($listingId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Listing ID is required']);
            return;
        }

        try {
            $result = \App\Services\MarketplaceService::buyNow(Auth::id(), $listingId);
            if (!($result['success'] ?? false)) {
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $result['error'] ?? 'Purchase failed']);
                return;
            }
            echo json_encode(['success' => true, 'order_id' => $result['order_id']]);
        } catch (\Throwable $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function myBids(): void
    {
        Auth::requireAuth();

        View::render('pages/marketplace/my-bids', [
            'title' => 'My Bids - Marketplace',
        ]);
    }

    public function uploadImage(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'No image uploaded or upload error']);
            return;
        }

        $file = $_FILES['image'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if ($file['size'] > $maxSize) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Image must be under 5MB']);
            return;
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedTypes)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Only JPEG, PNG, and WebP images are allowed']);
            return;
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $filename = Auth::id() . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $key = 'marketplace/' . $filename;
        $content = file_get_contents($file['tmp_name']);

        if ($content === false) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to read uploaded file']);
            return;
        }

        try {
            $stored = StorageService::put($key, $content, $mime);
            if (!$stored) {
                // Fallback to local storage
                $localDir = BASE_PATH . '/public/uploads/marketplace/';
                if (!is_dir($localDir)) {
                    mkdir($localDir, 0755, true);
                }
                file_put_contents($localDir . $filename, $content);
            }

            $url = '/uploads/marketplace/' . $filename;
            echo json_encode(['success' => true, 'url' => $url]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to store image']);
        }
    }
}
