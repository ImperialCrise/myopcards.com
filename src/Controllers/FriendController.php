<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Friendship;
use App\Models\User;

class FriendController
{
    public function index(): void
    {
        Auth::requireAuth();

        $friends = Friendship::getFriends(Auth::id());
        $pendingRequests = Friendship::getPendingRequests(Auth::id());
        $sentRequests = Friendship::getSentRequests(Auth::id());

        View::render('pages/friends', [
            'title' => 'Friends',
            'friends' => $friends,
            'pendingRequests' => $pendingRequests,
            'sentRequests' => $sentRequests,
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
