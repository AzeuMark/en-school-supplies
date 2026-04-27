<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('admin');

$PAGE_TITLE   = 'Analytics';
$CURRENT_PAGE = 'analytics';
$PAGE_CSS     = '/admin/analytics/analytics.css';
include __DIR__ . '/../../includes/layout_header.php';

// Revenue last 7 days
$rev7 = $pdo->query(
    "SELECT DATE(created_at) AS d, SUM(total_price) AS rev, COUNT(*) AS cnt
     FROM orders
     WHERE status IN ('pending','ready','claimed')
       AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(created_at)
     ORDER BY d"
)->fetchAll();

// Top 5 items by quantity sold (all time)
$topItems = $pdo->query(
    "SELECT oi.item_name_snapshot AS name, SUM(oi.quantity) AS qty
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id AND o.status IN ('pending','ready','claimed')
     GROUP BY oi.item_name_snapshot
     ORDER BY qty DESC
     LIMIT 5"
)->fetchAll();

// Revenue by category
$byCat = $pdo->query(
    "SELECT COALESCE(c.category_name, 'Uncategorized') AS cat,
            SUM(oi.quantity * oi.unit_price) AS rev
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id AND o.status IN ('pending','ready','claimed')
     LEFT JOIN inventory i ON i.id = oi.item_id
     LEFT JOIN item_categories c ON c.id = i.category_id
     GROUP BY cat
     ORDER BY rev DESC"
)->fetchAll();

// Summary stats
$total_rev   = (float)$pdo->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status IN ('pending','ready','claimed')")->fetchColumn();
$total_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','ready','claimed')")->fetchColumn();
$total_items  = (int)$pdo->query("SELECT COUNT(*) FROM inventory")->fetchColumn();
$total_customers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='customer' AND status='active'")->fetchColumn();
?>
<div class="page-header">
  <div class="page-header-main">
    <h1>Analytics</h1>
    <p class="page-subtitle">Monitor sales trends, demand signals, and category performance.</p>
  </div>
</div>

<div class="stats-grid">
  <div class="card stat-card"><span class="stat-label">Total Revenue</span><span class="stat-value"><?= format_price($total_rev) ?></span></div>
  <div class="card stat-card"><span class="stat-label">Total Orders</span><span class="stat-value"><?= $total_orders ?></span></div>
  <div class="card stat-card"><span class="stat-label">Inventory Items</span><span class="stat-value"><?= $total_items ?></span></div>
  <div class="card stat-card"><span class="stat-label">Active Customers</span><span class="stat-value"><?= $total_customers ?></span></div>
</div>

<div class="analytics-grid mt-6">
  <!-- Revenue chart -->
  <div class="card">
    <h3 class="card-title mb-4">Revenue — Last 7 Days</h3>
    <div class="chart-bars" id="rev-chart">
      <?php
      $max_rev = max(array_column($rev7, 'rev') ?: [1]);
      for ($i = 6; $i >= 0; $i--) {
          $d = date('Y-m-d', strtotime("-{$i} days"));
          $label = date('D', strtotime($d));
          $found = array_filter($rev7, fn($r) => $r['d'] === $d);
          $row = $found ? reset($found) : null;
          $rev = $row ? (float)$row['rev'] : 0;
          $cnt = $row ? (int)$row['cnt'] : 0;
          $pct = $max_rev > 0 ? round(($rev / $max_rev) * 100) : 0;
          echo '<div class="bar-col">';
          echo '<div class="bar-val">' . format_price($rev) . '</div>';
          echo '<div class="bar" style="height:' . max(4, $pct) . '%"></div>';
          echo '<div class="bar-label">' . e($label) . '</div>';
          echo '</div>';
      }
      ?>
    </div>
  </div>

  <!-- Top items -->
  <div class="card">
    <h3 class="card-title mb-4">Top 5 Items (by qty sold)</h3>
    <?php if (empty($topItems)): ?>
      <div class="empty-state"><div class="es-icon">📊</div>No sales data yet</div>
    <?php else: ?>
      <?php $maxQty = max(array_column($topItems, 'qty') ?: [1]); ?>
      <?php foreach ($topItems as $ti): ?>
        <div class="hor-bar-row">
          <div class="hb-label"><?= e($ti['name']) ?></div>
          <div class="hb-track"><div class="hb-fill" style="width:<?= round(((int)$ti['qty'] / $maxQty) * 100) ?>%"></div></div>
          <div class="hb-val"><?= (int)$ti['qty'] ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Revenue by category -->
  <div class="card">
    <h3 class="card-title mb-4">Revenue by Category</h3>
    <?php if (empty($byCat)): ?>
      <div class="empty-state"><div class="es-icon">📊</div>No sales data yet</div>
    <?php else: ?>
      <?php $maxCat = max(array_column($byCat, 'rev') ?: [1]); ?>
      <?php foreach ($byCat as $cr): ?>
        <div class="hor-bar-row">
          <div class="hb-label"><?= e($cr['cat']) ?></div>
          <div class="hb-track"><div class="hb-fill" style="width:<?= round(((float)$cr['rev'] / $maxCat) * 100) ?>%"></div></div>
          <div class="hb-val"><?= format_price($cr['rev']) ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php $PAGE_JS = '/admin/analytics/analytics.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
