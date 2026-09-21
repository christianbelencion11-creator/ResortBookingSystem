<?php
$pageTitle = 'User & Staff Management';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Only Admin can access
requireRole('Admin');

// Create User POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $roleId = (int)($_POST['role_id'] ?? 2);
    $password = trim($_POST['password'] ?? 'Password123!');

    if (!empty($firstName) && !empty($email) && !empty($password)) {
        // Check duplicate email
        $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE Email = ?");
        $check->execute([$email]);
        if ($check->fetchColumn() > 0) {
            setFlash('danger', "The email '$email' is already in use.");
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (RoleId, FirstName, LastName, Email, PasswordHash, PhoneNumber, IsActive, CreatedAt, UpdatedAt) VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())");
            $stmt->execute([$roleId, $firstName, $lastName, $email, $password, $phone]);

            logAudit('Create', 'Users', "Created account for $firstName $lastName ($email)", 'badge-success');
            setFlash('success', "User account for $firstName $lastName successfully created!");
        }
    }
    header("Location: " . url('/admin/users.php'));
    exit;
}

// Edit User POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    $userId = (int)$_POST['user_id'];
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $roleId = (int)($_POST['role_id'] ?? 2);
    $newPassword = trim($_POST['password'] ?? '');

    if (!empty($firstName) && !empty($email)) {
        if (!empty($newPassword)) {
            $stmt = $pdo->prepare("UPDATE users SET FirstName = ?, LastName = ?, Email = ?, PhoneNumber = ?, RoleId = ?, PasswordHash = ?, UpdatedAt = NOW() WHERE UserId = ?");
            $stmt->execute([$firstName, $lastName, $email, $phone, $roleId, $newPassword, $userId]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET FirstName = ?, LastName = ?, Email = ?, PhoneNumber = ?, RoleId = ?, UpdatedAt = NOW() WHERE UserId = ?");
            $stmt->execute([$firstName, $lastName, $email, $phone, $roleId, $userId]);
        }

        logAudit('Update', 'Users', "Updated user account #$userId ($email)", 'badge-warning');
        setFlash('success', "User account #$userId successfully updated.");
    }
    header("Location: " . url('/admin/users.php'));
    exit;
}

// Toggle Active status
if (isset($_GET['toggle']) && (int)$_GET['toggle'] > 0) {
    $tId = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE users SET IsActive = NOT IsActive, UpdatedAt = NOW() WHERE UserId = ?")->execute([$tId]);
    logAudit('Toggle', 'Users', "Toggled status of user #$tId", 'badge-info');
    setFlash('success', 'User status updated.');
    header("Location: " . url('/admin/users.php'));
    exit;
}

// Fetch all users with role names
$users = $pdo->query("SELECT u.*, r.RoleName FROM users u JOIN roles r ON u.RoleId = r.RoleId ORDER BY u.RoleId ASC, u.FirstName ASC")->fetchAll();
$roles = $pdo->query("SELECT * FROM roles ORDER BY RoleId ASC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 style="margin:0; font-weight:700;">User & Staff Accounts</h4>
        <p class="text-muted" style="margin:0; font-size:14px;">Manage administrative privileges, front-desk staff, and guest credentials</p>
    </div>
    <button type="button" class="btn btn-primary" onclick="new bootstrap.Modal(document.getElementById('addUserModal')).show()">
        <i class="bi bi-person-plus-fill me-1"></i> Add User Account
    </button>
</div>

<div class="card">
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email Address</th>
                    <th>Phone</th>
                    <th>Role Privilege</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): 
                    $roleCls = $u['RoleId'] == 1 ? 'badge-danger' : ($u['RoleId'] == 2 ? 'badge-info' : 'badge-secondary');
                ?>
                    <tr>
                        <td>
                            <strong style="font-size:14px;"><?= htmlspecialchars(trim($u['FirstName'] . ' ' . $u['LastName'])) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($u['Email']) ?></td>
                        <td><?= htmlspecialchars($u['PhoneNumber'] ?: '-') ?></td>
                        <td><span class="badge <?= $roleCls ?>"><?= htmlspecialchars($u['RoleName']) ?></span></td>
                        <td>
                            <a href="<?= url('/admin/users.php?toggle=' . $u['UserId']) ?>" class="badge <?= $u['IsActive'] ? 'badge-success' : 'badge-secondary' ?>" style="text-decoration:none;" title="Click to toggle">
                                <?= $u['IsActive'] ? 'Active' : 'Deactivated' ?>
                            </a>
                        </td>
                        <td><?= formatDate($u['CreatedAt']) ?></td>
                        <td style="text-align:right;">
                            <button type="button" class="btn btn-sm btn-outline" onclick='openEditUserModal(<?= json_encode($u) ?>)'>
                                <i class="bi bi-pencil me-1"></i> Edit
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add User -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; background:var(--bg-surface);">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_user" />
                <div class="modal-header">
                    <h6 class="modal-title" style="font-weight:700;">Add New Account</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required />
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Email Address *</label>
                        <input type="email" name="email" class="form-control" required />
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Role *</label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['RoleId'] ?>" <?= $r['RoleId'] == 2 ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['RoleName']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Mobile Phone</label>
                            <input type="text" name="phone" class="form-control" placeholder="09171234567" />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Initial Password *</label>
                        <input type="password" name="password" class="form-control" value="Password123!" required />
                        <small class="text-muted">Default: Password123!</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:14px; background:var(--bg-surface);">
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_user" />
                <input type="hidden" name="user_id" id="editUserId" />
                <div class="modal-header">
                    <h6 class="modal-title" style="font-weight:700;">Edit User Account</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">First Name *</label>
                            <input type="text" name="first_name" id="editFirstName" class="form-control" required />
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Last Name *</label>
                            <input type="text" name="last_name" id="editLastName" class="form-control" required />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">Email Address *</label>
                        <input type="email" name="email" id="editEmail" class="form-control" required />
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Role *</label>
                            <select name="role_id" id="editRoleId" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['RoleId'] ?>"><?= htmlspecialchars($r['RoleName']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label" style="font-weight:600; font-size:13px;">Mobile Phone</label>
                            <input type="text" name="phone" id="editPhone" class="form-control" />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" style="font-weight:600; font-size:13px;">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control" placeholder="Optional" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary">Save Updates</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditUserModal(user) {
    document.getElementById('editUserId').value = user.UserId;
    document.getElementById('editFirstName').value = user.FirstName;
    document.getElementById('editLastName').value = user.LastName;
    document.getElementById('editEmail').value = user.Email;
    document.getElementById('editRoleId').value = user.RoleId;
    document.getElementById('editPhone').value = user.PhoneNumber || '';
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
