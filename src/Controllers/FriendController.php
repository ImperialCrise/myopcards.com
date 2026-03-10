<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Friendship;
use App\Models\User;
use App\Services\NotificationService;

class FriendController
{
    public function index(): void
    {
        Auth::requireAuth();

        $friends = Friendship::getFriends(Auth::id());
        $pendingRequests = Friendship::getPendingRequests(Auth::id());
        $sentRequests = Friendship::getSentRequests(Auth::id());
        $blockedUsers = Friendship::getBlockedUsers(Auth::id());
        foreach ($friends as &$f) {
            $f['avatar_url'] = User::getAvatarUrl($f);
        }
        foreach ($pendingRequests as &$r) {
            $r['avatar_url'] = User::getAvatarUrl($r);
        }
        foreach ($sentRequests as &$r) {
            $r['avatar_url'] = User::getAvatarUrl($r);
        }
        foreach ($blockedUsers as &$b) {
            $b['avatar_url'] = User::getAvatarUrl($b);
        }
        unset($f, $r, $b);

        View::render('pages/friends', [
            'title' => 'Friends',
            'friends' => $friends,
            'pendingRequests' => $pendingRequests,
            'sentRequests' => $sentRequests,
            'blockedUsers' => $blockedUsers,
        ]);
    }

    public function sendRequest(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $friendId = (int)($_POST['user_id'] ?? 0);
        if ($friendId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid user']);
            return;
        }

        $result = Friendship::sendRequest(Auth::id(), $friendId);
        if ($result) {
            $me = User::findById(Auth::id());
            if ($me) {
                NotificationService::createFriendRequest($friendId, Auth::id(), $me['username']);
            }
        }
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Friend request sent' : 'Could not send request',
        ]);
    }

    public function accept(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $friendId = (int)($_POST['user_id'] ?? 0);
        $result = Friendship::acceptRequest(Auth::id(), $friendId);
        if ($result) {
            $me = User::findById(Auth::id());
            if ($me) {
                NotificationService::createFriendAccepted($friendId, Auth::id(), $me['username']);
            }
        }
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Friend request accepted' : 'Could not accept request',
        ]);
    }

    public function decline(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $friendId = (int)($_POST['user_id'] ?? 0);
        Friendship::declineRequest(Auth::id(), $friendId);
        echo json_encode(['success' => true, 'message' => 'Request declined']);
    }

    public function remove(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $friendId = (int)($_POST['user_id'] ?? 0);
        Friendship::removeFriend(Auth::id(), $friendId);
        echo json_encode(['success' => true, 'message' => 'Friend removed']);
    }

    public function block(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $targetId = (int)($_POST['user_id'] ?? 0);
        $ok = Friendship::blockUser(Auth::id(), $targetId);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'User blocked' : 'Could not block']);
    }

    public function unblock(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $targetId = (int)($_POST['user_id'] ?? 0);
        $ok = Friendship::unblockUser(Auth::id(), $targetId);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'User unblocked' : 'Could not unblock']);
    }

    public function searchUsers(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $query = trim($_GET['q'] ?? '');
        if (strlen($query) < 2) {
            echo json_encode([]);
            return;
        }

        $users = User::searchByUsername($query, Auth::id());
        foreach ($users as &$u) {
            $u['avatar_url'] = User::getAvatarUrl($u);
        }
        unset($u);
        echo json_encode($users);
    }

    public function pendingJson(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $pending = Friendship::getPendingRequests(Auth::id());
        echo json_encode($pending);
    }
}
