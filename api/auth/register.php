<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/aes.php';
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

$errors = [];
if ($full_name === '' || mb_strlen($full_name) > 150) $errors[] = 'Full name is required.';
if (!valid_username($username))                     $errors[] = 'Username must be 3-50 characters and use letters, numbers, or underscores.';
if (!valid_email($email))                              $errors[] = 'A valid email is required.';
if ($phone === '' || mb_strlen($phone) > 20)           $errors[] = 'Phone is required.';
if ($pass !== $pass2)                                  $errors[] = 'Passwords do not match.';

// Email uniqueness
if (!$errors) {
    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) $errors[] = 'That email is already registered.';

    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    if ($stmt->fetch()) $errors[] = 'That username is already taken.';
}

if ($errors) {
    flash_error(implode(' ', $errors));
    $_SESSION['flash_old'] = compact('full_name', 'username', 'email', 'phone');
    redirect(url('/register.php'));
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
