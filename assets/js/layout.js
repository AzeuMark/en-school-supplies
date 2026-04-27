/* ===== Sidebar collapse, profile dropdown, navbar clock, badge refresh ===== */

(function () {
  const shell = document.querySelector('.app-shell');

  // Sidebar collapse / mobile open
  const DRAWER_KEY = 'sidebarDrawerOpen';
  if (shell && localStorage.getItem(DRAWER_KEY) === '1') shell.classList.add('sidebar-open');

  document.addEventListener('click', function (e) {
    const ham = e.target.closest('[data-sidebar-toggle]');
    const backdrop = e.target.closest('[data-sidebar-backdrop]');
    const navLink = e.target.closest('.sidebar-link');

    if (backdrop && shell) {
      shell.classList.remove('sidebar-open');
      localStorage.setItem(DRAWER_KEY, '0');
    }

    if (ham && shell) {
      shell.classList.toggle('sidebar-open');
      localStorage.setItem(DRAWER_KEY, shell.classList.contains('sidebar-open') ? '1' : '0');
      ham.setAttribute('aria-expanded', shell.classList.contains('sidebar-open') ? 'true' : 'false');
    }
    if (navLink && shell) {
      shell.classList.remove('sidebar-open');
      localStorage.setItem(DRAWER_KEY, '0');
      const toggle = document.querySelector('[data-sidebar-toggle]');
      if (toggle) toggle.setAttribute('aria-expanded', 'false');
    }
    // close mobile sidebar when clicking backdrop area
    if (shell && shell.classList.contains('sidebar-open') && !e.target.closest('.app-sidebar') && !e.target.closest('[data-sidebar-toggle]')) {
      shell.classList.remove('sidebar-open');
      localStorage.setItem(DRAWER_KEY, '0');
      const toggle = document.querySelector('[data-sidebar-toggle]');
      if (toggle) toggle.setAttribute('aria-expanded', 'false');
    }
    // Profile dropdown
    const chip = e.target.closest('[data-profile-chip]');
    document.querySelectorAll('.profile-chip.open').forEach(c => { if (c !== chip) c.classList.remove('open'); });
    if (chip) chip.classList.toggle('open');
    else if (!e.target.closest('.profile-dropdown')) {
      document.querySelectorAll('.profile-chip.open').forEach(c => c.classList.remove('open'));
    }
  });

  // Navbar clock
  function tick() {
    const el = document.querySelector('[data-navbar-clock]');
    if (!el) return;
    const d = new Date();
    const timezone = el.dataset.timezone || 'Asia/Manila';
    el.textContent = d.toLocaleString('en-US', {
      hour: 'numeric', minute: '2-digit', second: '2-digit', hour12: true,
      timeZone: timezone,
    });
  }
  tick();
  setInterval(tick, 1000);

  // Badge refresh every 30s
  async function refreshBadges() {
    if (!document.querySelector('[data-badge]')) return;
    try {
      const res = await fetch(EN.BASE + '/api/badges.php', { credentials: 'same-origin' });
      if (!res.ok) return;
      const data = await res.json();
      if (!data.ok) return;
      document.querySelectorAll('[data-badge]').forEach(el => {
        const key = el.dataset.badge;
        const count = data.counts[key] || 0;
        el.textContent = count;
        el.style.display = count > 0 ? '' : 'none';
      });
    } catch (_) {}
  }
  refreshBadges();
  setInterval(refreshBadges, 30000);
})();
