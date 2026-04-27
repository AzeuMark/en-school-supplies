<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/aes.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('admin');
require_post();

$raw = file_get_contents('php://input');
$body = $raw ? (json_decode($raw, true) ?: []) : $_POST;
csrf_check($body['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

$id        = (int)($body['id'] ?? 0);
$full_name = sanitize($body['full_name'] ?? '');
$username  = normalize_username($body['username'] ?? '');
$email     = strtolower(trim($body['email'] ?? ''));
$phone     = sanitize($body['phone'] ?? '');
$role      = (string)($body['role'] ?? '');
$password  = (string)($body['password'] ?? ''); // optional reset

if ($id <= 0) json_response(['ok' => false, 'error' => 'Invalid user.'], 400);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$target = $stmt->fetch();
if (!$target) json_response(['ok' => false, 'error' => 'User not found.'], 404);

// Cannot demote/edit role of admin
if ($target['role'] === 'admin' && $role !== 'admin') {
    json_response(['ok' => false, 'error' => 'Cannot change admin role.'], 400);
}

$errors = [];
if ($full_name === '') $errors[] = 'Name required.';
if (!valid_username($username)) $errors[] = 'Username is required and must use letters, numbers, or underscores.';
if (!valid_email($email)) $errors[] = 'Valid email required.';
if ($phone === '') $errors[] = 'Phone required.';
if (!in_array($role, ['admin', 'staff', 'customer'], true)) $errors[] = 'Invalid role.';
$stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? AND id <> ?");
$stmt->execute([$username, $id]);
if ($stmt->fetch()) $errors[] = 'Username already in use by another account.';
$stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = ? AND id <> ?");
$stmt->execute([$email, $id]);
if ($stmt->fetch()) $errors[] = 'Email in use by another account.';

if ($errors) json_response(['ok' => false, 'error' => implode(' ', $errors)], 400);

if ($password !== '') {
    $stmt = $pdo->prepare("UPDATE users SET full_name=?, username=?, email=?, phone=?, role=?, password=? WHERE id=?");
    $stmt->execute([$full_name, $username, $email, $phone, $role, aes_encrypt($password), $id]);
} else {
    $stmt = $pdo->prepare("UPDATE users SET full_name=?, username=?, email=?, phone=?, role=? WHERE id=?");
    $stmt->execute([$full_name, $username, $email, $phone, $role, $id]);
}

log_info('User edited', ['user_id' => $id, 'by' => $_SESSION['user']['id']]);
json_response(['ok' => true, 'message' => 'User updated.']);
