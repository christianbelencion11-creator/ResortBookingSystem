<?php
$pageTitle = 'Room Availability Calendar';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// Date navigation
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

// Wrap year/month
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$monthStart = sprintf('%04d-%02d-01 00:00:00', $year, $month);
$monthEnd = sprintf('%04d-%02d-%02d 23:59:59', $year, $month, $daysInMonth);
$monthName = date('F Y', strtotime($monthStart));

// Fetch all rooms
$rooms = $pdo->query("SELECT r.*, rt.TypeName FROM rooms r JOIN roomtypes rt ON r.RoomTypeId = rt.RoomTypeId ORDER BY r.Floor ASC, r.RoomNumber ASC")->fetchAll();

// Fetch reservations covering this month
$stmtBookings = $pdo->prepare("SELECT r.ReservationId, r.CheckInDate, r.CheckOutDate, r.Status, ri.ReferenceId as RoomId,
    COALESCE(CONCAT(g.FirstName, ' ', g.LastName), CONCAT(u.FirstName, ' ', u.LastName), 'Guest') AS GuestName
    FROM reservations r
    JOIN reservationitems ri ON r.ReservationId = ri.ReservationId
    LEFT JOIN guestrecords g ON r.GuestId = g.GuestId
    LEFT JOIN users u ON r.UserId = u.UserId
    WHERE ri.ItemType = 0 
      AND r.Status != 4 
      AND r.CheckInDate <= ? 
      AND r.CheckOutDate >= ?");
$stmtBookings->execute([$monthEnd, $monthStart]);
$bookings = $stmtBookings->fetchAll();

// Build day-to-booking map per room
$roomDayMap = [];
foreach ($bookings as $b) {
    $roomId = $b['RoomId'];
    $checkIn = strtotime($b['CheckInDate']);
    $checkOut = strtotime($b['CheckOutDate']);

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $dayTs = strtotime(sprintf('%04d-%02d-%02d', $year, $month, $d));
        if ($dayTs >= $checkIn && $dayTs < $checkOut) {
            $roomDayMap[$roomId][$d] = $b;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="margin:0; font-weight:700;">Availability Calendar</h4>
        <p class="text-muted" style="margin:0; font-size:14px;">Interactive room occupancy and booking schedule</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?= url('/rooms/calendar.php?year=' . ($month == 1 ? $year - 1 : $year) . '&month=' . ($month == 1 ? 12 : $month - 1)) ?>" class="btn btn-outline btn-sm">
            <i class="bi bi-chevron-left"></i> Prev
        </a>
        <span style="font-weight:700; font-size:16px; min-width:140px; text-align:center;"><?= $monthName ?></span>
        <a href="<?= url('/rooms/calendar.php?year=' . ($month == 12 ? $year + 1 : $year) . '&month=' . ($month == 12 ? 1 : $month + 1)) ?>" class="btn btn-outline btn-sm">
            Next <i class="bi bi-chevron-right"></i>
        </a>
        <a href="<?= url('/rooms/calendar.php') ?>" class="btn btn-sm btn-outline ms-2">Today</a>
    </div>
</div>

<!-- Calendar Legend -->
<div class="d-flex flex-wrap gap-3 mb-3 p-3 card" style="flex-direction:row; font-size:13px;">
    <div class="d-flex align-items-center gap-1">
        <div style="width:14px; height:14px; border-radius:3px; background:var(--success, #10b981);"></div> Available
    </div>
    <div class="d-flex align-items-center gap-1">
        <div style="width:14px; height:14px; border-radius:3px; background:var(--info, #0284c7);"></div> Occupied (Checked-in)
    </div>
    <div class="d-flex align-items-center gap-1">
        <div style="width:14px; height:14px; border-radius:3px; background:#f59e0b;"></div> Confirmed Booking
    </div>
    <div class="d-flex align-items-center gap-1">
        <div style="width:14px; height:14px; border-radius:3px; background:#64748b;"></div> Pending Reservation
    </div>
</div>

<!-- Calendar Matrix -->
<div class="card">
    <div class="table-responsive" style="max-height: 650px;">
        <table class="table table-bordered mb-0" style="font-size:12px; min-width: 900px;">
            <thead class="sticky-top" style="background:var(--bg-surface); z-index:2;">
                <tr>
                    <th style="min-width:120px; position:sticky; left:0; background:var(--bg-surface); z-index:3;">Room</th>
                    <?php for ($d = 1; $d <= $daysInMonth; $d++): 
                        $isToday = ($year == date('Y') && $month == date('n') && $d == date('j'));
                    ?>
                        <th style="text-align:center; min-width:32px; padding:6px 2px; <?= $isToday ? 'background:rgba(2,132,199,0.15); font-weight:bold; color:var(--primary);' : '' ?>">
                            <?= $d ?>
                        </th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $r): ?>
                    <tr>
                        <td style="position:sticky; left:0; background:var(--bg-surface); font-weight:600; z-index:1;">
                            <?= htmlspecialchars($r['RoomNumber']) ?>
                            <small class="d-block text-muted" style="font-size:10px; font-weight:normal;"><?= htmlspecialchars($r['TypeName']) ?></small>
                        </td>
                        <?php for ($d = 1; $d <= $daysInMonth; $d++): 
                            $booking = $roomDayMap[$r['RoomId']][$d] ?? null;
                            $bg = 'var(--bg-surface)';
                            $title = 'Available';
                            $content = '';

                            if ($booking) {
                                $status = (int)$booking['Status'];
                                if ($status === 2) {
                                    $bg = 'var(--info, #0284c7)'; // Checked In
                                } elseif ($status === 1) {
                                    $bg = '#f59e0b'; // Confirmed
                                } else {
                                    $bg = '#64748b'; // Pending
                                }
                                $title = "Booking #" . $booking['ReservationId'] . " - " . $booking['GuestName'];
                                $content = '<i class="bi bi-person-fill text-white" style="font-size:11px;"></i>';
                            }
                        ?>
                            <td style="text-align:center; padding:0; height:36px; background:<?= $bg ?>;" title="<?= htmlspecialchars($title) ?>">
                                <?php if ($booking): ?>
                                    <a href="<?= url('/reservations/details.php?id=' . $booking['ReservationId']) ?>" style="display:block; width:100%; height:100%; line-height:36px; text-decoration:none;">
                                        <?= $content ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
