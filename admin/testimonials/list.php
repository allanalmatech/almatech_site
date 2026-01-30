<?php
declare(strict_types=1);

// Early authentication check before any HTML output
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_admin_login();

require_once __DIR__ . '/../../includes/db.php';

$db = $GLOBALS['db'] ?? $mysqli ?? null;

$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

$where = "1=1";
$params = [];
$types = "";

if ($q !== '') {
  $where .= " AND (client_name LIKE CONCAT('%',?,'%') OR company LIKE CONCAT('%',?,'%') OR message LIKE CONCAT('%',?,'%'))";
  $params[] = $q; $params[] = $q; $params[] = $q;
  $types .= "sss";
}
if (in_array($status, ['published','draft'], true)) {
  $where .= " AND t.status=?";
  $params[] = $status;
  $types .= "s";
}

$sql = "SELECT t.id,t.client_name,t.company,t.rating,t.status,t.sort_order,t.show_project_link,t.created_at,
               t.project_id
        FROM testimonials t
        WHERE $where
        ORDER BY t.sort_order ASC, t.id DESC
        LIMIT 500";

$st = $db->prepare($sql);
if ($types !== "") $st->bind_param($types, ...$params);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Testimonials | Admin";
$page_heading = "Testimonials";
$page_subtitle = "Manage client testimonials";
$active_admin = "testimonials";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>
<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-0">Testimonials</h4>
        <small class="text-muted">Manage public testimonials + optional project linking</small>
      </div>
      <a class="btn btn-orange" href="add.php">
        <i class="bi bi-plus-circle me-1"></i> Add Testimonial
      </a>
    </div>

    <form class="row g-2 mb-4" method="get">
      <div class="col-md-6">
        <input class="form-control" name="q" value="<?= h($q) ?>" placeholder="Search name, company, message...">
      </div>
      <div class="col-md-3">
        <select class="form-select" name="status">
          <option value="">All status</option>
          <option value="published" <?= $status==='published'?'selected':'' ?>>Published</option>
          <option value="draft" <?= $status==='draft'?'selected':'' ?>>Draft</option>
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-outline-secondary w-100">Filter</button>
        <a class="btn btn-outline-dark w-100" href="list.php">Reset</a>
      </div>
    </form>

  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Client</th>
            <th>Company</th>
            <th>Rating</th>
            <th>Status</th>
            <th style="width:90px;">Order</th>
            <th style="width:210px;">Project Link</th>
            <th style="width:170px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="7" class="text-center py-4 text-muted">No testimonials found.</td></tr>
        <?php endif; ?>

        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="fw-semibold"><?= htmlspecialchars($r['client_name']) ?></td>
            <td><?= htmlspecialchars((string)($r['company'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($r['rating'] ?? '')) ?></td>
            <td>
              <span class="badge <?= $r['status']==='published'?'bg-success':'bg-secondary' ?>">
                <?= htmlspecialchars($r['status']) ?>
              </span>
            </td>
            <td><?= (int)$r['sort_order'] ?></td>
            <td>
              <?php if ((int)$r['show_project_link'] === 1): ?>
                <span class="badge bg-info">Enabled</span>
                <small class="text-muted ms-2">Project ID: <?= (int)($r['project_id'] ?? 0) ?></small>
              <?php else: ?>
                <span class="badge bg-secondary">Disabled</span>
              <?php endif; ?>
            </td>
            <td class="text-nowrap">
              <a class="btn btn-sm btn-outline-primary" href="edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
              <form class="d-inline" method="post" action="delete.php" onsubmit="return confirm('Delete this testimonial?');">
                <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
