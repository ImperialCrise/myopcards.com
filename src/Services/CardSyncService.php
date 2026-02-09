<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Card;
use App\Models\CardSet;

class CardSyncService
{
    private string $apiBase;

    public function __construct()
    {
        $this->apiBase = $_ENV['OPTCG_API_BASE'] ?? 'https://optcgapi.com/api';
    }

    public function syncAll(): array
    {
        $stats = ['sets' => 0, 'cards' => 0, 'errors' => []];

        $this->syncBoosterSets($stats);
        $this->syncStarterDecks($stats);
        $this->syncPromos($stats);
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
            $setId = $deck['st_id'] ?? null;
            $setName = $deck['st_name'] ?? null;
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
        $cards = $this->apiGet('/allPromoCards/');
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

        Card::upsert([
            'card_set_id' => $uniqueId,
            'card_name' => $card['card_name'] ?? '',
            'set_name' => $card['set_name'] ?? $card['st_name'] ?? '',
            'set_id' => $card['set_id'] ?? $card['st_id'] ?? $defaultSetId,
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
            'card_image_url' => $card['card_image'] ?? null,
            'market_price' => $card['market_price'] ?? null,
            'inventory_price' => $card['inventory_price'] ?? null,
            'is_parallel' => $isParallel ? 1 : 0,
        ]);
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
