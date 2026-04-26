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
if ($id <= 0) json_response(['ok' => false, 'error' => 'Invalid user.'], 400);

$stmt = $pdo->prepare("UPDATE users SET status='active' WHERE id=? AND status='pending'");
$stmt->execute([$id]);
$affected = $stmt->rowCount();

if ($affected === 0) json_response(['ok' => false, 'error' => 'User is not pending or does not exist.'], 400);

log_info('User approved', ['user_id' => $id, 'by' => $_SESSION['user']['id']]);
json_response(['ok' => true, 'message' => 'User approved.']);
