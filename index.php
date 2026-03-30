<?php
// index.php - Protected entry point that shows the booking UI only after login
require_once __DIR__ . '/middleware/auth.php';

// Redirects to login.php if JWT cookie is missing/invalid
$auth = requireAdminAuth();

// Serve the original HTML content
$htmlPath = __DIR__ . '/views/index.html';
if (!file_exists($htmlPath)) {
    http_response_code(500);
    echo "File views/index.html tidak ditemukan.";
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
$html = file_get_contents($htmlPath);
if ($auth) {
    $html = str_replace(
        ['{{USER_NAME}}', '{{USER_INITIAL}}'], 
        [
            htmlspecialchars((string) ($auth['user'] ?? 'Admin')),
            htmlspecialchars(strtoupper(substr((string) ($auth['user'] ?? 'A'), 0, 1)))
        ], 
        $html
    );
}
echo $html;
exit;
