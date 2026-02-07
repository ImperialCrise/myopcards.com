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
        ];

        $page = max(1, (int)($_GET['page'] ?? 1));
        $result = Card::search($filters, $page);

        $userCards = [];
        if (Auth::check()) {
            $userCards = Collection::getUserCardIds(Auth::id());
        }

        View::render('pages/cards', [
            'title' => 'Card Database',
            'result' => $result,
            'filters' => $filters,
            'sets' => Card::getDistinctValues('set_id'),
            'colors' => Card::getDistinctValues('card_color'),
            'rarities' => Card::getDistinctValues('rarity'),
            'types' => Card::getDistinctValues('card_type'),
            'userCards' => $userCards,
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

        View::render('pages/card-detail', [
            'title' => $card['card_name'] . ' - ' . $card['set_name'],
            'card' => $card,
            'userOwns' => $userOwns,
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
        $tcg = PriceHistory::getForCard($card['id'], 'tcgplayer', $days);
        $cm = PriceHistory::getForCard($card['id'], 'cardmarket', $days);

        echo json_encode(['tcgplayer' => $tcg, 'cardmarket' => $cm]);
    }
}
