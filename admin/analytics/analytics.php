<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('admin');

$PAGE_TITLE   = 'Analytics';
$CURRENT_PAGE = 'analytics';
$PAGE_CSS     = '/admin/analytics/analytics.css';
include __DIR__ . '/../../includes/layout_header.php';

// Period filter
$valid_periods = ['1', '3', '7', '30', 'all'];
$period    = in_array($_GET['period'] ?? '7', $valid_periods) ? ($_GET['period'] ?? '7') : '7';
$days_back = ($period === 'all') ? 0 : max(0, (int)$period - 1);

$period_labels = ['1' => 'Today', '3' => 'Last 3 days', '7' => 'Last 7 days', '30' => 'Last 30 days', 'all' => 'All time'];
$period_label  = $period_labels[$period];

$chart_subtitles = [
    '1'   => "Today's revenue",
    '3'   => 'Daily revenue — last 3 days',
    '7'   => 'Daily revenue — last 7 days',
    '30'  => 'Daily revenue — last 30 days',
    'all' => 'Monthly revenue — all time',
];

// SQL date conditions (plain table + aliased join variant)
if ($period === 'all') {
    $date_cond = $date_cond_j = '';
} elseif ($period === '1') {
    $date_cond   = "AND created_at >= CURDATE()";
    $date_cond_j = "AND o.created_at >= CURDATE()";
} else {
    $date_cond   = "AND created_at >= DATE_SUB(CURDATE(), INTERVAL {$days_back} DAY)";
    $date_cond_j = "AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL {$days_back} DAY)";
}

// Revenue chart data
if ($period === 'all') {
    $revChart = $pdo->query(
        "SELECT DATE_FORMAT(created_at, '%Y-%m') AS d, SUM(total_price) AS rev, COUNT(*) AS cnt
         FROM orders WHERE status IN ('pending','ready','claimed')
         GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY d LIMIT 12"
    )->fetchAll();
} else {
    $revChart = $pdo->query(
        "SELECT DATE(created_at) AS d, SUM(total_price) AS rev, COUNT(*) AS cnt
         FROM orders WHERE status IN ('pending','ready','claimed')
           AND created_at >= DATE_SUB(CURDATE(), INTERVAL {$days_back} DAY)
         GROUP BY DATE(created_at) ORDER BY d"
    )->fetchAll();
}

// Pre-build bar data array
$chart_max = max(array_column($revChart, 'rev') ?: [1]);
$chartBars = [];
if ($period === 'all') {
    foreach ($revChart as $row) {
        $chartBars[] = ['label' => date('M', strtotime($row['d'] . '-01')), 'rev' => (float)$row['rev'], 'cnt' => (int)$row['cnt']];
    }
} else {
    $revIdx = array_column($revChart, null, 'd');
    for ($i = $days_back; $i >= 0; $i--) {
        $d   = date('Y-m-d', strtotime("-{$i} days"));
        $lbl = ($period === '30') ? date('j', strtotime($d)) : ($period === '1' ? 'Today' : date('D', strtotime($d)));
        $row = $revIdx[$d] ?? null;
        $chartBars[] = ['label' => $lbl, 'rev' => $row ? (float)$row['rev'] : 0, 'cnt' => $row ? (int)$row['cnt'] : 0];
    }
}

// Top 5 items by quantity sold
$topItems = $pdo->query(
    "SELECT oi.item_name_snapshot AS name, SUM(oi.quantity) AS qty
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id AND o.status IN ('pending','ready','claimed')
     WHERE 1=1 {$date_cond_j}
     GROUP BY oi.item_name_snapshot ORDER BY qty DESC LIMIT 5"
)->fetchAll();

// Revenue by category
$byCat = $pdo->query(
    "SELECT COALESCE(c.category_name, 'Uncategorized') AS cat,
            SUM(oi.quantity * oi.unit_price) AS rev
     FROM order_items oi
     JOIN orders o ON o.id = oi.order_id AND o.status IN ('pending','ready','claimed')
     LEFT JOIN inventory i ON i.id = oi.item_id
     LEFT JOIN item_categories c ON c.id = i.category_id
     WHERE 1=1 {$date_cond_j}
     GROUP BY cat ORDER BY rev DESC"
)->fetchAll();

// Summary stats
$total_rev    = (float)$pdo->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status IN ('pending','ready','claimed') {$date_cond}")->fetchColumn();
$total_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','ready','claimed') {$date_cond}")->fetchColumn();
$total_items  = (int)$pdo->query("SELECT COUNT(*) FROM inventory")->fetchColumn();
$total_customers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='customer' AND status='active'")->fetchColumn();
?>
<div class="page-header">
  <div class="page-header-main">
    <h1>Analytics</h1>
    <p class="page-subtitle">Monitor sales trends, demand signals, and category performance.</p>
  </div>
  <div class="period-filter">
    <?php foreach (['1' => '1 Day', '3' => '3 Days', '7' => '7 Days', '30' => '30 Days', 'all' => 'All Time'] as $val => $lbl): ?>
      <a href="?period=<?= e($val) ?>" class="period-btn <?= $period === $val ? 'active' : '' ?>"><?= e($lbl) ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="stats-grid">
  <div class="card stat-card stat-card--revenue">
    <div class="stat-icon">💰</div>
    <span class="stat-label">Total Revenue</span>
    <span class="stat-value"><?= format_price($total_rev) ?></span>
    <span class="stat-context"><?= e($period_label) ?></span>
  </div>
  <div class="card stat-card stat-card--orders">
    <div class="stat-icon">📦</div>
    <span class="stat-label">Total Orders</span>
    <span class="stat-value"><?= $total_orders ?></span>
    <span class="stat-context"><?= e($period_label) ?></span>
  </div>
  <div class="card stat-card stat-card--items">
    <div class="stat-icon">🗃️</div>
    <span class="stat-label">Inventory Items</span>
    <span class="stat-value"><?= $total_items ?></span>
    <span class="stat-context">Across all categories</span>
  </div>
  <div class="card stat-card stat-card--customers">
    <div class="stat-icon">👥</div>
    <span class="stat-label">Active Customers</span>
    <span class="stat-value"><?= $total_customers ?></span>
    <span class="stat-context">Registered &amp; active</span>
  </div>
</div>

<div class="analytics-grid mt-6">
  <!-- Revenue chart — full width -->
  <div class="card analytics-card--wide">
    <div class="card-header">
      <div>
        <h3 class="card-title">Revenue Trends</h3>
        <p class="card-subtitle"><?= e($chart_subtitles[$period]) ?></p>
      </div>
    </div>
    <div class="chart-bars" id="rev-chart" data-period="<?= e($period) ?>">
      <?php foreach ($chartBars as $bar):
        $pct = $chart_max > 0 ? round(($bar['rev'] / $chart_max) * 100) : 0;
        $tip = $bar['cnt'] . ' order' . ($bar['cnt'] !== 1 ? 's' : '') . ' · ' . format_price($bar['rev']);
      ?>
        <div class="bar-col" title="<?= e($tip) ?>">
          <div class="bar-val"><?= $bar['rev'] > 0 ? format_price($bar['rev']) : '—' ?></div>
          <div class="bar" style="height:<?= max(4, $pct) ?>%"></div>
          <div class="bar-label"><?= e($bar['label']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Top items -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Top Items Sold</h3>
        <p class="card-subtitle">Ranked by quantity — <?= e($period_label) ?></p>
      </div>
    </div>
    <?php if (empty($topItems)): ?>
      <div class="empty-state"><div class="es-icon">📊</div><div class="es-title">No sales data yet</div></div>
    <?php else: ?>
      <?php
      $maxQty = max(array_column($topItems, 'qty') ?: [1]);
      $rankClasses = ['hb-rank--gold', 'hb-rank--silver', 'hb-rank--bronze'];
      foreach ($topItems as $idx => $ti):
        $rankClass = $rankClasses[$idx] ?? '';
      ?>
        <div class="hor-bar-row">
          <div class="hb-rank <?= $rankClass ?>"><?= $idx + 1 ?></div>
          <div class="hb-label" title="<?= e($ti['name']) ?>"><?= e($ti['name']) ?></div>
          <div class="hb-track"><div class="hb-fill" style="width:<?= round(((int)$ti['qty'] / $maxQty) * 100) ?>%"></div></div>
          <div class="hb-val"><?= (int)$ti['qty'] ?> <small>sold</small></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Revenue by category -->
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">Revenue by Category</h3>
        <p class="card-subtitle">Share of revenue — <?= e($period_label) ?></p>
      </div>
    </div>
    <?php if (empty($byCat)): ?>
      <div class="empty-state"><div class="es-icon">📊</div><div class="es-title">No sales data yet</div></div>
    <?php else: ?>
      <?php
      $maxCat = max(array_column($byCat, 'rev') ?: [1]);
      $totalCatRev = array_sum(array_column($byCat, 'rev'));
      $rankClasses = ['hb-rank--gold', 'hb-rank--silver', 'hb-rank--bronze'];
      foreach ($byCat as $idx => $cr):
        $pctDisplay = $totalCatRev > 0 ? round(((float)$cr['rev'] / $totalCatRev) * 100) : 0;
        $rankClass = $rankClasses[$idx] ?? '';
      ?>
        <div class="hor-bar-row">
          <div class="hb-rank <?= $rankClass ?>"><?= $idx + 1 ?></div>
          <div class="hb-label" title="<?= e($cr['cat']) ?>"><?= e($cr['cat']) ?></div>
          <div class="hb-track"><div class="hb-fill" style="width:<?= round(((float)$cr['rev'] / $maxCat) * 100) ?>%"></div></div>
          <div class="hb-val"><?= format_price($cr['rev']) ?> <small><?= $pctDisplay ?>%</small></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php $PAGE_JS = '/admin/analytics/analytics.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
