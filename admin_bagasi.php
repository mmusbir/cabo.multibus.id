<?php
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/db-migrate.php';

$auth = requireAdminAuth();
$userLabel = $auth['user'] ?? 'Admin';
$userInitial = strtoupper(substr((string) $userLabel, 0, 1));

// Filtering & Searching Logic
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$params = [];
$whereClauses = ["1=1"];

if ($status_filter !== 'all') {
    $whereClauses[] = "l.status = ?";
    $params[] = $status_filter;
}

if ($search !== '') {
    $whereClauses[] = "(l.sender_name LIKE ? OR l.receiver_name LIKE ? OR l.sender_phone LIKE ?)";
    $likeSearch = "%$search%";
    $params[] = $likeSearch;
    $params[] = $likeSearch;
    $params[] = $likeSearch;
}

$whereSql = implode(" AND ", $whereClauses);
$query = "SELECT l.*, s.name as service_name 
          FROM luggages l 
          LEFT JOIN luggage_services s ON l.service_id = s.id 
          WHERE $whereSql
          ORDER BY l.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$luggages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Services for Modal
$services = $conn->query("SELECT * FROM luggage_services ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

function getStatusConfig($status) {
    switch ($status) {
        case 'pending':
            return ['label' => 'Pending', 'bg' => 'bg-orange-500/10', 'text' => 'text-orange-500', 'dot' => 'bg-orange-500', 'border' => 'border-orange-500/20'];
        case 'active':
            return ['label' => 'Terangkut', 'bg' => 'bg-emerald-500/10', 'text' => 'text-emerald-500', 'dot' => 'bg-emerald-500', 'border' => 'border-emerald-500/20'];
        case 'canceled':
            return ['label' => 'Dibatalkan', 'bg' => 'bg-red-500/10', 'text' => 'text-red-500', 'dot' => 'bg-red-500', 'border' => 'border-red-500/20'];
        default: // finished or lunas
            return ['label' => 'Selesai', 'bg' => 'bg-blue-500/10', 'text' => 'text-blue-400', 'dot' => 'bg-blue-400', 'border' => 'border-blue-500/20'];
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="light" data-default-theme="light">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no" name="viewport">
  <title>Manajemen Bagasi - Data Bagasi</title>
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
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <script id="tailwind-config">
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "surface-dim": "var(--surface-dim, #111319)",
            "surface-container-low": "var(--surface-container-low, #191b22)",
            "surface-container-high": "var(--surface-container-high, #282a30)",
            "on-surface": "var(--on-surface, #e2e2eb)",
            "on-surface-variant": "var(--on-surface-variant, #e0c0b1)",
            "outline-variant": "var(--outline-variant, #584237)",
            "primary-container": "var(--primary-container, #f97316)",
          },
          fontFamily: {
            "plus-jakarta": ["Plus Jakarta Sans"],
            "space-grotesk": ["Space Grotesk"],
          }
        },
      },
    }
  </script>
  <link href="https://fonts.googleapis.com/css2?family=Header+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <link rel="stylesheet" href="assets/css/admin-bootstrap.css?v=28">
  <link rel="stylesheet" href="assets/css/theme-toggle.css?v=<?= time() ?>">
  <style>
    :root {
      --surface-dim: #111319;
      --surface-container-low: #191b22;
      --surface-container-high: #282a30;
      --on-surface: #e2e2eb;
      --on-surface-variant: #e0c0b1;
      --outline-variant: rgba(88, 66, 55, 0.2);
      --primary-container: #f97316;
    }
    html[data-theme="light"] {
      --surface-dim: #f8fafc;
      --surface-container-low: #ffffff;
      --surface-container-high: #f1f5f9;
      --on-surface: #0f172a;
      --on-surface-variant: #64748b;
      --outline-variant: rgba(148, 163, 184, 0.18);
      --primary-container: #f97316;
    }
    body {
      background-color: var(--surface-dim);
      color: var(--on-surface);
      font-family: 'Space Grotesk', sans-serif;
    }
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    .scrollbar-hide::-webkit-scrollbar { display: none; }
    .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    .admin-bootstrap-container { max-width: 1400px; padding-top: 2rem; }
    
    .filter-btn.active {
        background-color: var(--primary-container);
        color: white;
    }
    
    /* Input Styling for Light/Dark */
    .form-control-custom {
        background-color: var(--surface-container-high);
        border: 1px solid var(--outline-variant);
        color: var(--on-surface);
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
    }
    .form-control-custom:focus {
        background-color: var(--surface-container-high);
        color: var(--on-surface);
        border-color: var(--primary-container);
        box-shadow: 0 0 0 0.25rem rgba(249, 115, 22, 0.1);
    }
    .modal-content-custom {
        background-color: var(--surface-dim);
        border: 1px solid var(--outline-variant);
        border-radius: 1.25rem;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }
  </style>
</head>
<body class="admin-bootstrap-page app-admin app-bagasi bg-surface-dim">

  <div id="toast" class="toast" role="status" aria-live="polite"></div>

  <?php include 'includes/navbar.php'; ?>

  <main class="container container-fluid admin-bootstrap-container">
    <div class="layout admin-bootstrap-grid">
      <div class="left admin-main-column">
        
        <div class="pb-32">
          <!-- Action Header -->
          <section class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
            <div>
              <p class="text-xs uppercase tracking-[0.2em] text-on-surface-variant mb-2 font-bold">Logistics Operations</p>
              <h2 class="text-4xl font-extrabold tracking-tighter text-on-surface">Data Bagasi</h2>
            </div>
            <button class="bg-primary-container text-white font-bold py-3 px-6 rounded-lg flex items-center gap-2 active:scale-95 duration-200 shadow-lg shadow-orange-500/20" data-bs-toggle="modal" data-bs-target="#modalTambahBagasi">
              <span class="material-symbols-outlined font-bold">add</span>
              <span class="uppercase tracking-wider text-sm">Tambah Bagasi</span>
            </button>
          </section>

          <!-- Filters & Search -->
          <section class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8">
            <div class="lg:col-span-8 flex flex-wrap gap-2 p-1.5 bg-surface-container-low rounded-xl border border-outline-variant">
              <a href="?status=all&search=<?= urlencode($search) ?>" class="px-5 py-2 rounded-lg text-sm font-bold uppercase tracking-wider transition-all no-underline filter-btn <?= $status_filter === 'all' ? 'active' : 'text-on-surface-variant hover:bg-surface-container-high' ?>">Semua</a>
              <a href="?status=pending&search=<?= urlencode($search) ?>" class="px-5 py-2 rounded-lg text-sm font-bold uppercase tracking-wider transition-all no-underline filter-btn <?= $status_filter === 'pending' ? 'active' : 'text-on-surface-variant hover:bg-surface-container-high' ?>">Pending</a>
              <a href="?status=active&search=<?= urlencode($search) ?>" class="px-5 py-2 rounded-lg text-sm font-bold uppercase tracking-wider transition-all no-underline filter-btn <?= $status_filter === 'active' ? 'active' : 'text-on-surface-variant hover:bg-surface-container-high' ?>">Terangkut</a>
              <a href="?status=finished&search=<?= urlencode($search) ?>" class="px-5 py-2 rounded-lg text-sm font-bold uppercase tracking-wider transition-all no-underline filter-btn <?= $status_filter === 'finished' ? 'active' : 'text-on-surface-variant hover:bg-surface-container-high' ?>">Selesai</a>
            </div>
            <form action="" method="GET" class="lg:col-span-4">
              <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
              <div class="relative group">
                <span class="material-symbols-outlined absolute left-4 top-1/2 -translate-y-1/2 text-on-surface-variant group-focus-within:text-orange-500 transition-colors">search</span>
                <input name="search" value="<?= htmlspecialchars($search) ?>" class="w-full bg-surface-container-low border-none rounded-xl py-3.5 pl-12 pr-4 text-on-surface placeholder:text-on-surface-variant/50 focus:ring-2 focus:ring-orange-500/30 transition-all font-space-grotesk" placeholder="Cari Nama Pengirim/Penerima..." type="text"/>
              </div>
            </form>
          </section>

          <!-- Data Grid -->
          <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6" id="luggage-grid">
            <?php if (empty($luggages)): ?>
                <div class="col-span-full py-20 flex flex-col items-center justify-center opacity-50">
                    <span class="material-symbols-outlined text-6xl mb-4">inventory_2</span>
                    <p class="font-bold text-lg">Tidak ada data bagasi ditemukan</p>
                </div>
            <?php else: ?>
                <?php foreach ($luggages as $l): ?>
                    <?php 
                        $cfg = getStatusConfig($l['status']); 
                        $lug_id = "BGS-" . date('Ymd', strtotime($l['created_at'])) . "-" . str_pad($l['id'], 3, '0', STR_PAD_LEFT);
                    ?>
                    <div class="bg-surface-container-high rounded-xl p-6 border border-outline-variant hover:border-orange-500/30 transition-all group relative overflow-hidden">
                      <div class="absolute top-0 right-0 p-4">
                        <span class="bg-orange-500/5 text-orange-500 text-[10px] font-bold px-2 py-1 rounded border border-orange-500/10 tracking-tighter">ID: #<?= $l['id'] ?></span>
                      </div>
                      <div class="mb-4">
                        <code class="font-mono text-sm text-orange-500 tracking-tight"><?= $lug_id ?></code>
                        <h3 class="text-xl font-bold text-on-surface mt-1"><?= htmlspecialchars($l['sender_name']) ?></h3>
                        <p class="text-[11px] text-on-surface-variant"><span class="material-symbols-outlined text-[10px]">call</span> <?= htmlspecialchars($l['sender_phone']) ?></p>
                      </div>
                      <div class="space-y-4">
                        <div class="flex items-start gap-3">
                          <div class="flex flex-col items-center pt-1">
                            <div class="w-2 h-2 rounded-full bg-orange-500"></div>
                            <div class="w-0.5 h-6 border-l border-dashed border-outline-variant my-1"></div>
                            <div class="w-2 h-2 rounded-full border border-orange-500"></div>
                          </div>
                          <div class="flex-1">
                            <p class="text-[10px] uppercase tracking-widest text-on-surface-variant font-bold leading-none mb-1">Kepada & Alamat</p>
                            <p class="text-sm font-medium text-on-surface"><?= htmlspecialchars($l['receiver_name']) ?></p>
                            <p class="text-[11px] text-on-surface-variant line-clamp-1"><?= htmlspecialchars($l['receiver_address'] ?: '-') ?></p>
                          </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 pt-2 border-t border-outline-variant">
                          <div>
                            <p class="text-[10px] uppercase tracking-widest text-on-surface-variant font-bold mb-1">Item Type</p>
                            <div class="flex items-center gap-2">
                              <span class="material-symbols-outlined text-lg text-on-surface-variant">package_2</span>
                              <span class="text-sm font-bold text-on-surface"><?= htmlspecialchars($l['service_name'] ?: 'Paket') ?></span>
                            </div>
                          </div>
                          <div>
                            <p class="text-[10px] uppercase tracking-widest text-on-surface-variant font-bold mb-1">Quantity</p>
                            <span class="font-mono text-lg font-bold text-on-surface"><?= number_format($l['quantity'] ?: 1, 0) ?> Koli</span>
                          </div>
                        </div>
                        <div class="flex items-center justify-between pt-4">
                          <div class="flex items-center gap-2 px-3 py-1.5 rounded-md <?= $cfg['bg'] ?> <?= $cfg['text'] ?> text-xs font-bold uppercase tracking-widest border <?= $cfg['border'] ?>">
                            <span class="w-2 h-2 rounded-full <?= $cfg['dot'] ?>"></span>
                            <?= $cfg['label'] ?>
                          </div>
                          <div class="flex gap-2">
                             <?php if ($l['status'] === 'pending'): ?>
                                <button class="w-8 h-8 rounded-full flex items-center justify-center text-emerald-500 hover:bg-emerald-500/10 transition-colors" title="Terangkut" onclick="updateStatus(<?= $l['id'] ?>, 'active')">
                                    <span class="material-symbols-outlined text-lg">local_shipping</span>
                                </button>
                             <?php endif; ?>
                             <?php if ($l['status'] === 'active'): ?>
                                <button class="w-8 h-8 rounded-full flex items-center justify-center text-blue-500 hover:bg-blue-500/10 transition-colors" title="Selesai" onclick="updateStatus(<?= $l['id'] ?>, 'finished')">
                                    <span class="material-symbols-outlined text-lg">done_all</span>
                                </button>
                             <?php endif; ?>
                             <button class="w-8 h-8 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-orange-500/10 hover:text-orange-500 transition-colors" onclick="alert('Harga: Rp <?= number_format($l['price'], 0, ',', '.') ?>\nBayar: <?= $l['payment_status'] ?>\nNotes: <?= htmlspecialchars($l['notes'] ?: '-') ?>')">
                                <span class="material-symbols-outlined text-lg">info</span>
                             </button>
                             <button class="w-8 h-8 rounded-full flex items-center justify-center text-red-500 hover:bg-red-500/10 transition-colors" title="Batalkan" onclick="updateStatus(<?= $l['id'] ?>, 'canceled')">
                                <span class="material-symbols-outlined text-lg">block</span>
                             </button>
                          </div>
                        </div>
                      </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
          </section>
        </div>

      </div>
    </div>
  </main>

  <!-- Modal Tambah Bagasi -->
  <div class="modal fade" id="modalTambahBagasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content modal-content-custom bg-surface-dim">
        <form id="formTambahBagasi">
          <div class="modal-header border-outline-variant p-6">
            <h5 class="modal-title font-bold text-2xl tracking-tight">Input Bagasi Baru</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- SENDER -->
              <div class="space-y-4">
                <p class="text-xs font-bold uppercase tracking-widest text-orange-500">Data Pengirim</p>
                <div>
                   <label class="text-xs font-bold text-on-surface-variant mb-1 block">Nama Pengirim</label>
                   <input type="text" name="sender_name" required class="form-control form-control-custom w-full">
                </div>
                <div>
                   <label class="text-xs font-bold text-on-surface-variant mb-1 block">No. HP Pengirim</label>
                   <input type="tel" name="sender_phone" required class="form-control form-control-custom w-full">
                </div>
                <div>
                   <label class="text-xs font-bold text-on-surface-variant mb-1 block">Alamat Pengirim</label>
                   <textarea name="sender_address" rows="2" class="form-control form-control-custom w-full"></textarea>
                </div>
              </div>

              <!-- RECEIVER -->
              <div class="space-y-4">
                <p class="text-xs font-bold uppercase tracking-widest text-emerald-500">Data Penerima</p>
                <div>
                   <label class="text-xs font-bold text-on-surface-variant mb-1 block">Nama Penerima</label>
                   <input type="text" name="receiver_name" required class="form-control form-control-custom w-full">
                </div>
                <div>
                   <label class="text-xs font-bold text-on-surface-variant mb-1 block">No. HP Penerima</label>
                   <input type="tel" name="receiver_phone" required class="form-control form-control-custom w-full">
                </div>
                <div>
                   <label class="text-xs font-bold text-on-surface-variant mb-1 block">Alamat Penerima</label>
                   <textarea name="receiver_address" rows="2" class="form-control form-control-custom w-full"></textarea>
                </div>
              </div>

              <!-- SHIPMENT INFO -->
              <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-outline-variant">
                 <div>
                    <label class="text-xs font-bold text-on-surface-variant mb-1 block">Layanan / Tipe Barang</label>
                    <select name="service_id" required class="form-control form-control-custom w-full">
                       <?php foreach ($services as $s): ?>
                          <option value="<?= $s['id'] ?>" data-price="<?= $s['price'] ?>"><?= htmlspecialchars($s['name']) ?> - Rp <?= number_format($s['price'],0,',','.') ?></option>
                       <?php endforeach; ?>
                    </select>
                 </div>
                 <div>
                    <label class="text-xs font-bold text-on-surface-variant mb-1 block">Jumlah (Koli)</label>
                    <input type="number" name="quantity" value="1" min="1" required class="form-control form-control-custom w-full">
                 </div>
                 <div>
                    <label class="text-xs font-bold text-on-surface-variant mb-1 block">Total Harga (Rp)</label>
                    <input type="number" name="price" required class="form-control form-control-custom w-full">
                 </div>
                 <div class="md:col-span-3">
                    <label class="text-xs font-bold text-on-surface-variant mb-1 block">Catatan Tambahan</label>
                    <input type="text" name="notes" class="form-control form-control-custom w-full" placeholder="Misal: Barang mudah pecah">
                 </div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-outline-variant p-6 gap-3">
            <button type="button" class="px-6 py-2.5 rounded-xl font-bold text-on-surface-variant hover:bg-surface-container-high transition-colors" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="px-8 py-2.5 rounded-xl bg-primary-container text-white font-bold shadow-lg shadow-orange-500/20 active:scale-95 transition-all">Simpan Bagasi</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="assets/js/theme-toggle.js?v=<?= time() ?>"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  
  <script>
    const saveLuggage = async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        
        // Manual conversion to JSON for api.php pattern if needed, or just form data
        // We'll use the existing API structure
        try {
            const res = await fetch('admin/ajax.php?action=inputLuggageRaw', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal menyimpan: ' + (data.error || 'Terjadi kesalahan'));
            }
        } catch (err) {
            alert('Kesalahan koneksi saat menyimpan bagasi');
        }
    };

    const updateStatus = async (id, status) => {
        if (!confirm('Ubah status bagasi ini?')) return;
        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', status);
        
        try {
            const res = await fetch('admin/ajax.php?action=updateLuggageSimple', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                location.reload();
            } else {
                alert('Gagal update: ' + (data.error || 'Terjadi kesalahan'));
            }
        } catch (err) {
            alert('Kesalahan koneksi');
        }
    };

    document.getElementById('formTambahBagasi').onsubmit = saveLuggage;
    
    // Auto calculate price based on service
    document.querySelector('select[name="service_id"]').onchange = function() {
        const option = this.options[this.selectedIndex];
        const price = option.getAttribute('data-price');
        const qty = document.querySelector('input[name="quantity"]').value;
        document.querySelector('input[name="price"]').value = price * qty;
    };
    document.querySelector('input[name="quantity"]').oninput = function() {
        const select = document.querySelector('select[name="service_id"]');
        const price = select.options[select.selectedIndex].getAttribute('data-price');
        document.querySelector('input[name="price"]').value = price * this.value;
    };
  </script>
</body>
</html>
