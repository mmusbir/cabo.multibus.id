<?php
/**
 * db-migrate.php
 * Database migration & schema setup
 * Run once during deployment via CLI: php db-migrate.php
 * Or include in admin.php setup
 */

if (!isset($conn)) {
  require_once __DIR__ . '/config/db.php';
}

// ==================== TABLE SETUP ====================

// 1. Ensure all tables exist first
$conn->exec("CREATE TABLE IF NOT EXISTS charters (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    departure_time TIME,
    pickup_point VARCHAR(255),
    drop_point VARCHAR(255),
    unit_id INT,
    driver_name VARCHAR(100),
    price NUMERIC(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW()
)");

$conn->exec("CREATE TABLE IF NOT EXISTS drivers (
    id SERIAL PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    phone VARCHAR(50),
    unit_id INT,
    created_at TIMESTAMP DEFAULT NOW()
)");

$conn->exec("CREATE TABLE IF NOT EXISTS segments (
    id SERIAL PRIMARY KEY,
    route_id INT DEFAULT 0,
    rute VARCHAR(100) NOT NULL,
    pickup_time VARCHAR(5),
    harga NUMERIC(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW()
)");

$conn->exec("CREATE TABLE IF NOT EXISTS trip_assignments (
    id SERIAL PRIMARY KEY,
    rute VARCHAR(100),
    tanggal DATE,
    jam TIME,
    unit INT,
    driver_id INT,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE (rute, tanggal, jam, unit)
)");

$conn->exec("CREATE TABLE IF NOT EXISTS bookings (
    id SERIAL PRIMARY KEY,
    rute VARCHAR(100) NOT NULL,
    tanggal DATE NOT NULL,
    jam TIME NOT NULL,
    unit INT DEFAULT 1,
    seat VARCHAR(20) NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    pickup_point VARCHAR(255),
    pembayaran VARCHAR(50) DEFAULT 'Belum Lunas',
    status VARCHAR(20) DEFAULT 'active',
    segment_id INT DEFAULT 0,
    price NUMERIC(15,2) DEFAULT 0,
    discount NUMERIC(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW()
)");

$conn->exec("CREATE TABLE IF NOT EXISTS customers (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL UNIQUE,
    address TEXT,
    pickup_point VARCHAR(255),
    created_at TIMESTAMP DEFAULT NOW()
)");

$conn->exec("CREATE TABLE IF NOT EXISTS schedules (
    id SERIAL PRIMARY KEY,
    rute VARCHAR(100) NOT NULL,
    dow INT NOT NULL,
    jam TIME NOT NULL,
    units INT DEFAULT 1,
    seats INT DEFAULT 8,
    unit_id INT,
    layout TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(rute, dow, jam)
)");

$conn->exec("CREATE TABLE IF NOT EXISTS units (
    id SERIAL PRIMARY KEY,
    nopol VARCHAR(50) NOT NULL UNIQUE,
    merek VARCHAR(100),
    type VARCHAR(100),
    category VARCHAR(100) DEFAULT 'Big Bus',
    tahun INT DEFAULT 0,
    kapasitas INT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'Aktif',
    layout TEXT,
    created_at TIMESTAMP DEFAULT NOW()
)");

$conn->exec("CREATE TABLE IF NOT EXISTS settings (
    key VARCHAR(100) PRIMARY KEY,
    value TEXT,
    created_at TIMESTAMP DEFAULT NOW()
)");

$conn->exec("CREATE TABLE IF NOT EXISTS cancellations (
    id SERIAL PRIMARY KEY,
    booking_id INT NOT NULL,
    admin_user VARCHAR(100),
    reason TEXT,
    created_at TIMESTAMP DEFAULT NOW()
)");

$conn->exec("CREATE TABLE IF NOT EXISTS luggage_services (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price NUMERIC(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
)");

$conn->exec("CREATE TABLE IF NOT EXISTS luggages (
    id SERIAL PRIMARY KEY,
    sender_name VARCHAR(255) NOT NULL,
    sender_phone VARCHAR(50) NOT NULL,
    sender_address TEXT,
    receiver_name VARCHAR(255) NOT NULL,
    receiver_phone VARCHAR(50) NOT NULL,
    receiver_address TEXT,
    service_id INT NOT NULL,
    quantity INT DEFAULT 1,
    notes TEXT,
    price NUMERIC(15,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    payment_status VARCHAR(20) DEFAULT 'Belum Lunas',
    created_at TIMESTAMP DEFAULT NOW()
)");

$conn->exec("CREATE TABLE IF NOT EXISTS routes (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    origin VARCHAR(255),
    destination VARCHAR(255),
    created_at TIMESTAMP DEFAULT NOW()
)");

$conn->exec("CREATE TABLE IF NOT EXISTS master_carter (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    origin VARCHAR(255),
    destination VARCHAR(255),
    duration VARCHAR(50),
    rental_price NUMERIC(15,2) DEFAULT 0,
    bop_price NUMERIC(15,2) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(origin, destination, duration)
)");

$conn->exec("CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    fullname VARCHAR(100),
    created_at TIMESTAMP DEFAULT NOW()
)");

$conn->exec("CREATE TABLE IF NOT EXISTS customer_bagasi (
    id SERIAL PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    no_hp VARCHAR(50) NOT NULL,
    alamat TEXT,
    tipe VARCHAR(50) DEFAULT 'keduanya', 
    created_at TIMESTAMP DEFAULT NOW()
)");

$conn->exec("CREATE TABLE IF NOT EXISTS customer_charter (
    id SERIAL PRIMARY KEY,
    nama VARCHAR(255) NOT NULL,
    perusahaan VARCHAR(255),
    no_hp VARCHAR(50) NOT NULL,
    alamat TEXT,
    created_at TIMESTAMP DEFAULT NOW()
)");

$conn->exec("CREATE TABLE IF NOT EXISTS harga_bagasi (
    id SERIAL PRIMARY KEY,
    rute_id INT NOT NULL,
    layanan_id INT NOT NULL,
    harga NUMERIC(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(rute_id, layanan_id)
)");

$conn->exec("CREATE TABLE IF NOT EXISTS bagasi_logs (
    id SERIAL PRIMARY KEY,
    kode_resi VARCHAR(50) NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT,
    created_by_username VARCHAR(100),
    created_at TIMESTAMP DEFAULT NOW()
)");

// ==================== COLUMN ALTERATIONS ====================

// Add columns if they don't exist
if (!db_column_exists($conn, 'charters', 'departure_time')) {
  $conn->exec("ALTER TABLE charters ADD COLUMN departure_time TIME");
}
if (!db_column_exists($conn, 'charters', 'company_name')) {
  $conn->exec("ALTER TABLE charters ADD COLUMN company_name VARCHAR(255)");
}
if (!db_column_exists($conn, 'charters', 'bop_status')) {
  $conn->exec("ALTER TABLE charters ADD COLUMN bop_status VARCHAR(20) DEFAULT 'pending'");
}
if (!db_column_exists($conn, 'charters', 'layanan')) {
  $conn->exec("ALTER TABLE charters ADD COLUMN layanan VARCHAR(255) DEFAULT 'Regular'");
}
if (!db_column_exists($conn, 'charters', 'bop_price')) {
  $conn->exec("ALTER TABLE charters ADD COLUMN bop_price NUMERIC(15,2) DEFAULT 0");
}
if (!db_column_exists($conn, 'charters', 'down_payment')) {
  $conn->exec("ALTER TABLE charters ADD COLUMN down_payment NUMERIC(15,2) DEFAULT 0");
}
if (!db_column_exists($conn, 'charters', 'payment_status')) {
  $conn->exec("ALTER TABLE charters ADD COLUMN payment_status VARCHAR(30) DEFAULT 'Belum Bayar'");
}

// Add unique constraint to customer_charter no_hp if not exists
try {
  $conn->exec("ALTER TABLE customer_charter ADD CONSTRAINT customer_charter_no_hp_key UNIQUE (no_hp)");
} catch (PDOException $e) {
  // Constraint might already exist
}

// Add segment_id, price, discount to bookings if not exists
if (!db_column_exists($conn, 'bookings', 'segment_id')) {
  $conn->exec("ALTER TABLE bookings ADD COLUMN segment_id INT");
  $conn->exec("ALTER TABLE bookings ADD COLUMN price NUMERIC(15,2) DEFAULT 0");
  $conn->exec("ALTER TABLE bookings ADD COLUMN discount NUMERIC(15,2) DEFAULT 0");
}
if (!db_column_exists($conn, 'bookings', 'created_by_user_id')) {
  $conn->exec("ALTER TABLE bookings ADD COLUMN created_by_user_id INT NULL");
}
if (!db_column_exists($conn, 'bookings', 'created_by_username')) {
  $conn->exec("ALTER TABLE bookings ADD COLUMN created_by_username VARCHAR(255) NULL");
}

try {
  $conn->exec("
    UPDATE bookings
    SET created_by_username = 'Admin Panel'
    WHERE COALESCE(NULLIF(TRIM(created_by_username), ''), '') = ''
  ");
} catch (PDOException $e) {
  // Silent fail
}

if (!db_column_exists($conn, 'segments', 'origin')) {
  $conn->exec("ALTER TABLE segments ADD COLUMN origin VARCHAR(255)");
  $conn->exec("ALTER TABLE segments ADD COLUMN destination VARCHAR(255)");
}

if (!db_column_exists($conn, 'segments', 'route_id')) {
  $conn->exec("ALTER TABLE segments ADD COLUMN route_id INT DEFAULT 0");
}
if (!db_column_exists($conn, 'segments', 'pickup_time')) {
  $conn->exec("ALTER TABLE segments ADD COLUMN pickup_time VARCHAR(5)");
}

if (!db_column_exists($conn, 'routes', 'origin')) {
  $conn->exec("ALTER TABLE routes ADD COLUMN origin VARCHAR(255)");
  $conn->exec("ALTER TABLE routes ADD COLUMN destination VARCHAR(255)");
}

// Add logistic info to luggages if not exists
if (!db_column_exists($conn, 'luggages', 'rute')) {
  $conn->exec("ALTER TABLE luggages ADD COLUMN rute VARCHAR(100)");
  $conn->exec("ALTER TABLE luggages ADD COLUMN tanggal DATE");
  $conn->exec("ALTER TABLE luggages ADD COLUMN unit_id INT");
}

if (!db_column_exists($conn, 'luggages', 'kode_resi')) {
  $conn->exec("ALTER TABLE luggages ADD COLUMN kode_resi VARCHAR(50)");
}
if (!db_column_exists($conn, 'luggages', 'pengirim_id')) {
  $conn->exec("ALTER TABLE luggages ADD COLUMN pengirim_id INT");
}
if (!db_column_exists($conn, 'luggages', 'penerima_id')) {
  $conn->exec("ALTER TABLE luggages ADD COLUMN penerima_id INT");
}
if (!db_column_exists($conn, 'luggages', 'rute_id')) {
  $conn->exec("ALTER TABLE luggages ADD COLUMN rute_id INT");
}
if (!db_column_exists($conn, 'luggages', 'layanan_id')) {
  $conn->exec("ALTER TABLE luggages ADD COLUMN layanan_id INT");
}

// ==================== OTHER CONSTRAINTS & DATA ====================

try {
  @$conn->exec("ALTER TABLE schedules ADD CONSTRAINT unique_schedule_combo UNIQUE (rute, dow, jam)");
} catch (PDOException $e) { /* ignore */ }

// Auto-sync charters
try {
  $syncSql = "UPDATE charters SET layanan = r.duration, bop_price = r.bop_price
              FROM master_carter r
              WHERE UPPER(TRIM(charters.pickup_point)) = UPPER(TRIM(r.origin))
                AND UPPER(TRIM(charters.drop_point)) = UPPER(TRIM(r.destination))
                AND (charters.layanan = 'Regular' OR charters.layanan IS NULL OR charters.layanan = '')";
  $conn->exec($syncSql);
} catch (PDOException $e) {
  // Silent fail
}

// ==================== PERFORMANCE INDEXES ====================
try {
  // For admin booking list group by & filters
  $conn->exec("CREATE INDEX IF NOT EXISTS idx_bookings_trip_date_active ON bookings (tanggal, jam, rute, unit) WHERE status <> 'canceled'");
  $conn->exec("CREATE INDEX IF NOT EXISTS idx_bookings_trip_route_active ON bookings (rute, tanggal, jam, unit, seat) WHERE status <> 'canceled'");
  
  // For driver & assignment lookups in admin booking page
  $conn->exec("CREATE INDEX IF NOT EXISTS idx_trip_assignments_composite ON trip_assignments (rute, tanggal, jam, unit, driver_id)");
  
  // For charter list past/future partition
  $conn->exec("CREATE INDEX IF NOT EXISTS idx_charters_start_date ON charters (start_date)");
  $conn->exec("CREATE INDEX IF NOT EXISTS idx_charters_bop_status ON charters (bop_status)");
  $conn->exec("CREATE INDEX IF NOT EXISTS idx_charters_start_created ON charters (start_date, created_at DESC)");
  
  // For luggage list active vs history queries
  $conn->exec("CREATE INDEX IF NOT EXISTS idx_luggages_status_payment ON luggages (status, payment_status, created_at DESC)");
  $conn->exec("CREATE INDEX IF NOT EXISTS idx_luggages_created_at ON luggages (created_at)");

} catch (PDOException $e) {
  // Silent fail
}

// Create default admin if users table is empty
$userCheck = $conn->query("SELECT id FROM users LIMIT 1");
if ($userCheck->rowCount() === 0) {
    $default_hash = password_hash('admin', PASSWORD_BCRYPT);
    $conn->exec("INSERT INTO users (username, password_hash, fullname) VALUES ('admin', '$default_hash', 'Administrator')");
}

// ==================== AUTO-SYNC SEQUENCES ====================
// Fix PostgreSQL SERIAL sequences to prevent "duplicate key" errors
// This runs on every migration to keep sequences in sync with actual data.
$seq_tables = ['charters','drivers','segments','trip_assignments','bookings',
               'customers','schedules','units','cancellations','luggage_services',
               'luggages','routes','master_carter','users','settings','customer_bagasi','harga_bagasi','bagasi_logs','customer_charter'];
foreach ($seq_tables as $tbl) {
    try {
        $max = (int) $conn->query("SELECT COALESCE(MAX(id), 0) FROM $tbl")->fetchColumn();
        if ($max > 0) {
            $conn->exec("SELECT setval('{$tbl}_id_seq', $max)");
        }
    } catch (PDOException $e) {
        // Table or sequence may not exist yet, skip silently
    }
}

// If this file is run directly via CLI
if (php_sapi_name() === 'cli' && !empty($argv) && basename($argv[0]) === 'db-migrate.php') {
  echo "✓ Database migration completed successfully\n";
  exit(0);
}
