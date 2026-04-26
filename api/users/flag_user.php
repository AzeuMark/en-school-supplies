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

$id     = (int)($body['id'] ?? 0);
$reason = sanitize($body['reason'] ?? '');

if ($id <= 0) json_response(['ok' => false, 'error' => 'Invalid user.'], 400);
if ($reason === '') json_response(['ok' => false, 'error' => 'Flag reason is required.'], 400);
if ($id === (int)$_SESSION['user']['id']) json_response(['ok' => false, 'error' => 'You cannot flag your own account.'], 400);

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$id]);
$target = $stmt->fetch();
if (!$target) json_response(['ok' => false, 'error' => 'User not found.'], 404);
if ($target['role'] === 'admin') json_response(['ok' => false, 'error' => 'Admin accounts cannot be flagged.'], 400);

$stmt = $pdo->prepare("UPDATE users SET status = 'flagged', flag_reason = ? WHERE id = ?");
$stmt->execute([$reason, $id]);

log_warning('User flagged', ['user_id' => $id, 'reason' => $reason, 'by' => $_SESSION['user']['id']]);
json_response(['ok' => true, 'message' => 'User has been flagged.']);
