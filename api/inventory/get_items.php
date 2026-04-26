<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/settings.php';

// Public read endpoint (used by kiosk + customer make-order).
// Filters: q (search), category (id), per_page, page.

$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = max(1, min(50, (int)($_GET['per_page'] ?? 20)));
$q        = trim((string)($_GET['q'] ?? ''));
$category = (int)($_GET['category'] ?? 0);
$only_in_stock = !isset($_GET['in_stock']) || $_GET['in_stock'] === '1';
$item_id  = (int)($_GET['id'] ?? 0);

$where  = [];
$params = [];

if ($item_id > 0) {
    $where[]  = 'i.id = ?';
    $params[] = $item_id;
}

if ($q !== '') {
    $where[]  = 'i.item_name LIKE ?';
    $params[] = '%' . $q . '%';
}
if ($category > 0) {
    $where[]  = 'i.category_id = ?';
    $params[] = $category;
}
if ($only_in_stock) {
    $where[] = 'i.stock_count > 0';
}

$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count
$count_sql = "SELECT COUNT(*) FROM inventory i $sql_where";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// Items
$sql = "SELECT i.id, i.item_name, i.price, i.stock_count, i.max_order_qty, i.item_image, c.category_name AS category
        FROM inventory i
        LEFT JOIN item_categories c ON c.id = i.category_id
        $sql_where
        ORDER BY i.item_name ASC
        LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Cast numeric fields and add status
$low_pct = (int)get_setting('low_stock_percent', '10');
foreach ($items as &$it) {
    $it['id']            = (int)$it['id'];
    $it['price']         = (float)$it['price'];
    $it['stock_count']   = (int)$it['stock_count'];
    $it['max_order_qty'] = (int)$it['max_order_qty'];
    if ($it['stock_count'] <= 0) $it['status'] = 'no_stock';
    elseif ($it['max_order_qty'] > 0 && $it['stock_count'] <= max(1, (int)floor($it['max_order_qty'] * $low_pct / 100))) {
        $it['status'] = 'low_stock';
    } else $it['status'] = 'on_stock';
}
unset($it);

json_response([
    'ok'          => true,
    'items'       => $items,
    'page'        => $page,
    'per_page'    => $per_page,
    'total'       => $total,
    'total_pages' => $total_pages,
]);
