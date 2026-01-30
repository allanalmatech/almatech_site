<?php
declare(strict_types=1);

$page_title="Maintenance Templates | Admin";
$active_admin="settings";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';

csrf_init();
$db = $GLOBALS['db'] ?? $mysqli ?? null;
if (!($db instanceof mysqli)) { http_response_code(500); exit('DB not available'); }

$rows = [];
$res = $db->query("SELECT id,title,slug,is_active,updated_at,created_at FROM maintenance_templates ORDER BY created_at DESC");
if ($res) $rows = $res->fetch_all(MYSQLI_ASSOC);

$csrf = csrf_token();
?>
<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h4 class="mb-0">Maintenance Templates</h4>
        <div class="text-muted small">Saved pages for Under Construction mode.</div>
      </div>
      <a class="btn btn-primary" href="add.php">+ New Template</a>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:80px;">#</th>
            <th>Title</th>
            <th style="width:220px;">Slug</th>
            <th style="width:120px;">Active</th>
            <th style="width:200px;">Updated</th>
            <th style="width:200px;" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="6" class="text-center text-muted py-5">No templates yet.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td class="text-muted"><?= (int)$r['id'] ?></td>
              <td class="fw-semibold"><?= h($r['title']) ?></td>
              <td><code><?= h($r['slug']) ?></code></td>
              <td><?= !empty($r['is_active']) ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
              <td class="text-muted small"><?= h((string)($r['updated_at'] ?? $r['created_at'])) ?></td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-secondary" href="edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
                <form class="d-inline" method="post" action="delete.php" onsubmit="return confirm('Delete this template?');">
                  <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
