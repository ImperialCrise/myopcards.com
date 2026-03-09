<?php

declare(strict_types=1);

/**
 * Pre-load all card images from optcgapi.com into MinIO.
 * Run once: php scripts/cache-card-images.php
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

use App\Core\Database;
use App\Services\StorageService;

if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

if (!StorageService::isConfigured()) {
    die("MinIO not configured. Set MINIO_ENDPOINT in .env\n");
}

$db = Database::getConnection();
$stmt = $db->query("SELECT id, card_set_id, card_image_url FROM cards WHERE card_image_url IS NOT NULL AND card_image_url != ''");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = count($rows);

echo "Caching $total card images to MinIO (bucket: cards/)\n";

$ok = 0;
$skip = 0;
$fail = 0;

foreach ($rows as $i => $row) {
    $url = trim($row['card_image_url']);
    if (empty($url)) {
        $skip++;
        continue;
    }

    $filename = basename(parse_url($url, PHP_URL_PATH));
    if (empty($filename)) {
        $skip++;
        continue;
    }

    $key = 'cards/' . $filename;

    if (StorageService::get($key) !== null) {
        $skip++;
        if (($i + 1) % 500 === 0) {
            echo '[' . ($i + 1) . "/$total] skipped (already in MinIO)\n";
        }
        continue;
    }

    $img = @file_get_contents($url);
    if ($img === false) {
        for ($retry = 0; $retry < 2; $retry++) {
            usleep(200000);
            $img = @file_get_contents($url);
            if ($img !== false) break;
        }
    }

    if ($img === false) {
        $fail++;
        echo '[' . ($i + 1) . "/$total] FAIL $filename\n";
        continue;
    }

    $ctype = 'image/jpeg';
    if (preg_match('/\.(png|gif|webp)$/i', $filename)) {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $ctype = $ext === 'png' ? 'image/png' : ($ext === 'gif' ? 'image/gif' : 'image/webp');
    }

    if (StorageService::put($key, $img, $ctype)) {
        $ok++;
        if (($i + 1) % 100 === 0 || $i === 0) {
            echo '[' . ($i + 1) . "/$total] $filename ✓\n";
        }
    } else {
        $fail++;
        echo '[' . ($i + 1) . "/$total] FAIL put $filename\n";
    }
}

echo "\nDone. OK: $ok, Skipped: $skip, Failed: $fail\n";
