<?php
$pageTitle = 'Discount Codes';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('Admin');

// Add Discount Code POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_code') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $description = trim($_POST['description'] ?? '');
    $percent = (float)($_POST['discount_percent'] ?? 0);
    $fixed = (float)($_POST['fixed_amount'] ?? 0);
    $validUntil = !empty($_POST['valid_until']) ? $_POST['valid_until'] . ' 23:59:59' : null;

    if (!empty($code) && ($percent > 0 || $fixed > 0)) {
        $stmt = $pdo->prepare("INSERT INTO discountcodes (Code, Description, DiscountPercent, FixedAmount, ValidUntil, IsActive, UsageCount, CreatedAt) VALUES (?, ?, ?, ?, ?, 1, 0, NOW())");
        $stmt->execute([$code, $description, $percent, $fixed, $validUntil]);

        logAudit('Create', 'Settings', "Created discount voucher: $code", 'badge-info');
        setFlash('success', "Discount code '$code' created successfully!");
        header("Location: " . url('/settings/discounts.php'));
        exit;
    }
}

// Toggle status
if (isset($_GET['toggle']) && (int)$_GET['toggle'] > 0) {
    $dId = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE discountcodes SET IsActive = NOT IsActive WHERE DiscountCodeId = ?")->execute([$dId]);
    setFlash('success', 'Discount code status updated.');
    header("Location: " . url('/settings/discounts.php'));
    exit;
}

// Delete discount
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $delId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM discountcodes WHERE DiscountCodeId = ?")->execute([$delId]);
    setFlash('success', 'Discount code deleted.');
    header("Location: " . url('/settings/discounts.php'));
    exit;
}

$codes = $pdo->query("SELECT * FROM discountcodes ORDER BY CreatedAt DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="margin:0; font-weight:700;">Promotional Discount Codes</h4>
        <p class="text-muted" style="margin:0; font-size:14px;">Manage coupon vouchers, percentage markdowns, and special holiday promos</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="new bootstrap.Modal(document.getElementById('addCouponModal')).show()">
        <i class="bi bi-tag-fill me-1"></i> Add Discount Code
    </button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Coupon Code</th>
                    <th>Description</th>
                    <th>Discount Value</th>
                    <th>Expiration</th>
                    <th>Times Used</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($codes)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No discount codes configured yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($codes as $c): ?>
                        <tr>
                            <td><strong style="font-family:monospace; font-size:15px; letter-spacing:1px; color:var(--primary);"><?= htmlspecialchars($c['Code']) ?></strong></td>
                            <td><?= htmlspecialchars($c['Description'] ?: '-') ?></td>
                            <td>
                                <?php if ($c['DiscountPercent'] > 0): ?>
                                    <span class="badge badge-success"><?= (float)$c['DiscountPercent'] ?>% OFF</span>
                                <?php else: ?>
                                    <span class="badge badge-info">₱<?= number_format($c['FixedAmount'], 2) ?> OFF</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $c['ValidUntil'] ? formatDate($c['ValidUntil']) : '<span class="text-muted">No expiry</span>' ?></td>
                            <td><span class="badge badge-secondary"><?= (int)$c['UsageCount'] ?> redeemed</span></td>
                            <td>
                                <a href="<?= url('/settings/discounts.php?toggle=' . $c['DiscountCodeId']) ?>" class="badge <?= $c['IsActive'] ? 'badge-success' : 'badge-secondary' ?>" style="text-decoration:none;" title="Click to toggle">
                                    <?= $c['IsActive'] ? 'Active' : 'Disabled' ?>
                                </a>
                            </td>
                            <td style="text-align:right;">
                                <a href="<?= url('/settings/discounts.php?delete=' . $c['DiscountCodeId']) ?>" class="btn btn-xs btn-outline text-danger" onclick="return confirm('Delete coupon code <?= htmlspecialchars($c['Code']) ?>?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Coupon -->
<div class="modal fade" id="addCouponModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; background:var(--bg-surface);">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_code" />
                <div class="modal-header">
                    <h6 class="modal-title" style="font-weight:700;">Create Discount Coupon</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Coupon Code *</label>
                        <input type="text" name="code" class="form-control text-uppercase" placeholder="e.g. SUMMER10, PROMO2026" required style="font-family:monospace; font-weight:700;" />
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Discount Percent (%)</label>
                            <input type="number" step="0.5" name="discount_percent" class="form-control" placeholder="e.g. 10" />
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Or Fixed Amount (₱)</label>
                            <input type="number" step="10" name="fixed_amount" class="form-control" placeholder="e.g. 500" />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Expiration Date</label>
                        <input type="date" name="valid_until" class="form-control" />
                        <small class="text-muted">Leave blank for indefinite validity</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Description & Remarks</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g. Early bird discount for summer guests" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Create Code</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
