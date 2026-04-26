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

$logoUrl = null;
$logoFile = $_FILES['logo_file'] ?? null;
if ($logoFile && ($logoFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if (($logoFile['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        json_response(['ok' => false, 'error' => 'Logo upload failed.'], 400);
    }
    if (($logoFile['size'] ?? 0) > 2 * 1024 * 1024) {
        json_response(['ok' => false, 'error' => 'Logo must be 2 MB or smaller.'], 400);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($logoFile['tmp_name']);
    if ($mime !== 'image/png') {
        json_response(['ok' => false, 'error' => 'Only PNG logos are allowed.'], 400);
    }

    $uploadDir = APP_ROOT . '/uploads/system';
    if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        json_response(['ok' => false, 'error' => 'Unable to create logo upload folder.'], 500);
    }

    $targetRel = 'uploads/system/logo.png';
    $targetAbs = APP_ROOT . '/' . $targetRel;
    if (!move_uploaded_file($logoFile['tmp_name'], $targetAbs)) {
        json_response(['ok' => false, 'error' => 'Could not save the logo.'], 500);
    }
    $logoUrl = url('/' . $targetRel);
}

// Allowed settings keys
$allowed = [
    'store_name', 'store_phone', 'store_email', 'timezone',
    'navbar_country_flag',
    'auto_logout_hours', 'low_stock_threshold', 'low_stock_percent', 'kiosk_idle_seconds',
    'force_dark', 'system_status', 'disable_no_login_orders', 'online_payment',
];

$settings = $body['settings'] ?? [];
if (!is_array($settings) || empty($settings)) {
    json_response(['ok' => false, 'error' => 'No settings provided.'], 400);
}

$intRules = [
    'auto_logout_hours'  => ['min' => 1, 'max' => 72, 'label' => 'Auto-Logout (hours)'],
    'low_stock_threshold'=> ['min' => 1, 'max' => 100000, 'label' => 'Low Stock Threshold (units)'],
    'low_stock_percent'  => ['min' => 1, 'max' => 100000, 'label' => 'Low Stock Threshold (units)'],
    'kiosk_idle_seconds' => ['min' => 30, 'max' => 600, 'label' => 'Kiosk Idle Timeout (seconds)'],
];

$updated = 0;
foreach ($settings as $key => $value) {
    if (!in_array($key, $allowed, true)) continue;

    if (isset($intRules[$key])) {
        $raw = trim((string)$value);
        if (!preg_match('/^-?\d+$/', $raw)) {
            json_response(['ok' => false, 'error' => $intRules[$key]['label'] . ' must be a whole number.'], 400);
        }
        $intVal = (int)$raw;
        if ($intVal < $intRules[$key]['min'] || $intVal > $intRules[$key]['max']) {
            json_response(['ok' => false, 'error' => sprintf('%s must be between %d and %d.', $intRules[$key]['label'], $intRules[$key]['min'], $intRules[$key]['max'])], 400);
        }
        $value = (string)$intVal;
    }

    if ($key === 'navbar_country_flag') {
        $value = navbar_country_flag_code($value);
    }

    if ($key === 'low_stock_percent') {
        $key = 'low_stock_threshold';
    }

    set_setting($key, (string)$value);
    $updated++;

    if ($key === 'low_stock_threshold') {
        set_setting('low_stock_percent', (string)$value);
    }
}

if ($logoUrl !== null) {
    set_setting('logo_path', 'uploads/system/logo.png');
    $updated++;
}

log_info('System settings updated', ['count' => $updated, 'by' => $_SESSION['user']['id']]);
json_response(['ok' => true, 'message' => "Updated $updated settings.", 'logo_url' => $logoUrl]);
