<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

$user = currentUser();
if (!$user) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    if (!in_array($file['type'], $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Invalid image format. JPEG, PNG, or WebP only.']);
        exit;
    }

    $uploadDir = ROOT_DIR . '/uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $user['id'] . '_' . date('YmdHis') . '.' . $ext;
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $webPath = '/uploads/' . $filename;
        $stmt = $pdo->prepare("UPDATE users SET ProfileImage = ?, UpdatedAt = NOW() WHERE UserId = ?");
        $stmt->execute([$webPath, $user['id']]);
        $_SESSION['profile_image'] = $webPath;
        echo json_encode(['success' => true, 'image' => asset($webPath)]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save uploaded file.']);
        exit;
    }
}

// GET profile
$stmt = $pdo->prepare("SELECT UserId, FirstName, LastName, Email, PhoneNumber, Address, ProfileImage, RoleId FROM users WHERE UserId = ?");
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

echo json_encode([
    'id' => $profile['UserId'] ?? null,
    'name' => trim(($profile['FirstName'] ?? '') . ' ' . ($profile['LastName'] ?? '')),
    'email' => $profile['Email'] ?? '',
    'image' => !empty($profile['ProfileImage']) ? asset($profile['ProfileImage']) : null
]);
