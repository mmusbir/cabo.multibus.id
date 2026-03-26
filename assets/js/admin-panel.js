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

    const adminLazySections = new Set([
      'bookings',
      'charter-create',
      'customers',
      'routes',
      'schedules',
      'drivers',
      'segments',
      'users',
      'units',
      'booking-detail',
      'cancellations',
      'reports',
      'luggage_services',
      'luggage'
    ]);
    const adminSectionLoadPromises = {};

    function unwrapAdminSectionScript(code) {
      if (!code) return '';
      return code
        .replace(/(?:document|window)\.addEventListener\(\s*['"]DOMContentLoaded['"]\s*,\s*function\s*\(\)\s*\{([\s\S]*?)\}\s*\)\s*;?/g, '$1')
        .replace(/(?:document|window)\.addEventListener\(\s*['"]DOMContentLoaded['"]\s*,\s*\(\)\s*=>\s*\{([\s\S]*?)\}\s*\)\s*;?/g, '$1');
    }

    function executeAdminSectionScripts(container) {
      if (!container) return;
      container.querySelectorAll('script').forEach(function (script) {
        const code = unwrapAdminSectionScript(script.textContent || '');
        if (script.src) {
          const injected = document.createElement('script');
          injected.src = script.src;
          document.body.appendChild(injected);
          document.body.removeChild(injected);
        } else if (code.trim()) {
          try {
            window.eval(code);
          } catch (err) {
            console.error('Failed to init admin section script:', err);
          }
        }
        script.remove();
      });
    }

    async function ensureAdminSectionLoaded(id) {
      if (!adminLazySections.has(id)) {
        return document.getElementById(id);
      }

      const slot = document.getElementById('section-slot-' + id);
      if (!slot) {
        return document.getElementById(id);
      }
      if (slot.dataset.loaded === '1') {
        return document.getElementById(id);
      }
      if (adminSectionLoadPromises[id]) {
        return adminSectionLoadPromises[id];
      }

      slot.dataset.loading = '1';
      adminSectionLoadPromises[id] = (async function () {
        const url = new URL('admin.php', window.location.origin);
        url.searchParams.set('action', 'getSectionFragment');
        url.searchParams.set('section', id);

        const res = await fetch(url.toString(), { credentials: 'same-origin' });
        const js = await parseAdminApiResponse(res);
        if (!js.success || !js.html) {
          throw new Error(js.error || 'Gagal memuat panel');
        }

        slot.innerHTML = js.html;
        slot.dataset.loaded = '1';
        slot.dataset.loading = '0';
        executeAdminSectionScripts(slot);
        return document.getElementById(id);
      })();

      try {
        return await adminSectionLoadPromises[id];
      } finally {
        delete adminSectionLoadPromises[id];
      }
    }

    document.addEventListener('DOMContentLoaded', function () {
      // Show only active section and load data
      async function showSection(id) {
        document.querySelectorAll('.card').forEach(function (card) {
          card.style.display = 'none';
        });

        try {
          await ensureAdminSectionLoaded(id);
        } catch (err) {
          const fallback = document.getElementById(id);
          if (fallback) {
            fallback.style.display = 'block';
            fallback.innerHTML = '<div class="small admin-grid-message admin-grid-message-error">' + (err.message || 'Gagal memuat panel') + '</div>';
          }
          return;
        }

        if (id === 'charter-create' && window.bookingDashboardState) {
          window.bookingDashboardState.active = 'charters';
        }
        var active = document.getElementById(id);
        if (active) active.style.display = 'block';
        if (typeof window.syncAdminNavState === 'function') {
          window.syncAdminNavState(id);
        }
        bindAdminLazyControls(id);
        // Auto-load data for each section
        if (id === 'bookings') ajaxListLoad('bookings', buildAdminListParams('bookings', { page: 1, per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10), search: '' }));
        if (id === 'customers') ajaxListLoad('customers', { page: 1, per_page: parseInt(document.getElementById('customers_per_page')?.value || '25', 10) });
        if (id === 'schedules') ajaxListLoad('schedules', { page: 1, per_page: parseInt(document.getElementById('schedules_per_page')?.value || '25', 10) });
        if (id === 'users') ajaxListLoad('users', { page: 1, per_page: parseInt(document.getElementById('users_per_page')?.value || '25', 10) });
        if (id === 'routes' && typeof window.switchRouteTab === 'function') {
          window.switchRouteTab(window.currentRouteType || 'reguler');
        }
        if (id === 'cancellations') ajaxListLoad('cancellations', { page: 1, per_page: parseInt(document.getElementById('cancellations_per_page')?.value || '25', 10) });
        if (id === 'reports' && document.getElementById('btnGenerateReport')) {
          // Reports section is ready after its script is injected; no auto-fetch here.
        }
        if (id === 'luggage_services' && typeof window.loadLuggageServices === 'function') window.loadLuggageServices();
        if (id === 'luggage' && typeof window.loadLuggageData === 'function') window.loadLuggageData();
        if (id === 'units') { /* Units loaded via PHP, no AJAX list load needed yet */ }
      }
      window.showSectionById = showSection;
      function updateSectionFromHash() {
        var hash = window.location.hash.replace('#', '');
        if (hash) {
          showSection(hash);
          return;
        }
        var params = new URLSearchParams(window.location.search);
        var open = params.get('open');
        if (open) {
          showSection(open);
          return;
        }
        showSection('dashboard');
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
    function bindAdminLazyControls(sectionId) {
      const debounce = (fn, wait = 300) => {
        clearTimeout(window.__adminLazyDebounceTimer);
        window.__adminLazyDebounceTimer = setTimeout(fn, wait);
      };

      if (sectionId === 'customers') {
        const input = document.getElementById('search_customer_name_input');
        const btn = document.getElementById('searchCustomerBtn');
        const perPage = document.getElementById('customers_per_page');
        if (input && !input.dataset.boundLazy) {
          input.dataset.boundLazy = '1';
          input.addEventListener('input', function () {
            const search = this.value;
            debounce(function () {
              ajaxListLoad('customers', {
                page: 1,
                per_page: parseInt(perPage?.value || '25', 10),
                search: search
              });
            }, 250);
          });
        }
        if (btn) {
          btn.onclick = function () {
            ajaxListLoad('customers', {
              page: 1,
              per_page: parseInt(perPage?.value || '25', 10),
              search: input?.value || ''
            });
          };
        }
        if (perPage) {
          perPage.onchange = function () {
            ajaxListLoad('customers', {
              page: 1,
              per_page: parseInt(this.value || '25', 10),
              search: input?.value || ''
            });
          };
        }
      }

      if (sectionId === 'routes') {
        const input = document.getElementById('search_route_input');
        const btn = document.getElementById('searchRouteBtn');
        const perPage = document.getElementById('routes_per_page');
        if (input && !input.dataset.boundLazy) {
          input.dataset.boundLazy = '1';
          input.addEventListener('input', function () {
            const search = this.value;
            debounce(function () {
              ajaxListLoad('routes', {
                page: 1,
                per_page: parseInt(perPage?.value || '25', 10),
                search: search,
                type: window.currentRouteType || 'reguler'
              });
            }, 250);
          });
        }
        if (btn) {
          btn.onclick = function () {
            ajaxListLoad('routes', {
              page: 1,
              per_page: parseInt(perPage?.value || '25', 10),
              search: input?.value || '',
              type: window.currentRouteType || 'reguler'
            });
          };
        }
        if (perPage) {
          perPage.onchange = function () {
            ajaxListLoad('routes', {
              page: 1,
              per_page: parseInt(this.value || '25', 10),
              search: input?.value || '',
              type: window.currentRouteType || 'reguler'
            });
          };
        }
      }

      if (sectionId === 'schedules') {
        const input = document.getElementById('search_schedule_route_input');
        const btn = document.getElementById('searchScheduleRouteBtn');
        const perPage = document.getElementById('schedules_per_page');
        if (input && !input.dataset.boundLazy) {
          input.dataset.boundLazy = '1';
          input.addEventListener('input', function () {
            const search = this.value;
            debounce(function () {
              ajaxListLoad('schedules', {
                page: 1,
                per_page: parseInt(perPage?.value || '25', 10),
                search: search
              });
            }, 250);
          });
        }
        if (btn) {
          btn.onclick = function () {
            ajaxListLoad('schedules', {
              page: 1,
              per_page: parseInt(perPage?.value || '25', 10),
              search: input?.value || ''
            });
          };
        }
        if (perPage) {
          perPage.onchange = function () {
            ajaxListLoad('schedules', {
              page: 1,
              per_page: parseInt(this.value || '25', 10),
              search: input?.value || ''
            });
          };
        }
      }

      if (sectionId === 'users') {
        const input = document.getElementById('search_user_input');
        const btn = document.getElementById('searchUserBtn');
        const perPage = document.getElementById('users_per_page');
        if (input && !input.dataset.boundLazy) {
          input.dataset.boundLazy = '1';
          input.addEventListener('input', function () {
            const search = this.value;
            debounce(function () {
              ajaxListLoad('users', {
                page: 1,
                per_page: parseInt(perPage?.value || '25', 10),
                search: search
              });
            }, 250);
          });
        }
        if (btn) {
          btn.onclick = function () {
            ajaxListLoad('users', {
              page: 1,
              per_page: parseInt(perPage?.value || '25', 10),
              search: input?.value || ''
            });
          };
        }
        if (perPage) {
          perPage.onchange = function () {
            ajaxListLoad('users', {
              page: 1,
              per_page: parseInt(this.value || '25', 10),
              search: input?.value || ''
            });
          };
        }
      }

      if (sectionId === 'cancellations') {
        const input = document.getElementById('search_cancellations_input');
        const btn = document.getElementById('searchCancellationsBtn');
        const perPage = document.getElementById('cancellations_per_page');
        const typeInput = document.getElementById('log_activity_type');
        if (input && !input.dataset.boundLazy) {
          input.dataset.boundLazy = '1';
          input.addEventListener('input', function () {
            const search = this.value;
            debounce(function () {
              ajaxListLoad('cancellations', {
                page: 1,
                per_page: parseInt(perPage?.value || '25', 10),
                search: search,
                type: typeInput?.value || ''
              });
            }, 250);
          });
        }
        if (btn) {
          btn.onclick = function () {
            ajaxListLoad('cancellations', {
              page: 1,
              per_page: parseInt(perPage?.value || '25', 10),
              search: input?.value || '',
              type: typeInput?.value || ''
            });
          };
        }
        if (perPage) {
          perPage.onchange = function () {
            ajaxListLoad('cancellations', {
              page: 1,
              per_page: parseInt(this.value || '25', 10),
              search: input?.value || '',
              type: typeInput?.value || ''
            });
          };
        }
      }
    }

    function buildAdminListParams(target, baseParams) {
      const params = Object.assign({}, baseParams || {});
      if (typeof window.getAdminListParams === 'function') {
        return window.getAdminListParams(target, params);
      }
      return params;
    }

    window.setupAdminStaticListPagination = function setupAdminStaticListPagination(config) {
      const list = document.getElementById(config.listId);
      if (!list) return null;

      const info = document.getElementById(config.infoId);
      const pagination = document.getElementById(config.paginationId);
      const perPageSelect = document.getElementById(config.perPageId);
      const searchInput = config.searchInputId ? document.getElementById(config.searchInputId) : null;
      const itemSelector = config.itemSelector || '.admin-card-compact';
      const pageState = { value: 1 };

      if (!list.dataset.staticPagerBound) {
        if (searchInput) {
          searchInput.addEventListener('input', function () {
            pageState.value = 1;
            render();
          });
        }

        if (perPageSelect) {
          perPageSelect.addEventListener('change', function () {
            pageState.value = 1;
            render();
          });
        }

        if (pagination) {
          pagination.addEventListener('click', function (event) {
            const trigger = event.target.closest('.admin-static-page');
            if (!trigger) return;
            event.preventDefault();
            pageState.value = parseInt(trigger.getAttribute('data-page') || '1', 10) || 1;
            render();
          });
        }

        list.dataset.staticPagerBound = '1';
      }

      function getItems() {
        return Array.from(list.querySelectorAll(itemSelector));
      }

      function ensureEmptyState() {
        let emptyState = list.querySelector('[data-static-empty]');
        if (!emptyState) {
          if (list.tagName === 'TBODY') {
            emptyState = document.createElement('tr');
            emptyState.setAttribute('data-static-empty', '1');
            emptyState.style.display = 'none';
            const cell = document.createElement('td');
            cell.colSpan = parseInt(list.getAttribute('data-colspan') || '6', 10) || 6;
            cell.className = 'customers-table-empty';
            cell.textContent = 'Tidak ada data.';
            emptyState.appendChild(cell);
            list.appendChild(emptyState);
          } else {
            emptyState = document.createElement('div');
            emptyState.setAttribute('data-static-empty', '1');
            emptyState.className = 'small admin-grid-message';
            emptyState.textContent = 'Tidak ada data.';
            emptyState.style.display = 'none';
            list.appendChild(emptyState);
          }
        }
        return emptyState;
      }

      function render() {
        const items = getItems();
        const keyword = (searchInput?.value || '').trim().toLowerCase();
        const perPage = Math.max(1, parseInt(perPageSelect?.value || '25', 10) || 25);
        const filtered = items.filter((item) => {
          const haystack = (item.dataset.searchText || item.textContent || '').toLowerCase();
          return keyword === '' || haystack.includes(keyword);
        });

        const total = filtered.length;
        const totalPages = Math.max(1, Math.ceil(total / perPage));
        pageState.value = Math.min(Math.max(1, pageState.value), totalPages);

        const start = (pageState.value - 1) * perPage;
        const end = start + perPage;
        const visibleItems = new Set(filtered.slice(start, end));
        const emptyState = ensureEmptyState();

        items.forEach((item) => {
          if (!item.dataset.originalDisplay) {
            const currentDisplay = getComputedStyle(item).display;
            item.dataset.originalDisplay = currentDisplay === 'none' ? (list.tagName === 'TBODY' ? 'table-row' : 'flex') : currentDisplay;
          }
          item.style.display = visibleItems.has(item) ? item.dataset.originalDisplay : 'none';
        });

        emptyState.style.display = total === 0 ? (list.tagName === 'TBODY' ? 'table-row' : 'block') : 'none';

        if (info) {
          info.textContent = 'Total: ' + total;
        }

        if (pagination) {
          if (total <= perPage) {
            pagination.innerHTML = '';
          } else {
            let html = '<div class="pagination-container">';
            for (let page = 1; page <= totalPages; page++) {
              if (page === pageState.value) {
                html += '<span class="badge active">' + page + '</span>';
              } else {
                html += '<a href="#" class="badge admin-static-page" data-page="' + page + '">' + page + '</a>';
              }
            }
            html += '</div>';
            pagination.innerHTML = html;
          }
        }
      }

      render();

      return {
        refresh: function () {
          pageState.value = 1;
          render();
        },
        render: render
      };
    };

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
            const isTableBody = tbody.tagName === 'TBODY';
            const colspan = tbody.getAttribute('data-colspan') || '6';
            tbody.innerHTML = isTableBody
              ? '<tr><td colspan="' + colspan + '" class="customers-table-empty">Error: ' + (js.error || 'Unknown error') + '</td></tr>'
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
        if (target === 'bookings') { attachEditBookingHandlers(); attachTableCancelHandlers(); attachTableMarkPaidHandlers(); attachMarkAllPaidHandler(); }
        if (target === 'charters') { attachCharterHandlers(); }
        if (target === 'luggage') { attachLuggageHandlers(); }
      } catch (e) {
        if (tbody) {
          const isTableBody = tbody.tagName === 'TBODY';
          const colspan = tbody.getAttribute('data-colspan') || '6';
          tbody.innerHTML = isTableBody
            ? '<tr><td colspan="' + colspan + '" class="customers-table-empty">Kesalahan koneksi</td></tr>'
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
        ajaxListLoad(target, buildAdminListParams(target, { page: 1, per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10), search: search }));
      };
    }
    // Auto-search on typing with debounce
    if (document.getElementById('search_name_input')) {
      document.getElementById('search_name_input').addEventListener('input', function () {
        clearTimeout(searchDebounceTimer);
        const search = this.value;
        searchDebounceTimer = setTimeout(function () {
          const target = getActiveBookingTarget();
          ajaxListLoad(target, buildAdminListParams(target, { page: 1, per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10), search: search }));
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
        ajaxListLoad(target, buildAdminListParams(target, {
          page: 1,
          per_page: parseInt(this.value, 10),
          search: document.getElementById('search_name_input')?.value || ''
        }));
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
          if (target === 'ls') {
            if (typeof window.goToLsPage === 'function') {
              window.goToLsPage(page);
            }
            return;
          }
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
            params.type = document.getElementById('log_activity_type')?.value || '';
          } else if (target === 'bookingsHistory' || target === 'chartersHistory') {
            params.per_page = parseInt(document.getElementById('bookings_per_page')?.value || '25', 10);
            params.search = document.getElementById('search_name_input')?.value || '';
          } else if (target === 'luggage') {
            params.per_page = parseInt(document.getElementById('bookings_per_page')?.value || '25', 10);
            params.search = document.getElementById('search_name_input')?.value || '';
          }
          params = buildAdminListParams(target, params);
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
        ajaxListLoad('cancellations', {
          page: 1,
          per_page: parseInt(document.getElementById('cancellations_per_page')?.value || '25', 10),
          search: search,
          type: document.getElementById('log_activity_type')?.value || ''
        });
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
          if (typeof window.attachBookingDetailCardToggles === 'function') {
            window.attachBookingDetailCardToggles();
          }
          if (typeof window.sortBookingDetailCards === 'function') {
            window.sortBookingDetailCards();
          }
          if (typeof window.filterBookingDetailCards === 'function') {
            window.filterBookingDetailCards();
          }
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

    function sortBookingDetailCards() {
      const list = document.getElementById('passengerList');
      const sortSelect = document.getElementById('booking_detail_sort');
      if (!list || !sortSelect) return;

      const cards = Array.from(list.querySelectorAll('.booking-detail-card'));
      if (!cards.length) return;

      const mode = sortSelect.value || 'seat';
      const fragment = document.createDocumentFragment();
      const sorted = cards.slice().sort((a, b) => {
        if (mode === 'name') {
          const nameA = (a.dataset.name || '').trim();
          const nameB = (b.dataset.name || '').trim();
          return nameA.localeCompare(nameB, 'id', { sensitivity: 'base' });
        }

        if (mode === 'payment') {
          const rankA = parseInt(a.dataset.paymentRank || '99', 10);
          const rankB = parseInt(b.dataset.paymentRank || '99', 10);
          if (rankA !== rankB) return rankA - rankB;
          const seatA = (a.dataset.seat || '').trim();
          const seatB = (b.dataset.seat || '').trim();
          return seatA.localeCompare(seatB, undefined, { numeric: true, sensitivity: 'base' });
        }

        const seatA = (a.dataset.seat || '').trim();
        const seatB = (b.dataset.seat || '').trim();
        return seatA.localeCompare(seatB, undefined, { numeric: true, sensitivity: 'base' });
      });

      sorted.forEach((card) => fragment.appendChild(card));
      list.appendChild(fragment);
    }
    window.sortBookingDetailCards = sortBookingDetailCards;

    function filterBookingDetailCards() {
      const list = document.getElementById('passengerList');
      const searchInput = document.getElementById('booking_detail_search');
      if (!list || !searchInput) return;

      const cards = Array.from(list.querySelectorAll('.booking-detail-card'));
      const query = (searchInput.value || '').trim().toLowerCase();
      const shell = list.querySelector('.view-booking-list-shell');
      const grid = list.querySelector('.booking-detail-grid');
      if (!grid) return;

      let emptyState = shell?.querySelector('[data-booking-detail-filter-empty]');
      if (!emptyState && shell) {
        emptyState = document.createElement('div');
        emptyState.className = 'admin-empty-state view-empty-state';
        emptyState.setAttribute('data-booking-detail-filter-empty', '1');
        emptyState.textContent = 'Data penumpang tidak ditemukan untuk pencarian ini.';
        emptyState.style.display = 'none';
        shell.appendChild(emptyState);
      }

      let visibleCount = 0;
      cards.forEach((card) => {
        const haystack = [
          card.dataset.name || '',
          card.dataset.seat || '',
          card.dataset.phone || '',
          card.dataset.pickup || '',
          card.dataset.paymentLabel || ''
        ].join(' ').toLowerCase();

        const visible = query === '' || haystack.includes(query);
        card.style.display = visible ? '' : 'none';
        if (visible) visibleCount += 1;
      });

      if (emptyState) {
        emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
      }
    }
    window.filterBookingDetailCards = filterBookingDetailCards;

    function attachBookingDetailCardToggles() {
      document.querySelectorAll('.booking-detail-toggle').forEach((btn) => {
        btn.onclick = function (e) {
          e.preventDefault();
          const card = this.closest('.booking-detail-card');
          if (!card) return;
          card.classList.toggle('is-expanded');
        };
      });
    }
    window.attachBookingDetailCardToggles = attachBookingDetailCardToggles;

    if (document.getElementById('booking_detail_sort')) {
      document.getElementById('booking_detail_sort').addEventListener('change', function () {
        sortBookingDetailCards();
        filterBookingDetailCards();
      });
    }
    if (document.getElementById('booking_detail_search')) {
      document.getElementById('booking_detail_search').addEventListener('input', function () {
        filterBookingDetailCards();
      });
    }
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
      attachEditBookingHandlers();
      attachCancelHandlers();
      attachSeatLayoutMarkPaidHandlers();
      attachMarkAllPaidHandler();
    }
    const originalLoadBookingDetailPassengers = window.loadBookingDetailPassengers;
    window.loadBookingDetailPassengers = async function () {
      if (typeof originalLoadBookingDetailPassengers === 'function') {
        await originalLoadBookingDetailPassengers();
      }
      await refreshBookingDetailInteractiveState();
    };
    attachCopyHandlers();
    attachEditBookingHandlers();
    attachCancelHandlers();
    attachSeatLayoutMarkPaidHandlers();
    attachMarkAllPaidHandler();
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
                  ajaxListLoad('bookings', buildAdminListParams('bookings', { page: 1, per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10), search: document.getElementById('search_name_input')?.value || '' }));
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
                  ajaxListLoad('bookings', buildAdminListParams('bookings', { page: 1, per_page: parseInt(document.getElementById('bookings_per_page')?.value || '25', 10), search: document.getElementById('search_name_input')?.value || '' }));
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
    function attachMarkAllPaidHandler() {
      document.querySelectorAll('.mark-all-paid-btn').forEach((btn) => {
        btn.onclick = function () {
          const rute    = this.getAttribute('data-rute');
          const tanggal = this.getAttribute('data-tanggal');
          const jam     = this.getAttribute('data-jam');
          const unit    = this.getAttribute('data-unit');
          const countMatch = this.textContent.match(/\((\d+)\)/);
          const count   = countMatch ? countMatch[1] : '';
          const originalHtml = btn.innerHTML;
          customConfirm(
            'Tandai ' + (count ? count + ' penumpang' : 'semua penumpang') + ' pada keberangkatan ini menjadi LUNAS?',
            async () => {
              btn.disabled = true;
              btn.innerHTML = '<span class="spinner-small"></span><span>Memproses...</span>';
              try {
                const url = `admin.php?mark_all_paid=1&rute=${encodeURIComponent(rute)}&tanggal=${encodeURIComponent(tanggal)}&jam=${encodeURIComponent(jam)}&unit=${encodeURIComponent(unit)}`;
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const js  = await res.json();
                if (js.success) {
                  await customAlert('Berhasil! ' + (js.updated || 0) + ' penumpang pada keberangkatan ini telah ditandai Lunas.', 'Lunas Semua');
                  if (window.bookingDashboardState?.active === 'bookings' && typeof ajaxListLoad === 'function') {
                    const perPage = parseInt(document.getElementById('bookings_per_page')?.value || '25', 10);
                    const params = typeof buildAdminListParams === 'function'
                      ? buildAdminListParams('bookings', { page: 1, per_page: perPage, search: document.getElementById('search_name_input')?.value || '' })
                      : { page: 1, per_page: perPage, search: '' };
                    ajaxListLoad('bookings', params);
                  }
                  if (document.getElementById('booking-detail')?.style.display !== 'none' && typeof window.loadBookingDetailPassengers === 'function') {
                    window.loadBookingDetailPassengers();
                  }
                } else {
                  customAlert('Gagal: ' + (js.error || 'unknown'));
                  btn.disabled = false;
                  btn.innerHTML = originalHtml;
                }
              } catch (e) {
                customAlert('Kesalahan koneksi: ' + e);
                btn.disabled = false;
                btn.innerHTML = originalHtml;
              }
            },
            'Konfirmasi Lunas Semua',
            'success'
          );
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
          const pembayaran = this.getAttribute('data-pembayaran')
            || (this.closest('.admin-card-compact') ? this.closest('.admin-card-compact').querySelector('.status-tag')?.textContent.trim() : '')
            || 'Belum Lunas';

          document.getElementById('edit_booking_id').value = id;
          document.getElementById('edit_seat').value = seat;
          document.getElementById('edit_pickup').value = pickup;

                    let currentTanggal = tanggal;
          const dateInput = document.getElementById('edit_tanggal');
          if (dateInput) {
             dateInput.value = tanggal;
             dateInput.onchange = function() {
                 currentTanggal = this.value;
                 fetchAvailableUnits();
             };
          }

          const unitSelect = document.getElementById('edit_unit');
          async function fetchAvailableUnits() {
            if (unitSelect) {
              unitSelect.innerHTML = '<option value="">Memuat...</option>';
              try {
                const res = await fetch(`admin.php?action=getAvailableUnits&rute=${encodeURIComponent(rute)}&tanggal=${currentTanggal}&jam=${jam}`);
                const js = await res.json();
                if (js.success) {
                  unitSelect.innerHTML = '';
                  const maxUnits = js.units || 1;
                  for (let i = 1; i <= maxUnits; i++) {
                    const opt = document.createElement('option');
                    opt.value = i;
                    opt.textContent = 'Unit ' + i;
                    if (String(i) === String(unit) && currentTanggal === tanggal) opt.selected = true;
                    unitSelect.appendChild(opt);
                  }
                  updateSeats(unitSelect.value, currentTanggal === tanggal ? seat : null);
                } else {
                  unitSelect.innerHTML = `<option value="${unit}">Unit ${unit}</option>`;
                  updateSeats(unit, seat);
                }
              } catch (err) {
                unitSelect.innerHTML = `<option value="${unit}">Unit ${unit}</option>`;
                updateSeats(unit, seat);
              }
            }
          }

          async function updateSeats(curUnit, curSeat) {
            const seatSelect = document.getElementById('edit_seat');
            if (!seatSelect) return;
            seatSelect.innerHTML = '<option value="">Memuat kursi...</option>';
            try {
              const res = await fetch(`admin.php?action=getScheduleSeats&rute=${encodeURIComponent(rute)}&tanggal=${currentTanggal}&jam=${jam}&unit=${curUnit}`);
              const js = await res.json();
              if (js.success) {
                seatSelect.innerHTML = '<option value="">Pilih Kursi</option>';
                const occupied = js.occupied || [];
                const layout = js.layout || [];
                let seats = [];
                layout.forEach(row => { row.forEach(cell => { if (cell && cell.type === 'seat' && cell.label) seats.push(cell.label); }); });
                if (seats.length === 0) { for (let i = 1; i <= 8; i++) seats.push(String(i)); }

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
              } else {
                seatSelect.innerHTML = `<option value="${curSeat || ''}">${curSeat || 'Semua kursi penuh/gagal'}</option>`;
              }
            } catch (err) {
              seatSelect.innerHTML = `<option value="${curSeat || ''}">${curSeat || 'Kursi Default'}</option>`;
            }
          }

          if (unitSelect) {
            unitSelect.onchange = function () {
              updateSeats(this.value, currentTanggal === tanggal ? seat : null);
            };
          }
          fetchAvailableUnits();

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
    function attachCharterHandlers() {
      document.querySelectorAll('.edit-charter-btn').forEach(btn => {
        btn.onclick = async function (e) {
          e.preventDefault();
          const id = this.getAttribute('data-id');
          try {
            const res = await fetch('admin.php?action=get_charter&id=' + id);
            const js = await parseAdminApiResponse(res);
            if (js.success) {
              const d = js.data;
              document.getElementById('edit_charter_id').value = d.id;
              document.getElementById('edit_charter_name').value = d.name || '';
              document.getElementById('edit_charter_company').value = d.company_name || '';
              document.getElementById('edit_charter_phone').value = d.phone || '';
              document.getElementById('edit_charter_price').value = d.price || 0;
              document.getElementById('edit_charter_start').value = d.start_date || '';
              document.getElementById('edit_charter_end').value = d.end_date || '';
              document.getElementById('edit_charter_time').value = (d.departure_time || '08:00').substring(0, 5);
              document.getElementById('edit_charter_pickup').value = d.pickup_point || '';
              document.getElementById('edit_charter_drop').value = d.drop_point || '';
              document.getElementById('edit_charter_layanan').value = d.layanan || '';
              document.getElementById('edit_charter_bop_val').value = d.bop_price || 0;

              // Populate Unit Select
              const unitRes = await fetch('admin.php?action=get_units');
              const unitJs = await parseAdminApiResponse(unitRes);
              const uSelect = document.getElementById('edit_charter_unit');
              if (unitJs.success && uSelect) {
                uSelect.innerHTML = '<option value="">-- Unit --</option>';
                unitJs.units.forEach(u => {
                  const opt = document.createElement('option');
                  opt.value = u.id;
                  opt.textContent = (u.nopol || '-') + ' - ' + (u.merek || 'Unit');
                  if (String(u.id) === String(d.unit_id)) opt.selected = true;
                  uSelect.appendChild(opt);
                });
              }

              // Populate Driver Select
              const driverRes = await fetch('admin.php?action=get_drivers');
              const driverJs = await parseAdminApiResponse(driverRes);
              const drSelect = document.getElementById('edit_charter_driver');
              if (driverJs.success && drSelect) {
                drSelect.innerHTML = '<option value="">-- Pilih Driver --</option>';
                driverJs.drivers.forEach(dr => {
                  const opt = document.createElement('option');
                  opt.value = dr.nama;
                  opt.textContent = dr.nama;
                  if (dr.nama === d.driver_name) opt.selected = true;
                  drSelect.appendChild(opt);
                });
              }

              // Master Routes
              const routeRes = await fetch('admin.php?action=get_charter_routes');
              const routeJs = await parseAdminApiResponse(routeRes);
              const rSelect = document.getElementById('edit_charter_route_id');
              if (routeJs.success && rSelect) {
                rSelect.innerHTML = '<option value="">-- Master Rute Carter --</option>';
                routeJs.routes.forEach(r => {
                  const opt = document.createElement('option');
                  opt.value = r.id;
                  opt.textContent = r.name || (r.origin + ' - ' + r.destination);
                  opt.dataset.pickup = r.origin;
                  opt.dataset.drop = r.destination;
                  opt.dataset.price = r.rental_price;
                  opt.dataset.bop = r.bop_price;
                  rSelect.appendChild(opt);
                });
                rSelect.onchange = function() {
                  const sel = this.options[this.selectedIndex];
                  if (!sel.value) return;
                  document.getElementById('edit_charter_pickup').value = sel.dataset.pickup || '';
                  document.getElementById('edit_charter_drop').value = sel.dataset.drop || '';
                  document.getElementById('edit_charter_price').value = sel.dataset.price || 0;
                  document.getElementById('edit_charter_bop_val').value = sel.dataset.bop || 0;
                };
              }

              const modal = document.getElementById('editCharterModal');
              modal.style.display = 'flex';
              setTimeout(() => modal.classList.add('show'), 10);
            }
          } catch (err) {
            customAlert('Gagal mengambil data carter.');
          }
        };
      });

      document.querySelectorAll('.delete-charter-btn').forEach(btn => {
        btn.onclick = function (e) {
          e.preventDefault();
          const id = this.getAttribute('data-id');
          customConfirm('Hapus data carter ini?', async () => {
            try {
              const res = await fetch('admin.php?action=delete_charter&id=' + id);
              const js = await parseAdminApiResponse(res);
              if (js.success) {
                ajaxListLoad('charters', { page: 1 });
              } else {
                customAlert('Gagal menghapus: ' + (js.error || 'unknown'));
              }
            } catch (err) {
              customAlert('Kesalahan koneksi.');
            }
          });
        };
      });

      document.querySelectorAll('.bop-done-btn').forEach(btn => {
        btn.onclick = function (e) {
          e.preventDefault();
          const id = this.getAttribute('data-id');
          customConfirm('Tandai BOP sebagai LUNAS SEMUA?', async () => {
            try {
              const res = await fetch('admin.php?action=toggle_bop&id=' + id);
              const js = await parseAdminApiResponse(res);
              if (js.success) {
                ajaxListLoad('charters', { page: 1 });
              }
            } catch (err) {
              customAlert('Kesalahan koneksi.');
            }
          }, 'Konfirmasi Pembayaran', 'success');
        };
      });

      document.querySelectorAll('.copy-charter-btn').forEach(btn => {
        btn.onclick = function (e) {
          e.preventDefault();
          const card = this.closest('.admin-card-compact');
          if (!card) return;
          const name = card.querySelector('.ac-title')?.innerText || '';
          const phone = card.querySelector('.ac-stat:nth-child(2) span')?.innerText || '';
          const route = card.querySelector('.ac-stat:nth-child(3) span')?.innerText || '';
          const date = card.querySelector('.ac-stat:nth-child(1) span')?.innerText || '';
          
          const text = `DETAIL CARTER\nNama: ${name}\nHP: ${phone}\nRute: ${route}\nTanggal: ${date}`;
          if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => customAlert('Detail carter disalin!'));
          } else {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand("copy");
            document.body.removeChild(textArea);
            customAlert('Detail carter disalin!');
          }
        };
      });
    }

    if (document.getElementById('closeEditCharterModal')) {
      document.getElementById('closeEditCharterModal').onclick = function () {
        const modal = document.getElementById('editCharterModal');
        modal.classList.remove('show');
        setTimeout(() => { modal.style.display = 'none'; }, 300);
      };
    }
    if (document.getElementById('editCharterModal')) {
      document.getElementById('editCharterModal').onclick = function (e) {
        if (e.target === this) {
          this.classList.remove('show');
          setTimeout(() => { this.style.display = 'none'; }, 300);
        }
      };
    }

    if (document.getElementById('editCharterForm')) {
      document.getElementById('editCharterForm').onsubmit = async function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'update_charter');
        try {
          const res = await fetch('admin.php?action=update_charter', {
            method: 'POST',
            body: formData
          });
          const js = await parseAdminApiResponse(res);
          if (js.success) {
            document.getElementById('closeEditCharterModal').click();
            ajaxListLoad('charters', { page: 1 });
          } else {
            customAlert('Gagal menyimpan: ' + (js.error || 'unknown'));
          }
        } catch (err) {
          customAlert('Kesalahan koneksi saat update charter.');
        }
      };
    }

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
        const js = await parseAdminApiResponse(res); // using my helper

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


