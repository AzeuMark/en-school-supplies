<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/settings.php';

if (is_logged_in()) {
    $role = current_role();
    redirect(url("/{$role}/dashboard/dashboard.php"));
}

$store_name = get_setting('store_name', config('system.store_name'));
$logo_path  = get_setting('logo_path', config('system.logo_path'));
$force_dark = get_setting('force_dark', '0');
$err = flash_pop('flash_error');
$old = flash_pop('flash_old') ?? [];
?>
<!doctype html>
<html lang="en" data-force-dark="<?= e($force_dark) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title>Register — <?= e($store_name) ?></title>
<link rel="stylesheet" href="<?= e(url('/assets/css/global.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/components.css')) ?>">
<style>
  .auth-wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
  .auth-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 32px; max-width: 460px; width: 100%; box-shadow: var(--shadow-md); }
  .auth-card .auth-head { text-align: center; margin-bottom: 24px; }
  .auth-card .auth-head img { width: 64px; height: 64px; margin: 0 auto 12px; border-radius: 12px; object-fit: contain; }
  .auth-card .auth-head h1 { color: var(--primary); margin: 0 0 4px; font-size: 1.5rem; }
  .auth-card .auth-foot { text-align: center; margin-top: 16px; font-size: .875rem; color: var(--text-muted); }
  .row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  @media (max-width: 480px) { .row { grid-template-columns: 1fr; } }
</style>
</head>
<body>
  <div class="auth-wrap">
    <div class="auth-card">
      <div class="auth-head">
        <img src="<?= e(url('/' . ltrim($logo_path, '/'))) ?>" alt="" onerror="this.style.display='none'">
        <h1>Create Account</h1>
        <div class="text-muted">Register as a customer — your account will be reviewed by staff before activation.</div>
      </div>

      <?php if ($err): ?><div class="page-error"><?= e($err) ?></div><?php endif; ?>

      <form action="<?= e(url('/api/auth/register.php')) ?>" method="post" data-prg-guard>
        <?= csrf_field() ?>
        <div class="field">
          <label for="username">Username</label>
          <input class="input" id="username" name="username" type="text" required maxlength="50" value="<?= e($old['username'] ?? '') ?>" autocomplete="username">
          <div class="field-help">3-50 characters. Letters, numbers, and underscores only.</div>
        </div>
        <div class="field">
          <label for="full_name">Full Name</label>
          <input class="input" id="full_name" name="full_name" type="text" required maxlength="150" value="<?= e($old['full_name'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="email">Email</label>
          <input class="input" id="email" name="email" type="email" required maxlength="150" value="<?= e($old['email'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="phone">Phone</label>
          <input class="input" id="phone" name="phone" type="tel" required maxlength="20" inputmode="tel" value="<?= e($old['phone'] ?? '') ?>">
        </div>
        <div class="row">
          <div class="field">
            <label for="password">Password</label>
            <input class="input" id="password" name="password" type="password" required minlength="8" autocomplete="new-password">
            <div class="field-help">At least 8 characters, with letters and numbers.</div>
          </div>
          <div class="field">
            <label for="password2">Confirm Password</label>
            <input class="input" id="password2" name="password2" type="password" required minlength="8" autocomplete="new-password">
          </div>
        </div>
        <button class="btn btn-block btn-lg" type="submit">Register</button>
      </form>

      <div class="auth-foot">
        Already have an account? <a href="<?= e(url('/login.php')) ?>">Log in</a><br>
        <a href="<?= e(url('/index.php')) ?>">← Back to Home</a>
      </div>
    </div>
  </div>
  <script src="<?= e(url('/assets/js/global.js')) ?>"></script>
  <script src="<?= e(url('/assets/js/theme.js')) ?>"></script>
</body>
</html>
