/* ===== Global utilities: toast, fetch wrapper, formatters, CSRF ===== */

(function () {
  // CSRF token from meta tag injected by layout
  function getCsrf() {
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? m.getAttribute('content') : '';
  }

  // ----- Toasts -----
  function ensureToastContainer() {
    let c = document.querySelector('.toast-container');
    if (!c) {
      c = document.createElement('div');
      c.className = 'toast-container';
      document.body.appendChild(c);
    }
    return c;
  }

  function toast(message, type = 'info', duration = 3500) {
    const c = ensureToastContainer();
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    el.innerHTML = `<span>${escapeHtml(message)}</span>`;
    c.appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    setTimeout(() => {
      el.classList.remove('show');
      setTimeout(() => el.remove(), 250);
    }, duration);
  }

  function escapeHtml(s) {
    return String(s)
      .replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;').replaceAll("'", '&#039;');
  }

  // Base path for subdirectory installations (set by PHP in layout)
  const BASE = window.__BASE || '';

  // ----- Fetch wrapper for JSON APIs -----
  async function api(url, { method = 'POST', body = null, formData = null } = {}) {
    if (url.startsWith('/')) url = BASE + url;
    const opts = { method, headers: { 'X-CSRF-Token': getCsrf(), 'X-Requested-With': 'XMLHttpRequest' } };
    if (formData) {
      formData.append('csrf_token', getCsrf());
      opts.body = formData;
    } else if (body) {
      opts.headers['Content-Type'] = 'application/json';
      opts.body = JSON.stringify({ ...body, csrf_token: getCsrf() });
    }
    let res;
    try {
      res = await fetch(url, opts);
    } catch (e) {
      toast('Network error. Please try again.', 'error');
      throw e;
    }
    let data = {};
    try { data = await res.json(); } catch (_) {}
    if (!res.ok || data.ok === false) {
      const msg = data.error || `Request failed (${res.status})`;
      toast(msg, 'error');
      throw new Error(msg);
    }
    return data;
  }

  // ----- Formatters -----
  function formatPrice(amount) {
    return '\u20B1' + Number(amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  // ----- PRG double-submit guard -----
  document.addEventListener('submit', function (ev) {
    const form = ev.target;
    if (form.matches('form[data-prg-guard]')) {
      const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
      if (form.dataset.submitting === '1') { ev.preventDefault(); return; }
      form.dataset.submitting = '1';
      if (submitBtn) submitBtn.disabled = true;
      // safety reset after 8s
      setTimeout(() => { form.dataset.submitting = '0'; if (submitBtn) submitBtn.disabled = false; }, 8000);
    }
  });

  // Auto-dismiss flash messages after 5s
  document.querySelectorAll('.page-error, .page-success').forEach(el => {
    setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity 300ms'; setTimeout(() => el.remove(), 320); }, 5000);
  });

  // Expose
  window.EN = { toast, api, formatPrice, escapeHtml, getCsrf, BASE };
})();
