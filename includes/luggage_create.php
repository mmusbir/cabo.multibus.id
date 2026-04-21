<?php
/**
 * includes/luggage_create.php - Modern & Premium Add Luggage Form
 */
$lcUnits = [];
try {
  $lcUnits = $conn->query("SELECT id, nopol, merek, kapasitas FROM units ORDER BY nopol")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$lcServices = [];
try {
  $lcServices = $conn->query("SELECT id, name, price FROM luggage_services ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$lcRoutes = [];
try {
  $lcRoutes = $conn->query("SELECT id, name FROM routes ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$lcHarga = [];
try {
  $lcHarga = $conn->query("SELECT rute_id, layanan_id, harga FROM harga_bagasi")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$lcCustomers = [];
try {
  $lcCustomers = $conn->query("SELECT id, nama, no_hp, alamat, tipe FROM customer_bagasi ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}
?>

<section id="luggage-create" class="card" style="display:none; background:transparent !important; box-shadow:none !important; border:none !important; padding:0 !important;">
    <div class="admin-section-header mb-4">
      <div>
        <h3 class="admin-section-title"><i class="fa-solid fa-square-plus fa-icon" style="color:var(--neu-primary); margin-right:8px;"></i> Input Bagasi Baru</h3>
        <p class="admin-section-subtitle">Pendaftaran pengiriman paket/bagasi ke dalam manifest armada</p>
      </div>
      <a href="#luggage" class="btn btn-outline-secondary btn-modern secondary" data-target="luggage">
        <i class="fa-solid fa-arrow-left fa-icon"></i> Kembali ke Daftar
      </a>
    </div>

    <form onsubmit="submitLuggageCreateForm(event)" class="modern-form-container">
      <div class="row g-4">
        <!-- Panel Kiri: Informasi Customer -->
        <div class="col-lg-7">
          <div class="card p-4 h-100 luggage-card shadow-sm" style="border-radius: 20px;">
            <div class="d-flex align-items-center gap-3 mb-4">
               <div class="icon-box bg-primary-soft p-3 rounded-4" style="background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                  <i class="fa-solid fa-users-viewfinder fa-2x"></i>
               </div>
               <div>
                  <h5 class="mb-0 fw-bold">Informasi Customer</h5>
                  <span class="small text-muted">Data pengirim dan penerima paket</span>
               </div>
            </div>

            <!-- Pengirim -->
            <div class="customer-section mb-4 p-3 rounded-4" style="background: rgba(148, 163, 184, 0.05); border: 1px solid rgba(148, 163, 184, 0.1);">
              <label class="small fw-800 text-primary mb-3 d-block text-uppercase letter-spacing-1">Data Pengirim</label>
              <div class="row g-3">
                <div class="col-md-5">
                  <div class="input-group-modern">
                    <span class="input-icon"><i class="fa-solid fa-phone"></i></span>
                    <input type="text" name="sender_phone" id="lc_sender_phone" class="form-control modern-input ps-5" list="lc_cust_phones" placeholder="No. HP Pengirim" required>
                  </div>
                </div>
                <div class="col-md-7">
                  <div class="input-group-modern">
                    <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                    <input type="text" name="sender_name" id="lc_sender_name" class="form-control modern-input ps-5" placeholder="Nama Lengkap Pengirim" required>
                  </div>
                </div>
                <div class="col-12">
                  <div class="input-group-modern">
                    <span class="input-icon"><i class="fa-solid fa-location-dot"></i></span>
                    <input type="text" name="sender_address" id="lc_sender_address" class="form-control modern-input ps-5" placeholder="Alamat Lengkap Pengirim">
                  </div>
                </div>
              </div>
            </div>

            <!-- Penerima -->
            <div class="customer-section p-3 rounded-4" style="background: rgba(148, 163, 184, 0.05); border: 1px solid rgba(148, 163, 184, 0.1);">
              <label class="small fw-800 text-success mb-3 d-block text-uppercase letter-spacing-1">Data Penerima</label>
              <div class="row g-3">
                <div class="col-md-5">
                  <div class="input-group-modern">
                    <span class="input-icon"><i class="fa-solid fa-phone-volume"></i></span>
                    <input type="text" name="receiver_phone" id="lc_receiver_phone" class="form-control modern-input ps-5" list="lc_cust_phones" placeholder="No. HP Penerima" required>
                  </div>
                </div>
                <div class="col-md-7">
                  <div class="input-group-modern">
                    <span class="input-icon"><i class="fa-solid fa-user-check"></i></span>
                    <input type="text" name="receiver_name" id="lc_receiver_name" class="form-control modern-input ps-5" placeholder="Nama Lengkap Penerima" required>
                  </div>
                </div>
                <div class="col-12">
                  <div class="input-group-modern">
                    <span class="input-icon"><i class="fa-solid fa-truck-ramp-box"></i></span>
                    <input type="text" name="receiver_address" id="lc_receiver_address" class="form-control modern-input ps-5" placeholder="Alamat Lengkap Penerima">
                  </div>
                </div>
              </div>
            </div>
            <datalist id="lc_cust_phones">
              <?php foreach ($lcCustomers as $c): ?>
                <option value="<?= htmlspecialchars($c['no_hp']) ?>"><?= htmlspecialchars($c['nama']) ?></option>
              <?php endforeach; ?>
            </datalist>
          </div>
        </div>

        <!-- Panel Kanan: Detail & Pembayaran -->
        <div class="col-lg-5">
          <div class="card p-4 h-100 luggage-card shadow-sm" style="border-radius: 20px; border-top: 4px solid var(--neu-primary);">
            <div class="d-flex align-items-center gap-3 mb-4">
               <div class="icon-box bg-warning-soft p-3 rounded-4" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                  <i class="fa-solid fa-box-open fa-2x"></i>
               </div>
               <div>
                  <h5 class="mb-0 fw-bold">Detail Manifest</h5>
                  <span class="small text-muted">Rute, Layanan, dan Kalkulasi</span>
               </div>
            </div>

            <div class="mb-3">
              <label class="admin-bs-input-label">Rute Perjalanan</label>
              <select name="rute_id" id="lc_route_select" class="form-select modern-input" required>
                <option value="">Pilih Rute Tujuan</option>
                <?php foreach ($lcRoutes as $rt): ?>
                    <option value="<?= htmlspecialchars($rt['id']) ?>"><?= htmlspecialchars($rt['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="admin-bs-input-label">Jenis Layanan</label>
              <select name="service_id" id="lc_service_select" class="form-select modern-input" required>
                <option value="" data-price="0">Pilih Layanan Bagasi</option>
                <?php foreach ($lcServices as $srv): ?>
                  <option value="<?= $srv['id'] ?>" data-price="<?= $srv['price'] ?>"><?= htmlspecialchars($srv['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-md-6">
                <label class="admin-bs-input-label">Jumlah (Koli)</label>
                <div class="input-group">
                   <input type="number" name="quantity" id="lc_quantity" class="form-control modern-input text-center fw-bold" value="1" min="1" required>
                </div>
              </div>
              <div class="col-md-6">
                <label class="admin-bs-input-label">Berat Est. (Kg)</label>
                <input type="number" name="weight" class="form-control modern-input text-center" value="1" min="1">
              </div>
            </div>

            <div class="mb-4">
              <label class="admin-bs-input-label">Catatan Tambahan</label>
              <textarea name="notes" class="form-control modern-input" rows="2" placeholder="Contoh: Barang pecah belah, isi elektronik..."></textarea>
            </div>

            <!-- Pricing Summary Card -->
            <div class="summary-card p-4 rounded-4 mb-4" style="background: var(--neu-primary); color: white; box-shadow: 0 10px 20px rgba(13, 110, 253, 0.2);">
               <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="small fw-600 opacity-75">ESTIMASI TOTAL BIAYA</span>
                  <i class="fa-solid fa-receipt opacity-50"></i>
               </div>
               <div class="d-flex align-items-baseline gap-2">
                  <span class="h4 mb-0 fw-bold">Rp</span>
                  <span id="lc_total_amount" class="display-6 fw-900 mb-0">0</span>
                  <input type="hidden" name="price" id="lc_price_input" value="0">
               </div>
            </div>

            <div class="mb-4">
               <label class="admin-bs-input-label">Status Pembayaran</label>
               <div class="d-flex gap-3">
                  <label class="flex-fill">
                    <input type="radio" name="payment_status" value="Belum Lunas" checked class="btn-check">
                    <span class="btn btn-outline-secondary w-100 py-2 border-2 fw-bold" style="border-radius:12px;">BELUM LUNAS</span>
                  </label>
                  <label class="flex-fill">
                    <input type="radio" name="payment_status" value="Lunas" class="btn-check">
                    <span class="btn btn-outline-success w-100 py-2 border-2 fw-bold" style="border-radius:12px;">LUNAS</span>
                  </label>
               </div>
            </div>

            <button type="submit" id="lc_submit_btn" class="btn btn-primary btn-modern w-100 py-3 shadow-lg" style="font-size:18px; border-radius:15px; background: linear-gradient(135deg, #0d6efd 0%, #0052cc 100%);">
              <i class="fa-solid fa-cloud-arrow-up me-2"></i> SIMPAN & PROSES RESI
            </button>
          </div>
        </div>
      </div>
    </form>

    <script>
    (function() {
      let hargaMapping = <?php echo json_encode($lcHarga); ?>;
      let customersData = <?php echo json_encode($lcCustomers); ?>;
      
      async function refreshLuggageFormData() {
          try {
              const res = await fetch('admin.php?action=getLuggageFormData');
              const js = await res.json();
              if (js.success) {
                  hargaMapping = js.mapping;
                  customersData = js.customers;
                  
                  const srvSelect = document.getElementById('lc_service_select');
                  if (srvSelect) {
                      const currentVal = srvSelect.value;
                      srvSelect.innerHTML = '<option value="" data-price="0">Pilih Layanan Bagasi</option>';
                      js.services.forEach(s => {
                          const opt = document.createElement('option');
                          opt.value = s.id;
                          opt.dataset.price = s.price;
                          opt.textContent = s.name;
                          srvSelect.appendChild(opt);
                      });
                      srvSelect.value = currentVal;
                  }

                  const routeSelect = document.getElementById('lc_route_select');
                  if (routeSelect) {
                      const currentVal = routeSelect.value;
                      routeSelect.innerHTML = '<option value="">Pilih Rute Tujuan</option>';
                      js.routes.forEach(r => {
                          const opt = document.createElement('option');
                          opt.value = r.id;
                          opt.textContent = r.name;
                          routeSelect.appendChild(opt);
                      });
                      routeSelect.value = currentVal;
                  }

                  const datalist = document.getElementById('lc_cust_phones');
                  if (datalist) {
                      datalist.innerHTML = '';
                      js.customers.forEach(c => {
                          const opt = document.createElement('option');
                          opt.value = c.no_hp;
                          opt.textContent = c.nama;
                          datalist.appendChild(opt);
                      });
                  }
                  
                  updateLuggageCreatePrice();
              }
          } catch (err) {
              console.error('Failed to refresh luggage form data:', err);
          }
      }

      function formatRupiah(amount) {
          try {
              return new Intl.NumberFormat('id-ID').format(amount);
          } catch (e) {
              return amount.toLocaleString();
          }
      }

      function updateLuggageCreatePrice() {
          const srvSelect = document.getElementById('lc_service_select');
          const routeSelect = document.getElementById('lc_route_select');
          const qtyInput = document.getElementById('lc_quantity');
          const priceInput = document.getElementById('lc_price_input');
          const totalAmount = document.getElementById('lc_total_amount');
          const btn = document.getElementById('lc_submit_btn');

          if (!srvSelect || !routeSelect || !qtyInput || !totalAmount) return;

          let qty = parseInt(qtyInput.value, 10);
          if (isNaN(qty) || qty < 1) qty = 1;

          let layanan_id = parseInt(srvSelect.value, 10) || 0;
          let rute_id = parseInt(routeSelect.value, 10) || 0;
          
          let pricePerUnit = null;
          
          if (layanan_id > 0 && rute_id > 0 && Array.isArray(hargaMapping)) {
              for (let i = 0; i < hargaMapping.length; i++) {
                  const m = hargaMapping[i];
                  if (parseInt(m.rute_id) === rute_id && parseInt(m.layanan_id) === layanan_id) {
                      pricePerUnit = parseFloat(m.harga);
                      break;
                  }
              }
          }
          
          if (pricePerUnit === null && srvSelect.selectedIndex > 0) {
              const selectedOpt = srvSelect.options[srvSelect.selectedIndex];
              const basePrice = parseFloat(selectedOpt.getAttribute('data-price') || 0);
              if (basePrice > 0) {
                  pricePerUnit = basePrice;
              }
          }
          
          if (pricePerUnit !== null && !isNaN(pricePerUnit)) {
              const total = pricePerUnit * qty;
              if (priceInput) priceInput.value = total;
              totalAmount.textContent = formatRupiah(total);
          } else {
              if (priceInput) priceInput.value = 0;
              if (layanan_id > 0 && rute_id > 0) {
                  totalAmount.innerHTML = '<span style="font-size:14px; color:rgba(255,255,255,0.7);">Harga belum diset, akan dicatat 0</span>';
              } else {
                  totalAmount.textContent = '0';
              }
          }
          if (btn) btn.disabled = false;
      }

      const serviceSelect = document.getElementById('lc_service_select');
      const routeSelect = document.getElementById('lc_route_select');
      const qtyInput = document.getElementById('lc_quantity');
      
      if(serviceSelect) {
          serviceSelect.addEventListener('change', updateLuggageCreatePrice);
          serviceSelect.addEventListener('input', updateLuggageCreatePrice);
      }
      if(routeSelect) {
          routeSelect.addEventListener('change', updateLuggageCreatePrice);
          routeSelect.addEventListener('input', updateLuggageCreatePrice);
      }
      if(qtyInput) {
          qtyInput.addEventListener('input', updateLuggageCreatePrice);
          qtyInput.addEventListener('change', updateLuggageCreatePrice);
      }

      function autofillCustomer(phoneInputId, nameInputId, addressInputId) {
          const phoneInput = document.getElementById(phoneInputId);
          const nameInput = document.getElementById(nameInputId);
          const addressInput = document.getElementById(addressInputId);
          
          if(phoneInput && nameInput && addressInput) {
              phoneInput.addEventListener('input', function() {
                  const hp = this.value;
                  const customer = customersData.find(c => c.no_hp === hp);
                  if (customer) {
                      nameInput.value = customer.nama;
                      addressInput.value = customer.alamat;
                  }
              });
          }
      }
      
      autofillCustomer('lc_sender_phone', 'lc_sender_name', 'lc_sender_address');
      autofillCustomer('lc_receiver_phone', 'lc_receiver_name', 'lc_receiver_address');

      refreshLuggageFormData();
      window.addEventListener('hashchange', function() {
          if (window.location.hash === '#luggage-create') {
              refreshLuggageFormData();
          }
      });

      window.submitLuggageCreateForm = async function submitLuggageCreateForm(e) {
          e.preventDefault();
          const form = e.target;
          const btn = document.getElementById('lc_submit_btn');
          if(!btn) return;
          
          btn.disabled = true;
          const originalText = btn.innerHTML;
          btn.innerHTML = '<div class="ajax-spinner" style="width:16px;height:16px;border-width:2px;display:inline-block;vertical-align:middle;margin-right:5px"></div> Menyimpan...';
          
          const fd = new FormData(form);
          try {
              const res = await fetch('admin.php?action=inputLuggageRaw', {
                  method: 'POST',
                  body: fd
              });
              const js = await res.json();
              if (js.success) {
                  form.reset();
                  updateLuggageCreatePrice();
                  window.location.hash = '#luggage';
                  if(typeof ajaxListLoad === 'function') {
                      ajaxListLoad('luggage', { page: 1 });
                  }
              } else {
                  customAlert('Gagal: ' + (js.error || 'Terjadi kesalahan'));
              }
          } catch(err) {
              customAlert('Kesalahan koneksi saat menyimpan bagasi.');
          } finally {
              btn.disabled = false;
              btn.innerHTML = originalText;
          }
      };

      document.querySelectorAll('#luggage-create [data-target="luggage"]').forEach(link => {
        link.addEventListener('click', function (e) {
          e.preventDefault();
          window.location.hash = '#luggage';
        });
      });
    })();
    </script>
</section>
