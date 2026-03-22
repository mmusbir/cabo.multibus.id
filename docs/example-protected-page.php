<?php
/**
 * protected_page.php - Example Page restricted by JWT
 */

require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/config/auth_config.php';

// Check for JWT valid token - will redirect to login if fail
$payload = requireAdminAuth();

// Current user ID from JWT 'sub' claim
$userId = $payload['sub'];
$username = $payload['user'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Terlindungi</title>
    <style>
        body { font-family: 'Inter', sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f0f9ff; }
        .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); text-align: center; max-width: 400px; width: 90%; }
        h1 { color: #020617; font-size: 24px; margin-bottom: 8px; }
        p { color: #64748b; margin-bottom: 24px; line-height: 1.6; }
        .status-badge { display: inline-block; background: #dcfce7; color: #15803d; padding: 6px 12px; border-radius: 99px; font-size: 12px; font-weight: 700; border: 1px solid #bbfcce; margin-bottom: 20px; }
        .btn-logout { background: #ef4444; color: white; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-weight: 700; transition: 0.2s; }
        .btn-logout:hover { background: #dc2626; transform: scale(1.02); }
    </style>
</head>
<body>
    <div class="card">
        <div class="status-badge">✓ JWT Autentikasi Berhasil</div>
        <h1>Halo, <?php echo htmlspecialchars($username); ?>!</h1>
        <p>Anda sedang mengakses **halaman yang dilindungi** menggunakan JSON Web Token (JWT) yang disimpan secara aman dalam HttpOnly Cookie.</p>
        
        <div style="margin-top:20px;">
            <a href="logout.php" class="btn-logout">Keluaran (Logout)</a>
        </div>
        
        <div style="margin-top:30px; font-size:11px; color:#94a3b8;">
            User ID: <?php echo intval($userId); ?> | Exp: <?php echo date('H:i:s', $payload['exp']); ?>
        </div>
    </div>
</body>
</html>
