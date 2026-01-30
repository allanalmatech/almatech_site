<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

$page_title = "Dashboard | Alma Tech Consults";
$page_heading = "Dashboard";
$page_subtitle = "Quick overview of the website";
$active_admin = "dashboard";

require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

// Include database connection
require_once __DIR__ . '/../includes/db.php';

// Get database connection
$db = $GLOBALS['db'] ?? $mysqli ?? null;

// Initialize counters
$leads_count = 0;
$services_count = 0;
$projects_count = 0;
$posts_count = 0;

if ($db instanceof mysqli) {
  // Count leads
  $result = $db->query("SELECT COUNT(*) AS total FROM leads");
  if ($result) {
    $row = $result->fetch_assoc();
    $leads_count = (int)($row['total'] ?? 0);
  }
  
  // Count services
  $result = $db->query("SELECT COUNT(*) AS total FROM services");
  if ($result) {
    $row = $result->fetch_assoc();
    $services_count = (int)($row['total'] ?? 0);
  }
  
  // Count projects
  $result = $db->query("SELECT COUNT(*) AS total FROM projects");
  if ($result) {
    $row = $result->fetch_assoc();
    $projects_count = (int)($row['total'] ?? 0);
  }
  
  // Count posts
  $result = $db->query("SELECT COUNT(*) AS total FROM posts");
  if ($result) {
    $row = $result->fetch_assoc();
    $posts_count = (int)($row['total'] ?? 0);
  }
}
?>

<div class="admin-main">
  <?php require_once __DIR__ . '/includes/admin_topbar.php'; ?>

  <div class="row g-3 g-lg-4">
    <div class="col-md-6 col-lg-3">
      <div class="admin-card p-4">
        <div class="small text-muted">Leads</div>
        <div class="h3 fw-bold mb-0"><?= number_format($leads_count) ?></div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="admin-card p-4">
        <div class="small text-muted">Services</div>
        <div class="h3 fw-bold mb-0"><?= number_format($services_count) ?></div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="admin-card p-4">
        <div class="small text-muted">Projects</div>
        <div class="h3 fw-bold mb-0"><?= number_format($projects_count) ?></div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3">
      <div class="admin-card p-4">
        <div class="small text-muted">Posts</div>
        <div class="h3 fw-bold mb-0"><?= number_format($posts_count) ?></div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="admin-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="fw-bold">Quick Actions</div>
        </div>

        <div class="row g-2">
          <div class="col-md-6">
            <a class="btn btn-orange w-100" href="<?= ADMIN_URL ?>services/list.php"><i class="bi bi-grid-1x2 me-1"></i> Manage Services</a>
          </div>
          <div class="col-md-6">
            <a class="btn btn-outline-orange w-100" href="<?= ADMIN_URL ?>projects/list.php"><i class="bi bi-briefcase me-1"></i> Manage Projects</a>
          </div>
          <div class="col-md-6">
            <a class="btn btn-outline-orange w-100" href="<?= ADMIN_URL ?>posts/list.php"><i class="bi bi-journal-text me-1"></i> Manage Blog Posts</a>
          </div>
          <div class="col-md-6">
            <a class="btn btn-outline-orange w-100" href="<?= ADMIN_URL ?>leads/list.php"><i class="bi bi-inbox me-1"></i> View Leads</a>
          </div>
          <div class="col-md-6">
            <a class="btn btn-primary w-100" href="<?= ADMIN_URL ?>home/index.php"><i class="bi bi-house-gear-fill me-1"></i> Homepage Settings</a>
          </div>
          <div class="col-md-6">
            <a class="btn btn-outline-secondary w-100" href="<?= ADMIN_URL ?>settings/index.php"><i class="bi bi-gear me-1"></i> General Settings</a>
          </div>
        </div>

        <div class="small text-muted mt-3">
          Next: connect the counters to the database and build CRUD pages.
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="admin-card p-4">
        <div class="fw-bold mb-2">Account</div>
        <div class="small text-muted mb-1">Name</div>
        <div class="fw-semibold mb-2"><?= h($admin['name'] ?? 'Admin') ?></div>

        <div class="small text-muted mb-1">Role</div>
        <div class="fw-semibold mb-3"><?= h($admin['role'] ?? 'admin') ?></div>

        <a class="btn btn-outline-orange w-100" href="logout.php">
          <i class="bi bi-box-arrow-right me-1"></i> Logout
        </a>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
