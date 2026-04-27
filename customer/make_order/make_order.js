// Customer — Make Order (kiosk-style redesign)
(function () {
  const grid   = document.getElementById('item-grid');
  const pagEl  = document.getElementById('pagination');
  const metaEl = document.getElementById('results-meta');
  const search = document.getElementById('search');

  let state = { page: 1, q: '', category: '' };

  // ---- Category pill tabs ----
  document.getElementById('mo-cat-tabs').addEventListener('click', (e) => {
    const tab = e.target.closest('.mo-cat-tab');
    if (!tab) return;
    document.querySelectorAll('.mo-cat-tab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    state.category = tab.dataset.cat;
    state.page = 1;
    load();
  });

  // ---- Load items from API ----
  async function load() {
    grid.innerHTML = '<div class="empty-state"><div class="es-icon">⏳</div>Loading…</div>';
    if (metaEl) metaEl.textContent = '';
    try {
      const params = new URLSearchParams({ page: state.page, q: state.q, category: state.category, per_page: 12, in_stock: '1' });
      const res = await fetch((window.__BASE || '') + '/api/inventory/get_items.php?' + params.toString());
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
        onChange: (p) => { state.page = p; load(); },
      });
    } catch (err) {
      grid.innerHTML = '<div class="empty-state"><div class="es-icon">⚠️</div>Failed to load items. Please try again.</div>';
    }
  }

  // ---- Render product cards ----
  function render(items) {
    if (!items || !items.length) {
      grid.innerHTML = '<div class="empty-state"><div class="es-icon">📚</div><div class="es-title">No items found</div><div>Try a different search or category.</div></div>';
      return;
    }
    grid.innerHTML = items.map(it => {
      const noStock = it.stock_count <= 0;
      const img = it.item_image
        ? `<img src="${window.__BASE || ''}/${it.item_image}" alt="${window.EN.escapeHtml(it.item_name)}" loading="lazy">`
        : '<span class="no-image">📦</span>';
      const inCart = (window.CartDrawer.cart || []).find(c => c.id === it.id);
      const qty = inCart ? inCart.qty : 0;
      const max = Math.min(it.max_order_qty, it.stock_count);
      const stockLabel = it.stock_count <= 5 && it.stock_count > 0
        ? `<span class="mo-stock mo-stock-low">⚠ ${it.stock_count} left</span>`
        : `<span class="mo-stock">${it.stock_count} in stock</span>`;
      const itemJson = JSON.stringify({
        id: it.id, name: it.item_name, price: it.price,
        image: it.item_image ? (window.__BASE || '') + '/' + it.item_image : '',
        max: it.max_order_qty, stock: it.stock_count,
      }).replace(/'/g, "&#39;");
      return `
        <div class="item-card ${noStock ? 'no-stock' : ''} ${qty > 0 ? 'selected' : ''}" data-card="${it.id}">
          <div class="img-wrap">${img}</div>
          <div class="card-body">
            <div class="name">${window.EN.escapeHtml(it.item_name)}</div>
            <div class="meta">
              <span class="mo-cat-badge">${window.EN.escapeHtml(it.category || 'Uncategorized')}</span>
              ${noStock ? '<span class="mo-stock" style="color:var(--danger);font-weight:700">Out of stock</span>' : stockLabel}
            </div>
            <div class="price">${window.EN.formatPrice(it.price)}</div>
            <div class="actions">
              ${noStock
                ? `<div class="qty-stepper" style="opacity:.4;pointer-events:none;width:100%">
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

  // ---- Cart helpers ----
  function updateCartSubtitle() {
    const el = document.getElementById('cart-drawer-subtitle');
    if (!el) return;
    const cart = window.CartDrawer.cart || [];
    const totalQty = cart.reduce((s, c) => s + c.qty, 0);
    el.textContent = totalQty > 0 ? `${totalQty} item${totalQty !== 1 ? 's' : ''} in cart` : 'Your cart is empty';
  }

  function updateFabPulse() {
    const fab = document.querySelector('[data-cart-fab]');
    if (!fab) return;
    fab.classList.toggle('has-items', (window.CartDrawer.cart || []).length > 0);
  }

  // ---- Grid interaction: stepper + card tap ----
  grid.addEventListener('click', (e) => {
    const inc  = e.target.closest('[data-inc]');
    const dec  = e.target.closest('[data-dec]');
    const card = e.target.closest('[data-card]');
    if (!card) return;
    const inp = card.querySelector('[data-qty]');
    if (!inp) return;
    const item = JSON.parse(inp.dataset.item);
    let q = parseInt(inp.value || '0', 10);
    if (inc) q++;
    else if (dec) q--;
    else if (!e.target.closest('[data-qty]') && q === 0) q = 1;
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
      if (card) card.classList.remove('selected');
    } else {
      if (!window.CartDrawer.cart.find(c => c.id === item.id)) {
        window.CartDrawer.add(item, qty);
      } else {
        window.CartDrawer.setQty(item.id, qty);
      }
      if (card) card.classList.add('selected');
    }
    updateFabPulse();
    updateCartSubtitle();
  }

  // ---- Two-way sync: cart drawer → grid ----
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

  // ---- Search ----
  search.addEventListener('input', () => {
    clearTimeout(window._mo_dbt);
    window._mo_dbt = setTimeout(() => { state.q = search.value.trim(); state.page = 1; load(); }, 280);
  });

  // ---- CartDrawer init (logged-in customer, local storage) ----
  window.CartDrawer.init({
    storage: 'local',
    storageKey: 'customer_cart_' + (window._userId || '0'),
    collectGuest: false,
    onCheckout: async (payload) => {
      const data = await window.EN.api('/api/orders/create.php', { body: payload });
      return data;
    },
  });

  updateFabPulse();
  updateCartSubtitle();
  load();
})();
