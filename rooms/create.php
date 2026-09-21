<?php
$pageTitle = 'Add Room or Cottage';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$error = '';
$roomTypes = $pdo->query("SELECT * FROM roomtypes WHERE IsActive = 1 ORDER BY TypeName")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomNumber = trim($_POST['room_number'] ?? '');
    $roomTypeId = (int)($_POST['room_type_id'] ?? 0);
    $floor = (int)($_POST['floor'] ?? 1);
    $status = (int)($_POST['status'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    if (empty($roomNumber) || empty($roomTypeId)) {
        $error = "Room Number and Room Type are required.";
    } else {
        // Check uniqueness of RoomNumber
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE RoomNumber = ? AND Floor = ?");
        $stmtCheck->execute([$roomNumber, $floor]);
        if ($stmtCheck->fetchColumn() > 0) {
            $error = "A room with number '$roomNumber' on Floor $floor already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO rooms (RoomTypeId, RoomNumber, Floor, Status, Description, CreatedAt, UpdatedAt) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
            $stmt->execute([$roomTypeId, $roomNumber, $floor, $status, $description]);
            $newId = $pdo->lastInsertId();

            logAudit('Create', 'Rooms', "Created Room #$roomNumber (ID: $newId)", 'badge-success');
            setFlash('success', "Room $roomNumber successfully added!");
            header("Location: " . url('/rooms/index.php'));
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= url('/rooms/index.php') ?>" class="btn btn-outline btn-sm mb-2">
        <i class="bi bi-arrow-left me-1"></i> Back to Rooms
    </a>
    <h4 style="margin:0; font-weight:700;">Add New Room or Cottage</h4>
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
                    <input type="text" name="room_number" class="form-control" placeholder="e.g. R104, C03, V03" required value="<?= htmlspecialchars($_POST['room_number'] ?? '') ?>" />
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Floor Level</label>
                    <input type="number" name="floor" class="form-control" value="<?= htmlspecialchars($_POST['floor'] ?? '1') ?>" min="0" max="20" />
                    <small class="text-muted">Use 0 for ground floor, villas, and beach cottages.</small>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Accommodation Type *</label>
                    <select name="room_type_id" class="form-select" required>
                        <option value="">Select Type</option>
                        <?php foreach ($roomTypes as $rt): ?>
                            <option value="<?= $rt['RoomTypeId'] ?>" <?= (isset($_POST['room_type_id']) && (int)$_POST['room_type_id'] === (int)$rt['RoomTypeId']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($rt['TypeName']) ?> (<?= formatCurrency($rt['BasePrice']) ?>/night)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Initial Status</label>
                    <select name="status" class="form-select">
                        <option value="0">Available</option>
                        <option value="1">Occupied</option>
                        <option value="2">Under Maintenance</option>
                        <option value="3">Reserved</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" style="font-weight:600; font-size:13px;">Description & Amenities</label>
                <textarea name="description" class="form-control" rows="3" placeholder="e.g. Sea view, balcony, queen bed, smart TV..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= url('/rooms/index.php') ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Save Room
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
