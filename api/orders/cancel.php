<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_login();
require_post();

$raw = file_get_contents('php://input');
$body = $raw ? (json_decode($raw, true) ?: []) : $_POST;
csrf_check($body['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

$order_id = (int)($body['order_id'] ?? 0);
if ($order_id <= 0) json_response(['ok' => false, 'error' => 'Invalid order.'], 400);

$me   = get_current_user_data();
$role = $me['role'];
$uid  = (int)$me['id'];

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? FOR UPDATE");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    if (!$order) throw new RuntimeException('Order not found.');

    // Customer can only cancel their own pending/ready orders.
    if ($role === 'customer') {
        if ((int)$order['user_id'] !== $uid) throw new RuntimeException('Not your order.');
        if (!in_array($order['status'], ['pending', 'ready'], true)) {
            throw new RuntimeException('This order can no longer be cancelled.');
        }
    } else {
        // Staff/admin can cancel anything not yet claimed.
        if ($order['status'] === 'claimed') throw new RuntimeException('Claimed orders cannot be cancelled.');
        if ($order['status'] === 'cancelled') throw new RuntimeException('Order is already cancelled.');
    }

    // Restore stock
    $items = $pdo->prepare("SELECT item_id, quantity FROM order_items WHERE order_id = ?");
    $items->execute([$order_id]);
    $rows = $items->fetchAll();

    $rest = $pdo->prepare("UPDATE inventory SET stock_count = stock_count + ? WHERE id = ?");
    foreach ($rows as $r) {
        $rest->execute([(int)$r['quantity'], (int)$r['item_id']]);
    }

    $stmt = $pdo->prepare("UPDATE orders SET status='cancelled', processed_by=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([$uid, $order_id]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_response(['ok' => false, 'error' => $e->getMessage()], 400);
}

log_info('Order cancelled', ['order_id' => $order_id, 'by' => $uid, 'role' => $role]);
json_response(['ok' => true, 'message' => 'Order cancelled and stock restored.']);
