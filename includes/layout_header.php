<?php
// Shared header: <html>, <head>, navbar, sidebar opening, main wrapper.
// Pages should set:
//   $PAGE_TITLE   - title shown in <title>
//   $CURRENT_PAGE - key matching the sidebar link (e.g. 'dashboard', 'manage_orders')
//   $PAGE_CSS     - relative path to page-specific CSS (optional)

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/helpers.php';

require_login();

$user        = get_current_user_data();
$role        = $user['role'];
$store_name  = get_setting('store_name', config('system.store_name'));
$logo_path   = get_setting('logo_path', config('system.logo_path'));
$flag_code   = strtoupper(preg_replace('/[^A-Z]/', '', (string)get_setting('navbar_country_flag', 'PH')));
if (strlen($flag_code) !== 2) {
  $flag_code = 'PH';
}
$flag_asset  = '/assets/images/country_flags/' . strtolower($flag_code) . '.png';
$flag_asset_url = url($flag_asset);
$flag_asset_exists = file_exists(APP_ROOT . $flag_asset);
$flag_labels = [
  'PH' => 'Philippines',
  'SG' => 'Singapore',
  'JP' => 'Japan',
  'KR' => 'South Korea',
  'CN' => 'China',
  'IN' => 'India',
  'US' => 'United States',
  'CA' => 'Canada',
  'MX' => 'Mexico',
  'BR' => 'Brazil',
  'GB' => 'United Kingdom',
  'DE' => 'Germany',
  'FR' => 'France',
  'ES' => 'Spain',
  'IT' => 'Italy',
  'AU' => 'Australia',
  'NZ' => 'New Zealand',
  'AE' => 'United Arab Emirates',
  'SA' => 'Saudi Arabia',
  'ZA' => 'South Africa',
];
$flag_label  = $flag_labels[$flag_code] ?? 'Philippines';
$status      = get_setting('system_status', 'online');
$status_labels = [
  'online' => 'System Online',
  'maintenance' => 'Under Maintenance',
  'offline' => 'Currently Offline',
];
$status_label = $status_labels[$status] ?? ucfirst($status);
$force_dark  = get_setting('force_dark', '0');
$user_theme  = $user['theme'] ?? 'auto';
$badges      = get_badge_counts();
$timezone    = get_setting('timezone', config('system.timezone', 'Asia/Manila'));

// Sidebar definition per role.
// Each item: ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => '🏠', 'href' => '/admin/dashboard/dashboard.php', 'badge' => 'pending_orders']
$sidebar_items = [];
if ($role === 'admin') {
    $sidebar_items = [
        ['key' => 'dashboard',         'label' => 'Dashboard',          'icon' => '🏠', 'href' => url('/admin/dashboard/dashboard.php')],
        ['key' => 'manage_orders',     'label' => 'Manage Orders',      'icon' => '📦', 'href' => url('/admin/manage_orders/manage_orders.php'), 'badge' => 'pending_orders'],
        ['key' => 'inventory',         'label' => 'Inventory',          'icon' => '🗃️', 'href' => url('/admin/inventory/inventory.php')],
        ['key' => 'manage_users',      'label' => 'Manage Users',       'icon' => '👥', 'href' => url('/admin/manage_users/manage_users.php'), 'badge' => 'pending_accounts'],
        ['key' => 'analytics',         'label' => 'Analytics',          'icon' => '📈', 'href' => url('/admin/analytics/analytics.php')],
        ['key' => 'system_settings',   'label' => 'System Settings',    'icon' => '⚙️', 'href' => url('/admin/system_settings/system_settings.php')],
    ];
} elseif ($role === 'staff') {
    $sidebar_items = [
        ['key' => 'dashboard',        'label' => 'Dashboard',        'icon' => '🏠', 'href' => url('/staff/dashboard/dashboard.php')],
        ['key' => 'manage_orders',    'label' => 'Manage Orders',    'icon' => '📦', 'href' => url('/staff/manage_orders/manage_orders.php'), 'badge' => 'pending_orders'],
        ['key' => 'pending_users',    'label' => 'Pending Users',    'icon' => '👤', 'href' => url('/staff/pending_users/pending_users.php'), 'badge' => 'pending_accounts'],
    ];
} elseif ($role === 'customer') {
    $sidebar_items = [
        ['key' => 'dashboard',     'label' => 'Dashboard',     'icon' => '🏠', 'href' => url('/customer/dashboard/dashboard.php')],
        ['key' => 'make_order',    'label' => 'Make Order',    'icon' => '🛒', 'href' => url('/customer/make_order/make_order.php')],
        ['key' => 'order_history', 'label' => 'Order History', 'icon' => '📜', 'href' => url('/customer/order_history/order_history.php'), 'badge' => 'active_orders'],
    ];
}

$current_page = $CURRENT_PAGE ?? '';
$page_title   = $PAGE_TITLE ?? 'Dashboard';

$avatar_url = null;
if (!empty($user['avatar']) && file_exists(APP_ROOT . '/' . $user['avatar'])) {
    $avatar_url = url('/' . ltrim($user['avatar'], '/'));
}
$initials = strtoupper(mb_substr($user['full_name'] ?? '?', 0, 1));

$flash_err = flash_pop('flash_error');
$flash_ok  = flash_pop('flash_success');
?>
<!doctype html>
<html lang="en"
      data-force-dark="<?= e($force_dark) ?>"
      data-user-theme="<?= e($user_theme) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($page_title) ?> — <?= e($store_name) ?></title>
  <link rel="icon" type="image/png" href="<?= e(url('/' . ltrim($logo_path, '/'))) ?>">
  <link rel="stylesheet" href="<?= e(url('/assets/css/global.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('/assets/css/components.css')) ?>">
  <link rel="stylesheet" href="<?= e(url('/assets/css/layout.css')) ?>">
  <?php if (!empty($EXTRA_CSS) && is_array($EXTRA_CSS)): foreach ($EXTRA_CSS as $_css): ?>
    <link rel="stylesheet" href="<?= e(url($_css)) ?>">
  <?php endforeach; endif; ?>
  <?php if (!empty($PAGE_CSS)): ?>
    <link rel="stylesheet" href="<?= e(url($PAGE_CSS)) ?>">
  <?php endif; ?>
  <script>window.__BASE='<?= BASE_PATH ?>';</script>
</head>
<body>
  <div class="app-shell">
    <!-- Sidebar -->
    <aside class="app-sidebar">
      <div class="sidebar-brand">
        <button class="nav-hamburger" data-sidebar-toggle aria-label="Toggle sidebar" aria-expanded="false">
          <svg class="nav-icon nav-icon-menu" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M3 6h18M3 12h18M3 18h18"/>
          </svg>
          <svg class="nav-icon nav-icon-collapse" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M14 6l-6 6 6 6"/>
            <path d="M20 12H8"/>
          </svg>
        </button>
        <span class="brand-text"><?= e($store_name) ?></span>
      </div>
      <nav>
        <?php foreach ($sidebar_items as $item):
          $active = $current_page === $item['key'];
          $badge_count = !empty($item['badge']) ? ($badges[$item['badge']] ?? 0) : 0;
        ?>
          <a class="sidebar-link <?= $active ? 'active' : '' ?>" href="<?= e($item['href']) ?>">
            <span class="sl-icon"><?= $item['icon'] ?></span>
            <span class="sl-text"><?= e($item['label']) ?></span>
            <?php if (!empty($item['badge'])): ?>
              <span class="sl-badge" data-badge="<?= e($item['badge']) ?>" style="<?= $badge_count > 0 ? '' : 'display:none' ?>"><?= (int)$badge_count ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </nav>
    </aside>

    <div class="sidebar-backdrop" data-sidebar-backdrop aria-hidden="true"></div>

    <!-- Navbar -->
    <header class="app-navbar">
      <button class="nav-mobile-toggle" data-sidebar-toggle aria-label="Toggle sidebar" aria-expanded="false">
        <svg class="nav-icon nav-icon-menu" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M3 6h18M3 12h18M3 18h18"/>
        </svg>
        <svg class="nav-icon nav-icon-collapse" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M14 6l-6 6 6 6"/>
          <path d="M20 12H8"/>
        </svg>
      </button>
      <div class="nav-brand">
        <?php if (!empty($logo_path) && file_exists(APP_ROOT . '/' . ltrim($logo_path, '/'))): ?>
          <img src="<?= e(url('/' . ltrim($logo_path, '/'))) ?>" alt="<?= e($store_name) ?> logo" onerror="this.style.display='none'">
        <?php endif; ?>
        <span><?= e($store_name) ?></span>
      </div>
      <div class="nav-center">
        <span class="nav-time">
          <?php if ($flag_asset_exists): ?>
            <img class="nav-flag nav-flag-img" src="<?= e($flag_asset_url) ?>" alt="<?= e($flag_label) ?>" title="<?= e($flag_label) ?>">
          <?php else: ?>
            <span class="nav-flag nav-flag-fallback" aria-label="<?= e($flag_label) ?>" title="<?= e($flag_label) ?>"><?= e($flag_code) ?></span>
          <?php endif; ?>
          <span data-navbar-clock data-timezone="<?= e($timezone) ?>"></span>
        </span>

        <span class="system-status <?= e($status) ?>">
          <span class="dot"></span>
          <?= e($status_label) ?>
        </span>
      </div>

      <div class="nav-actions">
        <button class="theme-toggle" data-theme-toggle aria-label="Toggle theme" title="Toggle theme"></button>

        <div class="profile-chip" data-profile-chip>
        <span class="avatar avatar-sm">
          <?php if ($avatar_url): ?>
            <img src="<?= e($avatar_url) ?>" alt="">
          <?php else: ?>
            <?= e($initials) ?>
          <?php endif; ?>
        </span>
        <span class="pc-info">
          <span class="pc-name"><?= e($user['full_name']) ?></span>
          <span class="pc-role"><?= e(ucfirst($role)) ?></span>
        </span>
        <span class="pc-caret" aria-hidden="true">▾</span>
        <div class="profile-dropdown">
          <a href="<?= e(url('/' . $role . '/profile/profile.php')) ?>">👤 Profile</a>
          <form action="<?= e(url('/logout.php')) ?>" method="post" data-prg-guard style="margin:0">
            <?= csrf_field() ?>
            <button type="submit">🚪 Logout</button>
          </form>
        </div>
        </div>
      </div>
    </header>

    <main class="app-main">
      <?php if ($flash_err): ?><div class="page-error"><?= e($flash_err) ?></div><?php endif; ?>
      <?php if ($flash_ok): ?><div class="page-success"><?= e($flash_ok) ?></div><?php endif; ?>
