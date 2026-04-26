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
<html lang="en" data-force-dark="<?= e($force_dark) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title>Order Kiosk — <?= e($store_name) ?></title>
<link rel="stylesheet" href="<?= e(url('/assets/css/global.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/components.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/cart-drawer.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/kiosk.css')) ?>">
</head>
<body class="kiosk-body">

<header class="kiosk-header">
  <img src="<?= e(url('/' . ltrim($logo_path, '/'))) ?>" alt="" onerror="this.style.display='none'">
  <div>
    <h1><?= e($store_name) ?></h1>
    <div class="ks-tagline">Self-service kiosk — quick & easy ordering</div>
  </div>
  <div class="spacer"></div>
  <div class="ks-time" id="kiosk-time"></div>
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
      <a class="btn" href="<?= e(url('/index.php')) ?>">Back to Home</a>
    </div>
  </div>
<?php else: ?>
  <main class="kiosk-main">
    <div class="kiosk-toolbar">
      <div class="search-box" style="flex:1;max-width:400px;position:relative">
        <input class="input" id="search" placeholder="Search items..." style="padding-left:36px">
      </div>
      <select class="select-native" id="category-filter" data-custom-select style="min-width:200px">
        <option value="">All Categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= e($c['category_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="item-grid" id="item-grid"></div>
    <div class="pagination" id="pagination"></div>
  </main>

  <button class="cart-fab" data-cart-fab aria-label="Open cart">🛒</button>

  <div class="cart-drawer-backdrop" data-cart-backdrop></div>
  <aside class="cart-drawer" data-cart-drawer aria-label="Cart">
    <div class="cart-drawer-header">
      <h3>Your Cart</h3>
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
  // Clock
  function tick() {
    document.getElementById('kiosk-time').textContent = new Date().toLocaleString('en-PH', {
      hour: 'numeric', minute: '2-digit', hour12: true, timeZone: 'Asia/Manila'
    });
  }
  tick(); setInterval(tick, 30000);

  <?php if (!$disable_no_login && $status === 'online'): ?>
  let state = { page: 1, q: '', category: '' };
  const grid = document.getElementById('item-grid');
  const pagEl = document.getElementById('pagination');

  async function loadItems() {
    grid.innerHTML = '<div class="empty-state">Loading...</div>';
    try {
      const params = new URLSearchParams({ page: state.page, q: state.q, category: state.category, per_page: 20 });
      const res = await fetch((window.__BASE||'')+'/api/inventory/get_items.php?' + params.toString());
      const data = await res.json();
      if (!data.ok) throw new Error(data.error || 'Failed to load');
      render(data.items);
      window.Pagination.render(pagEl, {
        current: data.page,
        total: data.total_pages,
        onChange: (p) => { state.page = p; loadItems(); },
      });
    } catch (e) {
      grid.innerHTML = '<div class="empty-state"><div class="es-icon">⚠️</div>Failed to load items</div>';
    }
  }

  function render(items) {
    if (!items.length) { grid.innerHTML = '<div class="empty-state"><div class="es-icon">📦</div>No items found</div>'; return; }
    grid.innerHTML = items.map(it => {
      const noStock = it.stock_count <= 0;
      const img = it.item_image ? `<img src="${window.__BASE||''}/${it.item_image}" alt="">` : '<span class="no-image">📦</span>';
      const inCart = (window.CartDrawer.cart || []).find(c => c.id === it.id);
      const qty = inCart ? inCart.qty : 0;
      const max = Math.min(it.max_order_qty, it.stock_count);
      return `
        <div class="item-card ${noStock ? 'no-stock' : ''} ${qty > 0 ? 'selected' : ''}" data-card="${it.id}">
          <div class="img-wrap">${img}</div>
          <div class="name">${window.EN.escapeHtml(it.item_name)}</div>
          <div class="meta"><span>${window.EN.escapeHtml(it.category || 'Uncategorized')}</span><span>${it.stock_count} left</span></div>
          <div class="price">${window.EN.formatPrice(it.price)}</div>
          <div class="actions">
            ${noStock
              ? '<span class="badge badge-danger">Out of stock</span>'
              : `<div class="qty-stepper">
                  <button type="button" data-dec="${it.id}">−</button>
                  <input type="number" min="0" max="${max}" value="${qty}" data-qty="${it.id}" data-item='${JSON.stringify({id:it.id,name:it.item_name,price:it.price,image:it.item_image?(window.__BASE||'')+'/'+it.item_image:'',max:it.max_order_qty,stock:it.stock_count}).replace(/'/g,"&#39;")}'>
                  <button type="button" data-inc="${it.id}">+</button>
                 </div>`
            }
          </div>
        </div>`;
    }).join('');
  }

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
    if (dec) q--;
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
    if (qty <= 0) { window.CartDrawer.remove(item.id); card.classList.remove('selected'); }
    else { window.CartDrawer.setQty(item.id, qty); /* setQty falls through to remove if not present, so add then setQty */
      if (!window.CartDrawer.cart.find(c => c.id === item.id)) {
        window.CartDrawer.add(item, qty);
      } else {
        window.CartDrawer.setQty(item.id, qty);
      }
      card.classList.add('selected');
    }
  }

  document.getElementById('search').addEventListener('input', (e) => {
    state.q = e.target.value.trim(); state.page = 1;
    clearTimeout(window._dbk); window._dbk = setTimeout(loadItems, 250);
  });
  document.getElementById('category-filter').addEventListener('change', (e) => {
    state.category = e.target.value; state.page = 1; loadItems();
  });

  window.CartDrawer.init({
    storage: 'session',
    storageKey: 'kiosk_cart',
    collectGuest: true,
    onCheckout: async (payload) => {
      const data = await window.EN.api('/api/orders/create.php', { body: payload });
      return data;
    },
  });

  loadItems();

  // Idle reset
  let idleTimer;
  function resetIdle() {
    clearTimeout(idleTimer);
    idleTimer = setTimeout(() => {
      window.CartDrawer.clear();
      window.CartDrawer.closeDrawer();
      state.page = 1; state.q = ''; state.category = '';
      document.getElementById('search').value = '';
      document.getElementById('category-filter').value = '';
      document.getElementById('category-filter').dispatchEvent(new Event('change'));
    }, <?= max(30, $idle_seconds) ?> * 1000);
  }
  ['click', 'keydown', 'touchstart', 'mousemove'].forEach(evt => document.addEventListener(evt, resetIdle, { passive: true }));
  resetIdle();
  <?php endif; ?>
})();
</script>
</body>
</html>
