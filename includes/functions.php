<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

function formatCurrency($amount) {
    return '₱' . number_format((float)$amount, 2);
}

function formatDate($dateStr, $format = 'M d, Y') {
    if (empty($dateStr)) return '-';
    $time = strtotime($dateStr);
    return $time ? date($format, $time) : '-';
}

function formatDateTime($dateStr, $format = 'M d, Y h:i A') {
    if (empty($dateStr)) return '-';
    $time = strtotime($dateStr);
    return $time ? date($format, $time) : '-';
}

function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function logAudit($action, $module, $details, $badgeClass = 'badge-info') {
    global $pdo;
    try {
        $user = currentUser();
        $userName = $user ? $user['name'] : 'System';
        $stmt = $pdo->prepare("INSERT INTO audit_logs (User, Action, Module, Details, BadgeClass, Timestamp) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$userName, $action, $module, $details, $badgeClass]);
    } catch (Exception $e) {
        // Silently skip if table issue
    }
}

function addNotification($title, $message, $type = 'info', $icon = 'bi-bell', $actionUrl = null, $userId = null) {
    global $pdo;
    try {
        if ($userId === null) {
            // Send to all active admins (RoleId = 1)
            $stmt = $pdo->query("SELECT UserId FROM users WHERE RoleId = 1 AND IsActive = 1");
            $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $insert = $pdo->prepare("INSERT INTO notifications (Title, Message, Type, Icon, ActionUrl, UserId, IsRead, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
            foreach ($admins as $adminId) {
                $insert->execute([$title, $message, $type, $icon, $actionUrl, $adminId]);
            }
        } else {
            $insert = $pdo->prepare("INSERT INTO notifications (Title, Message, Type, Icon, ActionUrl, UserId, IsRead, CreatedAt) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
            $insert->execute([$title, $message, $type, $icon, $actionUrl, $userId]);
        }
    } catch (Exception $e) {
        // Silently skip
    }
}

// Helper status converters
function getReservationStatusName($status) {
    $map = [
        0 => 'Pending',
        1 => 'Confirmed',
        2 => 'CheckedIn',
        3 => 'CheckedOut',
        4 => 'Cancelled'
    ];
    if (is_numeric($status)) {
        return $map[(int)$status] ?? 'Unknown';
    }
    return (string)$status;
}

function getReservationStatusBadge($status) {
    $name = getReservationStatusName($status);
    return '<span class="status-badge status-' . htmlspecialchars($name) . '"><span class="status-dot"></span> ' . htmlspecialchars($name) . '</span>';
}

function getRoomStatusName($status) {
    $map = [
        0 => 'Available',
        1 => 'Occupied',
        2 => 'UnderMaintenance',
        3 => 'Reserved'
    ];
    if (is_numeric($status)) {
        return $map[(int)$status] ?? 'Unknown';
    }
    return (string)$status;
}

function getRoomStatusBadge($status) {
    $name = getRoomStatusName($status);
    $badgeColors = [
        'Available' => 'badge-success',
        'Occupied' => 'badge-info',
        'UnderMaintenance' => 'badge-warning',
        'Reserved' => 'badge-secondary'
    ];
    $cls = $badgeColors[$name] ?? 'badge-secondary';
    return '<span class="badge ' . $cls . '">' . htmlspecialchars($name) . '</span>';
}

function getPaymentMethodName($method) {
    $map = [
        0 => 'Cash',
        1 => 'Card',
        2 => 'GCash',
        3 => 'Maya',
        4 => 'BankTransfer'
    ];
    if (is_numeric($method)) {
        return $map[(int)$method] ?? 'Other';
    }
    return (string)$method;
}

function getPaymentTypeName($type) {
    $map = [
        0 => 'Downpayment',
        1 => 'Full',
        2 => 'Partial',
        3 => 'Balance'
    ];
    if (is_numeric($type)) {
        return $map[(int)$type] ?? 'Payment';
    }
    return (string)$type;
}

function getPaymentStatusName($status) {
    $map = [
        0 => 'Pending',
        1 => 'Completed',
        2 => 'Failed',
        3 => 'Refunded'
    ];
    if (is_numeric($status)) {
        return $map[(int)$status] ?? 'Pending';
    }
    return (string)$status;
}
