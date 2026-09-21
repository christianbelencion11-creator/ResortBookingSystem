<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$id = (int)($_GET['id'] ?? 0);
$resortSettings = getResortSettings();

$stmt = $pdo->prepare("SELECT r.*, 
    COALESCE(CONCAT(g.FirstName, ' ', g.LastName), CONCAT(u.FirstName, ' ', u.LastName), 'Guest') as GuestName,
    g.PhoneNumber as GuestPhone,
    g.Email as GuestEmail
    FROM reservations r
    LEFT JOIN guestrecords g ON r.GuestId = g.GuestId
    LEFT JOIN users u ON r.UserId = u.UserId
    WHERE r.ReservationId = ?");
$stmt->execute([$id]);
$res = $stmt->fetch();

if (!$res) {
    die("Reservation not found.");
}

// Fetch reservation items
$stmtItems = $pdo->prepare("SELECT ri.*, 
    CASE 
        WHEN ri.ItemType = 0 THEN (SELECT CONCAT('Room ', r.RoomNumber, ' - ', rt.TypeName) FROM rooms r JOIN roomtypes rt ON r.RoomTypeId = rt.RoomTypeId WHERE r.RoomId = ri.ReferenceId)
        WHEN ri.ItemType = 1 THEN (SELECT a.ActivityName FROM activities a WHERE a.ActivityId = ri.ReferenceId)
        WHEN ri.ItemType = 2 THEN (SELECT f.FacilityName FROM facilities f WHERE f.FacilityId = ri.ReferenceId)
        ELSE 'Item'
    END as ItemDescription
    FROM reservationitems ri WHERE ri.ReservationId = ?");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll();

// Fetch payments
$stmtPay = $pdo->prepare("SELECT * FROM payments WHERE ReservationId = ? AND Status = 1 ORDER BY PaymentDate ASC");
$stmtPay->execute([$id]);
$payments = $stmtPay->fetchAll();

$totalPaid = array_sum(array_column($payments, 'Amount'));
$balance = max(0, (float)$res['TotalAmount'] - $totalPaid);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Official Receipt - Booking #<?= $res['ReservationId'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
    <style>
        body {
            background: #f1f5f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 30px 15px;
            color: #1e293b;
        }
        .receipt-card {
            max-width: 780px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .header-logo {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 10px;
        }
        .badge-paid {
            background: #dcfce7;
            color: #15803d;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
        }
        .badge-due {
            background: #fee2e2;
            color: #b91c1c;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
        }
        @media print {
            body { background: transparent; padding: 0; }
            .receipt-card { box-shadow: none; border: none; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="receipt-card">
        <!-- Print / Back action buttons -->
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom no-print">
            <a href="<?= url('/reservations/details.php?id=' . $res['ReservationId']) ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Return to Booking
            </a>
            <button onclick="window.print()" class="btn btn-sm btn-primary">
                <i class="bi bi-printer me-1"></i> Print / Save as PDF
            </button>
        </div>

        <!-- Receipt Header -->
        <div class="row align-items-center mb-4">
            <div class="col-7 d-flex align-items-center gap-3">
                <?php if (!empty($resortSettings['LogoImage'])): ?>
                    <img src="<?= asset($resortSettings['LogoImage']) ?>" alt="Logo" class="header-logo" />
                <?php endif; ?>
                <div>
                    <h3 style="font-weight:800; margin:0; color:#0f172a;"><?= htmlspecialchars($resortSettings['ResortName']) ?></h3>
                    <div style="font-size:13px; color:#64748b;"><?= htmlspecialchars($resortSettings['ResortSubtitle']) ?></div>
                    <small style="color:#94a3b8; font-size:12px;"><?= htmlspecialchars($resortSettings['Address']) ?></small>
                </div>
            </div>
            <div class="col-5 text-end">
                <h4 style="font-weight:800; color:#0284c7; margin:0;">GUEST FOLIO</h4>
                <div style="font-weight:700; font-size:14px;">#<?= str_pad($res['ReservationId'], 6, '0', STR_PAD_LEFT) ?></div>
                <small class="text-muted">Date: <?= date('M d, Y h:i A') ?></small>
            </div>
        </div>

        <!-- Guest & Dates Info Box -->
        <div class="p-3 mb-4" style="background:#f8fafc; border-radius:10px; border:1px solid #e2e8f0;">
            <div class="row g-3" style="font-size:13px;">
                <div class="col-sm-6">
                    <span class="text-muted text-uppercase" style="font-size:11px; font-weight:600;">Guest Name:</span>
                    <div style="font-weight:700; font-size:15px;"><?= htmlspecialchars($res['GuestName']) ?></div>
                    <div class="text-muted"><?= htmlspecialchars($res['GuestPhone'] ?: '') ?> <?= htmlspecialchars($res['GuestEmail'] ? '| ' . $res['GuestEmail'] : '') ?></div>
                </div>
                <div class="col-sm-6 text-sm-end">
                    <span class="text-muted text-uppercase" style="font-size:11px; font-weight:600;">Stay Duration:</span>
                    <div><strong>Check-In:</strong> <?= formatDate($res['CheckInDate']) ?></div>
                    <div><strong>Check-Out:</strong> <?= formatDate($res['CheckOutDate']) ?></div>
                </div>
            </div>
        </div>

        <!-- Itemized Charges -->
        <table class="table table-bordered mb-4" style="font-size:13px;">
            <thead class="table-light">
                <tr>
                    <th>Description</th>
                    <th style="text-align:center; width:100px;">Quantity</th>
                    <th style="text-align:right; width:130px;">Rate</th>
                    <th style="text-align:right; width:130px;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($item['ItemDescription']) ?></strong></td>
                        <td style="text-align:center;"><?= (int)$item['Quantity'] ?></td>
                        <td style="text-align:right;"><?= formatCurrency($item['UnitPrice']) ?></td>
                        <td style="text-align:right;"><strong><?= formatCurrency($item['Subtotal']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align:right; font-weight:700;">Subtotal:</td>
                    <td style="text-align:right; font-weight:700;"><?= formatCurrency($res['TotalAmount']) ?></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:right; font-weight:700;">Total Payments Applied:</td>
                    <td style="text-align:right; font-weight:700; color:#15803d;"><?= formatCurrency($totalPaid) ?></td>
                </tr>
                <tr style="font-size:16px;">
                    <td colspan="3" style="text-align:right; font-weight:800;">Balance Due:</td>
                    <td style="text-align:right; font-weight:800; color:<?= $balance > 0 ? '#dc2626' : '#15803d' ?>;">
                        <?= formatCurrency($balance) ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <!-- Payments Breakdown -->
        <?php if (!empty($payments)): ?>
            <h6 style="font-weight:700; font-size:13px; text-transform:uppercase; color:#64748b; margin-bottom:8px;">Payment Details</h6>
            <table class="table table-sm table-striped mb-4" style="font-size:12px;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Ref #</th>
                        <th>Method</th>
                        <th>Type</th>
                        <th style="text-align:right;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= formatDate($p['PaymentDate']) ?></td>
                            <td><?= htmlspecialchars($p['ReferenceNumber'] ?: 'RCPT-' . $p['PaymentId']) ?></td>
                            <td><?= getPaymentMethodName($p['PaymentMethod']) ?></td>
                            <td><?= getPaymentTypeName($p['PaymentType']) ?></td>
                            <td style="text-align:right; font-weight:600;"><?= formatCurrency($p['Amount']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Footer Notice -->
        <div class="text-center pt-3 border-top" style="font-size:12px; color:#94a3b8;">
            <p style="margin:0 0 4px;">Thank you for choosing <?= htmlspecialchars($resortSettings['ResortName']) ?>! We hope you enjoyed your stay.</p>
            <p style="margin:0;">For questions or inquiries, contact us at <?= htmlspecialchars($resortSettings['Phone']) ?> or <?= htmlspecialchars($resortSettings['Email']) ?></p>
        </div>
    </div>
</body>
</html>
