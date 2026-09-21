<?php
$pageTitle = 'Guest Profile';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM guestrecords WHERE GuestId = ?");
$stmt->execute([$id]);
$guest = $stmt->fetch();

if (!$guest) {
    setFlash('danger', 'Guest not found.');
    header("Location: " . url('/guests/index.php'));
    exit;
}

// Fetch guest reservations
$stmtRes = $pdo->prepare("SELECT r.*, 
    (SELECT SUM(p.Amount) FROM payments p WHERE p.ReservationId = r.ReservationId AND p.Status = 1) as PaidAmount
    FROM reservations r 
    WHERE r.GuestId = ? 
    ORDER BY r.CreatedAt DESC");
$stmtRes->execute([$id]);
$reservations = $stmtRes->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= url('/guests/index.php') ?>" class="btn btn-outline btn-sm mb-2">
        <i class="bi bi-arrow-left me-1"></i> Back to Guests
    </a>
    <h4 style="margin:0; font-weight:700;">Guest Profile: <?= htmlspecialchars(trim($guest['FirstName'] . ' ' . $guest['LastName'])) ?></h4>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-person-lines-fill"></i> Contact Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted" style="font-size:12px; text-transform:uppercase;">Full Name</label>
                    <div style="font-weight:600;"><?= htmlspecialchars(trim($guest['FirstName'] . ' ' . $guest['LastName'])) ?></div>
                </div>
                <div class="mb-3">
                    <label class="text-muted" style="font-size:12px; text-transform:uppercase;">Email Address</label>
                    <div><?= !empty($guest['Email']) ? htmlspecialchars($guest['Email']) : '<span class="text-muted">None</span>' ?></div>
                </div>
                <div class="mb-3">
                    <label class="text-muted" style="font-size:12px; text-transform:uppercase;">Phone Number</label>
                    <div><?= !empty($guest['PhoneNumber']) ? htmlspecialchars($guest['PhoneNumber']) : '<span class="text-muted">None</span>' ?></div>
                </div>
                <div class="mb-3">
                    <label class="text-muted" style="font-size:12px; text-transform:uppercase;">ID Presented</label>
                    <div><?= !empty($guest['IdType']) ? htmlspecialchars($guest['IdType']) : '<span class="text-muted">None</span>' ?></div>
                </div>
                <div>
                    <label class="text-muted" style="font-size:12px; text-transform:uppercase;">Customer Since</label>
                    <div><?= formatDate($guest['CreatedAt']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-clock-history"></i> Stay & Reservation History</h5>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th>Status</th>
                            <th>Total Amount</th>
                            <th>Paid</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservations)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No reservations on file for this guest.</td></tr>
                        <?php else: ?>
                            <?php foreach ($reservations as $r): 
                                $paid = (float)($r['PaidAmount'] ?? 0);
                                $total = (float)$r['TotalAmount'];
                            ?>
                                <tr>
                                    <td><strong>#<?= $r['ReservationId'] ?></strong></td>
                                    <td><?= formatDate($r['CheckInDate']) ?></td>
                                    <td><?= formatDate($r['CheckOutDate']) ?></td>
                                    <td><?= getReservationStatusBadge($r['Status']) ?></td>
                                    <td><strong><?= formatCurrency($total) ?></strong></td>
                                    <td><span class="text-success"><?= formatCurrency($paid) ?></span></td>
                                    <td>
                                        <a href="<?= url('/reservations/details.php?id=' . $r['ReservationId']) ?>" class="btn btn-xs btn-outline">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
