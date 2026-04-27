<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('admin');

$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$q        = trim((string)($_GET['q'] ?? ''));
$filter_role   = (string)($_GET['role'] ?? '');
$filter_status = (string)($_GET['status'] ?? '');
$date_filter   = (string)($_GET['date_filter'] ?? 'all');
$sort          = (string)($_GET['sort'] ?? 'newest');

$where  = [];
$params = [];

if (in_array($filter_role, ['admin', 'staff', 'customer'], true)) {
    $where[]  = 'u.role = ?';
    $params[] = $filter_role;
}
if (in_array($filter_status, ['active', 'pending', 'flagged'], true)) {
    $where[]  = 'u.status = ?';
    $params[] = $filter_status;
}
if ($q !== '') {
    $where[]  = '(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}

// Date filter
if ($date_filter === 'today') {
    $where[] = 'DATE(u.created_at) = CURDATE()';
} elseif ($date_filter === 'week') {
    $where[] = 'u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)';
} elseif ($date_filter === 'month') {
    $where[] = 'u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)';
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Sorting
$order_by = 'u.created_at DESC';
$allowed_sort = [
    'newest'    => 'u.created_at DESC',
    'oldest'    => 'u.created_at ASC',
    'name_asc'  => 'u.full_name ASC',
    'name_desc' => 'u.full_name DESC',
    'role_asc'  => 'u.role ASC, u.full_name ASC',
    'role_desc' => 'u.role DESC, u.full_name ASC',
];
if (isset($allowed_sort[$sort])) {
    $order_by = $allowed_sort[$sort];
}

$count = $pdo->prepare("SELECT COUNT(*) FROM users u $sql_where");
$count->execute($params);
$total = (int)$count->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$sql = "SELECT u.id, u.full_name, u.username, u.email, u.phone, u.role, u.status, u.flag_reason, u.created_at
        FROM users u
        $sql_where
        ORDER BY $order_by
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

foreach ($users as &$u) $u['id'] = (int)$u['id'];
unset($u);

json_response([
    'ok' => true,
    'users' => $users,
    'page' => $page,
    'total' => $total,
    'total_pages' => $total_pages,
]);
