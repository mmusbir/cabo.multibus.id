# ✅ Router Implementation Checklist

## 📦 Files Created

- ✅ `Router.php` - Router class utama
- ✅ `helpers/api-response.php` - Helper functions untuk response API
- ✅ `example-api-with-router.php` - Contoh implementasi sederhana
- ✅ `api-refactored.php` - Contoh implementasi production-ready
- ✅ `ROUTER_GUIDE.md` - Dokumentasi lengkap

---

## 🚀 Quick Start (3 Langkah)

### Step 1: Test Router dengan File Sample
```bash
# Akses file contoh untuk testing
http://localhost/cabo.multibus.id/api-refactored.php?action=getRoutes
```

### Step 2: Ganti api.php Saat Siap
```php
// Setelah testing berhasil, backup api.php lama:
// Rename: api.php → api.php.backup

// Rename: api-refactored.php → api.php
```

### Step 3: Test Seluruh Aplikasi
```bash
# Test semua endpoint yang ada
# Pastikan frontend masih berfungsi
```

---

## 📝 Implementasi Manual untuk admin.php

Jika ingin apply router ke admin.php juga:

```php
<?php
require_once 'Router.php';
require_once 'helpers/api-response.php';
require_once 'config/db.php';

$router = new Router();

// Dashboard
$router->get('dashboard', function () {
    require_once 'admin/pages/dashboard.php';
});

// Bookings CRUD
$router->get('bookingsList', function () use ($conn) {
    $bookings = $conn->query("SELECT * FROM bookings")->fetchAll();
    apiSuccess(['bookings' => $bookings]);
});

$router->post('createBooking', function () use ($conn) {
    // create logic
});

$router->post('updateBooking', function () use ($conn) {
    // update logic
});

$router->post('deleteBooking', function () use ($conn) {
    // delete logic
});

// Dispatch
$router->dispatch();
?>
```

---

## 🧪 Testing Guide

### 1. Test GET Request
```bash
curl "http://localhost/api-refactored.php?action=getRoutes"
```

### 2. Test POST Request
```bash
curl -X POST "http://localhost/api-refactored.php?action=submitBooking" \
  -H "Content-Type: application/json" \
  -d '{"name":"John","phone":"08123456789","rute":"Jakarta-Bandung","tanggal":"2026-03-22","jam":"08:00","seat":"A1"}'
```

### 3. Test Invalid Action
```bash
curl "http://localhost/api-refactored.php?action=invalidAction"
# Expected: 404 error
```

---

## 🔄 Migration Steps (Complete)

- [ ] Step 1: Backup original api.php
- [ ] Step 2: Review example-api-with-router.php
- [ ] Step 3: Review api-refactored.php structure
- [ ] Step 4: Create new api.php dengan Router
- [ ] Step 5: Test all GET endpoints
- [ ] Step 6: Test all POST endpoints
- [ ] Step 7: Test error handling (bad input, missing params)
- [ ] Step 8: Verify frontend still works
- [ ] Step 9: Remove old if-based routing code
- [ ] Step 10: Deploy to production

---

## 📚 Helper Functions Quick Reference

```php
// API Responses
apiSuccess($data, 200);              // Send success response
apiError($message, 400, $errors);    // Send error response

// Input Handling
getQuery('param_name', 'default');   // Get GET parameter
getPost('param_name', 'default');    // Get POST parameter
getJsonInput();                       // Get JSON body
validateRequired(['field1', 'field2']); // Validate required fields

// Utils
isMethod('POST');                    // Check HTTP method
```

---

## ⚠️ Common Gotchas

### 1. Forgoing JSON Input for POST/JSON
```php
// ❌ Wrong - POST data tidak ada di $_POST untuk JSON
$name = $_POST['name'];

// ✅ Correct
$input = getJsonInput();
$name = $input['name'];
```

### 2. Forgetting to Exit in apiSuccess/apiError
```php
// ✅ Functions automatically exit()
apiSuccess(['data' => 'value']);
// Code after this won't run
```

### 3. Not Catching Database Exceptions
```php
// ✅ Best practice
try {
    $stmt->execute([...]);
    apiSuccess(['id' => $conn->lastInsertId()]);
} catch (Exception $e) {
    apiError('Database error: ' . $e->getMessage(), 500);
}
```

---

## 🎯 Benefits Achieved

| Aspek | Sebelum | Sesudah |
|---|---|---|
| **Code Lines (api.php)** | 400+ lines | ~250 lines |
| **Readability** | 30+ if statements | Clean mapping |
| **Error Handling** | Manual per endpoint | Centralized |
| **Response Format** | Inconsistent | Standardized |
| **Maintenance** | Difficult | Easy |
| **Testability** | Hard to unit test | Easy to test |

---

## 🔗 Related Files
- Router class: `Router.php`
- Examples: `example-api-with-router.php` & `api-refactored.php`
- Helpers: `helpers/api-response.php`
- Guide: `ROUTER_GUIDE.md`

---

**Last Updated:** March 22, 2026
**Status:** ✅ Ready to Implement
