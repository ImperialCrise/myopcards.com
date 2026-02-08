<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Models\PriceHistory;

class MarketController
{
    public function index(): void
    {
        $expensive = PriceHistory::getMostExpensive(20);
        $collected = PriceHistory::getMostCollected(20);
        $recentCards = PriceHistory::getRecentlyAdded(12);
        $setSummary = PriceHistory::getSetValueSummary();

        View::render('pages/market', [
            'title' => 'One Piece TCG Market Prices & Trends',
            'expensive' => $expensive,
            'collected' => $collected,
            'recentCards' => $recentCards,
            'setSummary' => $setSummary,
            'seoDescription' => 'Track One Piece TCG card prices and market trends. See the most expensive cards, top movers, set value summaries, and price history from TCGPlayer and Cardmarket.',
            'seoKeywords' => 'One Piece TCG prices, OPTCG market, card price tracker, most expensive One Piece cards, TCGPlayer prices, Cardmarket prices, card value trends',
        ]);
    }

    public function movers(): void
    {
        header('Content-Type: application/json');

        $direction = $_GET['direction'] ?? 'up';
        $days = max(1, min(30, (int)($_GET['days'] ?? 7)));
        $source = $_GET['source'] ?? 'tcgplayer';

        $movers = PriceHistory::getTopMovers($source, $days, 10, $direction);
        echo json_encode($movers);
    }
}
