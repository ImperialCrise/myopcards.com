<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\HttpCache;
use App\Core\View;
use App\Models\Card;
use App\Models\Collection;
use App\Models\PriceHistory;
use App\Services\CardSyncService;
use App\Services\DonCardImageResolver;
use App\Services\StorageService;

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
        $isFeatured = false;
        if (Auth::check()) {
            $userCards = Collection::getUserCardIds(Auth::id());
            $userOwns = $userCards[$card['id']] ?? 0;
            
            // Check if this card is the user's featured card
            $user = \App\Models\User::findById(Auth::id());
            $isFeatured = ($user && $user['featured_card_id'] == $card['id']);
        }

        $priceStr = !empty($card['market_price']) ? ' | $' . number_format((float)$card['market_price'], 2) : '';
        $cardTitle = $card['card_name'] . ' (' . $card['card_set_id'] . ') - ' . $card['set_name'] . $priceStr;
        $cardDesc = $card['card_name'] . ' from ' . $card['set_name'] . '. '
            . ($card['rarity'] ? 'Rarity: ' . $card['rarity'] . '. ' : '')
            . ($card['card_color'] ? 'Color: ' . $card['card_color'] . '. ' : '')
            . ($card['card_type'] ? 'Type: ' . $card['card_type'] . '. ' : '')
            . ($card['market_price'] ? 'Market price: $' . number_format((float)$card['market_price'], 2) . '. ' : '')
            . 'View prices, details and add to your collection on MyOPCards.';

        $cardImageForMeta = card_img_url($card);
        if ($cardImageForMeta !== '' && str_starts_with($cardImageForMeta, '/')) {
            $cardImageForMeta = rtrim($_ENV['APP_URL'] ?? 'https://myopcards.com', '/') . $cardImageForMeta;
        }

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $card['card_name'],
            'description' => $cardDesc,
            'image' => $cardImageForMeta,
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
            'isFeatured' => $isFeatured,
            'seoDescription' => $cardDesc,
            'seoImage' => $cardImageForMeta,
            'seoOgType' => 'product',
            'seoJsonLd' => $jsonLd,
        ]);
    }

    public function recommended(): void
    {
        header('Content-Type: application/json');
        $color = trim($_GET['color'] ?? '');
        if ($color === '') {
            echo json_encode(['cards' => []]);
            return;
        }
        $cards = Card::getRecommendedForDeck($color, 24);
        echo json_encode(['cards' => $cards]);
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

    public function setFeatured(string $id): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $card = Card::findBySetCardId($id);
        if (!$card) {
            echo json_encode(['success' => false, 'error' => 'Card not found']);
            return;
        }
        
        // Check if this card is already featured
        $user = \App\Models\User::findById(Auth::id());
        $currentlyFeatured = ($user && $user['featured_card_id'] == $card['id']);
        
        if ($currentlyFeatured) {
            // Unfeatured the card
            $success = \App\Models\User::setFeaturedCard(Auth::id(), null);
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Featured card removed', 'featured' => false]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to remove featured card']);
            }
        } else {
            // Set as featured
            $success = \App\Models\User::setFeaturedCard(Auth::id(), $card['id']);
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Featured card updated', 'featured' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'You must own this card to feature it']);
            }
        }
    }

    /**
     * Resolve DON!! artwork (OPECards, TCGPlayer API map, cache / config).
     * TCGPlayer CDN URLs are proxied as image bytes (hotlink 403 from browsers); others redirect.
     */
    public function donCardImage(): void
    {
        $id = $_GET['i'] ?? '';
        $name = (string)($_GET['n'] ?? '');
        $setHint = (string)($_GET['s'] ?? '');
        if (!DonCardImageResolver::isDonCardSetId($id)) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Invalid id';

            return;
        }
        $resolved = DonCardImageResolver::resolve($id, $name, true, $setHint);
        if ($resolved === null) {
            $resolved = DonCardImageResolver::placeholderPath();
        }
        if (str_starts_with($resolved, 'http') && DonCardImageResolver::isTcgplayerCdnImageUrl($resolved)) {
            if (DonCardImageResolver::serveTcgPlayerDonImage($id, $resolved)) {
                exit;
            }
            $resolved = DonCardImageResolver::placeholderPath();
        } elseif (str_starts_with($resolved, 'http')) {
            DonCardImageResolver::persistToDatabase($id, $resolved);
        }
        $base = rtrim($_ENV['APP_URL'] ?? 'https://myopcards.com', '/');
        $location = str_starts_with($resolved, '/') ? $base . $resolved : $resolved;
        header('Cache-Control: ' . HttpCache::CARD_IMAGE_IMMUTABLE);
        header('Location: ' . $location, true, 302);
        exit;
    }

    /**
     * Proxy official English cardlist PNGs — CDN blocks cross-site display in browsers (CORP / SameSite).
     */
    public function officialCardlistImage(): void
    {
        $id = trim((string)($_GET['i'] ?? ''));
        $src = CardSyncService::officialPngUrlForCardSetId($id);
        if ($src === null) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Invalid id';

            return;
        }

        if (CardSyncService::officialCardlistCacheExists($id)) {
            $base = rtrim($_ENV['APP_URL'] ?? 'https://myopcards.com', '/');
            header('Cache-Control: ' . HttpCache::CARD_IMAGE_IMMUTABLE);
            header('Location: ' . $base . CardSyncService::officialCardlistCachePublicPath($id), true, 302);

            return;
        }

        $ch = curl_init($src);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_USERAGENT => 'MyOPCards/1.0 (Official card art proxy)',
        ]);
        $proxy = trim((string)($_ENV['SCRAPING_HTTP_PROXY'] ?? $_ENV['HTTP_PROXY'] ?? ''));
        if ($proxy !== '') {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code !== 200 || strlen((string)$body) < 64) {
            http_response_code($code === 404 ? 404 : 502);

            return;
        }
        $body = (string)$body;

        $key = CardSyncService::officialCardlistCacheStorageKey($id);
        if (StorageService::isConfigured()) {
            StorageService::put($key, $body, 'image/png');
        }
        $localPath = CardSyncService::officialCardlistCacheLocalPath($id);
        $dir = \dirname($localPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($localPath, $body);

        header('Content-Type: image/png');
        header('Cache-Control: ' . HttpCache::CARD_IMAGE_IMMUTABLE);
        header('Cross-Origin-Resource-Policy: cross-origin');
        header('Content-Length: ' . (string)strlen($body));
        echo $body;
    }

    public function proxyImage(): void
    {
        $url = $_GET['url'] ?? '';
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';
        $allowed = ['optcgapi.com', 'www.optcgapi.com'];
        if (!in_array($host, $allowed, true)) {
            http_response_code(400);
            header('Content-Type: text/plain');
            echo 'Invalid URL';
            return;
        }
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 10,
                'follow_location' => 1,
                'user_agent' => 'MyOPCards/1.0 (Image Proxy)',
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) {
            http_response_code(502);
            return;
        }
        $ctype = 'image/jpeg';
        if (preg_match('/\.(png|gif|webp)$/i', $url)) {
            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
            $ctype = $ext === 'png' ? 'image/png' : ($ext === 'gif' ? 'image/gif' : 'image/webp');
        }
        header('Content-Type: ' . $ctype);
        header('Cache-Control: ' . HttpCache::CARD_IMAGE_IMMUTABLE);
        header('Content-Length: ' . strlen($body));
        echo $body;
    }
}
