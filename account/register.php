<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$resortSettings = getResortSettings();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if email already registered
        $stmt = $pdo->prepare("SELECT UserId FROM users WHERE Email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "This email is already registered. Please log in or use another email.";
        } else {
            // Role 3 = Guest
            $stmt = $pdo->prepare("INSERT INTO users (RoleId, FirstName, LastName, Email, PasswordHash, PhoneNumber, IsActive, CreatedAt, UpdatedAt) VALUES (3, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
            $stmt->execute([$firstName, $lastName, $email, $password, $phone]);
            $newUserId = $pdo->lastInsertId();

            // Also create a guest record
            $stmtGuest = $pdo->prepare("INSERT INTO guestrecords (UserId, FirstName, LastName, Email, PhoneNumber, CreatedAt) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmtGuest->execute([$newUserId, $firstName, $lastName, $email, $phone]);

            logAudit('Register', 'Auth', "New guest registered: $firstName $lastName ($email)", 'badge-success');
            addNotification('New Guest Registration', "$firstName $lastName created an account.", 'success', 'bi-person-plus', url('/guests/index.php'));

            header("Location: " . url('/account/login.php?success=' . urlencode('Registration successful! Please sign in with your credentials.')));
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create Account - <?= htmlspecialchars($resortSettings['ResortName']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="<?= asset('/assets/css/site.css') ?>" rel="stylesheet" />
    <link href="<?= asset('/css/site.css') ?>" rel="stylesheet" />
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--bg-body, #f8fafc);
            padding: 20px;
        }
        .register-card {
            background: var(--bg-surface, #ffffff);
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 16px;
            box-shadow: var(--shadow-lg, 0 10px 25px -5px rgba(0,0,0,0.1));
            width: 100%;
            max-width: 500px;
            padding: 36px 32px;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="text-center mb-4">
            <h4 style="font-weight:700; color:var(--text-primary);">Create Guest Account</h4>
            <p style="color:var(--text-muted); font-size:14px;">Register to book rooms and manage resort activities</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="font-size:14px; border-radius:10px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">First Name *</label>
                    <input type="text" name="first_name" class="form-control" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" />
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Last Name *</label>
                    <input type="text" name="last_name" class="form-control" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" />
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-weight:600; font-size:13px;">Email Address *</label>
                <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
            </div>

            <div class="mb-3">
                <label class="form-label" style="font-weight:600; font-size:13px;">Mobile Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="09171234567" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" />
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Password *</label>
                    <input type="password" name="password" class="form-control" placeholder="Min. 6 chars" required />
                </div>
                <div class="col-md-6">
                    <label class="form-label" style="font-weight:600; font-size:13px;">Confirm Password *</label>
                    <input type="password" name="confirm_password" class="form-control" required />
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 mt-2" style="font-weight:600; border-radius:10px;">
                <i class="bi bi-person-check-fill me-1"></i> Register Account
            </button>
        </form>

        <div class="text-center mt-3" style="font-size:13px; color:var(--text-muted);">
            Already have an account? <a href="<?= url('/account/login.php') ?>" style="color:var(--primary); font-weight:600; text-decoration:none;">Sign In</a>
        </div>
    </div>
</body>
</html>
