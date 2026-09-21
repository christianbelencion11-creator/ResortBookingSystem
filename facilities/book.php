<?php
$pageTitle = 'Book Venue or Facility';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$facilities = $pdo->query("SELECT * FROM facilities WHERE IsActive = 1 ORDER BY FacilityName")->fetchAll();
$selectedFacId = (int)($_GET['facility_id'] ?? 0);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $facId = (int)($_POST['facility_id'] ?? 0);
    $guestName = trim($_POST['guest_name'] ?? '');
    $guestPhone = trim($_POST['guest_phone'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? date('Y-m-d'));
    $startTime = trim($_POST['start_time'] ?? '09:00');
    $endTime = trim($_POST['end_time'] ?? '17:00');
    $specialRequests = trim($_POST['special_requests'] ?? '');

    if (empty($facId) || empty($guestName) || empty($eventDate)) {
        $error = "Please select a facility, enter guest name, and pick an event date.";
    } else {
        // Fetch facility details
        $stmtF = $pdo->prepare("SELECT * FROM facilities WHERE FacilityId = ?");
        $stmtF->execute([$facId]);
        $fac = $stmtF->fetch();

        if ($fac) {
            // Create guest record if not exists
            $nameParts = explode(' ', $guestName, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';
            $stmtG = $pdo->prepare("INSERT INTO guestrecords (FirstName, LastName, PhoneNumber, CreatedAt) VALUES (?, ?, ?, NOW())");
            $stmtG->execute([$firstName, $lastName, $guestPhone]);
            $guestId = $pdo->lastInsertId();

            $totalAmount = (float)$fac['RentalPrice'];

            // Create reservation
            $user = currentUser();
            $userId = $user ? $user['id'] : 1;
            $checkIn = $eventDate . ' ' . $startTime . ':00';
            $checkOut = $eventDate . ' ' . $endTime . ':00';

            $stmtRes = $pdo->prepare("INSERT INTO reservations (UserId, GuestId, CheckInDate, CheckOutDate, TotalAmount, Status, SpecialRequests, CreatedAt, UpdatedAt) VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW())");
            $stmtRes->execute([$userId, $guestId, $checkIn, $checkOut, $totalAmount, "Facility Booking: " . $fac['FacilityName'] . " | " . $specialRequests]);
            $resId = $pdo->lastInsertId();

            // Add reservation item (ItemType 2 = Facility)
            $stmtItem = $pdo->prepare("INSERT INTO reservationitems (ReservationId, ItemType, ReferenceId, Quantity, UnitPrice, Subtotal, CreatedAt) VALUES (?, 2, ?, 1, ?, ?, NOW())");
            $stmtItem->execute([$resId, $facId, $totalAmount, $totalAmount]);

            // Add facility schedule
            $stmtSched = $pdo->prepare("INSERT INTO facilityschedules (FacilityId, ScheduleDate, StartTime, EndTime, Status, CreatedAt) VALUES (?, ?, ?, ?, 1, NOW())");
            $stmtSched->execute([$facId, $eventDate, $startTime, $endTime]);

            logAudit('Create', 'Facilities', "Booked venue {$fac['FacilityName']} for $guestName on $eventDate", 'badge-success');
            addNotification('Venue Booking Confirmed', "{$fac['FacilityName']} reserved for $guestName on " . formatDate($eventDate), 'success', 'bi-building-check', url("/reservations/details.php?id=$resId"));

            setFlash('success', "Venue reservation created successfully! Booking #$resId");
            header("Location: " . url("/reservations/details.php?id=$resId"));
            exit;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= url('/facilities/index.php') ?>" class="btn btn-outline btn-sm mb-2">
        <i class="bi bi-arrow-left me-1"></i> Back to Facilities
    </a>
    <h4 style="margin:0; font-weight:700;">Book Facility / Event Venue</h4>
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
                <label class="form-label" style="font-weight:600; font-size:13px;">Select Facility *</label>
                <select name="facility_id" class="form-select" required>
                    <option value="">-- Choose Venue --</option>
                    <?php foreach ($facilities as $f): ?>
                        <option value="<?= $f['FacilityId'] ?>" <?= ($selectedFacId === (int)$f['FacilityId']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['FacilityName']) ?> - <?= formatCurrency($f['RentalPrice']) ?> (Max <?= $f['Capacity'] ?> pax)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Guest / Organizer Name *</label>
                    <input type="text" name="guest_name" class="form-control" placeholder="Full Name or Company" required />
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Contact Number</label>
                    <input type="text" name="guest_phone" class="form-control" placeholder="09171234567" />
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Event Date *</label>
                    <input type="date" name="event_date" class="form-control" value="<?= date('Y-m-d') ?>" required />
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Start Time</label>
                    <input type="time" name="start_time" class="form-control" value="09:00" />
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-weight:600; font-size:13px;">End Time</label>
                    <input type="time" name="end_time" class="form-control" value="17:00" />
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label" style="font-weight:600; font-size:13px;">Special Requests / Setup Details</label>
                <textarea name="special_requests" class="form-control" rows="3" placeholder="Audio visual, table arrangement, catering notes..."></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="<?= url('/facilities/index.php') ?>" class="btn btn-outline">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle me-1"></i> Confirm Venue Booking
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
