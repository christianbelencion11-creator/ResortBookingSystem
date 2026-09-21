<?php
$pageTitle = 'Check-In / Check-Out Station';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// Actions: Check-in / Check-out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['reservation_id'])) {
    $resId = (int)$_POST['reservation_id'];
    $action = $_POST['action'];

    if ($action === 'checkin') {
        $pdo->prepare("UPDATE reservations SET Status = 2, UpdatedAt = NOW() WHERE ReservationId = ?")->execute([$resId]);
        // Set rooms to Occupied
        $pdo->prepare("UPDATE rooms r 
            JOIN reservationitems ri ON r.RoomId = ri.ReferenceId 
            SET r.Status = 1, r.UpdatedAt = NOW() 
            WHERE ri.ReservationId = ? AND ri.ItemType = 0")->execute([$resId]);

        logAudit('Check-In', 'CheckInOut', "Checked in Booking #$resId", 'badge-info');
        setFlash('success', "Booking #$resId checked in! Room(s) marked as Occupied.");
        header("Location: " . url('/checkinout/index.php'));
        exit;
    }

    if ($action === 'checkout') {
        $pdo->prepare("UPDATE reservations SET Status = 3, UpdatedAt = NOW() WHERE ReservationId = ?")->execute([$resId]);
        // Set rooms to Available
        $pdo->prepare("UPDATE rooms r 
            JOIN reservationitems ri ON r.RoomId = ri.ReferenceId 
            SET r.Status = 0, r.UpdatedAt = NOW() 
            WHERE ri.ReservationId = ? AND ri.ItemType = 0")->execute([$resId]);

        logAudit('Check-Out', 'CheckInOut', "Checked out Booking #$resId", 'badge-success');
        setFlash('success', "Booking #$resId checked out! Room(s) marked as Available.");
        header("Location: " . url('/checkinout/index.php'));
        exit;
    }
}

$today = date('Y-m-d');

// Today Check-ins: Status 0 (Pending) or 1 (Confirmed) and CheckInDate <= today 23:59:59
$stmtCheckIns = $pdo->prepare("SELECT r.*, 
    COALESCE(CONCAT(g.FirstName, ' ', g.LastName), CONCAT(u.FirstName, ' ', u.LastName), 'Guest') AS GuestName,
    g.PhoneNumber as GuestPhone,
    (SELECT GROUP_CONCAT(rm.RoomNumber SEPARATOR ', ') FROM reservationitems ri JOIN rooms rm ON ri.ReferenceId = rm.RoomId WHERE ri.ReservationId = r.ReservationId AND ri.ItemType = 0) as RoomNumbers
    FROM reservations r
    LEFT JOIN guestrecords g ON r.GuestId = g.GuestId
    LEFT JOIN users u ON r.UserId = u.UserId
    WHERE r.Status IN (0, 1) AND DATE(r.CheckInDate) <= ?
    ORDER BY r.CheckInDate ASC");
$stmtCheckIns->execute([$today]);
$todayCheckIns = $stmtCheckIns->fetchAll();

// Today Check-outs: Status 2 (CheckedIn) and CheckOutDate <= today 23:59:59
$stmtCheckOuts = $pdo->prepare("SELECT r.*, 
    COALESCE(CONCAT(g.FirstName, ' ', g.LastName), CONCAT(u.FirstName, ' ', u.LastName), 'Guest') AS GuestName,
    g.PhoneNumber as GuestPhone,
    (SELECT GROUP_CONCAT(rm.RoomNumber SEPARATOR ', ') FROM reservationitems ri JOIN rooms rm ON ri.ReferenceId = rm.RoomId WHERE ri.ReservationId = r.ReservationId AND ri.ItemType = 0) as RoomNumbers,
    (SELECT COALESCE(SUM(p.Amount), 0) FROM payments p WHERE p.ReservationId = r.ReservationId AND p.Status = 1) as PaidAmount
    FROM reservations r
    LEFT JOIN guestrecords g ON r.GuestId = g.GuestId
    LEFT JOIN users u ON r.UserId = u.UserId
    WHERE r.Status = 2 AND DATE(r.CheckOutDate) <= ?
    ORDER BY r.CheckOutDate ASC");
$stmtCheckOuts->execute([$today]);
$todayCheckOuts = $stmtCheckOuts->fetchAll();

// Active Stays: All Status 2 (CheckedIn)
$stmtActive = $pdo->query("SELECT r.*, 
    COALESCE(CONCAT(g.FirstName, ' ', g.LastName), CONCAT(u.FirstName, ' ', u.LastName), 'Guest') AS GuestName,
    g.PhoneNumber as GuestPhone,
    (SELECT GROUP_CONCAT(rm.RoomNumber SEPARATOR ', ') FROM reservationitems ri JOIN rooms rm ON ri.ReferenceId = rm.RoomId WHERE ri.ReservationId = r.ReservationId AND ri.ItemType = 0) as RoomNumbers
    FROM reservations r
    LEFT JOIN guestrecords g ON r.GuestId = g.GuestId
    LEFT JOIN users u ON r.UserId = u.UserId
    WHERE r.Status = 2
    ORDER BY r.CheckInDate ASC");
$activeStays = $stmtActive->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="margin:0; font-weight:700;">Check-In & Check-Out Front Desk</h4>
        <p class="text-muted" style="margin:0; font-size:14px;">Process arrivals, manage room keys, and settle final accounts</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/walkin/index.php') ?>" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> New Walk-In Guest
        </a>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-tabs mb-4" id="checkInOutTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" id="checkins-tab" data-bs-toggle="tab" data-bs-target="#checkins" type="button" role="tab">
            <i class="bi bi-box-arrow-in-right text-success me-1"></i> Expected Check-Ins 
            <span class="badge badge-success ms-1"><?= count($todayCheckIns) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="checkouts-tab" data-bs-toggle="tab" data-bs-target="#checkouts" type="button" role="tab">
            <i class="bi bi-box-arrow-right text-danger me-1"></i> Due Check-Outs 
            <span class="badge badge-danger ms-1"><?= count($todayCheckOuts) ?></span>
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab">
            <i class="bi bi-people-fill text-primary me-1"></i> Currently In-House 
            <span class="badge badge-info ms-1"><?= count($activeStays) ?></span>
        </button>
    </li>
</ul>

<div class="tab-content" id="checkInOutTabsContent">
    <!-- Tab 1: Expected Check-Ins -->
    <div class="tab-pane fade show active" id="checkins" role="tabpanel">
        <div class="card">
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Booking #</th>
                            <th>Guest Name</th>
                            <th>Contact</th>
                            <th>Room(s) Assigned</th>
                            <th>Stay Period</th>
                            <th>Total Amount</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($todayCheckIns)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">No pending check-ins for today.</td></tr>
                        <?php else: ?>
                            <?php foreach ($todayCheckIns as $r): ?>
                                <tr>
                                    <td><strong>#<?= $r['ReservationId'] ?></strong></td>
                                    <td><strong style="font-size:14px;"><?= htmlspecialchars($r['GuestName']) ?></strong></td>
                                    <td><?= htmlspecialchars($r['GuestPhone'] ?: '-') ?></td>
                                    <td>
                                        <span class="badge badge-secondary"><?= htmlspecialchars($r['RoomNumbers'] ?: 'No Room Assigned') ?></span>
                                    </td>
                                    <td><?= formatDate($r['CheckInDate']) ?> to <?= formatDate($r['CheckOutDate']) ?></td>
                                    <td><strong><?= formatCurrency($r['TotalAmount']) ?></strong></td>
                                    <td style="text-align:right;">
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="action" value="checkin" />
                                            <input type="hidden" name="reservation_id" value="<?= $r['ReservationId'] ?>" />
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="bi bi-key-fill me-1"></i> Check In
                                            </button>
                                        </form>
                                        <a href="<?= url('/reservations/details.php?id=' . $r['ReservationId']) ?>" class="btn btn-sm btn-outline ms-1">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 2: Due Check-Outs -->
    <div class="tab-pane fade" id="checkouts" role="tabpanel">
        <div class="card">
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Booking #</th>
                            <th>Guest Name</th>
                            <th>Room(s)</th>
                            <th>Total Bill</th>
                            <th>Outstanding Balance</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($todayCheckOuts)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No check-outs scheduled for today.</td></tr>
                        <?php else: ?>
                            <?php foreach ($todayCheckOuts as $r): 
                                $bal = max(0, (float)$r['TotalAmount'] - (float)$r['PaidAmount']);
                            ?>
                                <tr>
                                    <td><strong>#<?= $r['ReservationId'] ?></strong></td>
                                    <td><strong style="font-size:14px;"><?= htmlspecialchars($r['GuestName']) ?></strong></td>
                                    <td><span class="badge badge-secondary"><?= htmlspecialchars($r['RoomNumbers']) ?></span></td>
                                    <td><?= formatCurrency($r['TotalAmount']) ?></td>
                                    <td>
                                        <?php if ($bal > 0): ?>
                                            <span class="badge badge-danger" style="font-size:12px;">Due: <?= formatCurrency($bal) ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-success">Fully Paid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:right;">
                                        <?php if ($bal > 0): ?>
                                            <a href="<?= url('/reservations/details.php?id=' . $r['ReservationId']) ?>" class="btn btn-sm btn-warning">
                                                <i class="bi bi-cash me-1"></i> Settle Balance
                                            </a>
                                        <?php else: ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="action" value="checkout" />
                                                <input type="hidden" name="reservation_id" value="<?= $r['ReservationId'] ?>" />
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-box-arrow-right me-1"></i> Check Out
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="<?= url('/receipt/index.php?id=' . $r['ReservationId']) ?>" class="btn btn-sm btn-outline ms-1" target="_blank">
                                            <i class="bi bi-printer"></i>
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

    <!-- Tab 3: Currently In-House -->
    <div class="tab-pane fade" id="active" role="tabpanel">
        <div class="card">
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Booking #</th>
                            <th>Guest</th>
                            <th>Room(s)</th>
                            <th>Check-In Date</th>
                            <th>Scheduled Check-Out</th>
                            <th style="text-align:right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activeStays)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No guests currently checked in.</td></tr>
                        <?php else: ?>
                            <?php foreach ($activeStays as $r): ?>
                                <tr>
                                    <td><strong>#<?= $r['ReservationId'] ?></strong></td>
                                    <td><strong><?= htmlspecialchars($r['GuestName']) ?></strong></td>
                                    <td><span class="badge badge-info"><?= htmlspecialchars($r['RoomNumbers']) ?></span></td>
                                    <td><?= formatDate($r['CheckInDate']) ?></td>
                                    <td><?= formatDate($r['CheckOutDate']) ?></td>
                                    <td style="text-align:right;">
                                        <a href="<?= url('/reservations/details.php?id=' . $r['ReservationId']) ?>" class="btn btn-sm btn-outline">
                                            <i class="bi bi-eye me-1"></i> View Details
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
