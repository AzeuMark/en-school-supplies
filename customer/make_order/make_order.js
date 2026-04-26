// Customer — Make Order (browse items + cart drawer)
(function () {
  const grid   = document.getElementById('items-grid');
  const pagEl  = document.getElementById('pagination');
  const search = document.getElementById('search');
  const fCat   = document.getElementById('filter-cat');

  let state = { page: 1, q: '', category: '' };

  async function load() {
    grid.innerHTML = '<div class="empty-state">Loading...</div>';
    const params = new URLSearchParams({ page: state.page, q: state.q, category: state.category, per_page: 12, in_stock: '1' });
    const res = await fetch(EN.BASE + '/api/inventory/get_items.php?' + params.toString());
    const data = await res.json();
    if (!data.ok) { grid.innerHTML = '<div class="empty-state">Failed to load items.</div>'; return; }
    render(data.items);
    Pagination.render(pagEl, { current: data.page, total: data.total_pages, onChange: (p) => { state.page = p; load(); } });
  }

  function render(items) {
    if (!items.length) {
      grid.innerHTML = '<div class="empty-state"><div class="es-icon">📚</div><div class="es-title">No items found</div></div>';
      return;
    }
    grid.innerHTML = items.map(it => {
      const img = it.item_image
        ? `<img src="${EN.BASE}/${it.item_image}" alt="${EN.escapeHtml(it.item_name)}" class="ic-img">`
        : `<div class="ic-img ic-placeholder">📦</div>`;
      const stockClass = it.status === 'low_stock' ? 'badge-warning' : '';
      return `<div class="item-card" data-id="${it.id}">
        ${img}
        <div class="ic-body">
          <div class="ic-name">${EN.escapeHtml(it.item_name)}</div>
          <div class="ic-cat text-muted">${EN.escapeHtml(it.category || '')}</div>
          <div class="ic-footer">
            <span class="ic-price">${EN.formatPrice(it.price)}</span>
            <span class="badge ${stockClass}">${it.stock_count} left</span>
          </div>
          <button class="btn btn-sm ic-add" data-add-cart='${JSON.stringify({id:it.id,name:it.item_name,price:+it.price,max:it.max_order_qty,stock:it.stock_count,image:it.item_image?EN.BASE+"/"+it.item_image:""})}'>+ Add to Cart</button>
        </div>
      </div>`;
    }).join('');
  }

  grid.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-add-cart]');
    if (!btn) return;
    const item = JSON.parse(btn.dataset.addCart);
    if (typeof CartDrawer !== 'undefined') {
      CartDrawer.add(item);
    }
  });

  search.addEventListener('input', () => {
    clearTimeout(window._dbi);
    window._dbi = setTimeout(() => { state.q = search.value.trim(); state.page = 1; load(); }, 300);
  });
  fCat.addEventListener('change', () => { state.category = fCat.value; state.page = 1; load(); });

  load();
})();
