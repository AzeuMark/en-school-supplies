<?php
// CSRF token helpers. Token is stored in session and required on every POST.

require_once __DIR__ . '/config.php';

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES) . '">';
}

function csrf_check($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    }
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!is_string($token) || $expected === '' || !hash_equals($expected, $token)) {
        http_response_code(419);
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) || (($_SERVER['CONTENT_TYPE'] ?? '') === 'application/json')) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token. Please refresh and try again.']);
        } else {
            $_SESSION['flash_error'] = 'Session expired. Please try again.';
            header('Location: /login.php');
        }
        exit;
    }
    return true;
}
