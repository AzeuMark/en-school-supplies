<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('customer');

$PAGE_TITLE   = 'Order History';
$CURRENT_PAGE = 'order_history';
$PAGE_CSS     = '/customer/order_history/order_history.css';
include __DIR__ . '/../../includes/layout_header.php';
?>
<div class="page-header">
  <h1>Order History</h1>
</div>

<div class="status-tabs" data-status-tabs>
  <button class="active" data-status="">All</button>
  <button data-status="pending">Pending</button>
  <button data-status="ready">Ready</button>
  <button data-status="claimed">Claimed</button>
  <button data-status="cancelled">Cancelled</button>
</div>

<div id="orders-tbl"></div>
<div class="pagination" id="pagination"></div>

<?php $PAGE_JS = '/customer/order_history/order_history.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
