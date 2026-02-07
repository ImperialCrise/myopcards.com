<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

echo "Starting card sync from OPTCG API...\n";
$start = microtime(true);

$service = new App\Services\CardSyncService();
$stats = $service->syncAll();

$elapsed = round(microtime(true) - $start, 2);

echo "\nSync completed in {$elapsed}s\n";
echo "Sets synced: {$stats['sets']}\n";
echo "Cards synced: {$stats['cards']}\n";

if (!empty($stats['errors'])) {
    echo "\nErrors:\n";
    foreach ($stats['errors'] as $error) {
        echo "  - $error\n";
    }
}
