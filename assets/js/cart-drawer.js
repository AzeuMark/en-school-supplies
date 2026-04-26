/* ===== Shared Cart Drawer (kiosk + customer make-order) =====
   Usage:
     CartDrawer.init({
       storage: 'session' | 'local',
       storageKey: 'kiosk_cart' | 'customer_cart_<uid>',
       collectGuest: true,           // kiosk only
       onCheckout: async (payload) => receiptData,
     });
*/

(function () {
  let opts = {};
  let cart = []; // [{ id, name, price, image, qty, max, stock }]
  let store = null;

  function load() {
    try { cart = JSON.parse(store.getItem(opts.storageKey)) || []; } catch (_) { cart = []; }
  }
  function save() { store.setItem(opts.storageKey, JSON.stringify(cart)); }

  function find(id) { return cart.find(c => c.id === id); }
  function totalQty() { return cart.reduce((s, c) => s + c.qty, 0); }
  function totalPrice() { return cart.reduce((s, c) => s + c.qty * Number(c.price), 0); }

  function add(item, qty = 1) {
    const existing = find(item.id);
    const max = Math.min(item.max || 99, item.stock || 99);
    if (existing) {
      existing.qty = Math.min(existing.qty + qty, max);
    } else if (qty > 0) {
      cart.push({ id: item.id, name: item.name, price: Number(item.price), image: item.image, qty: Math.min(qty, max), max, stock: item.stock });
    }
    save(); render();
  }

  function setQty(id, qty) {
    const c = find(id); if (!c) return;
    const max = Math.min(c.max || 99, c.stock || 99);
    c.qty = Math.max(0, Math.min(qty, max));
    if (c.qty === 0) cart = cart.filter(x => x.id !== id);
    save(); render();
  }

  function remove(id) { cart = cart.filter(c => c.id !== id); save(); render(); }
  function clear() { cart = []; save(); render(); }

  function render() {
    // Update FAB count
    const fab = document.querySelector('[data-cart-fab]');
    if (fab) {
      const count = totalQty();
      let badge = fab.querySelector('.cart-count');
      if (count > 0) {
        if (!badge) { badge = document.createElement('span'); badge.className = 'cart-count'; fab.appendChild(badge); }
        badge.textContent = count;
      } else if (badge) {
        badge.remove();
      }
    }

    // Update drawer body
    const body = document.querySelector('[data-cart-body]');
    if (!body) return;
    if (cart.length === 0) {
      body.innerHTML = `<div class="cart-empty"><div class="ce-icon">🛒</div><div>Your cart is empty</div></div>`;
    } else {
      body.innerHTML = cart.map(item => `
        <div class="cart-item" data-cart-row="${item.id}">
          <div class="ci-img">${item.image ? `<img src="${item.image}" alt="">` : '<span class="no-image">📦</span>'}</div>
          <div>
            <div class="ci-name">${escapeHtml(item.name)}</div>
            <div class="ci-price">${formatPrice(item.price)} × ${item.qty}</div>
            <div class="qty-stepper" style="margin-top:6px">
              <button type="button" data-qty-dec="${item.id}">−</button>
              <input type="number" value="${item.qty}" min="1" max="${Math.min(item.max, item.stock)}" data-qty-input="${item.id}">
              <button type="button" data-qty-inc="${item.id}">+</button>
            </div>
          </div>
          <div class="ci-controls">
            <button class="ci-remove" type="button" data-cart-remove="${item.id}" aria-label="Remove">×</button>
            <div class="ci-subtotal">${formatPrice(item.qty * item.price)}</div>
          </div>
        </div>`).join('');
    }

    const totalEl = document.querySelector('[data-cart-total]');
    if (totalEl) totalEl.textContent = formatPrice(totalPrice());

    const checkoutBtn = document.querySelector('[data-cart-checkout]');
    if (checkoutBtn) checkoutBtn.disabled = cart.length === 0;
  }

  function openDrawer() {
    document.querySelector('[data-cart-backdrop]')?.classList.add('show');
    document.querySelector('[data-cart-drawer]')?.classList.add('show');
    render();
  }
  function closeDrawer() {
    document.querySelector('[data-cart-backdrop]')?.classList.remove('show');
    document.querySelector('[data-cart-drawer]')?.classList.remove('show');
  }

  function escapeHtml(s) { return window.EN ? window.EN.escapeHtml(s) : String(s); }
  function formatPrice(n) { return window.EN ? window.EN.formatPrice(n) : ('₱' + Number(n).toFixed(2)); }

  function bindEvents() {
    document.addEventListener('click', (e) => {
      if (e.target.closest('[data-cart-fab]')) { e.preventDefault(); openDrawer(); }
      if (e.target.closest('[data-cart-close], [data-cart-backdrop]')) closeDrawer();
      const inc = e.target.closest('[data-qty-inc]');
      if (inc) { const id = +inc.dataset.qtyInc; const c = find(id); if (c) setQty(id, c.qty + 1); }
      const dec = e.target.closest('[data-qty-dec]');
      if (dec) { const id = +dec.dataset.qtyDec; const c = find(id); if (c) setQty(id, c.qty - 1); }
      const rm = e.target.closest('[data-cart-remove]');
      if (rm) remove(+rm.dataset.cartRemove);
      if (e.target.closest('[data-cart-checkout]')) checkout();
    });

    document.addEventListener('change', (e) => {
      const inp = e.target.closest('[data-qty-input]');
      if (inp) setQty(+inp.dataset.qtyInput, parseInt(inp.value || '0', 10));
    });
  }

  async function checkout() {
    if (cart.length === 0) return;

    let guest = null;
    if (opts.collectGuest) {
      guest = await promptGuest();
      if (!guest) return;
    }

    // Confirm modal
    const confirmHtml = `
      <p class="text-muted mb-3">Please review your order:</p>
      <div class="table-wrap" style="border:none">
      <table class="table" style="font-size:.875rem">
        <thead><tr><th>Item</th><th class="text-right">Qty</th><th class="text-right">Subtotal</th></tr></thead>
        <tbody>
          ${cart.map(c => `<tr>
            <td>${escapeHtml(c.name)}</td>
            <td class="text-right">${c.qty}</td>
            <td class="text-right">${formatPrice(c.qty * c.price)}</td>
          </tr>`).join('')}
        </tbody>
        <tfoot><tr><td colspan="2" class="text-right"><strong>Total</strong></td><td class="text-right"><strong>${formatPrice(totalPrice())}</strong></td></tr></tfoot>
      </table></div>`;
    const bd = window.Modal.show({
      title: 'Confirm Order',
      html: confirmHtml,
      footer: `<button class="btn btn-secondary" data-modal-close>Cancel</button>
               <button class="btn" data-confirm-place>Place Order</button>`,
    });

    bd.querySelector('[data-confirm-place]').addEventListener('click', async (e) => {
      const btn = e.currentTarget;
      if (btn.disabled) return;
      btn.disabled = true;
      btn.textContent = 'Placing...';
      try {
        const payload = {
          items: cart.map(c => ({ id: c.id, qty: c.qty })),
          guest,
        };
        const data = await opts.onCheckout(payload);
        window.Modal.close(bd);
        showReceipt(data);
        clear();
        closeDrawer();
      } catch (err) {
        btn.disabled = false;
        btn.textContent = 'Place Order';
      }
    });
  }

  function promptGuest() {
    return new Promise((resolve) => {
      const bd = window.Modal.show({
        title: 'Your Information',
        html: `
          <div class="field"><label>Name <span style="color:var(--danger)">*</span></label>
            <input class="input" id="g-name" required maxlength="150"></div>
          <div class="field"><label>Phone <span style="color:var(--danger)">*</span></label>
            <input class="input" id="g-phone" required maxlength="20" inputmode="tel"></div>
          <div class="field"><label>Note (optional)</label>
            <textarea class="textarea" id="g-note" maxlength="500"></textarea></div>`,
        footer: `<button class="btn btn-secondary" data-modal-close>Cancel</button>
                 <button class="btn" data-guest-ok>Continue</button>`,
      });
      bd.querySelector('[data-guest-ok]').addEventListener('click', () => {
        const name = bd.querySelector('#g-name').value.trim();
        const phone = bd.querySelector('#g-phone').value.trim();
        const note = bd.querySelector('#g-note').value.trim();
        if (!name || !phone) { window.EN.toast('Name and phone are required.', 'error'); return; }
        window.Modal.close(bd);
        resolve({ name, phone, note });
      });
      bd.addEventListener('click', (e) => {
        if (e.target === bd || e.target.matches('[data-modal-close]')) {
          setTimeout(() => resolve(null), 0);
        }
      });
    });
  }

  function showReceipt(data) {
    const itemsRows = (data.items || []).map(i =>
      `<tr><td>${escapeHtml(i.name)}</td><td class="num">${i.qty}</td><td class="num">${formatPrice(i.unit_price)}</td><td class="num">${formatPrice(i.subtotal)}</td></tr>`
    ).join('');
    const bd = window.Modal.show({
      title: 'Order Placed!',
      size: 'lg',
      html: `
        <p>Order <strong>${data.order_code}</strong> has been placed successfully.</p>
        <div class="pin-box receipt-modal-pin" style="background:rgba(46,125,50,.1);border:2px dashed var(--primary);border-radius:8px;padding:16px;text-align:center;margin:16px 0">
          <div style="font-size:.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em">Claim PIN</div>
          <div style="font-size:2.5rem;font-weight:800;color:var(--primary);letter-spacing:.25em">${data.claim_pin}</div>
          <div style="font-size:.8125rem;color:var(--text-muted);margin-top:6px">Show this PIN with your Order ID at the counter to claim.</div>
        </div>
        <table class="table" style="font-size:.875rem">
          <thead><tr><th>Item</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Subtotal</th></tr></thead>
          <tbody>${itemsRows}</tbody>
          <tfoot><tr><td colspan="3" class="text-right"><strong>Total</strong></td><td class="text-right"><strong>${formatPrice(data.total)}</strong></td></tr></tfoot>
        </table>`,
      footer: `<button class="btn btn-secondary" data-modal-close>Close</button>
               <a class="btn" target="_blank" href="${data.receipt_url}">Print Receipt</a>`,
    });
  }

  function init(options) {
    opts = options || {};
    store = opts.storage === 'local' ? localStorage : sessionStorage;
    load();
    bindEvents();
    render();
  }

  window.CartDrawer = { init, add, setQty, remove, clear, openDrawer, closeDrawer, get cart() { return cart; } };
})();
