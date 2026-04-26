<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/settings.php';
require_role('admin');

$PAGE_TITLE   = 'System Settings';
$CURRENT_PAGE = 'system_settings';
$PAGE_CSS     = '/admin/system_settings/system_settings.css';
include __DIR__ . '/../../includes/layout_header.php';

$s = [
    'store_name'              => get_setting('store_name', config('system.store_name')),
    'store_phone'             => get_setting('store_phone', config('system.store_phone')),
    'store_email'             => get_setting('store_email', config('system.store_email')),
    'timezone'                => get_setting('timezone', config('system.timezone')),
  'navbar_country_flag'     => get_setting('navbar_country_flag', 'PH'),
    'auto_logout_hours'       => get_setting('auto_logout_hours', (string)config('system.auto_logout_hours')),
    'low_stock_percent'       => get_setting('low_stock_percent', (string)config('system.low_stock_percent')),
    'kiosk_idle_seconds'      => get_setting('kiosk_idle_seconds', (string)config('system.kiosk_idle_seconds')),
    'force_dark'              => get_setting('force_dark', '0'),
    'system_status'           => get_setting('system_status', 'online'),
    'disable_no_login_orders' => get_setting('disable_no_login_orders', '0'),
    'online_payment'          => get_setting('online_payment', '0'),
];
?>
<div class="page-header">
  <h1>System Settings</h1>
</div>

<form id="settings-form">
  <div class="settings-grid">
    <!-- Store info -->
    <div class="card">
      <h3 class="card-title mb-4">Store Information</h3>
      <div class="field"><label>Store Name</label><input class="input" name="store_name" value="<?= e($s['store_name']) ?>" required maxlength="100"></div>
      <div class="field"><label>Phone</label><input class="input" name="store_phone" value="<?= e($s['store_phone']) ?>" maxlength="20"></div>
      <div class="field"><label>Email</label><input class="input" name="store_email" type="email" value="<?= e($s['store_email']) ?>" maxlength="100"></div>
      <div class="field"><label>Timezone</label><input class="input" name="timezone" value="<?= e($s['timezone']) ?>" maxlength="50"></div>
      <div class="field">
        <label>Navbar Country Flag</label>
        <select class="select-native" name="navbar_country_flag">
          <?php foreach (navbar_country_flag_options() as $group => $countries): ?>
            <optgroup label="<?= e($group) ?>">
              <?php foreach ($countries as $code => $label): ?>
                <option value="<?= e($code) ?>" <?= $s['navbar_country_flag'] === $code ? 'selected' : '' ?>><?= e($label) ?> (<?= e(navbar_country_flag_emoji($code)) ?>)</option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
        <div class="field-help">Choose which flag appears beside the clock in the navbar.</div>
      </div>
    </div>

    <!-- System -->
    <div class="card">
      <h3 class="card-title mb-4">System</h3>
      <div class="field">
        <label>System Status</label>
        <select class="select-native" name="system_status">
          <option value="online" <?= $s['system_status']==='online'?'selected':'' ?>>Online</option>
          <option value="maintenance" <?= $s['system_status']==='maintenance'?'selected':'' ?>>Maintenance</option>
          <option value="offline" <?= $s['system_status']==='offline'?'selected':'' ?>>Offline</option>
        </select>
        <div class="field-help">When offline, only admins can log in. Maintenance allows staff too.</div>
      </div>
      <div class="field"><label>Auto-Logout (hours)</label><input class="input" name="auto_logout_hours" type="number" min="1" max="72" value="<?= e($s['auto_logout_hours']) ?>"></div>
      <div class="field"><label>Low Stock Threshold (%)</label><input class="input" name="low_stock_percent" type="number" min="1" max="100" value="<?= e($s['low_stock_percent']) ?>"></div>
      <div class="field"><label>Kiosk Idle Timeout (seconds)</label><input class="input" name="kiosk_idle_seconds" type="number" min="30" max="600" value="<?= e($s['kiosk_idle_seconds']) ?>"></div>
    </div>

    <!-- Toggles -->
    <div class="card">
      <h3 class="card-title mb-4">Feature Toggles</h3>
      <div class="toggle-row">
        <div><strong>Force Dark Mode</strong><div class="field-help">Force dark theme for all users.</div></div>
        <label class="toggle"><input type="checkbox" name="force_dark" value="1" <?= $s['force_dark']==='1'?'checked':'' ?>><span class="slider"></span></label>
      </div>
      <div class="toggle-row">
        <div><strong>Disable No-Login Orders</strong><div class="field-help">Require login to place orders (disables kiosk).</div></div>
        <label class="toggle"><input type="checkbox" name="disable_no_login_orders" value="1" <?= $s['disable_no_login_orders']==='1'?'checked':'' ?>><span class="slider"></span></label>
      </div>
      <div class="toggle-row">
        <div><strong>Online Payment</strong><div class="field-help">Enable online payment option (placeholder).</div></div>
        <label class="toggle"><input type="checkbox" name="online_payment" value="1" <?= $s['online_payment']==='1'?'checked':'' ?>><span class="slider"></span></label>
      </div>
    </div>
  </div>

  <div class="mt-5">
    <button type="submit" class="btn btn-lg">Save Settings</button>
  </div>
</form>

<?php $PAGE_JS = '/admin/system_settings/system_settings.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
