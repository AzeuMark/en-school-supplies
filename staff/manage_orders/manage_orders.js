// Staff — Manage Orders
(function () {
  const tbl = document.getElementById('orders-tbl');
  const pagEl = document.getElementById('pagination');
  const search = document.getElementById('search');
  const tabs = document.querySelector('[data-status-tabs]');
  const role = 'staff';
  const canDelete = role === 'admin';
  const previewItemLimit = 5;

  let state = { page: 1, status: '', q: '' };
  let orderMap = new Map();
  let searchTimer = null;

  async function load() {
    tbl.innerHTML = '<div class="empty-state">Loading...</div>';
    const params = new URLSearchParams({ page: state.page, status: state.status, q: state.q });
    try {
      const res = await fetch(EN.BASE + '/api/orders/list.php?' + params.toString());
      const data = await res.json();
      if (!data.ok) {
        tbl.innerHTML = '<div class="empty-state">Failed to load.</div>';
        return;
      }
      render(data.orders || []);
      Pagination.render(pagEl, {
        current: data.page,
        total: data.total_pages,
        onChange: (p) => {
          state.page = p;
          load();
        },
      });
    } catch (_) {
      tbl.innerHTML = '<div class="empty-state">Failed to load.</div>';
    }
  }

  function formatOrderDate(createdAt) {
    return new Date(String(createdAt).replace(' ', 'T')).toLocaleString('en-PH', {
      month: 'short',
      day: 'numeric',
      hour: 'numeric',
      minute: '2-digit',
      hour12: true,
    });
  }

  function statusLabel(status) {
    return status.charAt(0).toUpperCase() + status.slice(1);
  }

  function customerLabel(order) {
    if (order.customer_name) {
      return EN.escapeHtml(order.customer_name);
    }
    const guestName = EN.escapeHtml(order.guest_name || 'Guest');
    const guestPhone = order.guest_phone
      ? `<div class="text-muted customer-phone">${EN.escapeHtml(order.guest_phone)}</div>`
      : '';
    return `<span class="text-muted">Guest:</span> ${guestName}${guestPhone}`;
  }

  function renderItemsPreview(order) {
    const items = Array.isArray(order.items) ? order.items : [];
    const previewItems = items.slice(0, previewItemLimit);

    return `
      <div class="order-items-box">
        <div class="order-items-list" aria-label="Ordered items preview">
          ${previewItems.map((it) => `
            <div class="order-item-line">
              ${EN.escapeHtml(it.item_name_snapshot)} x ${it.quantity} <span class="text-muted">(${EN.formatPrice(it.unit_price)})</span>
            </div>
          `).join('')}
        </div>
        ${items.length > previewItemLimit ? `<button type="button" class="btn btn-sm btn-secondary order-items-view-all" data-view-all="${order.id}">View All (${items.length})</button>` : ''}
      </div>
    `;
  }

  function renderActions(order, mode) {
    const attr = mode === 'modal' ? 'data-modal-action' : 'data-action';
    const actions = [];

    if (order.status === 'pending') {
      actions.push(`<button class="btn btn-sm btn-icon action-icon action-ready" ${attr}="ready" data-id="${order.id}" title="Mark Ready" aria-label="Mark Ready">✅</button>`);
    }
    if (order.status === 'ready') {
      actions.push(`<button class="btn btn-sm btn-icon action-icon action-claim" ${attr}="claim" data-id="${order.id}" title="Mark Claimed" aria-label="Mark Claimed">🎟️</button>`);
    }

    actions.push(`<a class="btn btn-sm btn-icon btn-receipt" target="_blank" href="${EN.BASE}/receipt.php?order=${encodeURIComponent(order.order_code)}&pin=${encodeURIComponent(order.claim_pin || '')}" title="View Receipt" aria-label="View Receipt">🧾</a>`);

    if (canDelete && order.status !== 'claimed') {
      actions.push(`<button class="btn btn-sm btn-icon action-icon action-delete" ${attr}="delete" data-id="${order.id}" title="Delete Order" aria-label="Delete Order">🗑️</button>`);
    }

    return actions.join('');
  }

  function render(orders) {
    orderMap = new Map((orders || []).map((o) => [Number(o.id), o]));

    if (!orders.length) {
      tbl.innerHTML = '<div class="empty-state"><div class="es-icon">📭</div><div class="es-title">No orders</div></div>';
      return;
    }

    tbl.innerHTML = `
      <div class="table-wrap"><table class="table">
        <thead><tr>
          <th>Order ID</th><th>Customer</th><th>Items</th><th>Note</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th>
        </tr></thead>
        <tbody>
          ${orders.map((order) => {
            const pinDisplay = order.claim_pin_display || order.claim_pin || '';
            const hasNote = Boolean(order.guest_note && String(order.guest_note).trim() !== '');
            return `
              <tr data-row="${order.id}">
                <td>
                  <strong>${EN.escapeHtml(order.order_code)}</strong>
                  ${pinDisplay ? `<div class="text-muted pin-text">PIN: ${EN.escapeHtml(pinDisplay)}</div>` : ''}
                </td>
                <td>${customerLabel(order)}</td>
                <td>${renderItemsPreview(order)}</td>
                <td>
                  <div class="order-note-box">
                    <div class="order-note-scroll">${hasNote ? EN.escapeHtml(order.guest_note) : '<span class="text-muted">-</span>'}</div>
                  </div>
                </td>
                <td>${EN.formatPrice(order.total_price)}</td>
                <td><span class="badge status-${order.status}">${statusLabel(order.status)}</span></td>
                <td>${formatOrderDate(order.created_at)}</td>
                <td><div class="actions actions-row">${renderActions(order, 'row')}</div></td>
              </tr>
            `;
          }).join('')}
        </tbody>
      </table></div>
    `;
  }

  function openItemsModal(orderId) {
    const order = orderMap.get(Number(orderId));
    if (!order) return;

    const modalRows = (order.items || []).map((it) => `
      <tr>
        <td>${EN.escapeHtml(it.item_name_snapshot)}</td>
        <td class="text-right">${it.quantity}</td>
        <td class="text-right">${EN.formatPrice(it.unit_price)}</td>
        <td class="text-right">${EN.formatPrice(it.quantity * Number(it.unit_price))}</td>
      </tr>
    `).join('');

    const bd = Modal.show({
      title: `Order ${EN.escapeHtml(order.order_code)} — Items`,
      size: 'lg',
      html: `
        <div class="items-modal-head">
          <div><strong>Customer:</strong> ${order.customer_name ? EN.escapeHtml(order.customer_name) : EN.escapeHtml(order.guest_name || 'Guest')}</div>
          <div><strong>Status:</strong> <span class="badge status-${order.status}">${statusLabel(order.status)}</span></div>
        </div>
        <div class="items-modal-scroll">
          <table class="table items-modal-table">
            <thead><tr><th>Item</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Subtotal</th></tr></thead>
            <tbody>${modalRows}</tbody>
          </table>
        </div>
        ${order.guest_note ? `<div class="order-note modal-note"><strong>Note:</strong> ${EN.escapeHtml(order.guest_note)}</div>` : ''}
      `,
      footer: `
        <button class="btn btn-secondary" data-modal-close>Close</button>
        <div class="actions actions-modal">${renderActions(order, 'modal')}</div>
      `,
    });

    bd.addEventListener('click', async (e) => {
      const btn = e.target.closest('[data-modal-action]');
      if (!btn) return;
      await runAction(btn.dataset.modalAction, Number(btn.dataset.id), bd);
    });
  }

  async function runAction(action, id, sourceModal) {
    const order = orderMap.get(Number(id));

    if (action === 'ready') {
      const ok = await Modal.confirm({
        title: 'Mark as Ready?',
        message: 'This order will become claimable by staff.',
        confirmText: 'Mark Ready',
      });
      if (!ok) return;
      try {
        await EN.api('/api/orders/update_status.php', { body: { order_id: id, action: 'ready' } });
        EN.toast('Order marked as Ready.', 'success');
        if (sourceModal) Modal.close(sourceModal);
        load();
      } catch (_) {}
      return;
    }

    if (action === 'claim') {
      const guestHint = order && order.is_guest_order
        ? '<div class="field-help">Guest PIN format accepted: GST-1234 or 1234.</div>'
        : '<div class="field-help">Enter the 4-digit Claim PIN.</div>';
      const claimModal = Modal.show({
        title: 'Mark Order as Claimed',
        html: `
          <p>Only <strong>ready</strong> orders can be claimed.</p>
          <div class="field">
            <label>Claim PIN</label>
            <input class="input" id="claim-pin" type="text" maxlength="8" placeholder="${order && order.is_guest_order ? 'GST-1234' : '1234'}" autofocus>
            ${guestHint}
          </div>
        `,
        footer: `
          <button class="btn btn-secondary" data-modal-close>Cancel</button>
          <button class="btn" data-confirm-claim>Confirm Claim</button>
        `,
      });

      claimModal.querySelector('[data-confirm-claim]').addEventListener('click', async () => {
        const pin = claimModal.querySelector('#claim-pin').value.trim();
        try {
          await EN.api('/api/orders/update_status.php', { body: { order_id: id, action: 'claim', pin } });
          Modal.close(claimModal);
          if (sourceModal) Modal.close(sourceModal);
          EN.toast('Order marked as Claimed.', 'success');
          load();
        } catch (_) {}
      });
      return;
    }

    if (action === 'delete') {
      const ok = await Modal.confirm({
        title: 'Delete Order?',
        message: 'This permanently removes the order record. Claimed orders cannot be deleted.',
        confirmText: 'Delete',
        danger: true,
      });
      if (!ok) return;
      try {
        await EN.api('/api/orders/delete.php', { body: { order_id: id } });
        if (sourceModal) Modal.close(sourceModal);
        EN.toast('Order deleted.', 'success');
        load();
      } catch (_) {}
    }
  }

  tbl.addEventListener('click', async (e) => {
    const allBtn = e.target.closest('[data-view-all]');
    if (allBtn) {
      openItemsModal(Number(allBtn.dataset.viewAll));
      return;
    }

    const actionBtn = e.target.closest('[data-action]');
    if (!actionBtn) return;
    await runAction(actionBtn.dataset.action, Number(actionBtn.dataset.id), null);
  });

  tabs.addEventListener('click', (e) => {
    const b = e.target.closest('button[data-status]');
    if (!b) return;
    tabs.querySelectorAll('button').forEach((x) => x.classList.remove('active'));
    b.classList.add('active');
    state.status = b.dataset.status;
    state.page = 1;
    load();
  });

  search.addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      state.q = search.value.trim();
      state.page = 1;
      load();
    }, 300);
  });

  load();
})();
