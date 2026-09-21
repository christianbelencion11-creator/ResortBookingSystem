<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM reservationitems WHERE ItemType = 2 AND ReferenceId = ?");
    $stmtCheck->execute([$id]);
    if ($stmtCheck->fetchColumn() > 0) {
        setFlash('danger', 'Cannot delete this facility because it is booked in existing reservations. Disable it instead.');
    } else {
        $stmtFac = $pdo->prepare("SELECT FacilityName FROM facilities WHERE FacilityId = ?");
        $stmtFac->execute([$id]);
        $name = $stmtFac->fetchColumn() ?: "#$id";

        $stmtDel = $pdo->prepare("DELETE FROM facilities WHERE FacilityId = ?");
        $stmtDel->execute([$id]);
        logAudit('Delete', 'Facilities', "Deleted facility: $name", 'badge-danger');
        setFlash('success', "Facility '$name' deleted.");
    }
}

header("Location: " . url('/facilities/index.php'));
exit;
