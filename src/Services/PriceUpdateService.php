<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Services\CardSyncService;
use PDO;

class PriceUpdateService
{
    public function updateTcgplayerPrices(): array
    {
        $stats = ['updated' => 0, 'errors' => []];
        $apiBase = $_ENV['OPTCG_API_BASE'] ?? 'https://optcgapi.com/api';

        echo "Fetching TCGPlayer prices from OPTCG API...\n";

        $allCards = $this->apiGet($apiBase . '/allSetCards/');
        if ($allCards) {
            echo "  Processing " . count($allCards) . " booster cards...\n";
            foreach ($allCards as $card) {
                $this->updateSingleTcgPrice($card);
                $stats['updated']++;
            }
        }

        $stCards = $this->apiGet($apiBase . '/allSTCards/');
        if ($stCards) {
            echo "  Processing " . count($stCards) . " starter cards...\n";
            foreach ($stCards as $card) {
                $this->updateSingleTcgPrice($card);
                $stats['updated']++;
            }
        }

        $promos = $this->apiGet($apiBase . '/allPromoCards/');
        if ($promos) {
            echo "  Processing " . count($promos) . " promo cards...\n";
            foreach ($promos as $card) {
                $this->updateSingleTcgPrice($card);
                $stats['updated']++;
            }
        }

        return $stats;
    }

    private function updateSingleTcgPrice(array $card): void
    {
        $db = Database::getConnection();
        $cardSetId = $card['card_set_id'] ?? '';
        if (empty($cardSetId)) return;

        [$uniqueId] = CardSyncService::deriveUniqueId($card);

        $marketPrice = $card['market_price'] ?? null;
        $inventoryPrice = $card['inventory_price'] ?? null;

        if ($marketPrice === null && $inventoryPrice === null) return;

        $stmt = $db->prepare(
            "UPDATE cards SET market_price = :mp, inventory_price = :ip WHERE card_set_id = :csi"
        );
        $stmt->execute(['mp' => $marketPrice, 'ip' => $inventoryPrice, 'csi' => $uniqueId]);

        if ($marketPrice !== null) {
            $stmt2 = $db->prepare("SELECT id FROM cards WHERE card_set_id = :csi LIMIT 1");
            $stmt2->execute(['csi' => $uniqueId]);
            $row = $stmt2->fetch();
            if ($row) {
                $stmt3 = $db->prepare(
                    "INSERT INTO price_history (card_id, source, price, recorded_at)
                     VALUES (:cid, 'tcgplayer', :price, CURDATE())
                     ON DUPLICATE KEY UPDATE price = VALUES(price)"
                );
                $stmt3->execute(['cid' => $row['id'], 'price' => $marketPrice]);
            }
        }
    }

    private function apiGet(string $url): ?array
    {
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

        if ($httpCode !== 200 || $response === false) return null;
        return json_decode($response, true);
    }
}
