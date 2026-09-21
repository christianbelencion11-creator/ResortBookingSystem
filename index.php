<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If not logged in, require login
requireLogin();

// Fetch Dashboard Metrics
$totalRooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn() ?: 0;
$availableRooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE Status = 0")->fetchColumn() ?: 0;
$activeReservations = $pdo->query("SELECT COUNT(*) FROM reservations WHERE Status = 2")->fetchColumn() ?: 0;
$pendingPayments = $pdo->query("SELECT COUNT(*) FROM payments WHERE Status = 0")->fetchColumn() ?: 0;

// Monthly Revenue
$monthStart = date('Y-m-01 00:00:00');
$stmtRev = $pdo->prepare("SELECT COALESCE(SUM(Amount), 0) FROM payments WHERE Status = 1 AND PaymentDate >= ?");
$stmtRev->execute([$monthStart]);
$monthlyRevenue = (float)$stmtRev->fetchColumn();

// Recent Reservations
$stmtRecent = $pdo->query("SELECT r.*, 
    COALESCE(CONCAT(g.FirstName, ' ', g.LastName), CONCAT(u.FirstName, ' ', u.LastName), 'Walk-in Guest') AS GuestName,
    COALESCE(g.PhoneNumber, u.PhoneNumber, '') AS GuestPhone
    FROM reservations r
    LEFT JOIN guestrecords g ON r.GuestId = g.GuestId
    LEFT JOIN users u ON r.UserId = u.UserId
    ORDER BY r.CreatedAt DESC LIMIT 6");
$recentReservations = $stmtRecent->fetchAll();

// Room Status Breakdown
$roomStatuses = [
    'Available' => $pdo->query("SELECT COUNT(*) FROM rooms WHERE Status = 0")->fetchColumn() ?: 0,
    'Occupied' => $pdo->query("SELECT COUNT(*) FROM rooms WHERE Status = 1")->fetchColumn() ?: 0,
    'UnderMaintenance' => $pdo->query("SELECT COUNT(*) FROM rooms WHERE Status = 2")->fetchColumn() ?: 0,
    'Reserved' => $pdo->query("SELECT COUNT(*) FROM rooms WHERE Status = 3")->fetchColumn() ?: 0,
];

include __DIR__ . '/includes/header.php';
?>

<!-- Quick Action Shortcuts -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="<?= url('/walkin/index.php') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-person-plus-fill me-1"></i> Quick Walk-In
    </a>
    <a href="<?= url('/reservations/create.php') ?>" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-calendar-plus me-1"></i> New Reservation
    </a>
    <a href="<?= url('/checkinout/index.php') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-box-arrow-in-right me-1"></i> Check-In / Check-Out
    </a>
    <a href="<?= url('/payments/index.php') ?>" class="btn btn-outline-success btn-sm">
        <i class="bi bi-cash-coin me-1"></i> Record Payment
    </a>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?= (int)$totalRooms ?></div>
                <div class="stat-label">Total Rooms & Cottages</div>
            </div>
            <div class="stat-icon green"><i class="bi bi-door-open"></i></div>
        </div>
        <div class="stat-change up"><i class="bi bi-check-circle"></i> <?= (int)$availableRooms ?> Available Now</div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?= (int)$activeReservations ?></div>
                <div class="stat-label">Active Bookings</div>
            </div>
            <div class="stat-icon blue"><i class="bi bi-calendar-check"></i></div>
        </div>
        <div class="stat-change"><i class="bi bi-arrow-right"></i> Currently checked-in</div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?= formatCurrency($monthlyRevenue) ?></div>
                <div class="stat-label">Monthly Revenue</div>
            </div>
            <div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
        </div>
        <div class="stat-change up"><i class="bi bi-graph-up-arrow"></i> <?= date('F Y') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?= (int)$pendingPayments ?></div>
                <div class="stat-label">Pending Payments</div>
            </div>
            <div class="stat-icon orange"><i class="bi bi-clock-history"></i></div>
        </div>
        <div class="stat-change down"><i class="bi bi-exclamation-circle"></i> Needs attention</div>
    </div>
</div>

<!-- Main Section: Recent Reservations + Room Status -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;" class="dashboard-grid">
    <!-- Recent Reservations Table -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-calendar3"></i> Recent Reservations</h5>
            <a href="<?= url('/reservations/index.php') ?>" class="btn btn-sm btn-outline">View All</a>
        </div>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Guest</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentReservations)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No reservations recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentReservations as $res): ?>
                            <tr>
                                <td><strong>#<?= $res['ReservationId'] ?></strong></td>
                                <td>
                                    <div style="font-weight:600;"><?= htmlspecialchars($res['GuestName']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($res['GuestPhone']) ?></small>
                                </td>
                                <td><?= formatDate($res['CheckInDate']) ?></td>
                                <td><?= formatDate($res['CheckOutDate']) ?></td>
                                <td><?= getReservationStatusBadge($res['Status']) ?></td>
                                <td><strong><?= formatCurrency($res['TotalAmount']) ?></strong></td>
                                <td>
                                    <a href="<?= url('/reservations/details.php?id=' . $res['ReservationId']) ?>" class="btn btn-xs btn-outline" title="Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Room Status Breakdown Widget -->
    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-pie-chart"></i> Room & Cottage Status</h5>
        </div>
        <div class="card-body">
            <?php
            $statusColors = [
                'Available' => 'var(--success, #10b981)',
                'Occupied' => 'var(--info, #0284c7)',
                'UnderMaintenance' => 'var(--warning, #f59e0b)',
                'Reserved' => 'var(--text-muted, #64748b)'
            ];
            foreach ($roomStatuses as $statusName => $count):
                $dotColor = $statusColors[$statusName] ?? 'var(--text-muted)';
            ?>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border);">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 10px; height: 10px; border-radius: 50%; background: <?= $dotColor ?>;"></div>
                        <span style="font-size: 14px; font-weight: 500;"><?= $statusName ?></span>
                    </div>
                    <span style="font-weight: 700; font-size: 15px;"><?= $count ?></span>
                </div>
            <?php endforeach; ?>

            <div class="mt-4 pt-2">
                <a href="<?= url('/rooms/index.php') ?>" class="btn btn-outline w-100 btn-sm">
                    <i class="bi bi-door-open me-1"></i> Manage All Rooms
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
