(function () {
  var STORAGE_KEY = 'siteTheme';
  var root = document.documentElement;

  function getDefaultTheme() {
    var preset = root.getAttribute('data-default-theme');
    if (preset === 'light' || preset === 'dark') return preset;
    return root.classList.contains('dark') ? 'dark' : 'light';
  }

  function getStoredTheme() {
    try {
      var stored = window.localStorage.getItem(STORAGE_KEY);
      if (stored === 'light' || stored === 'dark') return stored;
    } catch (err) {}
    return null;
  }

  function getTheme() {
    return getStoredTheme() || getDefaultTheme();
  }

  function updateButtons(theme) {
    var nextTheme = theme === 'dark' ? 'light' : 'dark';
    var nextLabel = nextTheme === 'dark' ? 'Aktifkan mode malam' : 'Aktifkan mode siang';
    document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
      button.setAttribute('aria-label', nextLabel);
      button.setAttribute('title', nextLabel);
      button.dataset.themeTarget = nextTheme;
      var icon = button.querySelector('[data-theme-icon]');
      if (icon) {
        icon.className = 'fa-solid fa-icon ' + (theme === 'dark' ? 'fa-sun' : 'fa-moon');
      }
      var label = button.querySelector('[data-theme-label]');
      if (label) {
        label.textContent = theme === 'dark' ? 'Mode Siang' : 'Mode Malam';
      }
    });
  }

  function applyTheme(theme) {
    root.setAttribute('data-theme', theme);
    root.classList.toggle('dark', theme === 'dark');
    root.classList.toggle('light', theme === 'light');
    if (document.body) {
      document.body.classList.toggle('theme-dark', theme === 'dark');
      document.body.classList.toggle('theme-light', theme === 'light');
    }
    updateButtons(theme);
  }

  function persistTheme(theme) {
    try {
      window.localStorage.setItem(STORAGE_KEY, theme);
    } catch (err) {}
  }

  function toggleTheme() {
    var current = root.getAttribute('data-theme') || getTheme();
    var next = current === 'dark' ? 'light' : 'dark';
    persistTheme(next);
    applyTheme(next);
  }

  function bindButtons() {
    document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
      if (button.dataset.themeBound === '1') return;
      button.dataset.themeBound = '1';
      button.addEventListener('click', toggleTheme);
    });
  }

  applyTheme(getTheme());

  document.addEventListener('DOMContentLoaded', function () {
    applyTheme(getTheme());
    bindButtons();
  });

  window.applySiteTheme = applyTheme;
  window.toggleSiteTheme = toggleTheme;
})();
