/* ===== Custom select dropdown =====
   Markup:
     <select data-custom-select name="x">
       <option value="1">One</option>
     </select>
   The script hides the native <select> and renders a styled equivalent that
   keeps the underlying <select> in sync (so PHP form submission works as usual).

   Supports optgroups and data-icon/data-emoji for visual enhancement.
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

    function getOptionVisual(opt) {
      const icon = opt.dataset.icon || '';
      const emoji = opt.dataset.emoji || '';
      const prefix = icon ? `<img src="${icon}" class="cs-icon" alt=""> ` : (emoji ? `<span class="cs-emoji">${emoji}</span> ` : '');
      return prefix + opt.textContent;
    }

    function renderOptions() {
      list.innerHTML = '';
      Array.from(select.children).forEach((child) => {
        if (child.tagName === 'OPTGROUP') {
          const group = document.createElement('div');
          group.className = 'cs-group';
          const label = document.createElement('div');
          label.className = 'cs-group-label';
          label.textContent = child.label;
          group.appendChild(label);
          Array.from(child.children).forEach((opt) => {
            const o = document.createElement('div');
            o.className = 'cs-option';
            if (opt.value === select.value) o.classList.add('active');
            o.innerHTML = getOptionVisual(opt);
            o.dataset.value = opt.value;
            o.addEventListener('click', () => {
              select.value = opt.value;
              select.dispatchEvent(new Event('change', { bubbles: true }));
              trigger.innerHTML = getOptionVisual(opt) || '\u00a0';
              list.querySelectorAll('.cs-option').forEach(x => x.classList.remove('active'));
              o.classList.add('active');
              wrap.classList.remove('open');
            });
            group.appendChild(o);
          });
          list.appendChild(group);
        } else if (child.tagName === 'OPTION') {
          const o = document.createElement('div');
          o.className = 'cs-option';
          if (child.value === select.value) o.classList.add('active');
          o.innerHTML = getOptionVisual(child);
          o.dataset.value = child.value;
          o.addEventListener('click', () => {
            select.value = child.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            trigger.innerHTML = getOptionVisual(child) || '\u00a0';
            list.querySelectorAll('.cs-option').forEach(x => x.classList.remove('active'));
            o.classList.add('active');
            wrap.classList.remove('open');
          });
          list.appendChild(o);
        }
      });
    }

    function syncTrigger() {
      const sel = select.options[select.selectedIndex];
      trigger.innerHTML = sel ? (getOptionVisual(sel) || '\u00a0') : '\u00a0';
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
    obs.observe(select, { childList: true, subtree: true });

    select.addEventListener('change', () => { syncTrigger(); list.querySelectorAll('.cs-option').forEach(o => o.classList.toggle('active', o.dataset.value === select.value)); });
  }

  function init(scope = document) {
    scope.querySelectorAll('select[data-custom-select]').forEach(buildFor);
  }

  init();
  window.CustomSelect = { init };
})();
