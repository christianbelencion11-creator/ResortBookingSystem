<?php
$pageTitle = 'Resort Facilities & Venues';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// Toggle active status
if (isset($_GET['toggle']) && (int)$_GET['toggle'] > 0) {
    $facId = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE facilities SET IsActive = NOT IsActive WHERE FacilityId = ?");
    $stmt->execute([$facId]);
    setFlash('success', 'Facility status updated.');
    header("Location: " . url('/facilities/index.php'));
    exit;
}

$facilities = $pdo->query("SELECT f.*, 
    (SELECT COUNT(*) FROM facilityschedules s WHERE s.FacilityId = f.FacilityId AND s.ScheduleDate >= CURDATE()) as UpcomingBookings
    FROM facilities f 
    ORDER BY f.IsActive DESC, f.FacilityName ASC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="margin:0; font-weight:700;">Facilities & Event Venues</h4>
        <p class="text-muted" style="margin:0; font-size:14px;">Pavilions, gazebos, meeting rooms, and special function areas</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/facilities/book.php') ?>" class="btn btn-outline">
            <i class="bi bi-calendar-plus me-1"></i> Book a Venue
        </a>
        <a href="<?= url('/facilities/create.php') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Facility
        </a>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Facility / Venue</th>
                    <th>Rental Price</th>
                    <th>Capacity</th>
                    <th>Upcoming Bookings</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($facilities)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No facilities found.</td></tr>
                <?php else: ?>
                    <?php foreach ($facilities as $f): ?>
                        <tr>
                            <td>
                                <strong style="font-size:15px;"><?= htmlspecialchars($f['FacilityName']) ?></strong>
                                <?php if (!empty($f['Description'])): ?>
                                    <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($f['Description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= formatCurrency($f['RentalPrice']) ?></strong></td>
                            <td><i class="bi bi-people me-1"></i> <?= (int)$f['Capacity'] ?> guests max</td>
                            <td><span class="badge badge-info"><?= (int)$f['UpcomingBookings'] ?> scheduled</span></td>
                            <td>
                                <a href="<?= url('/facilities/index.php?toggle=' . $f['FacilityId']) ?>" class="badge <?= $f['IsActive'] ? 'badge-success' : 'badge-secondary' ?>" style="text-decoration:none;" title="Click to toggle">
                                    <?= $f['IsActive'] ? 'Active' : 'Disabled' ?>
                                </a>
                            </td>
                            <td style="text-align:right;">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('/facilities/book.php?facility_id=' . $f['FacilityId']) ?>" class="btn btn-outline" title="Reserve Venue">
                                        <i class="bi bi-calendar-check"></i>
                                    </a>
                                    <a href="<?= url('/facilities/edit.php?id=' . $f['FacilityId']) ?>" class="btn btn-outline" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= url('/facilities/delete.php?id=' . $f['FacilityId']) ?>" class="btn btn-outline text-danger" title="Delete" onclick="return confirm('Delete facility <?= htmlspecialchars($f['FacilityName']) ?>?')">
                                        <i class="bi bi-trash"></i>
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
