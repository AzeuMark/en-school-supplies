<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/logger.php';

require_post();
csrf_check();

if (is_logged_in()) {
    $uid = (int)$_SESSION['user']['id'];
    $role = $_SESSION['user']['role'];
    // Close any open staff session row
    if (in_array($role, ['admin', 'staff'], true)) {
        try {
            $stmt = $pdo->prepare(
                "UPDATE staff_sessions
                 SET logout_time = NOW(),
                     logout_type = 'manual',
                     duration_minutes = TIMESTAMPDIFF(MINUTE, login_time, NOW())
                 WHERE user_id = ? AND logout_time IS NULL
                 ORDER BY login_time DESC LIMIT 1"
            );
            $stmt->execute([$uid]);
        } catch (Throwable $e) { /* ignore */ }
    }
    log_info('User logged out', ['user_id' => $uid, 'role' => $role]);
}

logout_user();
flash_success('You have been logged out.');
redirect(url('/login.php'));
