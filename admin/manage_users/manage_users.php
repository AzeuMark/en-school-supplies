<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('admin');

$PAGE_TITLE   = 'Manage Users';
$CURRENT_PAGE = 'manage_users';
$PAGE_CSS     = '/admin/manage_users/manage_users.css';
include __DIR__ . '/../../includes/layout_header.php';
?>
<div class="page-header">
  <h1>Manage Users</h1>
  <button class="btn" data-add-user>+ Add User</button>
</div>

<div class="toolbar">
  <div class="search-box"><input class="input" id="search" placeholder="Search by username, name, email, or phone..."></div>
  <select class="select-native" id="filter-role" data-custom-select>
    <option value="">All Roles</option>
    <option value="admin">Admin</option>
    <option value="staff">Staff</option>
    <option value="customer">Customer</option>
  </select>
  <select class="select-native" id="filter-status" data-custom-select>
    <option value="">All Statuses</option>
    <option value="active">Active</option>
    <option value="pending">Pending</option>
    <option value="flagged">Flagged</option>
  </select>
</div>

<div id="users-tbl"></div>
<div class="pagination" id="pagination"></div>

<?php $PAGE_JS = '/admin/manage_users/manage_users.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
