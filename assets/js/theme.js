/* ===== Dark/light theme controller =====
   Priority: force_dark (server) > user preference (DB/localStorage) > OS preference. */

(function () {
  const FORCE_DARK = document.documentElement.dataset.forceDark === '1';
  const USER_PREF = document.documentElement.dataset.userTheme || 'auto'; // light | dark | auto

  const ICONS = {
    moon: '<svg class="theme-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 13.5A8.5 8.5 0 1 1 10.5 3a7 7 0 1 0 10.5 10.5Z" fill="currentColor"/></svg>',
    sun: '<svg class="theme-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="4" fill="currentColor"/><path d="M12 2v2.5M12 19.5V22M4.93 4.93l1.77 1.77M17.3 17.3l1.77 1.77M2 12h2.5M19.5 12H22M4.93 19.07l1.77-1.77M17.3 6.7l1.77-1.77" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
  };

  function getEffectiveTheme() {
    const theme = document.documentElement.getAttribute('data-theme');
    if (theme === 'light' || theme === 'dark') return theme;
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  }

  function syncThemeToggle() {
    const btn = document.querySelector('[data-theme-toggle]');
    if (!btn) return;
    const theme = getEffectiveTheme();
    const nextTheme = theme === 'dark' ? 'light' : 'dark';
    btn.innerHTML = theme === 'dark' ? ICONS.sun : ICONS.moon;
    btn.setAttribute('aria-label', `Switch to ${nextTheme} mode`);
    btn.setAttribute('title', `Switch to ${nextTheme} mode`);
  }

  function applyTheme(mode) {
    if (mode === 'light' || mode === 'dark') {
      document.documentElement.setAttribute('data-theme', mode);
    } else {
      document.documentElement.removeAttribute('data-theme');
    }
  }

  function resolve() {
    if (FORCE_DARK) return 'dark';
    const saved = localStorage.getItem('theme') || USER_PREF;
    if (saved === 'light' || saved === 'dark') return saved;
    return null; // auto -> let CSS prefers-color-scheme handle it
  }

  applyTheme(resolve());
  syncThemeToggle();

  // Listen for OS pref changes when in auto
  const mq = window.matchMedia('(prefers-color-scheme: dark)');
  if (mq.addEventListener) mq.addEventListener('change', () => {
    if (!FORCE_DARK && (localStorage.getItem('theme') || USER_PREF) === 'auto') {
      applyTheme(null);
      syncThemeToggle();
    }
  });

  // Toggle handler
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-theme-toggle]');
    if (!btn) return;
    if (FORCE_DARK) { window.EN && window.EN.toast('Force Dark Mode is on.', 'warning'); return; }
    const cur = document.documentElement.getAttribute('data-theme');
    const next = cur === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    localStorage.setItem('theme', next);
    syncThemeToggle();
    // best-effort persist to server
    if (window.EN) {
      window.EN.api('/api/profile/update.php', { body: { theme_preference: next } }).catch(() => {});
    }
  });
})();
