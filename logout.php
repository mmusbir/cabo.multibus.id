<?php
/**
 * logout.php - JWT Cookie Invalider
 */

require_once __DIR__ . '/config/auth_config.php';

// Invalidate cookie by setting expiration to past
setcookie(
    COOKIE_NAME, 
    "", 
    [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '', 
        'secure' => isset($_SERVER['HTTPS']), 
        'httponly' => true,
        'samesite' => 'Strict'
    ]
);

// Redirect back to login
header('Location: login.php');
exit;
