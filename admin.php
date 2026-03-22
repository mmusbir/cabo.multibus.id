<?php

$isActionRequest = isset($_REQUEST['action']);
if ($isActionRequest) {
  // Keep AJAX responses JSON-clean even if PHP emits warnings/notices.
  ini_set('display_errors', 0);
  ini_set('display_startup_errors', 0);
  ini_set('html_errors', 0);
} else {
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
}
error_reporting(E_ALL);
// admin.php — Router-based ACTION handling (v2.0)
// IMPORTANT: remove the DEBUG / AUTO-LOGIN block before deploying to production.

// Error reporting - disable in production
/* 
if (getenv('APP_ENV') === 'production') {
  ini_set('display_errors', 0);
  ini_set('display_startup_errors', 0);
  error_reporting(0);
} else {
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
}
*/
if (!$isActionRequest) {
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
}

if (session_status() === PHP_SESSION_NONE) {
  // Secure session settings
  ini_set('session.cookie_httponly', 1);
  if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
  }
  session_start();
}

require_once 'middleware/auth.php';
require_once 'config/db.php';
require_once 'config/auth_config.php';
require_once 'Router.php';

// Check Auth immediately BEFORE any HTML output
$auth = null;
if (!isset($_REQUEST['action'])) {
  $auth = requireAdminAuth();
  // ==================== DATABASE MIGRATION ====================
  // Only run for page loads, not for AJAX requests
  // AJAX requests assume tables were created during initial page load
  require_once 'db-migrate.php';
}

// ==================== HELPERS ====================

function validDate($d) {
  return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d);
}

function validTime($t) {
  return preg_match('/^\d{2}:\d{2}$/', $t);
}

function formatTimeWithLabel($timeStr) {
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

function formatBookingId($id, $created_at) {
  $year = date('y', strtotime($created_at));
  return '#CBP' . $year . str_pad($id, 5, '0', STR_PAD_LEFT);
}

function formatCustomerId($id, $created_at) {
  $year = date('y', strtotime($created_at));
  return 'CST' . $year . str_pad($id, 5, '0', STR_PAD_LEFT);
}

function buildQueryString($overrides = []) {
  $qs = array_merge($_GET, $overrides);
  foreach ($qs as $k => $v)
    if ($v === null)
      unset($qs[$k]);
  return http_build_query($qs);
}

function render_pagination_ajax($total, $per_page, $current_page, $param_prefix, $around = 2) {
  if ($total <= $per_page)
    return '';
  $total_pages = (int) ceil($total / $per_page);
  $html = '<div class="pagination-container">';
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
  $html .= '<div class="small pagination-summary">Halaman ' . $current_page . ' dari ' . $total_pages . ' (Total: ' . $total . ')</div>';
  $html .= '</div>';
  return $html;
}

function getSetting($conn, $key, $default = null) {
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

function updateSetting($conn, $key, $value) {
  if (!$conn)
    return false;
  try {
    $stmt = $conn->prepare("INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT (key) DO UPDATE SET value=EXCLUDED.value");
    return $stmt->execute([$key, $value]);
  } catch (PDOException $e) {
    return false;
  }
}

// ==================== ROUTER-BASED ACTION HANDLING ====================

if ($isActionRequest) {
  header('Content-Type: application/json');
  set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
      return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
  });
  
  try {
    // AUTH CHECK
    $auth = getAuthenticatedUser();
    if (!$auth) {
      echo json_encode(['success' => false, 'error' => 'unauthorized']);
      exit;
    }
    
    // Initialize Router
    $router = new Router();
    $ajax_dir = __DIR__ . '/admin/ajax/';
    
    // ===== AJAX ROUTES - Include Files =====
    // These routes include specific files that handle the logic
    
    $router->get('routesPage', function () use ($ajax_dir) {
      include $ajax_dir . 'routes.php';
    });
    
    $router->get('bookingsPage', function () use ($ajax_dir) {
      include $ajax_dir . 'bookings.php';
    });
    
    $router->get('customersPage', function () use ($ajax_dir) {
      include $ajax_dir . 'customers.php';
    });
    
    $router->get('chartersPage', function () use ($ajax_dir) {
      include $ajax_dir . 'charters.php';
    });
    
    $router->get('schedulesPage', function () use ($ajax_dir) {
      include $ajax_dir . 'schedules_page.php';
    });
    
    $router->get('usersPage', function () use ($ajax_dir) {
      include $ajax_dir . 'users.php';
    });
    
    $router->get('cancellationsPage', function () use ($ajax_dir) {
      include $ajax_dir . 'cancellations.php';
    });
    
    $router->get('luggagePage', function () use ($ajax_dir) {
      include $ajax_dir . 'luggage_page.php';
    });
    
    $router->get('reportsPage', function () use ($ajax_dir) {
      include $ajax_dir . 'reports.php';
    });
    
    $router->get('luggageServicesPage', function () use ($ajax_dir) {
      include $ajax_dir . 'luggage_services_page.php';
    });
    
    $router->get('luggageServiceCRUD', function () use ($ajax_dir) {
      include $ajax_dir . 'luggage_service_crud.php';
    });
    
    $router->get('getSchedules', function () use ($ajax_dir) {
      include $ajax_dir . 'schedules.php';
    });
    
    $router->get('getPassengers', function () use ($ajax_dir) {
      include $ajax_dir . 'passengers.php';
    });
    
    $router->any('assignDriver', function () use ($ajax_dir) {
      include $ajax_dir . 'assign_driver.php';
    });
    
    $router->get('getAvailableUnits', function () use ($ajax_dir) {
      include $ajax_dir . 'get_available_units.php';
    });
    
    $router->get('getScheduleSeats', function () use ($ajax_dir) {
      include $ajax_dir . 'get_schedule_seats.php';
    });
    
    $router->get('exportReportCsv', function () use ($ajax_dir) {
      include $ajax_dir . 'export_report_csv.php';
    });
    
    $router->get('changePassword', function () use ($ajax_dir) {
      include $ajax_dir . 'change_password.php';
    });
    
    // Charter CRUD actions
    $router->any('delete_charter', function () use ($ajax_dir) {
      include $ajax_dir . 'charter_crud.php';
    });
    
    $router->any('get_charter', function () use ($ajax_dir) {
      include $ajax_dir . 'charter_crud.php';
    });
    
    $router->any('update_charter', function () use ($ajax_dir) {
      include $ajax_dir . 'charter_crud.php';
    });
    
    $router->any('toggle_bop', function () use ($ajax_dir) {
      include $ajax_dir . 'charter_crud.php';
    });
    
    $router->get('get_units', function () use ($ajax_dir) {
      include $ajax_dir . 'charter_crud.php';
    });
    
    $router->get('get_charter_routes', function () use ($ajax_dir) {
      include $ajax_dir . 'charter_crud.php';
    });
    
    $router->get('get_drivers', function () use ($ajax_dir) {
      include $ajax_dir . 'charter_crud.php';
    });
    
    // Luggage actions
    $router->post('markLuggagePaid', function () use ($ajax_dir) {
      include $ajax_dir . 'luggage_actions.php';
    });
    
    $router->post('cancelLuggage', function () use ($ajax_dir) {
      include $ajax_dir . 'luggage_actions.php';
    });
    
    $router->post('inputLuggage', function () use ($ajax_dir) {
      include $ajax_dir . 'luggage_actions.php';
    });
    
    // Dispatch request
    $router->dispatch();
    
  } catch (Error $e) {
    if (ob_get_length()) ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'PHP_ERROR: ' . $e->getMessage()]);
    exit;
  } catch (Exception $ex) {
    if (ob_get_length()) ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'EXCEPTION: ' . $ex->getMessage()]);
    exit;
  } finally {
    restore_error_handler();
  }
}

/********** AUTH HANDLER **********/
if (isset($_GET['logout'])) {
  header('Location: logout.php');
  exit;
}

/********** EDIT-FETCH HANDLERS (dari halaman form) **********/
// Code di sini dipangil ketika non-AJAX page loads
// ... (preserved dari original)

// Fetch All Segments for Dropdowns
$globalSegments = [];
$resSeg = $conn->query("SELECT id, rute, harga FROM segments ORDER BY rute ASC");
while ($rs = $resSeg->fetch()) {
  $globalSegments[] = $rs;
}

// ========================================
// NON-AJAX POST HANDLERS (CRUD, IMPORT, CANCEL)
// ========================================
// 
// NOTE: Handlers di bawah ini masih menggunakan $_POST checks
// Jika ingin full routing, bisa dimigrasikan ke POST routes juga
// Untuk sekarang, kami preserve original logic untuk stability

// (Include sisa dari admin.php.backup untuk handlers non-AJAX)
// Baca dari admin.php.backup dari baris 550 ke akhir untuk mencakup semua POST handlers

if (isset($_POST['save_route'])) {
  $route_id = isset($_POST['route_id']) ? intval($_POST['route_id']) : 0;
  $type = isset($_POST['route_type']) && $_POST['route_type'] === 'carter' ? 'carter' : 'reguler';

  $name = trim($_POST['route_name'] ?? '');
  $origin = trim($_POST['origin'] ?? '');
  $destination = trim($_POST['destination'] ?? '');
  $duration = trim($_POST['duration'] ?? '');
  $rental = floatval($_POST['rental_price'] ?? 0);
  $bop = floatval($_POST['bop_price'] ?? 0);
  $notes = trim($_POST['notes'] ?? '');

  if ($type === 'carter') {
    if ($origin && $destination) {
      $name = "$origin - $destination";
    }
    if ($name) {
      if ($route_id > 0) {
        $stmt = $conn->prepare("UPDATE master_carter SET name=?, origin=?, destination=?, duration=?, rental_price=?, bop_price=?, notes=? WHERE id=?");
        $stmt->execute([$name, $origin, $destination, $duration, $rental, $bop, $notes, $route_id]);
      } else {
        $stmt = $conn->prepare("INSERT INTO master_carter(name, origin, destination, duration, rental_price, bop_price, notes) VALUES(?,?,?,?,?,?,?) ON CONFLICT (origin, destination, duration) DO NOTHING");
        $stmt->execute([$name, $origin, $destination, $duration, $rental, $bop, $notes]);
      }
    }
      } else {
    if ($origin && $destination) {
      $name = "$origin - $destination";
    }
    if ($name) {
      if ($route_id > 0) {
        $stmtOld = $conn->prepare("SELECT name FROM routes WHERE id=? LIMIT 1");
        $stmtOld->execute([$route_id]);
        $oldName = ($r = $stmtOld->fetch()) ? $r['name'] : '';
        
        $stmt = $conn->prepare("UPDATE routes SET name=?, origin=?, destination=? WHERE id=?");
        $stmt->execute([$name, $origin, $destination, $route_id]);
        
        if ($oldName && $oldName !== $name) {
          $conn->prepare("UPDATE bookings SET rute=? WHERE rute=?")->execute([$name, $oldName]);
          $conn->prepare("UPDATE schedules SET rute=? WHERE rute=?")->execute([$name, $oldName]);
        }
      } else {
        $stmt = $conn->prepare("INSERT INTO routes(name, origin, destination) VALUES(?,?,?)");
        $stmt->execute([$name, $origin, $destination]);
      }
    }
  }
  header('Location: admin.php#routes');
  exit;
}
if (isset($_GET['delete_route'])) {
  $id = intval($_GET['delete_route']);
  $stmt = $conn->prepare("DELETE FROM routes WHERE id=?");
  $stmt->execute([$id]);
  header('Location: admin.php#routes');
  exit;
}
if (isset($_GET['delete_carter'])) {
  $id = intval($_GET['delete_carter']);
  $stmt = $conn->prepare("DELETE FROM master_carter WHERE id=?");
  $stmt->execute([$id]);
  header('Location: admin.php#routes');
  exit;
}

/* SCHEDULES */
if (isset($_POST['save_schedule'])) {
  $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
  $rute = trim($_POST['sch_rute'] ?? '');
  $dow = intval($_POST['sch_dow'] ?? 0);
  $jam = $_POST['sch_jam'] ?? '';
  $units = intval($_POST['sch_units'] ?? 1);
  $seats = intval($_POST['sch_seats'] ?? 8);
  $unit_id = isset($_POST['sch_unit_id']) && $_POST['sch_unit_id'] !== '' ? intval($_POST['sch_unit_id']) : null;
  if ($rute && $jam) {
    if ($schedule_id > 0) {
      $stmt = $conn->prepare("UPDATE schedules SET rute=?, dow=?, jam=?, units=?, seats=?, unit_id=? WHERE id=?");
      $stmt->execute([$rute, $dow, $jam, $units, $seats, $unit_id, $schedule_id]);
    } else {
      $stmt = $conn->prepare("INSERT INTO schedules (rute,dow,jam,units,seats,unit_id) VALUES (?,?,?,?,?,?) ON CONFLICT (rute, dow, jam) DO UPDATE SET units=EXCLUDED.units, seats=EXCLUDED.seats, unit_id=EXCLUDED.unit_id");
      $stmt->execute([$rute, $dow, $jam, $units, $seats, $unit_id]);
    }
  }
  header('Location: admin.php#schedules');
  exit;
}
if (isset($_GET['delete_schedule'])) {
  $id = intval($_GET['delete_schedule']);
  $stmt = $conn->prepare("DELETE FROM schedules WHERE id=?");
  $stmt->execute([$id]);
  header('Location: admin.php#schedules');
  exit;
}

/* BOOKINGS cancel (server) — supports AJAX (returns JSON) */
if (isset($_POST['save_booking_edit'])) {
  $id = intval($_POST['booking_id']);
  $seat = trim($_POST['seat']);
  $unit = intval($_POST['unit'] ?? 1);
  $pickup = trim($_POST['pickup_point']);
  $pembayaran = isset($_POST['edit_pembayaran']) ? trim($_POST['edit_pembayaran']) : '';
  $segment_id = intval($_POST['segment_id'] ?? 0);
  $price = floatval($_POST['price'] ?? 0);
  $discount = floatval($_POST['discount'] ?? 0);

  if ($pembayaran === '') {
    $pembayaran = 'Belum Lunas';
  }

  if ($id > 0) {
    $stmt = $conn->prepare("UPDATE bookings SET seat=?, unit=?, pickup_point=?, pembayaran=?, segment_id=?, price=?, discount=? WHERE id=?");
    $stmt->execute([$seat, $unit, $pickup, $pembayaran, $segment_id, $price, $discount, $id]);
  }
  header('Location: admin.php');
  exit;
}
if (isset($_GET['cancel_booking'])) {
  $id = intval($_GET['cancel_booking']);
  $reason = trim($_GET['reason'] ?? '');
  $admin_user = $_SESSION['admin_user'] ?? 'unknown';
  $result = ['success' => false];
  if ($id > 0) {
    $stmt = $conn->prepare("UPDATE bookings SET status='canceled' WHERE id=? AND status!='canceled'");
    $stmt->execute([$id]);
    $affected = $stmt->rowCount();
    if ($affected > 0) {
      $stmt2 = $conn->prepare("INSERT INTO cancellations (booking_id, admin_user, reason) VALUES (?,?,?)");
      $stmt2->execute([$id, $admin_user, $reason]);
      $result['success'] = true;
    } else {
      $result['error'] = 'nothing_changed';
    }
  } else {
    $result['error'] = 'invalid_id';
  }
  $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
  if ($isAjax || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
  }
  header('Location: admin.php');
  exit;
}

/* BOOKINGS mark paid (server) — supports AJAX (returns JSON) */
if (isset($_GET['mark_paid'])) {
  $id = intval($_GET['mark_paid']);
  $result = ['success' => false];
  if ($id > 0) {
    $stmt = $conn->prepare("UPDATE bookings SET pembayaran='Lunas' WHERE id=?");
    $stmt->execute([$id]);
    $affected = $stmt->rowCount();
    if ($affected > 0) {
      $result['success'] = true;
    } else {
      $result['error'] = 'nothing_changed';
    }
  } else {
    $result['error'] = 'invalid_id';
  }
  $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
  if ($isAjax || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
  }
  header('Location: admin.php');
  exit;
}

/* USERS create/update */
if (isset($_POST['add_user'])) {
  $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $fullname = trim($_POST['fullname'] ?? '');
  if ($user_id > 0 && $username) {
    if ($password !== '') {
      $hash = password_hash($password, PASSWORD_BCRYPT);
      $stmt = $conn->prepare("UPDATE users SET username=?, password_hash=?, fullname=? WHERE id=?");
      $stmt->execute([$username, $hash, $fullname, $user_id]);
    } else {
      $stmt = $conn->prepare("UPDATE users SET username=?, fullname=? WHERE id=?");
      $stmt->execute([$username, $fullname, $user_id]);
    }
  } elseif ($username && $password) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users(username,password_hash,fullname) VALUES(?,?,?)");
    $stmt->execute([$username, $hash, $fullname]);
  }
  header('Location: admin.php#users');
  exit;
}
if (isset($_GET['delete_user'])) {
  $id = intval($_GET['delete_user']);
  $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
  $stmt->execute([$id]);
  header('Location: admin.php#users');
  exit;
}

/* SETTINGS save */
if (isset($_POST['save_settings'])) {
  $enable_claude = isset($_POST['enable_claude_haiku_4_5']) ? '1' : '0';
  updateSetting($conn, 'enable_claude_haiku_4_5', $enable_claude);
  $_SESSION['settings_saved'] = true;
  header('Location: admin.php#settings');
  exit;
}

/* CUSTOMERS save/delete/import */
if (isset($_POST['save_customer'])) {
  $cid = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
  $name = trim($_POST['cust_name'] ?? '');
  $name = strtoupper($name); // Force Uppercase

  $phone = trim($_POST['cust_phone'] ?? '');
  $phone = preg_replace('/\D/', '', $phone); // Remove non-digits
  if (substr($phone, 0, 2) === '62')
    $phone = '0' . substr($phone, 2);
  if (substr($phone, 0, 1) === '8')
    $phone = '0' . $phone;
  if (strlen($phone) > 13)
    $phone = substr($phone, 0, 13);

  $pickup = trim($_POST['cust_pickup'] ?? '');
  $address = trim($_POST['cust_address'] ?? '');
  if ($name && $phone) {
    // Check duplicate phone
    $stmtC = $conn->prepare("SELECT id, name FROM customers WHERE phone=? LIMIT 1");
    $stmtC->execute([$phone]);
    $existing = $stmtC->fetch();

    if ($existing) {
      if ($cid > 0 && $existing['id'] != $cid) {
        $_SESSION['import_msg'] = "Gagal: No HP $phone sudah terdaftar atas nama " . $existing['name'];
        header('Location: admin.php#customers');
        exit;
      }
      if ($cid == 0) {
        $_SESSION['import_msg'] = "Gagal: No HP $phone sudah terdaftar atas nama " . $existing['name'];
        header('Location: admin.php#customers');
        exit;
      }
    }

    if ($cid > 0) {
      $stmt = $conn->prepare("UPDATE customers SET name=?, phone=?, pickup_point=?, address=? WHERE id=?");
      $stmt->execute([$name, $phone, $pickup, $address, $cid]);
    } else {
      $stmt = $conn->prepare("INSERT INTO customers (name,phone,address,pickup_point) VALUES(?,?,?,?) ON CONFLICT (phone) DO UPDATE SET address=EXCLUDED.address, pickup_point=EXCLUDED.pickup_point");
      $stmt->execute([$name, $phone, $address, $pickup]);
    }
  }
  header('Location: admin.php');
  exit;
}

if (isset($_POST['export_customers'])) {
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename=customers_' . date('Y-m-d_H-i') . '.csv');
  $output = fopen('php://output', 'w');

  // CSV Header
  fputcsv($output, ['ID', 'Nama', 'No HP', 'Alamat', 'Pickup Point', 'Created At']);

  // Data
  $stmt = $conn->prepare("SELECT id, name, phone, address, pickup_point, created_at FROM customers ORDER BY name ASC");
  $stmt->execute();
  while ($row = $stmt->fetch()) {
    fputcsv($output, [
      formatCustomerId($row['id'], $row['created_at']),
      $row['name'],
      $row['phone'],
      $row['address'],
      $row['pickup_point'],
      $row['created_at']
    ]);
  }
  fclose($output);
  exit;
}

if (isset($_GET['delete_customer'])) {
  $id = intval($_GET['delete_customer']);
  $stmt = $conn->prepare("DELETE FROM customers WHERE id=?");
  $stmt->execute([$id]);
  header('Location: admin.php');
  exit;
}

/* DRIVERS save/delete */
if (isset($_POST['save_driver'])) {
  $id = isset($_POST['driver_id']) ? intval($_POST['driver_id']) : 0;
  $nama = trim($_POST['driver_nama'] ?? '');
  $phone = trim($_POST['driver_phone'] ?? '');
  $unit_id = intval($_POST['driver_unit_id'] ?? 0);

  if ($nama && $phone && $unit_id > 0) {
    if ($id > 0) {
      $stmt = $conn->prepare("UPDATE drivers SET nama=?, phone=?, unit_id=? WHERE id=?");
      $stmt->execute([$nama, $phone, $unit_id, $id]);
    } else {
      $stmt = $conn->prepare("INSERT INTO drivers (nama, phone, unit_id) VALUES (?,?,?)");
      $stmt->execute([$nama, $phone, $unit_id]);
    }
  }
  header('Location: admin.php#drivers');
  exit;
}
if (isset($_GET['delete_driver'])) {
  $id = intval($_GET['delete_driver']);
  if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM drivers WHERE id=?");
    $stmt->execute([$id]);
  }
  header('Location: admin.php#drivers');
  exit;
}

/* SEGMENTS save/delete */
if (isset($_POST['save_segment'])) {
  $id = isset($_POST['segment_id']) ? intval($_POST['segment_id']) : 0;
  $route_id = isset($_POST['segment_route_id']) ? intval($_POST['segment_route_id']) : 0;
  $origin = trim($_POST['segment_origin'] ?? '');
  $destination = trim($_POST['segment_destination'] ?? '');
  $pickup_time = trim($_POST['segment_pickup_time'] ?? '');
  $harga = floatval($_POST['segment_harga'] ?? 0);
  $rute = "$origin - $destination";

  if ($pickup_time !== '' && !preg_match('/^\d{2}:\d{2}$/', $pickup_time)) {
    $pickup_time = '';
  }

  if ($origin && $destination) {
    if ($id > 0) {
      $stmt = $conn->prepare("UPDATE segments SET route_id=?, rute=?, origin=?, destination=?, pickup_time=?, harga=? WHERE id=?");
      $stmt->execute([$route_id, $rute, $origin, $destination, $pickup_time, $harga, $id]);
    } else {
      $stmt = $conn->prepare("INSERT INTO segments (route_id, rute, origin, destination, pickup_time, harga) VALUES (?,?,?,?,?,?)");
      $stmt->execute([$route_id, $rute, $origin, $destination, $pickup_time, $harga]);
    }
  }
  header('Location: admin.php#segments');
  exit;
}
if (isset($_GET['delete_segment'])) {
  $id = intval($_GET['delete_segment']);
  if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM segments WHERE id=?");
    $stmt->execute([$id]);
  }
  header('Location: admin.php#segments');
  exit;
}
if (isset($_POST['import_csv']) || isset($_POST['import_customers'])) {
  $fileKey = isset($_FILES['csv_file']) ? 'csv_file' : 'csv';
  if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['import_msg'] = 'Upload gagal';
    header('Location: admin.php#customers');
    exit;
  }
  $tmp = $_FILES[$fileKey]['tmp_name'];
  $rawLines = file($tmp, FILE_IGNORE_NEW_LINES);
  if (!$rawLines || !count($rawLines)) {
    $_SESSION['import_msg'] = 'CSV kosong';
    header('Location: admin.php#customers');
    exit;
  }
  $delimiter = (substr_count($rawLines[0], ';') > substr_count($rawLines[0], ',')) ? ';' : ',';
  $f = fopen($tmp, 'r');
  if (!$f) {
    $_SESSION['import_msg'] = 'Tidak dapat buka file';
    header('Location: admin.php#customers');
    exit;
  }
  $header = fgetcsv($f, 0, $delimiter);
  if (!$header) {
    $_SESSION['import_msg'] = 'CSV kosong';
    fclose($f);
    header('Location: admin.php#customers');
    exit;
  }
  $hmap = array_map(function ($v) {
    return strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $v)));
  }, $header);
  $colIndex = ['name' => -1, 'phone' => -1, 'pickup_point' => -1, 'address' => -1];
  foreach ($hmap as $i => $h) {
    if (in_array($h, ['name', 'nama']))
      $colIndex['name'] = $i;
    if (in_array($h, ['phone', 'no.hp', 'no_hp', 'no hp', 'hp', 'telp', 'telephone']))
      $colIndex['phone'] = $i;
    if (in_array($h, ['pickup_point', 'pickup', 'titik jemput', 'titik_jemput']))
      $colIndex['pickup_point'] = $i;
    if (in_array($h, ['address', 'alamat']))
      $colIndex['address'] = $i;
  }
  if ($colIndex['name'] == -1 || $colIndex['phone'] == -1) {
    $_SESSION['import_msg'] = 'Header CSV minimal name & phone';
    fclose($f);
    header('Location: admin.php#customers');
    exit;
  }
  $ok = 0;
  $err = 0;
  $conn->beginTransaction();
  while (($row = fgetcsv($f, 0, $delimiter)) !== false) {
    $name = strtoupper(trim($row[$colIndex['name']] ?? ''));
    $phone = trim($row[$colIndex['phone']] ?? '');
    $phone = preg_replace('/\D/', '', $phone);
    if (substr($phone, 0, 2) === '62')
      $phone = '0' . substr($phone, 2);
    if (substr($phone, 0, 1) === '8')
      $phone = '0' . $phone;
    if (strlen($phone) > 13)
      $phone = substr($phone, 0, 13);
    $pickup = trim($row[$colIndex['pickup_point']] ?? '');
    $address = trim($row[$colIndex['address']] ?? '');
    if (!$name || !$phone) {
      $err++;
      continue;
    }
    $stmt = $conn->prepare("INSERT INTO customers (name,phone,address,pickup_point) VALUES(?,?,?,?) ON CONFLICT (phone) DO UPDATE SET name=EXCLUDED.name, address=EXCLUDED.address, pickup_point=EXCLUDED.pickup_point");
    try {
      if ($stmt->execute([$name, $phone, $address, $pickup])) {
        $ok++;
      } else {
        $err++;
      }
    } catch (PDOException $e) {
      $err++;
    }
  }
  $conn->commit();
  fclose($f);
  $_SESSION['import_msg'] = "Import selesai - berhasil: $ok, error: $err";
  header('Location: admin.php#customers');
  exit;
}

/********** DATA FOR RENDER **********/
$routes = [];
$res = $conn->query("SELECT id,name FROM routes ORDER BY id");
while ($r = $res->fetch())
  $routes[] = $r;
$import_msg = $_SESSION['import_msg'] ?? '';
unset($_SESSION['import_msg']);
$booking_msg = $_SESSION['booking_msg'] ?? '';
unset($_SESSION['booking_msg']);
$settings_saved = $_SESSION['settings_saved'] ?? false;
unset($_SESSION['settings_saved']);
$cancellations = [];
if (db_table_exists($conn, 'cancellations')) {
  $res = $conn->query("SELECT id,booking_id,admin_user,reason,created_at FROM cancellations ORDER BY created_at DESC LIMIT 200");
  while ($r = $res->fetch())
    $cancellations[] = $r;
}

/* Load feature flags */
$enable_claude_haiku_4_5 = getSetting($conn, 'enable_claude_haiku_4_5', '0');

/* UNITS LOGIC */
include 'includes/units_logic.php';

/********** CONDITIONAL: ONLY RENDER HTML FOR NON-AJAX REQUESTS **********/
if (!isset($_REQUEST['action'])): 
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>Admin Panel — Hiace Booking</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/admin-bootstrap.css?v=25">
  <style>
    /* iOS Safari Auto-Zoom Prevention */
    @media (max-width: 768px) {
      input,
      input[type="text"],
      input[type="tel"],
      input[type="number"],
      input[type="email"],
      input[type="search"],
      select,
      textarea,
      .ss-search input,
      .ss-single-selected,
      .ss-multi-selected {
        font-size: 16px !important;
      }
    }
  </style>

</head>

<body class="admin-bootstrap-page">

  <div id="toast" class="toast" role="status" aria-live="polite"></div>

  <?php include 'includes/navbar.php'; ?>

  <main class="container container-fluid admin-bootstrap-container">
    <div class="layout admin-bootstrap-grid">
      <div class="left admin-main-column">
        <!-- DASHBOARD -->
        <?php include 'includes/dashboard.php'; ?>

        <!-- BOOKINGS -->
        <?php include 'includes/bookings.php'; ?>


        <!-- CUSTOMERS -->
        <?php include 'includes/customers.php'; ?>

        <!-- ROUTES -->
        <?php include 'includes/routes.php'; ?>

        <!-- SCHEDULES -->
        <?php include 'includes/schedules.php'; ?>

        <!-- DRIVERS -->
        <?php include 'includes/drivers.php'; ?>

        <!-- SEGMENTS -->
        <?php include 'includes/segments.php'; ?>

        <!-- USERS -->
        <?php include 'includes/users.php'; ?>

        <!-- UNITS -->
        <?php include 'includes/units.php'; ?>

        <!-- VIEW DETAIL -->
        <!-- VIEW DETAIL -->
        <?php include 'includes/booking_detail.php'; ?>

        <!-- CANCELLATIONS -->
        <?php include 'includes/cancellations.php'; ?>

        <!-- REPORTS -->
        <?php include 'includes/reports.php'; ?>

        <!-- LUGGAGE SERVICES -->
        <?php include 'includes/luggage_services.php'; ?>

      </div>

      <!-- RIGHT column -->
      <div class="right admin-side-column">
      </div>

    </div>
  </main>

  <!-- Bottom Navbar for Mobile -->
  <!-- Bottom Navbar for Mobile moved to includes/navbar.php -->
  <!-- Modal for Edit Booking (Seat & Pickup) -->
  <div class="bottom-more-modal admin-modal-overlay" id="editBookingModal">
    <div class="modal-popup-content admin-modal-card admin-modal-card-md admin-modal-card-form">
      <h3 class="modal-popup-title admin-modal-heading">Edit Penumpang</h3>

      <form method="post" id="editBookingForm" novalidate class="admin-modal-form">
        <div id="editBookingErrorMsg" class="admin-modal-error"></div>
        <input type="hidden" name="save_booking_edit" value="1">
        <input type="hidden" id="edit_booking_id" name="booking_id" value="">

        <div class="admin-modal-grid admin-modal-grid-2">
          <div class="admin-modal-field">
            <label class="admin-modal-label">Unit</label>
            <select id="edit_unit" name="unit" class="form-control admin-modal-control" required>
              <option value="1">Unit 1</option>
            </select>
          </div>
          <div class="admin-modal-field">
            <label class="admin-modal-label">Nomor Kursi</label>
            <select id="edit_seat" name="seat" class="form-control admin-modal-control" required>
              <option value="">Pilih Kursi</option>
            </select>
          </div>
        </div>

        <div class="admin-modal-field">
          <label class="admin-modal-label">Titik Jemput</label>
          <input type="text" id="edit_pickup" name="pickup_point" class="form-control admin-modal-control"
            placeholder="Lokasi jemput">
        </div>

        <div class="admin-modal-field">
          <label class="admin-modal-label">Status Pembayaran</label>
          <div class="admin-modal-radio-group">
            <label class="pay-radio-label admin-modal-radio-option">
              <input type="radio" name="edit_pembayaran" value="Belum Lunas" required>
              <span>Belum Lunas</span>
            </label>
            <label class="pay-radio-label admin-modal-radio-option">
              <input type="radio" name="edit_pembayaran" value="Lunas" required>
              <span>Lunas</span>
            </label>
            <label class="pay-radio-label admin-modal-radio-option">
              <input type="radio" name="edit_pembayaran" value="Redbus" required>
              <span>RedBus</span>
            </label>
          </div>
        </div>

        <div class="admin-modal-field">
          <label class="admin-modal-label">Segment Rute</label>
          <select id="edit_segment_id" name="segment_id" class="form-control admin-modal-control">
            <option value="0">-- Default Rute --</option>
            <?php foreach ($globalSegments as $gs): ?>
              <option value="<?= $gs['id'] ?>" data-price="<?= $gs['harga'] ?>">
                <?= htmlspecialchars($gs['rute']) ?> (Rp <?= number_format($gs['harga'], 0, ',', '.') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="admin-modal-grid admin-modal-grid-2">
          <div class="admin-modal-field">
            <label class="admin-modal-label">Harga (Rp)</label>
            <input type="number" id="edit_price_display" class="form-control admin-modal-control" disabled>
            <input type="hidden" id="edit_price" name="price">
          </div>
          <div class="admin-modal-field">
            <label class="admin-modal-label">Diskon (Rp)</label>
            <input type="number" id="edit_discount" name="discount" class="form-control admin-modal-control"
              placeholder="0">
          </div>
        </div>

        <div class="modal-popup-btn-group">
          <button type="submit" class="btn btn-modern modal-popup-btn">Simpan Perubahan</button>
          <button type="button" id="closeEditBookingModal" class="btn btn-modern secondary modal-popup-btn">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal for Edit Charter -->
  <div class="bottom-more-modal admin-modal-overlay" id="editCharterModal">
    <div class="modal-popup-content admin-modal-card admin-modal-card-lg admin-modal-card-form admin-modal-scroll">
      <h3 class="modal-popup-title admin-modal-heading">Edit Carter</h3>
      <form id="editCharterForm" class="admin-modal-form admin-modal-form-tight">
        <input type="hidden" id="edit_charter_id" name="id" value="">

        <div class="admin-modal-grid admin-modal-grid-2 admin-modal-grid-tight">
          <div class="admin-modal-field">
            <label class="admin-modal-label">Nama</label>
            <input type="text" id="edit_charter_name" name="name" class="form-control admin-modal-control admin-modal-control-tight">
          </div>
          <div class="admin-modal-field">
            <label class="admin-modal-label">Perusahaan</label>
            <input type="text" id="edit_charter_company" name="company_name" class="form-control admin-modal-control admin-modal-control-tight">
          </div>
        </div>

        <div class="admin-modal-grid admin-modal-grid-2 admin-modal-grid-tight">
          <div class="admin-modal-field">
            <label class="admin-modal-label">No. HP</label>
            <input type="text" id="edit_charter_phone" name="phone" class="form-control admin-modal-control admin-modal-control-tight">
          </div>
          <div class="admin-modal-field">
            <label class="admin-modal-label">Harga (Rp)</label>
            <input type="number" id="edit_charter_price" name="price" class="form-control admin-modal-control admin-modal-control-tight" min="0"
              step="1">
          </div>
        </div>

        <div class="admin-modal-grid admin-modal-grid-3 admin-modal-grid-tight">
          <div class="admin-modal-field">
            <label class="admin-modal-label">Tgl Mulai</label>
            <input type="date" id="edit_charter_start" name="start_date" class="form-control admin-modal-control admin-modal-control-tight">
          </div>
          <div class="admin-modal-field">
            <label class="admin-modal-label">Tgl Selesai</label>
            <input type="date" id="edit_charter_end" name="end_date" class="form-control admin-modal-control admin-modal-control-tight">
          </div>
          <div class="admin-modal-field">
            <label class="admin-modal-label">Jam</label>
            <input type="time" id="edit_charter_time" name="departure_time" class="form-control admin-modal-control admin-modal-control-tight">
          </div>
        </div>

        <div class="admin-modal-field">
          <label class="admin-modal-label">Pilih Rute (Auto-fill)</label>
          <select id="edit_charter_route_id" class="form-control admin-modal-control admin-modal-control-tight">
            <option value="">-- Master Rute Carter --</option>
          </select>
          <input type="hidden" id="edit_charter_pickup" name="pickup_point">
          <input type="hidden" id="edit_charter_drop" name="drop_point">
        </div>

        <div class="admin-modal-grid admin-modal-grid-2 admin-modal-grid-tight">
          <div class="admin-modal-field">
            <label class="admin-modal-label">Unit</label>
            <select id="edit_charter_unit" name="unit_id" class="form-control admin-modal-control admin-modal-control-tight">
              <option value="">-- Unit --</option>
            </select>
          </div>
          <div class="admin-modal-field">
            <label class="admin-modal-label">Driver</label>
            <select id="edit_charter_driver" name="driver_name" class="form-control admin-modal-control admin-modal-control-tight">
              <option value="">-- Pilih Driver --</option>
            </select>
          </div>
        </div>

        <div class="admin-modal-grid admin-modal-grid-2 admin-modal-grid-tight">
          <div class="admin-modal-field">
            <label class="admin-modal-label">Jenis Layanan</label>
            <input type="text" id="edit_charter_layanan" name="layanan" class="form-control admin-modal-control admin-modal-control-tight">
          </div>
          <div class="admin-modal-field">
            <label class="admin-modal-label">BOP (Nominal)</label>
            <input type="number" id="edit_charter_bop_val" name="bop_price" class="form-control admin-modal-control admin-modal-control-tight">
          </div>
        </div>

        <div class="admin-modal-actions admin-modal-actions-split">
          <button type="submit" class="btn btn-modern admin-modal-action admin-modal-action-wide">Simpan Perubahan</button>
          <button type="button" id="closeEditCharterModal" class="btn btn-modern secondary admin-modal-action">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal for Copy All Passengers -->
  <div class="bottom-more-modal admin-modal-overlay" id="copyAllModal">
    <div class="modal-popup-content admin-modal-card admin-modal-card-lg">
      <h3 class="modal-popup-title admin-modal-heading">Detail Penumpang Terisi</h3>
      <div id="copyAllList" class="admin-modal-list"></div>
      <div class="admin-modal-actions admin-modal-actions-split">
        <button id="copyAllFromModal" type="button" class="btn btn-modern admin-modal-action">Copy Semua</button>
        <button id="closeCopyAllModal" type="button" class="btn btn-modern secondary admin-modal-action">Tutup</button>
      </div>
    </div>
  </div>

  <!-- Modal for Custom Alert -->
  <div class="bottom-more-modal admin-modal-overlay" id="globalAlertModal">
    <div class="modal-popup-content admin-modal-card admin-modal-card-sm admin-modal-card-center">
      <div class="admin-modal-icon-row">
        <div id="alertIconContainer" class="admin-modal-icon-wrap admin-modal-icon-alert">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none"
            stroke="#d97706" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
        </div>
      </div>
      <div id="alertTitle" class="modal-popup-title">Pemberitahuan</div>
      <div id="alertMessage" class="modal-popup-message"></div>
      <button type="button" id="closeAlertBtn" class="btn btn-modern modal-popup-btn">Mengerti</button>
    </div>
  </div>

  <!-- Modal for Custom Confirm -->
  <div class="bottom-more-modal admin-modal-overlay" id="globalConfirmModal">
    <div class="modal-popup-content admin-modal-card admin-modal-card-sm admin-modal-card-center">
      <div class="admin-modal-icon-row">
        <div id="confirmIconWrapper" class="admin-modal-icon-wrap admin-modal-icon-confirm">
          <div id="confirmIconContainer"></div>
        </div>
      </div>
      <div id="confirmTitle" class="modal-popup-title">Konfirmasi</div>
      <div id="confirmMessage" class="modal-popup-message"></div>
      <div class="modal-popup-btn-group">
        <button type="button" id="okConfirmBtn" class="btn btn-modern modal-popup-btn">Ya, Lanjutkan</button>
        <button type="button" id="cancelConfirmBtn" class="btn btn-modern secondary modal-popup-btn">Batal</button>
      </div>
    </div>
  </div>

  <!-- Modal for Change Password -->
  <div class="bottom-more-modal admin-modal-overlay" id="changePasswordModal">
    <div class="modal-popup-content admin-modal-card admin-modal-card-md admin-modal-card-form">
      <h3 class="modal-popup-title admin-modal-heading">Ganti Password</h3>
      <form id="changePasswordForm" class="admin-modal-form">
        <div class="admin-modal-field">
          <label class="admin-modal-label">Password Lama</label>
          <input type="password" name="old_password" required class="form-control admin-modal-control">
        </div>
        <div class="admin-modal-field">
          <label class="admin-modal-label">Password Baru</label>
          <input type="password" name="new_password" required class="form-control admin-modal-control">
        </div>
        <div class="admin-modal-field">
          <label class="admin-modal-label">Konfirmasi Password Baru</label>
          <input type="password" name="confirm_password" required class="form-control admin-modal-control">
        </div>
        <div class="modal-popup-btn-group">
          <button type="submit" class="btn btn-modern modal-popup-btn">Update Password</button>
          <button type="button" id="closeChangePasswordModal" class="btn btn-modern secondary modal-popup-btn">Batal</button>
        </div>
      </form>
    </div>
  </div>
  <script>
    // --- Custom Modal Utilities ---
    window.customAlert = function (message, title = 'Pemberitahuan') {
      const modal = document.getElementById('globalAlertModal');
      document.getElementById('alertTitle').textContent = title;
      document.getElementById('alertMessage').textContent = message;
      modal.style.display = 'flex';
      setTimeout(() => modal.classList.add('show'), 10);
      return new Promise(resolve => {
        document.getElementById('closeAlertBtn').onclick = () => {
          modal.classList.remove('show');
          setTimeout(() => { modal.style.display = 'none'; }, 300);
          resolve();
        };
      });
    };

    window.customConfirm = function (message, onConfirm, title = 'Konfirmasi', type = 'danger') {
      const modal = document.getElementById('globalConfirmModal');
      const iconWrapper = document.getElementById('confirmIconWrapper');
      const iconContainer = document.getElementById('confirmIconContainer');
      const okBtn = document.getElementById('okConfirmBtn');

      document.getElementById('confirmTitle').textContent = title;
      document.getElementById('confirmMessage').textContent = message;

      // Reset & Set Icon + Button Style
      if (type === 'danger') {
        iconWrapper.style.backgroundColor = '#fef2f2';
        iconContainer.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>`;
        okBtn.style.background = '#ef4444';
        okBtn.style.borderColor = '#ef4444';
        okBtn.textContent = 'Ya, Lanjutkan';
      } else if (type === 'success') {
        iconWrapper.style.backgroundColor = '#f0fdf4';
        iconContainer.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>`;
        okBtn.style.background = '#22c55e';
        okBtn.style.borderColor = '#22c55e';
        okBtn.textContent = 'Ya, Selesai';
      }

      modal.style.display = 'flex';
      setTimeout(() => modal.classList.add('show'), 10);

      okBtn.onclick = () => {
        modal.classList.remove('show');
        setTimeout(() => { modal.style.display = 'none'; }, 300);
        if (onConfirm) onConfirm();
      };
      document.getElementById('cancelConfirmBtn').onclick = () => {
        modal.classList.remove('show');
        setTimeout(() => { modal.style.display = 'none'; }, 300);
      };
    };

    // --- Profile & Password Logic ---
    document.addEventListener('DOMContentLoaded', function () {
      const btnOpenPass = document.querySelectorAll('[data-open-change-password]');
      const passModal = document.getElementById('changePasswordModal');
      const closePass = document.getElementById('closeChangePasswordModal');
      const passForm = document.getElementById('changePasswordForm');

      if (btnOpenPass.length) {
        btnOpenPass.forEach(btn => {
          btn.onclick = () => {
            passModal.style.display = 'flex';
            setTimeout(() => passModal.classList.add('show'), 10);
          };
        });
      }
      if (closePass) {
        closePass.onclick = () => {
          passModal.classList.remove('show');
          setTimeout(() => { passModal.style.display = 'none'; }, 300);
        };
      }
      if (passModal) {
        passModal.onclick = (e) => {
          if (e.target === passModal) {
            passModal.classList.remove('show');
            setTimeout(() => { passModal.style.display = 'none'; }, 300);
          }
        };
      }

      if (passForm) {
        passForm.onsubmit = async (e) => {
          e.preventDefault();
          const formData = new FormData(passForm);
          formData.append('action', 'changePassword');

          try {
            const res = await fetch('admin.php?action=changePassword', {
              method: 'POST',
              body: formData
            });
            const js = await res.json();
            if (js.success) {
              await customAlert(js.message || 'Password berhasil diubah');
              passModal.classList.remove('show');
              setTimeout(() => { passModal.style.display = 'none'; }, 300);
              passForm.reset();
            } else {
              customAlert(js.error || 'Gagal mengubah password');
            }
          } catch (err) {
            customAlert('Kesalahan koneksi saat mengubah password');
          }
        };
      }
    });

    document.addEventListener('DOMContentLoaded', function () {
      // Show only active section and load data
      function showSection(id) {
        document.querySelectorAll('.card').forEach(function (card) {
          card.style.display = 'none';
        });
        var active = document.getElementById(id);
        if (active) active.style.display = '';
        if (typeof window.syncAdminNavState === 'function') {
          window.syncAdminNavState(id);
        }
        // Auto-load data for each section
        if (id === 'bookings') ajaxListLoad('bookings', { page: 1, per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10), search: document.getElementById('search_name_input')?.value || '' });
        if (id === 'customers') ajaxListLoad('customers', { page: 1, per_page: parseInt(document.getElementById('customers_per_page')?.value || '25', 10) });
        if (id === 'schedules') ajaxListLoad('schedules', { page: 1, per_page: parseInt(document.getElementById('schedules_per_page')?.value || '25', 10) });
        if (id === 'users') ajaxListLoad('users', { page: 1, per_page: parseInt(document.getElementById('users_per_page')?.value || '25', 10) });
        if (id === 'routes') ajaxListLoad('routes', { page: 1, per_page: parseInt(document.getElementById('routes_per_page')?.value || '25', 10) });
        if (id === 'cancellations') ajaxListLoad('cancellations', { page: 1, per_page: 25 });
        if (id === 'luggage_services' && typeof window.loadLuggageServices === 'function') window.loadLuggageServices();
        if (id === 'units') { /* Units loaded via PHP, no AJAX list load needed yet */ }
      }
      window.showSectionById = showSection;
      function updateSectionFromHash() {
        var hash = window.location.hash.replace('#', '');
        if (hash) showSection(hash);
        else showSection('dashboard');
      }
      window.addEventListener('hashchange', updateSectionFromHash);
      updateSectionFromHash();
      // Top nav click
      document.querySelectorAll('.nav a[data-target]').forEach(function (a) {
        a.onclick = function (e) {
          e.preventDefault();
          showSection(a.getAttribute('data-target'));
          window.location.hash = '#' + a.getAttribute('data-target');
        };
      });
      // More menu (desktop) moved to includes/navbar.php
    });
    async function parseAdminApiResponse(res) {
      const raw = await res.text();
      const trimmed = raw.trim();

      try {
        return JSON.parse(trimmed);
      } catch (_) {
        const jsonStart = Math.max(
          trimmed.indexOf('{') === -1 ? Number.MAX_SAFE_INTEGER : trimmed.indexOf('{'),
          0
        );
        const arrayStart = Math.max(
          trimmed.indexOf('[') === -1 ? Number.MAX_SAFE_INTEGER : trimmed.indexOf('['),
          0
        );
        const start = Math.min(jsonStart, arrayStart);

        if (Number.isFinite(start) && start !== Number.MAX_SAFE_INTEGER) {
          try {
            return JSON.parse(trimmed.slice(start));
          } catch (_) {
            // Fall through to detailed error below.
          }
        }

        const excerpt = trimmed.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 220);
        throw new Error(excerpt || 'Respon server tidak valid.');
      }
    }
    window.parseAdminApiResponse = parseAdminApiResponse;
    // AJAX list loader
    async function ajaxListLoad(target, params) {
      const spinnerWrap = document.getElementById(target + '_spinner_wrap'); if (spinnerWrap) spinnerWrap.style.display = 'flex';
      const tbody = document.getElementById(target + '_tbody'); const pagination = document.getElementById(target + '_pagination'); const info = document.getElementById(target + '_info');
      const url = new URL('admin.php', window.location.origin);
      url.searchParams.set('action', target + 'Page');

      if (params) {
        for (const key in params) {
          if (params[key] !== undefined && params[key] !== null) {
            url.searchParams.set(key, params[key]);
          }
        }
      }

      try {
        const res = await fetch(url.toString(), { credentials: 'same-origin' });
        const js = await parseAdminApiResponse(res);
        if (!js.success) { 
          if (tbody) {
            tbody.innerHTML = target === 'customers'
              ? '<tr><td colspan="6" class="customers-table-empty">Error: ' + (js.error || 'Unknown error') + '</td></tr>'
              : '<div class="small admin-grid-message admin-grid-message-error">Error: ' + (js.error || 'Unknown error') + '</div>';
          }
          return; 
        }
        if (tbody) tbody.innerHTML = js.rows;
        if (pagination) pagination.innerHTML = js.pagination;
        if (info) info.textContent = (js.total !== undefined ? ('Total: ' + js.total) : '');
        if (typeof window.updateBookingCommandSummary === 'function' && ['bookings', 'charters', 'luggage'].includes(target)) {
          window.updateBookingCommandSummary(target, js.total);
        }
        if (target === 'bookings') { attachEditBookingHandlers(); attachTableCancelHandlers(); attachTableMarkPaidHandlers(); }
        if (target === 'luggage') { attachLuggageHandlers(); }
      } catch (e) {
        if (tbody) {
          tbody.innerHTML = target === 'customers'
            ? '<tr><td colspan="6" class="customers-table-empty">Kesalahan koneksi</td></tr>'
            : '<div class="small admin-grid-message">Kesalahan koneksi</div>';
        }
      }
      finally { if (spinnerWrap) spinnerWrap.style.display = 'none'; }
    }
    // Search handlers
    let searchDebounceTimer = null;
    // Helper: determine which booking target to search
    function getActiveBookingTarget() {
      if (window.bookingDashboardState && window.bookingDashboardState.active) {
        return window.bookingDashboardState.active;
      }
      return 'bookings';
    }
    if (document.getElementById('searchBtn')) {
      document.getElementById('searchBtn').onclick = function () {
        const search = document.getElementById('search_name_input').value;
        const target = getActiveBookingTarget();
        ajaxListLoad(target, { page: 1, per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10), search: search });
      };
    }
    // Auto-search on typing with debounce
    if (document.getElementById('search_name_input')) {
      document.getElementById('search_name_input').addEventListener('input', function () {
        clearTimeout(searchDebounceTimer);
        const search = this.value;
        searchDebounceTimer = setTimeout(function () {
          const target = getActiveBookingTarget();
          ajaxListLoad(target, { page: 1, per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10), search: search });
        }, 300);
      });
    }
    if (document.getElementById('searchCustomerBtn')) {
      // Auto-search (debounce)
      document.getElementById('search_customer_name_input').addEventListener('input', function () {
        clearTimeout(searchDebounceTimer);
        const search = this.value;
        searchDebounceTimer = setTimeout(function () {
          ajaxListLoad('customers', {
            page: 1,
            per_page: parseInt(document.getElementById('customers_per_page')?.value || '25', 10),
            search: search
          });
        }, 300);
      });
      document.getElementById('searchCustomerBtn').onclick = function () {
        const search = document.getElementById('search_customer_name_input').value;
        ajaxListLoad('customers', {
          page: 1,
          per_page: parseInt(document.getElementById('customers_per_page')?.value || '25', 10),
          search: search
        });
      };
    }
    if (document.getElementById('customers_per_page')) {
      document.getElementById('customers_per_page').onchange = function () {
        ajaxListLoad('customers', {
          page: 1,
          per_page: parseInt(this.value, 10),
          search: document.getElementById('search_customer_name_input')?.value || ''
        });
      };
    }
    if (document.getElementById('bookings_per_page')) {
      document.getElementById('bookings_per_page').onchange = function () {
        const target = getActiveBookingTarget();
        ajaxListLoad(target, {
          page: 1,
          per_page: parseInt(this.value, 10),
          search: document.getElementById('search_name_input')?.value || ''
        });
      };
    }
    if (document.getElementById('routes_per_page')) {
      document.getElementById('routes_per_page').onchange = function () {
        ajaxListLoad('routes', {
          page: 1,
          per_page: parseInt(this.value, 10),
          search: document.getElementById('search_route_input')?.value || '',
          type: window.currentRouteType || 'reguler'
        });
      };
    }
    if (document.getElementById('schedules_per_page')) {
      document.getElementById('schedules_per_page').onchange = function () {
        ajaxListLoad('schedules', {
          page: 1,
          per_page: parseInt(this.value, 10)
        });
      };
    }
    if (document.getElementById('users_per_page')) {
      document.getElementById('users_per_page').onchange = function () {
        ajaxListLoad('users', {
          page: 1,
          per_page: parseInt(this.value, 10)
        });
      };
    }
    if (document.getElementById('cancellations_per_page')) {
      document.getElementById('cancellations_per_page').onchange = function () {
        ajaxListLoad('cancellations', {
          page: 1,
          per_page: parseInt(this.value, 10),
          search: document.getElementById('search_cancellations_input')?.value || ''
        });
      };
    }
    if (document.getElementById('searchRouteBtn')) {
      document.getElementById('searchRouteBtn').onclick = function () {
        const search = document.getElementById('search_route_input').value;
        ajaxListLoad('routes', {
          page: 1,
          per_page: parseInt(document.getElementById('routes_per_page')?.value || '25', 10),
          search: search,
          type: window.currentRouteType || 'reguler'
        });
      };
    }
    // Live Search for Routes
    if (document.getElementById('search_route_input')) {
      document.getElementById('search_route_input').addEventListener('input', function () {
        clearTimeout(searchDebounceTimer);
        const search = this.value;
        searchDebounceTimer = setTimeout(function () {
          ajaxListLoad('routes', {
            page: 1,
            per_page: parseInt(document.getElementById('routes_per_page')?.value || '25', 10),
            search: search,
            type: window.currentRouteType || 'reguler'
          });
        }, 300);
      });
      document.getElementById('search_route_input').addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
          clearTimeout(searchDebounceTimer);
          const search = this.value;
          ajaxListLoad('routes', {
            page: 1,
            per_page: parseInt(document.getElementById('routes_per_page')?.value || '25', 10),
            search: search,
            type: window.currentRouteType || 'reguler'
          });
        }
      });
    }
    if (document.getElementById('searchScheduleRouteBtn')) {
      document.getElementById('searchScheduleRouteBtn').onclick = function () {
        const search = document.getElementById('search_schedule_route_input').value;
        ajaxListLoad('schedules', {
          page: 1,
          per_page: parseInt(document.getElementById('schedules_per_page')?.value || '25', 10),
          search: search
        });
      };
    }
    // Pagination click handlers for all tables
    function attachPaginationHandlers() {
      document.querySelectorAll('.pagination-container .ajax-page').forEach(function (a) {
        a.onclick = function (e) {
          e.preventDefault();
          const target = a.getAttribute('data-target');
          const page = parseInt(a.getAttribute('data-page'), 10) || 1;
          let params = { page: page };
          if (target === 'bookings') {
            params.per_page = parseInt(document.getElementById('bookings_per_page')?.value || '25', 10);
            params.search = document.getElementById('search_name_input')?.value || '';
          } else if (target === 'customers') {
            params.per_page = parseInt(document.getElementById('customers_per_page')?.value || '25', 10);
            params.search = document.getElementById('search_customer_name_input')?.value || '';
          } else if (target === 'routes') {
            params.per_page = parseInt(document.getElementById('routes_per_page')?.value || '25', 10);
            params.type = window.currentRouteType || 'reguler';
            params.search = document.getElementById('search_route_input')?.value || '';
          } else if (target === 'schedules') {
            params.per_page = parseInt(document.getElementById('schedules_per_page')?.value || '25', 10);
          } else if (target === 'users') {
            params.per_page = parseInt(document.getElementById('users_per_page')?.value || '25', 10);
          } else if (target === 'cancellations') {
            params.per_page = parseInt(document.getElementById('cancellations_per_page')?.value || '25', 10);
            params.search = document.getElementById('search_cancellations_input')?.value || '';
          } else if (target === 'bookingsHistory' || target === 'chartersHistory') {
            params.per_page = parseInt(document.getElementById('bookings_per_page')?.value || '25', 10);
            params.search = document.getElementById('search_name_input')?.value || '';
          } else if (target === 'luggage') {
            params.per_page = parseInt(document.getElementById('bookings_per_page')?.value || '25', 10);
            params.search = document.getElementById('search_name_input')?.value || '';
          }
          ajaxListLoad(target, params);
        };
      });
    }

    /* ---------------- LUGGAGE HANDLERS ---------------- */
    function attachLuggageHandlers() {
      document.querySelectorAll('.luggage-action').forEach(btn => {
        btn.onclick = function (e) {
          e.preventDefault();
          const action = this.getAttribute('data-action');
          const id = this.getAttribute('data-id');
          const title = this.getAttribute('title');

          const isDanger = (action === 'cancelLuggage');
          const confirmType = isDanger ? 'danger' : 'success';
          const confirmBtnText = isDanger ? 'Ya, Batalkan' : 'Ya, Lanjutkan';

          customConfirm('Lanjutkan proses "' + title + '"?', async () => {
            const formData = new FormData();
            formData.append('id', id);

            try {
              const res = await fetch('admin.php?action=' + action, {
                method: 'POST',
                body: formData
              });

              const text = await res.text();
              console.log('Luggage Action Raw Response:', text);
              
              if (!text || text.trim() === '') {
                customAlert('Server memberikan respon kosong! Cek logs server.', 'Error Server');
                return;
              }

              let js;
              try {
                js = JSON.parse(text);
              } catch (parseErr) {
                console.error('Server output parsing error:', text);
                const firstLines = text.substring(0, 300).replace(/<[^>]*>/g, '');
                customAlert('Gagal memproses respon server. Respon mentah: ' + (firstLines || '[KOSONG]'), 'Error JSON');
                return;
              }

              if (js.success) {
                await customAlert(js.message || 'Berhasil!', 'Sukses');
                ajaxListLoad('luggage', { page: 1, per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10), search: document.getElementById('search_name_input')?.value || '' });
              } else {
                customAlert(js.error || 'Terjadi kesalahan sistem', 'Gagal');
              }
            } catch (e) {
              console.error('Luggage Action Error:', e);
              customAlert('Kesalahan koneksi atau data: ' + e.message, 'Network Error');
            }
          }, 'Konfirmasi ' + title, confirmType);
        }
      });
    }
    // Re-attach pagination handlers after each AJAX load
    const origAjaxListLoad = ajaxListLoad;
    ajaxListLoad = async function (target, params) {
      await origAjaxListLoad(target, params);
      attachPaginationHandlers();
    };
    // Initial attach for first load
    attachPaginationHandlers();
    if (document.getElementById('searchCancellationsBtn')) {
      document.getElementById('searchCancellationsBtn').onclick = function () {
        const search = document.getElementById('search_cancellations_input').value;
        ajaxListLoad('cancellations', { page: 1, per_page: parseInt(document.getElementById('cancellations_per_page')?.value || '25', 10), search: search });
      };
    }
    // Users search handler
    if (document.getElementById('searchUserBtn')) {
      document.getElementById('search_user_input')?.addEventListener('input', function () {
        clearTimeout(searchDebounceTimer);
        const search = this.value;
        searchDebounceTimer = setTimeout(function () {
          ajaxListLoad('users', {
            page: 1,
            per_page: parseInt(document.getElementById('users_per_page')?.value || '25', 10),
            search: search
          });
        }, 300);
      });
      document.getElementById('searchUserBtn').onclick = function () {
        const search = document.getElementById('search_user_input').value;
        ajaxListLoad('users', {
          page: 1,
          per_page: parseInt(document.getElementById('users_per_page')?.value || '25', 10),
          search: search
        });
      };
    }
    function formatBookingDetailDate(rawDate) {
      if (!rawDate) return '-';
      const parts = rawDate.split('-');
      if (parts.length !== 3) return rawDate;
      const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
      const monthIndex = parseInt(parts[1], 10) - 1;
      if (monthIndex < 0 || monthIndex > 11) return rawDate;
      return `${parts[2]} ${months[monthIndex]} ${parts[0]}`;
    }

    function syncBookingDetailContext() {
      const rute = document.getElementById('booking_detail_rute')?.value || '';
      const tanggal = document.getElementById('booking_detail_tanggal')?.value || '';
      const jam = document.getElementById('booking_detail_jam')?.value || '';
      const unit = document.getElementById('booking_detail_unit')?.value || '';
      const routeText = document.getElementById('booking_detail_route_text');
      const dateText = document.getElementById('booking_detail_date_text');
      const timeText = document.getElementById('booking_detail_time_text');
      const unitText = document.getElementById('booking_detail_unit_text');
      const unitBadge = document.getElementById('booking_detail_unit_text_badge');
      const helperText = document.getElementById('booking_detail_helper_text');

      if (routeText) routeText.textContent = rute || 'Belum dipilih';
      if (dateText) dateText.textContent = tanggal ? formatBookingDetailDate(tanggal) : '-';
      if (timeText) timeText.textContent = jam || '-';
      if (unitText) unitText.textContent = unit ? `Unit ${unit}` : '-';
      if (unitBadge) unitBadge.textContent = unit ? `Unit ${unit}` : 'Unit -';
      if (helperText) {
        helperText.innerHTML = rute && tanggal && jam && unit
          ? 'Menampilkan semua penumpang pada jadwal terpilih. Anda bisa salin data, ubah driver, tandai lunas, atau batalkan booking dari daftar ini.'
          : 'Pilih aksi <strong>Detail Booking List</strong> dari halaman Booking untuk menampilkan semua penumpang pada jadwal tersebut.';
      }
    }

    async function loadBookingDetailPassengers() {
      const rute = document.getElementById('booking_detail_rute')?.value || '';
      const tanggal = document.getElementById('booking_detail_tanggal')?.value || '';
      const jam = document.getElementById('booking_detail_jam')?.value || '';
      const unit = document.getElementById('booking_detail_unit')?.value || '';
      const spinner = document.getElementById('passenger_spinner_wrap');
      const list = document.getElementById('passengerList');

      syncBookingDetailContext();
      if (!list) return;

      if (spinner) spinner.style.display = 'flex';
      list.innerHTML = '<div class="admin-empty-state view-empty-state">Memuat detail booking...</div>';

      if (!rute || !tanggal || !jam || !unit) {
        list.innerHTML = '<div class="admin-empty-state view-empty-state">Belum ada jadwal yang dipilih. Buka menu Booking lalu tekan <strong>Detail Booking List</strong> pada trip yang ingin dilihat.</div>';
        if (spinner) spinner.style.display = 'none';
        return;
      }

      try {
        const url = new URL('admin.php', window.location.origin);
        url.searchParams.set('action', 'getPassengers');
        url.searchParams.set('rute', rute);
        url.searchParams.set('tanggal', tanggal);
        url.searchParams.set('jam', jam);
        url.searchParams.set('unit', unit);
        const res = await fetch(url.toString(), { credentials: 'same-origin' });
        const js = await parseAdminApiResponse(res);
        if (js.success && js.html) {
          list.innerHTML = js.html;
        } else {
          const errMsg = js.detail || js.message || js.error || 'Data penumpang tidak ditemukan.';
          list.innerHTML = '<div class="admin-empty-state view-empty-state">Tidak dapat memuat detail booking. ' + errMsg + '</div>';
        }
      } catch (e) {
        list.innerHTML = '<div class="admin-empty-state view-empty-state">Gagal memuat data penumpang. ' + (e.message || '') + '</div>';
      } finally {
        if (spinner) spinner.style.display = 'none';
      }
    }
    window.loadBookingDetailPassengers = loadBookingDetailPassengers;
    syncBookingDetailContext();
    // Optimalkan handler copy agar hanya menyalin detail penumpang yang relevan
    function attachCopyHandlers() {
      function fallbackCopy(text) {
        const temp = document.createElement('textarea');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        try {
          document.execCommand('copy');
          customAlert('Semua detail penumpang berhasil disalin!');
        } catch (e) {
          customAlert('Gagal menyalin ke clipboard.');
        }
        document.body.removeChild(temp);
      }
      if (!navigator.clipboard || typeof navigator.clipboard.writeText !== 'function') {
        document.querySelectorAll('#copyAllBtn, .copy-single').forEach(btn => {
          btn.onclick = function () {
            // Fallback manual
            if (this.id === 'copyAllBtn') {
              const list = document.getElementById('passengerList');
              const occupied = [];
              list.querySelectorAll('.seat-block').forEach(block => {
                const name = block.querySelector('.sb-val.name')?.innerText.trim() || '';
                if (name) {
                  const seat = block.querySelector('.seat-badge-num')?.innerText.replace('Kursi ', '').trim() || '';
                  const phone = block.querySelector('.sb-val.phone')?.innerText.trim() || '';
                  const pickup = block.querySelector('.sb-val.pickup')?.innerText.trim() || '';
                  const gmaps = block.querySelector('.sb-val.gmaps')?.innerText.trim() || '';
                  const pay = block.querySelector('.sb-val.pay')?.innerText.trim() || '';
                  occupied.push({ seat, name, phone, pickup, gmaps, pay });
                }
              });
              // Get departure info
              const rute = document.getElementById('booking_detail_rute')?.value || '';
              const tanggalRaw = document.getElementById('booking_detail_tanggal')?.value || '';
              const jam = document.getElementById('booking_detail_jam')?.value || '';
              // Format date
              let tanggalFormatted = tanggalRaw;
              if (tanggalRaw) {
                const d = new Date(tanggalRaw);
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                tanggalFormatted = months[d.getMonth()] + ' ' + String(d.getDate()).padStart(2, '0') + ', ' + d.getFullYear();
              }
              const jamFormatted = jam ? jam.replace(':', '.') : '';
              const totalPenumpang = occupied.length;

              // Get Driver Name
              const driverInfo = document.getElementById('departureInfoCard');
              const driverName = driverInfo ? (driverInfo.getAttribute('data-driver-name') || '-') : '-';

              // Build header
              let text = `Info Pemberangkatan\nTanggal & Jam: ${tanggalFormatted} - ${jamFormatted}\nRute: ${rute}\nTotal Penumpang: ${totalPenumpang}\nDriver: ${driverName}\n\n`;
              occupied.forEach(s => {
                text += `- Kursi: ${s.seat}\nNama: ${s.name}\nNo. HP: ${s.phone}\nTitik Jemput: ${s.pickup}\nGmaps: ${s.gmaps}\nPembayaran: ${s.pay}\n\n`;
              });

              // ADD SUMMARY TO COPY (Fallback)
              const summaryDiv = document.getElementById('passengerSummary');
              if (summaryDiv) {
                const paid = parseInt(summaryDiv.getAttribute('data-paid') || '0');
                const unpaid = parseInt(summaryDiv.getAttribute('data-unpaid') || '0');
                text += `Ringkasan Pembayaran\n`;
                text += `Sudah Lunas: Rp ${paid.toLocaleString('id-ID')}\n`;
                text += `Belum Lunas: Rp ${unpaid.toLocaleString('id-ID')}\n`;
                text += `Total Estimasi: Rp ${(paid + unpaid).toLocaleString('id-ID')}\n`;
              }

              fallbackCopy(text);
            } else {
              const block = btn.closest('.seat-block');
              if (block) {
                const seat = block.querySelector('.seat-badge-num')?.innerText.replace('Kursi ', '').trim() || '';
                const name = block.querySelector('.sb-val.name')?.innerText.trim() || '';
                const phone = block.querySelector('.sb-val.phone')?.innerText.trim() || '';
                const pickup = block.querySelector('.sb-val.pickup')?.innerText.trim() || '';
                const gmaps = block.querySelector('.sb-val.gmaps')?.innerText.trim() || '';
                const pay = block.querySelector('.sb-val.pay')?.innerText.trim() || '';
                const text = `- Kursi: ${seat}\nNama: ${name}\nNo. HP: ${phone}\nTitik Jemput: ${pickup}\nGmaps: ${gmaps}\nPembayaran: ${pay}`;
                fallbackCopy(text);
              }
            }
          };
        });
        return;
      }
      if (document.getElementById('copyAllBtn')) {
        document.getElementById('copyAllBtn').onclick = function () {
          const list = document.getElementById('passengerList');
          const occupied = [];
          list.querySelectorAll('.seat-block').forEach(block => {
            const name = block.querySelector('.sb-val.name')?.innerText.trim() || '';
            if (name) {
              const seat = block.querySelector('.seat-badge-num')?.innerText.replace('Kursi ', '').trim() || '';
              const phone = block.querySelector('.sb-val.phone')?.innerText.trim() || '';
              const pickup = block.querySelector('.sb-val.pickup')?.innerText.trim() || '';
              const gmaps = block.querySelector('.sb-val.gmaps')?.innerText.trim() || '';
              const pay = block.querySelector('.sb-val.pay')?.innerText.trim() || '';
              occupied.push({ seat, name, phone, pickup, gmaps, pay });
            }
          });
          if (occupied.length === 0) {
            customAlert('Tidak ada kursi terisi.');
            return;
          }
          // Get departure info
          const rute = document.getElementById('booking_detail_rute')?.value || '';
          const tanggalRaw = document.getElementById('booking_detail_tanggal')?.value || '';
          const jam = document.getElementById('booking_detail_jam')?.value || '';
          // Format date
          let tanggalFormatted = tanggalRaw;
          if (tanggalRaw) {
            const d = new Date(tanggalRaw);
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            tanggalFormatted = months[d.getMonth()] + ' ' + String(d.getDate()).padStart(2, '0') + ', ' + d.getFullYear();
          }
          const jamFormatted = jam ? jam.replace(':', '.') : '';
          const totalPenumpang = occupied.length;

          // Get Driver Name
          const driverInfo = document.getElementById('departureInfoCard');
          const driverName = driverInfo ? (driverInfo.getAttribute('data-driver-name') || '-') : '-';

          // Build header
          let text = `Info Pemberangkatan\nTanggal & Jam: ${tanggalFormatted} - ${jamFormatted}\nRute: ${rute}\nTotal Penumpang: ${totalPenumpang}\nDriver: ${driverName}\n\n`;
          occupied.forEach(s => {
            text += `- Kursi: ${s.seat}\nNama: ${s.name}\nNo. HP: ${s.phone}\nTitik Jemput: ${s.pickup}\nGmaps: ${s.gmaps}\nPembayaran: ${s.pay}\n\n`;
          });

          // ADD SUMMARY TO COPY
          const summaryDiv = document.getElementById('passengerSummary');
          if (summaryDiv) {
            const paid = parseInt(summaryDiv.getAttribute('data-paid') || '0');
            const unpaid = parseInt(summaryDiv.getAttribute('data-unpaid') || '0');
            text += `Ringkasan Pembayaran\n`;
            text += `Sudah Lunas: Rp ${paid.toLocaleString('id-ID')}\n`;
            text += `Belum Lunas: Rp ${unpaid.toLocaleString('id-ID')}\n`;
            text += `Total Estimasi: Rp ${(paid + unpaid).toLocaleString('id-ID')}\n`;
          }

          if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            navigator.clipboard.writeText(text).then(() => {
              customAlert('Semua detail penumpang berhasil disalin!');
            }).catch(() => fallbackCopy(text));
          } else {
            fallbackCopy(text);
          }
        };
      }
      document.querySelectorAll('.copy-single').forEach(btn => {
        btn.onclick = function (e) {
          const block = btn.closest('.seat-block');
          if (block) {
            const seat = block.querySelector('.seat-badge-num')?.innerText.replace('Kursi ', '').trim() || '';
            const name = block.querySelector('.sb-val.name')?.innerText.trim() || '';
            const phone = block.querySelector('.sb-val.phone')?.innerText.trim() || '';
            const pickup = block.querySelector('.sb-val.pickup')?.innerText.trim() || '';
            const gmaps = block.querySelector('.sb-val.gmaps')?.innerText.trim() || '';
            const pay = block.querySelector('.sb-val.pay')?.innerText.trim() || '';
            const text = `- Kursi: ${seat}\nNama: ${name}\nNo. HP: ${phone}\nTitik Jemput: ${pickup}\nGmaps: ${gmaps}\nPembayaran: ${pay}`;
            navigator.clipboard.writeText(text).then(() => {
              customAlert('Detail kursi berhasil disalin!');
            }, () => {
              fallbackCopy(text);
            });
          }
        };
      });
    }
    async function refreshBookingDetailInteractiveState() {
      attachCopyHandlers();
      attachCancelHandlers();
      attachSeatLayoutMarkPaidHandlers();
    }
    const originalLoadBookingDetailPassengers = window.loadBookingDetailPassengers;
    window.loadBookingDetailPassengers = async function () {
      if (typeof originalLoadBookingDetailPassengers === 'function') {
        await originalLoadBookingDetailPassengers();
      }
      await refreshBookingDetailInteractiveState();
    };
    attachCopyHandlers();
    attachCancelHandlers();
    attachSeatLayoutMarkPaidHandlers();
    if (document.getElementById('closeCopyAllModal')) {
      document.getElementById('closeCopyAllModal').onclick = function () {
        const modal = document.getElementById('copyAllModal');
        modal.classList.remove('show');
        setTimeout(() => { modal.style.display = 'none'; }, 300);
      };
    }
    // Close modal when clicking outside
    document.getElementById('copyAllModal').onclick = function (e) {
      if (e.target === this) {
        this.classList.remove('show');
        setTimeout(() => { this.style.display = 'none'; }, 300);
      }
    }
    function attachCancelHandlers() {
      document.querySelectorAll('.cancel-btn').forEach(btn => {
        btn.onclick = function () {
          customConfirm('Batalkan penumpang ini?', () => {
            const id = this.getAttribute('data-id');
            fetch('admin.php?cancel_booking=' + id, { method: 'GET', headers: { 'Accept': 'application/json' } }).then(res => res.json()).then(js => {
              if (js.success) {
                customAlert('Penumpang dibatalkan.').then(() => {
                  if (typeof window.loadBookingDetailPassengers === 'function') {
                    window.loadBookingDetailPassengers();
                  }
                });
              } else {
                customAlert('Gagal membatalkan: ' + (js.error || 'unknown'));
              }
            }).catch(e => customAlert('Error: ' + e));
          }, 'Konfirmasi Pembatalan', 'danger');
        };
      });
    }
    function attachTableCancelHandlers() {
      document.querySelectorAll('.cancel-link').forEach(btn => {
        btn.onclick = function (e) {
          e.preventDefault();
          const id = this.getAttribute('data-id');
          const name = this.getAttribute('data-name') || '-';
          const phone = this.getAttribute('data-phone') || '-';
          const seat = this.getAttribute('data-seat') || '-';
          const tanggal = this.getAttribute('data-tanggal') || '-';
          const jam = this.getAttribute('data-jam') || '-';

          const confirmMsg = `Batalkan booking ini?\n\nTanggal: ${tanggal} ${jam}\nNama: ${name}\nNo. HP: ${phone}\nKursi: ${seat}`;

          customConfirm(confirmMsg, () => {
            fetch('admin.php?cancel_booking=' + id, { method: 'GET', headers: { 'Accept': 'application/json' } }).then(res => res.json()).then(js => {
              if (js.success) {
                customAlert('Booking dibatalkan.').then(() => {
                  ajaxListLoad('bookings', { page: 1, per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10), search: document.getElementById('search_name_input')?.value || '' });
                });
              } else {
                customAlert('Gagal membatalkan: ' + (js.error || 'unknown'));
              }
            }).catch(e => customAlert('Error: ' + e));
          }, 'Konfirmasi Pembatalan', 'danger');
        };
      });
    }
    function attachTableMarkPaidHandlers() {
      document.querySelectorAll('.mark-paid').forEach(btn => {
        btn.onclick = function (e) {
          e.preventDefault();
          customConfirm('Mark as Lunas?', () => {
            const id = this.getAttribute('data-id');
            fetch('admin.php?mark_paid=' + id, { method: 'GET', headers: { 'Accept': 'application/json' } }).then(res => res.json()).then(js => {
              if (js.success) {
                customAlert('Status pembayaran diubah ke Lunas.').then(() => {
                  ajaxListLoad('bookings', { page: 1, per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10), search: document.getElementById('search_name_input')?.value || '' });
                });
              } else {
                customAlert('Gagal mengubah status: ' + (js.error || 'unknown'));
              }
            }).catch(e => customAlert('Error: ' + e));
          }, 'Pembayaran Penumpang', 'success');
        };
      });
    }
    function attachSeatLayoutMarkPaidHandlers() {
      document.querySelectorAll('.mark-paid-seat').forEach(btn => {
        btn.onclick = function (e) {
          e.preventDefault();
          customConfirm('Mark as Lunas?', () => {
            const id = this.getAttribute('data-id');
            fetch('admin.php?mark_paid=' + id, { method: 'GET', headers: { 'Accept': 'application/json' } }).then(res => res.json()).then(js => {
              if (js.success) {
                customAlert('Status pembayaran diubah ke Lunas.').then(() => {
                  if (typeof window.loadBookingDetailPassengers === 'function') {
                    window.loadBookingDetailPassengers();
                  }
                });
              } else {
                customAlert('Gagal mengubah status: ' + (js.error || 'unknown'));
              }
            }).catch(e => customAlert('Error: ' + e));
          }, 'Pembayaran Penumpang', 'success');
        };
      });
    }
    function updatePaymentRadioState(radios, selectedValue = '') {
      radios.forEach(radio => {
        const label = radio.closest('.pay-radio-label');
        if (!label) return;
        const isSelected = radio.value.toLowerCase() === selectedValue.toLowerCase();
        radio.checked = isSelected;
        label.classList.toggle('is-selected', isSelected);
      });
    }

    function attachEditBookingHandlers() {
      document.querySelectorAll('.edit-booking-btn').forEach(btn => {
        btn.onclick = async function (e) {
          e.preventDefault();
          const id = this.getAttribute('data-id');
          const unit = this.getAttribute('data-unit') || '1';
          const rute = this.getAttribute('data-rute');
          const tanggal = this.getAttribute('data-tanggal');
          const jam = this.getAttribute('data-jam');
          const seat = this.getAttribute('data-seat');
          const pickup = this.getAttribute('data-pickup');
          const segmentId = this.getAttribute('data-segment-id') || '0';
          const price = this.getAttribute('data-price') || '0';
          const discount = this.getAttribute('data-discount') || '0';
          const pembayaran = this.closest('.admin-card-compact') ? this.closest('.admin-card-compact').querySelector('.status-tag')?.textContent.trim() : 'Belum Lunas';

          document.getElementById('edit_booking_id').value = id;
          document.getElementById('edit_seat').value = seat;
          document.getElementById('edit_pickup').value = pickup;

          // Fetch Available Units from Schedule
          const unitSelect = document.getElementById('edit_unit');
          if (unitSelect) {
            unitSelect.innerHTML = '<option value="">Memuat...</option>';
            try {
              const res = await fetch(`admin.php?action=getAvailableUnits&rute=${encodeURIComponent(rute)}&tanggal=${tanggal}&jam=${jam}`);
              const js = await res.json();
              if (js.success) {
                unitSelect.innerHTML = '';
                const maxUnits = js.units || 1;
                for (let i = 1; i <= maxUnits; i++) {
                  const opt = document.createElement('option');
                  opt.value = i;
                  opt.textContent = 'Unit ' + i;
                  if (String(i) === String(unit)) opt.selected = true;
                  unitSelect.appendChild(opt);
                }
              }
            } catch (err) {
              unitSelect.innerHTML = `<option value="${unit}">Unit ${unit}</option>`;
            }
          }

          // Fetch Available & Occupied Seats
          async function updateSeats(curUnit, curSeat) {
            const seatSelect = document.getElementById('edit_seat');
            if (!seatSelect) return;
            seatSelect.innerHTML = '<option value="">Memuat kursi...</option>';
            try {
              const res = await fetch(`admin.php?action=getScheduleSeats&rute=${encodeURIComponent(rute)}&tanggal=${tanggal}&jam=${jam}&unit=${curUnit}`);
              const js = await res.json();
              if (js.success) {
                seatSelect.innerHTML = '<option value="">Pilih Kursi</option>';
                const occupied = js.occupied || [];
                const layout = js.layout || [];

                // Flat list of seat labels from layout
                let seats = [];
                layout.forEach(row => {
                  row.forEach(cell => {
                    if (cell && cell.type === 'seat' && cell.label) {
                      seats.push(cell.label);
                    }
                  });
                });

                if (seats.length === 0) {
                  // Fallback to 1-8 if layout missing
                  for (let i = 1; i <= 8; i++) seats.push(String(i));
                }

                seats.forEach(s => {
                  const isTaken = occupied.includes(String(s));
                  const isCurrent = String(s) === String(curSeat);
                  if (!isTaken || isCurrent) {
                    const opt = document.createElement('option');
                    opt.value = s;
                    opt.textContent = 'Kursi ' + s + (isCurrent ? ' (Sekarang)' : '');
                    if (isCurrent) opt.selected = true;
                    seatSelect.appendChild(opt);
                  }
                });
              }
            } catch (err) {
              seatSelect.innerHTML = `<option value="${curSeat}">${curSeat}</option>`;
            }
          }

          if (unitSelect) {
            unitSelect.onchange = function () {
              updateSeats(this.value, seat);
            };
          }
          updateSeats(unit, seat);

          // Handle Segment
          const segSelect = document.getElementById('edit_segment_id');
          if (segSelect) {
            segSelect.value = segmentId;
            // Trigger display update
            if (document.getElementById('edit_price')) document.getElementById('edit_price').value = price;
            if (document.getElementById('edit_price_display')) document.getElementById('edit_price_display').value = price;
          }

          // Handle Discount
          if (document.getElementById('edit_discount')) document.getElementById('edit_discount').value = discount;

          // Handle Payment Radio UI
          const radios = document.getElementsByName('edit_pembayaran');
          updatePaymentRadioState(radios, pembayaran || '');

          // Radio click events for UI
          radios.forEach(r => {
            const label = r.closest('.pay-radio-label');
            if (!label || label.dataset.bound === '1') return;
            label.dataset.bound = '1';
            label.onclick = function () {
              updatePaymentRadioState(radios, r.value);
            };
          });

          const modal = document.getElementById('editBookingModal');
          modal.style.display = 'flex';
          setTimeout(() => modal.classList.add('show'), 10);
        };
      });
    }
    if (document.getElementById('closeEditBookingModal')) {
      document.getElementById('closeEditBookingModal').onclick = function () {
        const modal = document.getElementById('editBookingModal');
        modal.classList.remove('show');
        setTimeout(() => { modal.style.display = 'none'; }, 300);
      };
    }
    if (document.getElementById('editBookingModal')) {
      document.getElementById('editBookingModal').onclick = function (e) {
        if (e.target === this) {
          this.classList.remove('show');
          setTimeout(() => { this.style.display = 'none'; }, 300);
        }
      };
    };

    // Auto-update price when segment changes in Edit Booking
    if (document.getElementById('edit_segment_id')) {
      document.getElementById('edit_segment_id').addEventListener('change', function () {
        const option = this.options[this.selectedIndex];
        const price = option.getAttribute('data-price') || '0';
        if (document.getElementById('edit_price')) document.getElementById('edit_price').value = price;
        if (document.getElementById('edit_price_display')) document.getElementById('edit_price_display').value = price;
      });
    }
    // ========== CHARTER FEATURE REMOVED ==========
    
    // Edit Booking Form Validation
    if (document.getElementById('editBookingForm')) {
      document.getElementById('editBookingForm').onsubmit = function (e) {
        let missing = [];
        if (!document.getElementById('edit_unit').value) missing.push('Unit');
        if (!document.getElementById('edit_seat').value) missing.push('Nomor Kursi');
        
        let payRadios = document.getElementsByName('edit_pembayaran');
        let paySelected = false;
        for (let r of payRadios) { if (r.checked) paySelected = true; }
        if (!paySelected) missing.push('Status Pembayaran');

        let msgDiv = document.getElementById('editBookingErrorMsg');
        if (missing.length > 0) {
          e.preventDefault();
          msgDiv.innerHTML = 'Mohon lengkapi field berikut: ' + missing.join(', ');
          msgDiv.style.display = 'block';
        } else {
          msgDiv.style.display = 'none';
        }
      };
    }

    // Save Driver Assignment
    window.saveDriverAssignment = async function (rute, tanggal, jam, unit) {
      const driverId = document.getElementById('driverSelect').value;
      const driverName = document.getElementById('driverSelect').options[document.getElementById('driverSelect').selectedIndex].text;

      try {
        const formData = new FormData();
        formData.append('rute', rute);
        formData.append('tanggal', tanggal);
        formData.append('jam', jam);
        formData.append('unit', unit);
        formData.append('driver_id', driverId);

        const res = await fetch('admin.php?action=assignDriver', {
          method: 'POST',
          body: formData
        });
        const js = await res.json();

        if (js.success) {
          // Update UI
          document.getElementById('driverNameText').textContent = js.driver_name;
          document.getElementById('departureInfoCard').setAttribute('data-driver-name', js.driver_name);

          // Toggleba ck to view mode
          document.getElementById('driverEdit').style.display = 'none';
          document.getElementById('driverDisplay').style.display = 'flex';

        } else {
          customAlert('Gagal update driver: ' + (js.error || 'unknown'), 'Gagal');
        }
      } catch (e) {
        customAlert('Error: ' + e, 'Network Error');
      }

    };
  </script>
  <style>
    /* Responsive styles moved to includes/navbar.php */
  </style>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"></script>
</body>

</html>
<?php endif; // End of conditional HTML rendering for non-AJAX requests
?>

