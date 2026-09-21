<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$resortSettings = getResortSettings();
$pageTitle = isset($pageTitle) ? $pageTitle : 'Resort Booking System';
$user = currentUser();

// Check for flash message
$flash = getFlash();
if (isset($_GET['success'])) {
    $flash = ['type' => 'success', 'message' => $_GET['success']];
} elseif (isset($_GET['error'])) {
    $flash = ['type' => 'danger', 'message' => $_GET['error']];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($resortSettings['ResortName']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="<?= asset('/assets/css/site.css') ?>" rel="stylesheet" />
    <link href="<?= asset('/css/site.css') ?>" rel="stylesheet" />
    <script>
        // Apply saved theme immediately to avoid flash
        (function() {
            var saved = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
</head>
<body>
    <div class="app-wrapper">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- TOP HEADER -->
            <header class="top-header">
                <div class="header-left">
                    <button class="hamburger-btn" id="hamburgerBtn" onclick="toggleSidebar()" aria-label="Toggle menu">
                        <i class="bi bi-list"></i>
                    </button>
                    <h6 class="header-title"><?= htmlspecialchars($pageTitle) ?></h6>
                </div>
                <div class="header-right">
                    <button class="theme-toggle" id="themeToggle" title="Toggle dark/light mode">
                        <i class="bi bi-sun-fill icon-sun"></i>
                        <i class="bi bi-moon-fill icon-moon"></i>
                    </button>
                    <div class="header-search">
                        <i class="bi bi-search"></i>
                        <input type="text" placeholder="Search..." id="globalSearch" />
                    </div>
                    
                    <!-- Notifications Dropdown -->
                    <div class="notif-dropdown-wrapper">
                        <button class="header-icon-btn" title="Notifications" id="notifBtn">
                            <i class="bi bi-bell"></i>
                            <span class="notif-dot" id="notifDot" style="display:none;"></span>
                        </button>
                        <div class="notif-dropdown" id="notifDropdown">
                            <div class="notif-dropdown-header">
                                <strong>Notifications</strong>
                                <button class="btn btn-sm btn-link" onclick="markAllNotificationsRead()">Mark all read</button>
                            </div>
                            <div class="notif-dropdown-body" id="notifList">
                                <div style="text-align:center; padding:20px; color:var(--text-muted); font-size:13px;">Loading...</div>
                            </div>
                            <div class="notif-dropdown-footer">
                                <a href="<?= url('/notifications/index.php') ?>">View all notifications</a>
                            </div>
                        </div>
                    </div>

                    <button class="header-icon-btn" title="Refresh" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>

                    <!-- User Profile Dropdown -->
                    <div class="profile-dropdown-wrapper">
                        <div class="header-avatar" title="Profile" id="profileBtn">
                            <?php if (!empty($user['image'])): ?>
                                <img src="<?= asset($user['image']) ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover; border-radius:50%;" />
                            <?php else: ?>
                                <i class="bi bi-person-fill"></i>
                            <?php endif; ?>
                        </div>
                        <div class="profile-dropdown" id="profileDropdown">
                            <div class="profile-dropdown-header">
                                <div class="profile-avatar-lg">
                                    <?php if (!empty($user['image'])): ?>
                                        <img src="<?= asset($user['image']) ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover; border-radius:50%;" />
                                    <?php else: ?>
                                        <i class="bi bi-person-fill"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="profile-name"><?= htmlspecialchars($user ? $user['name'] : 'Guest') ?></div>
                                    <div class="profile-email"><?= htmlspecialchars($user ? $user['role'] : 'Not logged in') ?></div>
                                </div>
                            </div>
                            <div class="profile-dropdown-body">
                                <?php if ($user): ?>
                                    <a class="profile-dropdown-item" href="<?= url('/profile/index.php') ?>">
                                        <i class="bi bi-pencil-square"></i> Edit Profile
                                    </a>
                                    <a class="profile-dropdown-item" href="<?= url('/settings/index.php') ?>">
                                        <i class="bi bi-gear"></i> Settings
                                    </a>
                                    <div class="profile-dropdown-divider"></div>
                                    <a class="profile-dropdown-item text-danger" href="<?= url('/account/logout.php') ?>">
                                        <i class="bi bi-box-arrow-right"></i> Logout
                                    </a>
                                <?php else: ?>
                                    <a class="profile-dropdown-item" href="<?= url('/account/login.php') ?>">
                                        <i class="bi bi-box-arrow-in-right"></i> Login
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="top-header-spacer"></div>

            <!-- Toast Flash Alert from session -->
            <div class="toast-container" id="toastContainer">
                <?php if ($flash): ?>
                    <div class="toast-alert <?= htmlspecialchars($flash['type']) ?>" id="sessionFlashToast">
                        <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : ($flash['type'] === 'danger' ? 'bi-x-circle-fill' : 'bi-info-circle-fill') ?> toast-icon"></i>
                        <span><?= htmlspecialchars($flash['message']) ?></span>
                        <button class="toast-close" onclick="this.parentElement.remove()"><i class="bi bi-x"></i></button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- PAGE CONTENT START -->
            <div class="page-content fade-in">
