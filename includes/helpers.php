<?php
// Common utilities: input sanitization, formatting, code/PIN generation.

require_once __DIR__ . '/config.php';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function sanitize($value) {
    if (is_array($value)) return array_map('sanitize', $value);
    return trim(strip_tags((string)$value));
}

function format_price($amount) {
    return '₱' . number_format((float)$amount, 2, '.', ',');
}

function generate_order_code(PDO $pdo) {
    // Format: ORD-00042 (zero-padded next id). Loops on rare collisions.
    for ($i = 0; $i < 5; $i++) {
        $row = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM orders")->fetch();
        $code = 'ORD-' . str_pad((string)$row['next_id'], 5, '0', STR_PAD_LEFT);
        $check = $pdo->prepare('SELECT 1 FROM orders WHERE order_code = ? LIMIT 1');
        $check->execute([$code]);
        if (!$check->fetch()) return $code;
    }
    // Fallback: random suffix
    return 'ORD-' . strtoupper(bin2hex(random_bytes(3)));
}

function generate_claim_pin() {
    return str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}

function normalize_claim_pin_input($value) {
    $pin = strtoupper(trim((string)$value));
    if (strpos($pin, 'GST-') === 0) {
        $pin = substr($pin, 4);
    }
    return preg_match('/^\d{4}$/', $pin) ? $pin : '';
}

function format_claim_pin_display($pin, $is_guest) {
    $normalized = normalize_claim_pin_input($pin);
    if ($normalized === '') return '';
    return $is_guest ? ('GST-' . $normalized) : $normalized;
}

function normalize_username($username) {
    return strtolower(trim((string)$username));
}

function valid_username($username) {
    $username = normalize_username($username);
    return $username !== '' && preg_match('/^[a-z][a-z0-9_]{2,49}$/', $username) === 1;
}

function normalize_login_identifier($identifier) {
    return strtolower(trim((string)$identifier));
}

function navbar_country_flag_options() {
    return [
        'Asia' => [
            'PH' => 'Philippines',
            'SG' => 'Singapore',
            'JP' => 'Japan',
            'KR' => 'South Korea',
            'CN' => 'China',
            'IN' => 'India',
        ],
        'Americas' => [
            'US' => 'United States',
            'CA' => 'Canada',
            'MX' => 'Mexico',
            'BR' => 'Brazil',
        ],
        'Europe' => [
            'GB' => 'United Kingdom',
            'DE' => 'Germany',
            'FR' => 'France',
            'ES' => 'Spain',
            'IT' => 'Italy',
        ],
        'Oceania' => [
            'AU' => 'Australia',
            'NZ' => 'New Zealand',
        ],
        'Middle East & Africa' => [
            'AE' => 'United Arab Emirates',
            'SA' => 'Saudi Arabia',
            'ZA' => 'South Africa',
        ],
    ];
}

function navbar_country_flag_map() {
    static $map = null;
    if ($map !== null) return $map;

    $map = [];
    foreach (navbar_country_flag_options() as $group) {
        foreach ($group as $code => $label) {
            $map[strtoupper($code)] = $label;
        }
    }
    return $map;
}

function navbar_country_flag_code($code) {
    $code = strtoupper(preg_replace('/[^A-Z]/', '', (string)$code));
    return strlen($code) === 2 ? $code : 'PH';
}

function navbar_country_flag_label($code) {
    $map = navbar_country_flag_map();
    $code = navbar_country_flag_code($code);
    return $map[$code] ?? $map['PH'];
}

function navbar_country_flag_emoji($code) {
    $code = navbar_country_flag_code($code);
    $base = 127397;
    return html_entity_decode('&#' . ($base + ord($code[0])) . ';', ENT_NOQUOTES, 'UTF-8')
        . html_entity_decode('&#' . ($base + ord($code[1])) . ';', ENT_NOQUOTES, 'UTF-8');
}

function redirect($path) {
    header('Location: ' . $path);
    exit;
}

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function flash_error($msg) { $_SESSION['flash_error'] = $msg; }
function flash_success($msg) { $_SESSION['flash_success'] = $msg; }
function flash_pop($key) {
    if (!isset($_SESSION[$key])) return null;
    $v = $_SESSION[$key];
    unset($_SESSION[$key]);
    return $v;
}

function is_post() {
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function require_post() {
    if (!is_post()) {
        http_response_code(405);
        die('Method Not Allowed');
    }
}

function get_client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Returns asset URL relative to web root.
function asset($path) {
    return '/' . ltrim($path, '/');
}

function url($path) {
    return BASE_PATH . $path;
}
