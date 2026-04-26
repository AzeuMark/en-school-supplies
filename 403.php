<?php
http_response_code(403);
if (!defined('APP_ROOT')) { require_once __DIR__ . '/includes/config.php'; require_once __DIR__ . '/includes/helpers.php'; }
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>403 — Access Denied</title>
<link rel="stylesheet" href="<?= e(url('/assets/css/global.css')) ?>">
<style>
  .err-wrap { min-height: 100vh; display: grid; place-items: center; padding: 32px; }
  .err-card { max-width: 480px; text-align: center; background: var(--surface); border: 1px solid var(--border); border-radius: 16px; padding: 40px; box-shadow: var(--shadow-md); }
  .err-code { font-size: 80px; font-weight: 800; color: var(--primary); line-height: 1; margin: 0; }
  .err-title { font-size: 24px; margin: 12px 0 8px; }
  .err-msg { color: var(--text-muted); margin-bottom: 24px; }
  .btn { display: inline-block; background: var(--primary); color: #fff; padding: 10px 24px; border-radius: 8px; text-decoration: none; }
</style>
</head>
<body>
  <div class="err-wrap">
    <div class="err-card">
      <p class="err-code">403</p>
      <h1 class="err-title">Access Denied</h1>
      <p class="err-msg">You don't have permission to view this page.</p>
      <a class="btn" href="<?= e(url('/index.php')) ?>">Go Home</a>
    </div>
  </div>
</body>
</html>
