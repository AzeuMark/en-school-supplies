<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('admin');
require_post();

$raw = file_get_contents('php://input');
$body = $raw ? (json_decode($raw, true) ?: []) : $_POST;
csrf_check($body['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

$order_id = (int)($body['order_id'] ?? 0);
if ($order_id <= 0) json_response(['ok' => false, 'error' => 'Invalid order.'], 400);

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? FOR UPDATE");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    if (!$order) throw new RuntimeException('Order not found.');

    // If pending/ready, restore stock first
    if (in_array($order['status'], ['pending', 'ready'], true)) {
        $items = $pdo->prepare("SELECT item_id, quantity FROM order_items WHERE order_id = ?");
        $items->execute([$order_id]);
        $rest = $pdo->prepare("UPDATE inventory SET stock_count = stock_count + ? WHERE id = ?");
        foreach ($items->fetchAll() as $r) {
            $rest->execute([(int)$r['quantity'], (int)$r['item_id']]);
        }
    }
    // Cascade delete handles order_items
    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    json_response(['ok' => false, 'error' => $e->getMessage()], 400);
}

log_info('Order deleted', ['order_id' => $order_id, 'order_code' => $order['order_code'], 'by' => $_SESSION['user']['id']]);
json_response(['ok' => true, 'message' => 'Order deleted.']);
