<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('admin', 'staff');
require_post();

$raw = file_get_contents('php://input');
$body = $raw ? (json_decode($raw, true) ?: []) : $_POST;
csrf_check($body['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

$order_id = (int)($body['order_id'] ?? 0);
$action   = (string)($body['action'] ?? ''); // 'ready' | 'claim'
$pin_raw  = (string)($body['pin'] ?? '');
$pin      = normalize_claim_pin_input($pin_raw);

if ($order_id <= 0 || !in_array($action, ['ready', 'claim'], true)) {
    json_response(['ok' => false, 'error' => 'Invalid request.'], 400);
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->execute([$order_id]);
$order = $stmt->fetch();
if (!$order) json_response(['ok' => false, 'error' => 'Order not found.'], 404);

$me  = get_current_user_data();
$uid = (int)$me['id'];

if ($action === 'ready') {
    if ($order['status'] !== 'pending') {
        json_response(['ok' => false, 'error' => 'Only pending orders can be marked ready.'], 400);
    }
    $stmt = $pdo->prepare("UPDATE orders SET status='ready', processed_by=?, updated_at=NOW() WHERE id=?");
    $stmt->execute([$uid, $order_id]);
    log_info('Order marked ready', ['order_id' => $order_id, 'by' => $uid]);
    json_response(['ok' => true, 'message' => 'Order marked as Ready.']);
}

// Claim — verify PIN
if ($order['status'] !== 'ready') {
    json_response(['ok' => false, 'error' => 'Only ready orders can be claimed.'], 400);
}
if ($pin === '') {
    json_response(['ok' => false, 'error' => 'Please enter a valid Claim PIN.'], 400);
}
if (!hash_equals((string)$order['claim_pin'], $pin)) {
    log_warning('Claim PIN mismatch', ['order_id' => $order_id, 'by' => $uid]);
    json_response(['ok' => false, 'error' => 'Incorrect Claim PIN.'], 400);
}

$stmt = $pdo->prepare("UPDATE orders SET status='claimed', processed_by=?, updated_at=NOW() WHERE id=?");
$stmt->execute([$uid, $order_id]);
log_info('Order claimed', ['order_id' => $order_id, 'by' => $uid]);
json_response(['ok' => true, 'message' => 'Order has been claimed.']);
