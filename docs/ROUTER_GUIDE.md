# 📋 Panduan Implementasi Router

## Overview
Router class menyediakan sistem routing yang terstruktur untuk mengelola GET/POST requests dengan lebih clean dan maintainable.

---

## 🚀 Cara Menggunakan

### 1. **Load Router Class**
```php
require_once 'Router.php';
$router = new Router();
```

### 2. **Define Routes**

#### GET Route
```php
$router->get('getRoutes', function () use ($conn) {
    // handler logic
    echo json_encode(['success' => true, 'data' => ...]);
});
```

#### POST Route
```php
$router->post('submitBooking', function () use ($conn) {
    $data = json_decode(file_get_contents("php://input"), true);
    // handler logic
    echo json_encode(['success' => true]);
});
```

#### Keduanya (GET & POST)
```php
$router->any('getUnits', function () use ($conn) {
    // Handler untuk GET dan POST
    echo json_encode(['success' => true, 'units' => []]);
});
```

### 3. **Dispatch Request**
```php
// Di akhir file api.php, ganti logic lama dengan:
$router->dispatch();
```

---

## 💡 Keuntungan

| Sebelum (If-based) | Sesudah (Router) |
|---|---|
| 30+ if statements | Clean function mapping |
| Hard to maintain | Easy to find handlers |
| No type checking | Centralized error handling |
| Repetitive code | DRY code |

---

## 🔍 Contoh Real-World

### Setup Routing untuk API Booking:

```php
<?php
require_once 'Router.php';
require_once 'config/db.php';

$router = new Router();

// === CUSTOMER (GET) ===
$router->get('getRoutes', function () use ($conn) {
    $result = $conn->query("SELECT * FROM routes");
    echo json_encode(['success' => true, 'routes' => $result->fetchAll()]);
});

$router->get('getSchedules', function () use ($conn) {
    $rute = $_GET['rute'] ?? '';
    $tanggal = $_GET['tanggal'] ?? '';
    
    if (!$rute) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing rute']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT * FROM schedules WHERE rute=? AND tanggal=?");
    $stmt->execute([$rute, $tanggal]);
    echo json_encode(['success' => true, 'schedules' => $stmt->fetchAll()]);
});

// ===== BOOKINGS (POST) =====
$router->post('submitBooking', function () use ($conn) {
    $input = json_decode(file_get_contents("php://input"), true);
    
    // Validate input
    if (empty($input['name']) || empty($input['phone'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing fields']);
        return;
    }
    
    // Insert booking
    $stmt = $conn->prepare("INSERT INTO bookings (name, phone, rute, tanggal, seat) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$input['name'], $input['phone'], $input['rute'], $input['tanggal'], $input['seat']]);
    
    echo json_encode(['success' => true, 'booking_id' => $conn->lastInsertId()]);
});

// ===== DISPATCH =====
$router->dispatch();
?>
```

---

## 📝 Migrasi dari IF-Based ke Router

### Before:
```php
$action = $_GET['action'] ?? null;
if ($method === 'GET') {
    if ($action === 'getRoutes') {
        // ... 50 lines of logic
    }
    if ($action === 'getSegments') {
        // ... 30 lines of logic
    }
}
```

### After:
```php
$router->get('getRoutes', function () use ($conn) {
    // ... 10 lines focused logic
});

$router->get('getSegments', function () use ($conn) {
    // ... 10 lines focused logic
});

$router->dispatch();
```

---

## 🛠 Advanced Features

### 1. **Check if Route Exists**
```php
if ($router->hasRoute('GET', 'getRoutes')) {
    echo "Route exists!";
}
```

### 2. **Debug Routes** 
```php
// Get all registered routes
$routes = $router->getRoutes();
print_r($routes);
```

### 3. **Class Method Callbacks** (Future)
```php
// If you want to use controllers
class BookingController {
    public static function submit($conn) {
        // logic
    }
}

$router->post('submitBooking', ['BookingController', 'submit']);
```

---

## ⚠️ Tips Implementation

1. **Keep handlers focused** - One action per handler
2. **Validate inputs early** - Check required parameters
3. **Return consistent JSON** - Always use `{'success': bool, ...}` format
4. **Use named parameters** - `?action=getRoutes&rute=Jakarta` is clear
5. **Error handling** - Set HTTP status codes properly (400, 404, 500)

---

## 📌 Next Steps

1. ✅ **Phase 1**: Replace api.php with router (see example-api-with-router.php)
2. 🔄 **Phase 2**: Replace admin.php action handlers with router
3. 🎯 **Phase 3** (Optional): Create Controller classes for more organization

---

**File Referensi:**
- Router Class: `Router.php`
- Contoh Lengkap: `example-api-with-router.php`
- Project Lama: `api.php` (tetap untuk reference)
