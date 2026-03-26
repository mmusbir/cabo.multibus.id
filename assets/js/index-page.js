      /* ================== CONFIG ================== */
      const API_URL = './api.php';

      /* ================== ELEMENTS ================== */
      const routeEl = document.getElementById('route');
      const tanggalEl = document.getElementById('tanggal');
      const jamEl = document.getElementById('jam');
      const unitEl = document.getElementById('unit');
      const refreshBtn = document.getElementById('refreshSeatsBtn');
      const layoutEl = document.getElementById('layout');
      const nameEl = document.getElementById('name');
      const phoneEl = document.getElementById('phone');
      const pickupEl = document.getElementById('pickup');
      const addressEl = document.getElementById('address');
      const searchNameEl = document.getElementById('search_name');
      const bookBtn = document.getElementById('book');
      const msgEl = document.getElementById('msg');
      const countEmptyEl = document.getElementById('count-empty');
      const countBookedEl = document.getElementById('count-booked');
      const countSelectedEl = document.getElementById('count-selected');
      const sugList = document.getElementById('sugList');
      const segmentEl = document.getElementById('segment');
      const discountEl = document.getElementById('discount');

      /* ================== STATE ================== */
      let CURRENT_SEATS = 8;
      let CURRENT_LAYOUT = [];
      let TOTAL_SEATS = CURRENT_SEATS;
      const booked = new Set();
      const selected = new Set();
      let seatDetails = {};
      window.currentSegments = [];

      /* ================== HELPERS ================== */
      function setMsg(msg, isSuccess = false) {
        msgEl.textContent = msg;
        msgEl.style.color = isSuccess ? '#ffb690' : '#ffb4ab';
      }

      function isValidDate(d) {
        return /^\d{4}-\d{2}-\d{2}$/.test(d);
      }

      function isValidTime(t) {
        return /^\d{2}:\d{2}$/.test(t);
      }

      function escapeHtml(value) {
        return String(value ?? '')
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#39;');
      }

      function normalizePhoneValue(rawValue) {
        let value = String(rawValue || '').replace(/[^0-9]/g, '');
        if (value.startsWith('62')) value = '0' + value.substring(2);
        if (value.startsWith('8')) value = '0' + value;
        if (value.length > 13) value = value.substring(0, 13);
        return value;
      }

      function fillCustomerFieldsFromLookup(customer, overwriteExisting) {
        if (!customer) return;
        const normalizedPhone = normalizePhoneValue(customer.phone || '');
        if (overwriteExisting || !nameEl.value.trim()) nameEl.value = String(customer.name || '').toUpperCase();
        if (overwriteExisting || !phoneEl.value.trim()) phoneEl.value = normalizedPhone;
        if (overwriteExisting || !pickupEl.value.trim()) pickupEl.value = customer.pickup_point || '';
        if (overwriteExisting || !addressEl.value.trim()) addressEl.value = customer.address || '';
        if (overwriteExisting || !getSearchLookupValue().trim()) {
          setSearchLookupValue(String(customer.name || '').toUpperCase());
        }
        validateBookingForm();
      }

      /* ================== LOAD ROUTES BY DATE ================== */
      function loadRoutesByDate(dateStr) {
        // Reset downstream
        routeEl.innerHTML = '<option value="" disabled selected>Sedang memuat...</option>';
        routeEl.disabled = true;
        jamEl.innerHTML = '<option value="" disabled selected>Pilih Jam</option>';
        jamEl.disabled = true;
        unitEl.innerHTML = '<option value="" disabled selected>Pilih Unit</option>';
        unitEl.disabled = true;
        if (!dateStr || !isValidDate(dateStr)) {
          routeEl.innerHTML = '<option value="" disabled selected>Pilih Tanggal Dulu</option>';
          return;
        }

        fetch(API_URL + '?action=getRoutesByDate&tanggal=' + encodeURIComponent(dateStr))
          .then(r => r.json())
          .then(js => {
            if (!js || !js.success) {
              console.warn('getRoutesByDate failed', js);
              setMsg('Tidak ada rute tersedia untuk tanggal ini');
              routeEl.innerHTML = '<option value="" disabled selected>Tidak ada rute</option>';
              return;
            }
            routeEl.innerHTML = '<option value="" disabled selected>Pilih Rute</option>';
            if (js.routes.length === 0) {
              setMsg('Tidak ada rute tersedia untuk tanggal ini');
              routeEl.innerHTML = '<option value="" disabled selected>Tidak ada rute</option>';
              return;
            }
            js.routes.forEach(rt => {
              const opt = document.createElement('option');
              opt.value = rt;
              opt.textContent = rt;
              routeEl.appendChild(opt);
            });
            routeEl.disabled = false; // Enable route selection
            setMsg('', true);
          })
          .catch(err => {
            console.error('loadRoutesByDate error', err);
            setMsg('Gagal memuat rute');
            routeEl.innerHTML = '<option value="" disabled selected>Gagal memuat rute</option>';
          });
      }

      /* ================== RENDER SEAT LAYOUT ================== */
      function renderSeatLayout() {
        console.log('renderSeatLayout called, CURRENT_SEATS:', CURRENT_SEATS, 'CURRENT_LAYOUT:', CURRENT_LAYOUT);

        layoutEl.innerHTML = '';

        // Use layout if available (layout is the source of truth)
        let useLayout = false;
        if (CURRENT_LAYOUT && Array.isArray(CURRENT_LAYOUT) && CURRENT_LAYOUT.length > 0) {
          // Count seats in layout and use that as the actual seat count
          let layoutSeatsCount = 0;
          CURRENT_LAYOUT.forEach(row => {
            row.forEach(cell => {
              if (cell.type === 'seat' && !cell.hidden) layoutSeatsCount++;
            });
          });

          if (layoutSeatsCount > 0) {
            useLayout = true;
            // Update CURRENT_SEATS to match what's actually in the layout
            CURRENT_SEATS = layoutSeatsCount;
            TOTAL_SEATS = layoutSeatsCount;
            console.log('Using layout with ' + layoutSeatsCount + ' seats');
          }
        }

        if (useLayout) {
          // Render from layout data (dynamic columns)
          CURRENT_LAYOUT.forEach((row, rowIdx) => {
            const rowDiv = document.createElement('div');
            rowDiv.className = 'layout-row';
            const colCount = row.length;
            rowDiv.style.setProperty('--cols', colCount);
            if (colCount === 4 && row.some(cell => cell.type === 'empty' && !cell.hidden)) {
              rowDiv.classList.add('has-aisle');
            }

            // Check if this is bagasi row (full width or spanning)
            const isBagasiRow = row.length > 0 && row[0].type === 'bagasi' && row[0].colspan > 0;

            if (isBagasiRow) {
              rowDiv.classList.add('bagasi-row');
              const bagasiDiv = document.createElement('div');
              bagasiDiv.className = 'bagasi';
              bagasiDiv.style.gridColumn = 'span ' + row[0].colspan;
              bagasiDiv.textContent = 'BAGASI';
              rowDiv.appendChild(bagasiDiv);
            } else {
              row.forEach((cell, colIdx) => {
                if (cell.hidden) return;

                if (cell.type === 'driver') {
                  const driverDiv = document.createElement('div');
                  driverDiv.className = 'driver';
                  driverDiv.setAttribute('aria-label', 'Driver');
                  driverDiv.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="8"></circle><circle cx="12" cy="12" r="2"></circle><path d="M12 4v4"></path><path d="M12 20v-4"></path><path d="M4 12h4"></path><path d="M20 12h-4"></path></svg>';
                  rowDiv.appendChild(driverDiv);
                } else if (cell.type === 'seat') {
                  const seatDiv = document.createElement('div');
                  seatDiv.className = 'seat';
                  seatDiv.dataset.seat = cell.label || cell.seatNumber || '';
                  seatDiv.textContent = cell.label || cell.seatNumber || '';
                  rowDiv.appendChild(seatDiv);
                } else if (cell.type === 'bagasi-custom') {
                  const bagasiDiv = document.createElement('div');
                  bagasiDiv.className = 'bagasi-small';
                  bagasiDiv.textContent = 'ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã‚Â¦';
                  rowDiv.appendChild(bagasiDiv);
                } else {
                  // Empty cell
                  const emptyDiv = document.createElement('div');
                  emptyDiv.className = 'empty-cell';
                  rowDiv.appendChild(emptyDiv);
                }
              });
            }

            layoutEl.appendChild(rowDiv);
          });
        } else {
          // Fallback: Simple vertical layout based on CURRENT_SEATS
          // Row 1: Seat 1 + Seat 2 + Driver (matches units.php default layout)
          const row1 = document.createElement('div');
          row1.className = 'layout-row';
          row1.innerHTML = '<div class="seat" data-seat="1">1</div><div class="seat" data-seat="2">2</div><div class="driver" aria-label="Driver"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="8"></circle><circle cx="12" cy="12" r="2"></circle><path d="M12 4v4"></path><path d="M12 20v-4"></path><path d="M4 12h4"></path><path d="M20 12h-4"></path></svg></div>';
          layoutEl.appendChild(row1);

          // Middle rows: 3 seats per row
          let seatNum = 3;
          while (seatNum <= CURRENT_SEATS) {
            const rowDiv = document.createElement('div');
            rowDiv.className = 'layout-row';

            for (let col = 0; col < 3 && seatNum <= CURRENT_SEATS; col++) {
              const seatDiv = document.createElement('div');
              seatDiv.className = 'seat';
              seatDiv.dataset.seat = String(seatNum);
              seatDiv.textContent = String(seatNum);
              rowDiv.appendChild(seatDiv);
              seatNum++;
            }

            // Fill remaining columns with empty cells
            while (rowDiv.children.length < 3) {
              const emptyDiv = document.createElement('div');
              emptyDiv.className = 'empty-cell';
              rowDiv.appendChild(emptyDiv);
            }

            layoutEl.appendChild(rowDiv);
          }

          // Bagasi row
          const bagasiRow = document.createElement('div');
          bagasiRow.className = 'layout-row bagasi-row';
          bagasiRow.innerHTML = '<div class="bagasi">BAGASI</div>';
          layoutEl.appendChild(bagasiRow);
        }

        console.log('Layout rendered with', CURRENT_SEATS, 'seats');

        // Re-attach event listeners
        document.querySelectorAll('.seat').forEach(el => {
          el.addEventListener('click', () => {
            const id = el.dataset.seat;
            if (booked.has(id)) {
              const info = seatDetails[id];
              if (info) {
                const modal = document.getElementById('detailModal');
                const tanggal = tanggalEl.value;
                const jam = jamEl.value || '-';
                const hari = tanggal ? new Date(tanggal + 'T00:00:00').toLocaleDateString('id-ID', { weekday: 'long' }) : '-';
                const segment = info.segment_name || '-';
                const pembayaran = info.pembayaran || 'Belum Lunas';
                const paymentInfo = formatPaymentLabel(pembayaran);
                const price = info.price || 0;
                const disc = info.discount || 0;
                const finalPrice = price - disc;
                const priceFormatted = formatRp(finalPrice);

                currentDetailBooking = {
                  bookingId: info.id || 0,
                  seatId: id,
                  name: info.name || '-',
                  phone: info.phone || '-',
                  pickup_point: info.pickup_point || '-',
                  segment: segment,
                  segment_id: info.segment_id || '',
                  price: price,
                  disc: disc,
                  finalPrice: finalPrice,
                  pembayaran: pembayaran,
                  tanggal: tanggal,
                  jam: jam,
                  hari: hari,
                  priceFormatted: priceFormatted
                };

                _currentRekapItem = {
                  seatId: id,
                  name: info.name || '-',
                  phone: info.phone || '-',
                  pickup: info.pickup_point || '-',
                  segment: segment,
                  price: price,
                  disc: disc,
                  finalPrice: finalPrice,
                  pembayaran: pembayaran,
                  tanggal: tanggal,
                  jam: jam,
                  hari: hari
                };

                renderDetailModalView();
                setDetailEditMode(false);

                document.getElementById('copyDetailBtn').onclick = () => {
                  const text = `BUKTI BOOKING
------------------------------
Keberangkatan : ${hari}, ${tanggal} - ${jam}
Kursi         : ${id}
Segment       : ${segment}
Harga         : ${priceFormatted}
Nama          : ${info.name || '-'}
No. HP        : ${info.phone || '-'}
Titik Jemput  : ${info.pickup_point || '-'}
${paymentInfo.label.padEnd(16, ' ')}: ${paymentInfo.value}`;

                  // Fallback function
                  const fallbackCopy = (txt) => {
                    const ta = document.createElement('textarea');
                    ta.value = txt;
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    try {
                      document.execCommand('copy');
                      alert('Detail berhasil disalin');
                      modal.style.display = 'none';
                    } catch (err) {
                      alert('Gagal menyalin (manual copy diperlukan)');
                    }
                    document.body.removeChild(ta);
                  };

                  if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text)
                      .then(() => { alert('Detail berhasil disalin'); modal.style.display = 'none'; })
                      .catch(() => fallbackCopy(text));
                  } else {
                    fallbackCopy(text);
                  }
                };
              } else {
                alert('Kursi ' + id + ' sudah terisi');
              }
              return;
            }
            if (selected.has(id)) selected.delete(id);
            else {
              selected.add(id);
            }
            updateSeatsUI();
            setMsg('');
          });
        });
        updateSeatsUI();
      }

      /* ================== UPDATE SEATS UI ================== */
      function updateSeatsUI() {
        document.querySelectorAll('.seat').forEach(el => {
          const id = el.dataset.seat;
          el.classList.remove('booked', 'selected');
          if (booked.has(id)) el.classList.add('booked');
          else if (selected.has(id)) el.classList.add('selected');
          if (seatDetails[id]) el.setAttribute('aria-label', (seatDetails[id].name || '') + ' - ' + (seatDetails[id].phone || ''));
          else el.removeAttribute('aria-label');
        });
        const bookedCount = booked.size;
        const selectedCount = selected.size;
        const emptyCount = Math.max(0, TOTAL_SEATS - bookedCount);
        countBookedEl.textContent = bookedCount;
        countSelectedEl.textContent = selectedCount;
        countEmptyEl.textContent = emptyCount;

        validateBookingForm(); // Trigger validation when seats change
        updateRegulerTotal(); // Update price calculation
      }

      /* ================== PRICE CALCULATION (Reguler) ================== */
      function getCurrentBookingTotal() {
        if (!segmentEl) return 0;
        const segmentId = segmentEl.value;
        const discountVal = parseInt(discountEl ? discountEl.value : 0) || 0;
        const selectedCount = selected.size;

        if (segmentId && selectedCount > 0) {
          const segment = window.currentSegments.find(s => String(s.id) === String(segmentId));
          if (segment) {
            const pricePerSeat = parseInt(segment.harga) || 0;
            return Math.max(0, (pricePerSeat * selectedCount) - discountVal);
          }
        }
        return 0;
      }

      function updateRegulerTotal() {
        updateRekapBar();
      }

      /* ================== VALIDATION ================== */
      function validateBookingForm() {
        if (!selected || !nameEl || !phoneEl || !pickupEl || !segmentEl || !bookBtn) return;

        const hasSeats = selected.size > 0;
        const hasName = nameEl.value.trim().length > 0;
        const hasPhone = phoneEl.value.trim().length > 0;
        const hasPickup = pickupEl.value.trim().length > 0;
        const hasSegment = segmentEl.value && segmentEl.value !== '';

        const isValid = hasSeats && hasName && hasPhone && hasPickup && hasSegment;
        bookBtn.disabled = !isValid;

        if (isValid) {
          bookBtn.classList.add('pulse-ready');
        } else {
          bookBtn.classList.remove('pulse-ready');
        }
      }

      /* ================== FILL TIMES ================== */
      function fillTimes(rute, dateStr) {
        // Reset downstream
        jamEl.innerHTML = '<option value="" disabled selected>Sedang memuat...</option>';
        jamEl.disabled = true;
        unitEl.innerHTML = '<option value="" disabled selected>Pilih Unit</option>';
        unitEl.disabled = true;

        if (!rute || !dateStr) return;

        fetch(API_URL + '?action=getSchedules&rute=' + encodeURIComponent(rute) + '&tanggal=' + encodeURIComponent(dateStr) + '&_=' + new Date().getTime())
          .then(r => r.json()).then(js => {
            if (!js || !js.success) {
              console.warn('getSchedules failed', js);
              setMsg('Tidak ada jadwal');
              jamEl.innerHTML = '<option value="" disabled selected>Tidak ada jadwal</option>';
              return;
            }
            jamEl.innerHTML = '<option value="" disabled selected>Pilih Jam</option>';
            if (js.schedules.length > 0) {
              CURRENT_SEATS = js.schedules[0].seats || 8;
              CURRENT_LAYOUT = js.schedules[0].layout || [];
            } else {
              CURRENT_SEATS = 8;
              CURRENT_LAYOUT = [];
            }
            js.schedules.forEach((s, idx) => {
              const opt = document.createElement('option');
              opt.value = s.jam;
              opt.textContent = s.units > 1 ? s.jam + ' (' + s.units + ' unit)' : s.jam;
              opt.dataset.units = s.units;
              opt.dataset.seats = s.seats;
              opt.dataset.layout = JSON.stringify(s.layout || []);
              jamEl.appendChild(opt);
              if (idx === 0) {
                CURRENT_SEATS = s.seats || 8;
                CURRENT_LAYOUT = s.layout || [];
              }
            });
            jamEl.disabled = false; // Enable time selection

            if (jamEl.options.length > 1) { jamEl.selectedIndex = 1; jamChanged(); }
          }).catch(err => {
            console.error('fillTimes error', err);
            setMsg('Gagal memuat jadwal');
            jamEl.innerHTML = '<option value="" disabled selected>Gagal memuat jadwal</option>';
          });
      }

      function jamChanged() {
        const units = Number(jamEl.selectedOptions[0]?.dataset?.units || 1);
        CURRENT_SEATS = Number(jamEl.selectedOptions[0]?.dataset?.seats || 8);
        try {
          CURRENT_LAYOUT = JSON.parse(jamEl.selectedOptions[0]?.dataset?.layout || '[]');
        } catch (e) {
          CURRENT_LAYOUT = [];
        }
        TOTAL_SEATS = CURRENT_SEATS;
        unitEl.innerHTML = '';
        for (let i = 1; i <= Math.max(1, units); i++) {
          const o = document.createElement('option');
          o.value = i;
          o.textContent = 'Unit ' + i;
          unitEl.appendChild(o);
        }
        unitEl.disabled = units <= 1;

        // Auto refresh seats when jam/unit changes
        refreshSeats();
      }

      /* ================== REFRESH SEATS ================== */
      async function refreshSeats() {
        console.log('refreshSeats called, CURRENT_SEATS:', CURRENT_SEATS);
        setMsg('');
        renderSeatLayout();
        booked.clear(); selected.clear(); seatDetails = {}; updateSeatsUI();

        const r = routeEl.value, d = tanggalEl.value, j = jamEl.value;
        const unit = Number(unitEl.value || 1);

        // If incomplete, just return (layout already rendered empty by renderSeatLayout)
        if (!r || !d || !j) return;
        if (!isValidDate(d) || !isValidTime(j)) { setMsg('Format tanggal/jam tidak valid'); return; }

        setMsg('Memuat kursi...');

        try {
          const urlDetail = API_URL + '?action=getBookedSeatsDetail&rute=' + encodeURIComponent(r) + '&tanggal=' + encodeURIComponent(d) + '&jam=' + encodeURIComponent(j) + '&unit=' + encodeURIComponent(unit);
          const res = await fetch(urlDetail, { cache: 'no-store' });
          if (res.ok) {
            const js = await res.json().catch(() => null);
            if (js && js.success && js.details) {
              Object.keys(js.details).forEach(k => {
                booked.add(String(k));
                seatDetails[String(k)] = js.details[k];
              });
              updateSeatsUI();
              setMsg('Kursi diperbarui', true);
              // refreshBtn.disabled = false; // button removed
              return;
            } else {
              // API returned success:false
              console.warn('API error:', js);
              setMsg('Gagal memuat kursi: ' + (js && js.error ? js.error : 'Respons tidak valid'));
              return;
            }
          } else {
            setMsg('Error server: ' + res.status);
            return;
          }
        } catch (err) {
          console.error('getBookedSeatsDetail fetch error', err);
          setMsg('Gagal koneksi ambil kursi');
        }

        // Fallback removed since getBookedSeats endpoint does not exist in api.php
        // setMsg('Gagal ambil kursi', false);
        // refreshBtn.disabled = false;
      }

      /* ================== BOOKING ================== */
      async function doBooking() {
        const r = routeEl.value, d = tanggalEl.value, j = jamEl.value;
        const unit = Number(unitEl.value || 1);
        const name = nameEl.value.trim(), phone = phoneEl.value.trim(), pickup = pickupEl.value.trim(), address = addressEl.value.trim();
        const seats = Array.from(selected);
        const pembayaranRad = document.querySelector('input[name="pembayaran"]:checked');
        const pembayaran = pembayaranRad ? pembayaranRad.value : 'Belum Lunas';
        // ADDED: segment_id and discount
        const segmentId = segmentEl ? segmentEl.value : '';
        const discountVal = discountEl ? discountEl.value : 0;

        if (!r || !d || !j || !name || !phone || !pickup || seats.length === 0) { setMsg('Lengkapi data (Nama, HP, Alamat) dan pilih kursi'); return; }
        bookBtn.disabled = true;
        setMsg('Mengirim booking...');
        try {
          const res = await fetch(API_URL + '?action=submitBooking', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              rute: r, tanggal: d, jam: j, unit: unit, name: name, phone: phone, address: address, pickup_point: pickup, seats: seats, pembayaran: pembayaran,
              segment_id: segmentId, discount: discountVal
            })
          });
          const js = await res.json();
          if (js.success) { setMsg('Booking berhasil', true); selected.clear(); nameEl.value = ''; phoneEl.value = ''; pickupEl.value = ''; addressEl.value = ''; setSearchLookupValue(''); refreshSeats(); }
          else if (js.error === 'conflict') { setMsg('Kursi bentrok: ' + (js.conflict || []).join(', ')); refreshSeats(); }
          else { 
            let errorMsg = js.error || 'tidak diketahui';
            if (js.detail) errorMsg += ' - ' + js.detail;
            setMsg('Booking gagal - ' + errorMsg); 
          }
        } catch (err) {
          setMsg('Gagal koneksi saat booking');
        } finally { bookBtn.disabled = false; }
      }

      /* ================== REKAP MULTI-PENUMPANG ================== */
      // State
      let rekapList = []; // Array of { seatId, name, phone, pickup, segment, price, disc, finalPrice, tanggal, jam, hari }
      let _currentRekapItem = null; // temp store for detail modal context
      let currentDetailBooking = null;
      let isDetailEditMode = false;

      function getSearchLookupValue() {
        if (!searchNameEl) return '';
        return (searchNameEl.textContent || '').replace(/\s+/g, ' ').trim();
      }

      function syncSearchLookupState() {
        const shell = document.getElementById('suggestions');
        if (!shell) return;
        shell.classList.toggle('has-value', getSearchLookupValue().length > 0);
      }

      function setSearchLookupValue(value) {
        if (!searchNameEl) return;
        searchNameEl.textContent = value || '';
        syncSearchLookupState();
      }

      // Formatters
      function formatRp(amount) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount);
      }

      function isPaymentMethodValue(value) {
        return ['QRIS', 'Transfer', 'Tunai'].includes(String(value || '').trim());
      }

      function formatPaymentLabel(value) {
        const normalized = String(value || '').trim();
        if (!normalized) return { label: 'Pembayaran', value: '-' };
        if (normalized === 'Belum Lunas') {
          return { label: 'Metode Pembayaran', value: 'QRIS / Transfer / Tunai' };
        }
        if (isPaymentMethodValue(normalized)) {
          return { label: 'Metode Pembayaran', value: normalized };
        }
        return { label: 'Status Pembayaran', value: normalized };
      }

      function renderDetailModalView() {
        const modal = document.getElementById('detailModal');
        const content = document.getElementById('detailContent');
        const stateEl = document.getElementById('detailActionState');
        if (!modal || !content || !currentDetailBooking) return;
        if (stateEl) stateEl.classList.remove('show', 'success', 'cancel');

        const info = currentDetailBooking;
        const paymentInfo = formatPaymentLabel(info.pembayaran);
        content.innerHTML = `
          <div><strong>Keberangkatan:</strong> ${info.hari}, ${info.tanggal} - ${info.jam}</div>
          <div><strong>Kursi:</strong> ${info.seatId} - Terisi</div>
          <div><strong>Segment:</strong> ${info.segment || '-'}</div>
          <div><strong>Harga:</strong> ${info.priceFormatted}</div>
          <div><strong>Nama:</strong> ${info.name || '-'}</div>
          <div><strong>No. HP:</strong> ${info.phone || '-'}</div>
          <div><strong>Titik Jemput:</strong> ${info.pickup_point || '-'}</div>
          <div><strong>${paymentInfo.label}:</strong> ${paymentInfo.value}</div>
        `;
        modal.style.display = 'flex';
      }

      function fillDetailEditSegmentOptions(selectedId) {
        const select = document.getElementById('detailEditSegment');
        if (!select) return;
        const currentValue = String(selectedId || '');
        const options = ['<option value="">-- Pilih Segment --</option>'];
        (window.currentSegments || []).forEach((segment) => {
          const isSelected = String(segment.id) === currentValue ? ' selected' : '';
          options.push(`<option value="${segment.id}"${isSelected}>${segment.rute} (${formatRp(segment.harga || 0)})</option>`);
        });
        select.innerHTML = options.join('');
      }

      function fillDetailEditSeatOptions(selectedSeat) {
        const select = document.getElementById('detailEditSeat');
        if (!select) return;

        const selectedValue = String(selectedSeat || '');
        const seatOptions = [];

        const pushSeat = (value) => {
          const seatValue = String(value || '').trim();
          if (!seatValue) return;
          if (!seatOptions.includes(seatValue)) {
            seatOptions.push(seatValue);
          }
        };

        if (Array.isArray(CURRENT_LAYOUT) && CURRENT_LAYOUT.length) {
          CURRENT_LAYOUT.forEach((row) => {
            row.forEach((cell) => {
              if (cell && cell.type === 'seat' && !cell.hidden) {
                pushSeat(cell.label || cell.seatNumber);
              }
            });
          });
        }

        if (!seatOptions.length) {
          for (let i = 1; i <= CURRENT_SEATS; i++) {
            pushSeat(String(i));
          }
        }

        pushSeat(selectedValue);

        const availableSeats = seatOptions.filter((seat) => !booked.has(seat) || seat === selectedValue);
        const options = ['<option value="">-- Pilih Kursi --</option>'];

        availableSeats.forEach((seat) => {
          const isSelected = seat === selectedValue ? ' selected' : '';
          options.push(`<option value="${seat}"${isSelected}>${seat}</option>`);
        });

        select.innerHTML = options.join('');
        select.value = selectedValue && availableSeats.includes(selectedValue) ? selectedValue : (availableSeats[0] || '');
      }

      function setDetailEditMode(editing) {
        isDetailEditMode = !!editing;
        const detailContent = document.getElementById('detailContent');
        const editForm = document.getElementById('detailEditForm');
        const viewActions = document.getElementById('detailModalViewActions');
        const editActions = document.getElementById('detailModalEditActions');
        if (detailContent) detailContent.style.display = editing ? 'none' : 'block';
        if (editForm) editForm.style.display = editing ? 'block' : 'none';
        if (viewActions) viewActions.style.display = editing ? 'none' : 'flex';
        if (editActions) editActions.style.display = editing ? 'flex' : 'none';
      }

      function openDetailEditMode() {
        if (!currentDetailBooking) return;
        document.getElementById('detailEditName').value = currentDetailBooking.name || '';
        document.getElementById('detailEditPhone').value = currentDetailBooking.phone || '';
        document.getElementById('detailEditPickup').value = currentDetailBooking.pickup_point || '';
        document.getElementById('detailEditDiscount').value = currentDetailBooking.disc || 0;
        document.getElementById('detailEditPayment').value = currentDetailBooking.pembayaran || 'Belum Lunas';
        fillDetailEditSegmentOptions(currentDetailBooking.segment_id || '');
        fillDetailEditSeatOptions(currentDetailBooking.seatId || '');
        setDetailEditMode(true);
      }

      function closeDetailEditMode() {
        setDetailEditMode(false);
      }

      async function flashDetailActionState(message, tone = 'success') {
        const stateEl = document.getElementById('detailActionState');
        const textEl = document.getElementById('detailActionStateText');
        const iconEl = document.getElementById('detailActionStateIcon');
        if (!stateEl || !textEl || !iconEl) return;
        stateEl.classList.remove('success', 'cancel', 'show');
        stateEl.classList.add(tone === 'cancel' ? 'cancel' : 'success');
        iconEl.className = `fa-solid ${tone === 'cancel' ? 'fa-ban' : 'fa-circle-check'} fa-icon`;
        textEl.textContent = message;
        stateEl.classList.add('show');
        await new Promise(resolve => setTimeout(resolve, 900));
        stateEl.classList.remove('show');
      }

      function openCancelBookingConfirm() {
        if (!currentDetailBooking || !currentDetailBooking.bookingId) return;
        const popup = document.getElementById('confirmCancelBookingPopup');
        if (popup) popup.style.display = 'flex';
      }

      function closeCancelBookingConfirm() {
        const popup = document.getElementById('confirmCancelBookingPopup');
        if (popup) popup.style.display = 'none';
      }

      async function saveDetailBookingEdit() {
        if (!currentDetailBooking) return;
        const payload = {
          id: currentDetailBooking.bookingId,
          name: document.getElementById('detailEditName').value.trim(),
          phone: document.getElementById('detailEditPhone').value.trim(),
          seat: document.getElementById('detailEditSeat').value.trim().toUpperCase(),
          pickup_point: document.getElementById('detailEditPickup').value.trim(),
          segment_id: document.getElementById('detailEditSegment').value || '',
          discount: document.getElementById('detailEditDiscount').value || 0,
          pembayaran: document.getElementById('detailEditPayment').value || 'Belum Lunas'
        };

        if (!payload.name || !payload.phone || !payload.pickup_point || !payload.seat) {
          showRekapToast('Lengkapi nama, no. handphone, kursi, dan titik jemput');
          return;
        }

        try {
          const res = await fetch(API_URL + '?action=updateBookedSeat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
          });
          const js = await res.json();
          if (!js || !js.success) {
            showRekapToast(js?.error || 'Gagal memperbarui booking');
            return;
          }
          showRekapToast(js.message || 'Booking berhasil diperbarui');
          closeDetailEditMode();
          await flashDetailActionState(js.message || 'Booking berhasil diperbarui', 'success');
          document.getElementById('detailModal').style.display = 'none';
          await refreshSeats();
        } catch (err) {
          showRekapToast('Gagal koneksi saat memperbarui booking');
        }
      }

      async function cancelCurrentDetailBooking() {
        if (!currentDetailBooking || !currentDetailBooking.bookingId) return;

        try {
          const res = await fetch(API_URL + '?action=cancelBookedSeat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: currentDetailBooking.bookingId })
          });
          const js = await res.json();
          if (!js || !js.success) {
            showRekapToast(js?.error || 'Gagal membatalkan booking');
            return;
          }
          rekapList = rekapList.filter(item => item.seatId !== currentDetailBooking.seatId);
          updateRekapBar();
          showRekapToast(js.message || 'Booking berhasil dibatalkan');
          closeCancelBookingConfirm();
          await flashDetailActionState(js.message || 'Booking berhasil dibatalkan', 'cancel');
          document.getElementById('detailModal').style.display = 'none';
          currentDetailBooking = null;
          await refreshSeats();
        } catch (err) {
          showRekapToast('Gagal koneksi saat membatalkan booking');
        }
      }

      // Update sticky bottom bar
      function updateRekapBar() {
        const bar = document.getElementById('rekapBar');
        const countEl = document.getElementById('rekapBarCount');
        const totalEl = document.getElementById('rekapBarTotal');
        const totalRekap = rekapList.reduce((s, r) => s + r.finalPrice, 0);
        const total = totalRekap + getCurrentBookingTotal();
        const totalCount = rekapList.length + selected.size;
        if (!bar || !countEl || !totalEl) return;
        bar.style.display = 'flex';
        countEl.textContent = String(totalCount).padStart(2, '0');
        totalEl.textContent = formatRp(total);
      }

      // Render isi modal rekap
      function renderRekapModal() {
        const content = document.getElementById('rekapContent');
        const totalEl = document.getElementById('rekapTotalAmount');
        const total = rekapList.reduce((s, r) => s + r.finalPrice, 0);
        totalEl.textContent = formatRp(total);

        if (rekapList.length === 0) {
          content.innerHTML = `
            <div class="rekap-empty-state">
              <span class="rekap-empty-icon"><i class="fa-solid fa-receipt fa-icon"></i></span>
              Belum ada penumpang di rekap.<br>Klik kursi yang terisi, lalu tekan "+ Tambah ke Rekap".
            </div>`;
          return;
        }

        content.innerHTML = rekapList.map((r, idx) => {
          const paymentInfo = formatPaymentLabel(r.pembayaran || '');
          return `
          <div class="rekap-item-card">
            <div class="rekap-item-header">
              <span class="rekap-item-seat">Kursi ${r.seatId}</span>
              <span class="rekap-item-price">${formatRp(r.finalPrice)}</span>
            </div>
            <div class="rekap-item-name">${r.name || '-'}</div>
            <div class="rekap-item-detail">
              HP: ${r.phone || '-'}<br>
              Jemput: ${r.pickup || '-'}<br>
              Segment: ${r.segment || '-'} - ${r.hari}, ${r.tanggal} - ${r.jam}<br>
              ${paymentInfo.label}: ${paymentInfo.value}
            </div>
            <button class="rekap-item-remove" onclick="removeFromRekap(${idx})" title="Hapus dari rekap">&times;</button>
          </div>
        `;
        }).join('');
      }
      // Tambah item ke rekap
      window.addCurrentToRekap = function () {
        if (!_currentRekapItem) return;
        const { seatId } = _currentRekapItem;
        // Cegah duplikat kursi yang sama
        if (rekapList.find(r => r.seatId === seatId)) {
          showRekapToast('Kursi ' + seatId + ' sudah ada di rekap');
          return;
        }
        rekapList.push({ ..._currentRekapItem });
        updateRekapBar();
        showRekapToast('Kursi ' + seatId + ' ditambahkan ke rekap');
        document.getElementById('detailModal').style.display = 'none';
        _currentRekapItem = null;
      };

      // Hapus item dari rekap berdasarkan index
      window.removeFromRekap = function (idx) {
        rekapList.splice(idx, 1);
        renderRekapModal();
        updateRekapBar();
      };

      // Buka modal rekap
      window.openRekapModal = function () {
        renderRekapModal();
        document.getElementById('rekapModal').style.display = 'flex';
      };

      // Toast notification
      let _toastTimer = null;
      function showRekapToast(msg) {
        let toast = document.getElementById('rekapToast');
        if (!toast) {
          toast = document.createElement('div');
          toast.id = 'rekapToast';
          toast.className = 'rekap-toast';
          document.body.appendChild(toast);
        }
        toast.textContent = msg;
        toast.classList.add('show');
        clearTimeout(_toastTimer);
        _toastTimer = setTimeout(() => toast.classList.remove('show'), 2200);
      }

      // Copy semua rekap ke clipboard
      function copyRekap() {
        if (rekapList.length === 0) {
          showRekapToast('Rekap masih kosong');
          return;
        }
        const total = rekapList.reduce((s, r) => s + r.finalPrice, 0);
        const lines = rekapList.map((r, i) => {
          const paymentInfo = formatPaymentLabel(r.pembayaran || '');
          return `${i + 1}. ${r.name || '-'}\n` +
            `   Kursi         : ${r.seatId}\n` +
            `   No. HP        : ${r.phone || '-'}\n` +
            `   Titik Jemput  : ${r.pickup || '-'}\n` +
            `   Segment       : ${r.segment || '-'}\n` +
            `   Harga         : ${formatRp(r.finalPrice)}\n` +
            `   ${paymentInfo.label.padEnd(16, ' ')}: ${paymentInfo.value}`;
        }).join('\n\n');
        const firstItem = rekapList[0];
        const divider = '-'.repeat(30);
        const header = `BUKTI BOOKING\nTanggal Keberangkatan : ${firstItem.hari}, ${firstItem.tanggal} - ${firstItem.jam}\n${divider}`;
        const footer = `${divider}\nTotal Penumpang       : ${rekapList.length}\nTotal Pembayaran      : ${formatRp(total)}`;
        const text = `${header}\n\n${lines}\n\n${footer}`;

        const doFallback = (t) => {
          const ta = document.createElement('textarea');
          ta.value = t; ta.style.position = 'fixed'; ta.style.opacity = '0';
          document.body.appendChild(ta); ta.select();
          try { document.execCommand('copy'); showRekapToast('Rekap berhasil disalin'); }
          catch (e) { showRekapToast('Gagal menyalin'); }
          document.body.removeChild(ta);
        };

        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text)
            .then(() => showRekapToast('Rekap berhasil disalin'))
            .catch(() => doFallback(text));
        } else {
          doFallback(text);
        }
      }

      // Bind tombol modal rekap
      document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('addToRekapBtn').addEventListener('click', addCurrentToRekap);
        document.getElementById('editDetailBtn')?.addEventListener('click', openDetailEditMode);
        document.getElementById('closeEditModeBtn')?.addEventListener('click', closeDetailEditMode);
        document.getElementById('saveDetailEditBtn')?.addEventListener('click', saveDetailBookingEdit);
        document.getElementById('cancelBookingBtn')?.addEventListener('click', openCancelBookingConfirm);
        document.getElementById('confirmCancelBookingNo')?.addEventListener('click', closeCancelBookingConfirm);
        document.getElementById('confirmCancelBookingYes')?.addEventListener('click', cancelCurrentDetailBooking);

        document.getElementById('copyRekapBtn').addEventListener('click', copyRekap);

        document.getElementById('clearRekapBtn').addEventListener('click', () => {
          if (rekapList.length === 0) { showRekapToast('Rekap sudah kosong'); return; }
          document.getElementById('confirmResetPopup').style.display = 'flex';
        });

        document.getElementById('confirmResetNo').addEventListener('click', () => {
          document.getElementById('confirmResetPopup').style.display = 'none';
        });

        document.getElementById('confirmResetYes').addEventListener('click', () => {
          rekapList = [];
          renderRekapModal();
          updateRekapBar();
          document.getElementById('rekapModal').style.display = 'none';
          document.getElementById('confirmResetPopup').style.display = 'none';
          showRekapToast('Rekap berhasil direset');
        });

        document.getElementById('closeRekapModal').addEventListener('click', () => {
          document.getElementById('rekapModal').style.display = 'none';
        });

        document.getElementById('rekapModal').addEventListener('click', (e) => {
          if (e.target.id === 'rekapModal') e.target.style.display = 'none';
        });
      });


      /* ================== INPUT VALIDATION (Reguler) ================== */
      nameEl.addEventListener('input', function () {
        this.value = this.value.toUpperCase();
      });

      let phoneLookupTimer = null;
      phoneEl.addEventListener('input', function () {
        const val = normalizePhoneValue(this.value);
        this.value = val;

        if (phoneLookupTimer) clearTimeout(phoneLookupTimer);
        if (val.length < 4) return;

        phoneLookupTimer = setTimeout(async () => {
          try {
            const res = await fetch(API_URL + '?action=searchCustomers&q=' + encodeURIComponent(val));
            const js = await res.json();
            if (!(js && js.success && Array.isArray(js.customers) && js.customers.length)) return;
            const exactMatch = js.customers.find((customer) => normalizePhoneValue(customer.phone || '') === val) || js.customers[0];
            if (exactMatch) {
              fillCustomerFieldsFromLookup(exactMatch, false);
            }
          } catch (err) {
            console.error('phone lookup error', err);
          }
        }, 250);
      });

      document.getElementById('detailEditName')?.addEventListener('input', function () {
        this.value = this.value.toUpperCase();
      });

      document.getElementById('detailEditPhone')?.addEventListener('input', function () {
        let val = this.value.replace(/[^0-9]/g, '');
        if (val.startsWith('62')) {
          val = '0' + val.substring(2);
        }
        if (val.startsWith('8')) {
          val = '0' + val;
        }
        if (val.length > 13) {
          val = val.substring(0, 13);
        }
        this.value = val;
      });

      /* ================== SUGGESTIONS ================== */
      let sugTimer = null;
      searchNameEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
        }
      });
      searchNameEl.addEventListener('focus', syncSearchLookupState);
      searchNameEl.addEventListener('blur', syncSearchLookupState);
      searchNameEl.addEventListener('input', function () {
        syncSearchLookupState();
        const q = getSearchLookupValue();
        if (sugTimer) clearTimeout(sugTimer);
        sugList.style.display = 'none';
        sugList.innerHTML = '';
        if (!q) return;
        sugTimer = setTimeout(async () => {
          try {
            const res = await fetch(API_URL + '?action=searchCustomers&q=' + encodeURIComponent(q));
            const js = await res.json();
            if (js && js.success && js.customers && js.customers.length) {
              sugList.innerHTML = '';
              js.customers.forEach(c => {
                const div = document.createElement('div');
                div.className = 'booking-suggestion-item';
                const safeName = escapeHtml(c.name || '-');
                const safePhone = escapeHtml(c.phone || '-');
                div.innerHTML = `
                  <span class="booking-suggestion-name">${safeName}</span>
                  <small class="booking-suggestion-meta">${safePhone}</small>
                `;
                div.dataset.name = c.name;
                div.dataset.phone = c.phone;
                div.dataset.pickup = c.pickup_point || '';
                div.dataset.gmaps = c.address || '';
                div.addEventListener('click', function () {
                  fillCustomerFieldsFromLookup({
                    name: this.dataset.name || '',
                    phone: this.dataset.phone || '',
                    pickup_point: this.dataset.pickup || '',
                    address: this.dataset.gmaps || ''
                  }, true);
                  sugList.style.display = 'none';
                  setSearchLookupValue((this.dataset.name || '').toUpperCase());
                });
                sugList.appendChild(div);
              });
              sugList.style.display = 'block';
            } else {
              sugList.style.display = 'none';
            }
          } catch (e) {
            sugList.style.display = 'none';
          }
        }, 300);
      });

      /* ================== LISTENING FOR VALIDATION ================== */
      [nameEl, phoneEl, pickupEl].forEach(el => {
        if (el) el.addEventListener('input', validateBookingForm);
      });
      if (segmentEl) {
        segmentEl.addEventListener('change', () => {
          validateBookingForm();
          updateRegulerTotal();
        });
      }
      if (discountEl) {
        discountEl.addEventListener('input', () => {
          validateBookingForm();
          updateRegulerTotal();
        });
      }

      // Initialize validation state
      validateBookingForm();

      document.addEventListener('click', function (e) {
        if (!document.getElementById('suggestions')?.contains(e.target)) {
          if (sugList) sugList.style.display = 'none';
        }
      });

      document.getElementById('closeDetailModal')?.addEventListener('click', () => {
        closeDetailEditMode();
        closeCancelBookingConfirm();
        document.getElementById('detailModal').style.display = 'none';
      });
      document.getElementById('detailModal')?.addEventListener('click', (e) => {
        if (e.target.id === 'detailModal') {
          closeDetailEditMode();
          closeCancelBookingConfirm();
          e.target.style.display = 'none';
        }
      });
      document.getElementById('confirmCancelBookingPopup')?.addEventListener('click', (e) => {
        if (e.target.id === 'confirmCancelBookingPopup') {
          closeCancelBookingConfirm();
        }
      });

      /* ================== INIT ================== */
      (function () {
        // Get today's date in YYYY-MM-DD format
        const today = new Date();
        const todayStr = today.toISOString().split('T')[0];

        // Calculate max date (60 days from today)
        const maxDate = new Date();
        maxDate.setDate(maxDate.getDate() + 60);

        let datePicker = null;
        function ensureDatePicker() {
          if (datePicker) return datePicker;
          datePicker = new Datepicker(tanggalEl, {
            language: 'id',
            format: 'yyyy-mm-dd',
            autohide: true,
            todayHighlight: true,
            minDate: today,
            maxDate: maxDate,
            todayBtn: true,
            todayBtnMode: 1,
            clearBtn: false,
            prevArrow: '&lsaquo;',
            nextArrow: '&rsaquo;',
            container: 'body'
          });
          return datePicker;
        }

        requestAnimationFrame(() => {
          setTimeout(() => ensureDatePicker(), 0);
        });

        tanggalEl.addEventListener('focus', ensureDatePicker, { once: true });

        // Handle Today button click - force select today's date
        document.addEventListener('click', function (e) {
          if (e.target.classList.contains('today-btn') || e.target.closest('.today-btn')) {
            const picker = ensureDatePicker();
            picker.setDate(today);
            picker.hide();
            const dateStr = tanggalEl.value;
            loadRoutesByDate(dateStr);
          }
        });

        // Handle date change - load routes for selected date
        tanggalEl.addEventListener('changeDate', function (e) {
          const dateStr = tanggalEl.value;
          loadRoutesByDate(dateStr);
          // Reset downstream
          if (segmentEl) segmentEl.innerHTML = '<option value="" selected>-- Pilih Segment --</option>';
        });

        // Handle route change - load times and segments for selected route and date
        routeEl.addEventListener('change', () => {
          const r = routeEl.value;
          const d = tanggalEl.value;
          fillTimes(r, d);
          loadSegments(r); // Load segments for this route
        });

        jamEl.addEventListener('change', jamChanged);
        unitEl.addEventListener('change', refreshSeats);
        bookBtn.addEventListener('click', doBooking);
        renderSeatLayout();
        if (jamEl.options.length > 1) {
          jamChanged();
        }
      })();
      /* ================== LOAD SEGMENTS ================== */
      function loadSegments(routeName = '') {
        if (!segmentEl) return;
        segmentEl.innerHTML = '<option value="" selected>-- Memuat Segment... --</option>';
        const url = API_URL + '?action=getSegments' + (routeName ? '&route_name=' + encodeURIComponent(routeName) : '');
        fetch(url)
          .then(r => r.json())
          .then(js => {
            if (js.success && js.segments) {
              window.currentSegments = js.segments; // Cache segments
              let html = '<option value="" selected>-- Pilih Segment --</option>';
              if (js.segments.length === 0) {
                html = '<option value="" selected>-- Tidak ada segment untuk rute ini --</option>';
              }
              js.segments.forEach(s => {
                html += `<option value="${s.id}">${s.rute} (Rp ${parseInt(s.harga).toLocaleString('id-ID')})</option>`;
              });
              segmentEl.innerHTML = html;
            }
          })
          .catch(e => {
            console.error('Error loading segments', e);
            segmentEl.innerHTML = '<option value="" selected>-- Gagal memuat segment --</option>';
          });
      }


