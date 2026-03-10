<?php

declare(strict_types=1);

/**
 * One-shot script: send welcome message from MyOPCards to all existing users
 * who have not yet received one (welcome_sent = 0).
 *
 * Usage: php bin/send-welcome-messages.php
 * Safe to run multiple times — skips users who already have welcome_sent = 1.
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// Bootstrap minimum needed
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_DATABASE']),
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

// Use the app's autoloader for models
spl_autoload_register(function (string $class) {
    $file = BASE_PATH . '/src/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Find users who haven't received a welcome message
$stmt = $pdo->query("SELECT id, username FROM users WHERE welcome_sent = 0 AND is_system = 0 ORDER BY id ASC");
$users = $stmt->fetchAll();

if (empty($users)) {
    echo "No users to process.\n";
    exit(0);
}

echo "Sending welcome messages to " . count($users) . " users...\n";

$systemRow = $pdo->query("SELECT id FROM users WHERE is_system = 1 LIMIT 1")->fetch();
if (!$systemRow) {
    echo "ERROR: System user not found. Run migration 028 first.\n";
    exit(1);
}
$systemId = (int)$systemRow['id'];

$welcome = "👋 Welcome to MyOPCards!\n\nWe're glad to have you here. This is your One Piece TCG hub — browse cards, manage your collection, track prices, play matches and connect with other players.\n\n🃏 Browse cards at /cards\n📊 Manage your collection at /collection\n⚔️ Play matches at /play\n🏆 Check the leaderboard at /leaderboard\n\nJoin our Discord to connect with the community. Have fun and good luck!";

$findConv = $pdo->prepare(
    "SELECT c.id FROM conversations c
     JOIN conversation_participants cp1 ON cp1.conversation_id = c.id AND cp1.user_id = :uid
     JOIN conversation_participants cp2 ON cp2.conversation_id = c.id AND cp2.user_id = :oid"
);
$createConv = $pdo->prepare("INSERT INTO conversations (created_at) VALUES (NOW())");
$addParticipant = $pdo->prepare("INSERT IGNORE INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)");
$insertMsg = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, body, type) VALUES (?, ?, ?, 'text')");
$markSent = $pdo->prepare("UPDATE users SET welcome_sent = 1 WHERE id = ?");

$ok = 0;
$skip = 0;
foreach ($users as $user) {
    try {
        $findConv->execute(['uid' => $systemId, 'oid' => $user['id']]);
        $row = $findConv->fetch();
        if ($row) {
            $convId = (int)$row['id'];
        } else {
            $pdo->beginTransaction();
            $createConv->execute();
            $convId = (int)$pdo->lastInsertId();
            $addParticipant->execute([$convId, $systemId]);
            $addParticipant->execute([$convId, $user['id']]);
            $pdo->commit();
        }
        $insertMsg->execute([$convId, $systemId, $welcome]);
        $markSent->execute([$user['id']]);
        echo "  ✓ {$user['username']} (id={$user['id']})\n";
        $ok++;
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo "  ✗ {$user['username']} (id={$user['id']}): {$e->getMessage()}\n";
        $skip++;
    }
}

echo "\nDone. Sent: $ok, Failed: $skip\n";
