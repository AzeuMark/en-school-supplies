<?php
// AES encryption helpers used for user passwords.
// Cipher and key/iv come from config.json.

require_once __DIR__ . '/config.php';

function aes_get_key_iv() {
    $cipher = config('aes.cipher', 'aes-256-cbc');
    $key    = config('aes.key', '');
    $iv     = config('aes.iv', '');
    // Normalize key length for the chosen cipher.
    if (stripos($cipher, '256') !== false) {
        $key = substr(hash('sha256', $key, true), 0, 32);
    } else {
        $key = substr(hash('sha256', $key, true), 0, 16);
    }
    $iv = substr(hash('sha256', $iv, true), 0, 16);
    return [$cipher, $key, $iv];
}

function aes_encrypt($plaintext) {
    [$cipher, $key, $iv] = aes_get_key_iv();
    $encrypted = openssl_encrypt((string)$plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    if ($encrypted === false) return null;
    return base64_encode($encrypted);
}

function aes_decrypt($ciphertext_b64) {
    if ($ciphertext_b64 === null || $ciphertext_b64 === '') return '';
    [$cipher, $key, $iv] = aes_get_key_iv();
    $raw = base64_decode($ciphertext_b64, true);
    if ($raw === false) return '';
    $plain = openssl_decrypt($raw, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    return $plain === false ? '' : $plain;
}
