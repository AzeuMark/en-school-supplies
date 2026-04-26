<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

if (!is_logged_in()) {
    json_response(['ok' => false, 'error' => 'Not logged in'], 401);
}

$counts = get_badge_counts();
json_response(['ok' => true, 'counts' => $counts]);
