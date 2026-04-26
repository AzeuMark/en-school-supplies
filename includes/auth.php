<?php
// Authentication, role guards, and system-status enforcement.

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/helpers.php';

function is_logged_in() {
    return !empty($_SESSION['user']['id']);
}

function get_current_user_data() {
    return $_SESSION['user'] ?? null;
}

function current_role() {
    return $_SESSION['user']['role'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please log in to continue.';
        redirect(url('/login.php'));
    }
    enforce_system_status();
}

function require_role(...$roles) {
    require_login();
    $role = current_role();
    if (!in_array($role, $roles, true)) {
        http_response_code(403);
        include APP_ROOT . '/403.php';
        exit;
    }
}

function login_user(array $user) {
    // Regenerate session ID to prevent fixation.
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'        => (int)$user['id'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'role'      => $user['role'],
        'avatar'    => $user['profile_image'] ?? null,
        'theme'     => $user['theme_preference'] ?? 'auto',
    ];
    // Generate fresh CSRF token bound to the new session.
    unset($_SESSION['csrf_token']);
}

function logout_user() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function enforce_system_status() {
    $status = get_system_status();
    $role = current_role();
    if ($status === 'online' || $role === 'admin') return;
    if ($status === 'offline') {
        // Non-admins blocked entirely.
        logout_user();
        $_SESSION['flash_error'] = 'The system is currently offline. Only administrators may sign in.';
        redirect(url('/login.php'));
    }
    // Maintenance: customers view-only, staff orders disabled — flagged via session and enforced in pages/APIs.
    $_SESSION['system_maintenance'] = true;
}

// Sidebar badge counts.
function get_badge_counts() {
    global $pdo;
    $role = current_role();
    $uid  = (int)($_SESSION['user']['id'] ?? 0);
    $out  = ['pending_orders' => 0, 'pending_accounts' => 0, 'active_orders' => 0];
    try {
        if (in_array($role, ['admin', 'staff'], true)) {
            $out['pending_orders']   = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetchColumn();
            $out['pending_accounts'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn();
        }
        if ($role === 'customer') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('pending','ready')");
            $stmt->execute([$uid]);
            $out['active_orders'] = (int)$stmt->fetchColumn();
        }
    } catch (Throwable $e) { /* tables may not exist during setup */ }
    return $out;
}
