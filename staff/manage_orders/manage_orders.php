<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('staff');

$PAGE_TITLE   = 'Manage Orders';
$CURRENT_PAGE = 'manage_orders';
$PAGE_CSS     = '/staff/manage_orders/manage_orders.css';
include __DIR__ . '/../../includes/layout_header.php';
?>
<div class="page-header">
  <h1>Manage Orders</h1>
</div>

<div class="status-tabs" data-status-tabs>
  <button class="active" data-status="">All</button>
  <button data-status="pending">Pending</button>
  <button data-status="ready">Ready</button>
  <button data-status="claimed">Claimed</button>
  <button data-status="cancelled">Cancelled</button>
</div>

<div class="toolbar">
  <div class="search-box"><input class="input" id="search" placeholder="Search by order code or name..."></div>
</div>

<div id="orders-tbl"></div>
<div class="pagination" id="pagination"></div>

<?php $PAGE_JS = '/staff/manage_orders/manage_orders.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
