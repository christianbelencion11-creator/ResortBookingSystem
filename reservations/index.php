<?php
$pageTitle = 'Reservations';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;
$search = trim($_GET['q'] ?? '');

$query = "SELECT r.*, 
    COALESCE(CONCAT(g.FirstName, ' ', g.LastName), CONCAT(u.FirstName, ' ', u.LastName), 'Guest') AS GuestName,
    COALESCE(g.PhoneNumber, u.PhoneNumber, '') AS GuestPhone,
    (SELECT COALESCE(SUM(p.Amount), 0) FROM payments p WHERE p.ReservationId = r.ReservationId AND p.Status = 1) as TotalPaid,
    (SELECT COUNT(*) FROM reservationitems ri WHERE ri.ReservationId = r.ReservationId) as ItemCount
    FROM reservations r
    LEFT JOIN guestrecords g ON r.GuestId = g.GuestId
    LEFT JOIN users u ON r.UserId = u.UserId
    WHERE 1=1";
$params = [];

if ($statusFilter !== null) {
    $query .= " AND r.Status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $query .= " AND (g.FirstName LIKE ? OR g.LastName LIKE ? OR u.FirstName LIKE ? OR u.LastName LIKE ? OR r.ReservationId = ?)";
    $term = "%$search%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = is_numeric($search) ? (int)$search : 0;
}

$query .= " ORDER BY r.CreatedAt DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="margin:0; font-weight:700;">Reservations Management</h4>
        <p class="text-muted" style="margin:0; font-size:14px;">Track room bookings, activity add-ons, balances, and stay statuses</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/walkin/index.php') ?>" class="btn btn-outline">
            <i class="bi bi-person-plus me-1"></i> Walk-In
        </a>
        <a href="<?= url('/reservations/create.php') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Reservation
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="" class="row g-3 align-items-center">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by booking # or guest name..." value="<?= htmlspecialchars($search) ?>" />
            </div>
            <div class="col-auto">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="0" <?= $statusFilter === 0 ? 'selected' : '' ?>>Pending</option>
                    <option value="1" <?= $statusFilter === 1 ? 'selected' : '' ?>>Confirmed</option>
                    <option value="2" <?= $statusFilter === 2 ? 'selected' : '' ?>>Checked In</option>
                    <option value="3" <?= $statusFilter === 3 ? 'selected' : '' ?>>Checked Out</option>
                    <option value="4" <?= $statusFilter === 4 ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            </div>
            <?php if ($statusFilter !== null || !empty($search)): ?>
                <div class="col-auto">
                    <a href="<?= url('/reservations/index.php') ?>" class="btn btn-sm btn-link text-danger">Reset</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Booking #</th>
                    <th>Guest Details</th>
                    <th>Stay Dates</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservations)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No reservations found.</td></tr>
                <?php else: ?>
                    <?php foreach ($reservations as $r): 
                        $total = (float)$r['TotalAmount'];
                        $paid = (float)$r['TotalPaid'];
                        $balance = max(0, $total - $paid);
                    ?>
                        <tr>
                            <td><strong>#<?= $r['ReservationId'] ?></strong></td>
                            <td>
                                <div style="font-weight:600;"><?= htmlspecialchars($r['GuestName']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($r['GuestPhone']) ?></small>
                            </td>
                            <td>
                                <div><i class="bi bi-box-arrow-in-right text-success me-1"></i> <?= formatDate($r['CheckInDate']) ?></div>
                                <div><i class="bi bi-box-arrow-right text-danger me-1"></i> <?= formatDate($r['CheckOutDate']) ?></div>
                            </td>
                            <td><span class="badge badge-secondary"><?= (int)$r['ItemCount'] ?> item(s)</span></td>
                            <td><strong><?= formatCurrency($total) ?></strong></td>
                            <td>
                                <?php if ($balance <= 0): ?>
                                    <span class="badge badge-success">Paid</span>
                                <?php else: ?>
                                    <span class="text-danger" style="font-weight:600;"><?= formatCurrency($balance) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= getReservationStatusBadge($r['Status']) ?></td>
                            <td style="text-align:right;">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('/reservations/details.php?id=' . $r['ReservationId']) ?>" class="btn btn-outline" title="Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= url('/reservations/edit.php?id=' . $r['ReservationId']) ?>" class="btn btn-outline" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= url('/receipt/index.php?id=' . $r['ReservationId']) ?>" class="btn btn-outline" title="Print Receipt" target="_blank">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
