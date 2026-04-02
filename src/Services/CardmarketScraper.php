<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class CardmarketScraper
{
    private string $flareSolverrUrl;
    private int $maxTimeout;
    private int $requestDelay;

    /** @var ?string HTTP(S) or SOCKS proxy for cURL → FlareSolverr (rare; usually unset). */
    private ?string $curlProxy;

    public function __construct()
    {
        $this->flareSolverrUrl = rtrim($_ENV['FLARESOLVERR_URL'] ?? 'http://127.0.0.1:8192/v1', '/');
        $this->maxTimeout = 60000;
        $this->requestDelay = 8;
        $proxy = $_ENV['CARDMARKET_FLARE_CURL_PROXY'] ?? $_ENV['HTTP_PROXY'] ?? '';
        $this->curlProxy = $proxy !== '' ? $proxy : null;
    }

    public function scrapeCardsForToday(int $limit = 100, string $edition = 'en'): array
    {
        $stats = ['updated' => 0, 'failed' => 0, 'skipped' => 0, 'edition' => $edition, 'errors' => []];

        if (!$this->isAvailable()) {
            throw new \RuntimeException('FlareSolverr is not reachable at ' . $this->flareSolverrUrl);
        }

        $cards = $this->getCardsToUpdate($limit);
        echo "  " . count($cards) . " cards to update on Cardmarket ($edition edition)...\n";

        foreach ($cards as $i => $card) {
            if ($i > 0) {
                sleep($this->requestDelay);
            }

            try {
                $result = $this->scrapeCardPrice($card, $edition);

                if ($result !== null) {
                    $this->updateCardPrice($card['id'], $result['price'], $result['url'], $edition);
                    $this->recordPriceHistory($card['id'], 'cardmarket', $result['price'], $edition);
                    $stats['updated']++;
                    echo "    [{$stats['updated']}] {$card['card_set_id']}: {$result['price']} EUR\n";
                } else {
                    $this->markUpdated($card['id']);
                    $stats['failed']++;
                    echo "    [MISS] {$card['card_set_id']}: no price found\n";
                }
            } catch (\Throwable $e) {
                $stats['failed']++;
                $stats['errors'][] = $card['card_set_id'] . ': ' . $e->getMessage();
                echo "    [ERR] {$card['card_set_id']}: {$e->getMessage()}\n";
            }

            if (($stats['updated'] + $stats['failed']) % 25 === 0 && ($stats['updated'] + $stats['failed']) > 0) {
                echo "    Progress: {$stats['updated']} ok, {$stats['failed']} fail / " . count($cards) . "\n";
            }
        }

        return $stats;
    }

    private function fetchPage(string $url, int $retries = 2): ?string
    {
        for ($attempt = 0; $attempt <= $retries; $attempt++) {
            if ($attempt > 0) {
                $backoff = $attempt * 15;
                echo "      Retry $attempt in {$backoff}s...\n";
                sleep($backoff);
            }

            $resp = $this->flareRequest([
                'cmd' => 'request.get',
                'url' => $url,
                'maxTimeout' => $this->maxTimeout,
            ]);

            if (($resp['status'] ?? '') !== 'ok') {
                echo "      FlareSolverr error: " . ($resp['message'] ?? 'unknown') . "\n";
                continue;
            }

            $html = $resp['solution']['response'] ?? '';

            if (str_contains($html, 'rate limited') || str_contains($html, 'Error 1015') || str_contains($html, 'banned you temporarily')) {
                echo "      Rate limited by Cloudflare, backing off...\n";
                $this->requestDelay = min($this->requestDelay + 5, 30);
                continue;
            }

            if (str_contains($html, 'cf-error') && strlen($html) < 10000) {
                echo "      Cloudflare error page received\n";
                continue;
            }

            $httpCode = (int)($resp['solution']['status'] ?? 0);
            if ($httpCode !== 200) {
                echo "      HTTP $httpCode\n";
                continue;
            }

            return $html;
        }

        return null;
    }

    private function flareRequest(array $payload): array
    {
        $ch = curl_init($this->flareSolverrUrl);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
        ];
        if ($this->curlProxy !== null) {
            $opts[CURLOPT_PROXY] = $this->curlProxy;
        }
        curl_setopt_array($ch, $opts);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response || $httpCode >= 500) {
            return ['status' => 'error', 'message' => "HTTP $httpCode"];
        }

        return json_decode($response, true) ?: ['status' => 'error', 'message' => 'Invalid JSON'];
    }

    private function cleanCardSetId(string $cardSetId): string
    {
        return preg_replace('/_(?:par|spr?|aa|dp|rp|p\d+)$/i', '', $cardSetId);
    }

    private function scrapeCardPrice(array $card, string $edition = 'en'): ?array
    {
        $cardSetId = $card['card_set_id'] ?? '';
        if ($cardSetId === '') {
            return null;
        }

        $searchId = $this->cleanCardSetId($cardSetId);
        $lang = match ($edition) {
            'fr' => 'fr',
            default => 'en',
        };

        $searchSuffix = $edition === 'jp' ? '+Japanese' : '';
        $url = "https://www.cardmarket.com/$lang/OnePiece/Products/Search?searchString=" . urlencode($searchId) . $searchSuffix;
        $html = $this->fetchPage($url);

        if (!$html) return null;

        $results = $this->parseSearchResults($html, $lang);

        if (empty($results)) return null;

        $profile = $this->resolveVariantProfile($card);
        $best = $this->pickBestResult($results, $profile, $edition);

        if (!$best || !$best['url']) return null;

        if ($best['from_price'] !== null && $best['from_price'] < 5.0) {
            return ['price' => $best['from_price'], 'url' => $best['url']];
        }

        sleep($this->requestDelay);

        $detailHtml = $this->fetchPage($best['url']);

        if ($detailHtml) {
            $trendPrice = $this->extractTrendPrice($detailHtml);
            if ($trendPrice !== null) {
                return ['price' => $trendPrice, 'url' => $best['url']];
            }
        }

        if ($best['from_price'] !== null) {
            return ['price' => $best['from_price'], 'url' => $best['url']];
        }

        return null;
    }

    private function parseSearchResults(string $html, string $lang = 'en'): array
    {
        $results = [];
        $pattern = '#<a\s+href="(/' . preg_quote($lang) . '/OnePiece/Products/Singles/[^"]+)"[^>]*class="[^"]*galleryBox[^"]*"[^>]*>.*?</a>#si';

        if (!preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            return $results;
        }

        foreach ($matches as $match) {
            $block = $match[0];
            $href = 'https://www.cardmarket.com' . $match[1];

            $title = '';
            if (preg_match('#<h2[^>]*>(.*?)</h2>#si', $block, $tm)) {
                $title = strip_tags($tm[1]);
            }

            $fromPrice = null;
            if (preg_match('#From\s*<b>(\d+[.,]\d+)\s*(?:&euro;|€)</b>#i', $block, $pm)) {
                $fromPrice = (float)str_replace(',', '.', $pm[1]);
            } elseif (preg_match('#(?:À\s*partir\s*de|Dès)\s*<b>(\d+[.,]\d+)\s*(?:&euro;|€)</b>#i', $block, $pm)) {
                $fromPrice = (float)str_replace(',', '.', $pm[1]);
            }

            $results[] = [
                'url' => $href,
                'title' => trim($title),
                'from_price' => $fromPrice,
            ];
        }

        return $results;
    }

    /**
     * Normalized variant for Cardmarket listing titles/URLs (OPTCG parallels, SPR, promos, etc.).
     *
     * @return array{type: string, is_parallel_db: bool, rarity: string}
     */
    private function resolveVariantProfile(array $card): array
    {
        $id = $card['card_set_id'] ?? '';
        $rarity = strtoupper((string)($card['rarity'] ?? ''));
        $isParallelDb = (int)($card['is_parallel'] ?? 0) === 1;

        $type = 'standard';
        if (preg_match('/_spr$/i', $id)) {
            $type = 'spr';
        } elseif (preg_match('/_dp$/i', $id)) {
            $type = 'dash_pack';
        } elseif (preg_match('/_rep$/i', $id)) {
            $type = 'reprint';
        } elseif (preg_match('/_manga$/i', $id)) {
            $type = 'manga';
        } elseif (preg_match('/_foil$/i', $id)) {
            $type = 'pirate_foil';
        } elseif (preg_match('/_sp$/i', $id)) {
            $type = 'sp_pack';
        } elseif (preg_match('/_aa$/i', $id)) {
            $type = 'alt_art';
        } elseif ($isParallelDb
            || preg_match('/_par$/i', $id)
            || preg_match('/_p\d+$/i', $id)) {
            $type = 'parallel';
        } elseif (in_array($rarity, ['SP', 'SEC'], true) && $isParallelDb) {
            $type = 'parallel';
        }

        return ['type' => $type, 'is_parallel_db' => $isParallelDb, 'rarity' => $rarity];
    }

    /**
     * Score a search result; highest score wins. Separates base print from alternates / JP / promo SKUs.
     */
    private function scoreListingMatch(string $url, string $title, array $profile, string $edition): int
    {
        $urlLower = strtolower($url);
        $titleLower = strtolower($title);
        $blob = $urlLower . ' ' . $titleLower;

        $isJpListing = str_contains($urlLower, 'japanese');
        if ($edition === 'jp' && !$isJpListing) {
            return -10000;
        }
        if ($edition !== 'jp' && $isJpListing) {
            return -10000;
        }

        $score = 0;
        if ($profile['rarity'] !== '') {
            $score += 2;
        }

        $hasV = static function (string $s, int $n): bool {
            return (bool)preg_match('/\(v\.\s*' . $n . '\)|\bv\.\s*' . $n . '\b|-v' . $n . '(?:\b|\/)/i', $s);
        };

        $looksParallel = str_contains($titleLower, 'parallel')
            || $hasV($blob, 1) || $hasV($blob, 2);
        $looksSpr = str_contains($titleLower, 'special art') || $hasV($blob, 3) || $hasV($blob, 4);
        $looksAlt = str_contains($titleLower, 'alternate') || str_contains($titleLower, 'alt art');
        $looksDash = str_contains($titleLower, 'dash pack');
        $looksManga = str_contains($titleLower, 'manga');
        $looksReprint = str_contains($titleLower, 'reprint');
        $looksPirateFoil = str_contains($titleLower, 'pirate foil') || str_contains($titleLower, 'foil');
        $looksPremiumBandai = str_contains($urlLower, 'premium-bandai') || str_contains($titleLower, 'premium bandai');

        switch ($profile['type']) {
            case 'parallel':
                if ($looksParallel || $hasV($blob, 2)) {
                    $score += 200;
                }
                if ($hasV($blob, 1)) {
                    $score += 160;
                }
                if (!$looksParallel && !$hasV($blob, 1) && !$hasV($blob, 2)) {
                    $score -= 120;
                }
                break;
            case 'spr':
                if ($looksSpr) {
                    $score += 220;
                }
                if ($looksParallel && !$looksSpr) {
                    $score -= 80;
                }
                break;
            case 'alt_art':
                if ($looksAlt || $hasV($blob, 2) || $hasV($blob, 3)) {
                    $score += 200;
                }
                if (!$looksAlt && !$hasV($blob, 2)) {
                    $score -= 100;
                }
                break;
            case 'dash_pack':
                if ($looksDash) {
                    $score += 220;
                } else {
                    $score -= 150;
                }
                break;
            case 'manga':
                if ($looksManga) {
                    $score += 220;
                } else {
                    $score -= 150;
                }
                break;
            case 'reprint':
                if ($looksReprint) {
                    $score += 200;
                }
                break;
            case 'pirate_foil':
                if ($looksPirateFoil) {
                    $score += 200;
                } else {
                    $score -= 120;
                }
                break;
            case 'sp_pack':
                if (str_contains($titleLower, '(sp)') || str_contains($titleLower, ' dash ') || $looksSpr) {
                    $score += 120;
                }
                break;
            case 'standard':
            default:
                if ($looksParallel || $looksSpr || $looksAlt || $hasV($blob, 1) || $hasV($blob, 2) || $hasV($blob, 3) || $hasV($blob, 4)) {
                    $score -= 180;
                }
                if (preg_match('/\(v\./i', $titleLower) || preg_match('/-v\d/i', $urlLower)) {
                    $score -= 80;
                }
                $score += 50;
                break;
        }

        if ($looksPremiumBandai) {
            $score -= 40;
        }

        return $score;
    }

    private function pickBestResult(array $results, array $profile, string $edition): ?array
    {
        $best = null;
        $bestScore = PHP_INT_MIN;

        foreach ($results as $r) {
            $s = $this->scoreListingMatch($r['url'], $r['title'], $profile, $edition);
            if ($s < -5000) {
                continue;
            }
            if ($r['from_price'] !== null) {
                $s += 1;
            }
            if ($s > $bestScore) {
                $bestScore = $s;
                $best = $r;
            }
        }

        if ($best !== null) {
            return $best;
        }

        foreach ($results as $r) {
            $urlLower = strtolower($r['url']);
            $isJpUrl = str_contains($urlLower, 'japanese');
            if ($edition === 'jp' && $isJpUrl) {
                return $r;
            }
            if ($edition !== 'jp' && !$isJpUrl) {
                return $r;
            }
        }

        return $results[0];
    }

    private function extractTrendPrice(string $html): ?float
    {
        if (preg_match('#<dt[^>]*>\s*(?:Trend\s*Price|Prix\s*Trend)\s*</dt>\s*<dd[^>]*>\s*(\d+[.,]\d+)\s*(?:&euro;|€)#si', $html, $m)) {
            return (float)str_replace(',', '.', $m[1]);
        }

        if (preg_match('#Trend[^<]{0,30}?(\d+[.,]\d+)\s*(?:&euro;|€)#i', $html, $m)) {
            return (float)str_replace(',', '.', $m[1]);
        }

        if (preg_match('#<dd[^>]*class="[^"]*font-weight-bold[^"]*"[^>]*>\s*(\d+[.,]\d+)\s*(?:&euro;|€)#i', $html, $m)) {
            return (float)str_replace(',', '.', $m[1]);
        }

        return null;
    }

    private function getCardsToUpdate(int $limit): array
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT id, card_set_id, rarity, is_parallel, market_price,
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
                CASE WHEN price_updated_at IS NULL THEN 0 ELSE 1 END ASC,
                price_updated_at ASC
            LIMIT :lim
        ");
        $stmt->bindValue('lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function updateCardPrice(int $cardId, float $price, string $url, string $edition = 'en'): void
    {
        $db = Database::getConnection();
        $col = match ($edition) {
            'fr' => 'price_fr',
            'jp' => 'price_jp',
            default => 'price_en',
        };
        $stmt = $db->prepare(
            "UPDATE cards SET $col = :price, cardmarket_price = COALESCE(cardmarket_price, :price2), cardmarket_url = :url, price_updated_at = NOW() WHERE id = :id"
        );
        $stmt->execute(['price' => $price, 'price2' => $price, 'url' => $url, 'id' => $cardId]);
    }

    private function markUpdated(int $cardId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE cards SET price_updated_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $cardId]);
    }

    private function recordPriceHistory(int $cardId, string $source, float $price, string $edition = 'en'): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            "INSERT INTO price_history (card_id, source, edition, price, recorded_at)
             VALUES (:card_id, :source, :edition, :price, CURDATE())
             ON DUPLICATE KEY UPDATE price = VALUES(price)"
        );
        $stmt->execute(['card_id' => $cardId, 'source' => $source, 'edition' => $edition, 'price' => $price]);
    }

    public function isAvailable(): bool
    {
        $resp = $this->flareRequest(['cmd' => 'sessions.list']);
        return ($resp['status'] ?? '') === 'ok';
    }
}
