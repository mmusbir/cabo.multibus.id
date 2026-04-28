<?php
// index.php - Smart entry point (eliminates redirect for faster LCP)
// Performance optimization: Render appropriate page directly based on auth state
// eliminates 260+ ms redirect overhead

$timer_start = microtime(true);

require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/auth_config.php';

// Check auth without redirect - allows us to render appropriate page
$token = $_COOKIE[COOKIE_NAME] ?? null;
$auth = null;
$isAuthenticated = false;

if ($token) {
    try {
        $auth = (array)\Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key(JWT_SECRET, JWT_ALGO));
        $isAuthenticated = true;
    } catch (Exception $e) {
        // Invalid/expired token - treat as unauthenticated
        setcookie(COOKIE_NAME, "", time() - 3600, "/", "", true, true);
    }
}

// If authenticated, show booking page; otherwise show login form
if ($isAuthenticated && $auth) {
    // Serve booking page directly - NO REDIRECT needed
    header('Content-Type: text/html; charset=UTF-8');
    
    // Add Server-Timing header for performance monitoring
    $timer_auth = round((microtime(true) - $timer_start) * 1000, 1);
    header("Server-Timing: auth;dur={$timer_auth};desc=\"JWT verification\"");
    
    $htmlPath = __DIR__ . '/views/index.html';
    if (!file_exists($htmlPath)) {
        http_response_code(500);
        echo "File views/index.html tidak ditemukan.";
        exit;
    }

    $html = file_get_contents($htmlPath);
    $html = str_replace(
        ['{{USER_NAME}}', '{{USER_INITIAL}}'], 
        [
            htmlspecialchars((string) ($auth['user'] ?? 'Admin')),
            htmlspecialchars(strtoupper(substr((string) ($auth['user'] ?? 'A'), 0, 1)))
        ], 
        $html
    );
    echo $html;
    exit;
} else {
    // Not authenticated - include login.php content directly (NO 302 REDIRECT)
    // This eliminates the 260ms redirect delay
    header('Content-Type: text/html; charset=UTF-8');
    $timer_auth = round((microtime(true) - $timer_start) * 1000, 1);
    header("Server-Timing: auth_check;dur={$timer_auth};desc=\"Authentication check (redirect eliminated)\"");
    
    require_once __DIR__ . '/login.php';
    exit;
}
