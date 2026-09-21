<?php
$pageTitle = 'Activities & Tours';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// Toggle active status
if (isset($_GET['toggle']) && (int)$_GET['toggle'] > 0) {
    $actId = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE activities SET IsActive = NOT IsActive WHERE ActivityId = ?");
    $stmt->execute([$actId]);
    setFlash('success', 'Activity status updated.');
    header("Location: " . url('/activities/index.php'));
    exit;
}

$activities = $pdo->query("SELECT a.*, 
    (SELECT COUNT(*) FROM activityschedules s WHERE s.ActivityId = a.ActivityId AND s.ScheduleDate >= CURDATE()) as UpcomingSchedules
    FROM activities a 
    ORDER BY a.IsActive DESC, a.ActivityName ASC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="margin:0; font-weight:700;">Activities & Tours</h4>
        <p class="text-muted" style="margin:0; font-size:14px;">Recreational activities, water sports, and guided tour packages</p>
    </div>
    <a href="<?= url('/activities/create.php') ?>" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Add Activity
    </a>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Activity Name</th>
                    <th>Rate per Hour</th>
                    <th>Rate per Day</th>
                    <th>Max Guests</th>
                    <th>Upcoming Slots</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($activities)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No activities found.</td></tr>
                <?php else: ?>
                    <?php foreach ($activities as $a): ?>
                        <tr>
                            <td>
                                <strong style="font-size:15px;"><?= htmlspecialchars($a['ActivityName']) ?></strong>
                                <?php if (!empty($a['Description'])): ?>
                                    <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($a['Description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= $a['PricePerHour'] ? formatCurrency($a['PricePerHour']) : '<span class="text-muted">-</span>' ?></td>
                            <td><?= $a['PricePerDay'] ? formatCurrency($a['PricePerDay']) : '<span class="text-muted">-</span>' ?></td>
                            <td><i class="bi bi-people me-1"></i> <?= (int)$a['MaxParticipants'] ?> pax</td>
                            <td>
                                <span class="badge badge-info"><?= (int)$a['UpcomingSchedules'] ?> scheduled</span>
                            </td>
                            <td>
                                <a href="<?= url('/activities/index.php?toggle=' . $a['ActivityId']) ?>" class="badge <?= $a['IsActive'] ? 'badge-success' : 'badge-secondary' ?>" style="text-decoration:none;" title="Click to toggle">
                                    <?= $a['IsActive'] ? 'Active' : 'Disabled' ?>
                                </a>
                            </td>
                            <td style="text-align:right;">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url('/activities/edit.php?id=' . $a['ActivityId']) ?>" class="btn btn-outline" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= url('/activities/delete.php?id=' . $a['ActivityId']) ?>" class="btn btn-outline text-danger" title="Delete" onclick="return confirm('Delete activity <?= htmlspecialchars($a['ActivityName']) ?>?')">
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
