<?php
// Closes the layout opened by layout_header.php
// Pages can set:
//   $PAGE_JS - relative path to page-specific JS (loaded after globals)
?>
    </main>
  </div><!-- /.app-shell -->

  <script src="<?= e(url('/assets/js/global.js')) ?>"></script>
  <script src="<?= e(url('/assets/js/theme.js')) ?>"></script>
  <script src="<?= e(url('/assets/js/layout.js')) ?>"></script>
  <script src="<?= e(url('/assets/js/custom-select.js')) ?>"></script>
  <script src="<?= e(url('/assets/js/pagination.js')) ?>"></script>
  <script src="<?= e(url('/assets/js/modal.js')) ?>"></script>
  <?php if (!empty($EXTRA_JS) && is_array($EXTRA_JS)): foreach ($EXTRA_JS as $_js): ?>
    <script src="<?= e(url($_js)) ?>"></script>
  <?php endforeach; endif; ?>
  <?php if (!empty($PAGE_JS)): ?>
    <script src="<?= e(url($PAGE_JS)) ?>"></script>
  <?php endif; ?>
</body>
</html>
