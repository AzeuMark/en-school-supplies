<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('admin', 'staff');

$me   = get_current_user_data();
$role = $me['role'];

$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$q        = trim((string)($_GET['q'] ?? ''));
$filter_role   = (string)($_GET['role'] ?? '');
$filter_status = (string)($_GET['status'] ?? '');

$where  = [];
$params = [];

// Staff only see customers (and only pending) for their pending_accounts page
if ($role === 'staff') {
    $where[]  = "u.role = 'customer'";
    if ($filter_status === '') $filter_status = 'pending';
}

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

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$count = $pdo->prepare("SELECT COUNT(*) FROM users u $sql_where");
$count->execute($params);
$total = (int)$count->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$sql = "SELECT u.id, u.full_name, u.username, u.email, u.phone, u.role, u.status, u.flag_reason, u.created_at
        FROM users u
        $sql_where
        ORDER BY u.created_at DESC
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
