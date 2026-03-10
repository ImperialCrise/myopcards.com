<?php
// Run every hour: */60 * * * * php /path/to/scripts/marketplace-auto-complete.php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Auto-complete shipped orders past auto_complete_at
$count = \App\Services\MarketplaceService::autoCompleteOrders();
echo date('Y-m-d H:i:s') . " - Auto-completed $count orders\n";
