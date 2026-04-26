// System Settings — admin
(function () {
  document.getElementById('settings-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const settings = {};

    // Text/number inputs
    form.querySelectorAll('input.input, select.select-native').forEach(el => {
      settings[el.name] = el.value;
    });

    // Checkboxes — send '0' if unchecked
    form.querySelectorAll('input[type="checkbox"]').forEach(el => {
      settings[el.name] = el.checked ? '1' : '0';
    });

    try {
      await EN.api('/api/settings/update.php', { body: { settings } });
      EN.toast('Settings saved.', 'success');
    } catch (_) {}
  });
})();
