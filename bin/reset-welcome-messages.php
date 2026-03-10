<?php

declare(strict_types=1);

/**
 * Reset and resend welcome messages for all users.
 *
 * This script:
 *   1. Soft-deletes all old messages sent by the system user
 *   2. Resets welcome_sent = 0 for all non-system users
 *   3. Sends the new formatted welcome message (text + One Piece GIF) to each user
 *
 * Usage: php bin/reset-welcome-messages.php
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $_ENV['DB_HOST'], $_ENV['DB_PORT'], $_ENV['DB_DATABASE']),
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$systemRow = $pdo->query("SELECT id FROM users WHERE is_system = 1 LIMIT 1")->fetch();
if (!$systemRow) {
    echo "ERROR: System user not found.\n";
    exit(1);
}
$systemId = (int)$systemRow['id'];

// Step 1: Soft-delete all system user messages
$deleted = $pdo->prepare("UPDATE messages SET is_deleted = 1 WHERE sender_id = ?");
$deleted->execute([$systemId]);
echo "Soft-deleted " . $deleted->rowCount() . " old system messages.\n";

// Step 2: Reset welcome_sent for all real users
$pdo->prepare("UPDATE users SET welcome_sent = 0 WHERE is_system = 0")->execute();
echo "Reset welcome_sent for all users.\n";

// Step 3: Fetch a One Piece GIF from Klipy
function fetchKlipyGif(string $query): ?string
{
    $key = $_ENV['KLIPY_API_KEY'] ?? '';
    if (!$key) return null;
    $url = 'https://api.klipy.com/api/v1/' . $key . '/gifs/search?'
         . http_build_query(['q' => $query, 'page' => 1, 'per_page' => 5, 'customer_id' => 1]);
    $ctx = stream_context_create([
        'http' => ['method' => 'GET', 'timeout' => 8,
            'header' => "Content-Type: application/json\r\nUser-Agent: MyOPCards/1.0\r\n",
            'ignore_errors' => true],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    if (!$body) return null;
    $data = json_decode($body, true);
    $items = $data['data']['data'] ?? [];
    if (empty($items)) return null;
    $idx = array_rand($items);
    return $items[$idx]['file']['hd']['gif']['url']
        ?? $items[$idx]['file']['md']['gif']['url']
        ?? null;
}

$gifUrl = fetchKlipyGif('one piece luffy');
echo "GIF URL: " . ($gifUrl ?? '(none, will skip GIF)') . "\n\n";

// Step 4: Send new welcome messages to all users
$users = $pdo->query("SELECT id, username FROM users WHERE is_system = 0 ORDER BY id ASC")->fetchAll();
echo "Sending welcome messages to " . count($users) . " users...\n";

$findConv = $pdo->prepare(
    "SELECT c.id FROM conversations c
     JOIN conversation_participants cp1 ON cp1.conversation_id = c.id AND cp1.user_id = :uid
     JOIN conversation_participants cp2 ON cp2.conversation_id = c.id AND cp2.user_id = :oid"
);
$createConv    = $pdo->prepare("INSERT INTO conversations (created_at) VALUES (NOW())");
$addPart       = $pdo->prepare("INSERT IGNORE INTO conversation_participants (conversation_id, user_id) VALUES (?, ?)");
$insertText    = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, body, type) VALUES (?, ?, ?, 'text')");
$insertGif     = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, body, type, media_url) VALUES (?, ?, '', 'gif', ?)");
$markSent      = $pdo->prepare("UPDATE users SET welcome_sent = 1 WHERE id = ?");
$ensureSettings = $pdo->prepare("INSERT IGNORE INTO notification_settings (user_id) VALUES (?)");
$insertNotif   = $pdo->prepare(
    "INSERT INTO notifications (user_id, type, title, message, data) VALUES (?, 'private_message', 'New message', 'MyOPCards sent you a message', ?)"
);

$welcome  = "👋 Welcome to MyOPCards!\n\n";
$welcome .= "We're thrilled to have you join our One Piece TCG community. ";
$welcome .= "Here's everything you can do:\n\n";
$welcome .= "🃏 Browse all cards → https://myopcards.com/cards\n";
$welcome .= "📦 Manage your collection → https://myopcards.com/collection\n";
$welcome .= "⚔️ Play ranked matches → https://myopcards.com/play\n";
$welcome .= "🏆 Climb the leaderboard → https://myopcards.com/leaderboard\n";
$welcome .= "💬 Join the community forum → https://myopcards.com/forum\n\n";
$welcome .= "Make friends, build your best deck, and climb the ELO ranks. ";
$welcome .= "Good luck and have fun! 🏴‍☠️";

$ok = 0; $fail = 0;
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
            $addPart->execute([$convId, $systemId]);
            $addPart->execute([$convId, $user['id']]);
            $pdo->commit();
        }

        $insertText->execute([$convId, $systemId, $welcome]);
        if ($gifUrl) {
            $insertGif->execute([$convId, $systemId, $gifUrl]);
        }
        $markSent->execute([$user['id']]);

        $ensureSettings->execute([$user['id']]);
        $insertNotif->execute([
            $user['id'],
            json_encode(['sender_id' => $systemId, 'sender_username' => 'MyOPCards', 'conversation_id' => $convId]),
        ]);

        echo "  ✓ {$user['username']} (id={$user['id']})\n";
        $ok++;
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "  ✗ {$user['username']} (id={$user['id']}): {$e->getMessage()}\n";
        $fail++;
    }
}

echo "\nDone. Sent: $ok, Failed: $fail\n";
