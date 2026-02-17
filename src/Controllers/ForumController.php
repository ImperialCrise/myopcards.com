<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Cache;
use App\Core\Database;
use App\Core\View;
use App\Services\NotificationService;
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

        $onlineUsers = Auth::getOnlineUsers(15);
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
            View::render('pages/errors/404', ['title' => 'Not Found']);
            return;
        }

        $totalStmt = $db->prepare("SELECT COUNT(*) FROM forum_topics WHERE category_id = :cid");
        $totalStmt->execute(['cid' => $category['id']]);
        $totalTopics = (int)$totalStmt->fetchColumn();
        $totalPages = max(1, ceil($totalTopics / self::TOPICS_PER_PAGE));

        $stmt = $db->prepare(
            "SELECT t.*, u.username, u.avatar,
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
        $db = Database::getConnection();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * self::POSTS_PER_PAGE;

        $topic = $db->prepare(
            "SELECT t.*, c.name as category_name, c.slug as category_slug, u.username, u.avatar
             FROM forum_topics t
             JOIN forum_categories c ON c.id = t.category_id
             JOIN users u ON u.id = t.user_id
             WHERE t.id = :id"
        );
        $topic->execute(['id' => $topicId]);
        $topic = $topic->fetch();

        if (!$topic || $topic['category_slug'] !== $categorySlug) {
            http_response_code(404);
            View::render('pages/errors/404', ['title' => 'Not Found']);
            return;
        }

        $db->prepare("UPDATE forum_topics SET views = views + 1 WHERE id = :id")
           ->execute(['id' => $topicId]);

        $totalStmt = $db->prepare("SELECT COUNT(*) FROM forum_posts WHERE topic_id = :tid");
        $totalStmt->execute(['tid' => $topicId]);
        $totalPosts = (int)$totalStmt->fetchColumn();
        $totalPages = max(1, ceil(($totalPosts + 1) / self::POSTS_PER_PAGE));

        $stmt = $db->prepare(
            "SELECT p.*, u.username, u.avatar, u.created_at as user_joined,
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

        View::render('pages/forum/topic', [
            'title' => $topic['title'] . ' - Forum',
            'topic' => $topic,
            'posts' => $posts,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalPosts' => $totalPosts,
            'userReactions' => $userReactions,
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
            View::render('pages/errors/404', ['title' => 'Not Found']);
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
        if (!in_array($mime, self::ALLOWED_IMAGES)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type']);
            return;
        }

        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $filename = date('Y/m/');
        if (!is_dir(self::UPLOAD_DIR . $filename)) {
            mkdir(self::UPLOAD_DIR . $filename, 0755, true);
        }
        $filename .= uniqid() . '_' . Auth::id() . '.' . $ext;

        if (move_uploaded_file($file['tmp_name'], self::UPLOAD_DIR . $filename)) {
            echo json_encode([
                'success' => true,
                'url' => '/uploads/forum/' . $filename,
            ]);
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
            if (!in_array($mime, self::ALLOWED_IMAGES)) {
                continue;
            }

            $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
            $filename = date('Y/m/') . uniqid() . '_' . Auth::id() . '.' . $ext;

            $dir = self::UPLOAD_DIR . date('Y/m/');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            if (move_uploaded_file($files['tmp_name'][$i], self::UPLOAD_DIR . $filename)) {
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
