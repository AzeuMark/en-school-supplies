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

$id = (int)($body['id'] ?? 0);
if ($id <= 0) json_response(['ok' => false, 'error' => 'Invalid item.'], 400);

$stmt = $pdo->prepare("SELECT item_image FROM inventory WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) json_response(['ok' => false, 'error' => 'Item not found.'], 404);

// Delete image file
if ($item['item_image'] && file_exists(APP_ROOT . '/' . $item['item_image'])) {
    @unlink(APP_ROOT . '/' . $item['item_image']);
}

$stmt = $pdo->prepare("DELETE FROM inventory WHERE id = ?");
$stmt->execute([$id]);

log_info('Inventory item deleted', ['item_id' => $id, 'by' => $_SESSION['user']['id']]);
json_response(['ok' => true, 'message' => 'Item deleted.']);
