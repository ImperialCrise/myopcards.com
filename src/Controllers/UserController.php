<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\User;
use App\Models\Friendship;
use App\Models\PageView;
use App\Models\Collection;
use App\Models\Deck;
use App\Models\Leaderboard;
use App\Models\Card;
use App\Models\CardSet;
use App\Services\StorageService;
use App\Services\BadgeService;

class UserController
{
    public function profile(): void
    {
        Auth::requireAuth();
        $uid = Auth::id();
        $user = Auth::user();
        $stats        = User::getCollectionStats($uid);
        $friendCount  = Friendship::getFriendCount($uid);
        $viewCounts   = PageView::getCounts($uid);
        $recentViewers = PageView::getRecentViewers($uid, 8);
        $friends      = Friendship::getFriends($uid);
        $recentCards  = Collection::getRecentAdditions($uid, 12);
        $featuredCard = User::getFeaturedCard($uid);
        $recentActivity = User::getRecentForumActivity($uid, 8);
        $forumStats   = User::getForumStats($uid);
        $deckCount    = Deck::getDeckCount($uid);
        $leaderboard  = Leaderboard::getByUserId($uid);
        $lbRank       = Leaderboard::getRankForUser($uid);
        $totalCards   = Card::getTotalCount();
        $setCompletion = CardSet::getCompletionForUser($uid);
        $rarityDist   = User::getRarityDistribution($uid);
        $parallelCount = User::getParallelCount($uid);
        $secCount     = User::getSecCount($uid);

        $badgeData = [
            'stats' => $stats,
            'friendCount' => $friendCount,
            'forumStats' => $forumStats,
            'leaderboard' => $leaderboard,
            'deckCount' => $deckCount,
            'setCompletion' => $setCompletion,
            'rarityDist' => $rarityDist,
            'parallelCount' => $parallelCount,
            'secCount' => $secCount,
            'user' => $user,
        ];
        $earnedBadges = BadgeService::computeBadges($badgeData);
        $allBadges    = BadgeService::getAllBadges();

        View::render('pages/profile', [
            'title' => 'My Profile',
            'user' => $user,
            'stats' => $stats,
            'friendCount' => $friendCount,
            'viewCounts' => $viewCounts,
            'recentViewers' => $recentViewers,
            'friends' => $friends,
            'recentCards' => $recentCards,
            'featuredCard' => $featuredCard,
            'recentActivity' => $recentActivity,
            'forumStats' => $forumStats,
            'deckCount' => $deckCount,
            'leaderboard' => $leaderboard,
            'lbRank' => $lbRank,
            'totalCards' => $totalCards,
            'setCompletion' => $setCompletion,
            'rarityDist' => $rarityDist,
            'parallelCount' => $parallelCount,
            'secCount' => $secCount,
            'earnedBadges' => $earnedBadges,
            'allBadges' => $allBadges,
        ]);
    }

    public function updateProfile(): void
    {
        Auth::requireAuth();

        $bio = trim($_POST['bio'] ?? '');
        $isPublic = isset($_POST['is_public']) ? 1 : 0;
        $gradient = $_POST['banner_gradient'] ?? null;
        $accent   = $_POST['profile_accent_color'] ?? null;
        $cardStyle = $_POST['card_style'] ?? null;

        $allowedGradients = ['default','ocean','fire','gold','nami','zoro','robin','law','shanks'];
        if ($gradient !== null && !in_array($gradient, $allowedGradients, true)) {
            $gradient = 'default';
        }
        if ($accent !== null && !preg_match('/^#[0-9a-fA-F]{6}$/', $accent)) {
            $accent = null;
        }
        $allowedCardStyles = ['default','ocean','fire','gold','emerald','purple','midnight','crimson','slate','rose','teal','amber'];
        if ($cardStyle !== null && !in_array($cardStyle, $allowedCardStyles, true)) {
            $cardStyle = 'default';
        }

        $updates = [
            'bio' => $bio,
            'is_public' => $isPublic,
        ];
        if ($gradient !== null)  $updates['banner_gradient'] = $gradient;
        if ($accent !== null)    $updates['profile_accent_color'] = $accent;
        if ($cardStyle !== null) $updates['card_style'] = $cardStyle;

        User::update(Auth::id(), $updates);

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

        $uid = (int)$user['id'];
        PageView::record($uid, 'profile');

        $stats        = User::getCollectionStats($uid);
        $friendCount  = Friendship::getFriendCount($uid);
        $isFriend     = false;
        $pendingSent  = false;
        $pendingReceived = false;
        if (Auth::check()) {
            $isFriend = Friendship::areFriends(Auth::id(), $uid);
            if (!$isFriend) {
                $pendingSent = Friendship::hasPendingRequest(Auth::id(), $uid);
                $pendingReceived = Friendship::hasPendingRequest($uid, Auth::id());
            }
        }
        $viewCounts    = PageView::getCounts($uid);
        $featuredCard  = User::getFeaturedCard($uid);
        $recentActivity = User::getRecentForumActivity($uid, 8);
        $forumStats    = User::getForumStats($uid);
        $deckCount     = Deck::getDeckCount($uid);
        $leaderboard   = Leaderboard::getByUserId($uid);
        $lbRank        = Leaderboard::getRankForUser($uid);
        $totalCards    = Card::getTotalCount();
        $setCompletion = CardSet::getCompletionForUser($uid);
        $rarityDist    = User::getRarityDistribution($uid);
        $parallelCount = User::getParallelCount($uid);
        $secCount      = User::getSecCount($uid);
        $recentCards   = Collection::getRecentAdditions($uid, 12);
        $friends       = Friendship::getFriends($uid);

        $badgeData = [
            'stats' => $stats,
            'friendCount' => $friendCount,
            'forumStats' => $forumStats,
            'leaderboard' => $leaderboard,
            'deckCount' => $deckCount,
            'setCompletion' => $setCompletion,
            'rarityDist' => $rarityDist,
            'parallelCount' => $parallelCount,
            'secCount' => $secCount,
            'profileUser' => $user,
        ];
        $earnedBadges = BadgeService::computeBadges($badgeData);
        $allBadges    = BadgeService::getAllBadges();

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
            'forumStats' => $forumStats,
            'deckCount' => $deckCount,
            'leaderboard' => $leaderboard,
            'lbRank' => $lbRank,
            'totalCards' => $totalCards,
            'setCompletion' => $setCompletion,
            'rarityDist' => $rarityDist,
            'parallelCount' => $parallelCount,
            'secCount' => $secCount,
            'recentCards' => $recentCards,
            'friends' => $friends,
            'earnedBadges' => $earnedBadges,
            'allBadges' => $allBadges,
            'seoDescription' => $profileDesc,
            'seoImage' => User::getAvatarUrl($user) ?: '',
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

    private const BANNER_MAX_SIZE = 5 * 1024 * 1024;
    private const BANNER_ALLOWED = ['image/jpeg', 'image/png', 'image/webp'];
    private const BANNER_UPLOAD_DIR = BASE_PATH . '/public/uploads/banners/';

    public function updateBanner(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        if (!isset($_FILES['banner']) || $_FILES['banner']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Upload failed']);
            return;
        }

        $file = $_FILES['banner'];
        if ($file['size'] > self::BANNER_MAX_SIZE) {
            echo json_encode(['success' => false, 'error' => 'File too large (max 5MB)']);
            return;
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!$mime || !in_array($mime, self::BANNER_ALLOWED)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type. Use JPG, PNG, or WebP.']);
            return;
        }

        $ext = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            default      => 'jpg',
        };
        $relativePath = date('Y/m/') . uniqid() . '_' . Auth::id() . '.' . $ext;
        $dir = self::BANNER_UPLOAD_DIR . dirname($relativePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $saved = move_uploaded_file($file['tmp_name'], self::BANNER_UPLOAD_DIR . $relativePath);

        if ($saved) {
            User::update(Auth::id(), ['banner_image' => $relativePath, 'banner_gradient' => null]);
            echo json_encode(['success' => true, 'url' => '/uploads/banners/' . $relativePath]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to save file']);
        }
    }

    public function removeBanner(): void
    {
        Auth::requireAuth();
        User::update(Auth::id(), ['banner_image' => null]);
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
}
