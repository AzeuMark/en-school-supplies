<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('staff');
require_post();

$raw = file_get_contents('php://input');
$body = $raw ? (json_decode($raw, true) ?: []) : $_POST;
csrf_check($body['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

$id = (int)($body['id'] ?? 0);
if ($id <= 0) json_response(['ok' => false, 'error' => 'Invalid user.'], 400);

$stmt = $pdo->prepare("SELECT role, status, profile_image FROM users WHERE id = ?");
$stmt->execute([$id]);
$target = $stmt->fetch();
if (!$target) json_response(['ok' => false, 'error' => 'User not found.'], 404);
if ($target['role'] !== 'customer') json_response(['ok' => false, 'error' => 'Staff can only delete customer accounts.'], 403);
if ($target['status'] !== 'pending') json_response(['ok' => false, 'error' => 'Staff can only delete pending accounts.'], 403);

if (!empty($target['profile_image'])) {
    $p = APP_ROOT . '/' . ltrim($target['profile_image'], '/');
    if (file_exists($p)) @unlink($p);
}

$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$id]);

log_info('Pending user deleted by staff', ['user_id' => $id, 'by' => $_SESSION['user']['id']]);
json_response(['ok' => true, 'message' => 'User deleted.']);
