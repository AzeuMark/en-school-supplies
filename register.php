<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php'; //
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/settings.php';

if (is_logged_in()) {
    $role = current_role();
    redirect(url("/{$role}/dashboard/dashboard.php"));
}

$store_name = get_setting('store_name', config('system.store_name'));
$logo_path  = get_setting('logo_path', config('system.logo_path'));
$force_dark = get_setting('force_dark', '0');

$login_err = flash_pop('flash_error');
$login_ok  = flash_pop('flash_success');
$reg_err    = flash_pop('flash_reg_error');
$reg_old    = flash_pop('flash_old') ?? [];
$reg_ferrs  = flash_pop('flash_field_errors') ?? [];

$next = $_GET['next'] ?? '';
$active_tab = 'register';
?>
<!doctype html>
<html lang="en" data-force-dark="<?= e($force_dark) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title>Register — <?= e($store_name) ?></title>
<link rel="icon" type="image/png" href="<?= e(url('/' . ltrim($logo_path, '/'))) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/global.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/components.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/layout.css')) ?>">
<style>
  .app-navbar { height: var(--header-h); }
  .auth-wrap { min-height: calc(100vh - var(--header-h)); display: grid; place-items: center; padding: 24px; }
  .auth-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 32px; max-width: 440px; width: 100%; box-shadow: var(--shadow-md); }
  .auth-head { text-align: center; margin-bottom: 24px; }
  .auth-head h1 { color: var(--primary); margin: 0 0 4px; font-size: 1.5rem; }
  .logo-wrap { width: 72px; height: 72px; margin: 0 auto 12px; border-radius: 14px; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    border: 1px solid var(--border); background: var(--bg); }
  .logo-wrap img { width: 100%; height: 100%; object-fit: contain; }
  .logo-placeholder { display: flex; flex-direction: column; align-items: center; justify-content: center;
    width: 72px; height: 72px; margin: 0 auto 12px; border-radius: 14px;
    border: 1px dashed var(--border); background: var(--bg); gap: 4px; }
  .logo-placeholder svg { width: 28px; height: 28px; color: var(--text-muted); }
  .logo-placeholder span { font-size: .6rem; color: var(--text-muted); text-align: center; line-height: 1.2; padding: 0 4px; }
  .auth-foot { text-align: center; margin-top: 16px; font-size: .875rem; color: var(--text-muted); }

  .auth-tabs { display: flex; border-bottom: 1px solid var(--border); margin-bottom: 24px; }
  .auth-tabs button {
    flex: 1; background: transparent; border: 0; padding: 10px 0;
    font-size: 1rem; font-weight: 600; color: var(--text-muted);
    border-bottom: 3px solid transparent; cursor: pointer;
    transition: color var(--transition), border-color var(--transition);
  }
  .auth-tabs button.active { color: var(--primary); border-bottom-color: var(--primary); }
  .auth-tabs button:hover:not(.active) { color: var(--text); }

  .auth-panel { display: none; }
  .auth-panel.active { display: block; }

  .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  @media (max-width: 480px) { .field-row { grid-template-columns: 1fr; } }

  .input-error { border-color: var(--danger) !important; background: rgba(211,47,47,.04); }
  .input-error:focus { border-color: var(--danger) !important; outline-color: var(--danger); }
</style>
</head>
<body>
  <?php
    $flag_code = strtoupper(preg_replace('/[^A-Z]/', '', (string)get_setting('navbar_country_flag', 'PH')));
    if (strlen($flag_code) !== 2) $flag_code = 'PH';
    $flag_asset = '/assets/images/country_flags/' . strtolower($flag_code) . '.png';
    $flag_exists = file_exists(APP_ROOT . $flag_asset);
    $timezone = get_setting('timezone', config('system.timezone', 'Asia/Manila'));
    $status   = get_setting('system_status', 'online');
    $status_labels = ['online'=>'System Online','maintenance'=>'Under Maintenance','offline'=>'Currently Offline'];
    $status_label  = $status_labels[$status] ?? ucfirst($status);
  ?>
  <header class="app-navbar">
    <a href="<?= e(url('/index.php')) ?>" class="btn btn-secondary btn-sm">← Back</a>
    <div class="nav-center">
      <span class="nav-time">
        <?php if ($flag_exists): ?>
          <img class="nav-flag nav-flag-img" src="<?= e(url($flag_asset)) ?>" alt="<?= e($flag_code) ?>" title="<?= e($flag_code) ?>">
        <?php else: ?>
          <span class="nav-flag nav-flag-fallback"><?= e($flag_code) ?></span>
        <?php endif; ?>
        <span data-navbar-clock data-timezone="<?= e($timezone) ?>"></span>
      </span>
      <span class="system-status <?= e($status) ?>">
        <span class="dot"></span>
        <?= e($status_label) ?>
      </span>
    </div>
    <div class="nav-actions">
      <button class="theme-toggle" data-theme-toggle data-no-persist aria-label="Toggle theme" title="Toggle theme"></button>
    </div>
  </header>
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="auth-head">
        <?php if (!empty($logo_path)): ?>
        <div class="logo-wrap" id="logo-wrap">
          <img src="<?= e(url('/' . ltrim($logo_path, '/'))) ?>" alt="<?= e($store_name) ?> logo"
               onerror="document.getElementById('logo-wrap').outerHTML='<div class=\'logo-placeholder\'><svg xmlns=\'http://www.w3.org/2000/svg\' fill=\'none\' viewBox=\'0 0 24 24\' stroke=\'currentColor\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'1.5\' d=\'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 19.5h18M3 4.5h18\'/></svg><span>No logo found</span></div>'">
        </div>
        <?php else: ?>
        <div class="logo-placeholder">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 19.5h18M3 4.5h18"/>
          </svg>
          <span>No logo found</span>
        </div>
        <?php endif; ?>
        <h1><?= e($store_name) ?></h1>
      </div>

      <div class="auth-tabs">
        <button type="button" data-tab="login" class="<?= $active_tab === 'login' ? 'active' : '' ?>">Login</button>
        <button type="button" data-tab="register" class="<?= $active_tab === 'register' ? 'active' : '' ?>">Register</button>
      </div>

      <!-- LOGIN PANEL -->
      <div class="auth-panel <?= $active_tab === 'login' ? 'active' : '' ?>" id="panel-login">
        <?php if ($login_err): ?><div class="page-error"><?= e($login_err) ?></div><?php endif; ?>
        <?php if ($login_ok): ?><div class="page-success"><?= e($login_ok) ?></div><?php endif; ?>

        <form action="<?= e(url('/api/auth/login.php')) ?>" method="post" data-prg-guard>
          <?= csrf_field() ?>
          <input type="hidden" name="next" value="<?= e($next) ?>">
          <div class="field">
            <label for="identifier">Username or Email</label>
            <input class="input" id="identifier" name="identifier" type="text" required autocomplete="username">
          </div>
          <div class="field">
            <label for="password">Password</label>
            <input class="input" id="password" name="password" type="password" required autocomplete="current-password">
          </div>
          <button class="btn btn-block btn-lg" type="submit">Log In</button>
        </form>
      </div>

      <!-- REGISTER PANEL -->
      <div class="auth-panel <?= $active_tab === 'register' ? 'active' : '' ?>" id="panel-register">
        <div class="text-muted text-center mb-4" style="font-size:.875rem;">Register as a customer — your account will be reviewed by staff before activation.</div>

        <?php if ($reg_err): ?><div class="page-error"><?= e($reg_err) ?></div><?php endif; ?>

        <form action="<?= e(url('/api/auth/register.php')) ?>" method="post" data-prg-guard>
          <?= csrf_field() ?>
          <?php
            function reg_cls($f, $e) { return isset($e[$f]) ? ' input-error' : ''; }
            function reg_msg($f, $e) { if (isset($e[$f])) echo '<div class="field-error">' . htmlspecialchars($e[$f], ENT_QUOTES, 'UTF-8') . '</div>'; }
          ?>
          <div class="field-row">
            <div class="field">
              <label for="full_name">Full Name</label>
              <input class="input<?= reg_cls('full_name', $reg_ferrs) ?>" id="full_name" name="full_name" type="text" required maxlength="150" value="<?= e($reg_old['full_name'] ?? '') ?>">
              <?php reg_msg('full_name', $reg_ferrs); ?>
            </div>
            <div class="field">
              <label for="username">Username</label>
              <input class="input<?= reg_cls('username', $reg_ferrs) ?>" id="username" name="username" type="text" required maxlength="50" value="<?= e($reg_old['username'] ?? '') ?>" autocomplete="username">
              <?php reg_msg('username', $reg_ferrs); ?>
            </div>
          </div>
          <div class="field-row">
            <div class="field">
              <label for="email">Email</label>
              <input class="input<?= reg_cls('email', $reg_ferrs) ?>" id="email" name="email" type="email" required maxlength="150" value="<?= e($reg_old['email'] ?? '') ?>">
              <?php reg_msg('email', $reg_ferrs); ?>
            </div>
            <div class="field">
              <label for="phone">Phone</label>
              <input class="input<?= reg_cls('phone', $reg_ferrs) ?>" id="phone" name="phone" type="tel" required maxlength="20" minlength="7" inputmode="tel" pattern="[0-9+\-\s().]{7,20}" title="7–20 characters: digits, spaces, +, -, (, ) only" placeholder="+63 9XX XXX XXXX" value="<?= e($reg_old['phone'] ?? '') ?>">
              <?php reg_msg('phone', $reg_ferrs); ?>
            </div>
          </div>
          <div class="field-row">
            <div class="field">
              <label for="reg_password">Password</label>
              <input class="input" id="reg_password" name="password" type="password" required minlength="4" autocomplete="new-password">
              <div class="field-help">At least 4 characters.</div>
            </div>
            <div class="field">
              <label for="password2">Confirm Password</label>
              <input class="input<?= reg_cls('password2', $reg_ferrs) ?>" id="password2" name="password2" type="password" required minlength="4" autocomplete="new-password">
              <?php reg_msg('password2', $reg_ferrs); ?>
            </div>
          </div>
          <button class="btn btn-block btn-lg" type="submit">Create Account</button>
        </form>

      </div>
    </div>
  </div>
  <script src="<?= e(url('/assets/js/global.js')) ?>"></script>
  <script src="<?= e(url('/assets/js/theme.js')) ?>"></script>
  <script src="<?= e(url('/assets/js/layout.js')) ?>"></script>
  <script>
    (function () {
      var tabs = document.querySelectorAll('.auth-tabs button');
      var panels = document.querySelectorAll('.auth-panel');
      tabs.forEach(function (btn) {
        btn.addEventListener('click', function () {
          var target = btn.dataset.tab;
          tabs.forEach(function (b) { b.classList.toggle('active', b.dataset.tab === target); });
          panels.forEach(function (p) { p.classList.toggle('active', p.id === 'panel-' + target); });
        });
      });

      // Client-side: highlight empty login fields on submit
      var loginForm = document.querySelector('#panel-login form');
      if (loginForm) {
        loginForm.addEventListener('submit', function () {
          loginForm.querySelectorAll('.input[required]').forEach(function (inp) {
            inp.classList.toggle('input-error', inp.value.trim() === '');
          });
        });
        loginForm.querySelectorAll('.input').forEach(function (inp) {
          inp.addEventListener('input', function () { inp.classList.remove('input-error'); });
        });
      }

      // Client-side: clear error highlight on register fields when user edits them
      var regForm = document.querySelector('#panel-register form');
      if (regForm) {
        regForm.querySelectorAll('.input-error').forEach(function (inp) {
          inp.addEventListener('input', function () {
            inp.classList.remove('input-error');
            var fe = inp.closest('.field') && inp.closest('.field').querySelector('.field-error');
            if (fe) fe.style.display = 'none';
          });
        });
      }
    })();
  </script>
</body>
</html>
