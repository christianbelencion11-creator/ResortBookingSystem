<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$user = currentUser();
if ($user) {
    logAudit('Logout', 'Auth', $user['name'] . ' logged out', 'badge-info');
}

logoutUser();
header("Location: " . url('/account/login.php?success=' . urlencode('You have been logged out successfully.')));
exit;
