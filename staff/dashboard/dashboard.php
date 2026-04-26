<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('staff');

$me = get_current_user_data();

$pending_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
$ready_orders   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='ready'")->fetchColumn();
$today_orders   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();

// My processed count
$uid = (int)$me['id'];
$my_processed = (int)$pdo->prepare("SELECT COUNT(*) FROM orders WHERE processed_by = ?")->execute([$uid]) ? 0 : 0;
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
  <h1>Welcome, <?= e(explode(' ', $me['full_name'])[0]) ?>! 👋</h1>
</div>

<div class="stats-grid">
  <div class="card stat-card"><span class="stat-label">Pending Orders</span><span class="stat-value"><?= $pending_orders ?></span></div>
  <div class="card stat-card"><span class="stat-label">Ready Orders</span><span class="stat-value"><?= $ready_orders ?></span></div>
  <div class="card stat-card"><span class="stat-label">Orders Today</span><span class="stat-value"><?= $today_orders ?></span></div>
  <div class="card stat-card"><span class="stat-label">My Processed</span><span class="stat-value"><?= $my_processed ?></span></div>
</div>

<h2 class="mt-6 mb-4">Quick Actions</h2>
<div class="actions-grid">
  <a class="action-card" href="<?= e(url('/staff/manage_orders/manage_orders.php')) ?>"><span class="ac-icon">📦</span><div><div class="ac-title">Manage Orders</div><div class="ac-desc">Process pending and ready orders</div></div></a>
</div>

<div class="card mt-6">
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
            <td><?= date('M j, g:i A', strtotime($o['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>

<?php $PAGE_JS = '/staff/dashboard/dashboard.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
