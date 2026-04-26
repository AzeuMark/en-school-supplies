<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/settings.php';

require_post();

// Accept JSON body
$raw = file_get_contents('php://input');
$body = $raw ? (json_decode($raw, true) ?: []) : $_POST;
$token = $body['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
csrf_check($token);

$is_guest = !is_logged_in();
$user_id  = $is_guest ? null : (int)$_SESSION['user']['id'];
$role     = current_role();

// System-status enforcement
$sys = get_system_status();
if ($sys === 'maintenance' && $role !== 'admin') {
    json_response(['ok' => false, 'error' => 'The system is under maintenance. Orders are temporarily disabled.'], 403);
}
if ($sys === 'offline') {
    json_response(['ok' => false, 'error' => 'The system is offline.'], 403);
}

// Disabled no-login orders?
if ($is_guest && get_setting('disable_no_login_orders', '0') === '1') {
    json_response(['ok' => false, 'error' => 'Self-service orders are disabled.'], 403);
}

// Parse + validate items
$items = $body['items'] ?? [];
if (!is_array($items) || count($items) === 0) {
    json_response(['ok' => false, 'error' => 'Your cart is empty.'], 400);
}
$normalized = [];
foreach ($items as $row) {
    $id = (int)($row['id'] ?? 0);
    $qty = (int)($row['qty'] ?? 0);
    if ($id <= 0 || $qty <= 0) {
        json_response(['ok' => false, 'error' => 'Invalid cart contents.'], 400);
    }
    $normalized[$id] = ($normalized[$id] ?? 0) + $qty;
}

// Guest fields
$guest_name = $guest_phone = $guest_note = null;
if ($is_guest) {
    $g = $body['guest'] ?? [];
    $guest_name  = sanitize($g['name'] ?? '');
    $guest_phone = sanitize($g['phone'] ?? '');
    $guest_note  = sanitize($g['note'] ?? '');
    if ($guest_name === '' || $guest_phone === '') {
        json_response(['ok' => false, 'error' => 'Name and phone are required.'], 400);
    }
}

// ---- Transaction: lock rows, validate stock, create order, decrement stock ----
$pdo->beginTransaction();
try {
    $ids = array_keys($normalized);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, item_name, price, stock_count, max_order_qty FROM inventory WHERE id IN ($placeholders) FOR UPDATE");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();
    if (count($rows) !== count($ids)) {
        throw new RuntimeException('One or more items no longer exist.');
    }
    $by_id = [];
    foreach ($rows as $r) $by_id[(int)$r['id']] = $r;

    $total = 0.0;
    $order_lines = [];
    foreach ($normalized as $iid => $qty) {
        $r = $by_id[$iid];
        if ($qty > (int)$r['max_order_qty']) {
            throw new RuntimeException("\"{$r['item_name']}\" allows max {$r['max_order_qty']} per order.");
        }
        if ((int)$r['stock_count'] < $qty) {
            throw new RuntimeException("\"{$r['item_name']}\" only has {$r['stock_count']} in stock.");
        }
        $subtotal = (float)$r['price'] * $qty;
        $total += $subtotal;
        $order_lines[] = [
            'item_id' => $iid,
            'name'    => $r['item_name'],
            'qty'     => $qty,
            'price'   => (float)$r['price'],
            'subtotal'=> $subtotal,
        ];
    }

    // Generate unique order_code + PIN
    $order_code = generate_order_code($pdo);
    $claim_pin  = generate_claim_pin();

    // Insert order
    $stmt = $pdo->prepare(
        "INSERT INTO orders (order_code, user_id, guest_name, guest_phone, guest_note, status, total_price, claim_pin, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, NOW(), NOW())"
    );
    $stmt->execute([
        $order_code, $user_id, $guest_name, $guest_phone, $guest_note,
        round($total, 2), $claim_pin,
    ]);
    $order_id = (int)$pdo->lastInsertId();

    // Insert items + decrement stock
    $insItem = $pdo->prepare(
        "INSERT INTO order_items (order_id, item_id, item_name_snapshot, quantity, unit_price)
         VALUES (?, ?, ?, ?, ?)"
    );
    $decStock = $pdo->prepare("UPDATE inventory SET stock_count = stock_count - ? WHERE id = ?");
    foreach ($order_lines as $line) {
        $insItem->execute([$order_id, $line['item_id'], $line['name'], $line['qty'], $line['price']]);
        $decStock->execute([$line['qty'], $line['item_id']]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    log_error('Order create failed', ['error' => $e->getMessage(), 'user_id' => $user_id]);
    json_response(['ok' => false, 'error' => $e->getMessage() ?: 'Failed to create order.'], 400);
}

log_info('Order placed', ['order_code' => $order_code, 'user_id' => $user_id, 'guest' => $is_guest, 'total' => $total]);

// Receipt URL: include PIN as query for guests so just having the URL isn't enough
$receipt_url = url('/receipt.php?order=' . urlencode($order_code) . '&pin=' . urlencode($claim_pin));

json_response([
    'ok'          => true,
    'order_id'    => $order_id,
    'order_code'  => $order_code,
    'claim_pin'   => $claim_pin,
    'total'       => round($total, 2),
    'receipt_url' => $receipt_url,
    'items'       => array_map(function ($l) {
        return [
            'name'       => $l['name'],
            'qty'        => $l['qty'],
            'unit_price' => $l['price'],
            'subtotal'   => $l['subtotal'],
        ];
    }, $order_lines),
]);
