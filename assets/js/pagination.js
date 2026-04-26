/* ===== Reusable pagination renderer =====
   Usage:
     Pagination.render(container, { current, total, onChange });
*/

(function () {
  function render(container, { current, total, onChange }) {
    container.innerHTML = '';
    if (total <= 1) return;

    const mk = (label, page, opts = {}) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.textContent = label;
      if (opts.active) b.classList.add('active');
      if (opts.disabled) b.disabled = true;
      if (!opts.disabled && !opts.active) {
        b.addEventListener('click', () => onChange(page));
      }
      container.appendChild(b);
    };

    mk('‹', current - 1, { disabled: current <= 1 });

    const window_ = 2;
    const pages = new Set([1, total, current]);
    for (let i = current - window_; i <= current + window_; i++) {
      if (i > 1 && i < total) pages.add(i);
    }
    const sorted = Array.from(pages).sort((a, b) => a - b);
    let prev = 0;
    sorted.forEach(p => {
      if (p - prev > 1) {
        const dots = document.createElement('button');
        dots.textContent = '…'; dots.disabled = true;
        container.appendChild(dots);
      }
      mk(String(p), p, { active: p === current });
      prev = p;
    });

    mk('›', current + 1, { disabled: current >= total });
  }

  window.Pagination = { render };
})();
