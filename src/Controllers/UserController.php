<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\User;
use App\Models\Friendship;
use App\Models\PageView;

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

        View::render('pages/profile', [
            'title' => 'My Profile',
            'user' => $user,
            'stats' => $stats,
            'friendCount' => $friendCount,
            'viewCounts' => $viewCounts,
            'recentViewers' => $recentViewers,
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
        $isFriend = Auth::check() ? Friendship::areFriends(Auth::id(), $user['id']) : false;
        $viewCounts = PageView::getCounts($user['id']);

        $profileDesc = $user['username'] . "'s One Piece TCG collection on MyOPCards. "
            . number_format((int)($stats['unique_cards'] ?? 0)) . ' unique cards, valued at $'
            . number_format((float)($stats['total_value'] ?? 0), 2) . '.';

        View::render('pages/public-profile', [
            'title' => $user['username'] . "'s One Piece TCG Collection - MyOPCards",
            'profileUser' => $user,
            'stats' => $stats,
            'friendCount' => $friendCount,
            'isFriend' => $isFriend,
            'viewCounts' => $viewCounts,
            'seoDescription' => $profileDesc,
            'seoImage' => $user['avatar'] ?? '',
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
        Auth::requireAuth();
        $currency = $_POST['currency'] ?? 'usd';
        if (!in_array($currency, ['usd', 'eur'])) $currency = 'usd';

        User::update(Auth::id(), ['preferred_currency' => $currency]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'currency' => $currency]);
    }
}
