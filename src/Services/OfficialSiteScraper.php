<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

class OfficialSiteScraper
{
    private const LANG_DOMAINS = [
        'en' => 'https://en.onepiece-cardgame.com/cardlist/',
        'fr' => 'https://fr.onepiece-cardgame.com/cardlist/',
        'ja' => 'https://www.onepiece-cardgame.com/cardlist/',
        'ko' => 'https://onepiece-cardgame.kr/cardlist/',
        'th' => 'https://asia-th.onepiece-cardgame.com/cardlist/',
        'zh' => 'https://asia-tc.onepiece-cardgame.com/cardlist/',
    ];

    public function syncLanguage(string $lang): array
    {
        $stats = ['lang' => $lang, 'cards' => 0, 'errors' => []];

        if (!isset(self::LANG_DOMAINS[$lang])) {
            $stats['errors'][] = "Unknown language: $lang";
            return $stats;
        }

        $db = Database::getConnection();
        $sets = $db->query("SELECT DISTINCT set_id FROM cards ORDER BY set_id")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($sets as $setId) {
            echo "  Scraping $lang for set $setId...\n";

            $cards = $this->scrapeSetCards($lang, $setId);

            if ($cards === null) {
                $stats['errors'][] = "Failed to scrape set $setId for $lang";
                continue;
            }

            foreach ($cards as $cardData) {
                $this->upsertTranslation($cardData, $lang);
                $stats['cards']++;
            }

            usleep(500000);
        }

        return $stats;
    }

    private function scrapeSetCards(string $lang, string $setId): ?array
    {
        $url = self::LANG_DOMAINS[$lang];

        $html = $this->fetchPage($url);
        if (!$html) return null;

        return $this->parseCardsFromHtml($html, $setId);
    }

    private function fetchPage(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: text/html,application/xhtml+xml',
                'Accept-Language: en-US,en;q=0.9,fr;q=0.8,ja;q=0.7',
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) return null;
        return $response;
    }

    private function parseCardsFromHtml(string $html, string $filterSetId): array
    {
        $cards = [];

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_NOERROR);
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);

        $cardNodes = $xpath->query("//dl[contains(@class, 'modalCol')]");
        if (!$cardNodes || $cardNodes->length === 0) {
            $cardNodes = $xpath->query("//dl");
        }

        $regex = '/([A-Z0-9]+-[A-Z0-9]+)\|([A-Z]+)\|/';

        $allText = $doc->saveHTML();
        preg_match_all(
            '/<div[^>]*class="[^"]*modalCol[^"]*"[^>]*>.*?<\/div>/si',
            $allText,
            $matches
        );

        preg_match_all(
            '/([A-Z]{2,}\d+-\d+)\|([A-Z]+)\|([A-Z]+)\s*\n\s*(.+?)(?=\n)/m',
            strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $allText)),
            $cardMatches,
            PREG_SET_ORDER
        );

        foreach ($cardMatches as $match) {
            $cardId = trim($match[1]);
            $name = trim($match[4] ?? '');

            if (empty($cardId) || empty($name)) continue;

            $normalizedSetId = $this->normalizeSetId($cardId);
            if ($filterSetId && $normalizedSetId !== $filterSetId) continue;

            if (!isset($cards[$cardId])) {
                $cards[$cardId] = [
                    'card_set_id' => $cardId,
                    'card_name' => $name,
                    'card_text' => '',
                    'sub_types' => '',
                    'set_name' => '',
                ];
            }
        }

        return array_values($cards);
    }

    private function normalizeSetId(string $cardSetId): string
    {
        if (preg_match('/^(OP\d+|ST-?\d+|EB-?\d+|PRB-?\d+)/i', $cardSetId, $m)) {
            $id = strtoupper($m[1]);
            $id = preg_replace('/^(OP)(\d+)$/', '$1-$2', $id);
            return $id;
        }
        return '';
    }

    private function upsertTranslation(array $data, string $lang): void
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT id FROM cards WHERE card_set_id = :csi LIMIT 1");
        $stmt->execute(['csi' => $data['card_set_id']]);
        $card = $stmt->fetch();

        if (!$card) return;

        $stmt = $db->prepare(
            "INSERT INTO card_translations (card_id, lang, card_name, card_text, sub_types, set_name)
             VALUES (:card_id, :lang, :card_name, :card_text, :sub_types, :set_name)
             ON DUPLICATE KEY UPDATE
                card_name = VALUES(card_name),
                card_text = VALUES(card_text),
                sub_types = VALUES(sub_types),
                set_name = VALUES(set_name)"
        );
        $stmt->execute([
            'card_id' => $card['id'],
            'lang' => $lang,
            'card_name' => $data['card_name'],
            'card_text' => $data['card_text'] ?? '',
            'sub_types' => $data['sub_types'] ?? '',
            'set_name' => $data['set_name'] ?? '',
        ]);
    }

    public static function getAvailableLanguages(): array
    {
        return [
            'en' => 'English',
            'fr' => 'Francais',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'th' => 'Thai',
            'zh' => 'Chinese',
        ];
    }
}
