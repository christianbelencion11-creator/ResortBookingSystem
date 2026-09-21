<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// If download requested
if (isset($_GET['type'])) {
    $type = $_GET['type'];
    $filename = "resort_" . $type . "_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $output = fopen('php://output', 'w');

    if ($type === 'reservations') {
        fputcsv($output, ['Booking ID', 'Guest Name', 'Phone', 'Check-In', 'Check-Out', 'Status', 'Total Amount', 'Created At']);
        $stmt = $pdo->query("SELECT r.ReservationId, 
            COALESCE(CONCAT(g.FirstName, ' ', g.LastName), CONCAT(u.FirstName, ' ', u.LastName), 'Guest') as GuestName,
            g.PhoneNumber, r.CheckInDate, r.CheckOutDate, r.Status, r.TotalAmount, r.CreatedAt
            FROM reservations r
            LEFT JOIN guestrecords g ON r.GuestId = g.GuestId
            LEFT JOIN users u ON r.UserId = u.UserId
            ORDER BY r.ReservationId DESC");
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['ReservationId'],
                $row['GuestName'],
                $row['PhoneNumber'],
                $row['CheckInDate'],
                $row['CheckOutDate'],
                getReservationStatusName($row['Status']),
                $row['TotalAmount'],
                $row['CreatedAt']
            ]);
        }
        exit;
    }

    if ($type === 'payments') {
        fputcsv($output, ['Payment ID', 'Reservation ID', 'Amount', 'Method', 'Type', 'Ref #', 'Payment Date']);
        $stmt = $pdo->query("SELECT * FROM payments ORDER BY PaymentId DESC");
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['PaymentId'],
                $row['ReservationId'],
                $row['Amount'],
                getPaymentMethodName($row['PaymentMethod']),
                getPaymentTypeName($row['PaymentType']),
                $row['ReferenceNumber'],
                $row['PaymentDate']
            ]);
        }
        exit;
    }

    if ($type === 'expenses') {
        fputcsv($output, ['Expense ID', 'Category', 'Description', 'Amount', 'Date', 'Recorded By']);
        $stmt = $pdo->query("SELECT * FROM expenses ORDER BY ExpenseId DESC");
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['ExpenseId'],
                $row['Category'],
                $row['Description'],
                $row['Amount'],
                $row['ExpenseDate'],
                $row['RecordedBy']
            ]);
        }
        exit;
    }

    if ($type === 'rooms') {
        fputcsv($output, ['Room ID', 'Room Number', 'Floor', 'Type', 'Rate', 'Status']);
        $stmt = $pdo->query("SELECT r.*, rt.TypeName, rt.BasePrice FROM rooms r JOIN roomtypes rt ON r.RoomTypeId = rt.RoomTypeId ORDER BY r.RoomNumber");
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['RoomId'],
                $row['RoomNumber'],
                $row['Floor'],
                $row['TypeName'],
                $row['BasePrice'],
                getRoomStatusName($row['Status'])
            ]);
        }
        exit;
    }
}

$pageTitle = 'Export Data';
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= url('/reports/index.php') ?>" class="btn btn-outline btn-sm mb-2">
        <i class="bi bi-arrow-left me-1"></i> Back to Reports
    </a>
    <h4 style="margin:0; font-weight:700;">Export Resort Data</h4>
    <p class="text-muted" style="margin:0; font-size:14px;">Download operational ledgers, transactions, and inventory in standard CSV format</p>
</div>

<div class="row g-4" style="max-width: 900px;">
    <!-- Export Reservations -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-calendar-check text-primary" style="font-size:24px;"></i>
                        <h5 style="margin:0;">Reservations Ledger</h5>
                    </div>
                    <p class="text-muted" style="font-size:13px;">Full export of all guest reservations, stay dates, guest contact info, and total amounts.</p>
                </div>
                <a href="<?= url('/reports/export.php?type=reservations') ?>" class="btn btn-primary w-100 mt-3">
                    <i class="bi bi-download me-1"></i> Download Reservations (CSV)
                </a>
            </div>
        </div>
    </div>

    <!-- Export Payments -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-credit-card text-success" style="font-size:24px;"></i>
                        <h5 style="margin:0;">Payment Transactions</h5>
                    </div>
                    <p class="text-muted" style="font-size:13px;">Complete transaction journal with cashier name, payment gateway (GCash/Cash/Card), and amounts.</p>
                </div>
                <a href="<?= url('/reports/export.php?type=payments') ?>" class="btn btn-success w-100 mt-3">
                    <i class="bi bi-download me-1"></i> Download Payments (CSV)
                </a>
            </div>
        </div>
    </div>

    <!-- Export Expenses -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-wallet2 text-warning" style="font-size:24px;"></i>
                        <h5 style="margin:0;">Operating Expenses</h5>
                    </div>
                    <p class="text-muted" style="font-size:13px;">Comprehensive record of resort expenditures, utilities, repair costs, and operational supplies.</p>
                </div>
                <a href="<?= url('/reports/export.php?type=expenses') ?>" class="btn btn-warning w-100 mt-3">
                    <i class="bi bi-download me-1"></i> Download Expenses (CSV)
                </a>
            </div>
        </div>
    </div>

    <!-- Export Rooms -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-door-open text-info" style="font-size:24px;"></i>
                        <h5 style="margin:0;">Rooms & Inventory</h5>
                    </div>
                    <p class="text-muted" style="font-size:13px;">Export current room inventory, room types, pricing matrix, and current occupancy status.</p>
                </div>
                <a href="<?= url('/reports/export.php?type=rooms') ?>" class="btn btn-outline-info w-100 mt-3">
                    <i class="bi bi-download me-1"></i> Download Room Inventory (CSV)
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
