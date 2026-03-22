<?php
/**
 * middleware/auth.php - Verify JWT Access Token in Cookie
 */

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Ensure Composer Autoload is included (Relative to this file)
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/auth_config.php';

/**
 * Validasi Token di setiap halaman yang dilindungi
 * @return array Object decoded payload if valid
 */
function requireAdminAuth() {
    $token = $_COOKIE[COOKIE_NAME] ?? null;

    if (!$token) {
        // Redirect to Login if no token
        header('Location: login.php?error=unauthorized');
        exit;
    }

    try {
        // Decode JWT using Secret from config
        $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGO));
        
        // Return decoded data (sub = ID User)
        return (array)$decoded;
    } catch (Exception $e) {
        // If expired or invalid, redirect to Login
        setcookie(COOKIE_NAME, "", time() - 3600, "/", "", true, true);
        header('Location: login.php?error=expired');
        exit;
    }
}

/**
 * Optional: Check if user is logged in but don't redirect
 */
function getAuthenticatedUser() {
    $token = $_COOKIE[COOKIE_NAME] ?? null;
    if (!$token) return null;

    try {
        return (array)JWT::decode($token, new Key(JWT_SECRET, JWT_ALGO));
    } catch (Exception $e) {
        return null;
    }
}
