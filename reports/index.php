<?php
$pageTitle = 'Executive Reports & Analytics';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// Overall metrics
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(Amount), 0) FROM payments WHERE Status = 1")->fetchColumn();
$totalBookings = (int)$pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$totalGuests = (int)$pdo->query("SELECT COUNT(*) FROM guestrecords")->fetchColumn();

// Occupancy Rate: Occupied rooms / Total rooms
$totalRooms = (int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$occupiedRooms = (int)$pdo->query("SELECT COUNT(*) FROM rooms WHERE Status = 1")->fetchColumn();
$occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

// Top Activities
$topActivities = $pdo->query("SELECT a.ActivityName, COUNT(ri.ItemId) as BookingsCount, COALESCE(SUM(ri.Subtotal), 0) as Revenue
    FROM activities a
    JOIN reservationitems ri ON a.ActivityId = ri.ReferenceId AND ri.ItemType = 1
    GROUP BY a.ActivityId
    ORDER BY BookingsCount DESC LIMIT 5")->fetchAll();

// Room Revenue Performance
$roomReports = $pdo->query("SELECT r.RoomNumber, rt.TypeName, r.Status,
    COUNT(ri.ItemId) as StaysCount,
    COALESCE(SUM(ri.Subtotal), 0) as TotalRevenue
    FROM rooms r
    JOIN roomtypes rt ON r.RoomTypeId = rt.RoomTypeId
    LEFT JOIN reservationitems ri ON r.RoomId = ri.ReferenceId AND ri.ItemType = 0
    GROUP BY r.RoomId
    ORDER BY TotalRevenue DESC")->fetchAll();

// Payment Methods Summary
$paymentMethods = $pdo->query("SELECT PaymentMethod, COUNT(*) as Count, SUM(Amount) as Total 
    FROM payments 
    WHERE Status = 1 
    GROUP BY PaymentMethod 
    ORDER BY Total DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="margin:0; font-weight:700;">Executive Business Reports</h4>
        <p class="text-muted" style="margin:0; font-size:14px;">Resort operational efficiency, room yields, and revenue summaries</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/reports/charts.php') ?>" class="btn btn-outline">
            <i class="bi bi-graph-up me-1"></i> Visual Charts
        </a>
        <a href="<?= url('/reports/export.php') ?>" class="btn btn-primary">
            <i class="bi bi-download me-1"></i> Export Data (CSV)
        </a>
    </div>
</div>

<!-- Key Performance Indicators -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?= formatCurrency($totalRevenue) ?></div>
                <div class="stat-label">Total Gross Revenue</div>
            </div>
            <div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
        </div>
        <div class="stat-change up"><i class="bi bi-arrow-up-circle"></i> Lifetime collections</div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?= $occupancyRate ?>%</div>
                <div class="stat-label">Current Occupancy</div>
            </div>
            <div class="stat-icon blue"><i class="bi bi-door-closed"></i></div>
        </div>
        <div class="stat-change"><i class="bi bi-pie-chart"></i> <?= $occupiedRooms ?> of <?= $totalRooms ?> rooms filled</div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?= $totalBookings ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-icon purple"><i class="bi bi-calendar-check"></i></div>
        </div>
        <div class="stat-change up"><i class="bi bi-check2-all"></i> Stays recorded</div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?= $totalGuests ?></div>
                <div class="stat-label">Unique Guests</div>
            </div>
            <div class="stat-icon orange"><i class="bi bi-people"></i></div>
        </div>
        <div class="stat-change"><i class="bi bi-person-check"></i> In customer directory</div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Top Activities -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="bi bi-compass"></i> Most Popular Activities & Excursions</h5>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Bookings</th>
                            <th>Revenue Generated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($topActivities)): ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted">No activity bookings recorded.</td></tr>
                        <?php else: ?>
                            <?php foreach ($topActivities as $ta): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($ta['ActivityName']) ?></strong></td>
                                    <td><span class="badge badge-info"><?= (int)$ta['BookingsCount'] ?> times</span></td>
                                    <td><strong><?= formatCurrency($ta['Revenue']) ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment Channels Distribution -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="bi bi-credit-card"></i> Revenue by Payment Channel</h5>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Payment Method</th>
                            <th>Transactions</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paymentMethods as $pm): ?>
                            <tr>
                                <td><strong><?= getPaymentMethodName($pm['PaymentMethod']) ?></strong></td>
                                <td><?= (int)$pm['Count'] ?> transactions</td>
                                <td><strong class="text-success"><?= formatCurrency($pm['Total']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Room Revenue Yield Performance -->
<div class="card">
    <div class="card-header">
        <h5><i class="bi bi-door-open"></i> Room & Cottage Revenue Performance</h5>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Room Number</th>
                    <th>Type</th>
                    <th>Total Bookings</th>
                    <th>Revenue Yield</th>
                    <th>Current Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roomReports as $rr): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($rr['RoomNumber']) ?></strong></td>
                        <td><?= htmlspecialchars($rr['TypeName']) ?></td>
                        <td><span class="badge badge-secondary"><?= (int)$rr['StaysCount'] ?> bookings</span></td>
                        <td><strong class="text-primary"><?= formatCurrency($rr['TotalRevenue']) ?></strong></td>
                        <td><?= getRoomStatusBadge($rr['Status']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
