<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('customer');

$me  = get_current_user_data();
$uid = (int)$me['id'];

$total_orders   = (int)$pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?")->execute([$uid]) ? 0 : 0;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?"); $stmt->execute([$uid]); $total_orders = (int)$stmt->fetchColumn();

$pending_orders = 0;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'pending'"); $stmt->execute([$uid]); $pending_orders = (int)$stmt->fetchColumn();

$ready_orders = 0;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'ready'"); $stmt->execute([$uid]); $ready_orders = (int)$stmt->fetchColumn();

$total_spent = 0;
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE user_id = ? AND status IN ('pending','ready','claimed')"); $stmt->execute([$uid]); $total_spent = (float)$stmt->fetchColumn();

$recent = $pdo->prepare(
    "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5"
);
$recent->execute([$uid]);
$recent = $recent->fetchAll();

$PAGE_TITLE   = 'Dashboard';
$CURRENT_PAGE = 'dashboard';
$PAGE_CSS     = '/customer/dashboard/dashboard.css';
include __DIR__ . '/../../includes/layout_header.php';
?>
<div class="page-header">
  <h1>Welcome, <?= e(explode(' ', $me['full_name'])[0]) ?>! 👋</h1>
</div>

<div class="stats-grid">
  <div class="card stat-card"><span class="stat-label">Total Orders</span><span class="stat-value"><?= $total_orders ?></span></div>
  <div class="card stat-card"><span class="stat-label">Pending</span><span class="stat-value"><?= $pending_orders ?></span></div>
  <div class="card stat-card"><span class="stat-label">Ready to Claim</span><span class="stat-value"><?= $ready_orders ?></span></div>
  <div class="card stat-card"><span class="stat-label">Total Spent</span><span class="stat-value"><?= format_price($total_spent) ?></span></div>
</div>

<h2 class="mt-6 mb-4">Quick Actions</h2>
<div class="actions-grid">
  <a class="action-card" href="<?= e(url('/customer/make_order/make_order.php')) ?>"><span class="ac-icon">🛒</span><div><div class="ac-title">New Order</div><div class="ac-desc">Browse items and place an order</div></div></a>
  <a class="action-card" href="<?= e(url('/customer/order_history/order_history.php')) ?>"><span class="ac-icon">📋</span><div><div class="ac-title">Order History</div><div class="ac-desc">View all your past and current orders</div></div></a>
</div>

<div class="card mt-6">
  <div class="card-header">
    <h3 class="card-title">Recent Orders</h3>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('/customer/order_history/order_history.php')) ?>">View all →</a>
  </div>
  <?php if (empty($recent)): ?>
    <div class="empty-state"><div class="es-icon">📭</div><div class="es-title">No orders yet</div><div>Place your first order to get started!</div></div>
  <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Order ID</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $o): ?>
          <tr>
            <td><strong><?= e($o['order_code']) ?></strong></td>
            <td><?= (int)$pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?")->execute([(int)$o['id']]) ? '—' : '' ?>
              <?php $c = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?"); $c->execute([(int)$o['id']]); echo (int)$c->fetchColumn() . ' items'; ?>
            </td>
            <td><?= format_price($o['total_price']) ?></td>
            <td><span class="badge status-<?= e($o['status']) ?>"><?= e(ucfirst($o['status'])) ?></span></td>
            <td><?= date('M j, g:i A', strtotime($o['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php endif; ?>
</div>

<?php $PAGE_JS = '/customer/dashboard/dashboard.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
