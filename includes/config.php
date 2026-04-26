<?php
// Loads config.json and creates a global PDO connection ($pdo).
// Also bootstraps timezone, error handling, and the session.

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

// Auto-detect base path for subdirectory installations.
// E.g. if project lives at htdocs/en-school-supplies-a/, BASE_PATH = '/en-school-supplies-a'
if (!defined('BASE_PATH')) {
    $_docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $_appRoot = rtrim(str_replace('\\', '/', APP_ROOT), '/');
    if ($_docRoot && stripos($_appRoot, $_docRoot) === 0) {
        define('BASE_PATH', substr($_appRoot, strlen($_docRoot)));
    } else {
        define('BASE_PATH', '');
    }
    unset($_docRoot, $_appRoot);
}

// ---- Load config.json ----
$config_path = APP_ROOT . '/config.json';
if (!file_exists($config_path)) {
    http_response_code(500);
    die('Missing config.json. Run setup or copy config.example.json.');
}
$CONFIG = json_decode(file_get_contents($config_path), true);
if (!is_array($CONFIG)) {
    http_response_code(500);
    die('Invalid config.json.');
}

// ---- Timezone ----
date_default_timezone_set($CONFIG['system']['timezone'] ?? 'Asia/Manila');

// ---- Error handling ----
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/logs/php_errors.log');
error_reporting(E_ALL);

// ---- Session (must be before any output) ----
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ---- PDO connection ----
$db = $CONFIG['database'];
$dsn = "mysql:host={$db['host']};charset={$db['charset']}";
try {
    $pdo = new PDO($dsn, $db['user'], $db['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    // Select database (may not exist yet during setup.php)
    if (!empty($db['name'])) {
        try {
            $pdo->exec("USE `{$db['name']}`");
        } catch (PDOException $e) {
            // Database not created yet; setup.php handles this case.
        }
    }
} catch (PDOException $e) {
    http_response_code(500);
    error_log('DB connect failed: ' . $e->getMessage());
    die('Database connection failed. Check config.json and that MySQL is running.');
}

// ---- Helper accessor for config values ----
function config($path, $default = null) {
    global $CONFIG;
    $parts = explode('.', $path);
    $cur = $CONFIG;
    foreach ($parts as $p) {
        if (!is_array($cur) || !array_key_exists($p, $cur)) return $default;
        $cur = $cur[$p];
    }
    return $cur;
}
