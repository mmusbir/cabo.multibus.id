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

            $jwt = JWT::encode($payload, JWT_SECRET, JWT_ALGO);

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
<html class="dark" lang="id">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Login | KINETIC COMMAND</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "on-error": "#690005",
                        "surface-bright": "#373940",
                        "primary-container": "#f97316",
                        "primary-fixed-dim": "#ffb690",
                        "primary-fixed": "#ffdbca",
                        "surface-variant": "#33343b",
                        "on-tertiary-container": "#003554",
                        "on-secondary": "#233143",
                        "on-tertiary-fixed-variant": "#004b74",
                        "on-secondary-fixed-variant": "#39485a",
                        "background": "#111319",
                        "tertiary-fixed": "#cde5ff",
                        "secondary-fixed-dim": "#b9c8de",
                        "on-error-container": "#ffdad6",
                        "on-background": "#e2e2eb",
                        "secondary-container": "#39485a",
                        "inverse-on-surface": "#2e3037",
                        "surface-container-highest": "#33343b",
                        "primary": "#ffb690",
                        "on-primary-fixed": "#341100",
                        "surface-container": "#1e1f26",
                        "tertiary-fixed-dim": "#93ccff",
                        "on-surface": "#e2e2eb",
                        "on-surface-variant": "#e0c0b1",
                        "outline": "#a78b7d",
                        "error-container": "#93000a",
                        "surface-container-lowest": "#0c0e14",
                        "on-tertiary-fixed": "#001d32",
                        "on-tertiary": "#003351",
                        "on-primary": "#552100",
                        "surface-container-high": "#282a30",
                        "on-primary-fixed-variant": "#783200",
                        "inverse-primary": "#9d4300",
                        "error": "#ffb4ab",
                        "on-secondary-container": "#a7b6cc",
                        "on-secondary-fixed": "#0d1c2d",
                        "tertiary": "#93ccff",
                        "tertiary-container": "#00a2f4",
                        "surface": "#111319",
                        "surface-container-low": "#191b22",
                        "secondary-fixed": "#d4e4fa",
                        "surface-tint": "#ffb690",
                        "outline-variant": "#584237",
                        "surface-dim": "#111319",
                        "secondary": "#b9c8de",
                        "on-primary-container": "#582200",
                        "inverse-surface": "#e2e2eb"
                    },
                    fontFamily: {
                        "headline": ["Plus Jakarta Sans"],
                        "body": ["Space Grotesk"],
                        "label": ["Plus Jakarta Sans"]
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }
        .bg-industrial-grid {
            background-image: radial-gradient(circle at 2px 2px, rgba(249, 115, 22, 0.03) 1px, transparent 0);
            background-size: 40px 40px;
        }
        body {
            min-height: max(884px, 100dvh);
        }
    </style>
</head>
<body class="bg-surface-dim text-on-surface font-body selection:bg-primary-container selection:text-on-primary-container min-h-screen flex flex-col items-center justify-center px-6 py-10 bg-industrial-grid">
    <div class="w-full flex flex-col items-center justify-center gap-10">
        <header class="text-center">
            <div class="flex flex-col items-center gap-4">
                <h1 class="font-headline font-black text-3xl tracking-[0.2em] uppercase text-primary-container">Cahaya Bone</h1>
                <p class="font-label text-[10px] tracking-[0.4em] text-outline uppercase opacity-60">Portal Akses Operator</p>
            </div>
        </header>

        <main class="w-full max-w-md">
            <div class="bg-surface-container-low rounded-xl overflow-hidden shadow-2xl relative">
                <div class="h-1 w-full bg-gradient-to-r from-primary-container to-primary"></div>
                <div class="p-8 md:p-10">
                    <div class="mb-10 text-center">
                        <h2 class="font-headline font-bold text-2xl text-on-surface tracking-tight">Masuk</h2>
                        <p class="text-on-surface-variant text-sm mt-2">Masukkan akun operator untuk mengakses panel kendali.</p>
                    </div>

                    <?php if ($error_msg): ?>
                        <div class="mb-6 rounded-lg border border-error/30 bg-error-container/20 px-4 py-3 text-sm text-on-error-container">
                            <?php echo htmlspecialchars($error_msg); ?>
                        </div>
                    <?php endif; ?>

                    <form class="space-y-6" method="POST">
                        <div class="space-y-2">
                            <label class="font-label text-[10px] font-bold uppercase tracking-widest text-outline ml-1" for="username">Username atau Email</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline group-focus-within:text-primary-container transition-colors">
                                    <span class="material-symbols-outlined text-lg">person</span>
                                </div>
                                <input autocomplete="username" class="w-full bg-surface-container-highest border-none rounded-lg py-4 pl-12 pr-4 text-on-surface font-body placeholder:text-outline/50 focus:ring-2 focus:ring-primary-container/20 transition-all" id="username" name="username" placeholder="Masukkan username atau email" required type="text" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="px-1"><label class="font-label text-[10px] font-bold uppercase tracking-widest text-outline" for="password">Password</label></div>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-outline group-focus-within:text-primary-container transition-colors">
                                    <span class="material-symbols-outlined text-lg">lock</span>
                                </div>
                                <input autocomplete="current-password" class="w-full bg-surface-container-highest border-none rounded-lg py-4 pl-12 pr-12 text-on-surface font-body placeholder:text-outline/50 focus:ring-2 focus:ring-primary-container/20 transition-all" id="password" name="password" placeholder="Masukkan password" required type="password">
                                <button class="absolute inset-y-0 right-0 pr-4 flex items-center text-outline hover:text-on-surface transition-colors" id="toggle-password" type="button">
                                    <span class="material-symbols-outlined text-lg">visibility</span>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 px-1">
                            <div class="relative flex items-center">
                                <input class="w-5 h-5 rounded border-outline-variant bg-surface-container-highest text-primary-container focus:ring-primary-container/20 focus:ring-offset-surface-container-low" id="remember" type="checkbox">
                            </div>
                            <label class="text-sm text-on-surface-variant font-medium select-none cursor-pointer" for="remember">Ingat perangkat ini</label>
                        </div>

                        <div class="pt-4">
                            <button class="w-full bg-primary-container text-on-primary-fixed font-headline font-extrabold text-sm tracking-widest uppercase py-5 rounded-lg shadow-lg hover:bg-primary transition-all active:scale-[0.98] relative overflow-hidden group" type="submit">
                                <div class="absolute inset-0 bg-white/10 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                <span class="relative">Masuk</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <div class="flex flex-col items-center gap-3 opacity-40">
            <div class="flex items-center gap-6">
                <span class="material-symbols-outlined text-2xl">encrypted</span>
                <span class="material-symbols-outlined text-2xl">verified_user</span>
                <span class="material-symbols-outlined text-2xl">shield</span>
            </div>
            <p class="font-label text-[9px] tracking-[0.2em] uppercase text-center">Autentikasi Berlapis Diperlukan</p>
        </div>
    </div>

    <script>
        const togglePasswordBtn = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');

        if (togglePasswordBtn && passwordInput) {
            togglePasswordBtn.addEventListener('click', () => {
                const isHidden = passwordInput.type === 'password';
                passwordInput.type = isHidden ? 'text' : 'password';
                togglePasswordBtn.querySelector('.material-symbols-outlined').textContent = isHidden ? 'visibility_off' : 'visibility';
            });
        }
    </script>
</body>
</html>
