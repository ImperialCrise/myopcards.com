<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

use App\Core\Database;

echo "Snapshotting collection values...\n";
$start = microtime(true);

$db = Database::getConnection();

$users = $db->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
$count = 0;

foreach ($users as $userId) {
    $stmt = $db->prepare(
        "SELECT
            COUNT(DISTINCT uc.card_id) as unique_cards,
            COALESCE(SUM(uc.quantity), 0) as total_cards,
            COALESCE(SUM(c.market_price * uc.quantity), 0) as total_value_usd,
            COALESCE(SUM(c.cardmarket_price * uc.quantity), 0) as total_value_eur
         FROM user_cards uc
         JOIN cards c ON c.id = uc.card_id
         WHERE uc.user_id = :uid AND uc.is_wishlist = 0"
    );
    $stmt->execute(['uid' => $userId]);
    $data = $stmt->fetch();

    $stmt2 = $db->prepare(
        "INSERT INTO collection_snapshots (user_id, total_value_usd, total_value_eur, unique_cards, total_cards, snapshot_date)
         VALUES (:uid, :usd, :eur, :uc, :tc, CURDATE())
         ON DUPLICATE KEY UPDATE
            total_value_usd = VALUES(total_value_usd),
            total_value_eur = VALUES(total_value_eur),
            unique_cards = VALUES(unique_cards),
            total_cards = VALUES(total_cards)"
    );
    $stmt2->execute([
        'uid' => $userId,
        'usd' => $data['total_value_usd'],
        'eur' => $data['total_value_eur'] ?: null,
        'uc' => $data['unique_cards'],
        'tc' => $data['total_cards'],
    ]);
    $count++;
}

$elapsed = round(microtime(true) - $start, 2);
echo "Snapshots created for $count users in {$elapsed}s\n";
