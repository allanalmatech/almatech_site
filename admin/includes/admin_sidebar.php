<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
?>

<!-- Sidebar (offcanvas on mobile, fixed column on desktop) -->
<div class="offcanvas-lg offcanvas-start admin-sidebar" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
  <div class="offcanvas-header d-lg-none border-bottom">
    <h5 class="offcanvas-title fw-bold" id="adminSidebarLabel">Admin Menu</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>

  <!-- This wrapper makes sidebar content NOT overlap + allows bottom pin -->
  <div class="offcanvas-body p-0 admin-sidebar-inner">

    <!-- Branding / Logo -->
    <div class="admin-brand">
      <a class="admin-brand-link" href="<?= ADMIN_URL ?>dashboard.php">
        <div class="admin-logo-box">A</div>
        <div class="admin-brand-text">
          <div class="admin-brand-name">Alma Tech Consults</div>
          <div class="admin-brand-sub">Admin Panel</div>
        </div>
      </a>
    </div>

    <!-- User -->
    <div class="admin-user">
      <div class="d-flex align-items-center gap-2">
        <div class="admin-avatar"><i class="bi bi-person"></i></div>
        <div>
          <div class="fw-semibold"><?= h($admin['name'] ?? 'Admin') ?></div>
          <div class="small text-muted"><?= h($admin['role'] ?? 'admin') ?></div>
        </div>
      </div>
    </div>

    <!-- Menu -->
    <div class="admin-menu">
      <div class="small text-uppercase text-muted px-3 pt-2 pb-1" style="letter-spacing:.04em; font-size:.68rem;">Overview</div>
      <a class="admin-link <?= (($active_admin ?? '') === 'dashboard') ? 'active' : '' ?>" href="<?= h(ADMIN_URL . 'dashboard.php') ?>">
        <i class="bi bi-speedometer2"></i>
        <span>Dashboard</span>
      </a>

      <?php $shopKeys = ['shop_categories', 'shop_products', 'shop_requests', 'shop_settings']; ?>
      <?php $shopOpen = in_array(($active_admin ?? ''), $shopKeys, true); ?>
      <div class="small text-uppercase text-muted px-3 pt-2 pb-1" style="letter-spacing:.04em; font-size:.68rem;">Shop Management</div>
      <button class="admin-link d-flex justify-content-between align-items-center <?= $shopOpen ? 'active' : '' ?> <?= $shopOpen ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#shopSubmenu" aria-expanded="<?= $shopOpen ? 'true' : 'false' ?>" aria-controls="shopSubmenu">
        <span><i class="bi bi-cart me-2"></i>Shop</span>
        <i class="bi shop-toggle-icon <?= $shopOpen ? 'bi-chevron-up' : 'bi-chevron-down' ?> small"></i>
      </button>
      <div class="collapse <?= $shopOpen ? 'show' : '' ?>" id="shopSubmenu">
        <a class="admin-link ps-5 <?= (($active_admin ?? '') === 'shop_categories') ? 'active' : '' ?>" href="<?= h(ADMIN_URL . 'categories.php') ?>">
          <i class="bi bi-tags"></i>
          <span>Categories</span>
        </a>
        <a class="admin-link ps-5 <?= (($active_admin ?? '') === 'shop_products') ? 'active' : '' ?>" href="<?= h(ADMIN_URL . 'products.php') ?>">
          <i class="bi bi-box-seam"></i>
          <span>Products</span>
        </a>
        <a class="admin-link ps-5 <?= (($active_admin ?? '') === 'shop_requests') ? 'active' : '' ?>" href="<?= h(ADMIN_URL . 'requests.php') ?>">
          <i class="bi bi-inboxes"></i>
          <span>Requests</span>
        </a>
        <a class="admin-link ps-5 <?= (($active_admin ?? '') === 'shop_settings') ? 'active' : '' ?>" href="<?= h(ADMIN_URL . 'settings.php') ?>">
          <i class="bi bi-sliders"></i>
          <span>Shop Settings</span>
        </a>
      </div>

      <div class="small text-uppercase text-muted px-3 pt-2 pb-1" style="letter-spacing:.04em; font-size:.68rem;">Website Content</div>
      <a class="admin-link <?= (($active_admin ?? '') === 'services') ? 'active' : '' ?>" href="<?= h(ADMIN_URL . 'services/list.php') ?>"><i class="bi bi-grid-1x2"></i><span>Services</span></a>
      <a class="admin-link <?= (($active_admin ?? '') === 'projects') ? 'active' : '' ?>" href="<?= h(ADMIN_URL . 'projects/list.php') ?>"><i class="bi bi-briefcase"></i><span>Projects</span></a>
      <a class="admin-link <?= (($active_admin ?? '') === 'posts') ? 'active' : '' ?>" href="<?= h(ADMIN_URL . 'posts/list.php') ?>"><i class="bi bi-journal-text"></i><span>Blog Posts</span></a>
      <a class="admin-link <?= (($active_admin ?? '') === 'pages') ? 'active' : '' ?>" href="<?= h(ADMIN_URL . 'pages/index.php') ?>"><i class="bi bi-file-text"></i><span>Pages</span></a>
      <a class="admin-link <?= (($active_admin ?? '') === 'team') ? 'active' : '' ?>" href="<?= h(ADMIN_URL . 'team/list.php') ?>"><i class="bi bi-people"></i><span>Team</span></a>
      <a class="admin-link <?= (($active_admin ?? '') === 'testimonials') ? 'active' : '' ?>" href="<?= h(ADMIN_URL . 'testimonials/list.php') ?>"><i class="bi bi-chat-quote"></i><span>Testimonials</span></a>
      <a class="admin-link <?= (($active_admin ?? '') === 'leads') ? 'active' : '' ?>" href="<?= h(ADMIN_URL . 'leads/list.php') ?>"><i class="bi bi-inbox"></i><span>Leads</span></a>
      <a class="admin-link <?= (($active_admin ?? '') === 'settings') ? 'active' : '' ?>" href="<?= h(ADMIN_URL . 'settings/index.php') ?>"><i class="bi bi-gear"></i><span>Settings</span></a>
    </div>

    <!-- Bottom area pinned -->
    <div class="admin-sidebar-bottom">
      <a class="btn btn-outline-orange w-100" href="<?= ADMIN_URL ?>logout.php">
        <i class="bi bi-box-arrow-right me-1"></i> Logout
      </a>
      <div class="admin-foot">© <?= date('Y') ?> Alma Tech Consults</div>
    </div>

  </div>
</div>
