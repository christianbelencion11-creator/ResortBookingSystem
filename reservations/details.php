<?php
$pageTitle = 'Reservation Details';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);

// Fetch reservation with guest & user
$stmt = $pdo->prepare("SELECT r.*, 
    COALESCE(CONCAT(g.FirstName, ' ', g.LastName), CONCAT(u.FirstName, ' ', u.LastName), 'Guest') AS GuestName,
    g.PhoneNumber as GuestPhone,
    g.Email as GuestEmail
    FROM reservations r
    LEFT JOIN guestrecords g ON r.GuestId = g.GuestId
    LEFT JOIN users u ON r.UserId = u.UserId
    WHERE r.ReservationId = ?");
$stmt->execute([$id]);
$res = $stmt->fetch();

if (!$res) {
    setFlash('danger', 'Reservation not found.');
    header("Location: " . url('/reservations/index.php'));
    exit;
}

// Handle Status Changes (Check-In, Check-Out, Cancel)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $userId = currentUser()['id'];

    if ($action === 'checkin') {
        $pdo->prepare("UPDATE reservations SET Status = 2, UpdatedAt = NOW() WHERE ReservationId = ?")->execute([$id]);
        
        // Mark rooms as Occupied (Status = 1)
        $pdo->prepare("UPDATE rooms r 
            JOIN reservationitems ri ON r.RoomId = ri.ReferenceId 
            SET r.Status = 1, r.UpdatedAt = NOW() 
            WHERE ri.ReservationId = ? AND ri.ItemType = 0")->execute([$id]);

        logAudit('Check-In', 'Reservations', "Checked in Booking #$id for {$res['GuestName']}", 'badge-info');
        setFlash('success', "Guest {$res['GuestName']} checked in successfully! Room status set to Occupied.");
        header("Location: " . url("/reservations/details.php?id=$id"));
        exit;
    }

    if ($action === 'checkout') {
        $pdo->prepare("UPDATE reservations SET Status = 3, UpdatedAt = NOW() WHERE ReservationId = ?")->execute([$id]);
        
        // Mark rooms as Available (Status = 0)
        $pdo->prepare("UPDATE rooms r 
            JOIN reservationitems ri ON r.RoomId = ri.ReferenceId 
            SET r.Status = 0, r.UpdatedAt = NOW() 
            WHERE ri.ReservationId = ? AND ri.ItemType = 0")->execute([$id]);

        logAudit('Check-Out', 'Reservations', "Checked out Booking #$id for {$res['GuestName']}", 'badge-success');
        setFlash('success', "Guest checked out successfully! Room status set to Available.");
        header("Location: " . url("/reservations/details.php?id=$id"));
        exit;
    }

    if ($action === 'cancel') {
        $pdo->prepare("UPDATE reservations SET Status = 4, UpdatedAt = NOW() WHERE ReservationId = ?")->execute([$id]);
        
        // Free up rooms (Status = 0)
        $pdo->prepare("UPDATE rooms r 
            JOIN reservationitems ri ON r.RoomId = ri.ReferenceId 
            SET r.Status = 0, r.UpdatedAt = NOW() 
            WHERE ri.ReservationId = ? AND ri.ItemType = 0")->execute([$id]);

        logAudit('Cancel', 'Reservations', "Cancelled Booking #$id", 'badge-danger');
        setFlash('warning', "Reservation #$id has been cancelled.");
        header("Location: " . url("/reservations/details.php?id=$id"));
        exit;
    }

    if ($action === 'add_payment') {
        $amount = (float)($_POST['amount'] ?? 0);
        $method = (int)($_POST['payment_method'] ?? 0);
        $refNum = trim($_POST['reference_number'] ?? '');
        $payType = (int)($_POST['payment_type'] ?? 2); // 2 = Partial/Balance

        if ($amount > 0) {
            $stmtPay = $pdo->prepare("INSERT INTO payments (ReservationId, Amount, PaymentMethod, PaymentType, ReferenceNumber, PaymentDate, Status, ProcessedBy) VALUES (?, ?, ?, ?, ?, NOW(), 1, ?)");
            $stmtPay->execute([$id, $amount, $method, $payType, $refNum, $userId]);

            logAudit('Payment', 'Payments', "Recorded payment of " . formatCurrency($amount) . " for Booking #$id", 'badge-success');
            setFlash('success', "Payment of " . formatCurrency($amount) . " recorded successfully!");
            header("Location: " . url("/reservations/details.php?id=$id"));
            exit;
        }
    }
}

// Fetch Items
$stmtItems = $pdo->prepare("SELECT ri.*, 
    CASE 
        WHEN ri.ItemType = 0 THEN (SELECT CONCAT('Room ', r.RoomNumber, ' (', rt.TypeName, ')') FROM rooms r JOIN roomtypes rt ON r.RoomTypeId = rt.RoomTypeId WHERE r.RoomId = ri.ReferenceId)
        WHEN ri.ItemType = 1 THEN (SELECT a.ActivityName FROM activities a WHERE a.ActivityId = ri.ReferenceId)
        WHEN ri.ItemType = 2 THEN (SELECT f.FacilityName FROM facilities f WHERE f.FacilityId = ri.ReferenceId)
        ELSE 'Item'
    END as ItemName
    FROM reservationitems ri WHERE ri.ReservationId = ?");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll();

// Fetch Payments
$stmtPayments = $pdo->prepare("SELECT p.*, CONCAT(u.FirstName, ' ', u.LastName) as ProcessorName 
    FROM payments p 
    LEFT JOIN users u ON p.ProcessedBy = u.UserId 
    WHERE p.ReservationId = ? 
    ORDER BY p.PaymentDate DESC");
$stmtPayments->execute([$id]);
$payments = $stmtPayments->fetchAll();

$totalAmount = (float)$res['TotalAmount'];
$totalPaid = 0;
foreach ($payments as $p) {
    if ((int)$p['Status'] === 1) { // Completed
        $totalPaid += (float)$p['Amount'];
    }
}
$balance = max(0, $totalAmount - $totalPaid);

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <a href="<?= url('/reservations/index.php') ?>" class="btn btn-outline btn-sm mb-2">
            <i class="bi bi-arrow-left me-1"></i> Back to Reservations
        </a>
        <h4 style="margin:0; font-weight:700;">Reservation #<?= $res['ReservationId'] ?></h4>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('/receipt/index.php?id=' . $res['ReservationId']) ?>" class="btn btn-outline" target="_blank">
            <i class="bi bi-printer me-1"></i> Print Receipt / Invoice
        </a>

        <?php if ((int)$res['Status'] === 0 || (int)$res['Status'] === 1): // Pending or Confirmed ?>
            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Check-in this guest now?');">
                <input type="hidden" name="action" value="checkin" />
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Check In
                </button>
            </form>
        <?php endif; ?>

        <?php if ((int)$res['Status'] === 2): // Checked In ?>
            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Check out this guest?');">
                <input type="hidden" name="action" value="checkout" />
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-box-arrow-right me-1"></i> Check Out
                </button>
            </form>
        <?php endif; ?>

        <?php if ((int)$res['Status'] !== 3 && (int)$res['Status'] !== 4): // Not CheckedOut or Cancelled ?>
            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this reservation?');">
                <input type="hidden" name="action" value="cancel" />
                <button type="submit" class="btn btn-outline text-danger">Cancel</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Details & Booked Items -->
    <div class="col-lg-8">
        <!-- Guest & Stay Overview Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-info-circle"></i> Booking Overview</h5>
                <?= getReservationStatusBadge($res['Status']) ?>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted" style="font-size:12px; text-transform:uppercase;">Guest Name</label>
                        <div style="font-weight:600; font-size:15px;"><?= htmlspecialchars($res['GuestName']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted" style="font-size:12px; text-transform:uppercase;">Contact Number</label>
                        <div><?= !empty($res['GuestPhone']) ? htmlspecialchars($res['GuestPhone']) : '<span class="text-muted">None</span>' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted" style="font-size:12px; text-transform:uppercase;">Check-In Date</label>
                        <div><i class="bi bi-calendar-event text-success me-1"></i> <?= formatDateTime($res['CheckInDate']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted" style="font-size:12px; text-transform:uppercase;">Check-Out Date</label>
                        <div><i class="bi bi-calendar-event text-danger me-1"></i> <?= formatDateTime($res['CheckOutDate']) ?></div>
                    </div>
                    <?php if (!empty($res['SpecialRequests'])): ?>
                        <div class="col-12 mt-2">
                            <label class="text-muted" style="font-size:12px; text-transform:uppercase;">Special Requests / Notes</label>
                            <div class="p-2" style="background:var(--bg-hover); border-radius:8px; font-size:13px;">
                                <?= nl2br(htmlspecialchars($res['SpecialRequests'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Booked Items Breakdown Card -->
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-list-check"></i> Booked Accommodations & Services</h5>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item Description</th>
                            <th>Quantity / Nights</th>
                            <th>Unit Rate</th>
                            <th style="text-align:right;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($item['ItemName']) ?></strong>
                                    <span class="badge badge-secondary ms-1" style="font-size:10px;">
                                        <?= $item['ItemType'] == 0 ? 'Room' : ($item['ItemType'] == 1 ? 'Activity' : 'Venue') ?>
                                    </span>
                                </td>
                                <td><?= (int)$item['Quantity'] ?></td>
                                <td><?= formatCurrency($item['UnitPrice']) ?></td>
                                <td style="text-align:right;"><strong><?= formatCurrency($item['Subtotal']) ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align:right; font-weight:700;">Grand Total:</td>
                            <td style="text-align:right; font-weight:700; font-size:16px;" class="text-primary"><?= formatCurrency($totalAmount) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Payment History Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-credit-card"></i> Payment Transactions</h5>
                <button type="button" class="btn btn-sm btn-primary" onclick="new bootstrap.Modal(document.getElementById('paymentModal')).show()">
                    <i class="bi bi-plus me-1"></i> Add Payment
                </button>
            </div>
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Date & Time</th>
                            <th>Method</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Processed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No payments recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td><strong><?= !empty($p['ReferenceNumber']) ? htmlspecialchars($p['ReferenceNumber']) : '#' . $p['PaymentId'] ?></strong></td>
                                    <td><?= formatDateTime($p['PaymentDate']) ?></td>
                                    <td><span class="badge badge-secondary"><?= getPaymentMethodName($p['PaymentMethod']) ?></span></td>
                                    <td><?= getPaymentTypeName($p['PaymentType']) ?></td>
                                    <td><strong class="text-success"><?= formatCurrency($p['Amount']) ?></strong></td>
                                    <td><?= htmlspecialchars($p['ProcessorName'] ?? 'Staff') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Financial Summary Widget -->
    <div class="col-lg-4">
        <div class="card" style="position:sticky; top:85px;">
            <div class="card-header">
                <h5><i class="bi bi-wallet2"></i> Payment Summary</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Bill:</span>
                    <strong><?= formatCurrency($totalAmount) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Total Paid:</span>
                    <strong class="text-success"><?= formatCurrency($totalPaid) ?></strong>
                </div>
                <div class="d-flex justify-content-between pt-2 border-top mb-4" style="font-size:18px;">
                    <span style="font-weight:700;">Balance Due:</span>
                    <strong class="<?= $balance > 0 ? 'text-danger' : 'text-success' ?>"><?= formatCurrency($balance) ?></strong>
                </div>

                <?php if ($balance > 0): ?>
                    <button type="button" class="btn btn-success w-100 py-2 mb-2" onclick="openSettleBalanceModal(<?= $balance ?>)">
                        <i class="bi bi-cash me-1"></i> Settle Remaining Balance
                    </button>
                <?php else: ?>
                    <div class="alert alert-success text-center py-2 mb-2" style="border-radius:8px; font-size:13px;">
                        <i class="bi bi-check-circle-fill me-1"></i> Fully Settled
                    </div>
                <?php endif; ?>

                <a href="<?= url('/receipt/index.php?id=' . $res['ReservationId']) ?>" class="btn btn-outline w-100" target="_blank">
                    <i class="bi bi-printer me-1"></i> View Receipt
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content" style="border-radius:14px; background:var(--bg-surface);">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_payment" />
                <div class="modal-header">
                    <h6 class="modal-title" style="font-weight:700;">Record Payment</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Amount (₱) *</label>
                        <input type="number" step="0.01" name="amount" id="modalPaymentAmount" class="form-control" placeholder="0.00" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Payment Method</label>
                        <select name="payment_method" class="form-select">
                            <option value="0">Cash</option>
                            <option value="2">GCash</option>
                            <option value="3">Maya</option>
                            <option value="1">Card</option>
                            <option value="4">Bank Transfer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Payment Type</label>
                        <select name="payment_type" class="form-select">
                            <option value="3">Balance Settlement</option>
                            <option value="0">Downpayment</option>
                            <option value="1">Full Payment</option>
                            <option value="2">Partial</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Reference # / Note</label>
                        <input type="text" name="reference_number" class="form-control" placeholder="e.g. GCash Ref 123456" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openSettleBalanceModal(bal) {
    document.getElementById('modalPaymentAmount').value = bal.toFixed(2);
    new bootstrap.Modal(document.getElementById('paymentModal')).show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
