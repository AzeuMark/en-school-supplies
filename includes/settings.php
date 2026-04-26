<?php
// Read/write key/value rows in `system_settings`. Values are cached per-request.

require_once __DIR__ . '/config.php';

$GLOBALS['_settings_cache'] = null;

function _settings_load() {
    global $pdo;
    if ($GLOBALS['_settings_cache'] !== null) return $GLOBALS['_settings_cache'];
    $cache = [];
    try {
        $rows = $pdo->query('SELECT setting_key, setting_value FROM system_settings')->fetchAll();
        foreach ($rows as $r) $cache[$r['setting_key']] = $r['setting_value'];
    } catch (Throwable $e) { /* table may not exist yet */ }
    return $GLOBALS['_settings_cache'] = $cache;
}

function get_setting($key, $default = null) {
    $cache = _settings_load();
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

function set_setting($key, $value) {
    global $pdo;
    $stmt = $pdo->prepare(
        'INSERT INTO system_settings (setting_key, setting_value, updated_at)
         VALUES (?, ?, NOW())
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()'
    );
    $stmt->execute([$key, (string)$value]);
    if ($GLOBALS['_settings_cache'] !== null) {
        $GLOBALS['_settings_cache'][$key] = (string)$value;
    }
}

function get_system_status() {
    return get_setting('system_status', 'online'); // online | offline | maintenance
}

function is_force_dark() {
    return get_setting('force_dark', '0') === '1';
}
