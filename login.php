<?php
/**
 * login.php - JWT Login Processor
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth_config.php';


$error = $_GET['error'] ?? null;
$error_msg = "";
if ($error === 'unauthorized') $error_msg = "Sila login terlebih dahulu.";
if ($error === 'expired') $error_msg = "Sesi telah berakhir. Silakan login kembali.";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $issuedAt = time();
            $expire = $issuedAt + EXPIRE_TIME;

            $payload = [
                'iat'  => $issuedAt,
                'exp'  => $expire,
                'sub'  => $user['id'],
                'user' => $user['username']
            ];

            $jwt = \Firebase\JWT\JWT::encode($payload, JWT_SECRET, JWT_ALGO);

            setcookie(
                COOKIE_NAME,
                $jwt,
                [
                    'expires' => $expire,
                    'path' => '/',
                    'domain' => '',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]
            );

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
<html class="light" lang="id" data-default-theme="light">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Login Admin Panel</title>
    <meta name="description" content="Masuk ke Admin Panel Cahaya Bone untuk mengelola booking, carter, bagasi, laporan, dan pengaturan operasional.">
    <meta name="theme-color" content="#f97316">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Cahaya Bone">
    <meta property="og:title" content="Login Admin Panel">
    <meta property="og:description" content="Masuk ke Admin Panel Cahaya Bone untuk mengelola booking, carter, bagasi, laporan, dan pengaturan operasional.">
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="shortcut icon" href="assets/images/favicon.svg">
    <!-- Preload: hanya font yang dipakai di halaman login (Plus Jakarta Sans 700 & 800, Space Grotesk 400) -->
    <link rel="preload" href="assets/lib/fonts/LDIbaomQNQcsA88c7O9yZ4KMCoOg4IA6-91aHEjcWuA_TknNSg.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="assets/lib/fonts/LDIbaomQNQcsA88c7O9yZ4KMCoOg4IA6-91aHEjcWuA_KUnNSg.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="assets/lib/fontawesome/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>
    <!-- Inline @font-face: hilangkan hop fonts.css → browser langsung tahu font yang dibutuhkan -->
    <style>
        /* Plus Jakarta Sans — weight yang dipakai di login (label, brand title, button) */
        @font-face {
            font-family: 'Plus Jakarta Sans';
            font-style: normal;
            font-weight: 600;
            font-display: swap;
            src: url('assets/lib/fonts/LDIbaomQNQcsA88c7O9yZ4KMCoOg4IA6-91aHEjcWuA_d0nNSg.ttf') format('truetype');
        }
        @font-face {
            font-family: 'Plus Jakarta Sans';
            font-style: normal;
            font-weight: 700;
            font-display: swap;
            src: url('assets/lib/fonts/LDIbaomQNQcsA88c7O9yZ4KMCoOg4IA6-91aHEjcWuA_TknNSg.ttf') format('truetype');
        }
        @font-face {
            font-family: 'Plus Jakarta Sans';
            font-style: normal;
            font-weight: 800;
            font-display: swap;
            src: url('assets/lib/fonts/LDIbaomQNQcsA88c7O9yZ4KMCoOg4IA6-91aHEjcWuA_KUnNSg.ttf') format('truetype');
        }
        /* Space Grotesk — font body halaman login */
        @font-face {
            font-family: 'Space Grotesk';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('assets/lib/fonts/V8mQoQDjQSkFtoMM3T6r8E7mF71Q-gOoraIAEj7oUUsj.ttf') format('truetype');
        }
    </style>
    <!-- Critical Stylesheets -->
    <link rel="stylesheet" href="assets/css/fontawesome-custom.min.css?v=1">
    <!-- Non-critical stylesheets loaded with preload-onload swap -->
    <link rel="preload" href="assets/css/login.css?v=1" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="assets/css/login.css?v=1"></noscript>
</head>
<body class="app-login bg-industrial-grid">
    
    <div class="login-wrapper">
        <header class="login-header">
            <div class="login-brand-container">
                <h1 class="login-brand-title">Cahaya Bone</h1>
                <p class="login-brand-subtitle">Portal Akses Operator</p>
            </div>
        </header>

        <main class="login-main">
            <div class="login-card">
                <div class="login-card-header"></div>
                <div class="login-card-content">
                    <h2 class="login-card-title">Masuk</h2>
                    <p class="login-card-subtitle">Masukkan akun operator untuk mengakses panel kendali.</p>

                    <?php if ($error_msg): ?>
                        <div class="login-error">
                            <?php echo htmlspecialchars($error_msg); ?>
                        </div>
                    <?php endif; ?>

                    <form class="login-form" method="POST">
                        <div class="form-group">
                            <label class="form-label" for="username">Username atau Email</label>
                            <input autocomplete="username" class="form-input" id="username" name="username" placeholder="Masukkan username atau email" required type="text" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <input autocomplete="current-password" class="form-input" id="password" name="password" placeholder="Masukkan password" required type="password">
                        </div>

                        <button class="form-submit-btn" type="submit">Masuk</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    
</body>
</html>
