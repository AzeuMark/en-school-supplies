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
<html lang="en" data-force-dark="<?= e($force_dark) ?>" data-page-default-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($store_name) ?> — Welcome</title>
<link rel="stylesheet" href="<?= e(url('/assets/css/global.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/components.css')) ?>">
<style>
  /* ── Landing page overrides ── */

  /* Animated gradient background */
  body.landing-page {
    background: var(--bg);
    overflow-x: hidden;
  }

  /* ── Navbar ── */
  .lp-nav {
    position: fixed;
    top: 0; left: 0; right: 0;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 32px;
    height: 64px;
    background: rgba(255,255,255,0.55);
    backdrop-filter: blur(16px) saturate(180%);
    -webkit-backdrop-filter: blur(16px) saturate(180%);
    border-bottom: 1px solid rgba(255,255,255,0.3);
    transition: background 300ms ease, box-shadow 300ms ease;
  }
  :root[data-theme="dark"] .lp-nav,
  :root:not([data-theme]) .lp-nav {
    background: rgba(18,26,18,0.6);
    border-bottom-color: rgba(255,255,255,0.08);
  }
  .lp-nav.scrolled {
    background: rgba(255,255,255,0.82);
    box-shadow: 0 2px 24px rgba(0,0,0,0.08);
  }
  :root[data-theme="dark"] .lp-nav.scrolled,
  :root:not([data-theme]) .lp-nav.scrolled {
    background: rgba(18,26,18,0.88);
  }
  .lp-nav-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: inherit;
  }
  .lp-nav-brand:hover { text-decoration: none; }
  .lp-nav-logo {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    object-fit: contain;
    background: rgba(46,125,50,0.12);
    padding: 4px;
  }
  .lp-nav-name {
    font-weight: 700;
    font-size: 1rem;
    color: var(--text);
    letter-spacing: -.01em;
  }
  .lp-nav-actions {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .lp-theme-btn {
    width: 38px;
    height: 38px;
    border-radius: var(--radius-md);
    border: 1px solid var(--border);
    background: rgba(255,255,255,0.4);
    backdrop-filter: blur(8px);
    color: var(--text);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background var(--transition), border-color var(--transition);
    padding: 0;
  }
  .lp-theme-btn:hover {
    background: rgba(46,125,50,0.12);
    border-color: var(--primary);
  }
  :root[data-theme="dark"] .lp-theme-btn,
  :root:not([data-theme]) .lp-theme-btn {
    background: rgba(0,0,0,0.25);
  }
  .theme-icon { width: 18px; height: 18px; display: block; }

  /* ── Hero ── */
  .lp-hero {
    min-height: 480px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 112px 24px 80px;
    position: relative;
    overflow: hidden;
  }
  .lp-hero-bg {
    position: absolute;
    inset: 0;
    background: linear-gradient(160deg, #1b5e20 0%, #2e7d32 45%, #43a047 80%, #81c784 100%);
    z-index: 0;
  }
  /* Soft fade into page background at the bottom */
  .lp-hero-bg-fade {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 100px;
    background: linear-gradient(to bottom, transparent, var(--bg));
    z-index: 1;
    pointer-events: none;
  }
  /* Decorative blobs */
  .lp-hero-bg::before {
    content: '';
    position: absolute;
    top: -20%;
    right: -10%;
    width: 600px;
    height: 600px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(129,199,132,0.35) 0%, transparent 70%);
    animation: blob-float 8s ease-in-out infinite;
  }
  .lp-hero-bg::after {
    content: '';
    position: absolute;
    bottom: -15%;
    left: -8%;
    width: 500px;
    height: 500px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(46,125,50,0.4) 0%, transparent 70%);
    animation: blob-float 10s ease-in-out infinite reverse;
  }
  @keyframes blob-float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(30px, -30px) scale(1.08); }
  }
  .lp-hero-content {
    position: relative;
    z-index: 1;
    max-width: 720px;
    margin: 0 auto;
  }
  .lp-hero h1 {
    font-size: clamp(2rem, 5vw, 3.25rem);
    font-weight: 800;
    color: #fff;
    margin-bottom: 16px;
    letter-spacing: -.02em;
    line-height: 1.15;
    text-shadow: 0 2px 16px rgba(0,0,0,0.18);
  }
  .lp-hero .tagline {
    font-size: clamp(1rem, 2vw, 1.2rem);
    color: rgba(255,255,255,0.88);
    margin-bottom: 40px;
    line-height: 1.6;
  }
  .lp-hero-cta {
    display: flex;
    gap: 14px;
    justify-content: center;
    flex-wrap: wrap;
  }
  .lp-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 32px;
    border-radius: var(--radius-md);
    background: rgba(255,255,255,0.95);
    color: var(--primary);
    font-weight: 700;
    font-size: 1.0625rem;
    border: 1px solid rgba(255,255,255,0.5);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    text-decoration: none;
    transition: background var(--transition), transform var(--transition), box-shadow var(--transition);
  }
  .lp-btn-primary:hover {
    background: #fff;
    transform: translateY(-2px);
    box-shadow: 0 8px 28px rgba(0,0,0,0.2);
    text-decoration: none;
  }
  .lp-btn-ghost {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 32px;
    border-radius: var(--radius-md);
    background: rgba(255,255,255,0.12);
    backdrop-filter: blur(8px);
    color: #fff;
    font-weight: 700;
    font-size: 1.0625rem;
    border: 1px solid rgba(255,255,255,0.4);
    text-decoration: none;
    transition: background var(--transition), transform var(--transition);
  }
  .lp-btn-ghost:hover {
    background: rgba(255,255,255,0.22);
    transform: translateY(-2px);
    text-decoration: none;
  }
  /* Status — centered absolutely in navbar */
  .lp-nav-status {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: .8125rem;
    font-weight: 600;
    color: var(--text);
    white-space: nowrap;
    pointer-events: none;
  }
  @media (max-width: 600px) { .lp-nav-status { display: none; } }
  .lp-status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #69f0ae;
    box-shadow: 0 0 0 0 rgba(105,240,174,0.7);
    animation: pulse-hero 2s infinite;
  }
  .lp-status-dot.offline { background: #ef9a9a; box-shadow: none; animation: none; }
  .lp-status-dot.maintenance { background: #ffe082; box-shadow: none; animation: none; }
  @keyframes pulse-hero {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(105,240,174,0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(105,240,174,0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(105,240,174,0); }
  }

  /* ── Featured section ── */
  .lp-featured {
    padding: 80px 24px;
    max-width: 1280px;
    margin: 0 auto;
  }
  .lp-section-header {
    text-align: center;
    margin-bottom: 48px;
  }
  .lp-section-eyebrow {
    display: inline-block;
    font-size: .8125rem;
    font-weight: 700;
    letter-spacing: .08em;
    text-transform: uppercase;
    color: var(--primary);
    background: rgba(46,125,50,0.1);
    padding: 4px 12px;
    border-radius: 999px;
    margin-bottom: 12px;
  }
  .lp-section-header h2 {
    font-size: clamp(1.5rem, 3vw, 2.25rem);
    font-weight: 800;
    letter-spacing: -.02em;
    color: var(--text);
    margin-bottom: 8px;
  }
  .lp-section-header p {
    color: var(--text-muted);
    font-size: 1.0625rem;
    margin: 0;
  }

  /* Glass item cards */
  .lp-item-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
  }
  .lp-item-card {
    background: rgba(255,255,255,0.6);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.7);
    border-radius: 20px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    box-shadow: 0 4px 24px rgba(46,125,50,0.07), 0 1px 4px rgba(0,0,0,0.04);
    transition: transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease;
  }
  :root[data-theme="dark"] .lp-item-card {
    background: rgba(30,42,30,0.55);
    border-color: rgba(255,255,255,0.08);
    box-shadow: 0 4px 24px rgba(0,0,0,0.3);
  }
  .lp-item-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(46,125,50,0.14), 0 2px 8px rgba(0,0,0,0.06);
    border-color: rgba(46,125,50,0.3);
  }
  :root[data-theme="dark"] .lp-item-card:hover {
    border-color: rgba(76,175,80,0.35);
    box-shadow: 0 12px 40px rgba(0,0,0,0.4);
  }
  .lp-item-card .img-wrap {
    width: 100%;
    aspect-ratio: 1;
    background: rgba(46,125,50,0.06);
    border-radius: 14px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  :root[data-theme="dark"] .lp-item-card .img-wrap {
    background: rgba(255,255,255,0.05);
  }
  .lp-item-card .img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 300ms ease;
  }
  .lp-item-card:hover .img-wrap img { transform: scale(1.05); }
  .lp-item-card .no-image {
    font-size: 36px;
    color: var(--text-muted);
    opacity: .5;
  }
  .lp-item-card .lp-item-name {
    font-weight: 700;
    font-size: .9375rem;
    color: var(--text);
    line-height: 1.3;
    text-align: center;
  }
  .lp-item-card .lp-item-meta {
    font-size: .8125rem;
    color: var(--text-muted);
    display: flex;
    align-items: center;
    gap: 4px;
    display: block;
    text-align: center;
  }
  .lp-item-card .lp-item-price {
    font-size: 1.125rem;
    font-weight: 800;
    color: var(--primary);
    margin-top: auto;
    text-align: right;
  }
  .lp-stock-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 999px;
    background: rgba(46,125,50,0.1);
    color: var(--primary);
    font-size: .75rem;
    font-weight: 600;
  }

  /* ── Footer ── */
  .lp-footer {
    background: var(--surface);
    border-top: 1px solid var(--border);
    padding: 40px 32px;
    text-align: center;
    color: var(--text-muted);
  }
  .lp-footer-inner {
    max-width: 900px;
    margin: 0 auto;
  }
  .lp-footer-brand {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 16px;
  }
  .lp-footer-brand-logo {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    object-fit: contain;
    background: rgba(46,125,50,0.1);
    padding: 4px;
  }
  .lp-footer-brand-name {
    font-weight: 700;
    font-size: 1rem;
    color: var(--text);
  }
  .lp-footer-contacts {
    display: flex;
    gap: 24px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 16px;
    font-size: .9375rem;
  }
  .lp-footer-contacts a {
    color: var(--text-muted);
    text-decoration: none;
    transition: color var(--transition);
  }
  .lp-footer-contacts a:hover { color: var(--primary); }
  .lp-footer-divider {
    width: 40px;
    height: 2px;
    background: var(--border);
    border-radius: 2px;
    margin: 0 auto 16px;
  }
  .lp-footer-bottom {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    flex-wrap: wrap;
    font-size: .8125rem;
  }

  /* Scroll fade-in animation */
  .fade-up {
    opacity: 0;
    transform: translateY(24px);
    transition: opacity 500ms ease, transform 500ms ease;
  }
  .fade-up.visible {
    opacity: 1;
    transform: translateY(0);
  }

  @media (max-width: 600px) {
    .lp-nav { padding: 0 16px; }
    .lp-hero { padding: 96px 16px 56px; min-height: unset; }
    .lp-featured { padding: 56px 16px; }
    .lp-footer { padding: 32px 16px; }
  }
</style>
</head>
<body class="landing-page">

  <!-- ── Navbar ── -->
  <nav class="lp-nav" id="lpNav">
    <a class="lp-nav-brand" href="<?= e(url('/index.php')) ?>">
      <img class="lp-nav-logo"
           src="<?= e(url('/' . ltrim($logo_path, '/'))) ?>"
           alt="<?= e($store_name) ?>"
           onerror="this.style.display='none'">
      <span class="lp-nav-name"><?= e($store_name) ?></span>
    </a>
    <div class="lp-nav-actions">
      <button class="lp-theme-btn" data-theme-toggle data-no-persist aria-label="Toggle dark mode">
        <!-- icon injected by theme.js -->
      </button>
      <a class="btn btn-sm" href="<?= e(url('/login.php')) ?>">Login</a>
    </div>
    <!-- centered status -->
    <span class="lp-nav-status">
      <span class="lp-status-dot <?= e($status === 'online' ? '' : ($status === 'maintenance' ? 'maintenance' : 'offline')) ?>"></span>
      System <?= e(ucfirst($status)) ?>
    </span>
  </nav>

  <!-- ── Hero ── -->
  <section class="lp-hero">
    <div class="lp-hero-bg"></div>
    <div class="lp-hero-bg-fade"></div>
    <div class="lp-hero-content">
      <h1><?= e($store_name) ?></h1>
      <p class="tagline">Your one-stop shop for school supplies —<br>order online or visit us in store.</p>

      <div class="lp-hero-cta">
        <?php if ($status === 'online' && get_setting('disable_no_login_orders', '0') !== '1'): ?>
          <a class="lp-btn-primary" href="<?= e(url('/kiosk.php')) ?>">
            🛒 Go to Kiosk
          </a>
        <?php endif; ?>
        <a class="lp-btn-ghost" href="<?= e(url('/login.php')) ?>">
          Login &rarr;
        </a>
      </div>
    </div>
  </section>

  <!-- ── Featured Items ── -->
  <?php if (!empty($featured)): ?>
  <section class="lp-featured">
    <div class="lp-section-header fade-up">
      <span class="lp-section-eyebrow">Now Available</span>
      <h2>Featured Items</h2>
      <p>Browse our latest school supplies, ready for pickup.</p>
    </div>

    <div class="lp-item-grid">
      <?php foreach ($featured as $i => $item): ?>
        <div class="lp-item-card fade-up" style="transition-delay: <?= min($i * 60, 420) ?>ms">
          <div class="img-wrap">
            <?php if (!empty($item['item_image']) && file_exists(APP_ROOT . '/' . $item['item_image'])): ?>
              <img src="<?= e(url('/' . $item['item_image'])) ?>" alt="<?= e($item['item_name']) ?>">
            <?php else: ?>
              <span class="no-image">📦</span>
            <?php endif; ?>
          </div>
          <div class="lp-item-name"><?= e($item['item_name']) ?></div>
          <div class="lp-item-meta">
            <span class="lp-stock-badge">✓ <?= (int)$item['stock_count'] ?> in stock</span>
          </div>
          <div class="lp-item-price"><?= format_price($item['price']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <!-- ── Footer ── -->
  <footer class="lp-footer">
    <div class="lp-footer-inner">
      <div class="lp-footer-brand">
        <img class="lp-footer-brand-logo"
             src="<?= e(url('/' . ltrim($logo_path, '/'))) ?>"
             alt="<?= e($store_name) ?>"
             onerror="this.style.display='none'">
        <span class="lp-footer-brand-name"><?= e($store_name) ?></span>
      </div>

      <?php if ($store_phone || $store_email): ?>
      <div class="lp-footer-contacts">
        <?php if ($store_phone): ?>
          <a href="tel:<?= e($store_phone) ?>">📞 <?= e($store_phone) ?></a>
        <?php endif; ?>
        <?php if ($store_email): ?>
          <a href="mailto:<?= e($store_email) ?>">✉️ <?= e($store_email) ?></a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="lp-footer-divider"></div>

      <div class="lp-footer-bottom">
        <span>© <?= date('Y') ?> <?= e($store_name) ?></span>
      </div>
    </div>
  </footer>

  <script src="<?= e(url('/assets/js/global.js')) ?>"></script>
  <script src="<?= e(url('/assets/js/theme.js')) ?>"></script>
  <script>
    // Navbar scroll effect
    (function () {
      var nav = document.getElementById('lpNav');
      function onScroll() {
        nav.classList.toggle('scrolled', window.scrollY > 20);
      }
      window.addEventListener('scroll', onScroll, { passive: true });
      onScroll();
    })();

    // Scroll fade-in observer
    (function () {
      var els = document.querySelectorAll('.fade-up');
      if (!('IntersectionObserver' in window)) {
        els.forEach(function (el) { el.classList.add('visible'); });
        return;
      }
      var obs = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) {
          if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); }
        });
      }, { threshold: 0.12 });
      els.forEach(function (el) { obs.observe(el); });
    })();
  </script>
</body>
</html>
