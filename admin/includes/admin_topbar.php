<?php
declare(strict_types=1);
?>
<!-- Desktop topbar -->
<div class="admin-topbar d-none d-lg-flex">
  <div class="d-flex align-items-center gap-2">
    <div class="fw-bold"><?= h($page_heading ?? 'Dashboard') ?></div>
    <?php if (!empty($page_subtitle)): ?>
      <div class="text-muted small">• <?= h($page_subtitle) ?></div>
    <?php endif; ?>
  </div>

  <div class="d-flex align-items-center gap-2">
    <a class="btn btn-outline-orange" href="../logout.php" title="Logout">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
</div>
