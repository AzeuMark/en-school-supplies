// System Settings — admin
(function () {
  const form = document.getElementById('settings-form');
  const saveBtn = form?.querySelector('button[type="submit"]');
  const saveBtnContainer = saveBtn?.parentElement;

  // Capture initial form state for dirty checking
  function getFormState() {
    const state = {};
    form.querySelectorAll('input.input, select.select-native, textarea').forEach(el => {
      if (el.name) state[el.name] = el.value;
    });
    form.querySelectorAll('input[type="checkbox"]').forEach(el => {
      if (el.name) state[el.name] = el.checked ? '1' : '0';
    });
    const logoInp = form.querySelector('input[type="file"][name="logo_file"]');
    state['logo_file'] = logoInp?.files?.[0]?.name || '';
    return JSON.stringify(state);
  }

  const initialState = getFormState();
  let currentState = initialState;

  function updateSaveButton() {
    const isDirty = currentState !== initialState;
    if (saveBtn) {
      saveBtn.disabled = !isDirty;
      saveBtn.textContent = isDirty ? 'Save Changes' : 'No Changes';
    }
    if (saveBtnContainer) {
      saveBtnContainer.classList.toggle('save-btn-floating', isDirty);
    }
  }

  // Initial disable
  updateSaveButton();

  // Listen to all form changes
  function onChange() {
    currentState = getFormState();
    updateSaveButton();
  }

  form.querySelectorAll('input.input, select.select-native, textarea').forEach(el => {
    el.addEventListener('input', onChange);
    el.addEventListener('change', onChange);
  });
  form.querySelectorAll('input[type="checkbox"]').forEach(el => {
    el.addEventListener('change', onChange);
  });

  // File input: update filename display and preview
  const logoInput = document.querySelector('[data-file-input]');
  if (logoInput) {
    logoInput.addEventListener('change', () => {
      const nameEl = logoInput.closest('.file-input')?.querySelector('[data-file-name]');
      if (nameEl) {
        nameEl.textContent = logoInput.files && logoInput.files[0] ? logoInput.files[0].name : 'No file chosen';
      }

      // Show preview of selected image before saving
      if (logoInput.files && logoInput.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
          const preview = document.querySelector('.logo-preview');
          if (preview) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Website logo preview">`;
          }
        };
        reader.readAsDataURL(logoInput.files[0]);
      }
      onChange();
    });
  }

  document.getElementById('settings-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const lowStockInput = form.querySelector('input[name="low_stock_threshold"]');
    const lowStockValue = parseInt((lowStockInput?.value || '').trim(), 10);
    if (!Number.isInteger(lowStockValue) || lowStockValue < 1 || lowStockValue > 100000) {
      EN.toast('Low Stock Threshold must be a whole number between 1 and 100000.', 'error');
      if (lowStockInput) {
        lowStockInput.focus();
        lowStockInput.select();
      }
      return;
    }
    if (lowStockInput) lowStockInput.value = String(lowStockValue);

    const formData = new FormData();

    // Text/number inputs
    form.querySelectorAll('input.input, select.select-native').forEach(el => {
      formData.append(`settings[${el.name}]`, el.value);
    });

    // Checkboxes — send '0' if unchecked
    form.querySelectorAll('input[type="checkbox"]').forEach(el => {
      formData.append(`settings[${el.name}]`, el.checked ? '1' : '0');
    });

    // Optional logo upload
    const logoInput = form.querySelector('input[type="file"][name="logo_file"]');
    if (logoInput && logoInput.files && logoInput.files[0]) {
      formData.append('logo_file', logoInput.files[0]);
    }

    try {
      const res = await EN.api('/api/settings/update.php', { formData });
      if (res.logo_url) {
        const preview = form.querySelector('.logo-preview');
        if (preview) preview.innerHTML = `<img src="${res.logo_url}" alt="Website logo">`;
      }
      EN.toast('Settings saved.', 'success');
      // Reset state after successful save
      setTimeout(() => location.reload(), 600);
    } catch (_) {}
  });
})();
