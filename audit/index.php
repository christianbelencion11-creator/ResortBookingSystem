<?php
$pageTitle = 'System Audit Trail';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$moduleFilter = trim($_GET['module'] ?? '');
$query = "SELECT * FROM audit_logs WHERE 1=1";
$params = [];

if (!empty($moduleFilter)) {
    $query .= " AND Module = ?";
    $params[] = $moduleFilter;
}

$query .= " ORDER BY Timestamp DESC LIMIT 100";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="margin:0; font-weight:700;">System Audit Log</h4>
        <p class="text-muted" style="margin:0; font-size:14px;">Tamper-resistant event history, logins, financial changes, and room status audits</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <span class="text-muted" style="font-size:13px;">Module:</span>
        <select class="form-select form-select-sm" style="width:auto;" onchange="location.href='<?= url("/audit/index.php") ?>?module=' + this.value">
            <option value="">All Modules</option>
            <option value="Auth" <?= $moduleFilter === 'Auth' ? 'selected' : '' ?>>Authentication</option>
            <option value="Reservations" <?= $moduleFilter === 'Reservations' ? 'selected' : '' ?>>Reservations</option>
            <option value="CheckInOut" <?= $moduleFilter === 'CheckInOut' ? 'selected' : '' ?>>Check-In / Out</option>
            <option value="WalkIn" <?= $moduleFilter === 'WalkIn' ? 'selected' : '' ?>>Walk-In</option>
            <option value="Rooms" <?= $moduleFilter === 'Rooms' ? 'selected' : '' ?>>Rooms & Cottages</option>
            <option value="Activities" <?= $moduleFilter === 'Activities' ? 'selected' : '' ?>>Activities</option>
            <option value="Facilities" <?= $moduleFilter === 'Facilities' ? 'selected' : '' ?>>Facilities</option>
            <option value="Payments" <?= $moduleFilter === 'Payments' ? 'selected' : '' ?>>Payments</option>
            <option value="Expenses" <?= $moduleFilter === 'Expenses' ? 'selected' : '' ?>>Expenses</option>
            <option value="Users" <?= $moduleFilter === 'Users' ? 'selected' : '' ?>>User Admin</option>
        </select>
    </div>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User / Operator</th>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Event Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">No audit events recorded for this selection.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr>
                            <td style="white-space:nowrap; font-size:12px; color:var(--text-muted);">
                                <i class="bi bi-clock me-1"></i> <?= formatDateTime($l['Timestamp']) ?>
                            </td>
                            <td><strong style="font-size:13px;"><?= htmlspecialchars($l['User']) ?></strong></td>
                            <td><span class="badge <?= htmlspecialchars($l['BadgeClass'] ?: 'badge-info') ?>"><?= htmlspecialchars($l['Action']) ?></span></td>
                            <td><span class="badge badge-secondary"><?= htmlspecialchars($l['Module']) ?></span></td>
                            <td><?= htmlspecialchars($l['Details']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
