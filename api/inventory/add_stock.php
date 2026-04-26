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
$amount = (int)($body['amount'] ?? 0);

if ($id <= 0) json_response(['ok' => false, 'error' => 'Invalid item.'], 400);
if ($amount <= 0) json_response(['ok' => false, 'error' => 'Stock to add must be at least 1.'], 400);

$stmt = $pdo->prepare("UPDATE inventory SET stock_count = stock_count + ? WHERE id = ?");
$stmt->execute([$amount, $id]);

if ($stmt->rowCount() < 1) {
    json_response(['ok' => false, 'error' => 'Item not found.'], 404);
}

$stmt = $pdo->prepare("SELECT stock_count FROM inventory WHERE id = ?");
$stmt->execute([$id]);
$new_stock = (int)$stmt->fetchColumn();

log_info('Inventory stock increased', [
    'item_id' => $id,
    'added_stock' => $amount,
    'new_stock' => $new_stock,
    'by' => $_SESSION['user']['id'],
]);

json_response([
    'ok' => true,
    'message' => 'Stock updated.',
    'stock_count' => $new_stock,
]);
