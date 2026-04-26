// System Settings — admin
(function () {
  document.getElementById('settings-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const lowStockInput = form.querySelector('input[name="low_stock_percent"]');
    const lowStockValue = parseInt((lowStockInput?.value || '').trim(), 10);
    if (!Number.isInteger(lowStockValue) || lowStockValue < 1 || lowStockValue > 100) {
      EN.toast('Low Stock Threshold must be a whole number between 1 and 100.', 'error');
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
    } catch (_) {}
  });
})();
