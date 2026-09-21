<?php
$pageTitle = 'Payments & Transactions';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// Record Payment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    $resId = (int)($_POST['reservation_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $method = (int)($_POST['payment_method'] ?? 0);
    $type = (int)($_POST['payment_type'] ?? 1);
    $refNum = trim($_POST['reference_number'] ?? '');
    $userId = currentUser()['id'];

    if ($resId > 0 && $amount > 0) {
        $stmtPay = $pdo->prepare("INSERT INTO payments (ReservationId, Amount, PaymentMethod, PaymentType, ReferenceNumber, PaymentDate, Status, ProcessedBy) VALUES (?, ?, ?, ?, ?, NOW(), 1, ?)");
        $stmtPay->execute([$resId, $amount, $method, $type, $refNum, $userId]);

        logAudit('Payment', 'Payments', "Logged payment of " . formatCurrency($amount) . " for Booking #$resId", 'badge-success');
        setFlash('success', 'Payment recorded successfully!');
        header("Location: " . url('/payments/index.php'));
        exit;
    }
}

// Payment method filter
$methodFilter = isset($_GET['method']) && $_GET['method'] !== '' ? (int)$_GET['method'] : null;

$query = "SELECT p.*, r.TotalAmount,
    COALESCE(CONCAT(g.FirstName, ' ', g.LastName), CONCAT(u.FirstName, ' ', u.LastName), 'Guest') as GuestName,
    CONCAT(staff.FirstName, ' ', staff.LastName) as StaffName
    FROM payments p
    JOIN reservations r ON p.ReservationId = r.ReservationId
    LEFT JOIN guestrecords g ON r.GuestId = g.GuestId
    LEFT JOIN users u ON r.UserId = u.UserId
    LEFT JOIN users staff ON p.ProcessedBy = staff.UserId
    WHERE 1=1";
$params = [];

if ($methodFilter !== null) {
    $query .= " AND p.PaymentMethod = ?";
    $params[] = $methodFilter;
}

$query .= " ORDER BY p.PaymentDate DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Financial Totals
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(Amount), 0) FROM payments WHERE Status = 1")->fetchColumn();
$cashTotal = (float)$pdo->query("SELECT COALESCE(SUM(Amount), 0) FROM payments WHERE Status = 1 AND PaymentMethod = 0")->fetchColumn();
$gcashTotal = (float)$pdo->query("SELECT COALESCE(SUM(Amount), 0) FROM payments WHERE Status = 1 AND PaymentMethod = 2")->fetchColumn();
$cardTotal = (float)$pdo->query("SELECT COALESCE(SUM(Amount), 0) FROM payments WHERE Status = 1 AND PaymentMethod = 1")->fetchColumn();

// Active reservations needing payment for modal dropdown
$unpaidReservations = $pdo->query("SELECT r.ReservationId, r.TotalAmount,
    COALESCE(CONCAT(g.FirstName, ' ', g.LastName), CONCAT(u.FirstName, ' ', u.LastName), 'Guest') as GuestName,
    (SELECT COALESCE(SUM(p.Amount), 0) FROM payments p WHERE p.ReservationId = r.ReservationId AND p.Status = 1) as PaidAmount
    FROM reservations r
    LEFT JOIN guestrecords g ON r.GuestId = g.GuestId
    LEFT JOIN users u ON r.UserId = u.UserId
    WHERE r.Status IN (0, 1, 2)
    ORDER BY r.CreatedAt DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="margin:0; font-weight:700;">Payments & Cashier</h4>
        <p class="text-muted" style="margin:0; font-size:14px;">Transaction audit trail, payment gateways, and guest receipt generation</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="new bootstrap.Modal(document.getElementById('newPaymentModal')).show()">
        <i class="bi bi-plus-lg me-1"></i> Receive Payment
    </button>
</div>

<!-- Revenue Stats Grid -->
<div class="stats-grid mb-4">
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?= formatCurrency($totalRevenue) ?></div>
                <div class="stat-label">Total Collections</div>
            </div>
            <div class="stat-icon green"><i class="bi bi-cash-stack"></i></div>
        </div>
        <div class="stat-change up"><i class="bi bi-check-circle"></i> All recorded channels</div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?= formatCurrency($cashTotal) ?></div>
                <div class="stat-label">Cash on Hand</div>
            </div>
            <div class="stat-icon blue"><i class="bi bi-wallet2"></i></div>
        </div>
        <div class="stat-change"><i class="bi bi-cash"></i> Front desk cash drawer</div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?= formatCurrency($gcashTotal) ?></div>
                <div class="stat-label">GCash Total</div>
            </div>
            <div class="stat-icon blue"><i class="bi bi-phone"></i></div>
        </div>
        <div class="stat-change up"><i class="bi bi-qr-code"></i> E-wallet payments</div>
    </div>
    <div class="stat-card">
        <div class="stat-header">
            <div>
                <div class="stat-value"><?= formatCurrency($cardTotal) ?></div>
                <div class="stat-label">Card / Bank</div>
            </div>
            <div class="stat-icon orange"><i class="bi bi-credit-card-2-front"></i></div>
        </div>
        <div class="stat-change"><i class="bi bi-bank"></i> Terminal transactions</div>
    </div>
</div>

<!-- Payments Ledger Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5><i class="bi bi-journal-check"></i> Transaction Records</h5>
        <div class="d-flex align-items-center gap-2">
            <span class="text-muted" style="font-size:13px;">Method:</span>
            <select class="form-select form-select-sm" style="width:auto;" onchange="location.href='<?= url("/payments/index.php") ?>?method=' + this.value">
                <option value="">All Methods</option>
                <option value="0" <?= $methodFilter === 0 ? 'selected' : '' ?>>Cash</option>
                <option value="2" <?= $methodFilter === 2 ? 'selected' : '' ?>>GCash</option>
                <option value="3" <?= $methodFilter === 3 ? 'selected' : '' ?>>Maya</option>
                <option value="1" <?= $methodFilter === 1 ? 'selected' : '' ?>>Credit / Debit Card</option>
                <option value="4" <?= $methodFilter === 4 ? 'selected' : '' ?>>Bank Transfer</option>
            </select>
        </div>
    </div>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Receipt #</th>
                    <th>Booking #</th>
                    <th>Guest</th>
                    <th>Date & Time</th>
                    <th>Payment Method</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Cashier / Staff</th>
                    <th style="text-align:right;">Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">No payment transactions found.</td></tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><strong><?= !empty($p['ReferenceNumber']) ? htmlspecialchars($p['ReferenceNumber']) : 'RCPT-' . str_pad($p['PaymentId'], 5, '0', STR_PAD_LEFT) ?></strong></td>
                            <td>
                                <a href="<?= url('/reservations/details.php?id=' . $p['ReservationId']) ?>" style="font-weight:600; text-decoration:none;">
                                    #<?= $p['ReservationId'] ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($p['GuestName']) ?></td>
                            <td><?= formatDateTime($p['PaymentDate']) ?></td>
                            <td><span class="badge badge-secondary"><?= getPaymentMethodName($p['PaymentMethod']) ?></span></td>
                            <td><?= getPaymentTypeName($p['PaymentType']) ?></td>
                            <td><strong class="text-success" style="font-size:14px;"><?= formatCurrency($p['Amount']) ?></strong></td>
                            <td><small class="text-muted"><?= htmlspecialchars($p['StaffName'] ?: 'System') ?></small></td>
                            <td style="text-align:right;">
                                <a href="<?= url('/receipt/index.php?id=' . $p['ReservationId']) ?>" class="btn btn-xs btn-outline" target="_blank" title="Print Official Receipt">
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

<!-- Modal: Receive Payment -->
<div class="modal fade" id="newPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; background:var(--bg-surface);">
            <form method="POST" action="">
                <input type="hidden" name="action" value="record_payment" />
                <div class="modal-header">
                    <h6 class="modal-title" style="font-weight:700;">Receive / Process Payment</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Select Reservation *</label>
                        <select name="reservation_id" id="modalResSelect" class="form-select" required onchange="updateModalBalance()">
                            <option value="">-- Choose Reservation --</option>
                            <?php foreach ($unpaidReservations as $ur): 
                                $bal = max(0, (float)$ur['TotalAmount'] - (float)$ur['PaidAmount']);
                            ?>
                                <option value="<?= $ur['ReservationId'] ?>" data-balance="<?= $bal ?>">
                                    #<?= $ur['ReservationId'] ?> - <?= htmlspecialchars($ur['GuestName']) ?> (Balance: <?= formatCurrency($bal) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Amount Paid (₱) *</label>
                            <input type="number" step="0.01" name="amount" id="modalPayAmount" class="form-control" placeholder="0.00" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="0">Cash</option>
                                <option value="2">GCash</option>
                                <option value="3">Maya</option>
                                <option value="1">Credit / Debit Card</option>
                                <option value="4">Bank Transfer</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Payment Type</label>
                            <select name="payment_type" class="form-select">
                                <option value="3">Balance Settlement</option>
                                <option value="0">Downpayment</option>
                                <option value="1">Full Payment</option>
                                <option value="2">Partial</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Reference / OR #</label>
                            <input type="text" name="reference_number" class="form-control" placeholder="Optional" />
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Save Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateModalBalance() {
    var sel = document.getElementById('modalResSelect');
    var opt = sel.options[sel.selectedIndex];
    var bal = opt && opt.getAttribute('data-balance') ? parseFloat(opt.getAttribute('data-balance')) : 0;
    if (bal > 0) {
        document.getElementById('modalPayAmount').value = bal.toFixed(2);
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
