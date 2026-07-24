<?php
declare(strict_types=1);

// Early authentication check before any HTML output
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_admin_login();

require_once __DIR__ . '/../../includes/db.php';

$db = $GLOBALS['db'] ?? $mysqli ?? null;
$UPLOAD_URL = rtrim((string)BASE_URL, '/') . '/uploads/team';

$q = trim((string)($_GET['q'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

$where = "1=1";
$params = [];
$types = "";

if ($q !== '') {
  $where .= " AND (name LIKE CONCAT('%',?,'%') OR role LIKE CONCAT('%',?,'%') OR slug LIKE CONCAT('%',?,'%'))";
  $params[] = $q; $params[] = $q; $params[] = $q;
  $types .= "sss";
}
if (in_array($status, ['active','inactive'], true)) {
  $where .= " AND status=?";
  $params[] = $status;
  $types .= "s";
}

$sql = "SELECT id,name,role,slug,photo,status,sort_order,created_at
        FROM team_members
        WHERE $where
        ORDER BY sort_order ASC, id DESC
        LIMIT 500";

$st = $db->prepare($sql);
if ($types !== "") $st->bind_param($types, ...$params);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "Team Members | Admin";
$page_heading = "Team Members";
$page_subtitle = "Manage public \"Our Team\" profiles";
$active_admin = "team";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>
<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-0">Team Members</h4>
        <small class="text-muted">Manage public "Our Team" profiles</small>
      </div>
      <a class="btn btn-orange" href="add.php">
        <i class="bi bi-plus-circle me-1"></i> Add Member
      </a>
    </div>

    <form class="row g-2 mb-4" method="get">
      <div class="col-md-5">
        <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search name, role, slug...">
      </div>
      <div class="col-md-3">
        <select class="form-select" name="status">
          <option value="">All status</option>
          <option value="active"   <?= $status==='active'?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-outline-secondary w-100">Filter</button>
      </div>
      <div class="col-md-2">
        <a class="btn btn-outline-dark w-100" href="list.php">Reset</a>
      </div>
    </form>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:70px;">Photo</th>
            <th>Name</th>
            <th>Role</th>
            <th>Slug</th>
            <th>Status</th>
            <th style="width:90px;">Order</th>
            <th style="width:170px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$rows): ?>
          <tr><td colspan="7" class="text-center py-4 text-muted">No team members found.</td></tr>
        <?php endif; ?>

        <?php foreach ($rows as $r): ?>
          <tr>
            <td>
              <?php if (!empty($r['photo'])): ?>
                <img src="<?= $UPLOAD_URL ?>/<?= htmlspecialchars($r['photo']) ?>" class="rounded" style="width:52px;height:52px;object-fit:cover;">
              <?php else: ?>
                <div class="rounded bg-secondary-subtle d-flex align-items-center justify-content-center" style="width:52px;height:52px;">—</div>
              <?php endif; ?>
            </td>
            <td>
              <div class="fw-semibold"><?= htmlspecialchars($r['name']) ?></div>
              <small class="text-muted">ID: <?= (int)$r['id'] ?></small>
            </td>
            <td><?= htmlspecialchars($r['role']) ?></td>
            <td><code><?= htmlspecialchars($r['slug']) ?></code></td>
            <td>
              <span class="badge <?= $r['status']==='active'?'bg-success':'bg-secondary' ?>">
                <?= htmlspecialchars($r['status']) ?>
              </span>
            </td>
            <td><?= (int)$r['sort_order'] ?></td>
            <td class="text-nowrap">
              <a class="btn btn-sm btn-outline-primary" href="edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
              <form class="d-inline" method="post" action="delete.php" onsubmit="return confirm('Delete this member?');">
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
