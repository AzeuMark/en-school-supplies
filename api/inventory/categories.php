<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/helpers.php';

// GET: list categories
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $pdo->query("SELECT id, category_name FROM item_categories ORDER BY category_name")->fetchAll();
    json_response(['ok' => true, 'categories' => $rows]);
}

// POST: add / edit / delete category
require_role('admin');
require_post();

$raw = file_get_contents('php://input');
$body = $raw ? (json_decode($raw, true) ?: []) : $_POST;
csrf_check($body['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

$action = (string)($body['action'] ?? '');
$id     = (int)($body['id'] ?? 0);
$name   = sanitize($body['category_name'] ?? '');

if ($action === 'add') {
    if ($name === '') json_response(['ok' => false, 'error' => 'Category name required.'], 400);
    $stmt = $pdo->prepare("INSERT INTO item_categories (category_name) VALUES (?)");
    $stmt->execute([$name]);
    json_response(['ok' => true, 'message' => 'Category added.', 'id' => (int)$pdo->lastInsertId()]);
}
if ($action === 'edit') {
    if ($id <= 0 || $name === '') json_response(['ok' => false, 'error' => 'Invalid.'], 400);
    $stmt = $pdo->prepare("UPDATE item_categories SET category_name = ? WHERE id = ?");
    $stmt->execute([$name, $id]);
    json_response(['ok' => true, 'message' => 'Category updated.']);
}
if ($action === 'delete') {
    if ($id <= 0) json_response(['ok' => false, 'error' => 'Invalid.'], 400);
    $pdo->prepare("UPDATE inventory SET category_id = NULL WHERE category_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM item_categories WHERE id = ?")->execute([$id]);
    json_response(['ok' => true, 'message' => 'Category deleted.']);
}

json_response(['ok' => false, 'error' => 'Invalid action.'], 400);
