<?php
require_once __DIR__ . '/../config/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'Guest',
        'image' => $_SESSION['profile_image'] ?? null
    ];
}

function hasRole($roles) {
    if (!isLoggedIn()) return false;
    $currentRole = $_SESSION['user_role'] ?? '';
    if (is_array($roles)) {
        return in_array($currentRole, $roles, true);
    }
    return strcasecmp($currentRole, $roles) === 0;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . url('/account/login.php?error=' . urlencode('Please log in to continue.')));
        exit;
    }
}

function requireRole($roles) {
    requireLogin();
    if (!hasRole($roles)) {
        http_response_code(403);
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>
            <h1 style='color:#e11d48;'>403 - Unauthorized Access</h1>
            <p>You do not have permission to view this section.</p>
            <p><a href='" . url('/') . "' style='color:#0284c7;'>Return to Dashboard</a></p>
        </div>";
        exit;
    }
}

function loginUser($user) {
    $_SESSION['user_id'] = (int)$user['UserId'];
    $_SESSION['user_name'] = trim(($user['FirstName'] ?? '') . ' ' . ($user['LastName'] ?? ''));
    $_SESSION['user_email'] = $user['Email'] ?? '';
    $_SESSION['user_role'] = $user['RoleName'] ?? 'Guest';
    $_SESSION['profile_image'] = $user['ProfileImage'] ?? null;
}

function logoutUser() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
