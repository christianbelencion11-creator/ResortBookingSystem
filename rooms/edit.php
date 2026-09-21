<?php
$pageTitle = 'Edit Room';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE RoomId = ?");
$stmt->execute([$id]);
$room = $stmt->fetch();

if (!$room) {
    setFlash('danger', 'Room not found.');
    header("Location: " . url('/rooms/index.php'));
    exit;
}

$error = '';
$roomTypes = $pdo->query("SELECT * FROM roomtypes WHERE IsActive = 1 ORDER BY TypeName")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomNumber = trim($_POST['room_number'] ?? '');
    $roomTypeId = (int)($_POST['room_type_id'] ?? 0);
    $floor = (int)($_POST['floor'] ?? 0);
    $status = (int)($_POST['status'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if (empty($roomNumber) || empty($roomTypeId)) {
        $error = "Room Number and Room Type are required.";
    } else {
        $stmtUpdate = $pdo->prepare("UPDATE rooms SET RoomTypeId = ?, RoomNumber = ?, Floor = ?, Status = ?, Description = ?, UpdatedAt = NOW() WHERE RoomId = ?");
        $stmtUpdate->execute([$roomTypeId, $roomNumber, $floor, $status, $description, $id]);

        logAudit('Update', 'Rooms', "Updated details for Room #$roomNumber", 'badge-warning');
        setFlash('success', "Room $roomNumber updated successfully!");
        header("Location: " . url('/rooms/index.php'));
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= url('/rooms/index.php') ?>" class="btn btn-outline btn-sm mb-2">
        <i class="bi bi-arrow-left me-1"></i> Back to Rooms
    </a>
    <h4 style="margin:0; font-weight:700;">Edit Room <?= htmlspecialchars($room['RoomNumber']) ?></h4>
</div>

<div class="card" style="max-width: 650px;">
    <div class="card-body" style="padding: 28px;">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mb-4" style="border-radius:10px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Room / Unit Number *</label>
                    <input type="text" name="room_number" class="form-control" required value="<?= htmlspecialchars($room['RoomNumber']) ?>" />
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Floor Level</label>
                    <input type="number" name="floor" class="form-control" value="<?= (int)$room['Floor'] ?>" min="0" max="20" />
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Accommodation Type *</label>
                    <select name="room_type_id" class="form-select" required>
                        <?php foreach ($roomTypes as $rt): ?>
                            <option value="<?= $rt['RoomTypeId'] ?>" <?= ((int)$room['RoomTypeId'] === (int)$rt['RoomTypeId']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rt['TypeName']) ?> (<?= formatCurrency($rt['BasePrice']) ?>/night)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Status</label>
                    <select name="status" class="form-select">
                        <option value="0" <?= (int)$room['Status'] === 0 ? 'selected' : '' ?>>Available</option>
                        <option value="1" <?= (int)$room['Status'] === 1 ? 'selected' : '' ?>>Occupied</option>
                        <option value="2" <?= (int)$room['Status'] === 2 ? 'selected' : '' ?>>Under Maintenance</option>
                        <option value="3" <?= (int)$room['Status'] === 3 ? 'selected' : '' ?>>Reserved</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" style="font-weight:600; font-size:13px;">Description & Amenities</label>
                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($room['Description'] ?? '') ?></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= url('/rooms/index.php') ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Update Room
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
