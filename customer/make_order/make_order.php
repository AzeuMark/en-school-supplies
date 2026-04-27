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
    <h1>Browse &amp; Order</h1>
    <p class="page-subtitle">Find school supplies fast, add items to your cart, and place your order securely.</p>
  </div>
</div>

<div class="mo-toolbar">
  <div class="mo-search-wrap">
    <span class="mo-search-icon">🔍</span>
    <input class="input" id="search" placeholder="Search items..." autocomplete="off" spellcheck="false">
  </div>
</div>

<div class="mo-cat-tabs" id="mo-cat-tabs">
  <button class="mo-cat-tab active" data-cat="">All Items</button>
  <?php foreach ($categories as $c): ?>
    <button class="mo-cat-tab" data-cat="<?= (int)$c['id'] ?>"><?= e($c['category_name']) ?></button>
  <?php endforeach; ?>
</div>

<div class="mo-results-meta" id="results-meta"></div>

<div class="mo-item-grid" id="item-grid"></div>
<div class="pagination" id="pagination"></div>

<!-- Cart FAB -->
<button class="cart-fab" data-cart-fab aria-label="Open cart">🛒</button>

<!-- Cart drawer -->
<div class="cart-drawer-backdrop" data-cart-backdrop></div>
<aside class="cart-drawer" data-cart-drawer aria-label="Cart">
  <div class="cart-drawer-header">
    <div>
      <h3>Your Cart</h3>
      <div class="mo-cart-subtitle" id="cart-drawer-subtitle"></div>
    </div>
    <button class="modal-close" data-cart-close aria-label="Close">×</button>
  </div>
  <div class="cart-drawer-body" data-cart-body></div>
  <div class="cart-drawer-footer">
    <div class="cart-totals"><span>Total</span><span class="ct-amount" data-cart-total>₱0.00</span></div>
    <button class="btn btn-block btn-lg" data-cart-checkout disabled>Place Order</button>
  </div>
</aside>

<script>
  window._userId = <?= (int)$me['id'] ?>;
</script>

<?php
$EXTRA_JS = ['/assets/js/cart-drawer.js'];
$PAGE_JS  = '/customer/make_order/make_order.js';
include __DIR__ . '/../../includes/layout_footer.php';
?>
