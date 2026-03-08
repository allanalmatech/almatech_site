<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_admin_login();

require_once __DIR__ . '/../includes/db.php';
//require_once __DIR__ . '/../config.php';

$db = $GLOBALS['db'] ?? $mysqli ?? null;
if (!($db instanceof mysqli)) {
  http_response_code(500);
  exit('DB not available');
}

function table_exists(mysqli $db, string $table): bool {
  static $cache = [];
  if (isset($cache[$table])) return $cache[$table];

  $safe = $db->real_escape_string($table);
  $res = $db->query("SHOW TABLES LIKE '{$safe}'");
  if (!$res) return $cache[$table] = false;
  $ok = $res->num_rows > 0;
  $res->free();
  return $cache[$table] = $ok;
}

function count_rows(mysqli $db, string $table, string $where = '1=1'): int {
  if (!table_exists($db, $table)) return 0;
  $res = $db->query("SELECT COUNT(*) AS c FROM `{$table}` WHERE {$where}");
  if (!$res) return 0;
  $c = (int)($res->fetch_assoc()['c'] ?? 0);
  $res->free();
  return $c;
}

$stats = [
  'projects_total' => count_rows($db, 'projects'),
  'projects_completed' => count_rows($db, 'projects', "status='completed'"),
  'projects_ongoing' => count_rows($db, 'projects', "status='ongoing'"),
  'posts_total' => count_rows($db, 'posts'),
  'posts_published' => count_rows($db, 'posts', "status='published'"),
  'posts_featured' => count_rows($db, 'posts', "is_featured=1"),
  'services_total' => count_rows($db, 'services'),
  'services_active' => count_rows($db, 'services', 'is_active=1'),
  'pages_total' => count_rows($db, 'pages'),
  'pages_published' => count_rows($db, 'pages', "status='published'"),
  'team_total' => count_rows($db, 'team_members'),
  'team_active' => count_rows($db, 'team_members', "status='active'"),
  'testimonials_total' => count_rows($db, 'testimonials'),
  'testimonials_published' => count_rows($db, 'testimonials', "status='published'"),
  'leads_total' => count_rows($db, 'leads'),
  'leads_new' => count_rows($db, 'leads', "status IN ('new','in_progress','contacted')"),
  'shop_categories' => count_rows($db, 'categories'),
  'shop_products' => count_rows($db, 'products'),
  'shop_featured' => count_rows($db, 'products', 'featured=1'),
  'shop_active' => count_rows($db, 'products', 'status=1'),
  'maintenance_templates' => count_rows($db, 'maintenance_templates'),
];

$page_title = 'Dashboard | Admin';
$page_heading = 'Dashboard';
$page_subtitle = 'Overview of content, shop, and lead activity';
$active_admin = 'dashboard';

require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

$base = rtrim((string)($GLOBALS['BASE_URL'] ?? BASE_URL), '/');
?>

<div class="admin-main">
  <?php require_once __DIR__ . '/includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
      <div>
        <h4 class="mb-1">Welcome back, <?= h($admin['name'] ?? 'Admin') ?></h4>
        <div class="text-muted small">Track what is happening across your website and shop catalog.</div>
      </div>
      <div class="small text-muted"><i class="bi bi-calendar3 me-1"></i><?= h(date('l, d M Y')) ?></div>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-6 col-xl-3">
        <div class="card stat-card p-3 h-100">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="text-muted small">Projects</div>
              <div class="h3 mb-0"><?= (int)$stats['projects_total'] ?></div>
            </div>
            <i class="bi bi-briefcase fs-4 text-primary"></i>
          </div>
          <div class="small text-muted mt-2">Completed: <?= (int)$stats['projects_completed'] ?> | Ongoing: <?= (int)$stats['projects_ongoing'] ?></div>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="card stat-card p-3 h-100">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="text-muted small">Blog Posts</div>
              <div class="h3 mb-0"><?= (int)$stats['posts_total'] ?></div>
            </div>
            <i class="bi bi-journal-text fs-4 text-warning"></i>
          </div>
          <div class="small text-muted mt-2">Published: <?= (int)$stats['posts_published'] ?> | Featured: <?= (int)$stats['posts_featured'] ?></div>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="card stat-card p-3 h-100">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="text-muted small">Leads</div>
              <div class="h3 mb-0"><?= (int)$stats['leads_total'] ?></div>
            </div>
            <i class="bi bi-inbox fs-4 text-success"></i>
          </div>
          <div class="small text-muted mt-2">Needs follow-up: <?= (int)$stats['leads_new'] ?></div>
        </div>
      </div>
      <div class="col-md-6 col-xl-3">
        <div class="card stat-card p-3 h-100">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="text-muted small">Shop Products</div>
              <div class="h3 mb-0"><?= (int)$stats['shop_products'] ?></div>
            </div>
            <i class="bi bi-cart-check fs-4 text-danger"></i>
          </div>
          <div class="small text-muted mt-2">Active: <?= (int)$stats['shop_active'] ?> | Featured: <?= (int)$stats['shop_featured'] ?></div>
        </div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-xl-4">
        <div class="card table-card p-4 h-100">
          <h5 class="mb-3"><i class="bi bi-grid-1x2-fill me-2 text-primary"></i>Content Management</h5>
          <ul class="list-unstyled small text-muted mb-4">
            <li class="mb-2">Services: <strong><?= (int)$stats['services_total'] ?></strong> (Active: <?= (int)$stats['services_active'] ?>)</li>
            <li class="mb-2">Pages: <strong><?= (int)$stats['pages_total'] ?></strong> (Published: <?= (int)$stats['pages_published'] ?>)</li>
            <li class="mb-0">Posts: <strong><?= (int)$stats['posts_total'] ?></strong></li>
          </ul>
          <div class="d-grid gap-2">
            <a class="btn btn-outline-primary" href="<?= ADMIN_URL ?>services/list.php"><i class="bi bi-grid-1x2 me-1"></i> Manage Services</a>
            <a class="btn btn-outline-primary" href="<?= ADMIN_URL ?>projects/list.php"><i class="bi bi-briefcase me-1"></i> Manage Projects</a>
            <a class="btn btn-outline-primary" href="<?= ADMIN_URL ?>posts/list.php"><i class="bi bi-journal-text me-1"></i> Manage Posts</a>
            <a class="btn btn-outline-primary" href="<?= ADMIN_URL ?>pages/index.php"><i class="bi bi-file-text me-1"></i> Manage Pages</a>
          </div>
        </div>
      </div>

      <div class="col-xl-4">
        <div class="card table-card p-4 h-100">
          <h5 class="mb-3"><i class="bi bi-cart-fill me-2 text-danger"></i>Shop Management</h5>
          <ul class="list-unstyled small text-muted mb-4">
            <li class="mb-2">Categories: <strong><?= (int)$stats['shop_categories'] ?></strong></li>
            <li class="mb-2">Products: <strong><?= (int)$stats['shop_products'] ?></strong></li>
            <li class="mb-0">Featured Products: <strong><?= (int)$stats['shop_featured'] ?></strong></li>
          </ul>
          <div class="d-grid gap-2">
            <a class="btn btn-outline-danger" href="<?= ADMIN_URL ?>categories.php"><i class="bi bi-tags me-1"></i> Shop Categories</a>
            <a class="btn btn-outline-danger" href="<?= ADMIN_URL ?>products.php"><i class="bi bi-box-seam me-1"></i> Shop Products</a>
            <a class="btn btn-outline-danger" href="<?= ADMIN_URL ?>settings.php"><i class="bi bi-sliders me-1"></i> Shop Settings</a>
            <a class="btn btn-outline-success" href="<?= h($base) ?>/shop/" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i> View Public Shop</a>
          </div>
        </div>
      </div>

      <div class="col-xl-4">
        <div class="card table-card p-4 h-100">
          <h5 class="mb-3"><i class="bi bi-people-fill me-2 text-success"></i>Leads & Social Proof</h5>
          <ul class="list-unstyled small text-muted mb-4">
            <li class="mb-2">Leads: <strong><?= (int)$stats['leads_total'] ?></strong></li>
            <li class="mb-2">Team Members: <strong><?= (int)$stats['team_total'] ?></strong> (Active: <?= (int)$stats['team_active'] ?>)</li>
            <li class="mb-2">Testimonials: <strong><?= (int)$stats['testimonials_total'] ?></strong> (Published: <?= (int)$stats['testimonials_published'] ?>)</li>
            <li class="mb-0">Maintenance Templates: <strong><?= (int)$stats['maintenance_templates'] ?></strong></li>
          </ul>
          <div class="d-grid gap-2">
            <a class="btn btn-outline-success" href="<?= ADMIN_URL ?>leads/list.php"><i class="bi bi-inbox me-1"></i> Manage Leads</a>
            <a class="btn btn-outline-success" href="<?= ADMIN_URL ?>team/list.php"><i class="bi bi-people me-1"></i> Manage Team</a>
            <a class="btn btn-outline-success" href="<?= ADMIN_URL ?>testimonials/list.php"><i class="bi bi-chat-quote me-1"></i> Manage Testimonials</a>
            <a class="btn btn-outline-secondary" href="<?= ADMIN_URL ?>settings/index.php"><i class="bi bi-gear me-1"></i> Global Settings</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
