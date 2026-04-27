<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_role('customer');

$me = get_current_user_data();
$categories = $pdo->query("SELECT id, category_name FROM item_categories ORDER BY category_name")->fetchAll();

$PAGE_TITLE   = 'Make Order';
$CURRENT_PAGE = 'make_order';
$PAGE_CSS     = '/customer/make_order/make_order.css';
$EXTRA_CSS    = ['/assets/css/cart-drawer.css'];
include __DIR__ . '/../../includes/layout_header.php';
?>
<div class="page-header">
  <div class="page-header-main">
    <h1>Browse & Order</h1>
    <p class="page-subtitle">Find school supplies fast, add items to cart, and place your order securely.</p>
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

<div class="items-grid" id="items-grid"></div>
<div class="pagination" id="pagination"></div>

<!-- Cart drawer -->
<button class="cart-fab" id="cart-fab" title="View Cart">🛒 <span class="cart-count" id="cart-count">0</span></button>
<div class="cart-overlay" id="cart-overlay"></div>
<div class="cart-drawer" id="cart-drawer">
  <div class="cd-header">
    <h3>Your Cart</h3>
    <button class="btn btn-ghost btn-sm" id="cart-close">✕</button>
  </div>
  <div class="cd-items" id="cd-items"></div>
  <div class="cd-footer">
    <div class="cd-total"><span>Total:</span><strong id="cd-total">₱0.00</strong></div>
    <button class="btn btn-lg" id="cd-checkout" style="width:100%">Place Order</button>
  </div>
</div>

<script>
  window._cartMode = 'customer';
  window._userId = <?= (int)$me['id'] ?>;
</script>

<?php
$EXTRA_JS = ['/assets/js/cart-drawer.js'];
$PAGE_JS  = '/customer/make_order/make_order.js';
include __DIR__ . '/../../includes/layout_footer.php';
?>
