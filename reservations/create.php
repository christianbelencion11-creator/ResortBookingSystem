<?php
$pageTitle = 'New Reservation';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

// Fetch available rooms
$rooms = $pdo->query("SELECT r.*, rt.TypeName, rt.BasePrice, rt.MaxOccupancy 
                      FROM rooms r 
                      JOIN roomtypes rt ON r.RoomTypeId = rt.RoomTypeId 
                      WHERE r.Status = 0 
                      ORDER BY r.Floor ASC, r.RoomNumber ASC")->fetchAll();

// Fetch active activities
$activities = $pdo->query("SELECT * FROM activities WHERE IsActive = 1 ORDER BY ActivityName")->fetchAll();

// Fetch active facilities
$facilities = $pdo->query("SELECT * FROM facilities WHERE IsActive = 1 ORDER BY FacilityName")->fetchAll();

// Fetch discount codes
$discountCodes = $pdo->query("SELECT * FROM discountcodes WHERE IsActive = 1")->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guestName = trim($_POST['guest_name'] ?? '');
    $guestPhone = trim($_POST['guest_phone'] ?? '');
    $guestEmail = trim($_POST['guest_email'] ?? '');
    $checkInDate = trim($_POST['check_in_date'] ?? date('Y-m-d'));
    $checkOutDate = trim($_POST['check_out_date'] ?? date('Y-m-d', strtotime('+1 day')));
    $specialRequests = trim($_POST['special_requests'] ?? '');
    $itemsJson = $_POST['items_json'] ?? '[]';
    $discountCodeInput = strtoupper(trim($_POST['discount_code'] ?? ''));
    $downpayment = (float)($_POST['downpayment'] ?? 0);
    $paymentMethod = (int)($_POST['payment_method'] ?? 0);

    $items = json_decode($itemsJson, true);

    if (empty($guestName)) {
        $error = "Guest Name is required.";
    } elseif (empty($items) || !is_array($items)) {
        $error = "Please add at least one room, activity, or facility to the reservation.";
    } elseif (strtotime($checkOutDate) <= strtotime($checkInDate)) {
        $error = "Check-Out date must be after Check-In date.";
    } else {
        // Create or find guest
        $nameParts = explode(' ', $guestName, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        $stmtGuest = $pdo->prepare("INSERT INTO guestrecords (FirstName, LastName, Email, PhoneNumber, CreatedAt) VALUES (?, ?, ?, ?, NOW())");
        $stmtGuest->execute([$firstName, $lastName, $guestEmail, $guestPhone]);
        $guestId = $pdo->lastInsertId();

        // Calculate total
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += (float)$item['subtotal'];
        }

        // Apply discount code if valid
        $discountAmount = 0;
        if (!empty($discountCodeInput)) {
            $stmtDisc = $pdo->prepare("SELECT * FROM discountcodes WHERE Code = ? AND IsActive = 1 AND (ValidUntil IS NULL OR ValidUntil >= CURDATE())");
            $stmtDisc->execute([$discountCodeInput]);
            $dc = $stmtDisc->fetch();
            if ($dc) {
                if ($dc['DiscountPercent'] > 0) {
                    $discountAmount = ($subtotal * ($dc['DiscountPercent'] / 100));
                } elseif ($dc['FixedAmount'] > 0) {
                    $discountAmount = min($subtotal, (float)$dc['FixedAmount']);
                }
                // Increment usage
                $pdo->prepare("UPDATE discountcodes SET UsageCount = UsageCount + 1 WHERE DiscountCodeId = ?")->execute([$dc['DiscountCodeId']]);
            }
        }

        $totalAmount = max(0, $subtotal - $discountAmount);
        $user = currentUser();
        $userId = $user ? $user['id'] : 1;

        // Insert reservation
        $stmtRes = $pdo->prepare("INSERT INTO reservations (UserId, GuestId, CheckInDate, CheckOutDate, TotalAmount, Status, SpecialRequests, CreatedAt, UpdatedAt) VALUES (?, ?, ?, ?, ?, 1, ?, NOW(), NOW())");
        $stmtRes->execute([$userId, $guestId, $checkInDate . ' 14:00:00', $checkOutDate . ' 12:00:00', $totalAmount, $specialRequests]);
        $reservationId = $pdo->lastInsertId();

        // Insert reservation items
        $stmtItem = $pdo->prepare("INSERT INTO reservationitems (ReservationId, ItemType, ReferenceId, Quantity, UnitPrice, Subtotal, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        foreach ($items as $item) {
            $itemType = (int)$item['type']; // 0 = Room, 1 = Activity, 2 = Facility
            $refId = (int)$item['ref_id'];
            $qty = (int)$item['qty'];
            $unitPrice = (float)$item['unit_price'];
            $itemSubtotal = (float)$item['subtotal'];

            $stmtItem->execute([$reservationId, $itemType, $refId, $qty, $unitPrice, $itemSubtotal]);

            // If room, mark as Reserved
            if ($itemType === 0) {
                $pdo->prepare("UPDATE rooms SET Status = 3, UpdatedAt = NOW() WHERE RoomId = ?")->execute([$refId]);
            }
        }

        // Record initial payment / downpayment if provided
        if ($downpayment > 0) {
            $stmtPay = $pdo->prepare("INSERT INTO payments (ReservationId, Amount, PaymentMethod, PaymentType, PaymentDate, Status, ProcessedBy) VALUES (?, ?, ?, ?, NOW(), 1, ?)");
            $payType = ($downpayment >= $totalAmount) ? 1 : 0; // 1 = Full, 0 = Downpayment
            $stmtPay->execute([$reservationId, $downpayment, $paymentMethod, $payType, $userId]);
        }

        logAudit('Create', 'Reservations', "Created Booking #$reservationId for $guestName ($totalAmount)", 'badge-success');
        addNotification('New Reservation', "Booking #$reservationId created for $guestName (" . formatCurrency($totalAmount) . ")", 'info', 'bi-calendar-check', url("/reservations/details.php?id=$reservationId"));

        setFlash('success', "Reservation #$reservationId successfully created!");
        header("Location: " . url("/reservations/details.php?id=$reservationId"));
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= url('/reservations/index.php') ?>" class="btn btn-outline btn-sm mb-2">
        <i class="bi bi-arrow-left me-1"></i> Back to Reservations
    </a>
    <h4 style="margin:0; font-weight:700;">Create New Reservation</h4>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger mb-4" style="border-radius:10px;">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<form method="POST" action="" id="bookingForm">
    <input type="hidden" name="items_json" id="itemsJsonInput" value="[]" />

    <div class="row g-4">
        <!-- Left: Guest Details & Dates -->
        <div class="col-lg-7">
            <!-- Guest Info Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="bi bi-person"></i> Guest Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Guest Full Name *</label>
                        <input type="text" name="guest_name" class="form-control" placeholder="e.g. John Santos" required value="<?= htmlspecialchars($_POST['guest_name'] ?? '') ?>" />
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Mobile Phone</label>
                            <input type="text" name="guest_phone" class="form-control" placeholder="09171234567" value="<?= htmlspecialchars($_POST['guest_phone'] ?? '') ?>" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Email Address</label>
                            <input type="email" name="guest_email" class="form-control" placeholder="guest@example.com" value="<?= htmlspecialchars($_POST['guest_email'] ?? '') ?>" />
                        </div>
                    </div>
                    <div>
                        <label class="form-label" style="font-weight:600; font-size:13px;">Special Requests / Notes</label>
                        <textarea name="special_requests" class="form-control" rows="2" placeholder="Late arrival, extra pillows, sea view..."><?= htmlspecialchars($_POST['special_requests'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Dates Card -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="bi bi-calendar-range"></i> Stay Dates</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Check-In Date *</label>
                            <input type="date" name="check_in_date" id="checkInInput" class="form-control" value="<?= date('Y-m-d') ?>" required onchange="calculateNights()" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Check-Out Date *</label>
                            <input type="date" name="check_out_date" id="checkOutInput" class="form-control" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required onchange="calculateNights()" />
                        </div>
                    </div>
                    <div class="mt-2 text-muted" style="font-size:13px;">
                        Duration: <strong id="nightsDisplay" class="text-primary">1 night</strong>
                    </div>
                </div>
            </div>

            <!-- Add Accommodations & Add-ons -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="bi bi-plus-circle"></i> Add Rooms, Activities & Facilities</h5>
                </div>
                <div class="card-body">
                    <!-- Add Room -->
                    <div class="mb-4 pb-3 border-bottom">
                        <label class="form-label" style="font-weight:600; font-size:13px;"><i class="bi bi-door-open me-1"></i> Add Room / Cottage:</label>
                        <div class="row g-2">
                            <div class="col-md-8">
                                <select id="roomPicker" class="form-select form-select-sm">
                                    <option value="">-- Select Available Room --</option>
                                    <?php foreach ($rooms as $rm): ?>
                                        <option value="<?= $rm['RoomId'] ?>" data-name="<?= htmlspecialchars($rm['RoomNumber'] . ' (' . $rm['TypeName'] . ')') ?>" data-price="<?= $rm['BasePrice'] ?>">
                                            <?= htmlspecialchars($rm['RoomNumber']) ?> - <?= htmlspecialchars($rm['TypeName']) ?> (<?= formatCurrency($rm['BasePrice']) ?>/night)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="addRoomItem()">
                                    <i class="bi bi-plus"></i> Add Room
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Add Activity -->
                    <div class="mb-4 pb-3 border-bottom">
                        <label class="form-label" style="font-weight:600; font-size:13px;"><i class="bi bi-compass me-1"></i> Add Activity Tour:</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <select id="actPicker" class="form-select form-select-sm">
                                    <option value="">-- Select Activity --</option>
                                    <?php foreach ($activities as $act): 
                                        $price = $act['PricePerHour'] ?: $act['PricePerDay'];
                                    ?>
                                        <option value="<?= $act['ActivityId'] ?>" data-name="<?= htmlspecialchars($act['ActivityName']) ?>" data-price="<?= $price ?>">
                                            <?= htmlspecialchars($act['ActivityName']) ?> (<?= formatCurrency($price) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="number" id="actQty" class="form-control form-control-sm" placeholder="Qty" value="1" min="1" />
                            </div>
                            <div class="col-md-3">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="addActivityItem()">
                                    <i class="bi bi-plus"></i> Add
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Add Facility -->
                    <div>
                        <label class="form-label" style="font-weight:600; font-size:13px;"><i class="bi bi-building me-1"></i> Add Venue / Facility:</label>
                        <div class="row g-2">
                            <div class="col-md-8">
                                <select id="facPicker" class="form-select form-select-sm">
                                    <option value="">-- Select Facility --</option>
                                    <?php foreach ($facilities as $fac): ?>
                                        <option value="<?= $fac['FacilityId'] ?>" data-name="<?= htmlspecialchars($fac['FacilityName']) ?>" data-price="<?= $fac['RentalPrice'] ?>">
                                            <?= htmlspecialchars($fac['FacilityName']) ?> (<?= formatCurrency($fac['RentalPrice']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="addFacilityItem()">
                                    <i class="bi bi-plus"></i> Add Venue
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Order Summary & Checkout -->
        <div class="col-lg-5">
            <div class="card" style="position:sticky; top:85px;">
                <div class="card-header">
                    <h5><i class="bi bi-receipt"></i> Booking Summary</h5>
                </div>
                <div class="card-body">
                    <!-- Items Table -->
                    <div id="bookingItemsList" class="mb-3">
                        <div class="text-center py-4 text-muted" id="emptyItemsPrompt" style="font-size:13px;">
                            <i class="bi bi-cart3" style="font-size:28px; display:block; margin-bottom:8px;"></i>
                            No items added yet. Please select a room or activity on the left.
                        </div>
                        <div id="itemsContainer"></div>
                    </div>

                    <!-- Totals Calculation -->
                    <div class="border-top pt-3">
                        <div class="d-flex justify-content-between mb-2" style="font-size:14px;">
                            <span>Subtotal:</span>
                            <span id="subtotalDisplay" style="font-weight:600;">₱0.00</span>
                        </div>

                        <!-- Coupon input -->
                        <div class="input-group input-group-sm mb-2">
                            <input type="text" name="discount_code" id="discountCode" class="form-control" placeholder="Discount code (e.g. SUMMER10)" />
                            <button type="button" class="btn btn-outline" onclick="applyCoupon()">Apply</button>
                        </div>

                        <div class="d-flex justify-content-between mb-2 text-success" id="discountRow" style="display:none !important; font-size:14px;">
                            <span>Discount:</span>
                            <span id="discountDisplay">-₱0.00</span>
                        </div>

                        <div class="d-flex justify-content-between mb-3 pt-2 border-top" style="font-size:18px; font-weight:700;">
                            <span>Total Payable:</span>
                            <span class="text-primary" id="grandTotalDisplay">₱0.00</span>
                        </div>
                    </div>

                    <!-- Payment Downpayment Section -->
                    <div class="p-3 mb-3" style="background:var(--bg-hover); border-radius:10px;">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Initial Downpayment / Full Payment (₱)</label>
                        <div class="row g-2 mb-2">
                            <div class="col-7">
                                <input type="number" step="0.01" name="downpayment" id="downpaymentInput" class="form-control form-control-sm" placeholder="0.00" />
                            </div>
                            <div class="col-5">
                                <select name="payment_method" class="form-select form-select-sm">
                                    <option value="0">Cash</option>
                                    <option value="2">GCash</option>
                                    <option value="3">Maya</option>
                                    <option value="1">Card</option>
                                    <option value="4">Bank</option>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-xs btn-outline" onclick="setDownpaymentPercent(0.5)">50% Deposit</button>
                            <button type="button" class="btn btn-xs btn-outline" onclick="setDownpaymentPercent(1.0)">100% Full</button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2" style="font-weight:600;">
                        <i class="bi bi-check-circle-fill me-1"></i> Confirm & Create Reservation
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
var bookingItems = [];
var currentDiscount = 0;

function calculateNights() {
    var checkIn = new Date(document.getElementById('checkInInput').value);
    var checkOut = new Date(document.getElementById('checkOutInput').value);
    var diffTime = checkOut - checkIn;
    var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    var nights = diffDays > 0 ? diffDays : 1;
    document.getElementById('nightsDisplay').textContent = nights + ' night(s)';
    
    // Update room subtotal based on nights
    bookingItems.forEach(function(item) {
        if (item.type === 0) { // Room
            item.qty = nights;
            item.subtotal = item.unit_price * nights;
        }
    });
    renderItems();
    return nights;
}

function addRoomItem() {
    var picker = document.getElementById('roomPicker');
    var selected = picker.options[picker.selectedIndex];
    if (!selected.value) return;

    var nights = calculateNights();
    var refId = parseInt(selected.value);
    var name = selected.getAttribute('data-name');
    var price = parseFloat(selected.getAttribute('data-price'));

    // Check duplicate
    if (bookingItems.some(function(i) { return i.type === 0 && i.ref_id === refId; })) {
        alert('Room already added to this reservation.');
        return;
    }

    bookingItems.push({
        type: 0,
        ref_id: refId,
        name: 'Room ' + name,
        qty: nights,
        unit_price: price,
        subtotal: price * nights
    });

    renderItems();
    picker.value = '';
}

function addActivityItem() {
    var picker = document.getElementById('actPicker');
    var selected = picker.options[picker.selectedIndex];
    if (!selected.value) return;

    var qty = parseInt(document.getElementById('actQty').value) || 1;
    var refId = parseInt(selected.value);
    var name = selected.getAttribute('data-name');
    var price = parseFloat(selected.getAttribute('data-price'));

    bookingItems.push({
        type: 1,
        ref_id: refId,
        name: name,
        qty: qty,
        unit_price: price,
        subtotal: price * qty
    });

    renderItems();
    picker.value = '';
    document.getElementById('actQty').value = 1;
}

function addFacilityItem() {
    var picker = document.getElementById('facPicker');
    var selected = picker.options[picker.selectedIndex];
    if (!selected.value) return;

    var refId = parseInt(selected.value);
    var name = selected.getAttribute('data-name');
    var price = parseFloat(selected.getAttribute('data-price'));

    bookingItems.push({
        type: 2,
        ref_id: refId,
        name: 'Venue: ' + name,
        qty: 1,
        unit_price: price,
        subtotal: price
    });

    renderItems();
    picker.value = '';
}

function removeItem(index) {
    bookingItems.splice(index, 1);
    renderItems();
}

function renderItems() {
    var container = document.getElementById('itemsContainer');
    var emptyPrompt = document.getElementById('emptyItemsPrompt');
    container.innerHTML = '';

    if (bookingItems.length === 0) {
        emptyPrompt.style.display = 'block';
    } else {
        emptyPrompt.style.display = 'none';
        var html = '<ul class="list-group list-group-flush mb-3">';
        bookingItems.forEach(function(item, idx) {
            html += '<li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2" style="background:transparent; border-color:var(--border);">' +
                '<div>' +
                    '<div style="font-weight:600; font-size:13px;">' + item.name + '</div>' +
                    '<small class="text-muted">' + item.qty + ' x ₱' + item.unit_price.toFixed(2) + '</small>' +
                '</div>' +
                '<div class="d-flex align-items-center gap-2">' +
                    '<strong>₱' + item.subtotal.toFixed(2) + '</strong>' +
                    '<button type="button" class="btn btn-xs btn-link text-danger p-0" onclick="removeItem(' + idx + ')"><i class="bi bi-x-circle"></i></button>' +
                '</div>' +
            '</li>';
        });
        html += '</ul>';
        container.innerHTML = html;
    }

    var subtotal = bookingItems.reduce(function(acc, i) { return acc + i.subtotal; }, 0);
    var grandTotal = Math.max(0, subtotal - currentDiscount);

    document.getElementById('subtotalDisplay').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('grandTotalDisplay').textContent = '₱' + grandTotal.toFixed(2);
    document.getElementById('itemsJsonInput').value = JSON.stringify(bookingItems);
}

function setDownpaymentPercent(pct) {
    var subtotal = bookingItems.reduce(function(acc, i) { return acc + i.subtotal; }, 0);
    var grandTotal = Math.max(0, subtotal - currentDiscount);
    document.getElementById('downpaymentInput').value = (grandTotal * pct).toFixed(2);
}

function applyCoupon() {
    var code = document.getElementById('discountCode').value.trim().toUpperCase();
    if (!code) return;

    var subtotal = bookingItems.reduce(function(acc, i) { return acc + i.subtotal; }, 0);
    if (subtotal <= 0) {
        alert('Please add items first before applying discount.');
        return;
    }

    if (code === 'SUMMER10' || code === 'PROMO10') {
        currentDiscount = subtotal * 0.10;
        document.getElementById('discountDisplay').textContent = '-₱' + currentDiscount.toFixed(2);
        document.getElementById('discountRow').style.setProperty('display', 'flex', 'important');
        renderItems();
        showToast('10% discount applied!', 'success');
    } else if (code === 'WELCOME500') {
        currentDiscount = 500;
        document.getElementById('discountDisplay').textContent = '-₱500.00';
        document.getElementById('discountRow').style.setProperty('display', 'flex', 'important');
        renderItems();
        showToast('₱500 voucher applied!', 'success');
    } else {
        showToast('Invalid or expired discount code', 'danger');
    }
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
