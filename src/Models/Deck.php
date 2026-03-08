<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Deck
{
    public static function getByUserId(int $userId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT d.*, c.card_name AS leader_name, c.card_set_id AS leader_set_id, c.card_image_url AS leader_image_url, c.card_color AS leader_color,
             (SELECT COALESCE(SUM(dc.quantity), 0) FROM deck_cards dc WHERE dc.deck_id = d.id) AS card_count
             FROM decks d
             JOIN cards c ON c.id = d.leader_card_id
             WHERE d.user_id = :user_id
             ORDER BY d.updated_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public static function getById(int $deckId, int $userId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare(
            'SELECT d.*, c.card_name AS leader_name, c.card_set_id AS leader_set_id, c.card_image_url AS leader_image_url, c.card_color AS leader_color
             FROM decks d
             JOIN cards c ON c.id = d.leader_card_id
             WHERE d.id = :id AND d.user_id = :user_id'
        );
        $stmt->execute(['id' => $deckId, 'user_id' => $userId]);
        $deck = $stmt->fetch();
        if (!$deck) {
            return null;
        }
        $stmt2 = $db->prepare(
            'SELECT dc.card_id, dc.quantity, ca.card_name, ca.card_set_id, ca.card_type, ca.card_color, ca.card_cost, ca.card_power, ca.card_image_url
             FROM deck_cards dc
             JOIN cards ca ON ca.id = dc.card_id
             WHERE dc.deck_id = :deck_id
             ORDER BY ca.card_type, ca.card_name'
        );
        $stmt2->execute(['deck_id' => $deckId]);
        $deck['cards'] = $stmt2->fetchAll();
        return $deck;
    }

    public static function save(int $userId, array $payload): array
    {
        $deckId = isset($payload['id']) ? (int)$payload['id'] : 0;
        $name = trim($payload['name'] ?? '');
        $leaderCardId = (int)($payload['leader_card_id'] ?? 0);
        $cards = $payload['cards'] ?? [];

        if ($name === '') {
            return ['success' => false, 'error' => 'Deck name is required.'];
        }

        $leader = Card::findById($leaderCardId);
        if (!$leader || ($leader['card_type'] ?? '') !== 'Leader') {
            return ['success' => false, 'error' => 'Valid Leader card is required.'];
        }

        $allowedColors = self::leaderAllowedColors($leader['card_color'] ?? '');
        $totalQty = 0;
        $byCard = [];

        foreach ($cards as $entry) {
            $cardId = (int)($entry['card_id'] ?? 0);
            $qty = max(0, min(4, (int)($entry['quantity'] ?? 0)));
            if ($cardId <= 0 || $qty === 0) {
                continue;
            }
            $card = Card::findById($cardId);
            if (!$card) {
                continue;
            }
            if (($card['card_type'] ?? '') === 'Leader') {
                return ['success' => false, 'error' => 'Deck list cannot include a Leader; select the leader separately.'];
            }
            $cardColor = trim($card['card_color'] ?? '');
            if ($cardColor !== '' && !self::colorMatchesLeader($cardColor, $allowedColors)) {
                return ['success' => false, 'error' => sprintf('Card "%s" color does not match the leader.', $card['card_name'] ?? '')];
            }
            $byCard[$cardId] = ($byCard[$cardId] ?? 0) + $qty;
            $totalQty += $qty;
        }

        foreach ($byCard as $cid => $q) {
            if ($q > 4) {
                return ['success' => false, 'error' => 'Maximum 4 copies of any card allowed.'];
            }
        }

        if ($totalQty !== 50) {
            return ['success' => false, 'error' => 'Deck must contain exactly 50 cards (excluding the leader).'];
        }

        $db = Database::getConnection();

        if ($deckId > 0) {
            $stmt = $db->prepare('SELECT id FROM decks WHERE id = :id AND user_id = :user_id');
            $stmt->execute(['id' => $deckId, 'user_id' => $userId]);
            if (!$stmt->fetch()) {
                return ['success' => false, 'error' => 'Deck not found.'];
            }
            $stmt = $db->prepare('UPDATE decks SET name = :name, leader_card_id = :leader_card_id, updated_at = NOW() WHERE id = :id');
            $stmt->execute(['name' => $name, 'leader_card_id' => $leaderCardId, 'id' => $deckId]);
        } else {
            $stmt = $db->prepare('INSERT INTO decks (user_id, name, leader_card_id, is_active) VALUES (:user_id, :name, :leader_card_id, 0)');
            $stmt->execute(['user_id' => $userId, 'name' => $name, 'leader_card_id' => $leaderCardId]);
            $deckId = (int)$db->lastInsertId();
        }

        $db->prepare('DELETE FROM deck_cards WHERE deck_id = :deck_id')->execute(['deck_id' => $deckId]);
        $ins = $db->prepare('INSERT INTO deck_cards (deck_id, card_id, quantity) VALUES (:deck_id, :card_id, :quantity)');
        foreach ($byCard as $cardId => $qty) {
            $ins->execute(['deck_id' => $deckId, 'card_id' => $cardId, 'quantity' => $qty]);
        }

        return ['success' => true, 'id' => $deckId];
    }

    public static function delete(int $deckId, int $userId): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('DELETE FROM decks WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $deckId, 'user_id' => $userId]);
        return $stmt->rowCount() > 0;
    }

    public static function getDeckCount(int $userId): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT COUNT(*) FROM decks WHERE user_id = :user_id');
        $stmt->execute(['user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public static function leaderAllowedColors(string $leaderColor): array
    {
        if ($leaderColor === '') {
            return [];
        }
        $parts = preg_split('/[\s\/]+/', $leaderColor);
        $parts = array_map('trim', $parts);
        return array_values(array_filter($parts));
    }

    public static function colorMatchesLeader(string $cardColor, array $allowedColors): bool
    {
        if (empty($allowedColors)) {
            return true;
        }
        $cardColors = preg_split('/[\s\/]+/', $cardColor);
        $cardColors = array_map('trim', $cardColors);
        foreach ($cardColors as $c) {
            if ($c !== '' && in_array($c, $allowedColors, true)) {
                return true;
            }
        }
        return false;
    }
}
