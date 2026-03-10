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

        $pendingReports = 0;
        try {
            $pendingReports = (int)$db->query("SELECT COUNT(*) FROM user_reports WHERE status = 'pending'")->fetchColumn();
        } catch (\Throwable $e) {}

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
            'forum_categories' => (int)$db->query("SELECT COUNT(*) FROM forum_categories")->fetchColumn(),
            'forum_topics' => (int)$db->query("SELECT COUNT(*) FROM forum_topics")->fetchColumn(),
            'forum_posts' => (int)$db->query("SELECT COUNT(*) FROM forum_posts")->fetchColumn(),
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
            'pendingReports' => $pendingReports,
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

    public function reports(): void
    {
        Auth::requireAdmin();
        $db = Database::getConnection();
        $reports = [];
        $pendingCount = 0;
        $filter = $_GET['status'] ?? 'all';
        try {
            $where = '1=1';
            if ($filter === 'pending') $where = "r.status = 'pending'";
            elseif ($filter === 'reviewed') $where = "r.status = 'reviewed'";
            elseif ($filter === 'dismissed') $where = "r.status = 'dismissed'";
            $reports = $db->query(
            "SELECT r.*, reporter.username as reporter_username, reported.username as reported_username
             FROM user_reports r
             JOIN users reporter ON reporter.id = r.reporter_id
             JOIN users reported ON reported.id = r.reported_id
             WHERE $where
             ORDER BY r.created_at DESC
             LIMIT 100"
            )->fetchAll();
            $pendingCount = (int)$db->query("SELECT COUNT(*) FROM user_reports WHERE status = 'pending'")->fetchColumn();
        } catch (\Throwable $e) {
            $filter = $_GET['status'] ?? 'all';
        }
        View::render('pages/admin/reports', [
            'title' => 'Admin - User Reports',
            'reports' => $reports,
            'filter' => $filter,
            'pendingCount' => $pendingCount,
        ]);
    }

    public function reviewReport(): void
    {
        Auth::requireAdmin();
        header('Content-Type: application/json');
        $reportId = (int)($_POST['report_id'] ?? 0);
        $action = $_POST['action'] ?? '';
        if ($reportId <= 0 || !in_array($action, ['dismiss', 'delete_user'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }
        $db = Database::getConnection();
        $report = $db->prepare("SELECT * FROM user_reports WHERE id = ?");
        $report->execute([$reportId]);
        $report = $report->fetch();
        if (!$report) {
            echo json_encode(['success' => false, 'message' => 'Report not found']);
            return;
        }
        if ($action === 'dismiss') {
            $db->prepare("UPDATE user_reports SET status = 'dismissed', reviewed_by = ?, reviewed_at = NOW(), admin_notes = ?")
               ->execute([Auth::id(), trim($_POST['notes'] ?? '')]);
        } elseif ($action === 'delete_user') {
            $userId = (int)$report['reported_id'];
            if ($userId !== Auth::id()) {
                $db->prepare('DELETE FROM user_cards WHERE user_id = ?')->execute([$userId]);
                $db->prepare('DELETE FROM friendships WHERE user_id = ? OR friend_id = ?')->execute([$userId, $userId]);
                $db->prepare('DELETE FROM collection_snapshots WHERE user_id = ?')->execute([$userId]);
                $db->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
            }
            $db->prepare("UPDATE user_reports SET status = 'reviewed', reviewed_by = ?, reviewed_at = NOW(), admin_notes = ?")
               ->execute([Auth::id(), trim($_POST['notes'] ?? '') . ' [User deleted]']);
        }
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

    public function forumCategories(): void
    {
        Auth::requireAdmin();
        $db = Database::getConnection();

        $stmt = $db->prepare(
            "SELECT c.*, 
                    COUNT(DISTINCT t.id) as topic_count,
                    COUNT(DISTINCT p.id) as post_count
             FROM forum_categories c
             LEFT JOIN forum_topics t ON t.category_id = c.id
             LEFT JOIN forum_posts p ON p.topic_id = t.id
             GROUP BY c.id
             ORDER BY c.sort_order ASC, c.name ASC"
        );
        $stmt->execute();
        $categories = $stmt->fetchAll();

        View::render('pages/admin/forum-categories', [
            'title' => 'Forum Categories - Admin',
            'categories' => $categories,
        ]);
    }

    public function createForumCategory(): void
    {
        Auth::requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);

            if (empty($name)) {
                $_SESSION['admin_error'] = 'Category name is required.';
                header('Location: /admin/forum-categories');
                exit;
            }

            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
            $slug = trim($slug, '-');

            $db = Database::getConnection();

            // Check if slug already exists
            $exists = $db->prepare("SELECT id FROM forum_categories WHERE slug = :slug");
            $exists->execute(['slug' => $slug]);
            if ($exists->fetch()) {
                $_SESSION['admin_error'] = 'A category with this name already exists.';
                header('Location: /admin/forum-categories');
                exit;
            }

            $stmt = $db->prepare(
                "INSERT INTO forum_categories (name, slug, description, sort_order) 
                 VALUES (:name, :slug, :description, :sort_order)"
            );
            $stmt->execute([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'sort_order' => $sortOrder,
            ]);

            // Clear cache
            \App\Core\Cache::forget('forum_categories');

            $_SESSION['admin_success'] = 'Category created successfully.';
            header('Location: /admin/forum-categories');
            exit;
        }

        View::render('pages/admin/create-forum-category', [
            'title' => 'Create Forum Category - Admin',
        ]);
    }

    public function editForumCategory(int $id): void
    {
        Auth::requireAdmin();
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM forum_categories WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $category = $stmt->fetch();

        if (!$category) {
            $_SESSION['admin_error'] = 'Category not found.';
            header('Location: /admin/forum-categories');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $sortOrder = (int)($_POST['sort_order'] ?? 0);

            if (empty($name)) {
                $_SESSION['admin_error'] = 'Category name is required.';
                header('Location: /admin/forum-categories/' . $id . '/edit');
                exit;
            }

            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
            $slug = trim($slug, '-');

            // Check if slug already exists (excluding current category)
            $exists = $db->prepare("SELECT id FROM forum_categories WHERE slug = :slug AND id != :id");
            $exists->execute(['slug' => $slug, 'id' => $id]);
            if ($exists->fetch()) {
                $_SESSION['admin_error'] = 'A category with this name already exists.';
                header('Location: /admin/forum-categories/' . $id . '/edit');
                exit;
            }

            $stmt = $db->prepare(
                "UPDATE forum_categories 
                 SET name = :name, slug = :slug, description = :description, sort_order = :sort_order 
                 WHERE id = :id"
            );
            $stmt->execute([
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'sort_order' => $sortOrder,
                'id' => $id,
            ]);

            // Clear cache
            \App\Core\Cache::forget('forum_categories');

            $_SESSION['admin_success'] = 'Category updated successfully.';
            header('Location: /admin/forum-categories');
            exit;
        }

        View::render('pages/admin/edit-forum-category', [
            'title' => 'Edit Forum Category - Admin',
            'category' => $category,
        ]);
    }

    public function deleteForumCategory(int $id): void
    {
        Auth::requireAdmin();
        $db = Database::getConnection();

        $stmt = $db->prepare("SELECT * FROM forum_categories WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $category = $stmt->fetch();

        if (!$category) {
            $_SESSION['admin_error'] = 'Category not found.';
            header('Location: /admin/forum-categories');
            exit;
        }

        // Check if category has topics
        $topicCount = $db->prepare("SELECT COUNT(*) FROM forum_topics WHERE category_id = :id");
        $topicCount->execute(['id' => $id]);
        $count = (int)$topicCount->fetchColumn();

        if ($count > 0) {
            $_SESSION['admin_error'] = 'Cannot delete category with existing topics. Move or delete topics first.';
            header('Location: /admin/forum-categories');
            exit;
        }

        $db->prepare("DELETE FROM forum_categories WHERE id = :id")->execute(['id' => $id]);

        // Clear cache
        \App\Core\Cache::forget('forum_categories');

        $_SESSION['admin_success'] = 'Category deleted successfully.';
        header('Location: /admin/forum-categories');
        exit;
    }

    public function marketplaceOverview(): void
    {
        Auth::requireAdmin();
        $db = Database::getConnection();

        $stats = [];
        try {
            $stats = [
                'active_listings' => (int)$db->query("SELECT COUNT(*) FROM marketplace_listings WHERE status = 'active'")->fetchColumn(),
                'total_listings' => (int)$db->query("SELECT COUNT(*) FROM marketplace_listings")->fetchColumn(),
                'total_orders' => (int)$db->query("SELECT COUNT(*) FROM marketplace_orders")->fetchColumn(),
                'completed_orders' => (int)$db->query("SELECT COUNT(*) FROM marketplace_orders WHERE status = 'completed'")->fetchColumn(),
                'total_volume' => (float)$db->query("SELECT COALESCE(SUM(total_price), 0) FROM marketplace_orders WHERE status IN ('completed', 'delivered')")->fetchColumn(),
                'open_disputes' => (int)$db->query("SELECT COUNT(*) FROM marketplace_disputes WHERE status = 'open'")->fetchColumn(),
                'total_wallets' => (int)$db->query("SELECT COUNT(*) FROM wallets")->fetchColumn(),
                'total_wallet_balance' => (float)$db->query("SELECT COALESCE(SUM(balance), 0) FROM wallets")->fetchColumn(),
                'pending_withdrawals' => (int)$db->query("SELECT COUNT(*) FROM wallet_transactions WHERE type = 'withdrawal' AND status = 'pending'")->fetchColumn(),
                'orders_today' => (int)$db->query("SELECT COUNT(*) FROM marketplace_orders WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
            ];
        } catch (\Throwable $e) {
            // Tables may not exist yet
            $stats = array_fill_keys([
                'active_listings', 'total_listings', 'total_orders', 'completed_orders',
                'total_volume', 'open_disputes', 'total_wallets', 'total_wallet_balance',
                'pending_withdrawals', 'orders_today',
            ], 0);
        }

        // Recent orders
        $recentOrders = [];
        try {
            $recentOrders = $db->query(
                "SELECT mo.*, seller.username as seller_username, buyer.username as buyer_username
                 FROM marketplace_orders mo
                 JOIN users seller ON seller.id = mo.seller_id
                 JOIN users buyer ON buyer.id = mo.buyer_id
                 ORDER BY mo.created_at DESC
                 LIMIT 20"
            )->fetchAll();
        } catch (\Throwable $e) {}

        View::render('pages/admin/marketplace', [
            'title' => 'Admin - Marketplace Overview',
            'stats' => $stats,
            'recentOrders' => $recentOrders,
        ]);
    }

    public function marketplaceDisputes(): void
    {
        Auth::requireAdmin();
        $db = Database::getConnection();

        $filter = $_GET['status'] ?? 'open';
        $validStatuses = ['open', 'resolved', 'all'];
        if (!in_array($filter, $validStatuses)) {
            $filter = 'open';
        }

        $disputes = [];
        try {
            $where = $filter === 'all' ? '1=1' : "md.status = '$filter'";
            $disputes = $db->query(
                "SELECT md.*, mo.total_price, mo.status as order_status,
                        opener.username as opener_username,
                        seller.username as seller_username,
                        buyer.username as buyer_username,
                        c.card_name, c.card_set_id
                 FROM marketplace_disputes md
                 JOIN marketplace_orders mo ON mo.id = md.order_id
                 JOIN marketplace_listings ml ON ml.id = mo.listing_id
                 JOIN cards c ON c.card_set_id = ml.card_set_id
                 JOIN users opener ON opener.id = md.opened_by
                 JOIN users seller ON seller.id = mo.seller_id
                 JOIN users buyer ON buyer.id = mo.buyer_id
                 WHERE $where
                 ORDER BY md.created_at DESC
                 LIMIT 100"
            )->fetchAll();
        } catch (\Throwable $e) {}

        View::render('pages/admin/marketplace-disputes', [
            'title' => 'Admin - Marketplace Disputes',
            'disputes' => $disputes,
            'filter' => $filter,
        ]);
    }

    public function resolveDispute(int $id): void
    {
        Auth::requireAdmin();
        header('Content-Type: application/json');

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $resolution = trim($input['resolution'] ?? '');
        $action = trim($input['action'] ?? '');
        $notes = trim($input['notes'] ?? '');

        if (empty($resolution) || empty($action)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Resolution and action are required']);
            return;
        }

        $validActions = ['refund_buyer', 'release_to_seller', 'partial_refund', 'no_action'];
        if (!in_array($action, $validActions)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            return;
        }

        try {
            $result = \App\Services\MarketplaceService::resolveDispute($id, Auth::id(), $resolution, $action, $notes);
            echo json_encode(['success' => true, 'message' => 'Dispute resolved', 'result' => $result]);
        } catch (\Throwable $e) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
