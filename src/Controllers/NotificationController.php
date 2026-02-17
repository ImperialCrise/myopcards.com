<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\NotificationService;

class NotificationController
{
    public function index(): void
    {
        Auth::requireAuth();
        
        $notifications = NotificationService::getNotifications(Auth::id());
        
        View::render('pages/notifications', [
            'title' => 'Notifications',
            'notifications' => $notifications
        ]);
    }

    public function markAsRead(): void
    {
        Auth::requireAuth();
        
        $notificationId = (int)($_POST['notification_id'] ?? 0);
        
        if ($notificationId) {
            NotificationService::markAsRead(Auth::id(), $notificationId);
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    public function markAllAsRead(): void
    {
        Auth::requireAuth();
        
        NotificationService::markAllAsRead(Auth::id());
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }

    public function getUnreadCount(): void
    {
        Auth::requireAuth();
        
        $count = NotificationService::getUnreadCount(Auth::id());
        
        header('Content-Type: application/json');
        echo json_encode(['count' => $count]);
    }
}