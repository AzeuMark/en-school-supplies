<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('admin');

$me = get_current_user_data();

// Time of day greeting
$hour = (int)date('G');
if ($hour < 12)     $greeting = 'Good morning';
elseif ($hour < 17) $greeting = 'Good afternoon';
else                $greeting = 'Good evening';
$today_display = date('l, F j, Y');

// Stats
$today_orders   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$today_revenue  = (float)$pdo->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE DATE(created_at) = CURDATE() AND status IN ('ready','claimed','pending')")->fetchColumn();
$pending_acc    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
$pending_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$low_stock      = (int)$pdo->query("SELECT COUNT(*) FROM inventory WHERE stock_count > 0 AND stock_count <= 10")->fetchColumn();

// Recent orders
$recent = $pdo->query(
    "SELECT o.*, u.full_name AS customer_name
     FROM orders o
     LEFT JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC
     LIMIT 5"
)->fetchAll();

$PAGE_TITLE   = 'Dashboard';
$CURRENT_PAGE = 'dashboard';
$PAGE_CSS     = '/admin/dashboard/dashboard.css';
include __DIR__ . '/../../includes/layout_header.php';
?>
<div class="page-header">
  <div class="page-header-main">
    <h1><?= e($greeting) ?>, <?= e(explode(' ', $me['full_name'])[0]) ?>! 👋</h1>
    <p class="page-subtitle"><?= e($today_display) ?> · Here’s an overview of your store right now.</p>
  </div>
</div>

<!-- Stats grid -->
<div class="stats-grid">
  <div class="card stat-card stat-card--orders-today">
    <div class="stat-icon">📋</div>
    <span class="stat-label">Orders Today</span>
    <span class="stat-value"><?= $today_orders ?></span>
    <span class="stat-context">All statuses</span>
  </div>
  <div class="card stat-card stat-card--revenue">
    <div class="stat-icon">💰</div>
    <span class="stat-label">Revenue Today</span>
    <span class="stat-value"><?= format_price($today_revenue) ?></span>
    <span class="stat-context">Excl. cancelled</span>
  </div>
  <div class="card stat-card stat-card--pending-orders">
    <div class="stat-icon">⏳</div>
    <span class="stat-label">Pending Orders</span>
    <span class="stat-value"><?= $pending_orders ?></span>
    <span class="stat-context"><?= $pending_orders > 0 ? 'Needs attention' : 'All clear' ?></span>
  </div>
  <div class="card stat-card stat-card--pending-acc">
    <div class="stat-icon">👤</div>
    <span class="stat-label">Pending Accounts</span>
    <span class="stat-value"><?= $pending_acc ?></span>
    <span class="stat-context"><?= $pending_acc > 0 ? 'Awaiting approval' : 'All approved' ?></span>
  </div>
  <div class="card stat-card stat-card--low-stock">
    <div class="stat-icon">⚠️</div>
    <span class="stat-label">Low Stock Items</span>
    <span class="stat-value"><?= $low_stock ?></span>
    <span class="stat-context"><?= $low_stock > 0 ? 'Restock needed' : 'Stock healthy' ?></span>
  </div>
</div>

<!-- Alert strip -->
<?php if ($pending_orders > 0 || $pending_acc > 0 || $low_stock > 0): ?>
<div class="alert-strip mt-5">
  <?php if ($pending_orders > 0): ?>
    <a href="<?= e(url('/admin/manage_orders/manage_orders.php')) ?>" class="alert-pill alert-pill--warning">
      ⏳ <?= $pending_orders ?> pending order<?= $pending_orders > 1 ? 's' : '' ?> awaiting action
    </a>
  <?php endif; ?>
  <?php if ($pending_acc > 0): ?>
    <a href="<?= e(url('/admin/manage_users/manage_users.php')) ?>" class="alert-pill alert-pill--info">
      👤 <?= $pending_acc ?> account<?= $pending_acc > 1 ? 's' : '' ?> awaiting approval
    </a>
  <?php endif; ?>
  <?php if ($low_stock > 0): ?>
    <a href="<?= e(url('/admin/inventory/inventory.php')) ?>" class="alert-pill alert-pill--danger">
      ⚠️ <?= $low_stock ?> item<?= $low_stock > 1 ? 's' : '' ?> running low on stock
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
      <a class="quick-action-link" href="<?= e(url('/admin/manage_orders/manage_orders.php')) ?>">
        <span class="qa-icon-wrap">📦</span>
        <div class="qa-body">
          <div class="qa-title">Manage Orders</div>
          <div class="qa-desc">Process pending and ready orders</div>
        </div>
        <?php if ($pending_orders > 0): ?>
          <span class="qa-badge"><?= $pending_orders ?></span>
        <?php else: ?><span class="qa-arrow">›</span><?php endif; ?>
      </a>
      <a class="quick-action-link" href="<?= e(url('/admin/inventory/inventory.php')) ?>">
        <span class="qa-icon-wrap">📚</span>
        <div class="qa-body">
          <div class="qa-title">Inventory</div>
          <div class="qa-desc">Add and update stock items</div>
        </div>
        <?php if ($low_stock > 0): ?>
          <span class="qa-badge qa-badge--warning"><?= $low_stock ?></span>
        <?php else: ?><span class="qa-arrow">›</span><?php endif; ?>
      </a>
      <a class="quick-action-link" href="<?= e(url('/admin/manage_users/manage_users.php')) ?>">
        <span class="qa-icon-wrap">👥</span>
        <div class="qa-body">
          <div class="qa-title">Manage Users</div>
          <div class="qa-desc">Approve accounts and manage roles</div>
        </div>
        <?php if ($pending_acc > 0): ?>
          <span class="qa-badge"><?= $pending_acc ?></span>
        <?php else: ?><span class="qa-arrow">›</span><?php endif; ?>
      </a>
      <a class="quick-action-link" href="<?= e(url('/admin/analytics/analytics.php')) ?>">
        <span class="qa-icon-wrap">📈</span>
        <div class="qa-body">
          <div class="qa-title">Analytics</div>
          <div class="qa-desc">Sales insights and charts</div>
        </div>
        <span class="qa-arrow">›</span>
      </a>
      <a class="quick-action-link" href="<?= e(url('/admin/system_settings/system_settings.php')) ?>">
        <span class="qa-icon-wrap">⚙️</span>
        <div class="qa-body">
          <div class="qa-title">System Settings</div>
          <div class="qa-desc">Store info and preferences</div>
        </div>
        <span class="qa-arrow">›</span>
      </a>
    </div>
  </div>

  <!-- Recent Orders -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Recent Orders</h3>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/manage_orders/manage_orders.php')) ?>">View all →</a>
    </div>
    <?php if (empty($recent)): ?>
      <div class="empty-state"><div class="es-icon">�</div><div class="es-title">No orders yet</div></div>
    <?php else: ?>
      <div class="table-wrap">
        <table class="table">
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
        </table>
      </div>
    <?php endif; ?>
  </div>

</div>

<?php $PAGE_JS = '/admin/dashboard/dashboard.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
