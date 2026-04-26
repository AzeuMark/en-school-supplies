<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('staff');

$PAGE_TITLE   = 'Pending Accounts';
$CURRENT_PAGE = 'pending_accounts';
$PAGE_CSS     = '/staff/pending_accounts/pending_accounts.css';
include __DIR__ . '/../../includes/layout_header.php';
?>
<div class="page-header">
  <h1>Pending Accounts</h1>
</div>

<div id="pending-tbl"></div>
<div class="pagination" id="pagination"></div>

<?php $PAGE_JS = '/staff/pending_accounts/pending_accounts.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
