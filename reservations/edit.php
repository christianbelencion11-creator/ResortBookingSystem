<?php
$pageTitle = 'Edit Reservation';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT r.*, 
    COALESCE(CONCAT(g.FirstName, ' ', g.LastName), CONCAT(u.FirstName, ' ', u.LastName), 'Guest') AS GuestName 
    FROM reservations r 
    LEFT JOIN guestrecords g ON r.GuestId = g.GuestId 
    LEFT JOIN users u ON r.UserId = u.UserId 
    WHERE r.ReservationId = ?");
$stmt->execute([$id]);
$res = $stmt->fetch();

if (!$res) {
    setFlash('danger', 'Reservation not found.');
    header("Location: " . url('/reservations/index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = (int)($_POST['status'] ?? 0);
    $specialRequests = trim($_POST['special_requests'] ?? '');
    $totalAmount = (float)($_POST['total_amount'] ?? 0);

    $stmtUpdate = $pdo->prepare("UPDATE reservations SET Status = ?, SpecialRequests = ?, TotalAmount = ?, UpdatedAt = NOW() WHERE ReservationId = ?");
    $stmtUpdate->execute([$status, $specialRequests, $totalAmount, $id]);

    logAudit('Update', 'Reservations', "Updated details for Booking #$id", 'badge-warning');
    setFlash('success', "Reservation #$id updated successfully!");
    header("Location: " . url("/reservations/details.php?id=$id"));
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= url('/reservations/details.php?id=' . $res['ReservationId']) ?>" class="btn btn-outline btn-sm mb-2">
        <i class="bi bi-arrow-left me-1"></i> Back to Details
    </a>
    <h4 style="margin:0; font-weight:700;">Edit Reservation #<?= $res['ReservationId'] ?></h4>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-body" style="padding: 28px;">
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label" style="font-weight:600; font-size:13px;">Guest</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($res['GuestName']) ?>" readonly disabled />
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Status</label>
                    <select name="status" class="form-select">
                        <option value="0" <?= (int)$res['Status'] === 0 ? 'selected' : '' ?>>Pending</option>
                        <option value="1" <?= (int)$res['Status'] === 1 ? 'selected' : '' ?>>Confirmed</option>
                        <option value="2" <?= (int)$res['Status'] === 2 ? 'selected' : '' ?>>Checked In</option>
                        <option value="3" <?= (int)$res['Status'] === 3 ? 'selected' : '' ?>>Checked Out</option>
                        <option value="4" <?= (int)$res['Status'] === 4 ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Total Amount (₱)</label>
                    <input type="number" step="0.01" name="total_amount" class="form-control" value="<?= (float)$res['TotalAmount'] ?>" required />
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" style="font-weight:600; font-size:13px;">Special Requests / Notes</label>
                <textarea name="special_requests" class="form-control" rows="4"><?= htmlspecialchars($res['SpecialRequests'] ?? '') ?></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= url('/reservations/details.php?id=' . $res['ReservationId']) ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
