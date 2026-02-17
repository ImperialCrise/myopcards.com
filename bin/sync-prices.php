<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

use App\Core\SyncLogger;

$source = $argv[1] ?? 'tcgplayer';
echo "Starting price sync for: $source\n";

if ($source === 'tcgplayer') {
    $log = new SyncLogger('price_tcgplayer', 'cron');
    try {
        $service = new App\Services\PriceUpdateService();
        $stats = $service->updateTcgplayerPrices();
        $msg = "Updated: {$stats['updated']}";
        $log->success($msg, $stats);
        echo "TCGPlayer: $msg\n";
    } catch (\Throwable $e) {
        $log->fail($e->getMessage());
        echo "FAILED: " . $e->getMessage() . "\n";
        exit(1);
    }
} elseif ($source === 'cardmarket') {
    $edition = $argv[2] ?? 'en';
    $log = new SyncLogger('price_cardmarket_' . $edition, 'cron');
    try {
        $scraper = new App\Services\CardmarketScraper();
        $limit = (int)($argv[3] ?? 100);
        $stats = $scraper->scrapeCardsForToday($limit, $edition);
        $msg = "Updated: {$stats['updated']}, Failed: {$stats['failed']}";
        $log->success($msg, $stats);
        echo "Cardmarket ($edition): $msg\n";
    } catch (\Throwable $e) {
        $log->fail($e->getMessage());
        echo "FAILED: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "Unknown source: $source. Use 'tcgplayer' or 'cardmarket'.\n";
    exit(1);
}

echo "Price sync done.\n";
