<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Card;
use App\Models\Collection;
use App\Models\User;
use App\Models\CardSet;

class CollectionController
{
    public function index(): void
    {
        Auth::requireAuth();

        $wishlist = (bool)($_GET['wishlist'] ?? false);
        $filters = [
            'q' => $_GET['q'] ?? '',
            'set_id' => $_GET['set_id'] ?? '',
        ];
        $page = max(1, (int)($_GET['page'] ?? 1));

        $result = Collection::getUserCollection(Auth::id(), $wishlist, $filters, $page);
        $stats = User::getCollectionStats(Auth::id());
        $sets = Card::getDistinctValues('set_id');

        View::render('pages/collection', [
            'title' => $wishlist ? 'My Wishlist' : 'My Collection',
            'result' => $result,
            'stats' => $stats,
            'filters' => $filters,
            'sets' => $sets,
            'wishlist' => $wishlist,
        ]);
    }

    public function add(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $cardId = (int)($_POST['card_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        $condition = $_POST['condition'] ?? 'NM';
        $isWishlist = (bool)($_POST['is_wishlist'] ?? false);

        if ($cardId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid card']);
            return;
        }

        Collection::addCard(Auth::id(), $cardId, $quantity, $condition, $isWishlist);
        echo json_encode(['success' => true, 'message' => 'Card added']);
    }

    public function remove(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $cardId = (int)($_POST['card_id'] ?? 0);
        $condition = $_POST['condition'] ?? 'NM';
        $isWishlist = (bool)($_POST['is_wishlist'] ?? false);

        Collection::removeCard(Auth::id(), $cardId, $condition, $isWishlist);
        echo json_encode(['success' => true, 'message' => 'Card removed']);
    }

    public function update(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $cardId = (int)($_POST['card_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        $condition = $_POST['condition'] ?? 'NM';
        $isWishlist = (bool)($_POST['is_wishlist'] ?? false);

        Collection::updateQuantity(Auth::id(), $cardId, $quantity, $condition, $isWishlist);
        echo json_encode(['success' => true, 'message' => 'Quantity updated']);
    }

    public function export(): void
    {
        Auth::requireAuth();

        $result = Collection::getUserCollection(Auth::id(), false, [], 1, 999999);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="myopcards_collection_' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Card ID', 'Card Name', 'Set', 'Rarity', 'Color', 'Type', 'Quantity', 'Condition', 'Market Price', 'Total Value']);

        foreach ($result['cards'] as $card) {
            fputcsv($out, [
                $card['card_set_id'],
                $card['card_name'],
                $card['set_name'],
                $card['rarity'],
                $card['card_color'],
                $card['card_type'],
                $card['quantity'],
                $card['condition'],
                $card['market_price'] ?? 'N/A',
                ($card['market_price'] ?? 0) * $card['quantity'],
            ]);
        }

        fclose($out);
        exit;
    }

    public function publicView(string $username): void
    {
        $owner = User::findByUsername($username);
        if (!$owner || !$owner['is_public']) {
            http_response_code(404);
            View::render('pages/404');
            return;
        }

        $filters = [
            'q' => $_GET['q'] ?? '',
            'set_id' => $_GET['set_id'] ?? '',
        ];
        $page = max(1, (int)($_GET['page'] ?? 1));

        $result = Collection::getUserCollection($owner['id'], false, $filters, $page);
        $stats = User::getCollectionStats($owner['id']);

        View::render('pages/public-collection', [
            'title' => $owner['username'] . "'s Collection",
            'owner' => $owner,
            'result' => $result,
            'stats' => $stats,
            'filters' => $filters,
            'sets' => Card::getDistinctValues('set_id'),
        ]);
    }
}
