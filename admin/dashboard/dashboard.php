<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('admin');

$me = get_current_user_data();

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
  <h1>Welcome back, <?= e(explode(' ', $me['full_name'])[0]) ?>! 👋</h1>
</div>

<!-- Stats grid -->
<div class="stats-grid">
  <div class="card stat-card">
    <span class="stat-label">Orders Today</span>
    <span class="stat-value"><?= $today_orders ?></span>
  </div>
  <div class="card stat-card">
    <span class="stat-label">Revenue Today</span>
    <span class="stat-value"><?= format_price($today_revenue) ?></span>
  </div>
  <div class="card stat-card">
    <span class="stat-label">Pending Orders</span>
    <span class="stat-value"><?= $pending_orders ?></span>
  </div>
  <div class="card stat-card">
    <span class="stat-label">Pending Accounts</span>
    <span class="stat-value"><?= $pending_acc ?></span>
  </div>
  <div class="card stat-card">
    <span class="stat-label">Low Stock Items</span>
    <span class="stat-value" style="color:var(--warning)"><?= $low_stock ?></span>
  </div>
</div>

<!-- Quick actions -->
<h2 class="mt-6 mb-4">Quick Actions</h2>
<div class="actions-grid">
  <a class="action-card" href="<?= e(url('/admin/manage_orders/manage_orders.php')) ?>"><span class="ac-icon">📦</span><div><div class="ac-title">Manage Orders</div><div class="ac-desc">Process pending and ready orders</div></div></a>
  <a class="action-card" href="<?= e(url('/admin/inventory/inventory.php')) ?>"><span class="ac-icon">📚</span><div><div class="ac-title">Inventory</div><div class="ac-desc">Add and update items</div></div></a>
  <a class="action-card" href="<?= e(url('/admin/manage_users/manage_users.php')) ?>"><span class="ac-icon">👥</span><div><div class="ac-title">Manage Users</div><div class="ac-desc">Add users, approve pending, and handle flagged accounts</div></div></a>
  <a class="action-card" href="<?= e(url('/admin/analytics/analytics.php')) ?>"><span class="ac-icon">📈</span><div><div class="ac-title">Analytics</div><div class="ac-desc">Sales insights and charts</div></div></a>
  <a class="action-card" href="<?= e(url('/admin/system_settings/system_settings.php')) ?>"><span class="ac-icon">⚙️</span><div><div class="ac-title">System Settings</div><div class="ac-desc">Store info and preferences</div></div></a>
</div>

<!-- Recent orders -->
<div class="card mt-6">
  <div class="card-header">
    <h3 class="card-title">Recent Orders</h3>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/manage_orders/manage_orders.php')) ?>">View all →</a>
  </div>
  <?php if (empty($recent)): ?>
    <div class="empty-state"><div class="es-icon">📭</div><div class="es-title">No orders yet</div></div>
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
              <td><?= date('M j, g:i A', strtotime($o['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php $PAGE_JS = '/admin/dashboard/dashboard.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
