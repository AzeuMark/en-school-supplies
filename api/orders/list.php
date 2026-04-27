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
$order_type = (string)($_GET['order_type'] ?? 'all'); // all | guest | registered
$date_filter = (string)($_GET['date_filter'] ?? 'all'); // all | today | week | month
$sort = (string)($_GET['sort'] ?? 'newest'); // newest | oldest | total_desc | total_asc | code_asc | code_desc | customer_asc | customer_desc

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

if ($order_type === 'guest') {
    $where[] = 'o.user_id IS NULL';
} elseif ($order_type === 'registered') {
    $where[] = 'o.user_id IS NOT NULL';
}

if ($date_filter === 'today') {
    $where[] = 'DATE(o.created_at) = CURDATE()';
} elseif ($date_filter === 'week') {
    $where[] = 'o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
} elseif ($date_filter === 'month') {
    $where[] = 'o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
}

if ($q !== '') {
    $where[]  = "(
        o.order_code LIKE ?
        OR CAST(o.id AS CHAR) LIKE ?
        OR o.guest_name LIKE ?
        OR o.guest_phone LIKE ?
        OR o.guest_note LIKE ?
        OR o.status LIKE ?
        OR CAST(o.total_price AS CHAR) LIKE ?
        OR o.claim_pin LIKE ?
        OR u.full_name LIKE ?
        OR u.username LIKE ?
        OR u.email LIKE ?
        OR EXISTS (
            SELECT 1
            FROM order_items oi
            WHERE oi.order_id = o.id
              AND (
                  oi.item_name_snapshot LIKE ?
                  OR CAST(oi.quantity AS CHAR) LIKE ?
                  OR CAST(oi.unit_price AS CHAR) LIKE ?
              )
        )
    )";
    $like = '%' . $q . '%';
    $params[] = $like; // order code
    $params[] = $like; // order id
    $params[] = $like; // guest name
    $params[] = $like; // guest phone
    $params[] = $like; // guest note
    $params[] = $like; // status
    $params[] = $like; // total
    $params[] = $like; // claim pin
    $params[] = $like; // customer full name
    $params[] = $like; // customer username
    $params[] = $like; // customer email
    $params[] = $like; // item name
    $params[] = $like; // item qty
    $params[] = $like; // item unit price
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count = $pdo->prepare("SELECT COUNT(*) FROM orders o LEFT JOIN users u ON u.id=o.user_id $sql_where");
$count->execute($params);
$total = (int)$count->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$sort_map = [
    'newest'       => 'o.created_at DESC, o.id DESC',
    'oldest'       => 'o.created_at ASC, o.id ASC',
    'total_desc'   => 'o.total_price DESC, o.created_at DESC',
    'total_asc'    => 'o.total_price ASC, o.created_at DESC',
    'code_asc'     => 'o.order_code ASC, o.created_at DESC',
    'code_desc'    => 'o.order_code DESC, o.created_at DESC',
    'customer_asc' => "COALESCE(NULLIF(u.full_name, ''), NULLIF(o.guest_name, ''), 'Guest') ASC, o.created_at DESC",
    'customer_desc'=> "COALESCE(NULLIF(u.full_name, ''), NULLIF(o.guest_name, ''), 'Guest') DESC, o.created_at DESC",
];
$order_by = $sort_map[$sort] ?? $sort_map['newest'];

$sql = "SELECT o.*, u.full_name AS customer_name
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id
        $sql_where
    ORDER BY $order_by
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
        $is_guest_order = empty($o['user_id']);
        $o['is_guest_order'] = $is_guest_order;
        if (in_array($role, ['admin', 'staff'], true)) {
            $o['claim_pin_display'] = format_claim_pin_display((string)$o['claim_pin'], $is_guest_order);
        }
        // Hide PIN unless admin/staff
        if (!in_array($role, ['admin', 'staff'], true)) {
            unset($o['claim_pin']);
            unset($o['claim_pin_display']);
        }
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
    'filters'     => [
        'status' => $status,
        'order_type' => $order_type,
        'date_filter' => $date_filter,
        'q' => $q,
    ],
    'sort'        => $sort,
]);
