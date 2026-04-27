<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('staff');

$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
$q        = trim((string)($_GET['q'] ?? ''));
$sort     = (string)($_GET['sort'] ?? 'newest');

$where  = ["u.status = 'pending'", "u.role = 'customer'"];
$params = [];

if ($q !== '') {
    $where[]  = '(u.full_name LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}

$sql_where = 'WHERE ' . implode(' AND ', $where);

$allowed_sort = [
    'newest'    => 'u.created_at DESC',
    'oldest'    => 'u.created_at ASC',
    'name_asc'  => 'u.full_name ASC',
    'name_desc' => 'u.full_name DESC',
];
$order_by = $allowed_sort[$sort] ?? 'u.created_at DESC';

$count = $pdo->prepare("SELECT COUNT(*) FROM users u $sql_where");
$count->execute($params);
$total = (int)$count->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$sql = "SELECT u.id, u.full_name, u.username, u.email, u.phone, u.role, u.status, u.created_at
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
