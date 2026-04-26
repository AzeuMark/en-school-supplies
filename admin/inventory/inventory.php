<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('admin');

$categories = $pdo->query("SELECT id, category_name FROM item_categories ORDER BY category_name")->fetchAll();
$default_names = $pdo->query("SELECT item_name FROM default_item_names ORDER BY item_name")->fetchAll(PDO::FETCH_COLUMN);

$PAGE_TITLE   = 'Inventory';
$CURRENT_PAGE = 'inventory';
$PAGE_CSS     = '/admin/inventory/inventory.css';
include __DIR__ . '/../../includes/layout_header.php';
?>
<div class="page-header inventory-page-header">
  <div>
    <h1>Inventory</h1>
    <p class="text-muted inventory-page-subtitle">Manage items, stock levels, and categories in one place.</p>
  </div>
  <div class="flex gap-2 inventory-page-actions">
    <button class="btn btn-secondary" data-manage-categories>📁 Categories</button>
    <button class="btn btn-danger" data-delete-all-inventory>Delete All Inventory</button>
    <button class="btn" data-add-item>+ Add Item</button>
  </div>
</div>

<div class="inventory-filters card">
  <div class="inventory-filters-head">
    <div>
      <h3>Find Items Faster</h3>
      <p>Use search, category, stock level, and sorting controls to narrow your list quickly.</p>
    </div>
    <button class="btn btn-secondary btn-sm" type="button" data-reset-filters>Reset Filters</button>
  </div>

  <div class="inventory-toolbar-grid">
    <label class="inventory-control inventory-control-search">
      <span>Search</span>
      <div class="search-box"><input class="input" id="search" placeholder="Search by item name..."></div>
    </label>

    <label class="inventory-control">
      <span>Category</span>
      <select class="select-native" id="filter-cat" data-custom-select>
        <option value="">All Categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['id'] ?>"><?= e($c['category_name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label class="inventory-control">
      <span>Stock Level</span>
      <select class="select-native" id="filter-stock" data-custom-select>
        <option value="all">All Stock Levels</option>
        <option value="in">In Stock</option>
        <option value="low">Low Stock</option>
        <option value="out">Out of Stock</option>
      </select>
    </label>

    <label class="inventory-control">
      <span>Sort By</span>
      <select class="select-native" id="sort-by" data-custom-select>
        <option value="name">Name (Default)</option>
        <option value="stock">Stock</option>
        <option value="max_order">Max Order</option>
        <option value="price">Price</option>
      </select>
    </label>

    <label class="inventory-control">
      <span>Order</span>
      <select class="select-native" id="sort-dir" data-custom-select>
        <option value="asc">Ascending</option>
        <option value="desc">Descending</option>
      </select>
    </label>
  </div>
</div>

<div class="inventory-table-shell card">
  <div class="inventory-table-head">
    <div>
      <h3>Inventory List</h3>
      <p class="text-muted" id="inventory-meta">Loading items...</p>
    </div>
  </div>
  <div id="inv-tbl"></div>
  <div class="pagination" id="pagination"></div>
</div>

<script>
  window._categories = <?= json_encode($categories) ?>;
  window._defaultNames = <?= json_encode($default_names) ?>;
</script>

<?php $PAGE_JS = '/admin/inventory/inventory.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
