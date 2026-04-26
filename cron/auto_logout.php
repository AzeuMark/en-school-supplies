<?php
/**
 * Cron: Auto-logout stale staff sessions.
 * Run periodically (e.g. every 15 minutes) via cron or Task Scheduler:
 *   php /path/to/cron/auto_logout.php
 *
 * Closes any open staff_sessions whose login_time is older than
 * the configured auto_logout_hours, and logs the action.
 */

// Boot config (starts session, connects DB)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/logger.php';

$hours = (int)get_setting('auto_logout_hours', (string)(config('system.auto_logout_hours') ?? 8));
if ($hours < 1) $hours = 8;

$cutoff = date('Y-m-d H:i:s', strtotime("-{$hours} hours"));

// Find open sessions older than cutoff
$stmt = $pdo->prepare(
    "SELECT id, user_id, login_time FROM staff_sessions
     WHERE logout_time IS NULL AND login_time < ?"
);
$stmt->execute([$cutoff]);
$stale = $stmt->fetchAll();

if (empty($stale)) {
    echo "No stale sessions.\n";
    exit(0);
}

$update = $pdo->prepare(
    "UPDATE staff_sessions
     SET logout_time = NOW(),
         logout_type = 'auto_system',
         duration_minutes = TIMESTAMPDIFF(MINUTE, login_time, NOW()),
         is_suspicious = 1
     WHERE id = ? AND logout_time IS NULL"
);

$count = 0;
foreach ($stale as $s) {
    $update->execute([$s['id']]);
    log_warning('Cron auto-logged out stale staff session', [
        'session_id' => (int)$s['id'],
        'user_id'    => (int)$s['user_id'],
        'login_time' => $s['login_time'],
        'cutoff'     => $cutoff,
    ]);
    $count++;
}

log_info('Cron: auto-logout closed stale sessions', ['count' => $count, 'cutoff' => $cutoff]);
echo "Closed {$count} stale session(s).\n";
