<?php
$pageTitle = 'Rooms & Cottages';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// Handle quick status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $roomId = (int)$_POST['room_id'];
    $newStatus = (int)$_POST['status'];
    $stmt = $pdo->prepare("UPDATE rooms SET Status = ?, UpdatedAt = NOW() WHERE RoomId = ?");
    $stmt->execute([$newStatus, $roomId]);
    logAudit('Update', 'Rooms', "Updated status of Room #$roomId to " . getRoomStatusName($newStatus), 'badge-warning');
    setFlash('success', 'Room status updated successfully.');
    header("Location: " . url('/rooms/index.php'));
    exit;
}

// Filters
$statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : null;
$typeFilter = isset($_GET['type_id']) && $_GET['type_id'] !== '' ? (int)$_GET['type_id'] : null;

$query = "SELECT r.*, rt.TypeName, rt.BasePrice, rt.MaxOccupancy 
          FROM rooms r 
          JOIN roomtypes rt ON r.RoomTypeId = rt.RoomTypeId 
          WHERE 1=1";
$params = [];

if ($statusFilter !== null) {
    $query .= " AND r.Status = ?";
    $params[] = $statusFilter;
}

if ($typeFilter !== null) {
    $query .= " AND r.RoomTypeId = ?";
    $params[] = $typeFilter;
}

$query .= " ORDER BY r.Floor ASC, r.RoomNumber ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

// Room types for filter dropdown
$roomTypes = $pdo->query("SELECT * FROM roomtypes WHERE IsActive = 1 ORDER BY TypeName")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="margin:0; font-weight:700;">Rooms & Cottages</h4>
        <p class="text-muted" style="margin:0; font-size:14px;">Manage inventory, view occupancy, and configure accommodations</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/rooms/calendar.php') ?>" class="btn btn-outline">
            <i class="bi bi-calendar3 me-1"></i> Availability Calendar
        </a>
        <a href="<?= url('/rooms/create.php') ?>" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Add Room / Cottage
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="card mb-4">
    <div class="card-body" style="padding: 16px 20px;">
        <form method="GET" action="" class="row g-3 align-items-center">
            <div class="col-auto">
                <label class="form-label mb-0" style="font-size:13px; font-weight:600;">Status:</label>
            </div>
            <div class="col-auto">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="0" <?= $statusFilter === 0 ? 'selected' : '' ?>>Available</option>
                    <option value="1" <?= $statusFilter === 1 ? 'selected' : '' ?>>Occupied</option>
                    <option value="2" <?= $statusFilter === 2 ? 'selected' : '' ?>>Under Maintenance</option>
                    <option value="3" <?= $statusFilter === 3 ? 'selected' : '' ?>>Reserved</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-0" style="font-size:13px; font-weight:600;">Type:</label>
            </div>
            <div class="col-auto">
                <select name="type_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($roomTypes as $rt): ?>
                        <option value="<?= $rt['RoomTypeId'] ?>" <?= $typeFilter === (int)$rt['RoomTypeId'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($rt['TypeName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($statusFilter !== null || $typeFilter !== null): ?>
                <div class="col-auto">
                    <a href="<?= url('/rooms/index.php') ?>" class="btn btn-sm btn-link text-danger">
                        <i class="bi bi-x-circle me-1"></i> Clear Filters
                    </a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Rooms Table -->
<div class="card">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Room / Cottage</th>
                    <th>Type & Category</th>
                    <th>Floor</th>
                    <th>Capacity</th>
                    <th>Base Rate</th>
                    <th>Current Status</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rooms)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No rooms match your filter criteria.</td></tr>
                <?php else: ?>
                    <?php foreach ($rooms as $r): ?>
                        <tr>
                            <td>
                                <strong style="font-size:15px;"><?= htmlspecialchars($r['RoomNumber']) ?></strong>
                                <?php if (!empty($r['Description'])): ?>
                                    <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($r['Description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge badge-secondary"><?= htmlspecialchars($r['TypeName']) ?></span>
                            </td>
                            <td><?= $r['Floor'] > 0 ? 'Floor ' . $r['Floor'] : 'Ground / Beach' ?></td>
                            <td><i class="bi bi-people me-1"></i> Up to <?= (int)$r['MaxOccupancy'] ?> guests</td>
                            <td><strong><?= formatCurrency($r['BasePrice']) ?></strong> <span class="text-muted" style="font-size:12px;">/ night</span></td>
                            <td><?= getRoomStatusBadge($r['Status']) ?></td>
                            <td style="text-align:right;">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline" title="Change Status" onclick="openStatusModal(<?= $r['RoomId'] ?>, '<?= htmlspecialchars($r['RoomNumber']) ?>', <?= $r['Status'] ?>)">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                    <a href="<?= url('/rooms/edit.php?id=' . $r['RoomId']) ?>" class="btn btn-outline" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="<?= url('/rooms/delete.php?id=' . $r['RoomId']) ?>" class="btn btn-outline text-danger" title="Delete" onclick="return confirm('Are you sure you want to delete room <?= htmlspecialchars($r['RoomNumber']) ?>?')">
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

<!-- Modal: Quick Change Status -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:14px; background:var(--bg-surface);">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_status" />
                <input type="hidden" name="room_id" id="modalRoomId" />
                <div class="modal-header">
                    <h6 class="modal-title" style="font-weight:700;">Update Status: <span id="modalRoomNumber"></span></h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" style="font-weight:600; font-size:13px;">New Status</label>
                    <select name="status" id="modalStatusSelect" class="form-select">
                        <option value="0">Available (Ready for check-in)</option>
                        <option value="1">Occupied (Guest inside)</option>
                        <option value="2">Under Maintenance (Cleaning/Repair)</option>
                        <option value="3">Reserved (Upcoming booking)</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openStatusModal(id, number, status) {
    document.getElementById('modalRoomId').value = id;
    document.getElementById('modalRoomNumber').textContent = number;
    document.getElementById('modalStatusSelect').value = status;
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
