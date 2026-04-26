// Staff — Manage Orders (same as admin but no delete action)
(function () {
  const tbl   = document.getElementById('orders-tbl');
  const pagEl = document.getElementById('pagination');
  const search = document.getElementById('search');
  const tabs  = document.querySelector('[data-status-tabs]');

  let state = { page: 1, status: '', q: '' };

  async function load() {
    tbl.innerHTML = '<div class="empty-state">Loading...</div>';
    const params = new URLSearchParams({ page: state.page, status: state.status, q: state.q });
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
        <th>Order ID</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th>
      </tr></thead>
      <tbody>
        ${orders.map(o => `
          <tr>
            <td><strong>${EN.escapeHtml(o.order_code)}</strong>${o.claim_pin ? `<div class="text-muted" style="font-size:.75rem">PIN: ${o.claim_pin}</div>`:''}</td>
            <td>${o.customer_name ? EN.escapeHtml(o.customer_name) : `<span class="text-muted">Guest:</span> ${EN.escapeHtml(o.guest_name||'—')}${o.guest_phone?`<div class="text-muted" style="font-size:.75rem">${EN.escapeHtml(o.guest_phone)}</div>`:''}`}</td>
            <td>
              <button type="button" class="btn btn-ghost btn-sm" data-toggle-items="${o.id}">${o.items.length} items ▾</button>
              <div class="items-detail hidden" id="items-${o.id}">
                <ul style="margin:6px 0 0 16px;font-size:.875rem">
                  ${o.items.map(it => `<li>${EN.escapeHtml(it.item_name_snapshot)} × ${it.quantity} <span class="text-muted">(${EN.formatPrice(it.unit_price)})</span></li>`).join('')}
                </ul>
                ${o.guest_note ? `<div class="text-muted" style="font-size:.8125rem;margin-top:4px"><strong>Note:</strong> ${EN.escapeHtml(o.guest_note)}</div>`:''}
              </div>
            </td>
            <td>${EN.formatPrice(o.total_price)}</td>
            <td><span class="badge status-${o.status}">${o.status[0].toUpperCase()+o.status.slice(1)}</span></td>
            <td>${new Date(o.created_at.replace(' ','T')).toLocaleString('en-PH',{month:'short',day:'numeric',hour:'numeric',minute:'2-digit',hour12:true})}</td>
            <td>
              <div class="actions">
                ${o.status === 'pending' ? `<button class="btn btn-sm" data-action="ready" data-id="${o.id}">✅ Ready</button>` : ''}
                ${(o.status === 'pending' || o.status === 'ready') ? `<button class="btn btn-sm" data-action="claim" data-id="${o.id}">🎟️ Claim</button>` : ''}
                ${o.status !== 'cancelled' && o.status !== 'claimed' ? `<button class="btn btn-sm btn-secondary" data-action="cancel" data-id="${o.id}">✖</button>` : ''}
                <a class="btn btn-sm btn-ghost" target="_blank" href="${EN.BASE}/receipt.php?order=${encodeURIComponent(o.order_code)}&pin=${encodeURIComponent(o.claim_pin||'')}" title="Receipt">🧾</a>
              </div>
            </td>
          </tr>`).join('')}
      </tbody>
    </table></div>`;
  }

  tbl.addEventListener('click', async (e) => {
    const tg = e.target.closest('[data-toggle-items]');
    if (tg) { document.getElementById('items-' + tg.dataset.toggleItems).classList.toggle('hidden'); return; }

    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    const id = +btn.dataset.id;
    const action = btn.dataset.action;

    if (action === 'ready') {
      const ok = await Modal.confirm({ title: 'Mark as Ready?', message: 'The customer will be able to claim this order.', confirmText: 'Mark Ready' });
      if (!ok) return;
      try { await EN.api('/api/orders/update_status.php', { body: { order_id: id, action: 'ready' } }); EN.toast('Order marked as Ready.', 'success'); load(); } catch (_) {}
    }
    if (action === 'claim') {
      const bd = Modal.show({
        title: 'Claim Order',
        html: `<p>Ask the customer for their <strong>4-digit Claim PIN</strong>.</p>
               <div class="field"><label>Claim PIN</label><input class="input" id="claim-pin" type="text" inputmode="numeric" maxlength="4" pattern="\\d{4}" autofocus></div>`,
        footer: `<button class="btn btn-secondary" data-modal-close>Cancel</button><button class="btn" data-confirm-claim>Confirm Claim</button>`,
      });
      bd.querySelector('[data-confirm-claim]').addEventListener('click', async () => {
        const pin = bd.querySelector('#claim-pin').value.trim();
        try { await EN.api('/api/orders/update_status.php', { body: { order_id: id, action: 'claim', pin } }); Modal.close(bd); EN.toast('Order claimed.', 'success'); load(); } catch (_) {}
      });
    }
    if (action === 'cancel') {
      const ok = await Modal.confirm({ title: 'Cancel Order?', message: 'Stock will be restored.', confirmText: 'Cancel Order', danger: true });
      if (!ok) return;
      try { await EN.api('/api/orders/cancel.php', { body: { order_id: id } }); EN.toast('Order cancelled.', 'success'); load(); } catch (_) {}
    }
  });

  tabs.addEventListener('click', (e) => {
    const b = e.target.closest('button[data-status]');
    if (!b) return;
    tabs.querySelectorAll('button').forEach(x => x.classList.remove('active'));
    b.classList.add('active');
    state.status = b.dataset.status; state.page = 1; load();
  });

  search.addEventListener('input', () => {
    clearTimeout(window._dbo); window._dbo = setTimeout(() => { state.q = search.value.trim(); state.page = 1; load(); }, 300);
  });

  load();
})();
