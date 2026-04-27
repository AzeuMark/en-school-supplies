<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('staff');

$PAGE_TITLE   = 'Pending Users';
$CURRENT_PAGE = 'pending_users';
$PAGE_CSS     = '/staff/pending_users/pending_users.css';
include __DIR__ . '/../../includes/layout_header.php';
?>
<div class="page-header">
  <div class="page-header-main">
    <h1>Pending Users</h1>
    <p class="page-subtitle">Review and approve pending customer accounts.</p>
  </div>
</div>

<div class="pu-filters card">
  <div class="pu-filters-head">
    <div>
      <h3>Filters</h3>
      <p>Search and sort pending customers.</p>
    </div>
    <button class="btn btn-secondary btn-sm" type="button" data-reset-filters>Reset Filters</button>
  </div>
  <div class="pu-toolbar-grid">
    <label class="pu-control pu-control-search">
      <span>Search</span>
      <div class="search-box"><input class="input" id="search" placeholder="Search by username, name, email, or phone..."></div>
    </label>
    <label class="pu-control">
      <span>Sort</span>
      <select class="select-native" id="sort-preset" data-custom-select>
        <option value="newest">Newest First</option>
        <option value="oldest">Oldest First</option>
        <option value="name_asc">Name (A-Z)</option>
        <option value="name_desc">Name (Z-A)</option>
      </select>
    </label>
  </div>
  <p class="pu-filter-summary text-muted" id="filter-summary">Showing all pending customers • Sorted by Newest First</p>
</div>

<div id="users-tbl"></div>
<div class="pagination" id="pagination"></div>

<?php $PAGE_JS = '/staff/pending_users/pending_users.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
