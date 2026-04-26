<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/helpers.php';

$store_name  = get_setting('store_name', config('system.store_name'));
$store_phone = get_setting('store_phone', config('system.store_phone'));
$store_email = get_setting('store_email', config('system.store_email'));
$logo_path   = get_setting('logo_path', config('system.logo_path'));
$status      = get_setting('system_status', 'online');
$force_dark  = get_setting('force_dark', '0');

// Featured items (top 8 by stock)
$featured = [];
try {
    $featured = $pdo->query("SELECT id, item_name, price, stock_count, item_image FROM inventory WHERE stock_count > 0 ORDER BY created_at DESC LIMIT 8")->fetchAll();
} catch (Throwable $e) { /* table may not exist before setup */ }
?>
<!doctype html>
<html lang="en" data-force-dark="<?= e($force_dark) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($store_name) ?> — Welcome</title>
<link rel="stylesheet" href="<?= e(url('/assets/css/global.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/components.css')) ?>">
<style>
  .hero { background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%); color: #fff; padding: 80px 24px; text-align: center; }
  .hero img { width: 96px; height: 96px; margin: 0 auto 16px; border-radius: 16px; background: #fff; padding: 8px; object-fit: contain; }
  .hero h1 { font-size: 2.5rem; margin-bottom: 8px; }
  .hero .tagline { font-size: 1.125rem; opacity: .92; margin-bottom: 32px; }
  .hero .cta { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
  .hero .btn { background: #fff; color: var(--primary); }
  .hero .btn:hover { background: var(--accent); color: #fff; }
  .hero .btn-outline { background: transparent; color: #fff; border: 2px solid #fff; }
  .hero .btn-outline:hover { background: #fff; color: var(--primary); }
  .featured { padding: 64px 24px; max-width: 1200px; margin: 0 auto; }
  .featured h2 { text-align: center; margin-bottom: 32px; }
  footer { background: var(--surface); border-top: 1px solid var(--border); padding: 32px 24px; text-align: center; color: var(--text-muted); }
  footer .f-row { display: flex; gap: 24px; justify-content: center; flex-wrap: wrap; margin-bottom: 12px; }
</style>
</head>
<body>
  <section class="hero">
    <img src="<?= e(url('/' . ltrim($logo_path, '/'))) ?>" alt="<?= e($store_name) ?>" onerror="this.style.display='none'">
    <h1><?= e($store_name) ?></h1>
    <p class="tagline">Your one-stop shop for school supplies — order online or visit us in store.</p>
    <div class="cta">
      <?php if ($status === 'online' && get_setting('disable_no_login_orders', '0') !== '1'): ?>
        <a class="btn btn-lg" href="<?= e(url('/kiosk.php')) ?>">🛒 Order Now</a>
      <?php endif; ?>
      <a class="btn btn-lg btn-outline" href="<?= e(url('/login.php')) ?>">Login</a>
    </div>
  </section>

  <?php if (!empty($featured)): ?>
  <section class="featured">
    <h2>Featured Items</h2>
    <div class="item-grid">
      <?php foreach ($featured as $item): ?>
        <div class="item-card">
          <div class="img-wrap">
            <?php if (!empty($item['item_image']) && file_exists(APP_ROOT . '/' . $item['item_image'])): ?>
              <img src="<?= e(url('/' . $item['item_image'])) ?>" alt="<?= e($item['item_name']) ?>">
            <?php else: ?>
              <span class="no-image">📦</span>
            <?php endif; ?>
          </div>
          <div class="name"><?= e($item['item_name']) ?></div>
          <div class="meta"><span><?= (int)$item['stock_count'] ?> in stock</span></div>
          <div class="price"><?= format_price($item['price']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <footer>
    <div class="f-row">
      <strong><?= e($store_name) ?></strong>
      <?php if ($store_phone): ?><span>📞 <?= e($store_phone) ?></span><?php endif; ?>
      <?php if ($store_email): ?><span>✉️ <?= e($store_email) ?></span><?php endif; ?>
    </div>
    <div>System status: <span class="system-status <?= e($status) ?>"><span class="dot"></span><?= e(ucfirst($status)) ?></span></div>
    <p style="margin-top: 12px; font-size: .8125rem">© <?= date('Y') ?> <?= e($store_name) ?></p>
  </footer>

  <script src="<?= e(url('/assets/js/global.js')) ?>"></script>
  <script src="<?= e(url('/assets/js/theme.js')) ?>"></script>
</body>
</html>
