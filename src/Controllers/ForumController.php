<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Cache;
use App\Core\Database;
use App\Core\View;
use App\Services\NotificationService;
use App\Services\StorageService;
use PDO;

class ForumController
{
    private const TOPICS_PER_PAGE = 20;
    private const POSTS_PER_PAGE = 15;
    private const MAX_UPLOAD_SIZE = 5 * 1024 * 1024;
    private const ALLOWED_IMAGES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const UPLOAD_DIR = BASE_PATH . '/public/uploads/forum/';

    public function index(): void
    {
        $db = Database::getConnection();

        $categories = Cache::remember('forum_categories', function () use ($db) {
            return $db->query(
                "SELECT c.*, 
                        (SELECT COUNT(*) FROM forum_topics WHERE category_id = c.id) as topic_count,
                        (SELECT COUNT(*) FROM forum_posts p JOIN forum_topics t ON p.topic_id = t.id WHERE t.category_id = c.id) as post_count,
                        (SELECT t.id FROM forum_topics t WHERE t.category_id = c.id ORDER BY COALESCE(t.last_reply_at, t.created_at) DESC LIMIT 1) as last_topic_id,
                        (SELECT t.title FROM forum_topics t WHERE t.category_id = c.id ORDER BY COALESCE(t.last_reply_at, t.created_at) DESC LIMIT 1) as last_topic_title,
                        (SELECT t.slug FROM forum_topics t WHERE t.category_id = c.id ORDER BY COALESCE(t.last_reply_at, t.created_at) DESC LIMIT 1) as last_topic_slug,
                        (SELECT COALESCE(t.last_reply_at, t.created_at) FROM forum_topics t WHERE t.category_id = c.id ORDER BY COALESCE(t.last_reply_at, t.created_at) DESC LIMIT 1) as last_activity,
                        (SELECT u.username FROM forum_topics t LEFT JOIN users u ON u.id = COALESCE(t.last_reply_user_id, t.user_id) WHERE t.category_id = c.id ORDER BY COALESCE(t.last_reply_at, t.created_at) DESC LIMIT 1) as last_user
                 FROM forum_categories c
                 ORDER BY c.sort_order ASC"
            )->fetchAll();
        }, 300); // Cache for 5 minutes

        $stats = Cache::remember('forum_stats', function () use ($db) {
            return $db->query(
                "SELECT 
                    (SELECT COUNT(*) FROM forum_topics) as total_topics,
                    (SELECT COUNT(*) FROM forum_posts) as total_posts,
                    (SELECT COUNT(*) FROM users) as total_members,
                    (SELECT username FROM users ORDER BY id DESC LIMIT 1) as newest_member"
            )->fetch();
        }, 600); // Cache for 10 minutes

        $onlineUsers = Auth::getOnlineUsers(15000);
        $onlineCount = count($onlineUsers);

        View::render('pages/forum/index', [
            'title' => 'Community Forum',
            'categories' => $categories,
            'stats' => $stats,
            'onlineUsers' => $onlineUsers,
            'onlineCount' => $onlineCount,
        ]);
    }

    public function category(string $slug): void
    {
        $db = Database::getConnection();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::TOPICS_PER_PAGE;

        $category = $db->prepare("SELECT * FROM forum_categories WHERE slug = :slug");
        $category->execute(['slug' => $slug]);
        $category = $category->fetch();

        if (!$category) {
            http_response_code(404);
            View::render('pages/404', ['title' => 'Not Found']);
            return;
        }

        $totalStmt = $db->prepare("SELECT COUNT(*) FROM forum_topics WHERE category_id = :cid");
        $totalStmt->execute(['cid' => $category['id']]);
        $totalTopics = (int)$totalStmt->fetchColumn();
        $totalPages = max(1, ceil($totalTopics / self::TOPICS_PER_PAGE));

        $stmt = $db->prepare(
            "SELECT t.*, u.username, u.avatar, u.custom_avatar,
                    lu.username as last_reply_username
             FROM forum_topics t
             JOIN users u ON u.id = t.user_id
             LEFT JOIN users lu ON lu.id = t.last_reply_user_id
             WHERE t.category_id = :cid
             ORDER BY t.is_pinned DESC, COALESCE(t.last_reply_at, t.created_at) DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue('cid', $category['id'], PDO::PARAM_INT);
        $stmt->bindValue('limit', self::TOPICS_PER_PAGE, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $topics = $stmt->fetchAll();

        View::render('pages/forum/category', [
            'title' => $category['name'] . ' - Forum',
            'category' => $category,
            'topics' => $topics,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalTopics' => $totalTopics,
        ]);
    }

    public function topic(string $categorySlug, int $topicId, string $topicSlug): void
    {
        try {
            $this->topicAction($categorySlug, $topicId, $topicSlug);
        } catch (\Throwable $e) {
            error_log('Forum topic error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
            http_response_code(500);
            View::render('pages/forum/error', ['title' => 'Error', 'message' => 'Unable to load this topic. Please try again later.']);
        }
    }

    private function topicAction(string $categorySlug, int $topicId, string $topicSlug): void
    {
        $db = Database::getConnection();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::POSTS_PER_PAGE;

        $topic = $db->prepare(
            "SELECT t.*, c.name as category_name, c.slug as category_slug,
                    u.username, u.avatar, u.custom_avatar, u.profile_accent_color, u.card_style
             FROM forum_topics t
             JOIN forum_categories c ON c.id = t.category_id
             JOIN users u ON u.id = t.user_id
             WHERE t.id = :id"
        );
        $topic->execute(['id' => $topicId]);
        $topic = $topic->fetch();

        if (!$topic || $topic['category_slug'] !== $categorySlug) {
            http_response_code(404);
            View::render('pages/404', ['title' => 'Not Found']);
            return;
        }

        $db->prepare("UPDATE forum_topics SET views = views + 1 WHERE id = :id")
           ->execute(['id' => $topicId]);

        $totalStmt = $db->prepare("SELECT COUNT(*) FROM forum_posts WHERE topic_id = :tid");
        $totalStmt->execute(['tid' => $topicId]);
        $totalPosts = (int)$totalStmt->fetchColumn();
        $totalPages = max(1, ceil(($totalPosts + 1) / self::POSTS_PER_PAGE));

        $stmt = $db->prepare(
            "SELECT p.*, u.username, u.avatar, u.custom_avatar, u.profile_accent_color, u.card_style,
                    u.created_at as user_joined,
                    (SELECT COUNT(*) FROM forum_posts WHERE user_id = u.id) as user_post_count,
                    (SELECT COUNT(*) FROM forum_reactions WHERE post_id = p.id AND reaction = 'like') as likes
             FROM forum_posts p
             JOIN users u ON u.id = p.user_id
             WHERE p.topic_id = :tid
             ORDER BY p.created_at ASC
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue('tid', $topicId, PDO::PARAM_INT);
        $stmt->bindValue('limit', self::POSTS_PER_PAGE, PDO::PARAM_INT);
        $stmt->bindValue('offset', max(0, $offset - 1), PDO::PARAM_INT);
        $stmt->execute();
        $posts = $stmt->fetchAll();

        $userReactions = [];
        if (Auth::check()) {
            $reactStmt = $db->prepare(
                "SELECT post_id, reaction FROM forum_reactions 
                 WHERE user_id = :uid AND post_id IN (SELECT id FROM forum_posts WHERE topic_id = :tid)"
            );
            $reactStmt->execute(['uid' => Auth::id(), 'tid' => $topicId]);
            foreach ($reactStmt->fetchAll() as $r) {
                $userReactions[$r['post_id']] = $r['reaction'];
            }
        }

        // Fetch attachments for the topic
        $topicAttachments = [];
        $attachStmt = $db->prepare(
            "SELECT * FROM forum_attachments WHERE topic_id = :tid AND post_id IS NULL ORDER BY created_at ASC"
        );
        $attachStmt->execute(['tid' => $topicId]);
        $topicAttachments = $attachStmt->fetchAll();

        // Fetch attachments for posts
        $postAttachments = [];
        if (!empty($posts)) {
            $postIds = array_column($posts, 'id');
            $attachStmt = $db->prepare(
                "SELECT * FROM forum_attachments WHERE post_id IN (" . str_repeat('?,', count($postIds) - 1) . "?) ORDER BY created_at ASC"
            );
            $attachStmt->execute(array_values($postIds));
            foreach ($attachStmt->fetchAll() as $attachment) {
                $postAttachments[$attachment['post_id']][] = $attachment;
            }
        }

        // Fetch featured cards for topic author and post authors
        $featuredCards = [];
        
        // Get all unique user IDs (topic author + post authors)
        $userIds = [$topic['user_id']];
        foreach ($posts as $post) {
            $userIds[] = $post['user_id'];
        }
        $userIds = array_unique($userIds);
        
        if (!empty($userIds)) {
            $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
            $featuredStmt = $db->prepare(
                "SELECT u.id as user_id, c.card_image_url, c.card_name, c.card_set_id
                 FROM users u 
                 JOIN cards c ON c.id = u.featured_card_id 
                 WHERE u.id IN ($placeholders) AND u.featured_card_id IS NOT NULL"
            );
            $featuredStmt->execute(array_values($userIds));
            foreach ($featuredStmt->fetchAll() as $featured) {
                $featuredCards[$featured['user_id']] = $featured;
            }
        }

        // Batch-fetch quick stats for author badge computation
        $userBadges = [];
        if (!empty($userIds)) {
            $placeholders2 = str_repeat('?,', count($userIds) - 1) . '?';
            $statsStmt = $db->prepare(
                "SELECT u.id,
                        (SELECT COUNT(DISTINCT card_id) FROM user_cards WHERE user_id = u.id) as card_count,
                        (SELECT COUNT(*) FROM forum_posts WHERE user_id = u.id) as post_count,
                        (SELECT COUNT(*) FROM forum_topics WHERE user_id = u.id) as topic_count,
                        (SELECT COUNT(*) FROM friendships WHERE (user_id = u.id OR friend_id = u.id) AND status = 'accepted') as friend_count,
                        COALESCE(l.elo_rating, 1000) as elo_rating,
                        COALESCE(l.wins, 0) as wins,
                        COALESCE(l.games_played, 0) as games_played,
                        COALESCE(l.best_streak, 0) as best_streak
                 FROM users u
                 LEFT JOIN leaderboard l ON l.user_id = u.id
                 WHERE u.id IN ($placeholders2)"
            );
            $statsStmt->execute(array_values($userIds));
            foreach ($statsStmt->fetchAll() as $row) {
                $userBadges[(int)$row['id']] = \App\Services\BadgeService::computeForumBadges($row, 3);
            }
        }

        // Sanitize content for safe output (handles corrupted data from failed uploads, etc.)
        $topic['content'] = $this->sanitizeContentForDisplay($topic['content']);
        foreach ($posts as $i => $post) {
            $posts[$i]['content'] = $this->sanitizeContentForDisplay($post['content']);
        }

        View::render('pages/forum/topic', [
            'title' => $topic['title'] . ' - Forum',
            'topic' => $topic,
            'posts' => $posts,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalPosts' => $totalPosts,
            'userReactions' => $userReactions,
            'topicAttachments' => $topicAttachments,
            'postAttachments' => $postAttachments,
            'featuredCards' => $featuredCards,
            'userBadges' => $userBadges,
        ]);
    }

    public function newTopicForm(string $categorySlug): void
    {
        Auth::requireAuth();
        $db = Database::getConnection();

        $category = $db->prepare("SELECT * FROM forum_categories WHERE slug = :slug AND is_locked = 0");
        $category->execute(['slug' => $categorySlug]);
        $category = $category->fetch();

        if (!$category) {
            http_response_code(404);
            View::render('pages/404', ['title' => 'Not Found']);
            return;
        }

        View::render('pages/forum/new-topic', [
            'title' => 'New Topic - ' . $category['name'],
            'category' => $category,
        ]);
    }

    public function createTopic(string $categorySlug): void
    {
        Auth::requireAuth();
        $db = Database::getConnection();

        $category = $db->prepare("SELECT * FROM forum_categories WHERE slug = :slug AND is_locked = 0");
        $category->execute(['slug' => $categorySlug]);
        $category = $category->fetch();

        if (!$category) {
            http_response_code(404);
            return;
        }

        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if (strlen($title) < 5 || strlen($title) > 255) {
            $_SESSION['forum_error'] = 'Title must be between 5 and 255 characters.';
            header('Location: /forum/' . $categorySlug . '/new');
            exit;
        }

        if (strlen($content) < 10) {
            $_SESSION['forum_error'] = 'Content must be at least 10 characters.';
            header('Location: /forum/' . $categorySlug . '/new');
            exit;
        }

        if ($this->containsProfanity($content) || $this->containsProfanity($title)) {
            $_SESSION['forum_error'] = 'Your post contains inappropriate language. Please keep discussions respectful.';
            header('Location: /forum/' . $categorySlug . '/new');
            exit;
        }

        $slug = $this->createSlug($title);
        $content = $this->processContent($content);

        $stmt = $db->prepare(
            "INSERT INTO forum_topics (category_id, user_id, title, slug, content)
             VALUES (:cid, :uid, :title, :slug, :content)"
        );
        $stmt->execute([
            'cid' => $category['id'],
            'uid' => Auth::id(),
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
        ]);

        $topicId = (int)$db->lastInsertId();

        $this->processAttachments($topicId, null);

        // Clear forum cache
        Cache::forget('forum_categories');
        Cache::forget('forum_stats');

        header('Location: /forum/' . $categorySlug . '/' . $topicId . '-' . $slug);
        exit;
    }

    public function reply(string $categorySlug, int $topicId): void
    {
        Auth::requireAuth();
        $db = Database::getConnection();

        $topic = $db->prepare(
            "SELECT t.*, c.slug as category_slug FROM forum_topics t
             JOIN forum_categories c ON c.id = t.category_id
             WHERE t.id = :id AND t.is_locked = 0"
        );
        $topic->execute(['id' => $topicId]);
        $topic = $topic->fetch();

        if (!$topic || $topic['category_slug'] !== $categorySlug) {
            http_response_code(404);
            return;
        }

        $content = trim($_POST['content'] ?? '');

        if (strlen($content) < 2) {
            $_SESSION['forum_error'] = 'Reply must be at least 2 characters.';
            header('Location: /forum/' . $categorySlug . '/' . $topicId . '-' . $topic['slug']);
            exit;
        }

        if ($this->containsProfanity($content)) {
            $_SESSION['forum_error'] = 'Your reply contains inappropriate language. Please keep discussions respectful.';
            header('Location: /forum/' . $categorySlug . '/' . $topicId . '-' . $topic['slug']);
            exit;
        }

        $content = $this->processContent($content);

        $stmt = $db->prepare(
            "INSERT INTO forum_posts (topic_id, user_id, content) VALUES (:tid, :uid, :content)"
        );
        $stmt->execute([
            'tid' => $topicId,
            'uid' => Auth::id(),
            'content' => $content,
        ]);

        $postId = (int)$db->lastInsertId();

        $db->prepare(
            "UPDATE forum_topics SET reply_count = reply_count + 1, 
             last_reply_at = NOW(), last_reply_user_id = :uid WHERE id = :tid"
        )->execute(['uid' => Auth::id(), 'tid' => $topicId]);

        $this->processAttachments(null, $postId);

        NotificationService::createForumReply($topic['user_id'], Auth::id(), $topicId, $topic['title']);

        // Clear forum cache
        Cache::forget('forum_categories');
        Cache::forget('forum_stats');

        $totalPosts = $topic['reply_count'] + 2;
        $lastPage = (int)ceil($totalPosts / self::POSTS_PER_PAGE);

        header('Location: /forum/' . $categorySlug . '/' . $topicId . '-' . $topic['slug'] . '?page=' . $lastPage . '#post-' . $postId);
        exit;
    }

    public function editPost(int $postId): void
    {
        Auth::requireAuth();
        $db = Database::getConnection();

        $post = $db->prepare(
            "SELECT p.*, t.slug as topic_slug, t.id as topic_id, c.slug as category_slug
             FROM forum_posts p
             JOIN forum_topics t ON t.id = p.topic_id
             JOIN forum_categories c ON c.id = t.category_id
             WHERE p.id = :id"
        );
        $post->execute(['id' => $postId]);
        $post = $post->fetch();

        if (!$post || ($post['user_id'] != Auth::id() && !Auth::isAdmin())) {
            http_response_code(403);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $content = trim($_POST['content'] ?? '');

            if (strlen($content) < 2) {
                $_SESSION['forum_error'] = 'Content must be at least 2 characters.';
                header('Location: /forum/post/' . $postId . '/edit');
                exit;
            }

            if ($this->containsProfanity($content)) {
                $_SESSION['forum_error'] = 'Your post contains inappropriate language.';
                header('Location: /forum/post/' . $postId . '/edit');
                exit;
            }

            $content = $this->processContent($content);

            $db->prepare(
                "UPDATE forum_posts SET content = :content, edited_at = NOW(), edited_by = :eby WHERE id = :id"
            )->execute(['content' => $content, 'eby' => Auth::id(), 'id' => $postId]);

            header('Location: /forum/' . $post['category_slug'] . '/' . $post['topic_id'] . '-' . $post['topic_slug'] . '#post-' . $postId);
            exit;
        }

        View::render('pages/forum/edit-post', [
            'title' => 'Edit Post',
            'post' => $post,
        ]);
    }

    public function deletePost(int $postId): void
    {
        Auth::requireAuth();
        $db = Database::getConnection();

        $post = $db->prepare(
            "SELECT p.*, t.id as topic_id, t.slug as topic_slug, c.slug as category_slug
             FROM forum_posts p
             JOIN forum_topics t ON t.id = p.topic_id
             JOIN forum_categories c ON c.id = t.category_id
             WHERE p.id = :id"
        );
        $post->execute(['id' => $postId]);
        $post = $post->fetch();

        if (!$post || ($post['user_id'] != Auth::id() && !Auth::isAdmin())) {
            http_response_code(403);
            return;
        }

        $db->prepare("DELETE FROM forum_posts WHERE id = :id")->execute(['id' => $postId]);
        $db->prepare("UPDATE forum_topics SET reply_count = GREATEST(0, reply_count - 1) WHERE id = :tid")
           ->execute(['tid' => $post['topic_id']]);

        header('Location: /forum/' . $post['category_slug'] . '/' . $post['topic_id'] . '-' . $post['topic_slug']);
        exit;
    }

    public function editTopic(int $topicId): void
    {
        Auth::requireAuth();
        $db = Database::getConnection();

        $topic = $db->prepare(
            "SELECT t.*, c.slug as category_slug, c.name as category_name
             FROM forum_topics t
             JOIN forum_categories c ON c.id = t.category_id
             WHERE t.id = :id"
        );
        $topic->execute(['id' => $topicId]);
        $topic = $topic->fetch();

        if (!$topic || ($topic['user_id'] != Auth::id() && !Auth::isAdmin())) {
            http_response_code(403);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');

            if (strlen($title) < 5 || strlen($title) > 255) {
                $_SESSION['forum_error'] = 'Title must be between 5 and 255 characters.';
                header('Location: /forum/topic/' . $topicId . '/edit');
                exit;
            }

            if (strlen($content) < 10) {
                $_SESSION['forum_error'] = 'Content must be at least 10 characters.';
                header('Location: /forum/topic/' . $topicId . '/edit');
                exit;
            }

            if ($this->containsProfanity($content) || $this->containsProfanity($title)) {
                $_SESSION['forum_error'] = 'Your post contains inappropriate language. Please keep discussions respectful.';
                header('Location: /forum/topic/' . $topicId . '/edit');
                exit;
            }

            $slug = $this->createSlug($title);
            $content = $this->processContent($content);

            $db->prepare(
                "UPDATE forum_topics SET title = :title, slug = :slug, content = :content, 
                 updated_at = NOW() WHERE id = :id"
            )->execute([
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'id' => $topicId
            ]);

            // Clear forum cache
            Cache::forget('forum_categories');
            Cache::forget('forum_stats');

            header('Location: /forum/' . $topic['category_slug'] . '/' . $topicId . '-' . $slug);
            exit;
        }

        View::render('pages/forum/edit-topic', [
            'title' => 'Edit Topic - ' . $topic['title'],
            'topic' => $topic,
        ]);
    }

    public function deleteTopic(int $topicId): void
    {
        Auth::requireAuth();
        
        if (!Auth::isAdmin()) {
            http_response_code(403);
            return;
        }
        
        $db = Database::getConnection();
        
        $topic = $db->prepare(
            "SELECT t.*, c.slug as category_slug FROM forum_topics t
             JOIN forum_categories c ON c.id = t.category_id
             WHERE t.id = :id"
        );
        $topic->execute(['id' => $topicId]);
        $topic = $topic->fetch();
        
        if (!$topic) {
            http_response_code(404);
            return;
        }
        
        // Delete all posts in the topic first (due to foreign key constraints)
        $db->prepare("DELETE FROM forum_reactions WHERE post_id IN (SELECT id FROM forum_posts WHERE topic_id = :tid)")
           ->execute(['tid' => $topicId]);
        $db->prepare("DELETE FROM forum_attachments WHERE post_id IN (SELECT id FROM forum_posts WHERE topic_id = :tid)")
           ->execute(['tid' => $topicId]);
        $db->prepare("DELETE FROM forum_posts WHERE topic_id = :tid")
           ->execute(['tid' => $topicId]);
        
        // Delete the topic itself
        $db->prepare("DELETE FROM forum_topics WHERE id = :id")
           ->execute(['id' => $topicId]);
        
        // Clear forum cache
        Cache::forget('forum_categories');
        Cache::forget('forum_stats');
        
        header('Location: /forum/' . $topic['category_slug']);
        exit;
    }

    public function react(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $postId = (int)($_POST['post_id'] ?? 0);
        $reaction = $_POST['reaction'] ?? 'like';

        if (!$postId || !in_array($reaction, ['like', 'helpful', 'thanks'])) {
            echo json_encode(['success' => false]);
            return;
        }

        $db = Database::getConnection();

        // Get post author
        $post = $db->prepare("SELECT user_id FROM forum_posts WHERE id = ?");
        $post->execute([$postId]);
        $postData = $post->fetch();
        
        if (!$postData) {
            echo json_encode(['success' => false]);
            return;
        }

        $existing = $db->prepare(
            "SELECT id FROM forum_reactions WHERE post_id = :pid AND user_id = :uid"
        );
        $existing->execute(['pid' => $postId, 'uid' => Auth::id()]);

        if ($existing->fetch()) {
            $db->prepare("DELETE FROM forum_reactions WHERE post_id = :pid AND user_id = :uid")
               ->execute(['pid' => $postId, 'uid' => Auth::id()]);
            echo json_encode(['success' => true, 'action' => 'removed']);
        } else {
            $db->prepare(
                "INSERT INTO forum_reactions (post_id, user_id, reaction) VALUES (:pid, :uid, :r)"
            )->execute(['pid' => $postId, 'uid' => Auth::id(), 'r' => $reaction]);
            
            NotificationService::createForumLike($postData['user_id'], Auth::id(), $postId);
            
            echo json_encode(['success' => true, 'action' => 'added']);
        }
    }

    public function uploadImage(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Upload failed']);
            return;
        }

        $file = $_FILES['image'];

        if ($file['size'] > self::MAX_UPLOAD_SIZE) {
            echo json_encode(['success' => false, 'error' => 'File too large (max 5MB)']);
            return;
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!$mime || !in_array($mime, self::ALLOWED_IMAGES)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type']);
            return;
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $filename = date('Y/m/') . uniqid() . '_' . Auth::id() . '.' . $ext;

        if (StorageService::isConfigured()) {
            $content = file_get_contents($file['tmp_name']);
            if ($content !== false && StorageService::put($filename, $content, $mime)) {
                echo json_encode(['success' => true, 'url' => '/uploads/forum/' . $filename]);
                return;
            }
        }

        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }
        $dir = self::UPLOAD_DIR . dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (move_uploaded_file($file['tmp_name'], self::UPLOAD_DIR . $filename)) {
            echo json_encode(['success' => true, 'url' => '/uploads/forum/' . $filename]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save file']);
        }
    }

    public function rules(): void
    {
        View::render('pages/forum/rules', [
            'title' => 'Forum Rules & Guidelines',
        ]);
    }

    public function search(): void
    {
        $q = trim($_GET['q'] ?? '');
        $db = Database::getConnection();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::TOPICS_PER_PAGE;

        $topics = [];
        $totalTopics = 0;

        if (strlen($q) >= 3) {
            $totalStmt = $db->prepare(
                "SELECT COUNT(*) FROM forum_topics WHERE MATCH(title, content) AGAINST(:q IN BOOLEAN MODE)"
            );
            $totalStmt->execute(['q' => $q . '*']);
            $totalTopics = (int)$totalStmt->fetchColumn();

            $stmt = $db->prepare(
                "SELECT t.*, c.name as category_name, c.slug as category_slug, u.username
                 FROM forum_topics t
                 JOIN forum_categories c ON c.id = t.category_id
                 JOIN users u ON u.id = t.user_id
                 WHERE MATCH(t.title, t.content) AGAINST(:q IN BOOLEAN MODE)
                 ORDER BY t.created_at DESC
                 LIMIT :limit OFFSET :offset"
            );
            $stmt->bindValue('q', $q . '*', PDO::PARAM_STR);
            $stmt->bindValue('limit', self::TOPICS_PER_PAGE, PDO::PARAM_INT);
            $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $topics = $stmt->fetchAll();
        }

        View::render('pages/forum/search', [
            'title' => 'Search Forum',
            'q' => $q,
            'topics' => $topics,
            'totalTopics' => $totalTopics,
            'page' => $page,
            'totalPages' => max(1, ceil($totalTopics / self::TOPICS_PER_PAGE)),
        ]);
    }

    private function createSlug(string $title): string
    {
        $slug = strtolower(trim($title));
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        return substr($slug, 0, 100) . '-' . substr(uniqid(), -5);
    }

    private function sanitizeContentForDisplay(string $content): string
    {
        if ($content === '') {
            return '';
        }
        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');
        $content = strip_tags($content, '<p><br><strong><em><u><s><a><img><iframe><ul><ol><li><blockquote><code><pre><h3><h4>');
        $content = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $content);
        $content = $this->sanitizeHtmlAttributes($content);
        return $content;
    }

    /** Remove dangerous attributes (on*, javascript:, data: in href/src) to prevent XSS */
    private function sanitizeHtmlAttributes(string $content): string
    {
        $content = preg_replace_callback('/<a\s+([^>]*)>/i', function ($m) {
            $attrs = $this->filterAttrs($m[1], ['href' => ['allowed' => ['http', 'https', '/'], 'default' => '#']]);
            return '<a ' . $attrs . '>';
        }, $content);
        $content = preg_replace_callback('/<img\s+([^>]*)>/i', function ($m) {
            $attrs = $this->filterAttrs($m[1], ['src' => ['allowed' => ['http', 'https', '/'], 'default' => ''], 'alt' => null]);
            return '<img ' . $attrs . '>';
        }, $content);
        $content = preg_replace_callback('/<iframe\s+([^>]*)>/i', function ($m) {
            $attrs = $m[1];
            if (preg_match('/src=["\']([^"\']+)["\']/', $attrs, $srcMatch) && preg_match('/youtube\.com|youtu\.be|vimeo\.com/', $srcMatch[1])) {
                return $m[0];
            }
            return '';
        }, $content);
        $content = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $content);
        $content = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/i', '', $content);
        return $content;
    }

    private function filterAttrs(string $attrString, array $rules): string
    {
        $safe = [];
        if (preg_match_all('/(\w+)\s*=\s*["\']([^"\']*)["\']/', $attrString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $name = strtolower($m[1]);
                $value = $m[2];
                if (str_starts_with($name, 'on') && strlen($name) > 2) {
                    continue;
                }
                if (isset($rules[$name])) {
                    $rule = $rules[$name];
                    if ($rule === null) {
                        $safe[] = $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
                    } elseif (isset($rule['allowed'])) {
                        $ok = false;
                        foreach ($rule['allowed'] as $prefix) {
                            if ($prefix === '/' && (str_starts_with($value, '/') || $value === '')) {
                                $ok = true;
                                break;
                            }
                            if (str_starts_with(strtolower($value), $prefix . ':')) {
                                $ok = true;
                                break;
                            }
                        }
                        if ($ok && !preg_match('/^(javascript|data|vbscript):/i', $value)) {
                            $safe[] = $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
                        } elseif (isset($rule['default'])) {
                            $safe[] = $name . '="' . htmlspecialchars($rule['default'], ENT_QUOTES, 'UTF-8') . '"';
                        }
                    }
                }
            }
        }
        return implode(' ', $safe);
    }

    private function processContent(string $content): string
    {
        $content = strip_tags($content, '<p><br><strong><em><u><s><a><img><iframe><ul><ol><li><blockquote><code><pre><h3><h4>');
        
        $content = preg_replace_callback(
            '/<iframe[^>]*src=["\']([^"\']+)["\'][^>]*><\/iframe>/i',
            function ($matches) {
                $url = $matches[1];
                if (preg_match('/youtube\.com|youtu\.be|vimeo\.com/', $url)) {
                    return $matches[0];
                }
                return '';
            },
            $content
        );

        $content = $this->sanitizeHtmlAttributes($content);
        return $content;
    }

    private function containsProfanity(string $text): bool
    {
        $patterns = [
            '/\b(fuck|shit|ass|bitch|damn|crap|bastard|dick|pussy|cock|cunt)\b/i',
            '/\b(putain|merde|connard|salope|enculé|nique|bordel)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    private function processAttachments(?int $topicId, ?int $postId): void
    {
        if (!isset($_FILES['attachments'])) {
            return;
        }

        $db = Database::getConnection();
        $files = $_FILES['attachments'];

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            if ($files['size'][$i] > self::MAX_UPLOAD_SIZE) {
                continue;
            }

            $mime = mime_content_type($files['tmp_name'][$i]);
            if (!$mime || !in_array($mime, self::ALLOWED_IMAGES)) {
                continue;
            }

            $ext = strtolower(trim(pathinfo($files['name'][$i], PATHINFO_EXTENSION) ?: 'jpg'));
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowedExt)) {
                $ext = 'jpg';
            }
            $filename = date('Y/m/') . uniqid() . '_' . Auth::id() . '.' . $ext;

            $saved = false;
            if (StorageService::isConfigured()) {
                $content = file_get_contents($files['tmp_name'][$i]);
                $saved = ($content !== false && StorageService::put($filename, $content, $mime));
            }
            if (!$saved) {
                $dir = self::UPLOAD_DIR . dirname($filename);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $saved = move_uploaded_file($files['tmp_name'][$i], self::UPLOAD_DIR . $filename);
            }

            if ($saved) {
                $db->prepare(
                    "INSERT INTO forum_attachments (post_id, topic_id, user_id, filename, original_name, mime_type, file_size)
                     VALUES (:pid, :tid, :uid, :fn, :on, :mt, :fs)"
                )->execute([
                    'pid' => $postId,
                    'tid' => $topicId,
                    'uid' => Auth::id(),
                    'fn' => $filename,
                    'on' => $files['name'][$i],
                    'mt' => $mime,
                    'fs' => $files['size'][$i],
                ]);
            }
        }
    }
}
