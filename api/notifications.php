<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$action = $_GET['action'] ?? 'get';
$user = currentUser();
$userId = $user ? $user['id'] : null;

try {
    if ($action === 'get') {
        if ($userId) {
            $stmt = $pdo->prepare("SELECT * FROM notifications WHERE (UserId = ? OR UserId IS NULL) ORDER BY CreatedAt DESC LIMIT 15");
            $stmt->execute([$userId]);
        } else {
            $stmt = $pdo->query("SELECT * FROM notifications WHERE UserId IS NULL ORDER BY CreatedAt DESC LIMIT 15");
        }
        $results = $stmt->fetchAll();
        echo json_encode($results ?: []);
        exit;
    }

    if ($action === 'mark_all_read') {
        if ($userId) {
            $stmt = $pdo->prepare("UPDATE notifications SET IsRead = 1 WHERE (UserId = ? OR UserId IS NULL)");
            $stmt->execute([$userId]);
        } else {
            $pdo->query("UPDATE notifications SET IsRead = 1 WHERE UserId IS NULL");
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'mark_read' && isset($_GET['id'])) {
        $notifId = (int)$_GET['id'];
        $stmt = $pdo->prepare("UPDATE notifications SET IsRead = 1 WHERE NotificationId = ?");
        $stmt->execute([$notifId]);
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['error' => 'Invalid action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
