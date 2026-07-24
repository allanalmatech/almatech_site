<?php
// admin/posts/list.php
declare(strict_types=1);

// Early authentication check before any HTML output
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_admin_login();

$page_title = "Posts | Admin";
$page_heading = "Posts";
$page_subtitle = "Manage blog posts shown on the website";
$active_admin = "posts";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
require_once __DIR__ . '/../../includes/db.php';

csrf_init();

$db = $GLOBALS['db'] ?? $mysqli ?? null;
if (!($db instanceof mysqli)) {
  http_response_code(500);
  exit('DB not available');
}

// -------------------- Filters --------------------
$q        = trim((string)($_GET['q'] ?? ''));
$status   = trim((string)($_GET['status'] ?? ''));   // published|draft|archived
$category = trim((string)($_GET['category'] ?? ''));

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$per_page = 15;
$offset   = ($page - 1) * $per_page;

$allowedStatus = ['published','draft','archived','trashed'];
if ($status !== '' && !in_array($status, $allowedStatus, true)) $status = '';

$where  = "1=1";
$types  = "";
$params = [];

// Search
if ($q !== '') {
  $where .= " AND (
      p.title LIKE CONCAT('%', ?, '%')
      OR p.slug LIKE CONCAT('%', ?, '%')
      OR p.excerpt LIKE CONCAT('%', ?, '%')
    )";
  $types .= "sss";
  $params[] = $q;
  $params[] = $q;
  $params[] = $q;
}

// Status
if ($status !== '') {
  $where .= " AND p.status = ?";
  $types .= "s";
  $params[] = $status;
}

// Category
if ($category !== '') {
  $where .= " AND p.category = ?";
  $types .= "s";
  $params[] = $category;
}

// -------------------- Total count --------------------
$sqlCount = "SELECT COUNT(*) AS cnt FROM posts p WHERE $where";
$stmt = $db->prepare($sqlCount);
if (!$stmt) {
  http_response_code(500);
  exit("DB error: " . $db->error);
}
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
$total = (int)($res->fetch_assoc()['cnt'] ?? 0);
$stmt->close();

$total_pages = max(1, (int)ceil($total / $per_page));
if ($page > $total_pages) {
  $page = $total_pages;
  $offset = ($page - 1) * $per_page;
}

// -------------------- Fetch rows --------------------
$sql = "
  SELECT
    p.id, p.title, p.slug, p.category, p.status,
    p.cover_image, p.published_at, p.updated_at, p.created_at,
    p.is_featured
  FROM posts p
  WHERE $where
  ORDER BY p.is_featured DESC, COALESCE(p.published_at, p.created_at) DESC
  LIMIT ? OFFSET ?
";

$stmt = $db->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  exit("DB error: " . $db->error);
}

$types2 = $types . "ii";
$params2 = array_merge($params, [$per_page, $offset]);

// Debug: Show the actual SQL and parameters
echo "<!-- Debug SQL: $sql -->";
echo "<!-- Debug params: " . json_encode($params2) . " -->";

$stmt->bind_param($types2, ...$params2);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Debug: Show results count and statuses
echo "<!-- Debug results count: " . count($rows) . " -->";
foreach ($rows as $row) {
  echo "<!-- Debug post ID {$row['id']}: status = {$row['status']} -->";
}

// -------------------- Categories for filter dropdown --------------------
$cats = [];
$catRes = $db->query("SELECT DISTINCT category FROM posts WHERE category IS NOT NULL AND category <> '' ORDER BY category ASC");
if ($catRes) {
  while ($r = $catRes->fetch_assoc()) $cats[] = (string)$r['category'];
}

$csrf = csrf_token();

function status_badge_post(string $status): string {
  $map = [
    'published' => 'success',
    'draft'     => 'secondary',
    'archived'  => 'warning',
    'trashed'   => 'danger',
  ];
  $cls = $map[$status] ?? 'dark';
  return '<span class="badge bg-' . $cls . '">' . h($status) . '</span>';
}
?>

<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
      <div>
        <h4 class="mb-0">Posts</h4>
        <div class="text-muted small">Manage blog posts (list, filter, edit, delete).</div>
      </div>
      <div class="d-flex gap-2">
        <?php if (isset($_GET['trashed']) && $_GET['trashed'] === '1'): ?>
          <div class="alert alert-success mb-0 py-2 px-3">Post moved to trash successfully.</div>
        <?php elseif (isset($_GET['restored']) && $_GET['restored'] === '1'): ?>
          <div class="alert alert-success mb-0 py-2 px-3">Post restored from trash successfully.</div>
        <?php endif; ?>
        <a class="btn btn-primary" href="add.php">+ New Post</a>
      </div>
    </div>

    <form class="card mb-3" method="get" action="">
      <div class="card-body">
        <div class="row g-2 align-items-end">
          <div class="col-12 col-md-4">
            <label class="form-label">Search</label>
            <input name="q" value="<?= h($q) ?>" class="form-control" placeholder="Title, slug, excerpt...">
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="">All</option>
              <?php foreach (['published','draft','archived','trashed'] as $s): ?>
                <option value="<?= h($s) ?>" <?= $status===$s?'selected':''; ?>><?= h(ucfirst($s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label">Category</label>
            <select name="category" class="form-select">
              <option value="">All</option>
              <?php foreach ($cats as $c): ?>
                <option value="<?= h($c) ?>" <?= $category===$c?'selected':''; ?>><?= h($c) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-md-2 d-flex gap-2">
            <button class="btn btn-primary w-100" type="submit">Filter</button>
            <a class="btn btn-outline-secondary w-100" href="list.php">Reset</a>
          </div>
        </div>
      </div>
    </form>

    <div class="card">
      <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="small text-muted">
          Showing <strong><?= (int)min($total, $offset + 1) ?></strong>–
          <strong><?= (int)min($total, $offset + $per_page) ?></strong>
          of <strong><?= (int)$total ?></strong>
        </div>
        <div class="small text-muted">
          Page <strong><?= (int)$page ?></strong> / <strong><?= (int)$total_pages ?></strong>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width:70px;">#</th>
              <th>Post</th>
              <th style="width:100px;">Featured</th>
              <th style="width:180px;">Category</th>
              <th style="width:140px;">Status</th>
              <th style="width:180px;">Published</th>
              <th style="width:180px;">Updated</th>
              <th style="width:190px;" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">No posts found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td class="text-muted"><?= (int)$r['id'] ?></td>

                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <?php if (!empty($r['cover_image'])): ?>
                        <img
                          src="<?= h(rtrim((string)BASE_URL, '/') . '/' . ltrim((string)$r['cover_image'], '/')) ?>"
                          alt=""
                          style="width:44px;height:44px;object-fit:cover;border-radius:10px;"
                        >
                      <?php else: ?>
                        <div style="width:44px;height:44px;border-radius:10px;" class="bg-light border d-flex align-items-center justify-content-center text-muted">
                          <span class="small">IMG</span>
                        </div>
                      <?php endif; ?>

                      <div>
                        <div class="fw-semibold"><?= h((string)$r['title']) ?></div>
                        <div class="small text-muted">
                          <code><?= h((string)$r['slug']) ?></code>
                        </div>
                      </div>
                    </div>
                  </td>

                  <td>
                    <?php if (($r['is_featured'] ?? 0) == 1): ?>
                      <span class="badge bg-warning">Featured</span>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>

                  <td><?= h((string)($r['category'] ?? '-')) ?></td>
                  <td><?= status_badge_post((string)($r['status'] ?? 'draft')) ?></td>

                  <td class="text-muted small">
                    <?= h((string)($r['published_at'] ?? '-')) ?>
                  </td>

                  <td class="text-muted small">
                    <?= h((string)($r['updated_at'] ?? $r['created_at'] ?? '')) ?>
                  </td>

                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-secondary"
                       href="edit.php?id=<?= (int)$r['id'] ?>">
                      Edit
                    </a>

                    <?php if (($r['status'] ?? 'draft') === 'trashed'): ?>
                      <!-- Restore button for trashed posts -->
                      <form class="d-inline" method="post" action="restore.php"
                            onsubmit="return confirm('Restore this post from trash?');">
                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn btn-sm btn-outline-success" type="submit">Restore</button>
                      </form>
                    <?php else: ?>
                      <!-- Trash button for non-trashed posts -->
                      <form class="d-inline" method="post" action="delete.php"
                            onsubmit="return confirm('Move this post to trash? You can restore it later.');">
                        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Trash</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php
        $qsBase = $_GET;
        unset($qsBase['page']);
        $qsBaseStr = http_build_query($qsBase);
        $link = function(int $p) use ($qsBaseStr) {
          $q = $qsBaseStr ? ($qsBaseStr . '&') : '';
          return 'list.php?' . $q . 'page=' . $p;
        };
      ?>

      <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="text-muted small">Per page: <?= (int)$per_page ?></div>

        <nav aria-label="Posts pagination">
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= h($link(1)) ?>">First</a>
            </li>
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= h($link(max(1, $page - 1))) ?>">Prev</a>
            </li>

            <?php
              $start = max(1, $page - 2);
              $end   = min($total_pages, $page + 2);
              for ($p = $start; $p <= $end; $p++):
            ?>
              <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                <a class="page-link" href="<?= h($link($p)) ?>"><?= (int)$p ?></a>
              </li>
            <?php endfor; ?>

            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= h($link(min($total_pages, $page + 1))) ?>">Next</a>
            </li>
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= h($link($total_pages)) ?>">Last</a>
            </li>
          </ul>
        </nav>
      </div>

    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
