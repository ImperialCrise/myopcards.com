<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

use App\Core\SyncLogger;

echo "Starting card sync from OPTCG API...\n";
$log = new SyncLogger('card_sync', 'cron');

try {
    $service = new App\Services\CardSyncService();
    $stats = $service->syncAll();
    $msg = "Sets: {$stats['sets']}, Cards: {$stats['cards']}";
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
