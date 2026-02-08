<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Card;
use App\Models\Collection;
use App\Models\PriceHistory;

class CardController
{
    public function index(): void
    {
        $filters = [
            'q' => $_GET['q'] ?? '',
            'set_id' => $_GET['set_id'] ?? '',
            'color' => $_GET['color'] ?? '',
            'rarity' => $_GET['rarity'] ?? '',
            'type' => $_GET['type'] ?? '',
            'sort' => $_GET['sort'] ?? 'set',
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $result = Card::search($filters, $page);

        $userCards = [];
        if (Auth::check()) {
            $userCards = Collection::getUserCardIds(Auth::id());
        }

        $seoTitle = 'One Piece TCG Card Database - Browse ' . number_format($result['total']) . ' Cards';
        if (!empty($filters['set_id'])) $seoTitle = $filters['set_id'] . ' Cards - One Piece TCG Database';
        if (!empty($filters['q'])) $seoTitle = 'Search: ' . $filters['q'] . ' - One Piece TCG Cards';

        View::render('pages/cards', [
            'title' => $seoTitle,
            'result' => $result,
            'filters' => $filters,
            'sets' => Card::getDistinctValues('set_id'),
            'colors' => Card::getDistinctValues('card_color'),
            'rarities' => Card::getDistinctValues('rarity'),
            'types' => Card::getDistinctValues('card_type'),
            'userCards' => $userCards,
            'seoDescription' => 'Browse and search the complete One Piece TCG card database. ' . number_format($result['total']) . ' cards with prices, rarities, and detailed information.',
        ]);
    }

    public function show(string $id): void
    {
        $card = Card::findBySetCardId($id);
        if (!$card) {
            http_response_code(404);
            View::render('pages/404');
            return;
        }

        $userOwns = 0;
        if (Auth::check()) {
            $userCards = Collection::getUserCardIds(Auth::id());
            $userOwns = $userCards[$card['id']] ?? 0;
        }

        $priceStr = !empty($card['market_price']) ? ' | $' . number_format((float)$card['market_price'], 2) : '';
        $cardTitle = $card['card_name'] . ' (' . $card['card_set_id'] . ') - ' . $card['set_name'] . $priceStr;
        $cardDesc = $card['card_name'] . ' from ' . $card['set_name'] . '. '
            . ($card['rarity'] ? 'Rarity: ' . $card['rarity'] . '. ' : '')
            . ($card['card_color'] ? 'Color: ' . $card['card_color'] . '. ' : '')
            . ($card['card_type'] ? 'Type: ' . $card['card_type'] . '. ' : '')
            . ($card['market_price'] ? 'Market price: $' . number_format((float)$card['market_price'], 2) . '. ' : '')
            . 'View prices, details and add to your collection on MyOPCards.';

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $card['card_name'],
            'description' => $cardDesc,
            'image' => $card['card_image_url'] ?? '',
            'sku' => $card['card_set_id'],
            'brand' => ['@type' => 'Brand', 'name' => 'Bandai - One Piece TCG'],
            'category' => 'Trading Card Games > One Piece TCG > ' . ($card['set_name'] ?? ''),
            'url' => 'https://myopcards.com/cards/' . $card['card_set_id'],
        ];
        if (!empty($card['market_price'])) {
            $jsonLd['offers'] = [
                '@type' => 'Offer',
                'priceCurrency' => 'USD',
                'price' => number_format((float)$card['market_price'], 2, '.', ''),
                'availability' => 'https://schema.org/InStock',
                'url' => 'https://myopcards.com/cards/' . $card['card_set_id'],
            ];
        }

        View::render('pages/card-detail', [
            'title' => $cardTitle,
            'card' => $card,
            'userOwns' => $userOwns,
            'seoDescription' => $cardDesc,
            'seoImage' => $card['card_image_url'] ?? '',
            'seoOgType' => 'product',
            'seoJsonLd' => $jsonLd,
        ]);
    }

    public function search(): void
    {
        header('Content-Type: application/json');

        $filters = [
            'q' => $_GET['q'] ?? '',
            'set_id' => $_GET['set_id'] ?? '',
            'color' => $_GET['color'] ?? '',
            'rarity' => $_GET['rarity'] ?? '',
            'type' => $_GET['type'] ?? '',
            'sort' => $_GET['sort'] ?? 'set',
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $result = Card::search($filters, $page);

        echo json_encode($result);
    }

    public function priceHistory(string $id): void
    {
        header('Content-Type: application/json');

        $card = Card::findBySetCardId($id);
        if (!$card) {
            echo json_encode(['error' => 'Card not found']);
            return;
        }

        $days = max(7, min(365, (int)($_GET['days'] ?? 90)));
        $tcg = PriceHistory::getForCard($card['id'], 'tcgplayer', $days, 'en');
        $cmEn = PriceHistory::getForCard($card['id'], 'cardmarket', $days, 'en');
        $cmFr = PriceHistory::getForCard($card['id'], 'cardmarket', $days, 'fr');
        $cmJp = PriceHistory::getForCard($card['id'], 'cardmarket', $days, 'jp');

        echo json_encode([
            'tcgplayer' => $tcg,
            'cardmarket_en' => $cmEn,
            'cardmarket_fr' => $cmFr,
            'cardmarket_jp' => $cmJp,
        ]);
    }
}
