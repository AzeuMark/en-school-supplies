/* ===== Generic modal helpers =====
   Open by setting .show on .modal-backdrop. Click [data-modal-close] or backdrop to close.
   Build dynamic modals via Modal.show({ title, html, footer, size }). */

(function () {
  function close(backdrop) {
    backdrop.classList.remove('show');
    setTimeout(() => { if (backdrop.dataset.dynamic === '1') backdrop.remove(); }, 250);
  }

  document.addEventListener('click', function (e) {
    const closeBtn = e.target.closest('[data-modal-close]');
    if (closeBtn) {
      const bd = closeBtn.closest('.modal-backdrop');
      if (bd) close(bd);
      return;
    }
    const backdrop = e.target.closest('.modal-backdrop');
    if (backdrop && e.target === backdrop) close(backdrop);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      const open = document.querySelector('.modal-backdrop.show');
      if (open) close(open);
    }
  });

  function open(selector) {
    const el = typeof selector === 'string' ? document.querySelector(selector) : selector;
    if (el) requestAnimationFrame(() => el.classList.add('show'));
  }

  function show({ title = '', html = '', footer = '', size = '' } = {}) {
    const bd = document.createElement('div');
    bd.className = 'modal-backdrop';
    bd.dataset.dynamic = '1';
    bd.innerHTML = `
      <div class="modal ${size === 'lg' ? 'modal-lg' : ''}">
        <div class="modal-header">
          <h3 class="modal-title">${title}</h3>
          <button class="modal-close" data-modal-close aria-label="Close">×</button>
        </div>
        <div class="modal-body">${html}</div>
        ${footer ? `<div class="modal-footer">${footer}</div>` : ''}
      </div>`;
    document.body.appendChild(bd);
    requestAnimationFrame(() => bd.classList.add('show'));
    return bd;
  }

  function confirm({ title = 'Confirm', message = '', confirmText = 'Confirm', cancelText = 'Cancel', danger = false }) {
    return new Promise((resolve) => {
      const bd = show({
        title,
        html: `<p>${message}</p>`,
        footer: `
          <button class="btn btn-secondary" data-modal-close>${cancelText}</button>
          <button class="btn ${danger ? 'btn-danger' : ''}" data-confirm-yes>${confirmText}</button>`,
      });
      bd.querySelector('[data-confirm-yes]').addEventListener('click', () => {
        close(bd); resolve(true);
      });
      bd.addEventListener('click', (e) => {
        if (e.target === bd || e.target.matches('[data-modal-close]')) {
          setTimeout(() => resolve(false), 0);
        }
      });
    });
  }

  window.Modal = { open, close, show, confirm };
})();
