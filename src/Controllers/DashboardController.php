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

        $user = Auth::user();
        $stats = User::getCollectionStats(Auth::id());
        $recent = Collection::getRecentAdditions(Auth::id(), 8);
        $friendCount = Friendship::getFriendCount(Auth::id());
        $pending = Friendship::getPendingRequests(Auth::id());
        $setCompletion = CardSet::getCompletionForUser(Auth::id());
        $viewCounts = PageView::getCounts(Auth::id());

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
