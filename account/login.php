<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/settings.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$resortSettings = getResortSettings();
$error = '';
$success = $_GET['success'] ?? '';

if (isLoggedIn()) {
    header("Location: " . url('/index.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "Please provide both email and password.";
    } else {
        $stmt = $pdo->prepare("SELECT u.*, r.RoleName FROM users u JOIN roles r ON u.RoleId = r.RoleId WHERE u.Email = ? AND u.IsActive = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Check direct password or password_verify
            $valid = ($user['PasswordHash'] === $password) || password_verify($password, $user['PasswordHash']);
            if ($valid) {
                loginUser($user);
                logAudit('Login', 'Auth', $user['RoleName'] . ' logged in: ' . $user['Email'], 'badge-success');
                setFlash('success', 'Welcome back, ' . htmlspecialchars($user['FirstName']) . '!');
                header("Location: " . url('/index.php'));
                exit;
            } else {
                $error = "Invalid password. Please try again.";
            }
        } else {
            $error = "Account not found or currently deactivated.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - <?= htmlspecialchars($resortSettings['ResortName']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="<?= asset('/assets/css/site.css') ?>" rel="stylesheet" />
    <link href="<?= asset('/css/site.css') ?>" rel="stylesheet" />
    <script>
        (function() {
            var saved = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--bg-body, #f8fafc);
            padding: 20px;
        }
        .login-card {
            background: var(--bg-surface, #ffffff);
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 16px;
            box-shadow: var(--shadow-lg, 0 10px 25px -5px rgba(0,0,0,0.1));
            width: 100%;
            max-width: 440px;
            padding: 36px 32px;
        }
        .brand-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .brand-header .icon-wrap {
            width: 64px;
            height: 64px;
            background: var(--primary, #0284c7);
            color: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 16px;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
            overflow: hidden;
        }
        .brand-header h4 {
            font-weight: 700;
            color: var(--text-primary, #0f172a);
            margin-bottom: 4px;
        }
        .brand-header p {
            color: var(--text-muted, #64748b);
            font-size: 14px;
            margin: 0;
        }
        .demo-box {
            background: var(--bg-hover, #f1f5f9);
            border-radius: 10px;
            padding: 12px 16px;
            margin-top: 20px;
            border: 1px dashed var(--border, #cbd5e1);
        }
        .demo-box strong {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            display: block;
            margin-bottom: 8px;
        }
        .demo-btn {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 6px;
            background: var(--bg-surface, #fff);
            border: 1px solid var(--border, #cbd5e1);
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.2s;
            margin-right: 4px;
            margin-bottom: 4px;
        }
        .demo-btn:hover {
            background: var(--primary, #0284c7);
            color: white;
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand-header">
            <div class="icon-wrap">
                <?php if (!empty($resortSettings['LogoImage'])): ?>
                    <img src="<?= asset($resortSettings['LogoImage']) ?>" alt="Logo" style="width:100%; height:100%; object-fit:cover;" />
                <?php else: ?>
                    <i class="bi bi-water"></i>
                <?php endif; ?>
            </div>
            <h4><?= htmlspecialchars($resortSettings['ResortName']) ?></h4>
            <p><?= htmlspecialchars($resortSettings['ResortSubtitle']) ?></p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger d-flex align-items-center mb-3" role="alert" style="font-size:14px; border-radius:10px;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success d-flex align-items-center mb-3" role="alert" style="font-size:14px; border-radius:10px;">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div><?= htmlspecialchars($success) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label" style="font-weight:600; font-size:13px;">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" id="emailInput" class="form-control" placeholder="name@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
                </div>
            </div>

            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0" style="font-weight:600; font-size:13px;">Password</label>
                </div>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" id="passwordInput" class="form-control" placeholder="••••••••" required />
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 mt-2" style="font-weight:600; border-radius:10px;">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
            </button>
        </form>

        <!-- Quick Fill Demo Accounts -->
        <div class="demo-box">
            <strong><i class="bi bi-lightning-charge-fill text-warning"></i> Quick Demo Logins:</strong>
            <div class="d-flex flex-wrap">
                <button type="button" class="demo-btn" onclick="fillLogin('admin@resort.com', 'Password123!')">
                    <i class="bi bi-shield-lock-fill text-danger me-1"></i>Admin
                </button>
                <button type="button" class="demo-btn" onclick="fillLogin('maria@resort.com', 'Password123!')">
                    <i class="bi bi-person-badge text-primary me-1"></i>Staff (Maria)
                </button>
                <button type="button" class="demo-btn" onclick="fillLogin('anna@gmail.com', 'Password123!')">
                    <i class="bi bi-person text-success me-1"></i>Guest (Anna)
                </button>
            </div>
        </div>

        <div class="text-center mt-3" style="font-size:13px; color:var(--text-muted);">
            Don't have an account? <a href="<?= url('/account/register.php') ?>" style="color:var(--primary); font-weight:600; text-decoration:none;">Register here</a>
        </div>
    </div>

    <script>
        function fillLogin(email, password) {
            document.getElementById('emailInput').value = email;
            document.getElementById('passwordInput').value = password;
        }
    </script>
</body>
</html>
