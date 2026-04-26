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
<div class="page-header">
  <h1>Inventory</h1>
  <div class="flex gap-2">
    <button class="btn btn-secondary" data-manage-categories>📁 Categories</button>
    <button class="btn btn-danger" data-delete-all-inventory>Delete All Inventory</button>
    <button class="btn" data-add-item>+ Add Item</button>
  </div>
</div>

<div class="inventory-filters card">
  <div class="inventory-filters-head">
    <div>
      <h3>Filter Inventory</h3>
      <p>Search, narrow down stock, and sort items faster.</p>
    </div>
    <button class="btn btn-secondary btn-sm" type="button" data-reset-filters>Reset</button>
  </div>

  <div class="toolbar inventory-toolbar">
    <div class="search-box"><input class="input" id="search" placeholder="Search items..."></div>
    <select class="select-native" id="filter-cat" data-custom-select>
      <option value="">All Categories</option>
      <?php foreach ($categories as $c): ?>
        <option value="<?= (int)$c['id'] ?>"><?= e($c['category_name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select class="select-native" id="filter-stock" data-custom-select>
      <option value="all">All Stock Levels</option>
      <option value="in">In Stock</option>
      <option value="low">Low Stock</option>
      <option value="out">Out of Stock</option>
    </select>
    <select class="select-native" id="sort-by" data-custom-select>
      <option value="name">Name (Default)</option>
      <option value="stock">Stock</option>
      <option value="max_order">Max Order</option>
      <option value="price">Price</option>
    </select>
    <select class="select-native" id="sort-dir" data-custom-select>
      <option value="asc">Ascending</option>
      <option value="desc">Descending</option>
    </select>
  </div>
</div>

<div id="inv-tbl"></div>
<div class="pagination" id="pagination"></div>

<script>
  window._categories = <?= json_encode($categories) ?>;
  window._defaultNames = <?= json_encode($default_names) ?>;
</script>

<?php $PAGE_JS = '/admin/inventory/inventory.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
