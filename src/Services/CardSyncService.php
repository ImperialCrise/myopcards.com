<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Card;
use App\Models\CardSet;
use App\Services\StorageService;

class CardSyncService
{
    private string $apiBase;

    public function __construct()
    {
        $this->apiBase = $_ENV['OPTCG_API_BASE'] ?? 'https://optcgapi.com/api';
    }

    public function syncAll(): array
    {
        $stats = ['sets' => 0, 'cards' => 0, 'sets_backfilled' => 0, 'errors' => []];

        $this->syncBoosterSets($stats);
        $this->syncStarterDecks($stats);
        $this->syncPromos($stats);
        $this->syncDonCards($stats);
        $stats['sets_backfilled'] = CardSet::ensureFromCards();
        $this->recalculateSetCounts();

        return $stats;
    }

    /**
     * Sync only DON!! cards from /allDonCards/ (faster than full catalog sync).
     * Still runs set backfill from cards and recalculates per-set card counts.
     */
    public function syncDonOnly(): array
    {
        $stats = ['sets' => 0, 'cards' => 0, 'sets_backfilled' => 0, 'errors' => []];
        $this->syncDonCards($stats);
        $stats['sets_backfilled'] = CardSet::ensureFromCards();
        $this->recalculateSetCounts();

        return $stats;
    }

    private function syncBoosterSets(array &$stats): void
    {
        $sets = $this->apiGet('/allSets/');
        if (!$sets) {
            $stats['errors'][] = 'Failed to fetch sets list';
            return;
        }

        foreach ($sets as $set) {
            $setId = $set['set_id'] ?? null;
            $setName = $set['set_name'] ?? null;
            if (!$setId || !$setName) continue;
            CardSet::upsert($setId, $setName, 'booster');
            $stats['sets']++;
        }

        echo "Fetching all booster cards (bulk)...\n";
        $allCards = $this->apiGet('/allSetCards/');
        if (!$allCards) {
            $stats['errors'][] = 'Failed to fetch all set cards';
            return;
        }

        echo "Processing " . count($allCards) . " booster cards...\n";
        foreach ($allCards as $card) {
            $this->upsertCard($card);
            $stats['cards']++;
        }
    }

    private function syncStarterDecks(array &$stats): void
    {
        $decks = $this->apiGet('/allDecks/');
        if (!$decks) {
            $stats['errors'][] = 'Failed to fetch starter decks list';
            return;
        }

        foreach ($decks as $deck) {
            $setId = $deck['structure_deck_id'] ?? $deck['st_id'] ?? null;
            $setName = $deck['structure_deck_name'] ?? $deck['st_name'] ?? null;
            if (!$setId || !$setName) continue;
            CardSet::upsert($setId, $setName, 'starter');
            $stats['sets']++;
        }

        echo "Fetching all starter deck cards (bulk)...\n";
        $allCards = $this->apiGet('/allSTCards/');
        if (!$allCards) {
            $stats['errors'][] = 'Failed to fetch all starter cards';
            return;
        }

        echo "Processing " . count($allCards) . " starter cards...\n";
        foreach ($allCards as $card) {
            $this->upsertCard($card);
            $stats['cards']++;
        }
    }

    private function syncPromos(array &$stats): void
    {
        echo "Fetching all promo cards...\n";
        $cards = $this->apiGet('/allPromos/');
        if (!$cards) {
            $cards = $this->apiGet('/allPromoCards/');
        }
        if (!$cards) {
            $stats['errors'][] = 'Failed to fetch promos';
            return;
        }

        CardSet::upsert('PROMO', 'Promo Cards', 'promo');
        $stats['sets']++;

        echo "Processing " . count($cards) . " promo cards...\n";
        foreach ($cards as $card) {
            $this->upsertCard($card, 'PROMO');
            $stats['cards']++;
        }
    }

    private function syncDonCards(array &$stats): void
    {
        echo "Fetching all DON!! cards...\n";
        $cards = $this->apiGet('/allDonCards/');
        if (!$cards) {
            $stats['errors'][] = 'Failed to fetch DON!! cards';
            return;
        }

        CardSet::upsert('DON', 'DON!! Cards', 'don');
        $stats['sets']++;

        echo "Processing " . count($cards) . " DON!! cards...\n";
        foreach ($cards as $card) {
            $imgId = trim((string)($card['card_image_id'] ?? $card['don_id'] ?? ''));
            if ($imgId === '') {
                continue;
            }
            $row = $card;
            $row['card_set_id'] = $imgId;
            $donLine = trim((string)($card['optcg_don_name'] ?? ''));
            $row['set_name'] = $donLine !== '' ? $donLine : (string)($card['card_name'] ?? 'DON!!');
            $releaseSetId = self::parseDonReleaseSetId($donLine);
            if ($releaseSetId !== null && strlen($releaseSetId) <= 20) {
                $row['set_id'] = $releaseSetId;
            }
            $this->upsertCard($row, 'DON');
            $stats['cards']++;
        }
    }

    /**
     * Suffix in optcg_don_name vs canonical set_id in /allSets/ (rare mismatches).
     *
     * @var array<string, string> UPPER short code => official set_id
     */
    private const DON_OPTCG_SET_SUFFIX_ALIASES = [
        'OP01' => 'OP-01',
        'OP02' => 'OP-02',
        'OP03' => 'OP-03',
        'OP04' => 'OP-04',
        'OP05' => 'OP-05',
        'OP06' => 'OP-06',
        'OP07' => 'OP-07',
        'OP08' => 'OP-08',
        'OP09' => 'OP-09',
        'OP10' => 'OP-10',
        'OP11' => 'OP-11',
        'OP12' => 'OP-12',
        'OP13' => 'OP-13',
        'OP14' => 'OP14-EB04',
        'OP15' => 'OP-15',
        'OP16' => 'OP-16',
        'OP17' => 'OP-17'
    ];

    /**
     * OPTCG encodes the physical release in optcg_don_name, e.g.
     * "DON!! Card (Sakazuki) (Gold) - Premium Booster -The Best- (PRB-01)" → PRB-01.
     * Azure Sea's Seven uses "(OP14)" in DON strings but the set is OP14-EB04 in allSets.
     */
    public static function parseDonReleaseSetId(string $optcgDonName): ?string
    {
        $optcgDonName = trim($optcgDonName);
        if ($optcgDonName === '') {
            return null;
        }
        if (!preg_match('/\(([A-Za-z0-9.-]+)\)\s*$/', $optcgDonName, $m)) {
            return null;
        }

        $suffix = $m[1];
        $key = strtoupper($suffix);

        return self::DON_OPTCG_SET_SUFFIX_ALIASES[$key] ?? $suffix;
    }

    private function recalculateSetCounts(): void
    {
        $db = Database::getConnection();
        $db->exec(
            'UPDATE sets s SET card_count = (SELECT COUNT(*) FROM cards c WHERE c.set_id = s.set_id)'
        );
        echo "Set card counts recalculated.\n";
    }

    private function upsertCard(array $card, string $defaultSetId = ''): void
    {
        $cardSetId = $card['card_set_id'] ?? '';
        if (empty($cardSetId)) return;

        [$uniqueId, $isParallel] = self::deriveUniqueId($card);

        $setId = trim((string)($card['set_id'] ?? $card['st_id'] ?? $defaultSetId));
        if ($setId === '') {
            $setId = $defaultSetId;
        }
        // Schema: VARCHAR(20). API sometimes returns junk (e.g. a color list) on promos.
        if (strlen($setId) > 20) {
            $setId = $defaultSetId !== '' ? $defaultSetId : substr($setId, 0, 20);
        }

        $imageUrl = self::resolveCardImageUrl($card, $uniqueId);

        Card::upsert([
            'card_set_id' => $uniqueId,
            'card_name' => $card['card_name'] ?? '',
            'set_name' => $card['set_name'] ?? $card['st_name'] ?? '',
            'set_id' => $setId,
            'rarity' => $card['rarity'] ?? '',
            'card_color' => $card['card_color'] ?? '',
            'card_type' => $card['card_type'] ?? '',
            'card_power' => $card['card_power'] ?? null,
            'card_cost' => $card['card_cost'] ?? null,
            'life' => $card['life'] ?? null,
            'sub_types' => $card['sub_types'] ?? null,
            'counter_amount' => $card['counter_amount'] ?? null,
            'attribute' => $card['attribute'] ?? null,
            'card_text' => $card['card_text'] ?? null,
            'card_image_url' => $imageUrl,
            'market_price' => $card['market_price'] ?? null,
            'inventory_price' => $card['inventory_price'] ?? null,
            'is_parallel' => $isParallel ? 1 : 0,
        ]);

        $imgUrl = isset($card['card_image']) ? trim((string) $card['card_image']) : '';
        if ($imgUrl !== '' && StorageService::isConfigured()) {
            $filename = basename(parse_url($imgUrl, PHP_URL_PATH));
            if ($filename) {
                $key = 'cards/' . $filename;
                if (StorageService::get($key) === null) {
                    $img = @file_get_contents($imgUrl);
                    if ($img) {
                        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                        $ctype = match ($ext) {
                            'png' => 'image/png',
                            'gif' => 'image/gif',
                            'webp' => 'image/webp',
                            default => 'image/jpeg',
                        };
                        StorageService::put($key, $img, $ctype);
                    }
                }
            }
        }
    }

    public static function deriveUniqueId(array $card): array
    {
        $cardSetId = $card['card_set_id'] ?? '';
        $cardImageId = $card['card_image_id'] ?? $cardSetId;
        $cardName = strtolower($card['card_name'] ?? '');

        $isVariant = str_contains($cardImageId, '_p')
            || str_contains($cardImageId, '_r');

        if ($cardImageId !== $cardSetId && $isVariant) {
            return [$cardImageId, true];
        }

        $suffixes = [
            '_spr'  => ['(spr)'],
            '_par'  => ['(parallel)'],
            '_dp'   => ['(dash pack)'],
            '_rep'  => ['(reprint)'],
            '_manga'=> ['(manga)'],
            '_foil' => ['(pirate foil)'],
            '_sp'   => ['(sp)', '- ' . $cardSetId . ' (sp)'],
        ];

        foreach ($suffixes as $suffix => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($cardName, $pattern)) {
                    return [$cardSetId . $suffix, true];
                }
            }
        }

        $isParallel = str_contains($cardName, 'alternate art')
            || str_contains($cardImageId, '_p')
            || str_contains($cardImageId, '_r');

        if ($isParallel && $cardImageId !== $cardSetId) {
            return [$cardImageId, true];
        }

        return [$cardSetId, false];
    }

    /**
     * Official English cardlist PNG (same rule as sync fallback). Not used for DON!! ids.
     */
    public static function officialPngUrlForCardSetId(string $cardSetId): ?string
    {
        if ($cardSetId === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $cardSetId)) {
            return null;
        }
        if (DonCardImageResolver::isDonCardSetId($cardSetId)) {
            return null;
        }
        $base = rtrim($_ENV['OP_OFFICIAL_CARD_IMAGE_BASE'] ?? 'https://en.onepiece-cardgame.com/images/cardlist/card', '/');

        return $base . '/' . $cardSetId . '.png';
    }

    /**
     * Browser-safe URL: official cardlist CDN uses CORP so cross-site img loads fail (NotSameSite).
     * Use /api/op-official-card to fetch server-side and re-serve from our origin.
     */
    public static function officialCardlistProxyUrl(string $cardSetId): ?string
    {
        return self::officialPngUrlForCardSetId($cardSetId) !== null
            ? '/api/op-official-card?' . http_build_query(['i' => $cardSetId])
            : null;
    }

    /** MinIO key for a cached official English cardlist PNG (see UploadController::serveCards). */
    public static function officialCardlistCacheStorageKey(string $cardSetId): string
    {
        return 'cards/official/' . $cardSetId . '.png';
    }

    public static function officialCardlistCachePublicPath(string $cardSetId): string
    {
        return '/uploads/cards/official/' . $cardSetId . '.png';
    }

    public static function officialCardlistCacheLocalPath(string $cardSetId): string
    {
        return \dirname(__DIR__, 2) . '/public/uploads/cards/official/' . $cardSetId . '.png';
    }

    public static function officialCardlistCacheExists(string $cardSetId): bool
    {
        $key = self::officialCardlistCacheStorageKey($cardSetId);
        if (StorageService::isConfigured() && StorageService::exists($key)) {
            return true;
        }
        $local = self::officialCardlistCacheLocalPath($cardSetId);

        return is_file($local) && is_readable($local);
    }

    /** Rewrite hotlinks to en.*.onepiece-cardgame.com cardlist PNGs to same-origin proxy URLs. */
    public static function rewriteOfficialCardlistUrlToProxy(string $url): string
    {
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        $host = strtolower((string)(parse_url($url, PHP_URL_HOST) ?? ''));
        if ($host === '' || !str_contains($host, 'onepiece-cardgame.com')) {
            return $url;
        }
        $path = (string)(parse_url($url, PHP_URL_PATH) ?? '');
        if (!preg_match('#/images/cardlist/card/([A-Za-z0-9._-]+)\.png$#i', $path, $m)) {
            return $url;
        }
        $p = self::officialCardlistProxyUrl($m[1]);

        return $p ?? $url;
    }

    /**
     * OPTCG API often leaves card_image empty (e.g. all promos with set_id "P"). Official English CDN
     * serves PNGs at /images/cardlist/card/{card_set_id}.png for standard game cards.
     */
    public static function resolveCardImageUrl(array $card, string $uniqueCardSetId): ?string
    {
        $direct = isset($card['card_image']) ? trim((string) $card['card_image']) : '';
        if ($direct !== '') {
            return $direct;
        }
        if ($uniqueCardSetId === '' || !preg_match('/^[A-Za-z0-9._-]+$/', $uniqueCardSetId)) {
            return null;
        }
        if (DonCardImageResolver::isDonCardSetId($uniqueCardSetId)) {
            $resolved = DonCardImageResolver::resolve(
                $uniqueCardSetId,
                (string)($card['card_name'] ?? ''),
                false
            );
            if ($resolved !== null && str_starts_with($resolved, 'http')) {
                return $resolved;
            }

            return null;
        }

        return self::officialPngUrlForCardSetId($uniqueCardSetId);
    }

    private function apiGet(string $endpoint): ?array
    {
        $url = $this->apiBase . $endpoint;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $response === false) {
            echo "  API error: HTTP $httpCode for $endpoint\n";
            return null;
        }

        return json_decode($response, true);
    }
}
