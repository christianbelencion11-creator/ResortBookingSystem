<?php
$pageTitle = 'Guest Management';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$search = trim($_GET['q'] ?? '');

$query = "SELECT g.*, 
    COUNT(r.ReservationId) as TotalBookings,
    COALESCE(SUM(r.TotalAmount), 0) as TotalSpent
    FROM guestrecords g
    LEFT JOIN reservations r ON g.GuestId = r.GuestId
    WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (g.FirstName LIKE ? OR g.LastName LIKE ? OR g.Email LIKE ? OR g.PhoneNumber LIKE ?)";
    $term = "%$search%";
    $params = [$term, $term, $term, $term];
}

$query .= " GROUP BY g.GuestId ORDER BY g.CreatedAt DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$guests = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="margin:0; font-weight:700;">Guest Directory</h4>
        <p class="text-muted" style="margin:0; font-size:14px;">Customer profiles, stay history, and lifetime spending records</p>
    </div>
</div>

<!-- Search Bar -->
<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Search by name, email, or mobile phone..." value="<?= htmlspecialchars($search) ?>" />
                </div>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Search</button>
            </div>
            <?php if (!empty($search)): ?>
                <div class="col-auto">
                    <a href="<?= url('/guests/index.php') ?>" class="btn btn-sm btn-link text-danger">Reset</a>
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
                    <th>Guest Name</th>
                    <th>Contact Email</th>
                    <th>Phone Number</th>
                    <th>Total Bookings</th>
                    <th>Lifetime Spent</th>
                    <th>Registered Date</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($guests)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No guest records found.</td></tr>
                <?php else: ?>
                    <?php foreach ($guests as $g): ?>
                        <tr>
                            <td>
                                <strong style="font-size:14px;"><?= htmlspecialchars(trim($g['FirstName'] . ' ' . $g['LastName'])) ?></strong>
                                <?php if (!empty($g['IdType'])): ?>
                                    <span class="badge badge-secondary ms-1" style="font-size:11px;"><?= htmlspecialchars($g['IdType']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= !empty($g['Email']) ? htmlspecialchars($g['Email']) : '<span class="text-muted">-</span>' ?></td>
                            <td><?= !empty($g['PhoneNumber']) ? htmlspecialchars($g['PhoneNumber']) : '<span class="text-muted">-</span>' ?></td>
                            <td><span class="badge badge-info"><?= (int)$g['TotalBookings'] ?> stays</span></td>
                            <td><strong><?= formatCurrency($g['TotalSpent']) ?></strong></td>
                            <td><?= formatDate($g['CreatedAt']) ?></td>
                            <td style="text-align:right;">
                                <a href="<?= url('/guests/details.php?id=' . $g['GuestId']) ?>" class="btn btn-sm btn-outline">
                                    <i class="bi bi-folder2-open me-1"></i> Profile & History
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
