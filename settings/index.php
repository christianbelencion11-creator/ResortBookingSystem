<?php
$pageTitle = 'Resort Settings';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('Admin');

$settings = getResortSettings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'brand_settings') {
        $settings['ResortName'] = trim($_POST['resort_name'] ?? 'ResortBooking');
        $settings['ResortSubtitle'] = trim($_POST['resort_subtitle'] ?? 'Management System');
        $settings['Address'] = trim($_POST['address'] ?? '');
        $settings['Phone'] = trim($_POST['phone'] ?? '');
        $settings['Email'] = trim($_POST['email'] ?? '');

        // Handle logo file upload
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['logo_file'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFilename = 'logo-' . date('YmdHis') . '.' . $ext;
            $uploadDir = ROOT_DIR . '/uploads/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            move_uploaded_file($file['tmp_name'], $uploadDir . $newFilename);
            $settings['LogoImage'] = '/uploads/' . $newFilename;
        }

        saveResortSettings($settings);
        logAudit('Update', 'Settings', 'Updated resort branding & contact details', 'badge-info');
        setFlash('success', 'Brand and resort information saved!');
        header("Location: " . url('/settings/index.php'));
        exit;
    }

    if ($action === 'policy_settings') {
        $settings['CheckInTime'] = trim($_POST['check_in_time'] ?? '14:00');
        $settings['CheckOutTime'] = trim($_POST['check_out_time'] ?? '12:00');
        $settings['DownpaymentPercent'] = (int)($_POST['downpayment_percent'] ?? 50);
        $settings['VatRate'] = (float)($_POST['vat_rate'] ?? 12);
        $settings['ServiceCharge'] = (float)($_POST['service_charge'] ?? 10);

        saveResortSettings($settings);
        logAudit('Update', 'Settings', 'Updated policy, tax and downpayment settings', 'badge-info');
        setFlash('success', 'Booking policies and pricing rules saved!');
        header("Location: " . url('/settings/index.php'));
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h4 style="margin:0; font-weight:700;">General Resort Settings</h4>
    <p class="text-muted" style="margin:0; font-size:14px;">Configure resort identity, contact information, check-in policies, and taxes</p>
</div>

<div class="row g-4" style="max-width: 900px;">
    <!-- Branding & Info Form -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="bi bi-shop"></i> Resort Identity & Contact</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="brand_settings" />
                    
                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Resort Brand Name *</label>
                        <input type="text" name="resort_name" class="form-control" value="<?= htmlspecialchars($settings['ResortName']) ?>" required />
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Subtitle / Tagline</label>
                        <input type="text" name="resort_subtitle" class="form-control" value="<?= htmlspecialchars($settings['ResortSubtitle']) ?>" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Physical Address</label>
                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($settings['Address'] ?? '') ?>" />
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($settings['Phone'] ?? '') ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($settings['Email'] ?? '') ?>" />
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Resort Logo</label>
                        <?php if (!empty($settings['LogoImage'])): ?>
                            <div class="mb-2">
                                <img src="<?= asset($settings['LogoImage']) ?>" alt="Logo" style="height:50px; border-radius:8px; border:1px solid var(--border);" />
                            </div>
                        <?php endif; ?>
                        <input type="file" name="logo_file" class="form-control" accept="image/*" />
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg me-1"></i> Save Identity
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Operating Policies & Rules Form -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="bi bi-clock-history"></i> Check-In Policies & Tax Rules</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="policy_settings" />

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Standard Check-In</label>
                            <input type="time" name="check_in_time" class="form-control" value="<?= htmlspecialchars($settings['CheckInTime'] ?? '14:00') ?>" required />
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Standard Check-Out</label>
                            <input type="time" name="check_out_time" class="form-control" value="<?= htmlspecialchars($settings['CheckOutTime'] ?? '12:00') ?>" required />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Default Downpayment (%)</label>
                        <input type="number" name="downpayment_percent" class="form-control" value="<?= (int)($settings['DownpaymentPercent'] ?? 50) ?>" min="0" max="100" />
                        <small class="text-muted">Standard minimum advance deposit requested from guests</small>
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">VAT Rate (%)</label>
                            <input type="number" step="0.1" name="vat_rate" class="form-control" value="<?= (float)($settings['VatRate'] ?? 12) ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Service Charge (%)</label>
                            <input type="number" step="0.1" name="service_charge" class="form-control" value="<?= (float)($settings['ServiceCharge'] ?? 10) ?>" />
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg me-1"></i> Save Policies & Tax
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
