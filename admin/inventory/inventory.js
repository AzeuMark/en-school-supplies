// Admin — Inventory Management
(function () {
  const tbl = document.getElementById('inv-tbl');
  const pagEl = document.getElementById('pagination');
  const search = document.getElementById('search');
  const fCat = document.getElementById('filter-cat');
  const fStock = document.getElementById('filter-stock');
  const sortPreset = document.getElementById('sort-preset');
  const metaEl = document.getElementById('inventory-meta');
  const filterSummaryEl = document.getElementById('filter-summary');
  const categories = window._categories || [];
  const defaultNames = window._defaultNames || [];

  let state = { page: 1, q: '', category: '', stockFilter: 'all', sortBy: 'name', sortDir: 'asc' };

  const sortPresetMap = {
    name_asc: { sortBy: 'name', sortDir: 'asc', label: 'Name (A-Z)' },
    name_desc: { sortBy: 'name', sortDir: 'desc', label: 'Name (Z-A)' },
    stock_desc: { sortBy: 'stock', sortDir: 'desc', label: 'Stock (High to Low)' },
    stock_asc: { sortBy: 'stock', sortDir: 'asc', label: 'Stock (Low to High)' },
    price_asc: { sortBy: 'price', sortDir: 'asc', label: 'Price (Low to High)' },
    price_desc: { sortBy: 'price', sortDir: 'desc', label: 'Price (High to Low)' },
    max_order_desc: { sortBy: 'max_order', sortDir: 'desc', label: 'Max Order (High to Low)' },
    max_order_asc: { sortBy: 'max_order', sortDir: 'asc', label: 'Max Order (Low to High)' },
  };

  const stockFilterLabelMap = {
    all: 'All Stock Levels',
    in: 'In Stock',
    low: 'Low Stock',
    out: 'Out of Stock',
  };

  function getCurrentSortPreset() {
    const found = Object.entries(sortPresetMap).find(([, v]) => v.sortBy === state.sortBy && v.sortDir === state.sortDir);
    return found ? found[0] : 'name_asc';
  }

  function updateFilterSummary() {
    if (!filterSummaryEl) return;
    const parts = [];
    if (state.q) parts.push(`Search: "${state.q}"`);
    if (state.category) {
      const cat = categories.find(c => String(c.id) === String(state.category));
      parts.push(`Category: ${cat ? cat.category_name : 'Selected'}`);
    }
    if (state.stockFilter !== 'all') parts.push(`Stock: ${stockFilterLabelMap[state.stockFilter] || 'Filtered'}`);

    const currentPreset = sortPresetMap[getCurrentSortPreset()] || sortPresetMap.name_asc;
    const filtersText = parts.length ? parts.join(' • ') : 'Showing all items';
    filterSummaryEl.textContent = `${filtersText} • Sorted by ${currentPreset.label}`;
  }

  async function load() {
    tbl.innerHTML = '<div class="empty-state">Loading...</div>';
    if (metaEl) metaEl.textContent = 'Loading items...';
    const params = new URLSearchParams({
      page: state.page,
      q: state.q,
      category: state.category,
      per_page: 15,
      in_stock: '',
      stock_filter: state.stockFilter,
      sort_by: state.sortBy,
      sort_dir: state.sortDir,
    });
    const res = await fetch(EN.BASE + '/api/inventory/get_items.php?' + params.toString());
    const data = await res.json();
    if (!data.ok) {
      tbl.innerHTML = '<div class="empty-state">Failed to load.</div>';
      if (metaEl) metaEl.textContent = 'Unable to load inventory right now.';
      return;
    }

    if (metaEl) {
      const count = Number(data.total || 0);
      const noun = count === 1 ? 'item' : 'items';
      metaEl.textContent = `${count.toLocaleString('en-PH')} ${noun} total • Page ${data.page} of ${Math.max(1, Number(data.total_pages || 1))}`;
    }

    render(data.items);
    Pagination.render(pagEl, { current: data.page, total: data.total_pages, onChange: (p) => { state.page = p; load(); } });
    updateFilterSummary();
  }

  function render(items) {
    if (!items.length) {
      tbl.innerHTML = '<div class="empty-state"><div class="es-icon">📚</div><div class="es-title">No items found</div><div>Try changing your filters or search term.</div></div>';
      return;
    }

    tbl.innerHTML = `<div class="table-wrap"><table class="table">
      <thead><tr><th>Item</th><th>Category</th><th>Price</th><th>Stock</th><th>Max/Order</th><th>Actions</th></tr></thead>
      <tbody>
        ${items.map(it => {
          const img = it.item_image
            ? `<span class="inv-thumb"><img src="${EN.BASE}/${it.item_image}" alt="${EN.escapeHtml(it.item_name)}"></span>`
            : '<span class="inv-thumb inv-thumb-placeholder">📦</span>';

          const stockClass = it.status === 'low_stock' ? 'badge-warning' : it.status === 'no_stock' ? 'badge-danger' : 'badge-success';
          const stockLabel = it.status === 'low_stock' ? 'Low stock' : it.status === 'no_stock' ? 'No stock' : 'In stock';

          return `<tr>
            <td>
              <div class="inv-item-cell">
                ${img}
                <div class="inv-name">
                  <strong>${EN.escapeHtml(it.item_name)}</strong>
                </div>
              </div>
            </td>
            <td><span class="inv-category">${EN.escapeHtml(it.category || 'Uncategorized')}</span></td>
            <td><span class="inv-price">${EN.formatPrice(it.price)}</span></td>
            <td><span class="badge ${stockClass}">${stockLabel}</span><div class="inv-stock-count">${it.stock_count} unit${it.stock_count === 1 ? '' : 's'}</div></td>
            <td><span class="inv-max">${it.max_order_qty}</span></td>
            <td><div class="inv-actions">
              <button class="btn btn-sm btn-secondary" data-edit="${it.id}" title="Edit">Edit</button>
              <button class="btn btn-sm btn-danger" data-del="${it.id}" data-name="${EN.escapeHtml(it.item_name)}" title="Delete">Delete</button>
              <button class="btn btn-sm" data-add-stock="${it.id}" data-name="${EN.escapeHtml(it.item_name)}" title="Add Stock">Add</button>
            </div></td>
          </tr>`;
        }).join('')}
      </tbody></table></div>`;
  }

  function catOptions(selected) {
    return `<option value="0">— Uncategorized —</option>` + categories.map(c => `<option value="${c.id}" ${c.id == selected ? 'selected' : ''}>${EN.escapeHtml(c.category_name)}</option>`).join('');
  }

  function nameDatalist() {
    return defaultNames.map(n => `<option value="${EN.escapeHtml(n)}">`).join('');
  }

  function itemFormHtml(it = {}) {
    const fileText = it.item_image ? 'Current image kept unless you choose a new one' : 'No file chosen';
    return `
      <div class="field"><label>Item Name</label>
        <input class="input" name="item_name" list="names-dl" required maxlength="150" value="${EN.escapeHtml(it.item_name || '')}">
        <datalist id="names-dl">${nameDatalist()}</datalist>
      </div>
      <div class="field"><label>Category</label>
        <select class="select-native" name="category_id" data-custom-select>${catOptions(it.category_id || 0)}</select>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
        <div class="field"><label>Price (₱)</label><input class="input" name="price" type="number" step="0.01" min="0.01" required value="${it.price || ''}"></div>
        <div class="field"><label>Stock</label><input class="input" name="stock_count" type="number" min="1" required value="${it.stock_count ?? ''}"><div class="field-help">Must be at least 1 for new items.</div></div>
        <div class="field"><label>Max/Order</label><input class="input" name="max_order_qty" type="number" min="1" required value="${it.max_order_qty || 10}"></div>
      </div>
      <div class="field">
        <label>Image (optional, max 2 MB)</label>
        <label class="file-input">
          <input type="file" name="item_image" accept="image/jpeg,image/png,image/webp" data-file-input>
          <span class="file-input-btn">Choose File</span>
          <span class="file-input-name" data-file-name>${fileText}</span>
        </label>
      </div>`;
  }

  function bindFileInputs(root) {
    root.querySelectorAll('[data-file-input]').forEach((input) => {
      input.addEventListener('change', () => {
        const nameEl = input.closest('.file-input')?.querySelector('[data-file-name]');
        if (!nameEl) return;
        nameEl.textContent = input.files && input.files[0] ? input.files[0].name : 'No file chosen';
      });
    });
  }

  // Add item
  document.querySelector('[data-add-item]').addEventListener('click', () => {
    const bd = Modal.show({
      title: 'Add Item', size: 'lg',
      html: `<form id="add-form" enctype="multipart/form-data">${itemFormHtml()}</form>`,
      footer: `<button class="btn btn-secondary" data-modal-close>Cancel</button><button class="btn" data-save-add>Add Item</button>`,
    });
    bindFileInputs(bd);
    if (window.CustomSelect) window.CustomSelect.init(bd);
    bd.querySelector('[data-save-add]').addEventListener('click', async () => {
      const fd = new FormData(bd.querySelector('#add-form'));
      try { await EN.api('/api/inventory/add_item.php', { formData: fd }); Modal.close(bd); EN.toast('Item added.', 'success'); load(); } catch (_) {}
    });
  });

  // Edit / Delete
  tbl.addEventListener('click', async (e) => {
    const ed = e.target.closest('[data-edit]');
    if (ed) {
      const id = +ed.dataset.edit;
      const data = await (await fetch(EN.BASE + '/api/inventory/get_items.php?per_page=1&in_stock=&id=' + id)).json();
      const it = (data.items || [])[0] || {};

      const bd = Modal.show({
        title: 'Edit Item', size: 'lg',
        html: `<form id="edit-form" enctype="multipart/form-data"><input type="hidden" name="id" value="${id}">${itemFormHtml(it)}</form>`,
        footer: `<button class="btn btn-secondary" data-modal-close>Cancel</button><button class="btn" data-save-edit>Save</button>`,
      });
      bindFileInputs(bd);
      if (window.CustomSelect) window.CustomSelect.init(bd);
      bd.querySelector('[data-save-edit]').addEventListener('click', async () => {
        const fd = new FormData(bd.querySelector('#edit-form'));
        try { await EN.api('/api/inventory/edit_item.php', { formData: fd }); Modal.close(bd); EN.toast('Item updated.', 'success'); load(); } catch (_) {}
      });
      return;
    }

    const addStock = e.target.closest('[data-add-stock]');
    if (addStock) {
      const id = +addStock.dataset.addStock;
      const itemName = addStock.dataset.name || 'this item';

      const bd = Modal.show({
        title: `Add Stock — ${itemName}`,
        html: `
          <div class="field">
            <label>Quantity to add</label>
            <input class="input" id="stock-amount" type="number" min="1" step="1" value="1" inputmode="numeric" autofocus>
            <div class="field-help">Enter a whole number greater than 0.</div>
          </div>`,
        footer: `<button class="btn btn-secondary" data-modal-close>Cancel</button><button class="btn" data-confirm-add-stock>Add Stock</button>`,
      });

      const amountInput = bd.querySelector('#stock-amount');
      const submitAddStock = async () => {
        const amount = +(amountInput?.value || 0);
        if (!Number.isInteger(amount) || amount < 1) {
          EN.toast('Enter a valid stock amount.', 'error');
          if (amountInput) amountInput.focus();
          return;
        }
        try {
          await EN.api('/api/inventory/add_stock.php', { body: { id, amount } });
          Modal.close(bd);
          EN.toast(`Stock added to ${itemName}.`, 'success');
          load();
        } catch (_) {}
      };

      bd.querySelector('[data-confirm-add-stock]').addEventListener('click', submitAddStock);
      amountInput?.addEventListener('keydown', (ev) => {
        if (ev.key === 'Enter') {
          ev.preventDefault();
          submitAddStock();
        }
      });
      return;
    }

    const del = e.target.closest('[data-del]');
    if (del) {
      const ok = await Modal.confirm({ title: 'Delete item?', message: `Permanently delete "${del.dataset.name}"?`, confirmText: 'Delete', danger: true });
      if (!ok) return;
      try { await EN.api('/api/inventory/delete_item.php', { body: { id: +del.dataset.del } }); EN.toast('Item deleted.', 'success'); load(); } catch (_) {}
    }
  });

  document.querySelector('[data-delete-all-inventory]').addEventListener('click', async () => {
    const ok = await Modal.confirm({
      title: 'Delete all inventory?',
      message: 'This permanently removes every inventory item. It will be blocked if existing order history still references any items.',
      confirmText: 'Delete All',
      danger: true
    });
    if (!ok) return;
    try {
      const data = await EN.api('/api/inventory/delete_all.php', { body: {} });
      EN.toast(data.message || 'All inventory deleted.', 'success');
      state.page = 1;
      load();
    } catch (_) {}
  });

  // Categories management
  document.querySelector('[data-manage-categories]').addEventListener('click', async () => {
    const res = await fetch(EN.BASE + '/api/inventory/categories.php');
    const data = await res.json();
    const cats = data.categories || [];
    const bd = Modal.show({
      title: 'Manage Categories', size: 'lg',
      html: `
        <div style="display:flex;gap:8px;margin-bottom:16px">
          <input class="input" id="new-cat-name" placeholder="New category name..." maxlength="100">
          <button class="btn" data-add-cat>Add</button>
        </div>
        <div id="cat-list">
          ${cats.map(c => `<div class="flex items-center justify-between gap-2 p-2" style="border-bottom:1px solid var(--border)" data-cat-row="${c.id}">
            <span>${EN.escapeHtml(c.category_name)}</span>
            <div class="actions">
              <button class="btn btn-sm btn-danger" data-del-cat="${c.id}">🗑️</button>
            </div>
          </div>`).join('')}
        </div>`,
      footer: `<button class="btn btn-secondary" data-modal-close>Close</button>`,
    });
    bd.querySelector('[data-add-cat]').addEventListener('click', async () => {
      const name = bd.querySelector('#new-cat-name').value.trim();
      if (!name) return;
      try {
        await EN.api('/api/inventory/categories.php', { body: { action: 'add', category_name: name } });
        Modal.close(bd); EN.toast('Category added.', 'success');
        // Refresh page to get updated categories
        location.reload();
      } catch (_) {}
    });
    bd.querySelector('#cat-list').addEventListener('click', async (e) => {
      const d = e.target.closest('[data-del-cat]');
      if (!d) return;
      const ok = await Modal.confirm({ title: 'Delete category?', message: 'Items in this category will become uncategorized.', confirmText: 'Delete', danger: true });
      if (!ok) return;
      try {
        await EN.api('/api/inventory/categories.php', { body: { action: 'delete', id: +d.dataset.delCat } });
        d.closest('[data-cat-row]').remove();
        EN.toast('Category deleted.', 'success');
      } catch (_) {}
    });
  });

  search.addEventListener('input', () => {
    clearTimeout(window._dbi); window._dbi = setTimeout(() => { state.q = search.value.trim(); state.page = 1; load(); }, 300);
  });
  fCat.addEventListener('change', () => { state.category = fCat.value; state.page = 1; load(); });
  fStock.addEventListener('change', () => { state.stockFilter = fStock.value; state.page = 1; load(); });
  if (sortPreset) {
    sortPreset.addEventListener('change', () => {
      const preset = sortPresetMap[sortPreset.value] || sortPresetMap.name_asc;
      state.sortBy = preset.sortBy;
      state.sortDir = preset.sortDir;
      state.page = 1;
      load();
    });
  }
  document.querySelector('[data-reset-filters]').addEventListener('click', () => {
    state = { page: 1, q: '', category: '', stockFilter: 'all', sortBy: 'name', sortDir: 'asc' };
    search.value = '';
    fCat.value = '';
    fStock.value = 'all';
    if (sortPreset) sortPreset.value = 'name_asc';
    load();
  });

  if (sortPreset) sortPreset.value = getCurrentSortPreset();
  updateFilterSummary();

  load();
})();
