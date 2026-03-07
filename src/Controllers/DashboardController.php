<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\User;
use App\Models\Collection;
use App\Models\Friendship;
use App\Models\CardSet;
use App\Models\PageView;

class DashboardController
{
    public function index(): void
    {
        Auth::requireAuth();

        $userId = Auth::id();
        $user = Auth::user();
        if (!$user) {
            Auth::logout();
            header('Location: /login');
            exit;
        }

        try {
            $stats = User::getCollectionStats($userId);
        } catch (\Throwable $e) {
            $stats = ['unique_cards' => 0, 'total_cards' => 0, 'total_value' => 0, 'total_value_symbol' => '$', 'total_value_label' => 'USD'];
        }

        try {
            $recent = Collection::getRecentAdditions($userId, 8);
        } catch (\Throwable $e) {
            $recent = [];
        }

        try {
            $friendCount = Friendship::getFriendCount($userId);
            $pending = Friendship::getPendingRequests($userId);
        } catch (\Throwable $e) {
            $friendCount = 0;
            $pending = [];
        }

        try {
            $setCompletion = CardSet::getCompletionForUser($userId);
        } catch (\Throwable $e) {
            $setCompletion = [];
        }

        $viewCounts = PageView::getCounts($userId);

        View::render('pages/dashboard', [
            'title' => 'Dashboard',
            'user' => $user,
            'stats' => $stats,
            'recentCards' => $recent,
            'friendCount' => $friendCount,
            'pendingRequests' => $pending,
            'setCompletion' => $setCompletion,
            'viewCounts' => $viewCounts,
        ]);
    }
}
