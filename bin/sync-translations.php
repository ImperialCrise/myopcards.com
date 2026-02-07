<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

$lang = $argv[1] ?? null;

if (!$lang) {
    $dayOfWeek = (int)date('N');
    $langSchedule = ['en', 'fr', 'ja', 'ko', 'th', 'zh', 'en'];
    $lang = $langSchedule[$dayOfWeek - 1] ?? 'en';
    echo "Auto-selected language for today: $lang\n";
}

echo "Syncing translations for: $lang\n";
$start = microtime(true);

$scraper = new App\Services\OfficialSiteScraper();
$stats = $scraper->syncLanguage($lang);

$elapsed = round(microtime(true) - $start, 2);

echo "\nTranslation sync completed in {$elapsed}s\n";
echo "Cards processed: {$stats['cards']}\n";

if (!empty($stats['errors'])) {
    echo "\nErrors:\n";
    foreach ($stats['errors'] as $error) {
        echo "  - $error\n";
    }
}
