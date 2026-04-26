/* ===== Custom select dropdown =====
   Markup:
     <select data-custom-select name="x">
       <option value="1">One</option>
     </select>
   The script hides the native <select> and renders a styled equivalent that
   keeps the underlying <select> in sync (so PHP form submission works as usual).
*/

(function () {
  function buildFor(select) {
    if (select.dataset.csInit === '1') return;
    select.dataset.csInit = '1';
    select.style.display = 'none';

    const wrap = document.createElement('div');
    wrap.className = 'custom-select';
    wrap.tabIndex = 0;

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'cs-trigger';

    const list = document.createElement('div');
    list.className = 'cs-options';

    function renderOptions() {
      list.innerHTML = '';
      Array.from(select.options).forEach((opt, i) => {
        const o = document.createElement('div');
        o.className = 'cs-option';
        if (opt.value === select.value) o.classList.add('active');
        o.textContent = opt.textContent;
        o.dataset.value = opt.value;
        o.addEventListener('click', () => {
          select.value = opt.value;
          select.dispatchEvent(new Event('change', { bubbles: true }));
          trigger.textContent = opt.textContent || '\u00a0';
          list.querySelectorAll('.cs-option').forEach(x => x.classList.remove('active'));
          o.classList.add('active');
          wrap.classList.remove('open');
        });
        list.appendChild(o);
      });
    }

    function syncTrigger() {
      const sel = select.options[select.selectedIndex];
      trigger.textContent = sel ? sel.textContent : '\u00a0';
    }

    trigger.addEventListener('click', (e) => {
      e.preventDefault();
      document.querySelectorAll('.custom-select.open').forEach(o => { if (o !== wrap) o.classList.remove('open'); });
      wrap.classList.toggle('open');
    });

    document.addEventListener('click', (e) => {
      if (!wrap.contains(e.target)) wrap.classList.remove('open');
    });

    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(trigger);
    wrap.appendChild(list);
    wrap.appendChild(select);

    syncTrigger();
    renderOptions();

    // observe option changes (e.g. when populated dynamically)
    const obs = new MutationObserver(() => { renderOptions(); syncTrigger(); });
    obs.observe(select, { childList: true });

    select.addEventListener('change', () => { syncTrigger(); list.querySelectorAll('.cs-option').forEach(o => o.classList.toggle('active', o.dataset.value === select.value)); });
  }

  function init(scope = document) {
    scope.querySelectorAll('select[data-custom-select]').forEach(buildFor);
  }

  init();
  window.CustomSelect = { init };
})();
