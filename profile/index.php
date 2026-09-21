<?php
$pageTitle = 'My Profile';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

$user = currentUser();
$userId = $user['id'];

$stmt = $pdo->prepare("SELECT u.*, r.RoleName FROM users u JOIN roles r ON u.RoleId = r.RoleId WHERE u.UserId = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');

    if (empty($firstName) || empty($email)) {
        $error = "First Name and Email are required.";
    } else {
        // Handle avatar image upload
        $imagePath = $userData['ProfileImage'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_image'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $userId . '_' . date('YmdHis') . '.' . $ext;
            $uploadDir = ROOT_DIR . '/uploads/';
            if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
            move_uploaded_file($file['tmp_name'], $uploadDir . $filename);
            $imagePath = '/uploads/' . $filename;
            $_SESSION['profile_image'] = $imagePath;
        }

        if (!empty($newPassword)) {
            $stmtUp = $pdo->prepare("UPDATE users SET FirstName = ?, LastName = ?, Email = ?, PhoneNumber = ?, Address = ?, ProfileImage = ?, PasswordHash = ?, UpdatedAt = NOW() WHERE UserId = ?");
            $stmtUp->execute([$firstName, $lastName, $email, $phone, $address, $imagePath, $newPassword, $userId]);
        } else {
            $stmtUp = $pdo->prepare("UPDATE users SET FirstName = ?, LastName = ?, Email = ?, PhoneNumber = ?, Address = ?, ProfileImage = ?, UpdatedAt = NOW() WHERE UserId = ?");
            $stmtUp->execute([$firstName, $lastName, $email, $phone, $address, $imagePath, $userId]);
        }

        $_SESSION['user_name'] = trim("$firstName $lastName");
        $_SESSION['user_email'] = $email;

        logAudit('Update', 'Profile', "User updated personal profile: $email", 'badge-info');
        setFlash('success', 'Profile updated successfully!');
        header("Location: " . url('/profile/index.php'));
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="mb-4">
    <h4 style="margin:0; font-weight:700;">Account Profile</h4>
    <p class="text-muted" style="margin:0; font-size:14px;">Update personal details, upload photo avatar, or change password</p>
</div>

<div class="row g-4" style="max-width: 850px;">
    <!-- Profile Card & Avatar -->
    <div class="col-md-4">
        <div class="card text-center p-4">
            <div class="mx-auto mb-3" style="width:110px; height:110px; border-radius:50%; overflow:hidden; border:3px solid var(--primary); background:var(--bg-hover);">
                <?php if (!empty($userData['ProfileImage'])): ?>
                    <img src="<?= asset($userData['ProfileImage']) ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;" />
                <?php else: ?>
                    <i class="bi bi-person-fill" style="font-size:64px; line-height:110px; color:var(--text-muted);"></i>
                <?php endif; ?>
            </div>
            <h5 style="margin:0; font-weight:700;"><?= htmlspecialchars($userData['FirstName'] . ' ' . $userData['LastName']) ?></h5>
            <span class="badge badge-info mt-1"><?= htmlspecialchars($userData['RoleName']) ?></span>
            <small class="text-muted d-block mt-2"><?= htmlspecialchars($userData['Email']) ?></small>
            <div class="mt-3 pt-3 border-top text-muted" style="font-size:12px;">
                Member since <?= formatDate($userData['CreatedAt']) ?>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="bi bi-pencil-square"></i> Edit Details</h5>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger mb-4" style="border-radius:10px;">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">First Name *</label>
                            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($userData['FirstName']) ?>" required />
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Last Name</label>
                            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($userData['LastName']) ?>" />
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Email Address *</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($userData['Email']) ?>" required />
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Mobile Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($userData['PhoneNumber'] ?? '') ?>" />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Address / Location</label>
                        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($userData['Address'] ?? '') ?>" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Update Photo Avatar</label>
                        <input type="file" name="profile_image" class="form-control" accept="image/*" />
                    </div>

                    <div class="mb-4">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Change Password (leave blank to keep unchanged)</label>
                        <input type="password" name="new_password" class="form-control" placeholder="••••••••" />
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2" style="font-weight:600;">
                        <i class="bi bi-check-lg me-1"></i> Save Profile Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
