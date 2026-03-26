<?php
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/db-migrate.php';

$auth = requireAdminAuth();
$userLabel = $auth['user'] ?? 'Admin';
$userInitial = strtoupper(substr((string) $userLabel, 0, 1));

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function parse_currency_input(string $value): float
{
    $normalized = preg_replace('/[^0-9,.-]/', '', $value);
    $normalized = str_replace('.', '', $normalized);
    $normalized = str_replace(',', '.', $normalized);
    return (float) $normalized;
}

function parse_route_text(string $value): array
{
    $value = trim(preg_replace('/\s+/', ' ', $value));
    if ($value === '') {
        return ['', ''];
    }

    foreach (['->', ' - ', ' -- ', ' to ', ' ke '] as $separator) {
        if (stripos($value, $separator) !== false) {
            $parts = preg_split('/' . preg_quote($separator, '/') . '/i', $value, 2);
            $from = trim($parts[0] ?? '');
            $to = trim($parts[1] ?? '');
            return [$from, $to];
        }
    }

    if (strpos($value, '-') !== false) {
        $parts = explode('-', $value, 2);
        return [trim($parts[0] ?? ''), trim($parts[1] ?? '')];
    }

    return [$value, ''];
}

$units = $conn->query("SELECT id, nopol, merek, kapasitas FROM units ORDER BY nopol")->fetchAll();
$drivers = $conn->query("SELECT nama FROM drivers ORDER BY nama")->fetchAll();
$charterRoutes = $conn->query("SELECT name, origin, destination, duration, rental_price FROM master_carter ORDER BY name")->fetchAll();

$form = [
    'name' => '',
    'phone' => '',
    'email' => '',
    'route_text' => '',
    'start_date' => date('Y-m-d'),
    'duration_days' => '3',
    'departure_time' => '08:30',
    'bus_type' => 'Big Bus',
    'unit_id' => (string) ($units[0]['id'] ?? ''),
    'driver_name' => '',
    'price' => '',
    'down_payment' => '',
    'payment_status' => 'DP',
];

$errors = [];
if (!empty($_GET['error'])) {
    $errors[] = trim((string) $_GET['error']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $default) {
        $form[$key] = trim((string) ($_POST[$key] ?? $default));
    }

    $name = strtoupper($form['name']);
    $phone = preg_replace('/\s+/', '', $form['phone']);
    $routeText = $form['route_text'];
    $startDate = $form['start_date'];
    $durationDays = max(1, (int) $form['duration_days']);
    $departureTime = $form['departure_time'] ?: '08:30';
    $busType = $form['bus_type'] ?: 'Big Bus';
    $unitId = (int) $form['unit_id'];
    $driverName = $form['driver_name'];
    $price = parse_currency_input($form['price']);

    if ($name === '') {
        $errors[] = 'Nama lengkap wajib diisi.';
    }
    if ($phone === '') {
        $errors[] = 'Nomor telepon wajib diisi.';
    }
    if ($routeText === '') {
        $errors[] = 'Rute perjalanan wajib diisi.';
    }
    if ($startDate === '') {
        $errors[] = 'Tanggal keberangkatan wajib diisi.';
    }
    if ($unitId <= 0) {
        $errors[] = 'Unit kendaraan wajib dipilih.';
    }

    if (!$errors) {
        [$pickupPoint, $dropPoint] = parse_route_text($routeText);
        $endDate = date('Y-m-d', strtotime($startDate . ' +' . max(0, $durationDays - 1) . ' days'));

        $stmt = $conn->prepare("
            INSERT INTO charters (
                name,
                company_name,
                phone,
                start_date,
                end_date,
                departure_time,
                pickup_point,
                drop_point,
                unit_id,
                driver_name,
                price,
                layanan,
                bop_price,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

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

            header('Location: admin.php?booking_mode=charters#bookings');
            exit;
        } catch (Throwable $e) {
            $errors[] = 'Gagal menyimpan carter: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Tambah Carter | Admin Panel</title>
  <meta name="description" content="Tambah data carter dari Admin Panel Cahaya Bone dengan form yang cepat, rapi, dan siap diproses.">
  <meta name="theme-color" content="#f97316">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Cahaya Bone">
  <meta property="og:title" content="Tambah Carter | Admin Panel">
  <meta property="og:description" content="Tambah data carter dari Admin Panel Cahaya Bone dengan form yang cepat, rapi, dan siap diproses.">
  <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
  <link rel="shortcut icon" href="assets/images/favicon.svg">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <style>
    :root {
      color-scheme: dark;
      --surface-dim: #111319;
      --surface-low: #191b22;
      --surface-lowest: #0c0e14;
      --surface-high: #282a30;
      --outline: #584237;
      --primary: #f97316;
      --text: #e2e2eb;
      --muted: #e0c0b1;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      background: var(--surface-dim);
      color: var(--text);
      font-family: "Space Grotesk", sans-serif;
    }

    .material-symbols-outlined {
      font-variation-settings: "FILL" 0, "wght" 400, "GRAD" 0, "opsz" 24;
      vertical-align: middle;
    }

    .page-shell {
      max-width: 980px;
      margin: 0 auto;
      padding: 24px 24px 120px;
    }

    .topbar {
      position: sticky;
      top: 0;
      z-index: 20;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 18px 24px;
      background: rgba(17, 19, 25, 0.92);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(88, 66, 55, 0.2);
    }

    .topbar-left,
    .topbar-right {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .icon-link,
    .type-chip {
      border: 0;
      background: transparent;
      color: inherit;
      text-decoration: none;
    }

    .icon-link {
      width: 42px;
      height: 42px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      background: rgba(40, 42, 48, 0.7);
      color: var(--primary);
    }

    .brand-title,
    .section-title {
      font-family: "Plus Jakarta Sans", sans-serif;
      font-weight: 800;
    }

    .brand-title {
      font-size: 0.82rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--primary);
    }

    .brand-mark {
      font-size: 1.2rem;
      font-family: "Plus Jakarta Sans", sans-serif;
      font-weight: 900;
      color: var(--primary);
    }

    .avatar {
      width: 36px;
      height: 36px;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: var(--surface-high);
      border: 1px solid rgba(167, 139, 125, 0.15);
      font-family: "Plus Jakarta Sans", sans-serif;
      font-weight: 800;
    }

    .page-lead {
      display: flex;
      justify-content: space-between;
      align-items: end;
      gap: 20px;
      margin: 22px 0 10px;
    }

    .page-kicker {
      font-family: "Plus Jakarta Sans", sans-serif;
      font-size: 0.7rem;
      font-weight: 800;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: rgba(224, 192, 177, 0.6);
      margin-bottom: 8px;
    }

    .page-title {
      margin: 0;
      font-size: clamp(2rem, 3vw, 3rem);
      letter-spacing: -0.04em;
      line-height: 0.98;
    }

    .page-note {
      max-width: 560px;
      margin: 10px 0 0;
      color: rgba(224, 192, 177, 0.72);
      font-size: 0.92rem;
      line-height: 1.65;
    }

    .page-status {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 12px 16px;
      border-radius: 16px;
      background: rgba(25, 27, 34, 0.9);
      border: 1px solid rgba(88, 66, 55, 0.18);
      color: rgba(224, 192, 177, 0.82);
      font-size: 0.78rem;
    }

    .page-status strong {
      color: var(--text);
      font-family: "Plus Jakarta Sans", sans-serif;
      font-size: 0.86rem;
    }

    .content-shell {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 300px;
      gap: 22px;
      align-items: start;
      margin-top: 24px;
    }

    .content-stack {
      display: grid;
      gap: 24px;
    }

    .section-head {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
    }

    .section-head-line {
      width: 32px;
      height: 2px;
      background: var(--primary);
    }

    .section-title {
      font-size: 0.82rem;
      text-transform: uppercase;
      letter-spacing: 0.22em;
      color: rgba(224, 192, 177, 0.78);
    }

    .panel {
      background: var(--surface-low);
      border: 1px solid rgba(88, 66, 55, 0.18);
      border-radius: 22px;
      padding: 28px;
    }

    .grid-2,
    .grid-3 {
      display: grid;
      gap: 18px;
    }

    .grid-2 {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .grid-3 {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .col-span-2 {
      grid-column: span 2;
    }

    .field {
      display: grid;
      gap: 8px;
    }

    .field label {
      font-family: "Plus Jakarta Sans", sans-serif;
      font-size: 0.64rem;
      font-weight: 700;
      letter-spacing: 0.16em;
      text-transform: uppercase;
      color: rgba(224, 192, 177, 0.72);
    }

    .input,
    .select,
    .counter-input {
      width: 100%;
      min-height: 52px;
      border: 0;
      border-radius: 14px;
      background: var(--surface-lowest);
      color: var(--text);
      padding: 0 16px;
      font: inherit;
    }

    .input::placeholder {
      color: rgba(224, 192, 177, 0.4);
    }

    .input:focus,
    .select:focus,
    .counter-input:focus {
      outline: 2px solid rgba(249, 115, 22, 0.2);
    }

    .input.with-icon {
      padding-left: 48px;
    }

    .field-icon-wrap {
      position: relative;
    }

    .field-icon {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--primary);
      font-size: 1.1rem;
    }

    .counter-wrap {
      display: flex;
      align-items: stretch;
      overflow: hidden;
      background: var(--surface-lowest);
      border-radius: 14px;
    }

    .counter-btn {
      width: 54px;
      border: 0;
      background: transparent;
      color: var(--primary);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
    }

    .counter-input {
      text-align: center;
      border-radius: 0;
      background: transparent;
      min-height: 52px;
    }

    .bus-type-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
    }

    .type-chip {
      cursor: pointer;
      min-height: 88px;
      border-radius: 16px;
      border: 1px solid rgba(88, 66, 55, 0.24);
      background: var(--surface-lowest);
      color: rgba(224, 192, 177, 0.82);
      display: grid;
      place-items: center;
      gap: 6px;
      padding: 12px;
      text-align: center;
      transition: 0.2s ease;
    }

    .type-chip.active {
      background: rgba(249, 115, 22, 0.12);
      color: var(--primary);
      border-color: rgba(249, 115, 22, 0.55);
    }

    .type-chip span:last-child {
      font-size: 0.68rem;
      font-family: "Plus Jakarta Sans", sans-serif;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
    }

    .route-presets {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 4px;
    }

    .route-preset {
      border: 1px solid rgba(88, 66, 55, 0.22);
      background: rgba(12, 14, 20, 0.85);
      color: rgba(224, 192, 177, 0.8);
      border-radius: 999px;
      padding: 10px 14px;
      font-family: "Plus Jakarta Sans", sans-serif;
      font-size: 0.7rem;
      font-weight: 700;
      letter-spacing: 0.05em;
      cursor: pointer;
    }

    .route-preset:hover {
      border-color: rgba(249, 115, 22, 0.4);
      color: var(--primary);
    }

    .payment-options {
      display: flex;
      flex-wrap: wrap;
      gap: 18px;
    }

    .radio-option {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      font-size: 0.8rem;
      color: rgba(224, 192, 177, 0.86);
      cursor: pointer;
    }

    .radio-option input {
      accent-color: var(--primary);
    }

    .error-box {
      border-radius: 16px;
      padding: 14px 16px;
      background: rgba(147, 0, 10, 0.16);
      border: 1px solid rgba(255, 180, 171, 0.24);
      color: #ffdad6;
      font-size: 0.88rem;
      line-height: 1.5;
    }

    .error-box ul {
      margin: 0;
      padding-left: 18px;
    }

    .summary-card {
      position: sticky;
      top: 96px;
      display: grid;
      gap: 18px;
      background: linear-gradient(180deg, rgba(25, 27, 34, 0.98) 0%, rgba(17, 19, 25, 0.98) 100%);
      border: 1px solid rgba(88, 66, 55, 0.18);
      border-radius: 24px;
      padding: 22px;
    }

    .summary-kicker {
      margin: 0;
      font-family: "Plus Jakarta Sans", sans-serif;
      font-size: 0.68rem;
      font-weight: 800;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: rgba(224, 192, 177, 0.52);
    }

    .summary-title {
      margin: 6px 0 0;
      font-size: 1.45rem;
      letter-spacing: -0.04em;
      line-height: 1.05;
    }

    .summary-subtle {
      margin: 8px 0 0;
      color: rgba(224, 192, 177, 0.68);
      font-size: 0.86rem;
      line-height: 1.6;
    }

    .summary-stack {
      display: grid;
      gap: 12px;
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      gap: 14px;
      align-items: start;
      padding: 12px 0;
      border-bottom: 1px solid rgba(88, 66, 55, 0.14);
    }

    .summary-row:last-child {
      border-bottom: 0;
      padding-bottom: 0;
    }

    .summary-label {
      color: rgba(224, 192, 177, 0.55);
      font-family: "Plus Jakarta Sans", sans-serif;
      font-size: 0.68rem;
      font-weight: 800;
      letter-spacing: 0.14em;
      text-transform: uppercase;
    }

    .summary-value {
      text-align: right;
      color: var(--text);
      font-size: 0.9rem;
      font-weight: 600;
      line-height: 1.45;
    }

    .summary-value.price {
      color: var(--primary);
      font-family: "Plus Jakarta Sans", sans-serif;
      font-size: 1.15rem;
      font-weight: 800;
      letter-spacing: -0.03em;
    }

    .actions {
      display: flex;
      gap: 14px;
      padding-top: 6px;
    }

    .btn {
      min-height: 58px;
      border-radius: 16px;
      border: 0;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      padding: 0 24px;
      text-decoration: none;
      cursor: pointer;
      font-family: "Plus Jakarta Sans", sans-serif;
      font-size: 0.8rem;
      font-weight: 800;
      letter-spacing: 0.14em;
      text-transform: uppercase;
    }

    .btn-primary {
      flex: 1 1 auto;
      background: var(--primary);
      color: #341100;
      box-shadow: 0 18px 40px rgba(249, 115, 22, 0.2);
    }

    .btn-secondary {
      min-width: 170px;
      background: transparent;
      border: 1px solid rgba(88, 66, 55, 0.24);
      color: rgba(224, 192, 177, 0.8);
    }

    .helper-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
    }

    @media (max-width: 900px) {
      .page-lead,
      .content-shell,
      .grid-2,
      .grid-3,
      .helper-grid,
      .bus-type-grid {
        grid-template-columns: 1fr;
      }

      .page-lead {
        flex-direction: column;
        align-items: start;
      }

      .col-span-2 {
        grid-column: auto;
      }

      .actions {
        flex-direction: column;
      }

      .btn-secondary {
        min-width: 0;
      }

      .page-shell {
        padding-inline: 18px;
      }

      .summary-card {
        position: static;
      }
    }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="topbar-left">
      <a class="icon-link" href="admin.php?booking_mode=charters#bookings" aria-label="Kembali ke daftar carter">
        <span class="material-symbols-outlined">arrow_back</span>
      </a>
      <div class="brand-title">Tambah Carter</div>
    </div>
    <div class="topbar-right">
      <span class="brand-mark">CM</span>
      <span class="avatar"><?php echo h($userInitial); ?></span>
    </div>
  </header>

  <main class="page-shell">
    <section class="page-lead">
      <div>
        <div class="page-kicker">Fleet Operations</div>
        <h1 class="page-title">Tambah Carter</h1>
        <p class="page-note">Kita buat alur input carter lebih cepat: isi penyewa, pilih rute, atur unit, lalu simpan tanpa keluar dari ritme operasional.</p>
      </div>
      <div class="page-status">
        <span class="material-symbols-outlined" style="color: var(--primary);">directions_bus</span>
        <div>
          <div class="page-kicker" style="margin:0 0 4px;">Mode Aktif</div>
          <strong>Carter Management</strong>
        </div>
      </div>
    </section>

    <?php if ($errors): ?>
      <div class="error-box">
        <ul>
          <?php foreach ($errors as $error): ?>
            <li><?php echo h($error); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="content-shell">
    <form method="post" action="admin.php?action=create_charter" class="content-stack" novalidate>
      <section>
        <div class="section-head">
          <div class="section-head-line"></div>
          <h2 class="section-title">Identitas Penyewa</h2>
        </div>
        <div class="panel grid-2">
          <div class="field">
            <label for="name">Nama Lengkap</label>
            <input id="name" name="name" class="input" type="text" placeholder="Contoh: Budi Santoso" value="<?php echo h($form['name']); ?>" required>
          </div>
          <div class="field">
            <label for="phone">Nomor Telepon</label>
            <input id="phone" name="phone" class="input" type="tel" placeholder="+62 812-xxxx-xxxx" value="<?php echo h($form['phone']); ?>" required>
          </div>
          <div class="field col-span-2">
            <label for="email">Email</label>
            <input id="email" name="email" class="input" type="email" placeholder="budi@example.com" value="<?php echo h($form['email']); ?>">
          </div>
        </div>
      </section>

      <section>
        <div class="section-head">
          <div class="section-head-line"></div>
          <h2 class="section-title">Detail Carter</h2>
        </div>
        <div class="panel grid-2">
          <div class="field col-span-2">
            <label for="route_text">Rute Perjalanan</label>
            <div class="field-icon-wrap">
              <span class="material-symbols-outlined field-icon">route</span>
              <input id="route_text" name="route_text" class="input with-icon" list="charter-route-list" type="text" placeholder="Jakarta - Yogyakarta (PP)" value="<?php echo h($form['route_text']); ?>" required>
            </div>
            <?php if ($charterRoutes): ?>
              <div class="route-presets">
                <?php foreach (array_slice($charterRoutes, 0, 4) as $route): ?>
                  <?php
                    $presetRoute = trim(($route['origin'] ?? '') . ' - ' . ($route['destination'] ?? ''));
                    $presetDuration = trim((string) ($route['duration'] ?? ''));
                    $presetPrice = (string) ($route['rental_price'] ?? '');
                  ?>
                  <button
                    type="button"
                    class="route-preset"
                    data-route-preset="<?php echo h($presetRoute); ?>"
                    data-route-duration="<?php echo h($presetDuration); ?>"
                    data-route-price="<?php echo h($presetPrice); ?>">
                    <?php echo h($presetRoute); ?>
                  </button>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <datalist id="charter-route-list">
              <?php foreach ($charterRoutes as $route): ?>
                <option
                  value="<?php echo h($route['origin'] . ' - ' . $route['destination']); ?>"
                  data-duration="<?php echo h($route['duration'] ?? ''); ?>"
                  data-price="<?php echo h((string) ($route['rental_price'] ?? '')); ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>

          <div class="field">
            <label for="start_date">Tanggal Keberangkatan</label>
            <input id="start_date" name="start_date" class="input" type="date" value="<?php echo h($form['start_date']); ?>" required>
          </div>

          <div class="field">
            <label for="departure_time">Jam Berangkat</label>
            <input id="departure_time" name="departure_time" class="input" type="time" value="<?php echo h($form['departure_time']); ?>">
          </div>

          <div class="field">
            <label for="duration_days">Durasi (Hari)</label>
            <div class="counter-wrap">
              <button class="counter-btn" type="button" data-counter-step="-1"><span class="material-symbols-outlined">remove</span></button>
              <input id="duration_days" name="duration_days" class="counter-input" type="number" min="1" value="<?php echo h($form['duration_days']); ?>">
              <button class="counter-btn" type="button" data-counter-step="1"><span class="material-symbols-outlined">add</span></button>
            </div>
          </div>

          <div class="field">
            <label for="unit_id">Unit Kendaraan</label>
            <select id="unit_id" name="unit_id" class="select" required>
              <option value="">Pilih unit kendaraan</option>
              <?php foreach ($units as $unit): ?>
                <?php $unitLabel = trim(($unit['nopol'] ?? '-') . ' - ' . ($unit['merek'] ?? '-') . ' (' . ($unit['kapasitas'] ?? '-') . ')'); ?>
                <option value="<?php echo h((string) $unit['id']); ?>" <?php echo (string) $unit['id'] === (string) $form['unit_id'] ? 'selected' : ''; ?>>
                  <?php echo h($unitLabel); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="field col-span-2">
            <label>Tipe Bus</label>
            <input type="hidden" id="bus_type" name="bus_type" value="<?php echo h($form['bus_type']); ?>">
            <div class="bus-type-grid">
              <?php foreach (['Big Bus' => 'directions_bus', 'Medium Bus' => 'airport_shuttle', 'Mini Bus' => 'minor_crash'] as $label => $icon): ?>
                <button type="button" class="type-chip<?php echo $form['bus_type'] === $label ? ' active' : ''; ?>" data-bus-type="<?php echo h($label); ?>">
                  <span class="material-symbols-outlined"><?php echo h($icon); ?></span>
                  <span><?php echo h($label); ?></span>
                </button>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </section>

      <section>
        <div class="section-head">
          <div class="section-head-line"></div>
          <h2 class="section-title">Pembayaran</h2>
        </div>
        <div class="panel">
          <div class="helper-grid">
            <div class="field">
              <label for="price">Total Harga</label>
              <input id="price" name="price" class="input" type="text" inputmode="numeric" value="<?php echo h($form['price']); ?>" placeholder="12.500.000">
            </div>
            <div class="field">
              <label for="down_payment">Uang Muka (DP)</label>
              <input id="down_payment" name="down_payment" class="input" type="text" inputmode="numeric" value="<?php echo h($form['down_payment']); ?>" placeholder="0">
            </div>
            <div class="field">
              <label for="driver_name">Driver</label>
              <select id="driver_name" name="driver_name" class="select">
                <option value="">Pilih driver</option>
                <?php foreach ($drivers as $driver): ?>
                  <option value="<?php echo h($driver['nama']); ?>" <?php echo $form['driver_name'] === $driver['nama'] ? 'selected' : ''; ?>>
                    <?php echo h($driver['nama']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field">
              <label>Status Pembayaran</label>
              <div class="payment-options">
                <?php foreach (['LUNAS', 'DP', 'BELUM BAYAR'] as $status): ?>
                  <label class="radio-option">
                    <input type="radio" name="payment_status" value="<?php echo h($status); ?>" <?php echo $form['payment_status'] === $status ? 'checked' : ''; ?>>
                    <span><?php echo h($status); ?></span>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="actions">
        <button type="submit" class="btn btn-primary">
          <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;">save</span>
          Konfirmasi &amp; Simpan
        </button>
        <a href="admin.php?booking_mode=charters#bookings" class="btn btn-secondary">Batalkan</a>
      </div>
    </form>
    <aside class="summary-card">
      <div>
        <p class="summary-kicker">Live Summary</p>
        <h2 class="summary-title">Preview Carter</h2>
        <p class="summary-subtle">Ringkasan ini ikut berubah saat form diisi, jadi kita bisa cek cepat sebelum simpan.</p>
      </div>

      <div class="summary-stack">
        <div class="summary-row">
          <span class="summary-label">Penyewa</span>
          <span class="summary-value" id="summaryName"><?php echo h($form['name'] ?: 'Belum diisi'); ?></span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Rute</span>
          <span class="summary-value" id="summaryRoute"><?php echo h($form['route_text'] ?: 'Belum diisi'); ?></span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Jadwal</span>
          <span class="summary-value" id="summarySchedule"><?php echo h(($form['start_date'] ?: '-') . ' • ' . ($form['departure_time'] ?: '-')); ?></span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Durasi</span>
          <span class="summary-value" id="summaryDuration"><?php echo h(($form['duration_days'] ?: '0') . ' Hari'); ?></span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Armada</span>
          <span class="summary-value" id="summaryBusType"><?php echo h($form['bus_type'] ?: '-'); ?></span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Unit / Driver</span>
          <span class="summary-value" id="summaryOps"><?php echo h(($form['unit_id'] ?: '-') . ' • ' . ($form['driver_name'] ?: 'TBD')); ?></span>
        </div>
        <div class="summary-row">
          <span class="summary-label">Harga</span>
          <span class="summary-value price" id="summaryPrice"><?php echo h($form['price'] !== '' ? 'Rp ' . $form['price'] : 'Rp 0'); ?></span>
        </div>
      </div>
    </aside>
    </div>
  </main>

  <script>
    function formatToRupiah(value) {
      const digits = String(value || '').replace(/\D/g, '');
      if (!digits) return '';
      return new Intl.NumberFormat('id-ID').format(Number(digits));
    }

    function getSelectedUnitLabel() {
      const unitSelect = document.getElementById('unit_id');
      if (!unitSelect) return '-';
      const option = unitSelect.options[unitSelect.selectedIndex];
      return option && option.value ? option.textContent.trim() : '-';
    }

    function syncSummary() {
      const name = document.getElementById('name')?.value.trim() || 'Belum diisi';
      const route = document.getElementById('route_text')?.value.trim() || 'Belum diisi';
      const startDate = document.getElementById('start_date')?.value || '-';
      const departureTime = document.getElementById('departure_time')?.value || '-';
      const duration = document.getElementById('duration_days')?.value || '0';
      const busType = document.getElementById('bus_type')?.value || '-';
      const driver = document.getElementById('driver_name')?.value || 'TBD';
      const unit = getSelectedUnitLabel();
      const priceRaw = document.getElementById('price')?.value || '';
      const price = formatToRupiah(priceRaw);

      document.getElementById('summaryName').textContent = name;
      document.getElementById('summaryRoute').textContent = route;
      document.getElementById('summarySchedule').textContent = startDate + ' • ' + departureTime;
      document.getElementById('summaryDuration').textContent = duration + ' Hari';
      document.getElementById('summaryBusType').textContent = busType;
      document.getElementById('summaryOps').textContent = unit + ' • ' + driver;
      document.getElementById('summaryPrice').textContent = 'Rp ' + (price || '0');
    }

    document.querySelectorAll('[data-counter-step]').forEach(function (button) {
      button.addEventListener('click', function () {
        const input = document.getElementById('duration_days');
        const current = parseInt(input.value || '1', 10);
        const next = Math.max(1, current + parseInt(button.getAttribute('data-counter-step'), 10));
        input.value = next;
        syncSummary();
      });
    });

    document.querySelectorAll('[data-bus-type]').forEach(function (button) {
      button.addEventListener('click', function () {
        document.getElementById('bus_type').value = button.getAttribute('data-bus-type');
        document.querySelectorAll('[data-bus-type]').forEach(function (item) {
          item.classList.remove('active');
        });
        button.classList.add('active');
        syncSummary();
      });
    });

    const routeInput = document.getElementById('route_text');
    const durationInput = document.getElementById('duration_days');
    const priceInput = document.getElementById('price');
    const downPaymentInput = document.getElementById('down_payment');
    const routeOptions = Array.from(document.querySelectorAll('#charter-route-list option'));

    document.querySelectorAll('[data-route-preset]').forEach(function (button) {
      button.addEventListener('click', function () {
        routeInput.value = button.getAttribute('data-route-preset') || '';
        if (button.getAttribute('data-route-duration')) {
          durationInput.value = button.getAttribute('data-route-duration');
        }
        if (button.getAttribute('data-route-price')) {
          priceInput.value = formatToRupiah(button.getAttribute('data-route-price'));
        }
        syncSummary();
      });
    });

    routeInput.addEventListener('change', function () {
      const selected = routeOptions.find(function (option) {
        return option.value.trim().toLowerCase() === routeInput.value.trim().toLowerCase();
      });

      if (selected) {
        if (selected.dataset.duration) {
          durationInput.value = selected.dataset.duration;
        }
        if (selected.dataset.price) {
          priceInput.value = formatToRupiah(selected.dataset.price);
        }
      }
      syncSummary();
    });

    ['name', 'start_date', 'departure_time', 'duration_days', 'unit_id', 'driver_name', 'route_text'].forEach(function (id) {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener('input', syncSummary);
      el.addEventListener('change', syncSummary);
    });

    priceInput.addEventListener('input', function () {
      const caretAtEnd = priceInput.selectionStart === priceInput.value.length;
      priceInput.value = formatToRupiah(priceInput.value);
      if (caretAtEnd) {
        priceInput.setSelectionRange(priceInput.value.length, priceInput.value.length);
      }
      syncSummary();
    });

    downPaymentInput.addEventListener('input', function () {
      const caretAtEnd = downPaymentInput.selectionStart === downPaymentInput.value.length;
      downPaymentInput.value = formatToRupiah(downPaymentInput.value);
      if (caretAtEnd) {
        downPaymentInput.setSelectionRange(downPaymentInput.value.length, downPaymentInput.value.length);
      }
    });

    syncSummary();
  </script>
</body>
</html>

