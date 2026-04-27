<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/helpers.php';

$store_name  = get_setting('store_name', config('system.store_name'));
$logo_path   = get_setting('logo_path', config('system.logo_path'));
$status      = get_setting('system_status', 'online');
$force_dark  = get_setting('force_dark', '0');
$disable_no_login = get_setting('disable_no_login_orders', '0') === '1';
$idle_seconds = (int)get_setting('kiosk_idle_seconds', '90');

// Get categories for filter
$categories = [];
try { $categories = $pdo->query("SELECT id, category_name FROM item_categories ORDER BY category_name")->fetchAll(); } catch (Throwable $e) {}
?>
<!doctype html>
<html lang="en" data-force-dark="<?= e($force_dark) ?>" data-page-default-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title>Order Kiosk — <?= e($store_name) ?></title>
<link rel="icon" type="image/png" href="<?= e(url('/' . ltrim($logo_path, '/'))) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/global.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/components.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/cart-drawer.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/kiosk.css')) ?>">
</head>
<body class="kiosk-body">

<header class="kiosk-header">
  <div class="ks-logo-wrap">
    <img src="<?= e(url('/' . ltrim($logo_path, '/'))) ?>" alt="" onerror="this.parentElement.style.background='rgba(255,255,255,.25)'">
  </div>
  <div class="ks-brand">
    <h1><?= e($store_name) ?></h1>
    <div class="ks-tagline">Self-service kiosk &mdash; quick &amp; easy ordering</div>
  </div>
  <div class="spacer"></div>
  <div class="ks-right">
    <div class="ks-time" id="kiosk-time"></div>
    <button class="ks-theme-btn" id="ks-theme-toggle" title="Toggle dark/light mode" aria-label="Toggle theme">🌙</button>
  </div>
</header>

<?php if ($disable_no_login || $status === 'offline' || $status === 'maintenance'): ?>
  <div class="kiosk-disabled">
    <div class="kd-card">
      <div class="kd-icon">🚫</div>
      <h2>Kiosk Ordering Unavailable</h2>
      <p class="text-muted">
        <?php if ($disable_no_login): ?>
          Self-service ordering is currently disabled. Please ask a staff member or log in to your account.
        <?php else: ?>
          The system is currently <?= e($status) ?>. Please try again later.
        <?php endif; ?>
      </p>
    </div>
  </div>
<?php else: ?>
  <main class="kiosk-main">

    <div class="kiosk-toolbar">
      <div class="ks-search-wrap">
        <span class="ks-search-icon">🔍</span>
        <input class="input" id="search" placeholder="Search items..." autocomplete="off" spellcheck="false">
      </div>
    </div>

    <div class="ks-cat-tabs" id="cat-tabs">
      <button class="ks-cat-tab active" data-cat="">All Items</button>
      <?php foreach ($categories as $c): ?>
        <button class="ks-cat-tab" data-cat="<?= (int)$c['id'] ?>"><?= e($c['category_name']) ?></button>
      <?php endforeach; ?>
    </div>

    <div class="ks-results-meta" id="results-meta"></div>
    <div class="item-grid" id="item-grid"></div>
    <div class="pagination" id="pagination"></div>
  </main>

  <button class="cart-fab" data-cart-fab aria-label="Open cart">🛒</button>
  <div class="ks-idle-toast" id="ks-idle-toast">⏱ Session resetting soon…</div>

  <div class="cart-drawer-backdrop" data-cart-backdrop></div>
  <aside class="cart-drawer" data-cart-drawer aria-label="Cart">
    <div class="cart-drawer-header">
      <div>
        <h3>Your Cart</h3>
        <div style="font-size:.8125rem;color:var(--text-muted);margin-top:2px" id="cart-drawer-subtitle"></div>
      </div>
      <button class="modal-close" data-cart-close aria-label="Close">×</button>
    </div>
    <div class="cart-drawer-body" data-cart-body></div>
    <div class="cart-drawer-footer">
      <div class="cart-totals"><span>Total</span><span class="ct-amount" data-cart-total>₱0.00</span></div>
      <button class="btn btn-block btn-lg" data-cart-checkout disabled>Place Order</button>
    </div>
  </aside>
<?php endif; ?>

<script>window.__BASE='<?= BASE_PATH ?>';</script>
<script src="<?= e(url('/assets/js/global.js')) ?>"></script>
<script src="<?= e(url('/assets/js/theme.js')) ?>"></script>
<script src="<?= e(url('/assets/js/custom-select.js')) ?>"></script>
<script src="<?= e(url('/assets/js/pagination.js')) ?>"></script>
<script src="<?= e(url('/assets/js/modal.js')) ?>"></script>
<script src="<?= e(url('/assets/js/cart-drawer.js')) ?>"></script>
<script>
(function () {
  // ---- Clock ----
  function tick() {
    const el = document.getElementById('kiosk-time');
    if (el) el.textContent = new Date().toLocaleString('en-PH', {
      hour: 'numeric', minute: '2-digit', hour12: true, timeZone: 'Asia/Manila'
    });
  }
  tick(); setInterval(tick, 30000);

  // ---- Dark / Light mode toggle ----
  (function () {
    const btn = document.getElementById('ks-theme-toggle');
    if (!btn) return;
    const root = document.documentElement;
    const stored = sessionStorage.getItem('ks_theme');
    if (stored) { root.dataset.theme = stored; }
    function updateBtn() {
      const isDark = root.dataset.theme === 'dark';
      btn.textContent = isDark ? '☀️' : '🌙';
      btn.title = isDark ? 'Switch to light mode' : 'Switch to dark mode';
    }
    updateBtn();
    btn.addEventListener('click', () => {
      const isDark = root.dataset.theme === 'dark';
      root.dataset.theme = isDark ? 'light' : 'dark';
      sessionStorage.setItem('ks_theme', root.dataset.theme);
      updateBtn();
    });
  })();

  <?php if (!$disable_no_login && $status === 'online'): ?>
  let state = { page: 1, q: '', category: '' };
  const grid = document.getElementById('item-grid');
  const pagEl = document.getElementById('pagination');
  const metaEl = document.getElementById('results-meta');

  // ---- Category pill tabs ----
  document.getElementById('cat-tabs').addEventListener('click', (e) => {
    const tab = e.target.closest('.ks-cat-tab');
    if (!tab) return;
    document.querySelectorAll('.ks-cat-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    state.category = tab.dataset.cat;
    state.page = 1;
    loadItems();
  });

  // ---- Load & render ----
  async function loadItems() {
    grid.innerHTML = '<div class="empty-state"><div class="es-icon">⏳</div>Loading…</div>';
    if (metaEl) metaEl.textContent = '';
    try {
      const params = new URLSearchParams({ page: state.page, q: state.q, category: state.category, per_page: 12 });
      const res = await fetch((window.__BASE||'')+'/api/inventory/get_items.php?' + params.toString());
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'Failed to load');
      if (metaEl) {
        const total = data.total_items !== undefined ? data.total_items : (data.items ? data.items.length : 0);
        metaEl.textContent = total ? `Showing ${data.items.length} of ${total} item${total !== 1 ? 's' : ''}` : '';
      }
      render(data.items);
      window.Pagination.render(pagEl, {
        current: data.page,
        total: data.total_pages,
        onChange: (p) => { state.page = p; loadItems(); },
      });
    } catch (e) {
      grid.innerHTML = '<div class="empty-state"><div class="es-icon">⚠️</div>Failed to load items. Please try again.</div>';
    }
  }

  function render(items) {
    if (!items.length) {
      grid.innerHTML = '<div class="empty-state"><div class="es-icon">�</div><div class="es-title">No items found</div><div>Try a different search or category.</div></div>';
      return;
    }
    grid.innerHTML = items.map(it => {
      const noStock = it.stock_count <= 0;
      const img = it.item_image
        ? `<img src="${window.__BASE||''}/${it.item_image}" alt="${window.EN.escapeHtml(it.item_name)}" loading="lazy">`
        : '<span class="no-image">📦</span>';
      const inCart = (window.CartDrawer.cart || []).find(c => c.id === it.id);
      const qty = inCart ? inCart.qty : 0;
      const max = Math.min(it.max_order_qty, it.stock_count);
      const stockLabel = it.stock_count <= 5 && it.stock_count > 0
        ? `<span class="ks-stock ks-stock-low">⚠ ${it.stock_count} left</span>`
        : `<span class="ks-stock">${it.stock_count} in stock</span>`;
      const itemJson = JSON.stringify({id:it.id,name:it.item_name,price:it.price,image:it.item_image?(window.__BASE||'')+'/'+it.item_image:'',max:it.max_order_qty,stock:it.stock_count}).replace(/'/g,"&#39;");
      return `
        <div class="item-card ${noStock ? 'no-stock' : ''} ${qty > 0 ? 'selected' : ''}" data-card="${it.id}">
          <div class="img-wrap">${img}</div>
          <div class="card-body">
            <div class="name">${window.EN.escapeHtml(it.item_name)}</div>
            <div class="meta">
              <span class="ks-cat-badge">${window.EN.escapeHtml(it.category || 'Uncategorized')}</span>
              ${noStock ? '<span class="ks-stock" style="color:var(--danger);font-weight:700">Out of stock</span>' : stockLabel}
            </div>
            <div class="price">${window.EN.formatPrice(it.price)}</div>
            <div class="actions">
              ${noStock
                ? `<div class="qty-stepper" style="opacity:.4;pointer-events:none">
                    <button type="button" disabled>−</button>
                    <input type="number" value="0" disabled>
                    <button type="button" disabled>+</button>
                   </div>`
                : `<div class="qty-stepper">
                    <button type="button" data-dec="${it.id}">−</button>
                    <input type="number" min="0" max="${max}" value="${qty}" data-qty="${it.id}" data-item='${itemJson}'>
                    <button type="button" data-inc="${it.id}">+</button>
                   </div>`
              }
            </div>
          </div>
        </div>`;
    }).join('');
  }

  // ---- Cart subtitle helper ----
  function updateCartSubtitle() {
    const el = document.getElementById('cart-drawer-subtitle');
    if (!el) return;
    const cart = window.CartDrawer.cart || [];
    const totalQty = cart.reduce((s, c) => s + c.qty, 0);
    el.textContent = totalQty > 0 ? `${totalQty} item${totalQty !== 1 ? 's' : ''} in cart` : 'Your cart is empty';
  }

  // ---- FAB pulse helper ----
  function updateFabPulse() {
    const fab = document.querySelector('[data-cart-fab]');
    if (!fab) return;
    const hasItems = (window.CartDrawer.cart || []).length > 0;
    fab.classList.toggle('has-items', hasItems);
  }

  // ---- Grid interaction (stepper + card tap) ----
  grid.addEventListener('click', (e) => {
    const inc = e.target.closest('[data-inc]');
    const dec = e.target.closest('[data-dec]');
    const card = e.target.closest('[data-card]');
    if (!card) return;
    const inp = card.querySelector('[data-qty]');
    if (!inp) return;
    const item = JSON.parse(inp.dataset.item);
    let q = parseInt(inp.value || '0', 10);
    if (inc) q++;
    else if (dec) q--;
    else if (!e.target.closest('[data-qty]') && q === 0) q = 1; // card tap = add 1 if not yet added
    q = Math.max(0, Math.min(q, item.max, item.stock));
    inp.value = q;
    syncCart(item, q, card);
  });
  grid.addEventListener('change', (e) => {
    const inp = e.target.closest('[data-qty]');
    if (!inp) return;
    const item = JSON.parse(inp.dataset.item);
    let q = parseInt(inp.value || '0', 10);
    q = Math.max(0, Math.min(q, item.max, item.stock));
    inp.value = q;
    syncCart(item, q, inp.closest('[data-card]'));
  });

  function syncCart(item, qty, card) {
    if (qty <= 0) {
      window.CartDrawer.remove(item.id);
      card.classList.remove('selected');
    } else {
      if (!window.CartDrawer.cart.find(c => c.id === item.id)) {
        window.CartDrawer.add(item, qty);
      } else {
        window.CartDrawer.setQty(item.id, qty);
      }
      card.classList.add('selected');
    }
    updateFabPulse();
    updateCartSubtitle();
  }

  // ---- Search ----
  document.getElementById('search').addEventListener('input', (e) => {
    state.q = e.target.value.trim(); state.page = 1;
    clearTimeout(window._dbk); window._dbk = setTimeout(loadItems, 280);
  });

  // ---- Cart drawer init ----
  window.CartDrawer.init({
    storage: 'session',
    storageKey: 'kiosk_cart',
    collectGuest: true,
    onCheckout: async (payload) => {
      const data = await window.EN.api('/api/orders/create.php', { body: payload });
      setTimeout(syncGridFromCart, 0);
      return data;
    },
  });
  updateFabPulse();
  updateCartSubtitle();

  // ---- Sync grid cards from cart state ----
  function syncGridFromCart() {
    const cart = window.CartDrawer.cart || [];
    grid.querySelectorAll('[data-card]').forEach(card => {
      const id = +card.dataset.card;
      const entry = cart.find(c => c.id === id);
      const qty = entry ? entry.qty : 0;
      const inp = card.querySelector('[data-qty]');
      if (inp) inp.value = qty;
      card.classList.toggle('selected', qty > 0);
    });
    updateFabPulse();
    updateCartSubtitle();
  }

  // Listen for cart drawer remove / qty changes and sync grid
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-cart-remove]') ||
        e.target.closest('[data-qty-inc]') ||
        e.target.closest('[data-qty-dec]')) {
      setTimeout(syncGridFromCart, 0);
    }
    if (e.target.closest('[data-cart-fab]') || e.target.closest('[data-cart-close]')) {
      setTimeout(updateCartSubtitle, 50);
    }
  });
  document.addEventListener('change', (e) => {
    if (e.target.closest('[data-qty-input]')) {
      setTimeout(syncGridFromCart, 0);
    }
  });

  loadItems();

  // ---- Idle reset with warning toast ----
  const IDLE_MS = <?= max(30, $idle_seconds) ?> * 1000;
  const WARN_MS = 10000;
  const idleToast = document.getElementById('ks-idle-toast');
  let idleTimer, warnTimer;

  function showIdleWarning() {
    if (idleToast) idleToast.classList.add('show');
  }
  function hideIdleWarning() {
    if (idleToast) idleToast.classList.remove('show');
  }

  function doIdleReset() {
    hideIdleWarning();
    window.CartDrawer.clear();
    window.CartDrawer.closeDrawer();
    state.page = 1; state.q = ''; state.category = '';
    document.getElementById('search').value = '';
    document.querySelectorAll('.ks-cat-tab').forEach(t => t.classList.remove('active'));
    const allTab = document.querySelector('.ks-cat-tab[data-cat=""]');
    if (allTab) allTab.classList.add('active');
    updateFabPulse();
    updateCartSubtitle();
    loadItems();
  }

  function resetIdle() {
    clearTimeout(idleTimer);
    clearTimeout(warnTimer);
    hideIdleWarning();
    if (IDLE_MS > WARN_MS) {
      warnTimer = setTimeout(showIdleWarning, IDLE_MS - WARN_MS);
    }
    idleTimer = setTimeout(doIdleReset, IDLE_MS);
  }

  ['click', 'keydown', 'touchstart', 'mousemove'].forEach(evt => document.addEventListener(evt, resetIdle, { passive: true }));
  resetIdle();
  <?php endif; ?>
})();
</script>
</body>
</html>
