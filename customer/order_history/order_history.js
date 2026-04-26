// Customer — Order History
(function () {
  const tbl   = document.getElementById('orders-tbl');
  const pagEl = document.getElementById('pagination');
  const tabs  = document.querySelector('[data-status-tabs]');

  let state = { page: 1, status: '' };

  async function load() {
    tbl.innerHTML = '<div class="empty-state">Loading...</div>';
    const params = new URLSearchParams({ page: state.page, status: state.status });
    const res = await fetch(EN.BASE + '/api/orders/list.php?' + params.toString());
    const data = await res.json();
    if (!data.ok) { tbl.innerHTML = '<div class="empty-state">Failed to load.</div>'; return; }
    render(data.orders);
    Pagination.render(pagEl, { current: data.page, total: data.total_pages, onChange: (p) => { state.page = p; load(); } });
  }

  function render(orders) {
    if (!orders.length) {
      tbl.innerHTML = '<div class="empty-state"><div class="es-icon">📭</div><div class="es-title">No orders</div></div>';
      return;
    }
    tbl.innerHTML = `<div class="table-wrap"><table class="table">
      <thead><tr>
        <th>Order ID</th><th>Items</th><th>Total</th><th>Status</th><th>PIN</th><th>Date</th><th>Actions</th>
      </tr></thead>
      <tbody>
        ${orders.map(o => `
          <tr>
            <td><strong>${EN.escapeHtml(o.order_code)}</strong></td>
            <td>
              <button type="button" class="btn btn-ghost btn-sm" data-toggle-items="${o.id}">${o.items.length} items ▾</button>
              <div class="items-detail hidden" id="items-${o.id}">
                <ul style="margin:6px 0 0 16px;font-size:.875rem">
                  ${o.items.map(it => `<li>${EN.escapeHtml(it.item_name_snapshot)} × ${it.quantity} <span class="text-muted">(${EN.formatPrice(it.unit_price)})</span></li>`).join('')}
                </ul>
              </div>
            </td>
            <td>${EN.formatPrice(o.total_price)}</td>
            <td><span class="badge status-${o.status}">${o.status[0].toUpperCase()+o.status.slice(1)}</span></td>
            <td>${o.claim_pin ? `<strong class="pin-display">${o.claim_pin}</strong>` : '—'}</td>
            <td>${new Date(o.created_at.replace(' ','T')).toLocaleString('en-PH',{month:'short',day:'numeric',hour:'numeric',minute:'2-digit',hour12:true})}</td>
            <td><div class="actions">
              ${o.status === 'pending' ? `<button class="btn btn-sm btn-secondary" data-cancel="${o.id}">Cancel</button>` : ''}
              <a class="btn btn-sm btn-ghost" target="_blank" href="${EN.BASE}/receipt.php?order=${encodeURIComponent(o.order_code)}&pin=${encodeURIComponent(o.claim_pin||'')}" title="Receipt">🧾</a>
            </div></td>
          </tr>`).join('')}
      </tbody>
    </table></div>`;
  }

  tbl.addEventListener('click', async (e) => {
    const tg = e.target.closest('[data-toggle-items]');
    if (tg) { document.getElementById('items-' + tg.dataset.toggleItems).classList.toggle('hidden'); return; }

    const cn = e.target.closest('[data-cancel]');
    if (cn) {
      const ok = await Modal.confirm({ title: 'Cancel Order?', message: 'Are you sure you want to cancel this order? Stock will be restored.', confirmText: 'Cancel Order', danger: true });
      if (!ok) return;
      try { await EN.api('/api/orders/cancel.php', { body: { order_id: +cn.dataset.cancel } }); EN.toast('Order cancelled.', 'success'); load(); } catch (_) {}
    }
  });

  tabs.addEventListener('click', (e) => {
    const b = e.target.closest('button[data-status]');
    if (!b) return;
    tabs.querySelectorAll('button').forEach(x => x.classList.remove('active'));
    b.classList.add('active');
    state.status = b.dataset.status; state.page = 1; load();
  });

  load();
})();
