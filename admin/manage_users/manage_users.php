<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('admin');

$current_admin_id = (int)$_SESSION['user']['id'];

$PAGE_TITLE   = 'Manage Users';
$CURRENT_PAGE = 'manage_users';
$PAGE_CSS     = '/admin/manage_users/manage_users.css';
include __DIR__ . '/../../includes/layout_header.php';
?>
<div class="page-header">
  <div class="page-header-main">
    <h1>Manage Users</h1>
    <p class="page-subtitle">Review users, approve pending accounts, and manage flagged accounts.</p>
  </div>
  <button class="btn" data-add-user>+ Add User</button>
</div>

<div class="status-tabs" data-status-tabs>
  <button class="active" data-status="">All</button>
  <button data-status="active">Active</button>
  <button data-status="pending">Pending</button>
  <button data-status="flagged">Flagged</button>
</div>

<div class="users-filters card">
  <div class="users-filters-head">
    <div>
      <h3>Smart Filters</h3>
      <p>Search, filter, and sort users quickly.</p>
    </div>
    <button class="btn btn-secondary btn-sm" type="button" data-reset-filters>Reset Filters</button>
  </div>

  <div class="users-toolbar-grid">
    <label class="users-control users-control-search">
      <span>Search</span>
      <div class="search-box"><input class="input" id="search" placeholder="Search by username, name, email, or phone..."></div>
    </label>

    <label class="users-control">
      <span>Role</span>
      <select class="select-native" id="filter-role" data-custom-select>
        <option value="all">All Roles</option>
        <option value="admin">Admin</option>
        <option value="staff">Staff</option>
        <option value="customer">Customer</option>
      </select>
    </label>

    <label class="users-control">
      <span>Date Range</span>
      <select class="select-native" id="filter-date" data-custom-select>
        <option value="all">All Time</option>
        <option value="today">Today</option>
        <option value="week">Last 7 Days</option>
        <option value="month">Last 30 Days</option>
      </select>
    </label>

    <label class="users-control">
      <span>Sort</span>
      <select class="select-native" id="sort-preset" data-custom-select>
        <option value="newest">Newest First</option>
        <option value="oldest">Oldest First</option>
        <option value="name_asc">Name (A-Z)</option>
        <option value="name_desc">Name (Z-A)</option>
        <option value="role_asc">Role (A-Z)</option>
        <option value="role_desc">Role (Z-A)</option>
      </select>
    </label>
  </div>

  <p class="users-filter-summary text-muted" id="filter-summary">Showing all users • Sorted by Newest First</p>
</div>

<div id="users-tbl"></div>
<div class="pagination" id="pagination"></div>

<script>window.__CURRENT_ADMIN_ID = <?= (int)$current_admin_id ?>;</script>
<?php $PAGE_JS = '/admin/manage_users/manage_users.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
