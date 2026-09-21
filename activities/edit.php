<?php
$pageTitle = 'Edit Activity';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM activities WHERE ActivityId = ?");
$stmt->execute([$id]);
$act = $stmt->fetch();

if (!$act) {
    setFlash('danger', 'Activity not found.');
    header("Location: " . url('/activities/index.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['activity_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priceHour = !empty($_POST['price_per_hour']) ? (float)$_POST['price_per_hour'] : null;
    $priceDay = !empty($_POST['price_per_day']) ? (float)$_POST['price_per_day'] : null;
    $maxPax = (int)($_POST['max_participants'] ?? 10);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if (empty($name)) {
        $error = "Activity Name is required.";
    } elseif ($priceHour === null && $priceDay === null) {
        $error = "Please specify at least a price per hour or price per day.";
    } else {
        $stmtUpdate = $pdo->prepare("UPDATE activities SET ActivityName = ?, Description = ?, PricePerHour = ?, PricePerDay = ?, MaxParticipants = ?, IsActive = ? WHERE ActivityId = ?");
        $stmtUpdate->execute([$name, $description, $priceHour, $priceDay, $maxPax, $isActive, $id]);

        logAudit('Update', 'Activities', "Updated activity: $name", 'badge-warning');
        setFlash('success', "Activity '$name' successfully updated!");
        header("Location: " . url('/activities/index.php'));
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= url('/activities/index.php') ?>" class="btn btn-outline btn-sm mb-2">
        <i class="bi bi-arrow-left me-1"></i> Back to Activities
    </a>
    <h4 style="margin:0; font-weight:700;">Edit Activity: <?= htmlspecialchars($act['ActivityName']) ?></h4>
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
                <label class="form-label" style="font-weight:600; font-size:13px;">Activity Name *</label>
                <input type="text" name="activity_name" class="form-control" required value="<?= htmlspecialchars($act['ActivityName']) ?>" />
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Price Per Hour (₱)</label>
                    <input type="number" step="0.01" name="price_per_hour" class="form-control" value="<?= htmlspecialchars($act['PricePerHour'] ?? '') ?>" />
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Price Per Day (₱)</label>
                    <input type="number" step="0.01" name="price_per_day" class="form-control" value="<?= htmlspecialchars($act['PricePerDay'] ?? '') ?>" />
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Max Participants</label>
                    <input type="number" name="max_participants" class="form-control" value="<?= (int)$act['MaxParticipants'] ?>" min="1" />
                </div>
                <div class="col-md-6 d-flex align-items-center pt-3">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" id="isActiveCheck" class="form-check-input" value="1" <?= $act['IsActive'] ? 'checked' : '' ?> />
                        <label class="form-check-label" for="isActiveCheck" style="font-size:13px; font-weight:600;">Available for booking</label>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" style="font-weight:600; font-size:13px;">Description</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($act['Description'] ?? '') ?></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= url('/activities/index.php') ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Update Activity
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
