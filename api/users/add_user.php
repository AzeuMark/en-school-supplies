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

$full_name = sanitize($body['full_name'] ?? '');
$username  = normalize_username($body['username'] ?? '');
$email     = strtolower(trim($body['email'] ?? ''));
$phone     = sanitize($body['phone'] ?? '');
$password  = (string)($body['password'] ?? '');
$role      = (string)($body['role'] ?? '');

$errors = [];
if ($full_name === '') $errors[] = 'Name is required.';
if (!valid_username($username)) $errors[] = 'Username is required and must use letters, numbers, or underscores.';
if (!valid_email($email)) $errors[] = 'Valid email required.';
if ($phone === '') $errors[] = 'Phone required.';
if (!in_array($role, ['staff', 'customer'], true)) $errors[] = 'Role must be staff or customer.';

$stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) $errors[] = 'Username already in use.';

$stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) $errors[] = 'Email already in use.';

if ($errors) json_response(['ok' => false, 'error' => implode(' ', $errors)], 400);

$stmt = $pdo->prepare(
    "INSERT INTO users (full_name, username, email, phone, password, role, status, theme_preference, created_by)
     VALUES (?, ?, ?, ?, ?, ?, 'active', 'auto', ?)"
);
$stmt->execute([$full_name, $username, $email, $phone, aes_encrypt($password), $role, (int)$_SESSION['user']['id']]);
$id = (int)$pdo->lastInsertId();

log_info('User created', ['user_id' => $id, 'role' => $role, 'by' => $_SESSION['user']['id']]);
json_response(['ok' => true, 'message' => 'User created.', 'id' => $id]);
