<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/aes.php'; //
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_post();
csrf_check();

$full_name = sanitize($_POST['full_name'] ?? '');
$username  = normalize_username($_POST['username'] ?? '');
$email     = strtolower(trim($_POST['email'] ?? ''));
$phone     = sanitize($_POST['phone'] ?? '');
$pass      = (string)($_POST['password'] ?? '');
$pass2     = (string)($_POST['password2'] ?? '');

$field_errors = [];
if ($full_name === '' || mb_strlen($full_name) > 150) $field_errors['full_name'] = 'Full name is required.';
if (!valid_username($username))                      $field_errors['username']  = 'Must be 3-50 chars, letters/numbers/underscores.';
if (!valid_email($email))                            $field_errors['email']     = 'A valid email is required.';
if ($phone === '')                                   $field_errors['phone']     = 'Phone is required.';
elseif (!preg_match('/^[0-9+\-\s().]{7,20}$/', $phone)) $field_errors['phone'] = 'Use 7–20 digits, spaces, +, -, (, or ).';
if ($pass !== $pass2)                                $field_errors['password2'] = 'Passwords do not match.';

// Email/username uniqueness
if (!$field_errors) {
    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) $field_errors['email'] = 'That email is already registered.';

    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    if ($stmt->fetch()) $field_errors['username'] = 'That username is already taken.';
}

if ($field_errors) {
    $_SESSION['flash_reg_error']    = implode(' ', $field_errors);
    $_SESSION['flash_field_errors'] = $field_errors;
    $_SESSION['flash_old'] = compact('full_name', 'username', 'email', 'phone');
    redirect(url('/login.php?tab=register'));
}

$stmt = $pdo->prepare(
    "INSERT INTO users (full_name, username, email, phone, password, role, status, theme_preference)
     VALUES (?, ?, ?, ?, ?, 'customer', 'pending', 'auto')"
);
$stmt->execute([$full_name, $username, $email, $phone, aes_encrypt($pass)]);
$new_id = (int)$pdo->lastInsertId();

log_info('Customer registered (pending)', ['user_id' => $new_id, 'email' => $email]);
flash_success('Account created! Please wait for staff approval before logging in.');
redirect(url('/login.php'));
