<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\SyncLogger;
use App\Core\View;
use PDO;

class AdminController
{
    public function dashboard(): void
    {
        Auth::requireAdmin();
        $db = Database::getConnection();

        $stats = [
            'total_users' => (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'total_cards' => (int)$db->query('SELECT COUNT(*) FROM cards')->fetchColumn(),
            'total_sets' => (int)$db->query('SELECT COUNT(*) FROM sets')->fetchColumn(),
            'total_collections' => (int)$db->query('SELECT COUNT(DISTINCT user_id) FROM user_cards')->fetchColumn(),
            'total_cards_owned' => (int)$db->query('SELECT COALESCE(SUM(quantity),0) FROM user_cards')->fetchColumn(),
            'cards_with_price' => (int)$db->query('SELECT COUNT(*) FROM cards WHERE market_price > 0')->fetchColumn(),
            'cards_with_eur' => (int)$db->query('SELECT COUNT(*) FROM cards WHERE price_en > 0 OR price_fr > 0')->fetchColumn(),
            'price_history_count' => (int)$db->query('SELECT COUNT(*) FROM price_history')->fetchColumn(),
            'new_users_today' => (int)$db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
            'last_sync' => $db->query("SELECT MAX(last_synced_at) FROM cards")->fetchColumn(),
            'last_price_update' => $db->query("SELECT MAX(price_updated_at) FROM cards WHERE price_updated_at IS NOT NULL")->fetchColumn(),
        ];

        $recentUsers = $db->query('SELECT id, username, email, created_at, is_admin FROM users ORDER BY id DESC LIMIT 10')->fetchAll();

        $priceSources = $db->query("SELECT source, edition, COUNT(*) as cnt, MAX(recorded_at) as latest FROM price_history GROUP BY source, edition")->fetchAll();

        View::render('pages/admin/dashboard', [
            'title' => 'Admin Dashboard',
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'priceSources' => $priceSources,
        ]);
    }

    public function users(): void
    {
        Auth::requireAdmin();
        $db = Database::getConnection();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $search = trim($_GET['q'] ?? '');
        $where = '1=1';
        $params = [];
        if ($search) {
            $where = '(username LIKE :q OR email LIKE :q2)';
            $params = ['q' => "%$search%", 'q2' => "%$search%"];
        }

        $total = (int)$db->prepare("SELECT COUNT(*) FROM users WHERE $where")->execute($params) ? (int)$db->query("SELECT FOUND_ROWS()")->fetchColumn() : 0;
        $stmt = $db->prepare("SELECT SQL_CALC_FOUND_ROWS u.*, (SELECT COUNT(*) FROM user_cards uc WHERE uc.user_id = u.id) as card_count, (SELECT COALESCE(SUM(c.market_price * uc2.quantity),0) FROM user_cards uc2 JOIN cards c ON c.id = uc2.card_id WHERE uc2.user_id = u.id) as collection_value FROM users u WHERE $where ORDER BY u.id DESC LIMIT $perPage OFFSET $offset");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $users = $stmt->fetchAll();
        $total = (int)$db->query("SELECT FOUND_ROWS()")->fetchColumn();

        View::render('pages/admin/users', [
            'title' => 'Admin - Users',
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'totalPages' => (int)ceil($total / $perPage),
            'search' => $search,
        ]);
    }

    public function toggleAdmin(): void
    {
        Auth::requireAdmin();
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId === Auth::id()) {
            echo json_encode(['success' => false, 'message' => 'Cannot change own admin status']);
            return;
        }
        $db = Database::getConnection();
        $db->prepare('UPDATE users SET is_admin = NOT is_admin WHERE id = :id')->execute(['id' => $userId]);
        echo json_encode(['success' => true]);
    }

    public function deleteUser(): void
    {
        Auth::requireAdmin();
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId === Auth::id()) {
            echo json_encode(['success' => false, 'message' => 'Cannot delete yourself']);
            return;
        }
        $db = Database::getConnection();
        $db->prepare('DELETE FROM user_cards WHERE user_id = :id')->execute(['id' => $userId]);
        $db->prepare('DELETE FROM friendships WHERE user_id = :id OR friend_id = :id2')->execute(['id' => $userId, 'id2' => $userId]);
        $db->prepare('DELETE FROM collection_snapshots WHERE user_id = :id')->execute(['id' => $userId]);
        $db->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $userId]);
        echo json_encode(['success' => true]);
    }

    public function cards(): void
    {
        Auth::requireAdmin();
        $db = Database::getConnection();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 50;
        $offset = ($page - 1) * $perPage;

        $search = trim($_GET['q'] ?? '');
        $filter = $_GET['filter'] ?? '';
        $where = '1=1';
        $params = [];
        if ($search) {
            $where .= ' AND (card_name LIKE :q OR card_set_id LIKE :q2)';
            $params = ['q' => "%$search%", 'q2' => "%$search%"];
        }
        if ($filter === 'no_price') $where .= ' AND (market_price IS NULL OR market_price = 0)';
        if ($filter === 'no_eur') $where .= ' AND (price_en IS NULL OR price_en = 0) AND (price_fr IS NULL OR price_fr = 0)';
        if ($filter === 'parallel') $where .= ' AND is_parallel = 1';

        $stmt = $db->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM cards WHERE $where ORDER BY card_set_id ASC LIMIT $perPage OFFSET $offset");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $cards = $stmt->fetchAll();
        $total = (int)$db->query("SELECT FOUND_ROWS()")->fetchColumn();

        View::render('pages/admin/cards', [
            'title' => 'Admin - Cards',
            'cards' => $cards,
            'total' => $total,
            'page' => $page,
            'totalPages' => (int)ceil($total / $perPage),
            'search' => $search,
            'filter' => $filter,
        ]);
    }

    public function prices(): void
    {
        Auth::requireAdmin();
        $db = Database::getConnection();

        $stats = [
            'usd_count' => (int)$db->query('SELECT COUNT(*) FROM cards WHERE market_price > 0')->fetchColumn(),
            'eur_en_count' => (int)$db->query('SELECT COUNT(*) FROM cards WHERE price_en > 0')->fetchColumn(),
            'eur_fr_count' => (int)$db->query('SELECT COUNT(*) FROM cards WHERE price_fr > 0')->fetchColumn(),
            'eur_jp_count' => (int)$db->query('SELECT COUNT(*) FROM cards WHERE price_jp > 0')->fetchColumn(),
            'total_cards' => (int)$db->query('SELECT COUNT(*) FROM cards')->fetchColumn(),
            'last_tcg_update' => $db->query("SELECT MAX(recorded_at) FROM price_history WHERE source = 'tcgplayer'")->fetchColumn(),
            'last_cm_update' => $db->query("SELECT MAX(recorded_at) FROM price_history WHERE source = 'cardmarket'")->fetchColumn(),
        ];

        $history = $db->query("SELECT source, edition, COUNT(*) as cnt, MIN(recorded_at) as earliest, MAX(recorded_at) as latest FROM price_history GROUP BY source, edition ORDER BY source, edition")->fetchAll();

        $flaresolverrOk = false;
        try {
            $scraper = new \App\Services\CardmarketScraper();
            $flaresolverrOk = $scraper->isAvailable();
        } catch (\Throwable $e) {}

        View::render('pages/admin/prices', [
            'title' => 'Admin - Prices',
            'stats' => $stats,
            'history' => $history,
            'flaresolverrOk' => $flaresolverrOk,
        ]);
    }

    public function syncCards(): void
    {
        Auth::requireAdmin();
        header('Content-Type: application/json');
        $log = new SyncLogger('card_sync', 'admin:' . Auth::user()['username']);

        try {
            $service = new \App\Services\CardSyncService();
            $result = $service->syncAll();
            $msg = "Synced {$result['cards']} cards, {$result['sets']} sets";
            $log->success($msg, $result);
            echo json_encode(['success' => true, 'message' => $msg, 'errors' => $result['errors']]);
        } catch (\Throwable $e) {
            $log->fail($e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function syncPricesTcg(): void
    {
        Auth::requireAdmin();
        header('Content-Type: application/json');
        $log = new SyncLogger('price_tcgplayer', 'admin:' . Auth::user()['username']);

        try {
            $service = new \App\Services\PriceUpdateService();
            $result = $service->updateTcgplayerPrices();
            $msg = "Updated {$result['updated']} TCGPlayer prices";
            $log->success($msg, $result);
            echo json_encode(['success' => true, 'message' => $msg]);
        } catch (\Throwable $e) {
            $log->fail($e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function syncPricesCardmarket(): void
    {
        Auth::requireAdmin();
        header('Content-Type: application/json');

        $edition = $_POST['edition'] ?? 'en';
        $limit = (int)($_POST['limit'] ?? 50);
        $log = new SyncLogger('price_cardmarket_' . $edition, 'admin:' . Auth::user()['username']);

        try {
            $scraper = new \App\Services\CardmarketScraper();
            $result = $scraper->scrapeCardsForToday($limit, $edition);
            $msg = "Scraped $limit cards for edition '$edition'";
            $log->success($msg, $result);
            echo json_encode(['success' => true, 'message' => $msg, 'result' => $result]);
        } catch (\Throwable $e) {
            $log->fail($e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function syncSnapshot(): void
    {
        Auth::requireAdmin();
        header('Content-Type: application/json');
        $log = new SyncLogger('snapshot', 'admin:' . Auth::user()['username']);

        try {
            $db = Database::getConnection();
            $users = $db->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
            $count = 0;
            foreach ($users as $userId) {
                $stmt = $db->prepare(
                    "SELECT COUNT(DISTINCT uc.card_id) as uc, COALESCE(SUM(uc.quantity),0) as tc,
                            COALESCE(SUM(c.market_price*uc.quantity),0) as usd,
                            COALESCE(SUM(COALESCE(c.price_en,c.cardmarket_price,0)*uc.quantity),0) as eur
                     FROM user_cards uc JOIN cards c ON c.id=uc.card_id WHERE uc.user_id=:uid AND uc.is_wishlist=0"
                );
                $stmt->execute(['uid' => $userId]);
                $d = $stmt->fetch();
                $db->prepare(
                    "INSERT INTO collection_snapshots (user_id,total_value_usd,total_value_eur,unique_cards,total_cards,snapshot_date)
                     VALUES(:uid,:usd,:eur,:uc,:tc,CURDATE())
                     ON DUPLICATE KEY UPDATE total_value_usd=VALUES(total_value_usd),total_value_eur=VALUES(total_value_eur),unique_cards=VALUES(unique_cards),total_cards=VALUES(total_cards)"
                )->execute(['uid'=>$userId,'usd'=>$d['usd'],'eur'=>$d['eur']?:null,'uc'=>$d['uc'],'tc'=>$d['tc']]);
                $count++;
            }
            $msg = "Snapshots taken for $count users";
            $log->success($msg, ['users' => $count]);
            echo json_encode(['success' => true, 'message' => $msg]);
        } catch (\Throwable $e) {
            $log->fail($e->getMessage());
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function logs(): void
    {
        Auth::requireAdmin();
        $logs = SyncLogger::getRecent(100);

        View::render('pages/admin/logs', [
            'title' => 'Admin - Sync Logs',
            'logs' => $logs,
        ]);
    }

    public function editCard(): void
    {
        Auth::requireAdmin();
        $cardSetId = $_GET['id'] ?? '';
        $db = Database::getConnection();
        $card = $db->prepare("SELECT * FROM cards WHERE card_set_id = :csi")->execute(['csi' => $cardSetId]);
        $card = $db->prepare("SELECT * FROM cards WHERE card_set_id = :csi");
        $card->execute(['csi' => $cardSetId]);
        $card = $card->fetch();

        if (!$card) {
            header('Location: /admin/cards');
            return;
        }

        View::render('pages/admin/card-edit', [
            'title' => 'Edit Card - ' . $card['card_set_id'],
            'card' => $card,
        ]);
    }

    public function updateCard(): void
    {
        Auth::requireAdmin();
        header('Content-Type: application/json');

        $id = (int)($_POST['card_id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Missing card ID']);
            return;
        }

        $db = Database::getConnection();
        $allowed = ['card_name','market_price','price_en','price_fr','price_jp','cardmarket_price','cardmarket_url','rarity','card_color','card_type'];
        $updates = [];
        $params = ['id' => $id];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $_POST)) {
                $val = $_POST[$col];
                if (in_array($col, ['market_price','price_en','price_fr','price_jp','cardmarket_price'])) {
                    $val = $val === '' ? null : (float)$val;
                }
                $updates[] = "$col = :$col";
                $params[$col] = $val;
            }
        }

        if (empty($updates)) {
            echo json_encode(['success' => false, 'message' => 'No fields to update']);
            return;
        }

        $sql = "UPDATE cards SET " . implode(', ', $updates) . ", price_updated_at = NOW() WHERE id = :id";
        $db->prepare($sql)->execute($params);
        echo json_encode(['success' => true, 'message' => 'Card updated']);
    }

    public function importPrices(): void
    {
        Auth::requireAdmin();

        if (empty($_FILES['csv']['tmp_name'])) {
            header('Location: /admin/prices');
            return;
        }

        $db = Database::getConnection();
        $file = fopen($_FILES['csv']['tmp_name'], 'r');
        $header = fgetcsv($file);

        $colMap = array_flip(array_map('strtolower', array_map('trim', $header)));
        $idIdx = $colMap['card_set_id'] ?? $colMap['id'] ?? null;

        if ($idIdx === null) {
            fclose($file);
            header('Location: /admin/prices?error=missing_card_set_id');
            return;
        }

        $updated = 0;
        while (($row = fgetcsv($file)) !== false) {
            $cardSetId = trim($row[$idIdx] ?? '');
            if (empty($cardSetId)) continue;

            $updates = [];
            $params = ['csi' => $cardSetId];

            foreach (['price_en', 'price_fr', 'price_jp'] as $col) {
                $idx = $colMap[$col] ?? null;
                if ($idx !== null && isset($row[$idx]) && $row[$idx] !== '') {
                    $updates[] = "$col = :$col";
                    $params[$col] = (float)str_replace(',', '.', $row[$idx]);
                }
            }

            if (!empty($updates)) {
                $updates[] = "cardmarket_price = COALESCE(cardmarket_price, :fallback)";
                $params['fallback'] = $params['price_en'] ?? $params['price_fr'] ?? $params['price_jp'] ?? 0;
                $sql = "UPDATE cards SET " . implode(', ', $updates) . ", price_updated_at = NOW() WHERE card_set_id = :csi";
                $db->prepare($sql)->execute($params);
                $updated++;
            }
        }
        fclose($file);

        header("Location: /admin/prices?imported=$updated");
    }
}
