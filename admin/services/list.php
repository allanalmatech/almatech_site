<?php
declare(strict_types=1);

// Early authentication check before any HTML output
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_admin_login();

require_once __DIR__ . '/../../includes/db.php';
csrf_init();

$flash = flash_get();

// Handle delete (POST) - BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $token = (string)($_POST['csrf_token'] ?? '');
  $id = (int)($_POST['id'] ?? 0);

  if (!csrf_validate($token)) {
    flash_set('danger', 'Security check failed. Please refresh and try again.');
    redirect('list.php');
  }

  if ($id > 0) {
    // fetch icon name so we can delete file
    $stmt = $mysqli->prepare("SELECT icon FROM services WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    $icon = $row['icon'] ?? null;

    $stmt = $mysqli->prepare("DELETE FROM services WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    // delete file (optional)
    if ($icon) {
      $path = __DIR__ . '/../../uploads/services/' . $icon;
      if (file_exists($path)) {
        unlink($path);
      }
    }

    flash_set('success', 'Service deleted successfully.');
    redirect('list.php');
  }
}

$page_title = "Services | Admin";
$page_heading = "Services";
$page_subtitle = "Manage services shown on the website";
$active_admin = "services";

// HTML layout only after all processing is complete
require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';

// Filters
$q = trim((string)($_GET['q'] ?? ''));

// Pagination
$limit = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Count total
if ($q !== '') {
  $like = '%' . $q . '%';
  $stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM services WHERE title LIKE ? OR short_desc LIKE ?");
  $stmt->bind_param("ss", $like, $like);
} else {
  $stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM services");
}
$stmt->execute();
$totalRes = $stmt->get_result();
$totalRow = $totalRes ? $totalRes->fetch_assoc() : ['total' => 0];
$stmt->close();

$total = (int)($totalRow['total'] ?? 0);
$totalPages = max(1, (int)ceil($total / $limit));

// Fetch rows
if ($q !== '') {
  $like = '%' . $q . '%';
  $stmt = $mysqli->prepare(
    "SELECT id, title, slug, short_desc, icon, is_active, created_at
     FROM services
     WHERE title LIKE ? OR short_desc LIKE ?
     ORDER BY id DESC
     LIMIT ? OFFSET ?"
  );
  $stmt->bind_param("ssii", $like, $like, $limit, $offset);
} else {
  $stmt = $mysqli->prepare(
    "SELECT id, title, slug, short_desc, icon, is_active, created_at
     FROM services
     ORDER BY id DESC
     LIMIT ? OFFSET ?"
  );
  $stmt->bind_param("ii", $limit, $offset);
}
$stmt->execute();
$res = $stmt->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

function page_url(int $p, string $q): string {
  $p = max(1, $p);
  $base = 'list.php?page=' . $p;
  if ($q !== '') $base .= '&q=' . urlencode($q);
  return $base;
}
?>

<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <?php if ($flash): ?>
    <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['msg']) ?></div>
  <?php endif; ?>

  <div class="admin-card p-3 p-md-4 mb-3">
    <div class="d-flex flex-column flex-lg-row gap-2 justify-content-between align-items-lg-center">
      <form class="d-flex gap-2" method="get" action="list.php" style="max-width:520px; width:100%;">
        <input type="text" class="form-control" name="q" value="<?= h($q) ?>" placeholder="Search services...">
        <button class="btn btn-outline-orange" type="submit"><i class="bi bi-search"></i></button>
      </form>

      <div class="d-flex gap-2">
        <a href="add.php" class="btn btn-orange">
          <i class="bi bi-plus-lg me-1"></i> Add Service
        </a>
      </div>
    </div>
  </div>

  <div class="admin-card p-0 overflow-hidden">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
      <div class="fw-bold">Services</div>
      <div class="small text-muted"><?= $total ?> total</div>
    </div>

    <div class="table-responsive">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:72px;">Icon</th>
            <th>Title</th>
            <th class="d-none d-lg-table-cell">Short Description</th>
            <th>Status</th>
            <th class="d-none d-md-table-cell">Created</th>
            <th style="width:180px;" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr>
                
              <td colspan="6" class="p-4 text-center text-muted">
                No services found.
              </td>
            </tr>
          <?php endif; ?>

          <?php foreach ($rows as $r): ?>
            <?php
              $icon = (string)($r['icon'] ?? '');
              $iconUrl = $icon ? '../../uploads/services/' . $icon : '';
              $iconPath = $icon ? (__DIR__ . '/../../uploads/services/' . $icon) : '';
              $hasIcon = $icon && is_file($iconPath);
            ?>
            <tr>
              <td>
                <div style="width:44px;height:44px;border-radius:14px;border:1px solid rgba(15,23,42,.08);background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                  <?php if ($hasIcon): ?>
                    <img src="<?= h($iconUrl) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                  <?php else: ?>
                    <i class="bi bi-image text-muted"></i>
                  <?php endif; ?>
                </div>
              </td>

              <td>
                <div class="fw-semibold"><?= h($r['title']) ?></div>
                <div class="small text-muted"><?= h($r['slug']) ?></div>
              </td>

              <td class="d-none d-lg-table-cell text-muted">
                <?= h($r['short_desc']) ?>
              </td>

              <td>
                <?php if ((int)$r['is_active'] === 1): ?>
                  <span class="badge text-bg-success">Active</span>
                <?php else: ?>
                  <span class="badge text-bg-secondary">Hidden</span>
                <?php endif; ?>
              </td>

              <td class="d-none d-md-table-cell text-muted">
                <?= h($r['created_at']) ?>
              </td>

              <td class="text-end">
                <div class="d-inline-flex gap-2">
                  <a class="btn btn-sm btn-outline-orange" href="edit.php?id=<?= (int)$r['id'] ?>">
                    <i class="bi bi-pencil"></i>
                  </a>

                  <form method="post" onsubmit="return confirm('Delete this service?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                    <button class="btn btn-sm btn-outline-danger" type="submit">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
      <div class="p-3 border-top d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Page <?= $page ?> of <?= $totalPages ?>
        </div>
        <div class="d-flex gap-2">
          <a class="btn btn-sm btn-outline-secondary <?= $page <= 1 ? 'disabled' : '' ?>"
             href="<?= h(page_url($page - 1, $q)) ?>">Prev</a>

          <a class="btn btn-sm btn-outline-secondary <?= $page >= $totalPages ? 'disabled' : '' ?>"
             href="<?= h(page_url($page + 1, $q)) ?>">Next</a>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
