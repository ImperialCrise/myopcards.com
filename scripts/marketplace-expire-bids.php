<?php
// Run every 15 minutes: */15 * * * * php /path/to/scripts/marketplace-expire-bids.php
declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Expire bids past expires_at, release locked funds
$bidCount = \App\Services\MarketplaceService::expireBids();
echo date('Y-m-d H:i:s') . " - Expired $bidCount bids\n";

// Expire listings past expires_at
$listingCount = \App\Services\MarketplaceService::expireListings();
echo date('Y-m-d H:i:s') . " - Expired $listingCount listings\n";
