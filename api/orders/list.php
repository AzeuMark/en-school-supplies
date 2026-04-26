<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_login();

$me   = get_current_user_data();
$role = $me['role'];

$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$status   = (string)($_GET['status'] ?? '');
$q        = trim((string)($_GET['q'] ?? ''));

$where  = [];
$params = [];

// Customers see only their own
if ($role === 'customer') {
    $where[]  = 'o.user_id = ?';
    $params[] = (int)$me['id'];
}

if ($status !== '' && in_array($status, ['pending', 'ready', 'claimed', 'cancelled'], true)) {
    $where[]  = 'o.status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $where[]  = '(o.order_code LIKE ? OR o.guest_name LIKE ? OR u.full_name LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like; $params[] = $like; $params[] = $like;
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count = $pdo->prepare("SELECT COUNT(*) FROM orders o LEFT JOIN users u ON u.id=o.user_id $sql_where");
$count->execute($params);
$total = (int)$count->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$sql = "SELECT o.*, u.full_name AS customer_name
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id
        $sql_where
        ORDER BY o.created_at DESC
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Attach items
if ($orders) {
    $ids = array_column($orders, 'id');
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $items_stmt = $pdo->prepare("SELECT order_id, item_name_snapshot, quantity, unit_price FROM order_items WHERE order_id IN ($ph)");
    $items_stmt->execute($ids);
    $by_order = [];
    foreach ($items_stmt->fetchAll() as $it) {
        $by_order[$it['order_id']][] = $it;
    }
    foreach ($orders as &$o) {
        $o['items'] = $by_order[$o['id']] ?? [];
        $o['id'] = (int)$o['id'];
        $o['total_price'] = (float)$o['total_price'];
        // Hide PIN unless admin/staff
        if (!in_array($role, ['admin', 'staff'], true)) unset($o['claim_pin']);
    }
    unset($o);
}

json_response([
    'ok'          => true,
    'orders'      => $orders,
    'page'        => $page,
    'total'       => $total,
    'total_pages' => $total_pages,
    'role'        => $role,
]);
