<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/helpers.php';

$order_code = trim((string)($_GET['order'] ?? ''));
$pin        = trim((string)($_GET['pin'] ?? ''));
$pin_check  = normalize_claim_pin_input($pin);

if ($order_code === '') { http_response_code(400); die('Missing order.'); }

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_code = ? LIMIT 1");
$stmt->execute([$order_code]);
$order = $stmt->fetch();
if (!$order) { http_response_code(404); include __DIR__ . '/404.php'; exit; }

// Access control: owner OR staff/admin OR PIN-matched (for guest)
$me  = get_current_user_data();
$ok  = false;
if ($me) {
    if (in_array($me['role'], ['admin', 'staff'], true)) $ok = true;
    elseif ((int)$order['user_id'] === (int)$me['id'])    $ok = true;
}
if (!$ok && $pin_check !== '' && hash_equals((string)$order['claim_pin'], $pin_check)) $ok = true;

if (!$ok) { http_response_code(403); include __DIR__ . '/403.php'; exit; }

$itemsStmt = $pdo->prepare("SELECT item_name_snapshot, quantity, unit_price FROM order_items WHERE order_id = ?");
$itemsStmt->execute([$order['id']]);
$items = $itemsStmt->fetchAll();

$store_name = get_setting('store_name', config('system.store_name'));
$logo_path  = get_setting('logo_path', config('system.logo_path'));
$store_phone = get_setting('store_phone', '');
$is_guest_order = empty($order['user_id']);
$display_pin = format_claim_pin_display((string)$order['claim_pin'], $is_guest_order);

$customer_label = 'Guest: ' . ($order['guest_name'] ?: 'N/A');
if ($order['user_id']) {
    $u = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
    $u->execute([$order['user_id']]);
    $customer_label = $u->fetchColumn() ?: 'Customer';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Receipt — <?= e($order['order_code']) ?></title>
<link rel="stylesheet" href="<?= e(url('/assets/css/global.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/components.css')) ?>">
<link rel="stylesheet" href="<?= e(url('/assets/css/print.css')) ?>">
</head>
<body>

<div class="receipt-page">
  <div class="r-head">
    <img src="<?= e(url('/' . ltrim($logo_path, '/'))) ?>" alt="" onerror="this.style.display='none'">
    <h1><?= e($store_name) ?></h1>
    <?php if ($store_phone): ?><div class="r-sub">📞 <?= e($store_phone) ?></div><?php endif; ?>
  </div>

  <div class="r-meta">
    <div><span>Order ID</span><strong><?= e($order['order_code']) ?></strong></div>
    <div><span>Date</span><strong><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></strong></div>
    <div><span>Customer</span><strong><?= e($customer_label) ?></strong></div>
    <div><span>Status</span><strong><?= e(ucfirst($order['status'])) ?></strong></div>
    <?php if ($order['guest_phone']): ?>
      <div><span>Phone</span><strong><?= e($order['guest_phone']) ?></strong></div>
    <?php endif; ?>
  </div>

  <?php if ($order['status'] !== 'claimed' && $order['status'] !== 'cancelled'): ?>
  <div class="pin-box">
    <div class="label">Claim PIN</div>
    <div class="value"><?= e($display_pin) ?></div>
    <div class="r-sub" style="margin-top:6px;font-size:.75rem">Show this PIN with your Order ID at the counter to claim.</div>
  </div>
  <?php endif; ?>

  <table class="r-items">
    <thead>
      <tr><th>Item</th><th class="num">Qty</th><th class="num">Price</th><th class="num">Subtotal</th></tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= e($it['item_name_snapshot']) ?></td>
          <td class="num"><?= (int)$it['quantity'] ?></td>
          <td class="num"><?= format_price($it['unit_price']) ?></td>
          <td class="num"><?= format_price($it['unit_price'] * $it['quantity']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="r-total"><span>Total</span><span><?= format_price($order['total_price']) ?></span></div>

  <?php if (!empty($order['guest_note'])): ?>
    <div class="r-foot" style="text-align:left;margin-top:8px"><strong>Note:</strong> <?= e($order['guest_note']) ?></div>
  <?php endif; ?>

  <div class="r-foot">Thank you for shopping at <?= e($store_name) ?>!</div>
</div>

<div class="receipt-actions no-print">
  <button class="btn" onclick="window.print()">🖨️ Print</button>
  <a class="btn btn-secondary" href="<?= e(url('/index.php')) ?>">Done</a>
</div>

</body>
</html>
