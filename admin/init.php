<?php
/**
 * admin/init.php - Shared initialization for admin module
 * Contains: session, DB connection, auth check, and helper functions
 */

require_once __DIR__ . '/../config/env.php';

// Error reporting - disable in production
if (getenv('APP_ENV') === 'production') {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

if (session_status() === PHP_SESSION_NONE) {
    // Secure session settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_path', '/'); // Crucial for AJAX consistency
    
    $isHttps = (
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    );
    if ($isHttps) {
        ini_set('session.cookie_secure', 1);
    }
    
    session_start();
}

// Database Connection
require_once __DIR__ . '/../config/db.php';

/********** HELPER FUNCTIONS **********/

function validDate($d)
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
}

function validTime($t)
{
    return preg_match('/^\d{2}:\d{2}$/', $t);
}

function formatTimeWithLabel($timeStr)
{
    if (!$timeStr || $timeStr === '00:00:00' || $timeStr === '-')
        return '-';
    $time = strtotime($timeStr);
    $hour = (int) date('H', $time);
    $ampm = date('h:i A', $time);

    $label = 'Malam';
    if ($hour >= 5 && $hour < 11)
        $label = 'Pagi';
    elseif ($hour >= 11 && $hour < 15)
        $label = 'Siang';
    elseif ($hour >= 15 && $hour < 19)
        $label = 'Sore';

    return $ampm . ' (' . $label . ')';
}

function formatBookingId($id, $created_at)
{
    $year = date('y', strtotime($created_at));
    return '#CBP' . $year . str_pad($id, 5, '0', STR_PAD_LEFT);
}

function formatCustomerId($id, $created_at)
{
    $year = date('y', strtotime($created_at));
    return 'CST' . $year . str_pad($id, 5, '0', STR_PAD_LEFT);
}

function getSetting($conn, $key, $default = null)
{
    if (!$conn)
        return $default;
    try {
        $stmt = $conn->prepare("SELECT value FROM settings WHERE key=? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

function updateSetting($conn, $key, $value)
{
    if (!$conn)
        return false;
    try {
        $stmt = $conn->prepare("INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT (key) DO UPDATE SET value=EXCLUDED.value");
        return $stmt->execute([$key, $value]);
    } catch (PDOException $e) {
        return false;
    }
}

/********** AUTH CHECK FOR AJAX **********/

function requireAdminAuth()
{
    if (empty($_SESSION['admin']) || !$_SESSION['admin']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'unauthorized']);
        exit;
    }
}

/********** PAGINATION HELPERS **********/

function buildQueryString($overrides = [])
{
    $qs = array_merge($_GET, $overrides);
    foreach ($qs as $k => $v)
        if ($v === null)
            unset($qs[$k]);
    return http_build_query($qs);
}

function render_pagination_ajax($total, $per_page, $current_page, $param_prefix, $around = 2)
{
    if ($total <= $per_page)
        return '';
    $total_pages = (int) ceil($total / $per_page);
    $html = '<div style="display:flex;align-items:center;gap:8px;margin-top:8px;" class="pagination-container">';
    $prev = max(1, $current_page - 1);
    $html .= '<a class="badge ajax-page" href="?' . buildQueryString([$param_prefix . '_page' => $prev]) . '" data-target="' . $param_prefix . '" data-page="' . $prev . '">Prev</a>';
    $start = max(1, $current_page - $around);
    $end = min($total_pages, $current_page + $around);
    if ($start > 1)
        $html .= '<span class="small dots">...</span>';
    for ($p = $start; $p <= $end; $p++) {
        if ($p == $current_page)
            $html .= '<span class="badge active">' . $p . '</span>';
        else
            $html .= '<a class="badge ajax-page" href="?' . buildQueryString([$param_prefix . '_page' => $p]) . '" data-target="' . $param_prefix . '" data-page="' . $p . '">' . $p . '</a>';
    }
    if ($end < $total_pages)
        $html .= '<span class="small dots">...</span>';
    $next = min($total_pages, $current_page + 1);
    $html .= '<a class="badge ajax-page" href="?' . buildQueryString([$param_prefix . '_page' => $next]) . '" data-target="' . $param_prefix . '" data-page="' . $next . '">Next</a>';
    $html .= '<div class="small" style="margin-left:12px">Halaman ' . $current_page . ' dari ' . $total_pages . ' (Total: ' . $total . ')</div>';
    $html .= '</div>';
    return $html;
}
