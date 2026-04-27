<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('customer');

$me  = get_current_user_data();
$uid = (int)$me['id'];

// Time of day greeting
$hour = (int)date('G');
if ($hour < 12)     $greeting = 'Good morning';
elseif ($hour < 17) $greeting = 'Good afternoon';
else                $greeting = 'Good evening';
$today_display = date('l, F j, Y');

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?"); $stmt->execute([$uid]); $total_orders = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'pending'"); $stmt->execute([$uid]); $pending_orders = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'ready'"); $stmt->execute([$uid]); $ready_orders = (int)$stmt->fetchColumn();
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE user_id = ? AND status IN ('pending','ready','claimed')"); $stmt->execute([$uid]); $total_spent = (float)$stmt->fetchColumn();

$recent = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$recent->execute([$uid]);
$recent = $recent->fetchAll();

$PAGE_TITLE   = 'Dashboard';
$CURRENT_PAGE = 'dashboard';
$PAGE_CSS     = '/customer/dashboard/dashboard.css';
include __DIR__ . '/../../includes/layout_header.php';
?>
<div class="page-header">
  <div class="page-header-main">
    <h1><?= e($greeting) ?>, <?= e(explode(' ', $me['full_name'])[0]) ?>! 👋</h1>
    <p class="page-subtitle"><?= e($today_display) ?> · Here's a summary of your orders at a glance.</p>
  </div>
</div>

<!-- Stats grid -->
<div class="stats-grid">
  <div class="card stat-card stat-card--total">
    <div class="stat-icon">📋</div>
    <span class="stat-label">Total Orders</span>
    <span class="stat-value"><?= $total_orders ?></span>
    <span class="stat-context">All time</span>
  </div>
  <div class="card stat-card stat-card--pending">
    <div class="stat-icon">⏳</div>
    <span class="stat-label">Pending</span>
    <span class="stat-value"><?= $pending_orders ?></span>
    <span class="stat-context"><?= $pending_orders > 0 ? 'Being prepared' : 'None pending' ?></span>
  </div>
  <div class="card stat-card stat-card--ready">
    <div class="stat-icon">✅</div>
    <span class="stat-label">Ready to Claim</span>
    <span class="stat-value"><?= $ready_orders ?></span>
    <span class="stat-context"><?= $ready_orders > 0 ? 'Pick up now!' : 'None ready yet' ?></span>
  </div>
  <div class="card stat-card stat-card--spent">
    <div class="stat-icon">💰</div>
    <span class="stat-label">Total Spent</span>
    <span class="stat-value"><?= format_price($total_spent) ?></span>
    <span class="stat-context">Excl. cancelled</span>
  </div>
</div>

<!-- Alert strip -->
<?php if ($ready_orders > 0 || $pending_orders > 0): ?>
<div class="alert-strip mt-5">
  <?php if ($ready_orders > 0): ?>
    <a href="<?= e(url('/customer/order_history/order_history.php')) ?>" class="alert-pill alert-pill--info">
      ✅ <?= $ready_orders ?> order<?= $ready_orders > 1 ? 's' : '' ?> ready to claim
    </a>
  <?php endif; ?>
  <?php if ($pending_orders > 0): ?>
    <a href="<?= e(url('/customer/order_history/order_history.php')) ?>" class="alert-pill alert-pill--warning">
      ⏳ <?= $pending_orders ?> order<?= $pending_orders > 1 ? 's' : '' ?> being prepared
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
      <a class="quick-action-link" href="<?= e(url('/customer/make_order/make_order.php')) ?>">
        <span class="qa-icon-wrap">🛒</span>
        <div class="qa-body">
          <div class="qa-title">New Order</div>
          <div class="qa-desc">Browse items and place an order</div>
        </div>
        <span class="qa-arrow">›</span>
      </a>
      <a class="quick-action-link" href="<?= e(url('/customer/order_history/order_history.php')) ?>">
        <span class="qa-icon-wrap">📋</span>
        <div class="qa-body">
          <div class="qa-title">Order History</div>
          <div class="qa-desc">View all your past and current orders</div>
        </div>
        <?php if ($ready_orders > 0): ?>
          <span class="qa-badge"><?= $ready_orders ?></span>
        <?php else: ?><span class="qa-arrow">›</span><?php endif; ?>
      </a>
      <a class="quick-action-link" href="<?= e(url('/customer/profile/profile.php')) ?>">
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
              <td><?php $c = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?"); $c->execute([(int)$o['id']]); echo (int)$c->fetchColumn() . ' items'; ?></td>
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

<?php $PAGE_JS = '/customer/dashboard/dashboard.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
