<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/aes.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/settings.php';

require_post();
csrf_check();

$email = strtolower(trim($_POST['email'] ?? ''));
$pass  = (string)($_POST['password'] ?? '');
$next  = $_POST['next'] ?? '';
$ip    = get_client_ip();

if ($email === '' || $pass === '' || !valid_email($email)) {
    flash_error('Please enter a valid email and password.');
    redirect(url('/login.php'));
}

// ---- Rate limit: 5 fails in 5 minutes per (ip+email) ----
try {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE email = ? AND ip_address = ? AND success = 0
         AND attempted_at > (NOW() - INTERVAL 5 MINUTE)"
    );
    $stmt->execute([$email, $ip]);
    if ((int)$stmt->fetchColumn() >= 5) {
        log_warning('Login rate-limited', ['email' => $email, 'ip' => $ip]);
        flash_error('Too many failed attempts. Please try again in a few minutes.');
        redirect(url('/login.php'));
    }
} catch (Throwable $e) { /* table may not exist */ }

function record_attempt($email, $ip, $success) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, ?)");
        $stmt->execute([$email, $ip, $success ? 1 : 0]);
    } catch (Throwable $e) { /* ignore */ }
}

// ---- Look up user ----
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    record_attempt($email, $ip, false);
    log_warning('Login failed: unknown email', ['email' => $email]);
    flash_error('Invalid email or password.');
    redirect(url('/login.php'));
}

// ---- Status checks ----
if ($user['status'] === 'flagged') {
    record_attempt($email, $ip, false);
    log_warning('Flagged user login attempt', ['user_id' => $user['id']]);
    $phone = get_setting('store_phone', '');
    flash_error('Your account has been flagged. Please contact us' . ($phone ? " at $phone" : '') . ' or visit the store.');
    redirect(url('/login.php'));
}

if ($user['status'] === 'pending') {
    record_attempt($email, $ip, false);
    flash_error('Your account is awaiting approval. Please check back later.');
    redirect(url('/login.php'));
}

// System status check (only admins allowed when offline)
$sys_status = get_system_status();
if ($sys_status === 'offline' && $user['role'] !== 'admin') {
    record_attempt($email, $ip, false);
    flash_error('The system is currently offline. Please try again later.');
    redirect(url('/login.php'));
}

// ---- Verify password (AES-decrypt and compare) ----
$decrypted = aes_decrypt($user['password']);
if (!hash_equals($decrypted, $pass)) {
    record_attempt($email, $ip, false);
    log_warning('Login failed: wrong password', ['user_id' => $user['id']]);
    flash_error('Invalid email or password.');
    redirect(url('/login.php'));
}

// ---- Success ----
record_attempt($email, $ip, true);
login_user($user);
log_info('User logged in', ['user_id' => $user['id'], 'role' => $user['role']]);

// Open a staff/admin session row
if (in_array($user['role'], ['admin', 'staff'], true)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO staff_sessions (user_id, login_time) VALUES (?, NOW())");
        $stmt->execute([$user['id']]);
    } catch (Throwable $e) { /* ignore */ }
}

// Redirect to next or role dashboard
$role = $user['role'];
$dest = url("/{$role}/dashboard/dashboard.php");
if ($next && preg_match('#^/[a-z0-9_/.-]+$#i', $next)) $dest = url($next);
redirect($dest);
