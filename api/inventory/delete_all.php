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

$referenced = (int)$pdo->query("SELECT COUNT(DISTINCT item_id) FROM order_items")->fetchColumn();
if ($referenced > 0) {
    json_response([
        'ok' => false,
        'error' => 'Cannot delete all inventory while existing orders still reference inventory items.',
    ], 400);
}

$rows = $pdo->query("SELECT id, item_image FROM inventory")->fetchAll();
if (!$rows) {
    json_response(['ok' => true, 'message' => 'Inventory is already empty.', 'deleted_count' => 0]);
}

$deleted_count = 0;
$image_paths = [];

try {
    $pdo->beginTransaction();
    foreach ($rows as $row) {
        $stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
        $stmt->execute([(int)$row['id']]);
        $deleted_count += $stmt->rowCount();
        if (!empty($row['item_image'])) {
            $image_paths[] = $row['item_image'];
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['ok' => false, 'error' => 'Failed to delete all inventory items.'], 500);
}

foreach ($image_paths as $path) {
    $abs = APP_ROOT . '/' . $path;
    if (is_file($abs)) {
        @unlink($abs);
    }
}

log_info('All inventory deleted', [
    'deleted_count' => $deleted_count,
    'by' => $_SESSION['user']['id'],
]);

json_response([
    'ok' => true,
    'message' => 'All inventory deleted.',
    'deleted_count' => $deleted_count,
]);
