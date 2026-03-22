<?php
/**
 * login.php - JWT Login Processor
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_config.php';

use Firebase\JWT\JWT;

$error = $_GET['error'] ?? null;
$error_msg = "";
if ($error === 'unauthorized') $error_msg = "Sila login terlebih dahulu.";
if ($error === 'expired') $error_msg = "Sesi telah berakhir. Silakan login kembali.";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        // 1. Find user by username using PDO (PostgreSQL/MySQL compatible)
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // 2. Verify password with password_verify()
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // 3. Issue JWT Token (payload: sub = user ID, iat = issued at, exp = expiration)
            $issuedAt = time();
            $expire = $issuedAt + EXPIRE_TIME; // 1 Hour from config
            
            $payload = [
                'iat'  => $issuedAt,
                'exp'  => $expire,
                'sub'  => $user['id'],
                'user' => $user['username']
            ];

            // 4. Encode token with Secret from config
            $jwt = JWT::encode($payload, JWT_SECRET, JWT_ALGO);

            // 5. Set HttpOnly cookie for security (Stateless)
            // setcookie($name, $value, $expire, $path, $domain, $secure, $httponly)
            setcookie(
                COOKIE_NAME, 
                $jwt, 
                [
                    'expires' => $expire,
                    'path' => '/',
                    'domain' => '', // Default to current
                    'secure' => isset($_SERVER['HTTPS']), // True on Vercel
                    'httponly' => true, // Prevents XSS stealing
                    'samesite' => 'Strict' // Prevents CSRF
                ]
            );

            // 6. Redirect to Booking Page after successful login
            header('Location: index.php');
            exit;
        } else {
            $error_msg = "Username atau Password salah!";
        }
    } else {
        $error_msg = "Silakan lengkapi data login.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login JWT</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); width: 100%; max-width: 360px; }
        .login-card h2 { margin: 0 0 24px; text-align: center; color: #333; }
        .error-box { background: #fee2e2; border: 1px solid #f87171; color: #b91c1c; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; }
        .form-group { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #555; }
        input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 14px; background: #3b82f6; border: none; color: white; border-radius: 8px; cursor: pointer; font-weight: 700; margin-top: 10px; }
        button:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Login Panel</h2>
        
        <?php if ($error_msg): ?>
            <div class="error-box">⚠️ <?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="admin">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="password">
            </div>
            <button type="submit">LOGIN</button>
        </form>
    </div>
</body>
</html>
