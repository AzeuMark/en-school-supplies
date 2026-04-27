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

<div class="orders-filters card">
  <div class="orders-filters-head">
    <div>
      <h3>Smart Filters</h3>
      <p>Search, filter, and sort orders quickly.</p>
    </div>
    <button class="btn btn-secondary btn-sm" type="button" data-reset-filters>Reset Filters</button>
  </div>

  <div class="orders-toolbar-grid">
    <label class="orders-control orders-control-search">
      <span>Search</span>
      <div class="search-box"><input class="input" id="search" placeholder="Search orders, customers, items, notes, pins, status..."></div>
    </label>

    <label class="orders-control">
      <span>Order Type</span>
      <select class="select-native" id="filter-order-type" data-custom-select>
        <option value="all">All Orders</option>
        <option value="guest">Guest Orders</option>
        <option value="registered">Registered Orders</option>
      </select>
    </label>

    <label class="orders-control">
      <span>Date Range</span>
      <select class="select-native" id="filter-date" data-custom-select>
        <option value="all">All Time</option>
        <option value="today">Today</option>
        <option value="week">Last 7 Days</option>
        <option value="month">Last 30 Days</option>
      </select>
    </label>

    <label class="orders-control">
      <span>Sort</span>
      <select class="select-native" id="sort-preset" data-custom-select>
        <option value="newest">Newest First</option>
        <option value="oldest">Oldest First</option>
        <option value="total_desc">Total (High to Low)</option>
        <option value="total_asc">Total (Low to High)</option>
        <option value="customer_asc">Customer (A-Z)</option>
        <option value="customer_desc">Customer (Z-A)</option>
        <option value="code_asc">Order Code (A-Z)</option>
        <option value="code_desc">Order Code (Z-A)</option>
      </select>
    </label>
  </div>

  <p class="orders-filter-summary text-muted" id="filter-summary">Showing all orders • Sorted by Newest First</p>
</div>

<div id="orders-tbl"></div>
<div class="pagination" id="pagination"></div>

<?php $PAGE_JS = '/staff/manage_orders/manage_orders.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
