<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

class ReportController
{
    private const VALID_REASONS = ['spam', 'harassment', 'inappropriate_content', 'cheating', 'other'];

    public function submitUserReport(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $reportedId = (int)($_POST['user_id'] ?? 0);
        $reason = $_POST['reason'] ?? '';
        $details = trim($_POST['details'] ?? '');

        if ($reportedId <= 0 || !in_array($reason, self::VALID_REASONS)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            return;
        }

        if ($reportedId === Auth::id()) {
            echo json_encode(['success' => false, 'message' => 'Cannot report yourself']);
            return;
        }

        $db = Database::getConnection();
        $existing = $db->prepare(
            "SELECT id FROM user_reports WHERE reporter_id = ? AND reported_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        $existing->execute([Auth::id(), $reportedId]);
        if ($existing->fetch()) {
            echo json_encode(['success' => false, 'message' => 'You have already reported this user recently. Please wait 24 hours.']);
            return;
        }

        $stmt = $db->prepare(
            "INSERT INTO user_reports (reporter_id, reported_id, reason, details) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([Auth::id(), $reportedId, $reason, $details ?: null]);
        echo json_encode(['success' => true, 'message' => 'Report submitted']);
    }
}
