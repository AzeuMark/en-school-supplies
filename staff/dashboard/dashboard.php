<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('staff');

$me = get_current_user_data();

// Time of day greeting
$hour = (int)date('G');
if ($hour < 12)     $greeting = 'Good morning';
elseif ($hour < 17) $greeting = 'Good afternoon';
else                $greeting = 'Good evening';
$today_display = date('l, F j, Y');

$pending_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$ready_orders   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='ready'")->fetchColumn();
$today_orders   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();

$uid = (int)$me['id'];
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE processed_by = ?");
$stmt->execute([$uid]);
$my_processed = (int)$stmt->fetchColumn();

$recent = $pdo->query(
    "SELECT o.*, u.full_name AS customer_name
     FROM orders o LEFT JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC LIMIT 5"
)->fetchAll();

$PAGE_TITLE   = 'Dashboard';
$CURRENT_PAGE = 'dashboard';
$PAGE_CSS     = '/staff/dashboard/dashboard.css';
include __DIR__ . '/../../includes/layout_header.php';
?>
<div class="page-header">
  <div class="page-header-main">
    <h1><?= e($greeting) ?>, <?= e(explode(' ', $me['full_name'])[0]) ?>! 👋</h1>
    <p class="page-subtitle"><?= e($today_display) ?> · View your workload and process customer orders efficiently.</p>
  </div>
</div>

<!-- Stats grid -->
<div class="stats-grid">
  <div class="card stat-card stat-card--pending-orders">
    <div class="stat-icon">⏳</div>
    <span class="stat-label">Pending Orders</span>
    <span class="stat-value"><?= $pending_orders ?></span>
    <span class="stat-context"><?= $pending_orders > 0 ? 'Needs attention' : 'All clear' ?></span>
  </div>
  <div class="card stat-card stat-card--ready-orders">
    <div class="stat-icon">📦</div>
    <span class="stat-label">Ready Orders</span>
    <span class="stat-value"><?= $ready_orders ?></span>
    <span class="stat-context"><?= $ready_orders > 0 ? 'Awaiting pickup' : 'None ready' ?></span>
  </div>
  <div class="card stat-card stat-card--today-orders">
    <div class="stat-icon">📋</div>
    <span class="stat-label">Orders Today</span>
    <span class="stat-value"><?= $today_orders ?></span>
    <span class="stat-context">All statuses</span>
  </div>
  <div class="card stat-card stat-card--my-processed">
    <div class="stat-icon">✅</div>
    <span class="stat-label">My Processed</span>
    <span class="stat-value"><?= $my_processed ?></span>
    <span class="stat-context">Orders handled by you</span>
  </div>
</div>

<!-- Alert strip -->
<?php if ($pending_orders > 0 || $ready_orders > 0): ?>
<div class="alert-strip mt-5">
  <?php if ($pending_orders > 0): ?>
    <a href="<?= e(url('/staff/manage_orders/manage_orders.php')) ?>" class="alert-pill alert-pill--warning">
      ⏳ <?= $pending_orders ?> pending order<?= $pending_orders > 1 ? 's' : '' ?> awaiting action
    </a>
  <?php endif; ?>
  <?php if ($ready_orders > 0): ?>
    <a href="<?= e(url('/staff/manage_orders/manage_orders.php')) ?>" class="alert-pill alert-pill--info">
      📦 <?= $ready_orders ?> order<?= $ready_orders > 1 ? 's' : '' ?> ready for pickup
    </a>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Dashboard grid: Quick Actions + Recent Orders -->
<div class="dashboard-grid mt-5">

  <!-- Quick Actions -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Quick Actions</h3>
    </div>
    <div class="quick-actions">
      <a class="quick-action-link" href="<?= e(url('/staff/manage_orders/manage_orders.php')) ?>">
        <span class="qa-icon-wrap">📦</span>
        <div class="qa-body">
          <div class="qa-title">Manage Orders</div>
          <div class="qa-desc">Process pending and ready orders</div>
        </div>
        <?php if ($pending_orders > 0): ?>
          <span class="qa-badge"><?= $pending_orders ?></span>
        <?php else: ?><span class="qa-arrow">›</span><?php endif; ?>
      </a>
      <a class="quick-action-link" href="<?= e(url('/staff/profile/profile.php')) ?>">
        <span class="qa-icon-wrap">👤</span>
        <div class="qa-body">
          <div class="qa-title">My Profile</div>
          <div class="qa-desc">Update your account details</div>
        </div>
        <span class="qa-arrow">›</span>
      </a>
    </div>
  </div>

  <!-- Recent Orders -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Recent Orders</h3>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('/staff/manage_orders/manage_orders.php')) ?>">View all →</a>
    </div>
    <?php if (empty($recent)): ?>
      <div class="empty-state"><div class="es-icon">📭</div><div class="es-title">No orders yet</div></div>
    <?php else: ?>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Order ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($recent as $o): ?>
            <tr>
              <td><strong><?= e($o['order_code']) ?></strong></td>
              <td><?= e($o['customer_name'] ?: ('Guest: ' . ($o['guest_name'] ?: '—'))) ?></td>
              <td><?= format_price($o['total_price']) ?></td>
              <td><span class="badge status-<?= e($o['status']) ?>"><?= e(ucfirst($o['status'])) ?></span></td>
              <td class="text-muted"><?= date('M j, g:i A', strtotime($o['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php endif; ?>
  </div>

</div>

<?php $PAGE_JS = '/staff/dashboard/dashboard.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
