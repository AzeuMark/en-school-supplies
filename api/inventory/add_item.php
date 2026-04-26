<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('admin');
require_post();

$token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
csrf_check($token);

$item_name     = sanitize($_POST['item_name'] ?? '');
$category_id   = (int)($_POST['category_id'] ?? 0);
$price         = (float)($_POST['price'] ?? 0);
$stock_count   = (int)($_POST['stock_count'] ?? 0);
$max_order_qty = (int)($_POST['max_order_qty'] ?? 10);

$errors = [];
if ($item_name === '') $errors[] = 'Item name is required.';
if ($price <= 0)       $errors[] = 'Price must be greater than zero.';
if ($stock_count < 0)  $errors[] = 'Stock cannot be negative.';
if ($max_order_qty < 1) $errors[] = 'Max order quantity must be at least 1.';

if ($errors) json_response(['ok' => false, 'error' => implode(' ', $errors)], 400);

// Handle image upload
$image_path = null;
if (!empty($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['item_image'];
    if ($file['size'] > 2 * 1024 * 1024) json_response(['ok' => false, 'error' => 'Image must be 2 MB or smaller.'], 400);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($allowed[$mime])) json_response(['ok' => false, 'error' => 'Only JPG, PNG, or WebP images allowed.'], 400);

    $dir = APP_ROOT . '/uploads/inventory';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $fname = uniqid('item_') . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], "$dir/$fname")) {
        json_response(['ok' => false, 'error' => 'Image upload failed.'], 500);
    }
    $image_path = "uploads/inventory/$fname";
}

$stmt = $pdo->prepare(
    "INSERT INTO inventory (item_name, category_id, price, stock_count, max_order_qty, item_image)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->execute([$item_name, $category_id ?: null, $price, $stock_count, $max_order_qty, $image_path]);
$id = (int)$pdo->lastInsertId();

log_info('Inventory item added', ['item_id' => $id, 'name' => $item_name, 'by' => $_SESSION['user']['id']]);
json_response(['ok' => true, 'message' => 'Item added.', 'id' => $id]);
