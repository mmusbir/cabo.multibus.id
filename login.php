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
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');

        :root {
            --glass-bg: rgba(255, 255, 255, 0.14);
            --glass-border: rgba(255, 255, 255, 0.28);
            --glass-shadow: 0 22px 54px rgba(15, 23, 42, 0.35);
            --glass-blur: blur(22px);
            --accent-1: #7c3aed;
            --accent-2: #22c55e;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', system-ui, -apple-system, 'Segoe UI', sans-serif;
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fb923c;
            padding: 20px 14px;
            overflow-x: hidden;
            overflow-y: auto;
        }

        body::before, body::after {
            content: none;
        }

        .login-card {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 36px 32px 32px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            box-shadow: var(--glass-shadow);
            backdrop-filter: var(--glass-blur);
            border-radius: 20px;
            color: #e5e7eb;
        }

        .login-card h2 {
            margin: 0 0 18px;
            text-align: center;
            color: #fff;
            letter-spacing: -0.02em;
            font-weight: 700;
        }

        .login-subtitle {
            margin: -6px 0 22px;
            text-align: center;
            color: rgba(226, 232, 240, 0.8);
            font-size: 14px;
        }

        .error-box {
            background: rgba(248, 113, 113, 0.16);
            border: 1px solid rgba(248, 113, 113, 0.55);
            color: #fecdd3;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-size: 14px;
        }

        .form-group { margin-bottom: 14px; }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: rgba(226, 232, 240, 0.92);
            font-size: 13px;
            letter-spacing: 0.02em;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            font-size: 16px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            box-sizing: border-box;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.35);
            transition: border 0.2s ease, background 0.2s ease;
        }

        input:focus {
            outline: none;
            border-color: rgba(124, 58, 237, 0.7);
            background: rgba(255, 255, 255, 0.12);
        }

        button {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            background: #c2410c;
            border: none;
            color: white;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            margin-top: 12px;
            letter-spacing: 0.01em;
            box-shadow: 0 16px 30px rgba(124, 45, 18, 0.3);
            transition: transform 0.15s ease, box-shadow 0.2s ease;
        }
        button:hover { transform: translateY(-1px); box-shadow: 0 20px 36px rgba(124, 45, 18, 0.35); }
        button:active { transform: translateY(0); }

        @media (max-width: 640px) {
            body {
                align-items: flex-start;
                padding: 18px 12px;
            }

            .login-card {
                max-width: 100%;
                padding: 24px 18px 20px;
                border-radius: 16px;
                margin: auto 0;
            }

            .login-card h2 {
                margin-bottom: 12px;
                font-size: 28px;
            }

            .login-subtitle {
                margin: -2px 0 18px;
                font-size: 13px;
            }

            .error-box {
                padding: 11px;
                margin-bottom: 16px;
                font-size: 13px;
            }

            .form-group {
                margin-bottom: 12px;
            }

            label {
                font-size: 12px;
                margin-bottom: 5px;
            }

            input {
                padding: 13px 14px;
                border-radius: 10px;
            }

            button {
                margin-top: 10px;
                padding: 13px 14px;
                border-radius: 10px;
            }
        }

        @media (max-width: 420px) {
            body {
                padding: 14px 10px;
            }

            .login-card {
                padding: 20px 14px 16px;
                border-radius: 14px;
            }

            .login-card h2 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Login Panel</h2>
        <div class="login-subtitle">Liquid Glass Interface</div>
        
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
