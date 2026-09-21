<?php
$pageTitle = 'Global Search';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$q = trim($_GET['q'] ?? '');
$resResults = [];
$roomResults = [];
$guestResults = [];
$actResults = [];

if (!empty($q)) {
    $term = "%$q%";

    // 1. Reservations
    $stmtR = $pdo->prepare("SELECT r.*, 
        COALESCE(CONCAT(g.FirstName, ' ', g.LastName), CONCAT(u.FirstName, ' ', u.LastName), 'Guest') as GuestName
        FROM reservations r
        LEFT JOIN guestrecords g ON r.GuestId = g.GuestId
        LEFT JOIN users u ON r.UserId = u.UserId
        WHERE r.ReservationId = ? OR g.FirstName LIKE ? OR g.LastName LIKE ? OR u.FirstName LIKE ? OR u.LastName LIKE ?
        LIMIT 10");
    $stmtR->execute([is_numeric($q) ? (int)$q : 0, $term, $term, $term, $term]);
    $resResults = $stmtR->fetchAll();

    // 2. Rooms
    $stmtRm = $pdo->prepare("SELECT r.*, rt.TypeName, rt.BasePrice 
        FROM rooms r 
        JOIN roomtypes rt ON r.RoomTypeId = rt.RoomTypeId 
        WHERE r.RoomNumber LIKE ? OR rt.TypeName LIKE ?
        LIMIT 10");
    $stmtRm->execute([$term, $term]);
    $roomResults = $stmtRm->fetchAll();

    // 3. Guests
    $stmtG = $pdo->prepare("SELECT * FROM guestrecords WHERE FirstName LIKE ? OR LastName LIKE ? OR Email LIKE ? OR PhoneNumber LIKE ? LIMIT 10");
    $stmtG->execute([$term, $term, $term, $term]);
    $guestResults = $stmtG->fetchAll();

    // 4. Activities
    $stmtA = $pdo->prepare("SELECT * FROM activities WHERE ActivityName LIKE ? OR Description LIKE ? LIMIT 10");
    $stmtA->execute([$term, $term]);
    $actResults = $stmtA->fetchAll();
}

$totalFound = count($resResults) + count($roomResults) + count($guestResults) + count($actResults);

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h4 style="margin:0; font-weight:700;">Global Search</h4>
    <p class="text-muted" style="margin:0; font-size:14px;">Instant cross-database search for bookings, accommodations, guests, and tours</p>
</div>

<!-- Search Form -->
<div class="card mb-4" style="max-width: 700px;">
    <div class="card-body p-3">
        <form method="GET" action="" class="d-flex gap-2">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control" placeholder="Search by name, room number, booking #, activity..." value="<?= htmlspecialchars($q) ?>" autofocus />
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
    </div>
</div>

<?php if (!empty($q)): ?>
    <div class="mb-3 text-muted" style="font-size:14px;">
        Found <strong><?= $totalFound ?></strong> result(s) for "<em><?= htmlspecialchars($q) ?></em>"
    </div>

    <?php if ($totalFound === 0): ?>
        <div class="card p-5 text-center text-muted" style="max-width: 700px;">
            <i class="bi bi-search" style="font-size:36px; display:block; margin-bottom:12px;"></i>
            <h5>No matches found</h5>
            <p style="font-size:13px;">Try searching with a guest name, room number like "R101", or activity like "Kayaking".</p>
        </div>
    <?php else: ?>
        <div class="row g-4" style="max-width: 900px;">
            <!-- Reservations Matches -->
            <?php if (!empty($resResults)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-calendar-check"></i> Matching Reservations (<?= count($resResults) ?>)</h5>
                        </div>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Booking #</th>
                                        <th>Guest</th>
                                        <th>Stay Period</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th style="text-align:right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resResults as $r): ?>
                                        <tr>
                                            <td><strong>#<?= $r['ReservationId'] ?></strong></td>
                                            <td><?= htmlspecialchars($r['GuestName']) ?></td>
                                            <td><?= formatDate($r['CheckInDate']) ?> - <?= formatDate($r['CheckOutDate']) ?></td>
                                            <td><?= formatCurrency($r['TotalAmount']) ?></td>
                                            <td><?= getReservationStatusBadge($r['Status']) ?></td>
                                            <td style="text-align:right;">
                                                <a href="<?= url('/reservations/details.php?id=' . $r['ReservationId']) ?>" class="btn btn-xs btn-outline">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Rooms Matches -->
            <?php if (!empty($roomResults)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-door-open"></i> Matching Rooms & Cottages (<?= count($roomResults) ?>)</h5>
                        </div>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Room Number</th>
                                        <th>Type</th>
                                        <th>Rate</th>
                                        <th>Status</th>
                                        <th style="text-align:right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($roomResults as $rm): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($rm['RoomNumber']) ?></strong></td>
                                            <td><?= htmlspecialchars($rm['TypeName']) ?></td>
                                            <td><?= formatCurrency($rm['BasePrice']) ?></td>
                                            <td><?= getRoomStatusBadge($rm['Status']) ?></td>
                                            <td style="text-align:right;">
                                                <a href="<?= url('/rooms/edit.php?id=' . $rm['RoomId']) ?>" class="btn btn-xs btn-outline">Edit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Guests Matches -->
            <?php if (!empty($guestResults)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-people"></i> Matching Guests (<?= count($guestResults) ?>)</h5>
                        </div>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Guest Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th style="text-align:right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($guestResults as $g): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars(trim($g['FirstName'] . ' ' . $g['LastName'])) ?></strong></td>
                                            <td><?= htmlspecialchars($g['Email'] ?: '-') ?></td>
                                            <td><?= htmlspecialchars($g['PhoneNumber'] ?: '-') ?></td>
                                            <td style="text-align:right;">
                                                <a href="<?= url('/guests/details.php?id=' . $g['GuestId']) ?>" class="btn btn-xs btn-outline">Profile</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Activities Matches -->
            <?php if (!empty($actResults)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="bi bi-compass"></i> Matching Activities (<?= count($actResults) ?>)</h5>
                        </div>
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Activity</th>
                                        <th>Rate</th>
                                        <th>Status</th>
                                        <th style="text-align:right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($actResults as $a): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($a['ActivityName']) ?></strong></td>
                                            <td><?= formatCurrency($a['PricePerHour'] ?: $a['PricePerDay']) ?></td>
                                            <td><span class="badge <?= $a['IsActive'] ? 'badge-success' : 'badge-secondary' ?>"><?= $a['IsActive'] ? 'Active' : 'Disabled' ?></span></td>
                                            <td style="text-align:right;">
                                                <a href="<?= url('/activities/edit.php?id=' . $a['ActivityId']) ?>" class="btn btn-xs btn-outline">Edit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
