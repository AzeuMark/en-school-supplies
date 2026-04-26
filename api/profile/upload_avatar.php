<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/logger.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (!is_logged_in()) json_response(['ok' => false, 'error' => 'Not logged in'], 401);

require_post();
csrf_check();

if (empty($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    json_response(['ok' => false, 'error' => 'No file uploaded.'], 400);
}

$file = $_FILES['avatar'];
if ($file['size'] > 1024 * 1024) {
    json_response(['ok' => false, 'error' => 'File must be 1 MB or smaller.'], 400);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if (!isset($allowed[$mime])) {
    json_response(['ok' => false, 'error' => 'Only JPG, PNG, or WebP images are allowed.'], 400);
}

$user = get_current_user_data();
$uid  = (int)$user['id'];
$role = $user['role'];
$ext  = $allowed[$mime];

$dir = APP_ROOT . "/uploads/{$role}/profiles";
if (!is_dir($dir)) @mkdir($dir, 0775, true);

// Delete old image (any extension)
foreach (['jpg', 'png', 'webp'] as $oldExt) {
    $old = "$dir/{$uid}.$oldExt";
    if (file_exists($old)) @unlink($old);
}

$dest = "$dir/{$uid}.$ext";
if (!move_uploaded_file($file['tmp_name'], $dest)) {
    log_error('Avatar upload failed', ['user_id' => $uid]);
    json_response(['ok' => false, 'error' => 'Upload failed. Please try again.'], 500);
}

$rel = "uploads/{$role}/profiles/{$uid}.{$ext}";
$stmt = $pdo->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
$stmt->execute([$rel, $uid]);
$_SESSION['user']['avatar'] = $rel;

log_info('Avatar updated', ['user_id' => $uid]);
json_response(['ok' => true, 'url' => '/' . $rel]);
