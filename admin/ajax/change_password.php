<?php
/**
 * admin/ajax/change_password.php - Handle admin password change
 */

global $conn;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$admin_user = $_SESSION['admin_user'] ?? '';
if (!$admin_user) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$old_pass = $_POST['old_password'] ?? '';
$new_pass = $_POST['new_password'] ?? '';
$confirm_pass = $_POST['confirm_password'] ?? '';

if (!$old_pass || !$new_pass || !$confirm_pass) {
    echo json_encode(['success' => false, 'error' => 'Semua field harus diisi']);
    exit;
}

if ($new_pass !== $confirm_pass) {
    echo json_encode(['success' => false, 'error' => 'Password baru dan konfirmasi tidak cocok']);
    exit;
}

if (strlen($new_pass) < 5) {
    echo json_encode(['success' => false, 'error' => 'Password baru minimal 5 karakter']);
    exit;
}

// Check old password
$stmt = $conn->prepare("SELECT id, password_hash FROM users WHERE username=? LIMIT 1");
$stmt->execute([$admin_user]);
$res = $stmt->fetch();

if (!$res || !password_verify($old_pass, $res['password_hash'])) {
    echo json_encode(['success' => false, 'error' => 'Password lama salah']);
    exit;
}

// Update to new password
$new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password_hash=? WHERE id=?");

if ($stmt->execute([$new_hash, $res['id']])) {
    echo json_encode(['success' => true, 'message' => 'Password berhasil diubah']);
} else {
    echo json_encode(['success' => false, 'error' => 'Gagal mengubah password di database']);
}
exit;
