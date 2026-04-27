<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/aes.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (!is_logged_in()) json_response(['ok' => false, 'error' => 'Not logged in'], 401);

// Accept JSON or form-data
$body = $_POST;
if (empty($body)) {
    $raw = file_get_contents('php://input');
    if ($raw) $body = json_decode($raw, true) ?: [];
}
$token = $body['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
csrf_check($token);

$uid = (int)$_SESSION['user']['id'];

// Quick path: theme-only update (used by theme.js)
if (isset($body['theme_preference']) && count($body) <= 2) {
    $theme = in_array($body['theme_preference'], ['light', 'dark', 'auto'], true) ? $body['theme_preference'] : 'auto';
    $stmt = $pdo->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
    $stmt->execute([$theme, $uid]);
    $_SESSION['user']['theme'] = $theme;
    json_response(['ok' => true]);
}

// Full profile update
$full_name = sanitize($body['full_name'] ?? '');
$username  = normalize_username($body['username'] ?? '');
$email     = strtolower(trim($body['email'] ?? ''));
$phone     = sanitize($body['phone'] ?? '');
$cur_pass  = (string)($body['current_password'] ?? '');
$new_pass  = (string)($body['new_password'] ?? '');
$new_pass2 = (string)($body['new_password2'] ?? '');

$errors = [];
if ($full_name === '' || mb_strlen($full_name) > 150) $errors[] = 'Full name is required.';
if (!valid_username($username))                     $errors[] = 'Username must be 3-50 characters and use letters, numbers, or underscores.';
if (!valid_email($email))                              $errors[] = 'Valid email required.';
if ($phone === '')                                     $errors[] = 'Phone is required.';

// Email uniqueness check
$stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = ? AND id <> ? LIMIT 1");
$stmt->execute([$email, $uid]);
if ($stmt->fetch()) $errors[] = 'That email is already in use.';

$stmt = $pdo->prepare("SELECT 1 FROM users WHERE username = ? AND id <> ? LIMIT 1");
$stmt->execute([$username, $uid]);
if ($stmt->fetch()) $errors[] = 'That username is already in use.';

// Password change validation
$change_pass = $new_pass !== '' || $new_pass2 !== '' || $cur_pass !== '';
if ($change_pass) {
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $cur_db = $stmt->fetchColumn();
    if (!hash_equals(aes_decrypt($cur_db), $cur_pass)) $errors[] = 'Current password is incorrect.';
    if (strlen($new_pass) < 4) $errors[] = 'New password must be at least 4 characters.';
    if (!preg_match('/[A-Za-z]/', $new_pass) || !preg_match('/[0-9]/', $new_pass)) $errors[] = 'New password must contain letters and numbers.';
    if ($new_pass !== $new_pass2) $errors[] = 'New passwords do not match.';
}

if ($errors) json_response(['ok' => false, 'error' => implode(' ', $errors)], 400);

if ($change_pass) {
    $stmt = $pdo->prepare("UPDATE users SET full_name=?, username=?, email=?, phone=?, password=? WHERE id=?");
    $stmt->execute([$full_name, $username, $email, $phone, aes_encrypt($new_pass), $uid]);
} else {
    $stmt = $pdo->prepare("UPDATE users SET full_name=?, username=?, email=?, phone=? WHERE id=?");
    $stmt->execute([$full_name, $username, $email, $phone, $uid]);
}

$_SESSION['user']['full_name'] = $full_name;
$_SESSION['user']['username']  = $username;
$_SESSION['user']['email']     = $email;

log_info('Profile updated', ['user_id' => $uid, 'pass_changed' => $change_pass]);
json_response(['ok' => true, 'message' => 'Profile updated.']);
