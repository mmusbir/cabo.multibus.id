<?php

if (ob_get_level() === 0) {
  ob_start();
}

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
require_once 'config/activity_log.php';
require_once 'config/perf_log.php';
require_once 'Router.php';

// Check Auth immediately BEFORE any HTML output
$auth = null;
if (!isset($_REQUEST['action'])) {
  $auth = requireAdminAuth();
  // ==================== DATABASE MIGRATION (CACHED) ====================
  // Schema is stable — only run migration if not done yet.
  // Migration version is cached in the `settings` table.
  $migVersion = '0';
  try {
    $migStmt = $conn->query("SELECT value FROM settings WHERE key='migration_version' LIMIT 1");
    $migVersion = $migStmt ? ($migStmt->fetchColumn() ?: '0') : '0';
  } catch (PDOException $e) {
    $migVersion = '0'; // Table doesn't exist yet — must run migration
  }
  if ((int) $migVersion < 7) {
    require_once 'db-migrate.php';
    try {
      $conn->exec("INSERT INTO settings (key, value) VALUES ('migration_version', '7') ON CONFLICT (key) DO UPDATE SET value='7'");
    } catch (PDOException $e) { /* silent */ }
  }
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

function renderAdminSectionFragmentFile($file) {
  global $conn;

  if (!is_file($file)) {
    return '';
  }

  $fragmentData = [];
  $basename = basename($file);

  if ($basename === 'customers.php') {
    $fragmentData['import_msg'] = $_SESSION['import_msg'] ?? '';
    $fragmentData['edit_customer'] = [];
    if (isset($conn) && isset($_GET['edit_customer'])) {
      $editId = intval($_GET['edit_customer']);
      if ($editId > 0) {
        $editStmt = $conn->prepare("SELECT * FROM customers WHERE id=? LIMIT 1");
        $editStmt->execute([$editId]);
        $fragmentData['edit_customer'] = $editStmt->fetch(PDO::FETCH_ASSOC) ?: [];
      }
    }
  }

  if ($basename === 'routes_carter.php') {
    $fragmentData['edit_carter'] = [];
    if (isset($conn) && isset($_GET['edit_carter'])) {
      $editId = intval($_GET['edit_carter']);
      if ($editId > 0) {
        $editStmt = $conn->prepare("SELECT * FROM master_carter WHERE id=? LIMIT 1");
        $editStmt->execute([$editId]);
        $fragmentData['edit_carter'] = $editStmt->fetch(PDO::FETCH_ASSOC) ?: [];
      }
    }
  }


  if ($basename === 'customer_bagasi.php') {
    $fragmentData['edit_customer_bagasi'] = [];
    if (isset($conn) && isset($_GET['edit_customer_bagasi'])) {
      $editId = intval($_GET['edit_customer_bagasi']);
      if ($editId > 0) {
        $editStmt = $conn->prepare("SELECT * FROM customer_bagasi WHERE id=? LIMIT 1");
        $editStmt->execute([$editId]);
        $fragmentData['edit_customer_bagasi'] = $editStmt->fetch(PDO::FETCH_ASSOC) ?: [];
      }
    }
  }

  if ($basename === 'schedules.php') {
    $fragmentData['routes'] = [];
    $fragmentData['units'] = [];

    if (isset($conn)) {
      $routeStmt = $conn->query("SELECT id, name FROM routes ORDER BY id");
      if ($routeStmt) {
        $fragmentData['routes'] = $routeStmt->fetchAll(PDO::FETCH_ASSOC);
      }

      $unitStmt = $conn->query("SELECT * FROM units ORDER BY id DESC");
      if ($unitStmt) {
        $fragmentData['units'] = $unitStmt->fetchAll(PDO::FETCH_ASSOC);
      }
    }
  }

  if ($basename === 'units.php') {
    $fragmentData['units'] = [];
    $fragmentData['edit_unit'] = [];

    if (isset($conn)) {
      $unitStmt = $conn->query("SELECT * FROM units ORDER BY id DESC");
      if ($unitStmt) {
        $fragmentData['units'] = $unitStmt->fetchAll(PDO::FETCH_ASSOC);
      }

      if (isset($_GET['edit_unit'])) {
        $editId = intval($_GET['edit_unit']);
        if ($editId > 0) {
          $editStmt = $conn->prepare("SELECT * FROM units WHERE id=? LIMIT 1");
          $editStmt->execute([$editId]);
          $fragmentData['edit_unit'] = $editStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        }
      }
    }
  }

  ob_start();
  extract($GLOBALS, EXTR_SKIP);
  extract($fragmentData, EXTR_OVERWRITE);
  include $file;
  return ob_get_clean();
}

function renderAdminSectionSkeleton($type = 'default') {
  echo '<div class="admin-section-skeleton admin-section-skeleton-' . htmlspecialchars($type) . '" aria-hidden="true">';
  echo '<div class="admin-section-skeleton-head">';
  echo '<span class="admin-section-skeleton-pill"></span>';
  echo '<span class="admin-section-skeleton-pill admin-section-skeleton-pill-short"></span>';
  echo '</div>';

  if ($type === 'booking') {
    echo '<div class="admin-section-skeleton-toolbar admin-section-skeleton-toolbar-booking">';
    echo '<span class="admin-section-skeleton-button"></span>';
    echo '<span class="admin-section-skeleton-field admin-section-skeleton-field-search"></span>';
    echo '<span class="admin-section-skeleton-field admin-section-skeleton-field-short"></span>';
    echo '<span class="admin-section-skeleton-icon-btn"></span>';
    echo '</div>';
    echo '<div class="admin-section-skeleton-subbar">';
    echo '<span class="admin-section-skeleton-chip"></span>';
    echo '<span class="admin-section-skeleton-chip"></span>';
    echo '<span class="admin-section-skeleton-chip admin-section-skeleton-chip-wide"></span>';
    echo '</div>';
    echo '<div class="admin-section-skeleton-list">';
    echo '<div class="admin-section-skeleton-trip-card">';
    echo '<div class="admin-section-skeleton-trip-time"></div>';
    echo '<div class="admin-section-skeleton-trip-content">';
    echo '<div class="admin-section-skeleton-trip-meta"></div>';
    echo '<div class="admin-section-skeleton-trip-title"></div>';
    echo '<div class="admin-section-skeleton-trip-line"></div>';
    echo '<div class="admin-section-skeleton-trip-line admin-section-skeleton-trip-line-short"></div>';
    echo '<div class="admin-section-skeleton-trip-actions">';
    echo '<span class="admin-section-skeleton-mini-btn"></span>';
    echo '<span class="admin-section-skeleton-mini-btn"></span>';
    echo '<span class="admin-section-skeleton-mini-btn admin-section-skeleton-mini-btn-primary"></span>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '<div class="admin-section-skeleton-trip-card">';
    echo '<div class="admin-section-skeleton-trip-time"></div>';
    echo '<div class="admin-section-skeleton-trip-content">';
    echo '<div class="admin-section-skeleton-trip-meta"></div>';
    echo '<div class="admin-section-skeleton-trip-title"></div>';
    echo '<div class="admin-section-skeleton-trip-line"></div>';
    echo '<div class="admin-section-skeleton-trip-line admin-section-skeleton-trip-line-short"></div>';
    echo '<div class="admin-section-skeleton-trip-actions">';
    echo '<span class="admin-section-skeleton-mini-btn"></span>';
    echo '<span class="admin-section-skeleton-mini-btn"></span>';
    echo '<span class="admin-section-skeleton-mini-btn admin-section-skeleton-mini-btn-primary"></span>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    return;
  }

  if ($type === 'charter') {
    echo '<div class="admin-section-skeleton-toolbar">';
    echo '<span class="admin-section-skeleton-chip"></span>';
    echo '<span class="admin-section-skeleton-chip"></span>';
    echo '<span class="admin-section-skeleton-chip"></span>';
    echo '</div>';
    echo '<div class="admin-section-skeleton-hero admin-section-skeleton-hero-tall"></div>';
    echo '<div class="admin-section-skeleton-grid admin-section-skeleton-grid-2">';
    echo '<div class="admin-section-skeleton-card admin-section-skeleton-card-wide"></div>';
    echo '<div class="admin-section-skeleton-card"></div>';
    echo '</div>';
    echo '</div>';
    return;
  }

  if ($type === 'logs') {
    echo '<div class="admin-section-skeleton-toolbar">';
    echo '<span class="admin-section-skeleton-chip"></span>';
    echo '<span class="admin-section-skeleton-field"></span>';
    echo '<span class="admin-section-skeleton-field admin-section-skeleton-field-short"></span>';
    echo '</div>';
    echo '<div class="admin-section-skeleton-table admin-section-skeleton-table-detailed">';
    echo '<div class="admin-section-skeleton-table-head admin-section-skeleton-table-head-detailed">';
    echo '<span class="admin-section-skeleton-table-col admin-section-skeleton-table-col-time"></span>';
    echo '<span class="admin-section-skeleton-table-col admin-section-skeleton-table-col-source"></span>';
    echo '<span class="admin-section-skeleton-table-col admin-section-skeleton-table-col-category"></span>';
    echo '<span class="admin-section-skeleton-table-col admin-section-skeleton-table-col-action"></span>';
    echo '<span class="admin-section-skeleton-table-col admin-section-skeleton-table-col-summary"></span>';
    echo '<span class="admin-section-skeleton-table-col admin-section-skeleton-table-col-actor"></span>';
    echo '</div>';
    for ($i = 0; $i < 4; $i++) {
      echo '<div class="admin-section-skeleton-table-detail-row">';
      echo '<span class="admin-section-skeleton-table-cell time"></span>';
      echo '<span class="admin-section-skeleton-table-cell source"></span>';
      echo '<span class="admin-section-skeleton-table-cell category"></span>';
      echo '<span class="admin-section-skeleton-table-cell action"></span>';
      echo '<span class="admin-section-skeleton-table-cell summary"></span>';
      echo '<span class="admin-section-skeleton-table-cell actor"></span>';
      echo '</div>';
    }
    echo '</div>';
    echo '</div>';
    return;
  }

  echo '<div class="admin-section-skeleton-hero"></div>';
  echo '<div class="admin-section-skeleton-grid">';
  echo '<div class="admin-section-skeleton-card"></div>';
  echo '<div class="admin-section-skeleton-card"></div>';
  echo '<div class="admin-section-skeleton-card"></div>';
  echo '</div>';
  echo '</div>';
}

function renderAdminSectionSlot($sectionId) {
  $safeId = preg_replace('/[^a-z0-9\-_]/i', '', (string) $sectionId);
  $skeletonMap = [
    'bookings' => 'booking',
    'booking-detail' => 'booking',
    'charter-create' => 'charter',
    'luggage' => 'charter',
    'cancellations' => 'logs',
    'reports' => 'logs',
    'customers' => 'logs',
    'routes' => 'logs',
    'routes_carter' => 'logs',
    'schedules' => 'logs',
    'drivers' => 'logs',
    'segments' => 'logs',
    'users' => 'logs',
    'units' => 'logs',
    'luggage_services' => 'logs',
    'customer_bagasi' => 'logs',
    'customer_charter' => 'logs'
  ];
  $skeletonType = $skeletonMap[$safeId] ?? 'default';
  echo '<div id="section-slot-' . htmlspecialchars($safeId) . '" class="admin-section-slot" data-section-slot="' . htmlspecialchars($safeId) . '" data-loaded="0">';
  echo '<section id="' . htmlspecialchars($safeId) . '" class="card admin-lazy-section" style="display:none;">';
  renderAdminSectionSkeleton($skeletonType);
  echo '</section>';
  echo '</div>';
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
    
    $router->get('dashboardData', function () use ($ajax_dir) {
      include $ajax_dir . 'dashboard_data.php';
    });
    
    $router->get('routesPage', function () use ($ajax_dir) {
      include $ajax_dir . 'routes.php';
    });
    
    $router->get('bookingsPage', function () use ($ajax_dir) {
      include $ajax_dir . 'bookings.php';
    });

    $router->get('getSectionFragment', function () {
      $section = preg_replace('/[^a-z0-9\-_]/i', '', (string) ($_GET['section'] ?? ''));
      $map = [
        'bookings' => __DIR__ . '/includes/bookings.php',
        'charter-create' => __DIR__ . '/includes/charter_create.php',
        'luggage-create' => __DIR__ . '/includes/luggage_create.php',
        'customers' => __DIR__ . '/includes/customers.php',
        'routes' => __DIR__ . '/includes/routes.php',
        'routes_carter' => __DIR__ . '/includes/routes_carter.php',
        'schedules' => __DIR__ . '/includes/schedules.php',
        'drivers' => __DIR__ . '/includes/drivers.php',
        'segments' => __DIR__ . '/includes/segments.php',
        'users' => __DIR__ . '/includes/users.php',
        'units' => __DIR__ . '/includes/units.php',
        'booking-detail' => __DIR__ . '/includes/booking_detail.php',
        'cancellations' => __DIR__ . '/includes/cancellations.php',
        'reports' => __DIR__ . '/includes/reports.php',
        'luggage_services' => __DIR__ . '/includes/luggage_services.php',
        'customer_bagasi' => __DIR__ . '/includes/customer_bagasi.php',
        'customer_charter' => __DIR__ . '/includes/customer_charter.php',
        'luggage' => __DIR__ . '/includes/luggage.php',
      ];

      if ($section === '' || !isset($map[$section])) {
        echo json_encode(['success' => false, 'error' => 'section_not_found']);
        return;
      }

      $html = renderAdminSectionFragmentFile($map[$section]);
      echo json_encode(['success' => true, 'html' => $html]);
    });
    
    $router->any('routes_crud', function () use ($ajax_dir) {
      include $ajax_dir . 'routes_crud.php';
    });

    $router->get('routes_carterPage', function () use ($ajax_dir) {
      $_GET['type'] = 'carter';
      include $ajax_dir . 'routes.php';
    });

    $router->get('customersPage', function () use ($ajax_dir) {
      include $ajax_dir . 'customers.php';
    });

    $router->get('customers', function () use ($ajax_dir) {
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
    
    $router->get('luggage_servicesPage', function () use ($ajax_dir) {
      include $ajax_dir . 'luggage_services_page.php';
    });
    
    $router->get('luggagePriceMappingPage', function () use ($ajax_dir) {
      include $ajax_dir . 'luggage_service_crud.php';
    });
    
    $router->any('luggageServiceCRUD', function () use ($ajax_dir) {
      include $ajax_dir . 'luggage_service_crud.php';
    });
    
    $router->get('customer_bagasiPage', function () use ($ajax_dir) {
      include $ajax_dir . 'customer_bagasi_page.php';
    });

    $router->get('customer_bagasi', function () use ($ajax_dir) {
      include $ajax_dir . 'customer_bagasi_page.php';
    });

    $router->any('customer_crud', function () use ($ajax_dir) {
      include $ajax_dir . 'customer_crud.php';
    });

    $router->any('customer_bagasi_crud', function () use ($ajax_dir) {
      include $ajax_dir . 'customer_bagasi_crud.php';
    });

    $router->get('customer_charterPage', function () use ($ajax_dir) {
      include $ajax_dir . 'customer_charter_page.php';
    });

    $router->get('customer_charter', function () use ($ajax_dir) {
      include $ajax_dir . 'customer_charter_page.php';
    });

    $router->any('customer_charterCRUD', function () use ($ajax_dir) {
      include $ajax_dir . 'customer_charter_crud.php';
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

    $router->any('create_charter', function () use ($ajax_dir) {
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
    
    $router->post('inputLuggageRaw', function () use ($ajax_dir) {
      include $ajax_dir . 'luggage_actions.php';
    });
    
    $router->get('getTrackingLogs', function () use ($ajax_dir) {
      include $ajax_dir . 'luggage_actions.php';
    });
    
    $router->get('getLuggageFormData', function () use ($conn) {
      header('Content-Type: application/json');
      try {
        $services = $conn->query("SELECT id, name, price FROM luggage_services ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $routes = $conn->query("SELECT id, name FROM routes ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $mapping = $conn->query("SELECT rute_id, layanan_id, harga FROM harga_bagasi")->fetchAll(PDO::FETCH_ASSOC);
        $customers = $conn->query("SELECT id, nama, no_hp, alamat, tipe FROM customer_bagasi ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
          'success' => true,
          'services' => $services,
          'routes' => $routes,
          'mapping' => $mapping,
          'customers' => $customers
        ]);
      } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
      }
      exit;
    });
    
    // Clear any stray output (whitespace, BOM, notices) captured since ob_start()
    if (ob_get_level() > 0) ob_end_clean();
    
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

// Fetch All Segments for Dropdowns — lazy loaded
$globalSegments = null;
function getGlobalSegments() {
  global $conn, $globalSegments;
  if ($globalSegments === null) {
    $globalSegments = [];
    $resSeg = $conn->query("SELECT id, rute, harga FROM segments ORDER BY rute ASC");
    while ($rs = $resSeg->fetch()) $globalSegments[] = $rs;
  }
  return $globalSegments;
}

// ========================================
// NON-AJAX POST HANDLERS (CRUD, IMPORT, CANCEL)
// ========================================
// 
// NOTE: Handlers di bawah ini masih menggunakan $_POST checks
// Jika ingin full routing, bisa dimigrasikan ke POST routes juga
// Untuk sekarang, kami preserve original logic untuk stability

if (isset($_POST['save_route'])) {
  $route_id = isset($_POST['route_id']) ? intval($_POST['route_id']) : 0;
  $type = isset($_POST['route_type']) && $_POST['route_type'] === 'carter' ? 'carter' : 'reguler';
  $actor = activity_log_current_actor($auth ?? null);

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
        $oldStmt = $conn->prepare("SELECT name, origin, destination FROM master_carter WHERE id=? LIMIT 1");
        $oldStmt->execute([$route_id]);
        $oldRoute = $oldStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $stmt = $conn->prepare("UPDATE master_carter SET name=?, origin=?, destination=?, duration=?, rental_price=?, bop_price=?, notes=? WHERE id=?");
        $stmt->execute([$name, $origin, $destination, $duration, $rental, $bop, $notes, $route_id]);
        activity_log_write(
          $conn,
          'settings',
          'master_carter',
          $route_id,
          'update',
          'Master carter diperbarui: ' . $name,
          'Sebelumnya: ' . trim(($oldRoute['name'] ?? '-') . ' | ' . ($oldRoute['origin'] ?? '-') . ' -> ' . ($oldRoute['destination'] ?? '-')),
          $actor
        );
      } else {
        $stmt = $conn->prepare("INSERT INTO master_carter(name, origin, destination, duration, rental_price, bop_price, notes) VALUES(?,?,?,?,?,?,?) ON CONFLICT (origin, destination, duration) DO NOTHING");
        $stmt->execute([$name, $origin, $destination, $duration, $rental, $bop, $notes]);
        if ($stmt->rowCount() > 0) {
          activity_log_write($conn, 'settings', 'master_carter', $conn->lastInsertId(), 'create', 'Master carter ditambahkan: ' . $name, $origin . ' -> ' . $destination, $actor);
        }
      }
    }
    header('Location: admin.php#routes_carter');
    exit;
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
        activity_log_write($conn, 'settings', 'route', $route_id, 'update', 'Rute diperbarui: ' . $name, 'Sebelumnya: ' . ($oldName ?: '-'), $actor);
        
        if ($oldName && $oldName !== $name) {
          $conn->prepare("UPDATE bookings SET rute=? WHERE rute=?")->execute([$name, $oldName]);
          $conn->prepare("UPDATE schedules SET rute=? WHERE rute=?")->execute([$name, $oldName]);
        }
      } else {
        $stmt = $conn->prepare("INSERT INTO routes(name, origin, destination) VALUES(?,?,?)");
        $stmt->execute([$name, $origin, $destination]);
        if ($stmt->rowCount() > 0) {
          activity_log_write($conn, 'settings', 'route', $conn->lastInsertId(), 'create', 'Rute ditambahkan: ' . $name, $origin . ' -> ' . $destination, $actor);
        }
      }
    }
    header('Location: admin.php#routes');
    exit;
  }
}
if (isset($_GET['delete_route'])) {
  $id = intval($_GET['delete_route']);
  $actor = activity_log_current_actor($auth ?? null);
  $stmtInfo = $conn->prepare("SELECT name FROM routes WHERE id=? LIMIT 1");
  $stmtInfo->execute([$id]);
  $routeName = (string) ($stmtInfo->fetchColumn() ?: '');
  $stmt = $conn->prepare("DELETE FROM routes WHERE id=?");
  $stmt->execute([$id]);
  if ($stmt->rowCount() > 0) {
    activity_log_write($conn, 'settings', 'route', $id, 'delete', 'Rute dihapus: ' . ($routeName ?: ('ID ' . $id)), '', $actor);
  }
  header('Location: admin.php#routes');
  exit;
}
if (isset($_GET['delete_carter'])) {
  $id = intval($_GET['delete_carter']);
  $actor = activity_log_current_actor($auth ?? null);
  $stmtInfo = $conn->prepare("SELECT name FROM master_carter WHERE id=? LIMIT 1");
  $stmtInfo->execute([$id]);
  $routeName = (string) ($stmtInfo->fetchColumn() ?: '');
  $stmt = $conn->prepare("DELETE FROM master_carter WHERE id=?");
  $stmt->execute([$id]);
  if ($stmt->rowCount() > 0) {
    activity_log_write($conn, 'settings', 'master_carter', $id, 'delete', 'Master carter dihapus: ' . ($routeName ?: ('ID ' . $id)), '', $actor);
  }
  header('Location: admin.php#routes_carter');
  exit;
}

/* SCHEDULES */
if (isset($_POST['save_schedule'])) {
  $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
  $actor = activity_log_current_actor($auth ?? null);
  $rute = trim($_POST['sch_rute'] ?? '');
  $dow = intval($_POST['sch_dow'] ?? 0);
  $jam = $_POST['sch_jam'] ?? '';
  $units = intval($_POST['sch_units'] ?? 1);
  $seats = intval($_POST['sch_seats'] ?? 8);
  $unit_id = isset($_POST['sch_unit_id']) && $_POST['sch_unit_id'] !== '' ? intval($_POST['sch_unit_id']) : null;
  if ($rute && $jam) {
    if ($schedule_id > 0) {
      $oldStmt = $conn->prepare("SELECT rute, jam, units, seats FROM schedules WHERE id=? LIMIT 1");
      $oldStmt->execute([$schedule_id]);
      $oldSchedule = $oldStmt->fetch(PDO::FETCH_ASSOC) ?: [];
      $stmt = $conn->prepare("UPDATE schedules SET rute=?, dow=?, jam=?, units=?, seats=?, unit_id=? WHERE id=?");
      $stmt->execute([$rute, $dow, $jam, $units, $seats, $unit_id, $schedule_id]);
      activity_log_write($conn, 'settings', 'schedule', $schedule_id, 'update', 'Jadwal diperbarui: ' . $rute . ' @ ' . $jam, 'Sebelumnya: ' . ($oldSchedule['rute'] ?? '-') . ' @ ' . ($oldSchedule['jam'] ?? '-'), $actor);
    } else {
      $stmt = $conn->prepare("INSERT INTO schedules (rute,dow,jam,units,seats,unit_id) VALUES (?,?,?,?,?,?) ON CONFLICT (rute, dow, jam) DO UPDATE SET units=EXCLUDED.units, seats=EXCLUDED.seats, unit_id=EXCLUDED.unit_id");
      $stmt->execute([$rute, $dow, $jam, $units, $seats, $unit_id]);
      activity_log_write($conn, 'settings', 'schedule', $conn->lastInsertId(), 'create', 'Jadwal disimpan: ' . $rute . ' @ ' . $jam, 'Unit: ' . $units . ' | Seat: ' . $seats, $actor);
    }
  }
  header('Location: admin.php#schedules');
  exit;
}
if (isset($_GET['delete_schedule'])) {
  $id = intval($_GET['delete_schedule']);
  $actor = activity_log_current_actor($auth ?? null);
  $stmtInfo = $conn->prepare("SELECT rute, jam FROM schedules WHERE id=? LIMIT 1");
  $stmtInfo->execute([$id]);
  $scheduleInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC) ?: [];
  $stmt = $conn->prepare("DELETE FROM schedules WHERE id=?");
  $stmt->execute([$id]);
  if ($stmt->rowCount() > 0) {
    activity_log_write($conn, 'settings', 'schedule', $id, 'delete', 'Jadwal dihapus: ' . (($scheduleInfo['rute'] ?? 'Jadwal') . ' @ ' . ($scheduleInfo['jam'] ?? '-')), '', $actor);
  }
  header('Location: admin.php#schedules');
  exit;
}

/* BOOKINGS cancel (server) Ã¢â‚¬â€ supports AJAX (returns JSON) */
if (isset($_POST['save_booking_edit'])) {
  $id = intval($_POST['booking_id']);
  $actor = activity_log_current_actor($auth ?? null);
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
    $oldStmt = $conn->prepare("SELECT pembayaran, name, rute, tanggal, jam, unit FROM bookings WHERE id=? LIMIT 1");
    $oldStmt->execute([$id]);
    $oldBooking = $oldStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $stmt = $conn->prepare("UPDATE bookings SET seat=?, unit=?, pickup_point=?, pembayaran=?, segment_id=?, price=?, discount=? WHERE id=?");
    $stmt->execute([$seat, $unit, $pickup, $pembayaran, $segment_id, $price, $discount, $id]);
    if (($oldBooking['pembayaran'] ?? '') !== $pembayaran) {
      activity_log_write(
        $conn,
        'booking',
        'payment',
        $id,
        'update',
        'Status pembayaran booking diperbarui menjadi ' . $pembayaran,
        ($oldBooking['name'] ?? 'Customer') . ' | ' . ($oldBooking['rute'] ?? '-') . ' | ' . ($oldBooking['tanggal'] ?? '-') . ' ' . ($oldBooking['jam'] ?? '-') . ' | Unit ' . ($oldBooking['unit'] ?? $unit),
        $actor
      );
    }
  }
  header('Location: admin.php');
  exit;
}
if (isset($_GET['cancel_booking'])) {
  $id = intval($_GET['cancel_booking']);
  $reason = trim($_GET['reason'] ?? '');
  $admin_user = activity_log_current_actor($auth ?? null);
  $result = ['success' => false];
  if ($id > 0) {
    $infoStmt = $conn->prepare("SELECT name, rute, tanggal, jam, unit FROM bookings WHERE id=? LIMIT 1");
    $infoStmt->execute([$id]);
    $bookingInfo = $infoStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $stmt = $conn->prepare("UPDATE bookings SET status='canceled' WHERE id=? AND status!='canceled'");
    $stmt->execute([$id]);
    $affected = $stmt->rowCount();
    if ($affected > 0) {
      $stmt2 = $conn->prepare("INSERT INTO cancellations (booking_id, admin_user, reason) VALUES (?,?,?)");
      $stmt2->execute([$id, $admin_user, $reason]);
      activity_log_write(
        $conn,
        'booking',
        'booking',
        $id,
        'cancel',
        'Booking dibatalkan: ' . ($bookingInfo['name'] ?? ('ID ' . $id)),
        trim(($bookingInfo['rute'] ?? '-') . ' | ' . ($bookingInfo['tanggal'] ?? '-') . ' ' . ($bookingInfo['jam'] ?? '-') . ' | Unit ' . ($bookingInfo['unit'] ?? '-') . ($reason !== '' ? ' | Alasan: ' . $reason : '')),
        $admin_user
      );
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

/* BOOKINGS mark paid (server) Ã¢â‚¬â€ supports AJAX (returns JSON) */
if (isset($_GET['mark_paid'])) {
  $id = intval($_GET['mark_paid']);
  $result = ['success' => false];
  if ($id > 0) {
    $actor = activity_log_current_actor($auth ?? null);
    $infoStmt = $conn->prepare("SELECT name, rute, tanggal, jam, unit, pembayaran FROM bookings WHERE id=? LIMIT 1");
    $infoStmt->execute([$id]);
    $bookingInfo = $infoStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $stmt = $conn->prepare("UPDATE bookings SET pembayaran='Lunas' WHERE id=?");
    $stmt->execute([$id]);
    $affected = $stmt->rowCount();
    if ($affected > 0) {
      activity_log_write(
        $conn,
        'booking',
        'payment',
        $id,
        'mark_paid',
        'Status pembayaran booking diubah menjadi Lunas',
        ($bookingInfo['name'] ?? ('ID ' . $id)) . ' | ' . ($bookingInfo['rute'] ?? '-') . ' | ' . ($bookingInfo['tanggal'] ?? '-') . ' ' . ($bookingInfo['jam'] ?? '-') . ' | Unit ' . ($bookingInfo['unit'] ?? '-'),
        $actor
      );
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

/* BOOKINGS mark ALL unpaid as Lunas for a given trip â€” AJAX JSON */
if (isset($_GET['mark_all_paid'])) {
  $rute    = trim($_GET['rute'] ?? '');
  $tanggal = trim($_GET['tanggal'] ?? '');
  $jam     = trim($_GET['jam'] ?? '');
  $unit    = intval($_GET['unit'] ?? 0);
  $result  = ['success' => false];
  if ($rute && $tanggal && $jam && $unit > 0) {
    $actor = activity_log_current_actor($auth ?? null);
    $stmt = $conn->prepare(
      "UPDATE bookings SET pembayaran='Lunas'
       WHERE rute=? AND tanggal=? AND jam=? AND unit=?
         AND status != 'canceled'
         AND (pembayaran IS NULL OR pembayaran NOT IN ('Lunas','Redbus','Traveloka'))"
    );
    $stmt->execute([$rute, $tanggal, $jam, $unit]);
    $result['success'] = true;
    $result['updated'] = $stmt->rowCount();
    if (($result['updated'] ?? 0) > 0) {
      activity_log_write(
        $conn,
        'booking',
        'departure_payment',
        $rute . '|' . $tanggal . '|' . $jam . '|' . $unit,
        'mark_all_paid',
        'Lunas Semua dijalankan untuk keberangkatan ' . $rute,
        $tanggal . ' ' . $jam . ' | Unit ' . $unit . ' | Booking diperbarui: ' . $result['updated'],
        $actor
      );
    }
  } else {
    $result['error'] = 'invalid_params';
  }
  header('Content-Type: application/json');
  echo json_encode($result);
  exit;
}

/* USERS create/update */
if (isset($_POST['add_user'])) {
  $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
  $actor = activity_log_current_actor($auth ?? null);
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $fullname = trim($_POST['fullname'] ?? '');
  if ($user_id > 0 && $username) {
    $oldStmt = $conn->prepare("SELECT username, fullname FROM users WHERE id=? LIMIT 1");
    $oldStmt->execute([$user_id]);
    $oldUser = $oldStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    if ($password !== '') {
      $hash = password_hash($password, PASSWORD_BCRYPT);
      $stmt = $conn->prepare("UPDATE users SET username=?, password_hash=?, fullname=? WHERE id=?");
      $stmt->execute([$username, $hash, $fullname, $user_id]);
    } else {
      $stmt = $conn->prepare("UPDATE users SET username=?, fullname=? WHERE id=?");
      $stmt->execute([$username, $fullname, $user_id]);
    }
    activity_log_write($conn, 'settings', 'user', $user_id, 'update', 'User diperbarui: ' . $username, 'Sebelumnya: ' . ($oldUser['username'] ?? '-'), $actor);
  } elseif ($username && $password) {
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users(username,password_hash,fullname) VALUES(?,?,?)");
    $stmt->execute([$username, $hash, $fullname]);
    if ($stmt->rowCount() > 0) {
      activity_log_write($conn, 'settings', 'user', $conn->lastInsertId(), 'create', 'User ditambahkan: ' . $username, $fullname, $actor);
    }
  }
  header('Location: admin.php#users');
  exit;
}

if (isset($_GET['delete_user'])) {
  $id = intval($_GET['delete_user']);
  $actor = activity_log_current_actor($auth ?? null);
  $stmtInfo = $conn->prepare("SELECT username FROM users WHERE id=? LIMIT 1");
  $stmtInfo->execute([$id]);
  $username = (string) ($stmtInfo->fetchColumn() ?: '');
  $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
  $stmt->execute([$id]);
  if ($stmt->rowCount() > 0) {
    activity_log_write($conn, 'settings', 'user', $id, 'delete', 'User dihapus: ' . ($username ?: ('ID ' . $id)), '', $actor);
  }
  header('Location: admin.php#users');
  exit;
}

/* SETTINGS save */
if (isset($_POST['save_settings'])) {
  $enable_claude = isset($_POST['enable_claude_haiku_4_5']) ? '1' : '0';
  updateSetting($conn, 'enable_claude_haiku_4_5', $enable_claude);
  activity_log_write($conn, 'settings', 'system_setting', 'enable_claude_haiku_4_5', 'update', 'Pengaturan sistem diperbarui', 'enable_claude_haiku_4_5 = ' . $enable_claude, activity_log_current_actor($auth ?? null));
  $_SESSION['settings_saved'] = true;
  header('Location: admin.php#settings');
  exit;
}

/* CUSTOMERS save/delete/import */


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



/* CUSTOMER BAGASI save/delete */




/* DRIVERS save/delete */
if (isset($_POST['save_driver'])) {
  $id = isset($_POST['driver_id']) ? intval($_POST['driver_id']) : 0;
  $actor = activity_log_current_actor($auth ?? null);
  $nama = trim($_POST['driver_nama'] ?? '');
  $phone = trim($_POST['driver_phone'] ?? '');
  $unit_id = intval($_POST['driver_unit_id'] ?? 0);

  if ($nama && $phone && $unit_id > 0) {
    if ($id > 0) {
      $oldStmt = $conn->prepare("SELECT nama FROM drivers WHERE id=? LIMIT 1");
      $oldStmt->execute([$id]);
      $oldName = (string) ($oldStmt->fetchColumn() ?: '');
      $stmt = $conn->prepare("UPDATE drivers SET nama=?, phone=?, unit_id=? WHERE id=?");
      $stmt->execute([$nama, $phone, $unit_id, $id]);
      activity_log_write($conn, 'settings', 'driver', $id, 'update', 'Driver diperbarui: ' . $nama, 'Sebelumnya: ' . ($oldName ?: '-'), $actor);
    } else {
      $stmt = $conn->prepare("INSERT INTO drivers (nama, phone, unit_id) VALUES (?,?,?)");
      $stmt->execute([$nama, $phone, $unit_id]);
      if ($stmt->rowCount() > 0) {
        activity_log_write($conn, 'settings', 'driver', $conn->lastInsertId(), 'create', 'Driver ditambahkan: ' . $nama, $phone, $actor);
      }
    }
  }
  header('Location: admin.php#drivers');
  exit;
}
if (isset($_GET['delete_driver'])) {
  $id = intval($_GET['delete_driver']);
  if ($id > 0) {
    $actor = activity_log_current_actor($auth ?? null);
    $stmtInfo = $conn->prepare("SELECT nama FROM drivers WHERE id=? LIMIT 1");
    $stmtInfo->execute([$id]);
    $driverName = (string) ($stmtInfo->fetchColumn() ?: '');
    $stmt = $conn->prepare("DELETE FROM drivers WHERE id=?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) {
      activity_log_write($conn, 'settings', 'driver', $id, 'delete', 'Driver dihapus: ' . ($driverName ?: ('ID ' . $id)), '', $actor);
    }
  }
  header('Location: admin.php#drivers');
  exit;
}

/* SEGMENTS save/delete */
if (isset($_POST['save_segment'])) {
  $id = isset($_POST['segment_id']) ? intval($_POST['segment_id']) : 0;
  $actor = activity_log_current_actor($auth ?? null);
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
      $oldStmt = $conn->prepare("SELECT rute FROM segments WHERE id=? LIMIT 1");
      $oldStmt->execute([$id]);
      $oldRoute = (string) ($oldStmt->fetchColumn() ?: '');
      $stmt = $conn->prepare("UPDATE segments SET route_id=?, rute=?, origin=?, destination=?, pickup_time=?, harga=? WHERE id=?");
      $stmt->execute([$route_id, $rute, $origin, $destination, $pickup_time, $harga, $id]);
      activity_log_write($conn, 'settings', 'segment', $id, 'update', 'Segment diperbarui: ' . $rute, 'Sebelumnya: ' . ($oldRoute ?: '-'), $actor);
    } else {
      $stmt = $conn->prepare("INSERT INTO segments (route_id, rute, origin, destination, pickup_time, harga) VALUES (?,?,?,?,?,?)");
      $stmt->execute([$route_id, $rute, $origin, $destination, $pickup_time, $harga]);
      if ($stmt->rowCount() > 0) {
        activity_log_write($conn, 'settings', 'segment', $conn->lastInsertId(), 'create', 'Segment ditambahkan: ' . $rute, $pickup_time !== '' ? ('Pickup ' . $pickup_time) : '', $actor);
      }
    }
  }
  header('Location: admin.php#segments');
  exit;
}
if (isset($_GET['delete_segment'])) {
  $id = intval($_GET['delete_segment']);
  if ($id > 0) {
    $actor = activity_log_current_actor($auth ?? null);
    $stmtInfo = $conn->prepare("SELECT rute FROM segments WHERE id=? LIMIT 1");
    $stmtInfo->execute([$id]);
    $segmentName = (string) ($stmtInfo->fetchColumn() ?: '');
    $stmt = $conn->prepare("DELETE FROM segments WHERE id=?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) {
      activity_log_write($conn, 'settings', 'segment', $id, 'delete', 'Segment dihapus: ' . ($segmentName ?: ('ID ' . $id)), '', $actor);
    }
  }
  header('Location: admin.php#segments');
  exit;
}
if (isset($_POST['import_csv']) || isset($_POST['import_customers'])) {
  $actor = activity_log_current_actor($auth ?? null);
  @set_time_limit(0);
  @ini_set('max_execution_time', '0');
  @ini_set('memory_limit', '512M');
  $fileKey = isset($_FILES['csv_file']) ? 'csv_file' : 'csv';
  if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['import_msg'] = 'Upload gagal';
    header('Location: admin.php#customers');
    exit;
  }
  $tmp = $_FILES[$fileKey]['tmp_name'];
  $f = fopen($tmp, 'r');
  if (!$f) {
    $_SESSION['import_msg'] = 'Tidak dapat buka file';
    header('Location: admin.php#customers');
    exit;
  }
  $firstLine = fgets($f);
  if ($firstLine === false) {
    $_SESSION['import_msg'] = 'CSV kosong';
    fclose($f);
    header('Location: admin.php#customers');
    exit;
  }
  $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
  rewind($f);
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
  $batchSize = 1000;
  $rowsBuffer = [];
  $singleStmt = $conn->prepare("INSERT INTO customers (name,phone,address,pickup_point) VALUES(?,?,?,?) ON CONFLICT (phone) DO UPDATE SET name=EXCLUDED.name, address=EXCLUDED.address, pickup_point=EXCLUDED.pickup_point");

  $normalizePhone = function ($phone) {
    $phone = trim((string) $phone);
    $phone = preg_replace('/\D/', '', $phone);
    if (substr($phone, 0, 2) === '62')
      $phone = '0' . substr($phone, 2);
    if (substr($phone, 0, 1) === '8')
      $phone = '0' . $phone;
    if (strlen($phone) > 13)
      $phone = substr($phone, 0, 13);
    return $phone;
  };

  $flushCustomerImportBatch = function (array &$batchRows) use ($conn, $singleStmt, &$ok, &$err) {
    if (!$batchRows) {
      return;
    }
    $values = [];
    $params = [];
    $uniqueRows = array_values($batchRows);
    foreach ($uniqueRows as $item) {
      $values[] = '(?,?,?,?)';
      array_push($params, $item['name'], $item['phone'], $item['address'], $item['pickup']);
    }
    $sql = "INSERT INTO customers (name,phone,address,pickup_point) VALUES " . implode(',', $values) . " ON CONFLICT (phone) DO UPDATE SET name=EXCLUDED.name, address=EXCLUDED.address, pickup_point=EXCLUDED.pickup_point";
    try {
      $conn->prepare($sql)->execute($params);
      $ok += count($uniqueRows);
    } catch (PDOException $batchError) {
      foreach ($uniqueRows as $item) {
        try {
          if ($singleStmt->execute([$item['name'], $item['phone'], $item['address'], $item['pickup']])) {
            $ok++;
          } else {
            $err++;
          }
        } catch (PDOException $rowError) {
          $err++;
        }
      }
    }
    $batchRows = [];
  };

  while (($row = fgetcsv($f, 0, $delimiter)) !== false) {
    $name = strtoupper(trim($row[$colIndex['name']] ?? ''));
    $phone = $normalizePhone($row[$colIndex['phone']] ?? '');
    $pickup = trim($row[$colIndex['pickup_point']] ?? '');
    $address = trim($row[$colIndex['address']] ?? '');
    if (!$name || !$phone) {
      $err++;
      continue;
    }

    $rowsBuffer[$phone] = [
      'name' => $name,
      'phone' => $phone,
      'pickup' => $pickup,
      'address' => $address,
    ];

    if (count($rowsBuffer) >= $batchSize) {
      $flushCustomerImportBatch($rowsBuffer);
    }
  }
  $flushCustomerImportBatch($rowsBuffer);
  fclose($f);
  $_SESSION['import_msg'] = "Import selesai - berhasil: $ok, error: $err";
  activity_log_write($conn, 'settings', 'customer_import', 'csv', 'import', 'Import CSV customers selesai', 'Berhasil: ' . $ok . ' | Error: ' . $err, $actor);
  header('Location: admin.php#customers');
  exit;
}
if (isset($_POST['create_charter_submit'])) {
  $actor = activity_log_current_actor($auth ?? null);
  $charterForm = [
    'name' => trim((string) ($_POST['name'] ?? '')),
    'phone' => trim((string) ($_POST['phone'] ?? '')),
    'email' => trim((string) ($_POST['email'] ?? '')),
    'pickup_point' => trim((string) ($_POST['pickup_point'] ?? '')),
    'drop_point' => trim((string) ($_POST['drop_point'] ?? '')),
    'start_date' => trim((string) ($_POST['start_date'] ?? date('Y-m-d'))),
    'duration_days' => trim((string) ($_POST['duration_days'] ?? '3')),
    'departure_time' => trim((string) ($_POST['departure_time'] ?? '08:30')),
    'bus_type' => trim((string) ($_POST['bus_type'] ?? 'Big Bus')),
    'unit_id' => trim((string) ($_POST['unit_id'] ?? '')),
    'driver_name' => trim((string) ($_POST['driver_name'] ?? '')),
    'price' => trim((string) ($_POST['price'] ?? '')),
    'down_payment' => trim((string) ($_POST['down_payment'] ?? '')),
    'payment_status' => trim((string) ($_POST['payment_status'] ?? 'DP')),
  ];

  $parseCurrency = function ($value) {
    $normalized = preg_replace('/[^0-9,.-]/', '', (string) $value);
    $normalized = str_replace('.', '', $normalized);
    $normalized = str_replace(',', '.', $normalized);
    return (float) $normalized;
  };

  $parseRoute = function ($value) {
    $value = trim(preg_replace('/\s+/', ' ', (string) $value));
    if ($value === '') {
      return ['', ''];
    }
    foreach (['->', ' - ', ' -- ', ' to ', ' ke '] as $separator) {
      if (stripos($value, $separator) !== false) {
        $parts = preg_split('/' . preg_quote($separator, '/') . '/i', $value, 2);
        return [trim($parts[0] ?? ''), trim($parts[1] ?? '')];
      }
    }
    if (strpos($value, '-') !== false) {
      $parts = explode('-', $value, 2);
      return [trim($parts[0] ?? ''), trim($parts[1] ?? '')];
    }
    return [$value, ''];
  };

  $errors = [];
  $name = strtoupper($charterForm['name']);
  $phone = preg_replace('/\s+/', '', $charterForm['phone']);
  $pickupPoint = $charterForm['pickup_point'];
  $dropPoint = $charterForm['drop_point'];
  $startDate = $charterForm['start_date'];
  $durationDays = max(1, (int) $charterForm['duration_days']);
  $departureTime = $charterForm['departure_time'] ?: '08:30';
  $busType = $charterForm['bus_type'] ?: 'Big Bus';
  $unitId = (int) $charterForm['unit_id'];
  $driverName = $charterForm['driver_name'];
  $price = $parseCurrency($charterForm['price']);

  if ($name === '')
    $errors[] = 'Nama lengkap wajib diisi.';
  if ($phone === '')
    $errors[] = 'Nomor telepon wajib diisi.';
  if ($pickupPoint === '')
    $errors[] = 'Lokasi penjemputan wajib diisi.';
  if ($dropPoint === '')
    $errors[] = 'Tujuan / destinasi wajib diisi.';
  if ($startDate === '')
    $errors[] = 'Tanggal keberangkatan wajib diisi.';
  if ($unitId <= 0)
    $errors[] = 'Unit kendaraan wajib dipilih.';

  if ($errors) {
    $_SESSION['charter_create_errors'] = $errors;
    $_SESSION['charter_create_old'] = $charterForm;
    header('Location: admin.php#charter-create');
    exit;
  }

  $endDate = date('Y-m-d', strtotime($startDate . ' +' . max(0, $durationDays - 1) . ' days'));
  $stmt = $conn->prepare("INSERT INTO charters (name, company_name, phone, start_date, end_date, departure_time, pickup_point, drop_point, unit_id, driver_name, price, layanan, bop_price, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

  try {
    $stmt->execute([
      $name,
      'ADMIN',
      $phone,
      $startDate,
      $endDate,
      $departureTime,
      $pickupPoint,
      $dropPoint,
      $unitId,
      $driverName,
      $price,
      $busType,
      0,
    ]);
    activity_log_write($conn, 'charter', 'charter', $conn->lastInsertId(), 'create', 'Carter ditambahkan: ' . $name, $pickupPoint . ' -> ' . $dropPoint . ' | ' . $startDate . ' ' . $departureTime, $actor);
    $_SESSION['booking_msg'] = 'Data carter berhasil disimpan.';
    unset($_SESSION['charter_create_errors'], $_SESSION['charter_create_old']);
    header('Location: admin.php?booking_mode=charters#bookings');
    exit;
  } catch (PDOException $e) {
    $_SESSION['charter_create_errors'] = ['Gagal menyimpan carter: ' . $e->getMessage()];
    $_SESSION['charter_create_old'] = $charterForm;
    header('Location: admin.php#charter-create');
    exit;
  }
}

/********** DATA FOR RENDER (OPTIMIZED) **********/
// Routes — deferred, only loaded when HTML is rendered
$routes = null;
function getRoutes() {
  global $conn, $routes;
  if ($routes === null) {
    $routes = [];
    $res = $conn->query("SELECT id,name FROM routes ORDER BY id");
    while ($r = $res->fetch()) $routes[] = $r;
  }
  return $routes;
}
$import_msg = $_SESSION['import_msg'] ?? '';
unset($_SESSION['import_msg']);
$booking_msg = $_SESSION['booking_msg'] ?? '';
unset($_SESSION['booking_msg']);
$settings_saved = $_SESSION['settings_saved'] ?? false;
unset($_SESSION['settings_saved']);
// Cancellations — loaded via AJAX lazy sections, no eager loading needed
$cancellations = [];

/* Load feature flags */
$enable_claude_haiku_4_5 = getSetting($conn, 'enable_claude_haiku_4_5', '0');

/* UNITS LOGIC — deferred to conditional block below */

/********** CONDITIONAL: ONLY RENDER HTML FOR NON-AJAX REQUESTS **********/
if (!isset($_REQUEST['action'])):
// Include units logic only for page loads, not AJAX
include 'includes/units_logic.php'; 
?>
<!doctype html>
<html lang="id" class="light" data-default-theme="light">


<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <title>Admin Panel</title>
  <meta name="description" content="Admin Panel Cahaya Bone untuk mengelola dashboard, booking, carter, bagasi, laporan, dan pengaturan operasional.">
  <meta name="theme-color" content="#f97316">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Cahaya Bone">
  <meta property="og:title" content="Admin Panel">
  <meta property="og:description" content="Admin Panel Cahaya Bone untuk mengelola dashboard, booking, carter, bagasi, laporan, dan pengaturan operasional.">
  <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
  <link rel="shortcut icon" href="assets/images/favicon.svg">
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
  <link rel="stylesheet" href="assets/lib/fonts/fonts.css?v=1">
  <link rel="stylesheet" href="assets/lib/bootstrap/css/bootstrap.min.css?v=1">
  <link rel="stylesheet" href="assets/lib/fontawesome/css/all.min.css?v=1">
  <link rel="stylesheet" href="assets/css/admin-bootstrap.css?v=71">
  <link rel="stylesheet" href="assets/css/theme-toggle.css?v=22">
  <style>
    /* Critical: ensure shell loader is always hidden on ready */
    .admin-shell-ready .admin-shell-loader,
    .admin-shell-loader.is-hidden {
      opacity: 0 !important;
      visibility: hidden !important;
      pointer-events: none !important;
      display: none !important;
    }




    <link rel="stylesheet" href="assets/lib/fontawesome/css/all.min.css?v=1">
  <link rel="stylesheet" href="assets/css/admin-bootstrap.css?v=60">
  <link rel="stylesheet" href="assets/css/admin-bootstrap.css?v=61">
  <link rel="stylesheet" href="assets/css/theme-toggle.css?v=21">
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
<?php
// Early flush: send <head> to browser so it can start downloading CSS/JS
// while PHP continues processing the body.
if (ob_get_level()) { ob_end_flush(); }
flush();
?>

<body class="admin-bootstrap-page app-admin admin-shell-ready">

  <div id="toast" class="toast" role="status" aria-live="polite"></div>

  <?php include 'includes/navbar.php'; ?>

  <script>
    (function () {
      function hideAdminShellLoader() {
        document.body.classList.add('admin-shell-ready');
      }

      window.setTimeout(hideAdminShellLoader, 50);
      window.addEventListener('load', hideAdminShellLoader, { once: true });
      window.hideAdminShellLoader = hideAdminShellLoader;
    })();
  </script>

  <main class="container container-fluid admin-bootstrap-container">
    <div class="layout admin-bootstrap-grid">
      <div class="left admin-main-column">
        <!-- DASHBOARD -->
        <?php include 'includes/dashboard.php'; ?>

        <?php renderAdminSectionSlot('bookings'); ?>
        <?php renderAdminSectionSlot('charter-create'); ?>
        <?php renderAdminSectionSlot('luggage-create'); ?>
        <?php renderAdminSectionSlot('customers'); ?>
        <?php renderAdminSectionSlot('routes'); ?>
        <?php renderAdminSectionSlot('routes_carter'); ?>
        <?php renderAdminSectionSlot('schedules'); ?>
        <?php renderAdminSectionSlot('drivers'); ?>
        <?php renderAdminSectionSlot('segments'); ?>
        <?php renderAdminSectionSlot('users'); ?>
        <?php renderAdminSectionSlot('units'); ?>
        <?php renderAdminSectionSlot('booking-detail'); ?>
        <?php renderAdminSectionSlot('cancellations'); ?>
        <?php renderAdminSectionSlot('reports'); ?>
        <?php renderAdminSectionSlot('luggage_services'); ?>
        <?php renderAdminSectionSlot('customer_bagasi'); ?>
        <?php renderAdminSectionSlot('customer_charter'); ?>
        <?php renderAdminSectionSlot('luggage'); ?>

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
    <div class="modal-popup-content admin-modal-card admin-modal-card-compact admin-modal-card-form">
      <h3 class="modal-popup-title admin-modal-heading">Edit Penumpang</h3>

      <form method="post" id="editBookingForm" novalidate class="admin-modal-form">
        <div id="editBookingErrorMsg" class="admin-modal-error"></div>
        <input type="hidden" name="save_booking_edit" value="1">
        <input type="hidden" id="edit_booking_id" name="booking_id" value="">

        <div class="admin-modal-grid admin-modal-grid-3 admin-modal-grid-tight">
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
          <div class="admin-modal-field">
            <label class="admin-modal-label">Tanggal Berangkat</label>
            <input type="date" id="edit_tanggal" name="edit_tanggal" class="form-control admin-modal-control" required>
          </div>
        </div>

        <div class="admin-modal-field">
          <label class="admin-modal-label">Alamat / Titik Jemput</label>
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

        <div class="admin-modal-grid admin-modal-grid-3 admin-modal-grid-tight">
          <div class="admin-modal-field">
            <label class="admin-modal-label">Segment Rute</label>
            <select id="edit_segment_id" name="segment_id" class="form-control admin-modal-control">
              <option value="0">-- Default Rute --</option>
              <?php foreach (getGlobalSegments() as $gs): ?>
                <option value="<?= $gs['id'] ?>" data-price="<?= $gs['harga'] ?>">
                  <?= htmlspecialchars($gs['rute']) ?> (Rp <?= number_format($gs['harga'], 0, ',', '.') ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
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

  <!-- Modal for Copy Fallback -->
  <div class="bottom-more-modal admin-modal-overlay" id="copyTextModal">
    <div class="modal-popup-content admin-modal-card admin-modal-card-md admin-modal-card-form">
      <h3 id="copyTextModalTitle" class="modal-popup-title admin-modal-heading">Salin Data Booking</h3>
      <p class="modal-popup-message admin-copy-modal-message">Safari kadang memblokir salin otomatis. Teks sudah kami siapkan di bawah ini supaya bisa langsung disalin.</p>
      <div class="admin-modal-field">
        <textarea id="copyTextModalValue" class="form-control admin-modal-control admin-modal-copy-textarea" readonly></textarea>
      </div>
      <div class="admin-modal-actions admin-modal-actions-split">
        <button id="copyTextModalCopyBtn" type="button" class="btn btn-modern admin-modal-action admin-modal-action-wide">Copy Sekarang</button>
        <button id="copyTextModalCloseBtn" type="button" class="btn btn-modern secondary admin-modal-action">Tutup</button>
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
  <script src="assets/js/admin-panel.js?v=11"></script>
  <style>
    /* Responsive styles moved to includes/navbar.php */
  </style>
  <script src="assets/js/theme-toggle.js?v=13"></script>
  <script src="assets/lib/bootstrap/js/bootstrap.bundle.min.js?v=1"></script>
</body>

</html>
<?php endif; // End of conditional HTML rendering for non-AJAX requests
?>


