<?php
$pageTitle = 'Walk-In Front Desk';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// Fetch available rooms
$availableRooms = $pdo->query("SELECT r.*, rt.TypeName, rt.BasePrice, rt.MaxOccupancy 
                               FROM rooms r 
                               JOIN roomtypes rt ON r.RoomTypeId = rt.RoomTypeId 
                               WHERE r.Status = 0 
                               ORDER BY r.Floor ASC, r.RoomNumber ASC")->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guestName = trim($_POST['guest_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $idType = trim($_POST['id_type'] ?? 'National ID');
    $roomId = (int)($_POST['room_id'] ?? 0);
    $checkIn = trim($_POST['check_in'] ?? date('Y-m-d'));
    $checkOut = trim($_POST['check_out'] ?? date('Y-m-d', strtotime('+1 day')));
    $paymentMethod = (int)($_POST['payment_method'] ?? 0); // 0 = Cash
    $amountPaid = (float)($_POST['amount_paid'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    if (empty($guestName) || empty($roomId)) {
        $error = "Guest Name and Room selection are required.";
    } elseif (strtotime($checkOut) <= strtotime($checkIn)) {
        $error = "Check-Out date must be after Check-In date.";
    } else {
        // Fetch room info
        $stmtRm = $pdo->prepare("SELECT r.*, rt.BasePrice, rt.TypeName FROM rooms r JOIN roomtypes rt ON r.RoomTypeId = rt.RoomTypeId WHERE r.RoomId = ?");
        $stmtRm->execute([$roomId]);
        $room = $stmtRm->fetch();

        if ($room) {
            $nameParts = explode(' ', $guestName, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            // 1. Create Guest Record
            $stmtG = $pdo->prepare("INSERT INTO guestrecords (FirstName, LastName, Email, PhoneNumber, IdType, CreatedAt) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmtG->execute([$firstName, $lastName, $email, $phone, $idType]);
            $guestId = $pdo->lastInsertId();

            // 2. Compute nights & total
            $diffDays = max(1, ceil((strtotime($checkOut) - strtotime($checkIn)) / (60 * 60 * 24)));
            $subtotal = (float)$room['BasePrice'] * $diffDays;
            $user = currentUser();
            $userId = $user ? $user['id'] : 1;

            // 3. Create Reservation immediately as Checked-In (Status = 2)
            $stmtRes = $pdo->prepare("INSERT INTO reservations (UserId, GuestId, CheckInDate, CheckOutDate, TotalAmount, Status, SpecialRequests, CreatedAt, UpdatedAt) VALUES (?, ?, ?, ?, ?, 2, ?, NOW(), NOW())");
            $stmtRes->execute([$userId, $guestId, $checkIn . ' ' . date('H:i:s'), $checkOut . ' 12:00:00', $subtotal, "Walk-in Guest | " . $notes]);
            $resId = $pdo->lastInsertId();

            // 4. Add reservation item
            $stmtItem = $pdo->prepare("INSERT INTO reservationitems (ReservationId, ItemType, ReferenceId, Quantity, UnitPrice, Subtotal, CreatedAt) VALUES (?, 0, ?, ?, ?, ?, NOW())");
            $stmtItem->execute([$resId, $roomId, $diffDays, $room['BasePrice'], $subtotal]);

            // 5. Update Room to Occupied (Status = 1)
            $pdo->prepare("UPDATE rooms SET Status = 1, UpdatedAt = NOW() WHERE RoomId = ?")->execute([$roomId]);

            // 6. Record Payment if provided
            if ($amountPaid > 0) {
                $payType = ($amountPaid >= $subtotal) ? 1 : 0; // 1=Full, 0=Downpayment
                $stmtPay = $pdo->prepare("INSERT INTO payments (ReservationId, Amount, PaymentMethod, PaymentType, PaymentDate, Status, ProcessedBy) VALUES (?, ?, ?, ?, NOW(), 1, ?)");
                $stmtPay->execute([$resId, $amountPaid, $paymentMethod, $payType, $userId]);
            }

            logAudit('Walk-In', 'WalkIn', "Walk-in registration for $guestName in Room {$room['RoomNumber']} ($subtotal)", 'badge-success');
            addNotification('Walk-In Guest Checked In', "$guestName checked in to Room {$room['RoomNumber']}", 'info', 'bi-person-check', url("/reservations/details.php?id=$resId"));

            setFlash('success', "Walk-in guest successfully registered and checked in to Room {$room['RoomNumber']}!");
            header("Location: " . url("/receipt/index.php?id=$resId"));
            exit;
        }
    }
}

// Active Walk-ins list
$stmtWalkIns = $pdo->query("SELECT r.*, 
    COALESCE(CONCAT(g.FirstName, ' ', g.LastName), 'Walk-In') as GuestName,
    g.PhoneNumber as GuestPhone,
    (SELECT rm.RoomNumber FROM reservationitems ri JOIN rooms rm ON ri.ReferenceId = rm.RoomId WHERE ri.ReservationId = r.ReservationId AND ri.ItemType = 0 LIMIT 1) as RoomNumber
    FROM reservations r
    JOIN guestrecords g ON r.GuestId = g.GuestId
    WHERE r.Status = 2 AND r.SpecialRequests LIKE '%Walk-in%'
    ORDER BY r.CreatedAt DESC LIMIT 10");
$activeWalkIns = $stmtWalkIns->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="margin:0; font-weight:700;">Walk-In Desk</h4>
        <p class="text-muted" style="margin:0; font-size:14px;">Instant on-the-spot guest registration, room key assignment, and check-in</p>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-4" style="border-radius:10px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Registration Form -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-person-badge"></i> Quick Walk-In Form</h5>
            </div>
            <div class="card-body" style="padding: 24px;">
                <form method="POST" action="" id="walkInForm">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Guest Full Name *</label>
                            <input type="text" name="guest_name" class="form-control" placeholder="e.g. Juan Carlos" required value="<?= htmlspecialchars($_POST['guest_name'] ?? '') ?>" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Mobile Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="09171234567" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" />
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="guest@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Valid ID Presented</label>
                            <select name="id_type" class="form-select">
                                <option value="National ID">Philippine National ID</option>
                                <option value="Driver's License">Driver's License</option>
                                <option value="Passport">Passport</option>
                                <option value="UMID">UMID / SSS</option>
                                <option value="Student ID">School / Student ID</option>
                            </select>
                        </div>
                    </div>

                    <!-- Room Assignment -->
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Assign Available Room *</label>
                        <select name="room_id" id="walkInRoom" class="form-select" required onchange="calculateWalkInTotal()">
                            <option value="">-- Choose Available Room --</option>
                            <?php foreach ($availableRooms as $rm): ?>
                                <option value="<?= $rm['RoomId'] ?>" data-price="<?= $rm['BasePrice'] ?>">
                                    <?= htmlspecialchars($rm['RoomNumber']) ?> - <?= htmlspecialchars($rm['TypeName']) ?> (<?= formatCurrency($rm['BasePrice']) ?>/night, max <?= $rm['MaxOccupancy'] ?> pax)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Stay Dates -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Check-In Date *</label>
                            <input type="date" name="check_in" id="walkInCheckIn" class="form-control" value="<?= date('Y-m-d') ?>" required onchange="calculateWalkInTotal()" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Check-Out Date *</label>
                            <input type="date" name="check_out" id="walkInCheckOut" class="form-control" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required onchange="calculateWalkInTotal()" />
                        </div>
                    </div>

                    <!-- Pricing & Payment Collection -->
                    <div class="p-3 mb-3" style="background:var(--bg-hover); border-radius:10px;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span style="font-weight:600; font-size:14px;">Calculated Total:</span>
                            <strong style="font-size:18px;" class="text-primary" id="computedTotalDisplay">₱0.00</strong>
                        </div>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:12px; font-weight:600;">Payment Collected (₱)</label>
                                <input type="number" step="0.01" name="amount_paid" id="amountPaidInput" class="form-control form-control-sm" placeholder="0.00" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-size:12px; font-weight:600;">Payment Method</label>
                                <select name="payment_method" class="form-select form-select-sm">
                                    <option value="0">Cash</option>
                                    <option value="2">GCash</option>
                                    <option value="3">Maya</option>
                                    <option value="1">Credit / Debit Card</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Notes / Remarks</label>
                        <input type="text" name="notes" class="form-control" placeholder="Luggage count, emergency contact..." />
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2" style="font-weight:600;">
                        <i class="bi bi-door-open-fill me-1"></i> Check In & Print Receipt
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Active Walk-in Guests List -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-person-check"></i> Recent Walk-Ins Checked In</h5>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Guest</th>
                            <th>Room</th>
                            <th>Dates</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activeWalkIns)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">No active walk-in guests.</td></tr>
                        <?php else: ?>
                            <?php foreach ($activeWalkIns as $w): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($w['GuestName']) ?></strong>
                                        <div style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars($w['GuestPhone'] ?: '') ?></div>
                                    </td>
                                    <td><span class="badge badge-info"><?= htmlspecialchars($w['RoomNumber'] ?: '-') ?></span></td>
                                    <td style="font-size:12px;"><?= formatDate($w['CheckInDate'], 'M d') ?> - <?= formatDate($w['CheckOutDate'], 'M d') ?></td>
                                    <td>
                                        <a href="<?= url('/receipt/index.php?id=' . $w['ReservationId']) ?>" class="btn btn-xs btn-outline" target="_blank" title="Receipt">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function calculateWalkInTotal() {
    var checkIn = new Date(document.getElementById('walkInCheckIn').value);
    var checkOut = new Date(document.getElementById('walkInCheckOut').value);
    var diffTime = checkOut - checkIn;
    var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    var nights = diffDays > 0 ? diffDays : 1;

    var roomSelect = document.getElementById('walkInRoom');
    var selected = roomSelect.options[roomSelect.selectedIndex];
    var price = selected && selected.value ? parseFloat(selected.getAttribute('data-price')) : 0;

    var total = price * nights;
    document.getElementById('computedTotalDisplay').textContent = '₱' + total.toFixed(2);
    document.getElementById('amountPaidInput').value = total.toFixed(2);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
