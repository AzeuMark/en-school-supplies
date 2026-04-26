<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_role('customer');

$PAGE_TITLE   = 'My Profile';
$CURRENT_PAGE = 'profile';
$PAGE_CSS     = '/customer/profile/profile.css';
include __DIR__ . '/../../includes/layout_header.php';
include __DIR__ . '/../../includes/profile_content.php';
include __DIR__ . '/../../includes/layout_footer.php';
