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
    <button class="btn" data-add-item>+ Add Item</button>
  </div>
</div>

<div class="toolbar">
  <div class="search-box"><input class="input" id="search" placeholder="Search items..."></div>
  <select class="select-native" id="filter-cat" data-custom-select>
    <option value="">All Categories</option>
    <?php foreach ($categories as $c): ?>
      <option value="<?= (int)$c['id'] ?>"><?= e($c['category_name']) ?></option>
    <?php endforeach; ?>
  </select>
</div>

<div id="inv-tbl"></div>
<div class="pagination" id="pagination"></div>

<script>
  window._categories = <?= json_encode($categories) ?>;
  window._defaultNames = <?= json_encode($default_names) ?>;
</script>

<?php $PAGE_JS = '/admin/inventory/inventory.js'; include __DIR__ . '/../../includes/layout_footer.php'; ?>
