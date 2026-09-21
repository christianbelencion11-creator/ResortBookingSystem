<?php
$pageTitle = 'Notifications Center';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$user = currentUser();
$userId = $user['id'];

// Handle Mark All Read
if (isset($_GET['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET IsRead = 1 WHERE UserId = ? OR UserId IS NULL");
    $stmt->execute([$userId]);
    setFlash('success', 'All notifications marked as read.');
    header("Location: " . url('/notifications/index.php'));
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE UserId = ? OR UserId IS NULL ORDER BY CreatedAt DESC LIMIT 50");
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="margin:0; font-weight:700;">Notifications & Activity Alerts</h4>
        <p class="text-muted" style="margin:0; font-size:14px;">Recent system announcements, booking arrivals, and transaction alerts</p>
    </div>
    <a href="<?= url('/notifications/index.php?mark_all_read=1') ?>" class="btn btn-outline btn-sm">
        <i class="bi bi-check-all me-1"></i> Mark All as Read
    </a>
</div>

<div class="card" style="max-width: 800px;">
    <div class="card-body p-0">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-bell-slash" style="font-size:36px; display:block; margin-bottom:12px;"></i>
                <h5>No notifications</h5>
                <p style="font-size:13px;">You are all caught up with resort updates.</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $n): 
                    $color = $n['Type'] === 'danger' ? 'text-danger' : ($n['Type'] === 'warning' ? 'text-warning' : ($n['Type'] === 'success' ? 'text-success' : 'text-primary'));
                    $icon = $n['Icon'] ?: 'bi-bell';
                ?>
                    <div class="list-group-item p-3 d-flex align-items-start gap-3 <?= $n['IsRead'] ? '' : 'unread' ?>" style="background: <?= $n['IsRead'] ? 'transparent' : 'rgba(2, 132, 199, 0.04)' ?>; border-color:var(--border);">
                        <div style="font-size:22px;" class="<?= $color ?> mt-1">
                            <i class="bi <?= htmlspecialchars($icon) ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 style="margin:0; font-weight:600; font-size:14px;"><?= htmlspecialchars($n['Title']) ?></h6>
                                <small class="text-muted" style="font-size:11px;"><?= formatDateTime($n['CreatedAt']) ?></small>
                            </div>
                            <p style="margin:4px 0 0; font-size:13px; color:var(--text-primary);"><?= htmlspecialchars($n['Message']) ?></p>
                            <?php if (!empty($n['ActionUrl'])): ?>
                                <a href="<?= htmlspecialchars($n['ActionUrl']) ?>" class="btn btn-xs btn-outline mt-2">
                                    View Details <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
