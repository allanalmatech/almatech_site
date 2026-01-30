<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$menu = [
  ['key' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'href' => ADMIN_URL . 'dashboard.php'],
  ['key' => 'services',  'label' => 'Services',  'icon' => 'bi-grid-1x2',     'href' => ADMIN_URL . 'services/list.php'],
  ['key' => 'projects',  'label' => 'Projects',  'icon' => 'bi-briefcase',    'href' => ADMIN_URL . 'projects/list.php'],
  ['key' => 'posts',     'label' => 'Blog Posts','icon' => 'bi-journal-text', 'href' => ADMIN_URL . 'posts/list.php'],
  ['key' => 'pages',     'label' => 'Pages',     'icon' => 'bi-file-text',    'href' => ADMIN_URL . 'pages/index.php'],
  ['key' => 'team',      'label' => 'Team',      'icon' => 'bi-people',       'href' => ADMIN_URL . 'team/list.php'],
  ['key' => 'testimonials','label' => 'Testimonials','icon' => 'bi-chat-quote', 'href' => ADMIN_URL . 'testimonials/list.php'],
  ['key' => 'leads',     'label' => 'Leads',     'icon' => 'bi-inbox',        'href' => ADMIN_URL . 'leads/list.php'],
  ['key' => 'settings',  'label' => 'Settings',  'icon' => 'bi-gear',         'href' => ADMIN_URL . 'settings/index.php'],
];
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
      <?php foreach ($menu as $m): ?>
        <?php $isActive = (($active_admin ?? '') === $m['key']); ?>
        <a class="admin-link <?= $isActive ? 'active' : '' ?>" href="<?= h($m['href']) ?>">
          <i class="bi <?= h($m['icon']) ?>"></i>
          <span><?= h($m['label']) ?></span>
        </a>
      <?php endforeach; ?>
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
