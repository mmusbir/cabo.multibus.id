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
    <!-- Preload critical font resources (woff2 format) -->
    <link rel="preload" href="assets/lib/fonts/UcCO3FwrK3iLTeHuS_nVMrMxCp50SjIw2boKoduKmMEVuLyfMZg.ttf" as="font" type="font/ttf" crossorigin>
    <link rel="preload" href="assets/lib/fontawesome/webfonts/fa-solid-900.woff2" as="font" type="font/woff2" crossorigin>
    <!-- Preload critical stylesheets -->
    <link rel="preload" href="assets/lib/fonts/fonts.css" as="style">
    <link rel="preload" href="assets/css/fontawesome-custom.min.css" as="style">
    <!-- Prefetch deferred stylesheets -->
    <link rel="prefetch" href="assets/css/theme-toggle.css">
    <link rel="prefetch" href="assets/css/login.css">
    <script>
        (function () {
            try {
                var storedTheme = localStorage.getItem('siteTheme');
                var theme = storedTheme === 'light' || storedTheme === 'dark' ? storedTheme : 'light';

                document.documentElement.setAttribute('data-theme', theme);
                document.documentElement.classList.toggle('dark', theme === 'dark');
                document.documentElement.classList.toggle('light', theme === 'light');
            } catch (err) {}
        })();
    </script>
    <!-- Critical Stylesheets -->
    <link rel="stylesheet" href="assets/lib/fonts/fonts.css?v=1">
    <link rel="stylesheet" href="assets/css/fontawesome-custom.min.css?v=1">
    <!-- Deferred Stylesheets -->
    <link rel="stylesheet" href="assets/css/theme-toggle.css?v=13" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="assets/css/login.css?v=1" media="print" onload="this.media='all'">
</head>
<body class="app-login bg-industrial-grid">
    <button class="theme-toggle-btn login-theme-toggle" type="button" data-theme-toggle aria-label="Ubah tema">
        <i class="fa-solid fa-sun fa-icon" data-theme-icon></i>
    </button>
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

    <script src="assets/js/theme-toggle.js" defer></script>
</body>
</html>
