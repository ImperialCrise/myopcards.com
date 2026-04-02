<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

use App\Core\SyncLogger;

$opts = getopt('', ['don-only', 'help']);
if (isset($opts['help'])) {
    echo <<<TXT
Usage:
  php bin/sync-cards.php              Full sync (boosters, starters, promos, DON!!)
  php bin/sync-cards.php --don-only   DON!! cards only (from /allDonCards/)

TXT;
    exit(0);
}

$donOnly = isset($opts['don-only']);
echo $donOnly
    ? "Starting DON!!-only sync from OPTCG API...\n"
    : "Starting card sync from OPTCG API...\n";
$log = new SyncLogger('card_sync', 'cron');

try {
    $service = new App\Services\CardSyncService();
    $stats = $donOnly ? $service->syncDonOnly() : $service->syncAll();
    $backfill = (int)($stats['sets_backfilled'] ?? 0);
    $msg = $donOnly
        ? "DON!! only — Sets touched: {$stats['sets']}, Cards: {$stats['cards']}, Sets backfilled: {$backfill}"
        : "Sets: {$stats['sets']}, Cards: {$stats['cards']}, Sets backfilled from cards: {$backfill}";
    $log->success($msg, $stats);
    echo "Sync completed: $msg\n";
    if (!empty($stats['errors'])) {
        echo "Errors:\n";
        foreach ($stats['errors'] as $error) echo "  - $error\n";
    }
} catch (\Throwable $e) {
    $log->fail($e->getMessage());
    echo "FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
