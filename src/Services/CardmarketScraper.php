<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Card;
use PDO;

class CardmarketScraper
{
    private const SEARCH_URL = 'https://www.cardmarket.com/en/OnePiece/Products/Search?searchString=%s';
    private const USER_AGENTS = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ];

    public function scrapeCardsForToday(int $limit = 670): array
    {
        $stats = ['updated' => 0, 'failed' => 0, 'skipped' => 0];
        $cards = $this->getCardsToUpdate($limit);

        echo "  " . count($cards) . " cards to update on Cardmarket...\n";

        foreach ($cards as $card) {
            $delay = random_int(2000000, 5000000);
            usleep($delay);

            $result = $this->scrapeCardPrice($card['card_set_id']);

            if ($result !== null) {
                $this->updateCardPrice($card['id'], $result['price'], $result['url']);
                $this->recordPriceHistory($card['id'], 'cardmarket', $result['price']);
                $stats['updated']++;
            } else {
                $this->markUpdated($card['id']);
                $stats['failed']++;
            }

            if ($stats['updated'] % 50 === 0 && $stats['updated'] > 0) {
                echo "    Progress: {$stats['updated']} updated, {$stats['failed']} failed\n";
            }
        }

        return $stats;
    }

    private function getCardsToUpdate(int $limit): array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT id, card_set_id, rarity, market_price,
                   CASE
                       WHEN rarity IN ('SEC','SP','L') AND is_parallel = 1 THEN 1
                       WHEN COALESCE(market_price, 0) > 20 THEN 1
                       WHEN rarity IN ('SR','R') THEN 2
                       WHEN COALESCE(market_price, 0) BETWEEN 5 AND 20 THEN 2
                       ELSE 3
                   END as tier
            FROM cards
            WHERE (
                (rarity IN ('SEC','SP') OR (rarity = 'L' AND is_parallel = 1) OR COALESCE(market_price, 0) > 20)
                OR
                (rarity IN ('SR','R') OR COALESCE(market_price, 0) BETWEEN 5 AND 20)
                    AND (price_updated_at IS NULL OR price_updated_at < DATE_SUB(NOW(), INTERVAL 3 DAY))
                OR
                (price_updated_at IS NULL OR price_updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY))
            )
            ORDER BY
                CASE
                    WHEN rarity IN ('SEC','SP') OR (rarity = 'L' AND is_parallel = 1) OR COALESCE(market_price, 0) > 20 THEN 0
                    WHEN rarity IN ('SR','R') OR COALESCE(market_price, 0) BETWEEN 5 AND 20 THEN 1
                    ELSE 2
                END ASC,
                price_updated_at ASC NULLS FIRST
            LIMIT :lim
        ");
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function scrapeCardPrice(string $cardSetId): ?array
    {
        $url = sprintf(self::SEARCH_URL, urlencode($cardSetId));
        $html = $this->fetchWithRetry($url, 2);

        if (!$html) return null;

        $price = $this->extractTrendPrice($html);
        $productUrl = $this->extractProductUrl($html);

        if ($price === null) return null;

        return [
            'price' => $price,
            'url' => $productUrl ?? $url,
        ];
    }

    private function fetchWithRetry(string $url, int $retries = 2): ?string
    {
        for ($i = 0; $i <= $retries; $i++) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_HTTPHEADER => [
                    'User-Agent: ' . self::USER_AGENTS[array_rand(self::USER_AGENTS)],
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.9',
                    'Cache-Control: no-cache',
                    'Connection: keep-alive',
                ],
                CURLOPT_ENCODING => 'gzip, deflate',
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                return $response;
            }

            if ($i < $retries) {
                sleep(random_int(3, 8));
            }
        }

        return null;
    }

    private function extractTrendPrice(string $html): ?float
    {
        if (preg_match('/Trend\s*Price[^<]*<[^>]*>\s*([0-9]+[.,][0-9]+)\s*&euro;/i', $html, $m)) {
            return (float)str_replace(',', '.', $m[1]);
        }

        if (preg_match('/class="[^"]*price-tag[^"]*"[^>]*>\s*([0-9]+[.,][0-9]+)\s*&euro;/i', $html, $m)) {
            return (float)str_replace(',', '.', $m[1]);
        }

        if (preg_match('/([0-9]+[.,][0-9]+)\s*(?:&euro;|EUR|€)/i', $html, $m)) {
            return (float)str_replace(',', '.', $m[1]);
        }

        return null;
    }

    private function extractProductUrl(string $html): ?string
    {
        if (preg_match('/href="(\/en\/OnePiece\/Products\/Singles\/[^"]+)"/i', $html, $m)) {
            return 'https://www.cardmarket.com' . $m[1];
        }
        return null;
    }

    private function updateCardPrice(int $cardId, float $price, string $url): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "UPDATE cards SET cardmarket_price = :price, cardmarket_url = :url, price_updated_at = NOW() WHERE id = :id"
        );
        $stmt->execute(['price' => $price, 'url' => $url, 'id' => $cardId]);
    }

    private function markUpdated(int $cardId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE cards SET price_updated_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $cardId]);
    }

    private function recordPriceHistory(int $cardId, string $source, float $price): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO price_history (card_id, source, price, recorded_at)
             VALUES (:card_id, :source, :price, CURDATE())
             ON DUPLICATE KEY UPDATE price = VALUES(price)"
        );
        $stmt->execute(['card_id' => $cardId, 'source' => $source, 'price' => $price]);
    }
}
