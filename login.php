<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/settings.php';

// If already logged in, redirect to role dashboard
if (is_logged_in()) {
    $role = current_role();
    redirect(url("/{$role}/dashboard/dashboard.php"));
}

$store_name = get_setting('store_name', config('system.store_name'));
$logo_path  = get_setting('logo_path', config('system.logo_path'));
$force_dark = get_setting('force_dark', '0');
$err = flash_pop('flash_error');
$ok  = flash_pop('flash_success');
$next = $_GET['next'] ?? '';
?>
<!doctype html>
<html lang="en" data-force-dark="<?= e($force_dark) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title>Login — <?= e($store_name) ?></title>
<link rel="stylesheet" href="<?= e(url('/assets/css/global.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/components.css')) ?>">
<style>
  .auth-wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
  .auth-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 32px; max-width: 420px; width: 100%; box-shadow: var(--shadow-md); }
  .auth-card .auth-head { text-align: center; margin-bottom: 24px; }
  .auth-card .auth-head img { width: 64px; height: 64px; margin: 0 auto 12px; border-radius: 12px; object-fit: contain; }
  .auth-card .auth-head h1 { color: var(--primary); margin: 0 0 4px; font-size: 1.5rem; }
  .auth-card .auth-foot { text-align: center; margin-top: 16px; font-size: .875rem; color: var(--text-muted); }
</style>
</head>
<body>
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="auth-head">
        <img src="<?= e(url('/' . ltrim($logo_path, '/'))) ?>" alt="" onerror="this.style.display='none'">
        <h1><?= e($store_name) ?></h1>
        <div class="text-muted">Welcome back, please log in</div>
      </div>

      <?php if ($err): ?><div class="page-error"><?= e($err) ?></div><?php endif; ?>
      <?php if ($ok): ?><div class="page-success"><?= e($ok) ?></div><?php endif; ?>

      <form action="<?= e(url('/api/auth/login.php')) ?>" method="post" data-prg-guard>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <div class="field">
          <label for="identifier">Username or Email</label>
          <input class="input" id="identifier" name="identifier" type="text" required autocomplete="username" autofocus>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input class="input" id="password" name="password" type="password" required autocomplete="current-password">
        </div>
        <button class="btn btn-block btn-lg" type="submit">Log In</button>
      </form>

      <div class="auth-foot">
        Don't have an account? <a href="<?= e(url('/register.php')) ?>">Register here</a><br>
        <a href="<?= e(url('/index.php')) ?>">← Back to Home</a>
      </div>
    </div>
  </div>
  <script src="<?= e(url('/assets/js/global.js')) ?>"></script>
  <script src="<?= e(url('/assets/js/theme.js')) ?>"></script>
</body>
</html>
