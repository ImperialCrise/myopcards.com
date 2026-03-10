<?php

declare(strict_types=1);

/**
 * Run ONLY migrations 025 (messages) and 026 (user_reports).
 * Use this on production when full migrate.php fails on earlier migrations.
 */
define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_DATABASE']),
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$files = [
    BASE_PATH . '/migrations/025_create_messages.sql',
    BASE_PATH . '/migrations/026_create_user_reports.sql',
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "Skip (missing): " . basename($file) . "\n";
        continue;
    }
    $sql = file_get_contents($file);
    $sql = preg_replace('/--.*$/m', '', $sql);
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => $s !== ''
    );

    echo "Running: " . basename($file) . " ... ";

    foreach ($statements as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') continue;
        try {
            $pdo->exec($stmt . ';');
        } catch (PDOException $e) {
            $code = $e->getCode();
            $msg = $e->getMessage();
            // Duplicate column / already exists -> OK, skip
            if (str_contains($msg, 'Duplicate column') || str_contains($msg, 'already exists')) {
                echo "Skip (exists): ";
                break;
            }
            throw $e;
        }
    }
    echo "OK\n";
}

echo "\nMessages migrations completed.\n";
