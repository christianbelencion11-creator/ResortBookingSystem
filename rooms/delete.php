<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    // Check if room has active bookings
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM reservationitems ri JOIN reservations r ON ri.ReservationId = r.ReservationId WHERE ri.ItemType = 0 AND ri.ReferenceId = ? AND r.Status IN (0, 1, 2)");
    $stmtCheck->execute([$id]);
    if ($stmtCheck->fetchColumn() > 0) {
        setFlash('danger', 'Cannot delete this room because it currently has active or upcoming reservations.');
    } else {
        $stmtRoom = $pdo->prepare("SELECT RoomNumber FROM rooms WHERE RoomId = ?");
        $stmtRoom->execute([$id]);
        $roomNumber = $stmtRoom->fetchColumn() ?: "#$id";

        $stmtDel = $pdo->prepare("DELETE FROM rooms WHERE RoomId = ?");
        $stmtDel->execute([$id]);
        logAudit('Delete', 'Rooms', "Deleted Room $roomNumber", 'badge-danger');
        setFlash('success', "Room $roomNumber successfully deleted.");
    }
}

header("Location: " . url('/rooms/index.php'));
exit;
