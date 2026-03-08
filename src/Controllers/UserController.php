<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\User;
use App\Models\Friendship;
use App\Models\PageView;
use App\Services\StorageService;

class UserController
{
    public function profile(): void
    {
        Auth::requireAuth();
        $user = Auth::user();
        $stats = User::getCollectionStats(Auth::id());
        $friendCount = Friendship::getFriendCount(Auth::id());
        $viewCounts = PageView::getCounts(Auth::id());
        $recentViewers = PageView::getRecentViewers(Auth::id(), 5);
        $friends = Friendship::getFriends(Auth::id());
        $recentCollection = \App\Models\Collection::getUserCollection(Auth::id(), false, ['sort' => 'added'], 1, 12);
        $featuredCard = User::getFeaturedCard(Auth::id());
        $recentActivity = User::getRecentForumActivity(Auth::id(), 8);

        View::render('pages/profile', [
            'title' => 'My Profile',
            'user' => $user,
            'stats' => $stats,
            'friendCount' => $friendCount,
            'viewCounts' => $viewCounts,
            'recentViewers' => $recentViewers,
            'friends' => $friends,
            'recentCards' => $recentCollection['cards'] ?? [],
            'featuredCard' => $featuredCard,
            'recentActivity' => $recentActivity,
        ]);
    }

    public function updateProfile(): void
    {
        Auth::requireAuth();

        $bio = trim($_POST['bio'] ?? '');
        $isPublic = isset($_POST['is_public']) ? 1 : 0;

        User::update(Auth::id(), [
            'bio' => $bio,
            'is_public' => $isPublic,
        ]);

        header('Location: /profile?updated=1');
        exit;
    }

    public function publicProfile(string $username): void
    {
        $user = User::findByUsername($username);
        if (!$user) {
            http_response_code(404);
            View::render('pages/404');
            return;
        }

        PageView::record($user['id'], 'profile');

        $stats = User::getCollectionStats($user['id']);
        $friendCount = Friendship::getFriendCount($user['id']);
        $isFriend = false;
        $pendingSent = false;
        $pendingReceived = false;
        if (Auth::check()) {
            $isFriend = Friendship::areFriends(Auth::id(), $user['id']);
            if (!$isFriend) {
                $pendingSent = Friendship::hasPendingRequest(Auth::id(), $user['id']);
                $pendingReceived = Friendship::hasPendingRequest($user['id'], Auth::id());
            }
        }
        $viewCounts = PageView::getCounts($user['id']);
        $featuredCard = User::getFeaturedCard($user['id']);
        $recentActivity = User::getRecentForumActivity($user['id'], 8);

        $profileDesc = $user['username'] . "'s One Piece TCG collection on MyOPCards. "
            . number_format((int)($stats['unique_cards'] ?? 0)) . ' unique cards, valued at $'
            . number_format((float)($stats['total_value'] ?? 0), 2) . '.';

        View::render('pages/public-profile', [
            'title' => $user['username'] . "'s One Piece TCG Collection - MyOPCards",
            'profileUser' => $user,
            'stats' => $stats,
            'friendCount' => $friendCount,
            'isFriend' => $isFriend,
            'pendingSent' => $pendingSent,
            'pendingReceived' => $pendingReceived,
            'viewCounts' => $viewCounts,
            'featuredCard' => $featuredCard,
            'recentActivity' => $recentActivity,
            'seoDescription' => $profileDesc,
            'seoImage' => \App\Models\User::getAvatarUrl($user) ?: '',
            'seoOgType' => 'profile',
        ]);
    }

    public function settings(): void
    {
        Auth::requireAuth();
        $user = Auth::user();

        View::render('pages/settings', [
            'title' => 'Settings',
            'user' => $user,
        ]);
    }

    public function changePassword(): void
    {
        Auth::requireAuth();
        $user = Auth::user();

        $current = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $errors = [];

        if ($user['password_hash'] && !password_verify($current, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        }

        if (strlen($newPass) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        }

        if ($newPass !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        if (!empty($errors)) {
            View::render('pages/settings', [
                'title' => 'Settings',
                'user' => $user,
                'errors' => $errors,
            ]);
            return;
        }

        User::update(Auth::id(), [
            'password_hash' => password_hash($newPass, PASSWORD_ARGON2ID),
        ]);

        header('Location: /settings?updated=1');
        exit;
    }

    public function changeLanguage(): void
    {
        $lang = $_POST['lang'] ?? 'en';
        $allowed = ['en', 'fr', 'ja', 'ko', 'th', 'zh'];
        if (!in_array($lang, $allowed)) $lang = 'en';

        setcookie('lang', $lang, time() + 86400 * 365, '/', '', true, true);

        if (Auth::check()) {
            User::update(Auth::id(), ['preferred_lang' => $lang]);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'lang' => $lang]);
    }

    public function changeCurrency(): void
    {
        $currency = $_POST['currency'] ?? 'usd';
        $valid = ['usd', 'eur_en', 'eur_fr', 'eur_jp'];
        if (!in_array($currency, $valid)) $currency = 'usd';

        setcookie('currency', $currency, time() + 86400 * 365, '/', '', true, false);

        if (Auth::check()) {
            User::update(Auth::id(), ['preferred_currency' => $currency]);
        }

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'currency' => $currency]);
    }

    private const AVATAR_MAX_SIZE = 2 * 1024 * 1024;
    private const AVATAR_ALLOWED = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const AVATAR_UPLOAD_DIR = BASE_PATH . '/public/uploads/avatars/';

    public function updateAvatar(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Upload failed']);
            return;
        }

        $file = $_FILES['avatar'];
        if ($file['size'] > self::AVATAR_MAX_SIZE) {
            echo json_encode(['success' => false, 'error' => 'File too large (max 2MB)']);
            return;
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!$mime || !in_array($mime, self::AVATAR_ALLOWED)) {
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
        $relativePath = date('Y/m/') . uniqid() . '_' . Auth::id() . '.' . $ext;
        $storageKey = 'avatars/' . $relativePath;

        $saved = false;
        if (StorageService::isConfigured()) {
            $content = file_get_contents($file['tmp_name']);
            $saved = ($content !== false && StorageService::put($storageKey, $content, $mime));
        }
        if (!$saved) {
            $dir = self::AVATAR_UPLOAD_DIR . dirname($relativePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $saved = move_uploaded_file($file['tmp_name'], self::AVATAR_UPLOAD_DIR . $relativePath);
        }

        if ($saved) {
            User::update(Auth::id(), ['custom_avatar' => $relativePath]);
            echo json_encode(['success' => true, 'url' => '/uploads/avatars/' . $relativePath]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save file']);
        }
    }

    public function removeAvatar(): void
    {
        Auth::requireAuth();
        User::update(Auth::id(), ['custom_avatar' => null]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
}
