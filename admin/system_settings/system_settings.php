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
    'logo_path'               => get_setting('logo_path', config('system.logo_path')),
    'timezone'                => get_setting('timezone', config('system.timezone')),
  'navbar_country_flag'     => get_setting('navbar_country_flag', 'PH'),
    'auto_logout_hours'       => get_setting('auto_logout_hours', (string)config('system.auto_logout_hours')),
    'low_stock_threshold'     => get_setting('low_stock_threshold', get_setting('low_stock_percent', (string)config('system.low_stock_percent'))),
    'kiosk_idle_seconds'      => get_setting('kiosk_idle_seconds', (string)config('system.kiosk_idle_seconds')),
    'force_dark'              => get_setting('force_dark', '0'),
    'system_status'           => get_setting('system_status', 'online'),
    'disable_no_login_orders' => get_setting('disable_no_login_orders', '0'),
    'online_payment'          => get_setting('online_payment', '0'),
];

$logo_display_path = '/' . ltrim($s['logo_path'], '/');
$logo_display_url = file_exists(APP_ROOT . $logo_display_path) ? url($logo_display_path) : null;
?>
<div class="page-header">
  <div class="page-header-main">
    <h1>System Settings</h1>
    <p class="page-subtitle">Configure branding, operations, and platform behavior across the whole system.</p>
  </div>
</div>

<form id="settings-form" enctype="multipart/form-data">
  <div class="settings-grid">
    <!-- Store info -->
    <div class="card">
      <div class="ss-card-header">
        <span class="ss-icon">🏡</span>
        <div>
          <h3 class="card-title">Store Information</h3>
          <p class="card-subtitle">Branding, contact, and regional settings</p>
        </div>
      </div>
      <div class="field">
        <label>Website Logo</label>
        <div class="logo-upload">
          <div class="logo-preview">
            <?php if ($logo_display_url): ?>
              <img src="<?= e($logo_display_url) ?>" alt="Website logo">
            <?php else: ?>
              <div class="logo-preview-empty">No logo uploaded</div>
            <?php endif; ?>
          </div>
          <div class="logo-upload-meta">
            <label class="file-input">
              <input type="file" name="logo_file" accept="image/png,.png" data-file-input>
              <span class="file-input-btn">Choose File</span>
              <span class="file-input-name" data-file-name><?= $logo_display_url ? 'Current logo set' : 'No file chosen' ?></span>
            </label>
            <div class="field-help">PNG only, max 2 MB. This logo appears in the navbar and sidebar.</div>
          </div>
        </div>
      </div>
      <div class="field"><label>Store Name</label><input class="input" name="store_name" value="<?= e($s['store_name']) ?>" required maxlength="100"><div class="field-help">Shown in the navbar, sidebar, login page, and receipts.</div></div>
      <div class="field"><label>Phone</label><input class="input" name="store_phone" value="<?= e($s['store_phone']) ?>" maxlength="20"></div>
      <div class="field"><label>Email</label><input class="input" name="store_email" type="email" value="<?= e($s['store_email']) ?>" maxlength="100"></div>
      <div class="field">
        <label>Timezone</label>
        <select class="select-native" name="timezone" data-custom-select>
          <?php
          function tz_country_code($tz) {
            $map = [
              'Asia/Manila'=>'ph', 'Asia/Singapore'=>'sg', 'Asia/Tokyo'=>'jp', 'Asia/Seoul'=>'kr', 'Asia/Shanghai'=>'cn',
              'Asia/Hong_Kong'=>'hk', 'Asia/Bangkok'=>'th', 'Asia/Jakarta'=>'id', 'Asia/Kuala_Lumpur'=>'my', 'Asia/Ho_Chi_Minh'=>'vn',
              'Asia/Dubai'=>'ae', 'Asia/Calcutta'=>'in', 'America/New_York'=>'us', 'America/Los_Angeles'=>'us', 'America/Chicago'=>'us',
              'America/Denver'=>'us', 'America/Toronto'=>'ca', 'America/Vancouver'=>'ca', 'America/Mexico_City'=>'mx',
              'America/Sao_Paulo'=>'br', 'America/Argentina/Buenos_Aires'=>'ar', 'Europe/London'=>'gb', 'Europe/Paris'=>'fr',
              'Europe/Berlin'=>'de', 'Europe/Madrid'=>'es', 'Europe/Rome'=>'it', 'Europe/Amsterdam'=>'nl', 'Europe/Brussels'=>'be',
              'Europe/Zurich'=>'ch', 'Europe/Vienna'=>'at', 'Europe/Stockholm'=>'se', 'Europe/Oslo'=>'no', 'Europe/Copenhagen'=>'dk',
              'Europe/Warsaw'=>'pl', 'Europe/Moscow'=>'ru', 'Australia/Sydney'=>'au', 'Australia/Melbourne'=>'au',
              'Australia/Brisbane'=>'au', 'Australia/Perth'=>'au', 'Pacific/Auckland'=>'nz', 'Pacific/Fiji'=>'fj',
              'Africa/Cairo'=>'eg', 'Africa/Johannesburg'=>'za', 'Africa/Lagos'=>'ng', 'Africa/Nairobi'=>'ke', 'Asia/Riyadh'=>'sa',
              'Asia/Jerusalem'=>'il', 'Asia/Tehran'=>'ir', 'Asia/Baghdad'=>'iq', 'Pacific/Honolulu'=>'us', 'Pacific/Guam'=>'gu',
              'Pacific/Samoa'=>'ws', 'Pacific/Tahiti'=>'pf',
            ];
            return $map[$tz] ?? '';
          }
          ?>
          <?php foreach (timezone_options() as $region => $zones): ?>
            <optgroup label="<?= e($region) ?>">
              <?php foreach ($zones as $tz => $label): ?>
                <?php $cc = tz_country_code($tz); ?>
                <option value="<?= e($tz) ?>" <?= $cc ? 'data-icon="' . e(url('/assets/images/country_flags/' . $cc . '.png')) . '"' : '' ?> <?= $s['timezone'] === $tz ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
        <div class="field-help">Controls the date and time shown throughout the system.</div>
      </div>
      <div class="field">
        <label>Country</label>
        <select class="select-native" name="navbar_country_flag" data-custom-select>
          <?php foreach (navbar_country_flag_options() as $group => $countries): ?>
            <optgroup label="<?= e($group) ?>">
              <?php foreach ($countries as $code => $label): ?>
                <option value="<?= e($code) ?>" data-icon="<?= e(url('/assets/images/country_flags/' . strtolower($code) . '.png')) ?>" <?= $s['navbar_country_flag'] === $code ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </optgroup>
          <?php endforeach; ?>
        </select>
        <div class="field-help">Choose which flag appears beside the clock in the navbar.</div>
      </div>
    </div>

    <!-- System -->
    <div class="card">
      <div class="ss-card-header">
        <span class="ss-icon">⚙️</span>
        <div>
          <h3 class="card-title">System</h3>
          <p class="card-subtitle">Server, session, and operational settings</p>
        </div>
      </div>
      <div class="field">
        <label>System Status</label>
        <select class="select-native" name="system_status" data-custom-select>
          <option value="online" data-status="online" <?= $s['system_status']==='online'?'selected':'' ?>>Online</option>
          <option value="maintenance" data-status="maintenance" <?= $s['system_status']==='maintenance'?'selected':'' ?>>Maintenance</option>
          <option value="offline" data-status="offline" <?= $s['system_status']==='offline'?'selected':'' ?>>Offline</option>
        </select>
        <div class="field-help">When offline, only admins can log in. Maintenance allows staff too.</div>
      </div>
      <div class="field"><label>Auto-Logout (hours)</label><input class="input" name="auto_logout_hours" type="number" min="1" max="72" value="<?= e($s['auto_logout_hours']) ?>"><div class="field-help">Logs out inactive staff after this many hours for security.</div></div>
      <div class="field"><label>Low Stock Threshold (units)</label><input class="input" name="low_stock_threshold" type="number" min="1" max="100000" step="1" inputmode="numeric" value="<?= e($s['low_stock_threshold']) ?>"><div class="field-help">Items at or below this fixed stock count are marked as low stock.</div></div>
      <div class="field"><label>Kiosk Idle Timeout (seconds)</label><input class="input" name="kiosk_idle_seconds" type="number" min="30" max="600" value="<?= e($s['kiosk_idle_seconds']) ?>"><div class="field-help">Resets the kiosk screen after this many seconds of inactivity.</div></div>
    </div>

    <!-- Toggles -->
    <div class="card">
      <div class="ss-card-header">
        <span class="ss-icon">🎛️</span>
        <div>
          <h3 class="card-title">Feature Toggles</h3>
          <p class="card-subtitle">Enable or disable platform-wide features</p>
        </div>
      </div>
      <div class="toggle-row">
        <div class="toggle-label">
          <span class="toggle-icon">🌙</span>
          <div><strong>Force Dark Mode</strong><div class="field-help">Force dark theme for all users.</div></div>
        </div>
        <label class="toggle"><input type="checkbox" name="force_dark" value="1" <?= $s['force_dark']==='1'?'checked':'' ?>><span class="slider"></span></label>
      </div>
      <div class="toggle-row">
        <div class="toggle-label">
          <span class="toggle-icon">🔐</span>
          <div><strong>Disable No-Login Orders</strong><div class="field-help">Require login to place orders (disables kiosk).</div></div>
        </div>
        <label class="toggle"><input type="checkbox" name="disable_no_login_orders" value="1" <?= $s['disable_no_login_orders']==='1'?'checked':'' ?>><span class="slider"></span></label>
      </div>
      <div class="toggle-row">
        <div class="toggle-label">
          <span class="toggle-icon">💳</span>
          <div><strong>Online Payment</strong><div class="field-help">Show the online payment option during checkout.</div></div>
        </div>
        <label class="toggle"><input type="checkbox" name="online_payment" value="1" <?= $s['online_payment']==='1'?'checked':'' ?>><span class="slider"></span></label>
      </div>
    </div>
  </div>

  <div class="settings-save-bar">
    <button type="submit" class="btn btn-lg">Save Settings</button>
  </div>
</form>

<?php $PAGE_JS = '/admin/system_settings/system_settings.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
