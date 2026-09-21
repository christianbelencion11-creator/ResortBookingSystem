<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    // Check if booked in reservations
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM reservationitems WHERE ItemType = 1 AND ReferenceId = ?");
    $stmtCheck->execute([$id]);
    if ($stmtCheck->fetchColumn() > 0) {
        setFlash('danger', 'Cannot delete this activity because it is referenced in reservations. You can disable it instead.');
    } else {
        $stmtAct = $pdo->prepare("SELECT ActivityName FROM activities WHERE ActivityId = ?");
        $stmtAct->execute([$id]);
        $name = $stmtAct->fetchColumn() ?: "#$id";

        $stmtDel = $pdo->prepare("DELETE FROM activities WHERE ActivityId = ?");
        $stmtDel->execute([$id]);
        logAudit('Delete', 'Activities', "Deleted activity: $name", 'badge-danger');
        setFlash('success', "Activity '$name' deleted.");
    }
}

header("Location: " . url('/activities/index.php'));
exit;
