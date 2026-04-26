<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('admin');

$PAGE_TITLE   = 'Flagged Users';
$CURRENT_PAGE = 'flagged_users';
$PAGE_CSS     = '/admin/flagged_users/flagged_users.css';
include __DIR__ . '/../../includes/layout_header.php';
?>
<div class="page-header">
  <h1>Flagged Users</h1>
</div>

<div id="flagged-tbl"></div>
<div class="pagination" id="pagination"></div>

<?php $PAGE_JS = '/admin/flagged_users/flagged_users.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
