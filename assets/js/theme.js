/* ===== Dark/light theme controller =====
   Priority: force_dark (server) > user preference (DB/localStorage) > OS preference. */

(function () {
  const FORCE_DARK = document.documentElement.dataset.forceDark === '1';
  const USER_PREF = document.documentElement.dataset.userTheme || 'auto'; // light | dark | auto

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

  // Listen for OS pref changes when in auto
  const mq = window.matchMedia('(prefers-color-scheme: dark)');
  if (mq.addEventListener) mq.addEventListener('change', () => { if (!FORCE_DARK && (localStorage.getItem('theme') || USER_PREF) === 'auto') applyTheme(null); });

  // Toggle handler
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-theme-toggle]');
    if (!btn) return;
    if (FORCE_DARK) { window.EN && window.EN.toast('Force Dark Mode is on.', 'warning'); return; }
    const cur = document.documentElement.getAttribute('data-theme');
    const next = cur === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    localStorage.setItem('theme', next);
    // best-effort persist to server
    if (window.EN) {
      window.EN.api('/api/profile/update.php', { body: { theme_preference: next } }).catch(() => {});
    }
  });
})();
