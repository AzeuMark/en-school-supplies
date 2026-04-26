<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/settings.php';

require_role('admin');
require_post();

$raw = file_get_contents('php://input');
$body = $raw ? (json_decode($raw, true) ?: []) : $_POST;
csrf_check($body['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

// Allowed settings keys
$allowed = [
    'store_name', 'store_phone', 'store_email', 'timezone',
    'navbar_country_flag',
    'auto_logout_hours', 'low_stock_percent', 'kiosk_idle_seconds',
    'force_dark', 'system_status', 'disable_no_login_orders', 'online_payment',
];

$settings = $body['settings'] ?? [];
if (!is_array($settings) || empty($settings)) {
    json_response(['ok' => false, 'error' => 'No settings provided.'], 400);
}

$updated = 0;
foreach ($settings as $key => $value) {
    if (!in_array($key, $allowed, true)) continue;
    if ($key === 'navbar_country_flag') {
        $value = navbar_country_flag_code($value);
    }
    set_setting($key, (string)$value);
    $updated++;
}

log_info('System settings updated', ['count' => $updated, 'by' => $_SESSION['user']['id']]);
json_response(['ok' => true, 'message' => "Updated $updated settings."]);
