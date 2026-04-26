<?php
// Dual logging: appends to logs/system.log AND inserts into system_logs DB table.

require_once __DIR__ . '/config.php';

function _log_write($level, $message, $context = null) {
    global $pdo;

    // 1) File log
    $line = sprintf(
        "[%s] [%s] [%s] %s%s\n",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $_SERVER['REMOTE_ADDR'] ?? 'cli',
        $message,
        $context ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES) : ''
    );
    $log_dir = APP_ROOT . '/logs';
    if (!is_dir($log_dir)) @mkdir($log_dir, 0775, true);
    @file_put_contents($log_dir . '/system.log', $line, FILE_APPEND);

    // 2) DB log (best-effort; ignored if table missing)
    try {
        if (isset($pdo)) {
            $stmt = $pdo->prepare(
                'INSERT INTO system_logs (level, message, context, user_id, ip_address, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $level,
                $message,
                $context ? json_encode($context, JSON_UNESCAPED_SLASHES) : null,
                $_SESSION['user']['id'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        }
    } catch (Throwable $e) {
        // ignore — file log is enough during early bootstrap
    }
}

function log_info($msg, $ctx = null)    { _log_write('info', $msg, $ctx); }
function log_warning($msg, $ctx = null) { _log_write('warning', $msg, $ctx); }
function log_error($msg, $ctx = null)   { _log_write('error', $msg, $ctx); }
