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
            'title' => 'Market Overview',
            'expensive' => $expensive,
            'collected' => $collected,
            'recentCards' => $recentCards,
            'setSummary' => $setSummary,
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
