<?php
$currentScript = $_SERVER['PHP_SELF'];
function isActiveNav($path) {
    global $currentScript;
    return (strpos($currentScript, $path) !== false) ? ' active' : '';
}
?>
<!-- SIDEBAR OVERLAY (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <?php if (!empty($resortSettings['LogoImage'])): ?>
                <img src="<?= asset($resortSettings['LogoImage']) ?>" alt="Logo" style="width:100%; height:100%; object-fit:cover; border-radius:8px;" />
            <?php else: ?>
                <i class="bi bi-water"></i>
            <?php endif; ?>
        </div>
        <div>
            <div class="brand-text"><?= htmlspecialchars($resortSettings['ResortName']) ?></div>
            <div class="brand-sub"><?= htmlspecialchars($resortSettings['ResortSubtitle']) ?></div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section">Main</div>
        <a class="sidebar-link<?= (basename($currentScript) === 'index.php' && dirname($currentScript) === dirname($_SERVER['SCRIPT_NAME'])) ? ' active' : '' ?>" href="<?= url('/index.php') ?>">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>

        <div class="sidebar-section">Management</div>
        <a class="sidebar-link<?= (strpos($currentScript, '/rooms/index.php') !== false || (strpos($currentScript, '/rooms/') !== false && strpos($currentScript, 'calendar.php') === false)) ? ' active' : '' ?>" href="<?= url('/rooms/index.php') ?>">
            <i class="bi bi-door-open"></i> Rooms & Cottages
        </a>
        <a class="sidebar-link<?= (strpos($currentScript, '/rooms/calendar.php') !== false) ? ' active' : '' ?>" href="<?= url('/rooms/calendar.php') ?>">
            <i class="bi bi-calendar3"></i> Availability Calendar
        </a>
        <a class="sidebar-link<?= isActiveNav('/activities/') ?>" href="<?= url('/activities/index.php') ?>">
            <i class="bi bi-compass"></i> Activities
        </a>
        <a class="sidebar-link<?= isActiveNav('/facilities/') ?>" href="<?= url('/facilities/index.php') ?>">
            <i class="bi bi-building"></i> Facilities
        </a>
        <a class="sidebar-link<?= isActiveNav('/guests/') ?>" href="<?= url('/guests/index.php') ?>">
            <i class="bi bi-people"></i> Guest Management
        </a>

        <div class="sidebar-section">Operations</div>
        <a class="sidebar-link<?= isActiveNav('/reservations/') ?>" href="<?= url('/reservations/index.php') ?>">
            <i class="bi bi-calendar-check"></i> Reservations
        </a>
        <a class="sidebar-link<?= isActiveNav('/checkinout/') ?>" href="<?= url('/checkinout/index.php') ?>">
            <i class="bi bi-box-arrow-in-right"></i> Check-In / Out
        </a>
        <a class="sidebar-link<?= isActiveNav('/walkin/') ?>" href="<?= url('/walkin/index.php') ?>">
            <i class="bi bi-person-plus"></i> Walk-In
        </a>

        <div class="sidebar-section">Finance</div>
        <a class="sidebar-link<?= (isActiveNav('/payments/') || isActiveNav('/receipt/')) ?>" href="<?= url('/payments/index.php') ?>">
            <i class="bi bi-credit-card"></i> Payments
        </a>
        <a class="sidebar-link<?= isActiveNav('/expenses/') ?>" href="<?= url('/expenses/index.php') ?>">
            <i class="bi bi-wallet2"></i> Expenses
        </a>

        <div class="sidebar-section">Insights</div>
        <a class="sidebar-link<?= (strpos($currentScript, '/reports/index.php') !== false) ? ' active' : '' ?>" href="<?= url('/reports/index.php') ?>">
            <i class="bi bi-bar-chart-line"></i> Reports
        </a>
        <a class="sidebar-link<?= (strpos($currentScript, '/reports/charts.php') !== false) ? ' active' : '' ?>" href="<?= url('/reports/charts.php') ?>">
            <i class="bi bi-graph-up"></i> Charts
        </a>
        <a class="sidebar-link<?= (strpos($currentScript, '/reports/export.php') !== false) ? ' active' : '' ?>" href="<?= url('/reports/export.php') ?>">
            <i class="bi bi-download"></i> Export Data
        </a>

        <div class="sidebar-section">System</div>
        <a class="sidebar-link<?= isActiveNav('/notifications/') ?>" href="<?= url('/notifications/index.php') ?>">
            <i class="bi bi-bell"></i> Notifications
        </a>
        <a class="sidebar-link<?= isActiveNav('/search/') ?>" href="<?= url('/search/index.php') ?>">
            <i class="bi bi-search"></i> Search
        </a>
        <?php if (hasRole('Admin')): ?>
        <a class="sidebar-link<?= isActiveNav('/admin/') ?>" href="<?= url('/admin/users.php') ?>">
            <i class="bi bi-people"></i> User Management
        </a>
        <?php endif; ?>
        <a class="sidebar-link<?= isActiveNav('/audit/') ?>" href="<?= url('/audit/index.php') ?>">
            <i class="bi bi-journal-text"></i> Audit Log
        </a>
        <a class="sidebar-link<?= (strpos($currentScript, '/settings/index.php') !== false) ? ' active' : '' ?>" href="<?= url('/settings/index.php') ?>">
            <i class="bi bi-gear"></i> Settings
        </a>
        <a class="sidebar-link<?= (strpos($currentScript, '/settings/pricing.php') !== false) ? ' active' : '' ?>" href="<?= url('/settings/pricing.php') ?>">
            <i class="bi bi-cash-coin"></i> Seasonal Pricing
        </a>
        <a class="sidebar-link<?= (strpos($currentScript, '/settings/discounts.php') !== false) ? ' active' : '' ?>" href="<?= url('/settings/discounts.php') ?>">
            <i class="bi bi-tag"></i> Discount Codes
        </a>
    </nav>
</aside>
