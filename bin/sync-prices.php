<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

$source = $argv[1] ?? 'tcgplayer';
echo "Starting price sync for: $source\n";
$start = microtime(true);

if ($source === 'tcgplayer') {
    $service = new App\Services\PriceUpdateService();
    $stats = $service->updateTcgplayerPrices();
    echo "TCGPlayer prices updated: {$stats['updated']}\n";
} elseif ($source === 'cardmarket') {
    $scraper = new App\Services\CardmarketScraper();
    $stats = $scraper->scrapeCardsForToday(670);
    echo "Cardmarket: {$stats['updated']} updated, {$stats['failed']} failed, {$stats['skipped']} skipped\n";
} else {
    echo "Unknown source: $source. Use 'tcgplayer' or 'cardmarket'.\n";
    exit(1);
}

$elapsed = round(microtime(true) - $start, 2);
echo "Price sync completed in {$elapsed}s\n";
