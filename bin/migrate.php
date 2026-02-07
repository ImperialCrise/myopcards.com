<?php

declare(strict_types=1);

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

$migrations = glob(BASE_PATH . '/migrations/*.sql');
sort($migrations);

foreach ($migrations as $file) {
    $sql = file_get_contents($file);
    echo "Running: " . basename($file) . " ... ";
    $pdo->exec($sql);
    echo "OK\n";
}

echo "\nAll migrations completed.\n";
