<?php
$pageTitle = 'Seasonal Pricing';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('Admin');

// Add Seasonal Rate POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_rate') {
    $seasonName = trim($_POST['season_name'] ?? '');
    $startDate = trim($_POST['start_date'] ?? '');
    $endDate = trim($_POST['end_date'] ?? '');
    $multiplier = (float)($_POST['price_multiplier'] ?? 1.2);
    $roomTypeId = !empty($_POST['room_type_id']) ? (int)$_POST['room_type_id'] : null;

    if (!empty($seasonName) && !empty($startDate) && !empty($endDate)) {
        $stmt = $pdo->prepare("INSERT INTO seasonalrates (SeasonName, StartDate, EndDate, PriceMultiplier, RoomTypeId, IsActive, CreatedAt) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        $stmt->execute([$seasonName, $startDate . ' 00:00:00', $endDate . ' 23:59:59', $multiplier, $roomTypeId]);

        logAudit('Create', 'Settings', "Added seasonal rate: $seasonName (Multiplier: {$multiplier}x)", 'badge-info');
        setFlash('success', 'Seasonal pricing rule added successfully!');
        header("Location: " . url('/settings/pricing.php'));
        exit;
    }
}

// Toggle status
if (isset($_GET['toggle']) && (int)$_GET['toggle'] > 0) {
    $rId = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE seasonalrates SET IsActive = NOT IsActive WHERE SeasonalRateId = ?")->execute([$rId]);
    setFlash('success', 'Rate status updated.');
    header("Location: " . url('/settings/pricing.php'));
    exit;
}

// Delete rate
if (isset($_GET['delete']) && (int)$_GET['delete'] > 0) {
    $dId = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM seasonalrates WHERE SeasonalRateId = ?")->execute([$dId]);
    setFlash('success', 'Seasonal rate deleted.');
    header("Location: " . url('/settings/pricing.php'));
    exit;
}

// Fetch rates
$rates = $pdo->query("SELECT sr.*, rt.TypeName 
    FROM seasonalrates sr 
    LEFT JOIN roomtypes rt ON sr.RoomTypeId = rt.RoomTypeId 
    ORDER BY sr.StartDate DESC")->fetchAll();

$roomTypes = $pdo->query("SELECT * FROM roomtypes WHERE IsActive = 1 ORDER BY TypeName")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="margin:0; font-weight:700;">Seasonal Pricing Multipliers</h4>
        <p class="text-muted" style="margin:0; font-size:14px;">Define peak holiday premiums, summer rates, and special calendar price adjustments</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="new bootstrap.Modal(document.getElementById('addRateModal')).show()">
        <i class="bi bi-plus-lg me-1"></i> Add Seasonal Rate
    </button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Season Name</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Multiplier</th>
                    <th>Applies To</th>
                    <th>Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rates)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No seasonal pricing rules configured.</td></tr>
                <?php else: ?>
                    <?php foreach ($rates as $r): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['SeasonName']) ?></strong></td>
                            <td><?= formatDate($r['StartDate']) ?></td>
                            <td><?= formatDate($r['EndDate']) ?></td>
                            <td><span class="badge badge-info" style="font-size:13px;"><?= number_format($r['PriceMultiplier'], 2) ?>x (<?= (($r['PriceMultiplier'] - 1) * 100) > 0 ? '+' . round(($r['PriceMultiplier'] - 1) * 100) . '%' : round(($r['PriceMultiplier'] - 1) * 100) . '%' ?>)</span></td>
                            <td><?= !empty($r['TypeName']) ? htmlspecialchars($r['TypeName']) : 'All Room Types' ?></td>
                            <td>
                                <a href="<?= url('/settings/pricing.php?toggle=' . $r['SeasonalRateId']) ?>" class="badge <?= $r['IsActive'] ? 'badge-success' : 'badge-secondary' ?>" style="text-decoration:none;" title="Click to toggle">
                                    <?= $r['IsActive'] ? 'Active' : 'Inactive' ?>
                                </a>
                            </td>
                            <td style="text-align:right;">
                                <a href="<?= url('/settings/pricing.php?delete=' . $r['SeasonalRateId']) ?>" class="btn btn-xs btn-outline text-danger" onclick="return confirm('Delete this rate?')">
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

<!-- Modal: Add Rate -->
<div class="modal fade" id="addRateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; background:var(--bg-surface);">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_rate" />
                <div class="modal-header">
                    <h6 class="modal-title" style="font-weight:700;">Add Seasonal Pricing Rule</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Season Title *</label>
                        <input type="text" name="season_name" class="form-control" placeholder="e.g. Holy Week Peak, Christmas Holiday" required />
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Start Date *</label>
                            <input type="date" name="start_date" class="form-control" required />
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">End Date *</label>
                            <input type="date" name="end_date" class="form-control" required />
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Price Multiplier *</label>
                            <input type="number" step="0.05" name="price_multiplier" class="form-control" value="1.25" required />
                            <small class="text-muted">1.25 = +25% premium</small>
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Applicable Room Type</label>
                            <select name="room_type_id" class="form-select">
                                <option value="">All Room Types</option>
                                <?php foreach ($roomTypes as $rt): ?>
                                    <option value="<?= $rt['RoomTypeId'] ?>"><?= htmlspecialchars($rt['TypeName']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Save Pricing Rule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
