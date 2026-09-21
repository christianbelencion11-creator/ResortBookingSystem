<?php
$pageTitle = 'Edit Facility';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM facilities WHERE FacilityId = ?");
$stmt->execute([$id]);
$fac = $stmt->fetch();

if (!$fac) {
    setFlash('danger', 'Facility not found.');
    header("Location: " . url('/facilities/index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['facility_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $rentalPrice = (float)($_POST['rental_price'] ?? 0);
    $capacity = (int)($_POST['capacity'] ?? 20);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name) || $rentalPrice <= 0) {
        $error = "Facility Name and a valid Rental Price are required.";
    } else {
        $stmtUpdate = $pdo->prepare("UPDATE facilities SET FacilityName = ?, Description = ?, RentalPrice = ?, Capacity = ?, IsActive = ? WHERE FacilityId = ?");
        $stmtUpdate->execute([$name, $description, $rentalPrice, $capacity, $isActive, $id]);

        logAudit('Update', 'Facilities', "Updated facility: $name", 'badge-warning');
        setFlash('success', "Facility '$name' successfully updated!");
        header("Location: " . url('/facilities/index.php'));
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= url('/facilities/index.php') ?>" class="btn btn-outline btn-sm mb-2">
        <i class="bi bi-arrow-left me-1"></i> Back to Facilities
    </a>
    <h4 style="margin:0; font-weight:700;">Edit Facility: <?= htmlspecialchars($fac['FacilityName']) ?></h4>
</div>

<div class="card" style="max-width: 650px;">
    <div class="card-body" style="padding: 28px;">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mb-4" style="border-radius:10px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label" style="font-weight:600; font-size:13px;">Facility / Venue Name *</label>
                <input type="text" name="facility_name" class="form-control" required value="<?= htmlspecialchars($fac['FacilityName']) ?>" />
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Rental Price (₱) *</label>
                    <input type="number" step="0.01" name="rental_price" class="form-control" required value="<?= htmlspecialchars($fac['RentalPrice']) ?>" />
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Guest Capacity (pax)</label>
                    <input type="number" name="capacity" class="form-control" value="<?= (int)$fac['Capacity'] ?>" min="1" />
                </div>
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" name="is_active" id="isActiveCheck" class="form-check-input" value="1" <?= $fac['IsActive'] ? 'checked' : '' ?> />
                    <label class="form-check-label" for="isActiveCheck" style="font-size:13px; font-weight:600;">Active and available for reservation</label>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" style="font-weight:600; font-size:13px;">Description & Features</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($fac['Description'] ?? '') ?></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= url('/facilities/index.php') ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Update Facility
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
