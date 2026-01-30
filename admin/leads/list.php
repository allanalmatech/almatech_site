<?php
// admin/leads/list.php
declare(strict_types=1);

// Early authentication check before any HTML output
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_admin_login();

$page_title    = "Leads | Admin";
$page_heading  = "Leads";
$page_subtitle = "Manage enquiries captured from the website";
$active_admin  = "leads";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
require_once __DIR__ . '/../../includes/db.php';

csrf_init();

$db = $GLOBALS['db'] ?? $mysqli ?? null;
if (!($db instanceof mysqli)) {
  http_response_code(500);
  exit('DB not available');
}

$base = rtrim((string)($GLOBALS['BASE_URL'] ?? ''), '/');

// -------------------- Filters --------------------
$q       = trim((string)($_GET['q'] ?? ''));
$status  = trim((string)($_GET['status'] ?? ''));   // new|contacted|won|lost|spam
$service = trim((string)($_GET['service'] ?? ''));
$from    = trim((string)($_GET['from'] ?? ''));     // YYYY-MM-DD
$to      = trim((string)($_GET['to'] ?? ''));       // YYYY-MM-DD

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$per_page = 15;
$offset   = ($page - 1) * $per_page;

$allowedStatus = ['new','contacted','won','lost','spam'];
if ($status !== '' && !in_array($status, $allowedStatus, true)) $status = '';

$where  = "1=1";
$types  = "";
$params = [];

// Search (name/email/phone/service/message)
if ($q !== '') {
  $where .= " AND (
    l.name LIKE CONCAT('%', ?, '%')
    OR l.email LIKE CONCAT('%', ?, '%')
    OR l.phone LIKE CONCAT('%', ?, '%')
    OR l.service LIKE CONCAT('%', ?, '%')
    OR l.message LIKE CONCAT('%', ?, '%')
  )";
  $types .= "sssss";
  array_push($params, $q, $q, $q, $q, $q);
}

if ($status !== '') {
  $where .= " AND l.status = ?";
  $types .= "s";
  $params[] = $status;
}

if ($service !== '') {
  $where .= " AND l.service = ?";
  $types .= "s";
  $params[] = $service;
}

// Date range (created_at)
if ($from !== '') {
  $where .= " AND DATE(l.created_at) >= ?";
  $types .= "s";
  $params[] = $from;
}
if ($to !== '') {
  $where .= " AND DATE(l.created_at) <= ?";
  $types .= "s";
  $params[] = $to;
}

// -------------------- Total count --------------------
$sqlCount = "SELECT COUNT(*) AS cnt FROM leads l WHERE $where";
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
    l.id, l.name, l.email, l.phone, l.service, l.status,
    l.source, l.created_at
  FROM leads l
  WHERE $where
  ORDER BY l.created_at DESC
  LIMIT ? OFFSET ?
";
$stmt = $db->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  exit("DB error: " . $db->error);
}

$types2  = $types . "ii";
$params2 = array_merge($params, [$per_page, $offset]);
$stmt->bind_param($types2, ...$params2);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// -------------------- Services for dropdown --------------------
$services = [];
$sRes = $db->query("SELECT DISTINCT service FROM leads WHERE service IS NOT NULL AND service <> '' ORDER BY service ASC");
if ($sRes) {
  while ($r = $sRes->fetch_assoc()) $services[] = (string)$r['service'];
}

$csrf = csrf_token();

function status_badge(string $s): string {
  $map = [
    'new'       => 'primary',
    'contacted' => 'info',
    'won'       => 'success',
    'lost'      => 'secondary',
    'spam'      => 'danger',
  ];
  $cls = $map[$s] ?? 'dark';
  return '<span class="badge bg-' . $cls . '">' . h($s) . '</span>';
}
?>

<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
      <div>
        <h4 class="mb-0">Leads</h4>
        <div class="text-muted small">Website enquiries (search, filter, view, delete).</div>
      </div>
      <div class="d-flex gap-2">
  <a class="btn btn-outline-secondary"
     href="<?= h($base) ?>/contact.php"
     target="_blank"
     rel="noopener">
    Open Contact Page
  </a>

  <a class="btn btn-primary"
     href="add.php">
    <i class="bi bi-plus-circle me-1"></i> Add Lead
  </a>
</div>

    </div>

    <form class="card mb-3" method="get" action="">
      <div class="card-body">
        <div class="row g-2 align-items-end">
          <div class="col-12 col-md-4">
            <label class="form-label">Search</label>
            <input name="q" value="<?= h($q) ?>" class="form-control" placeholder="Name, email, phone, service, message…">
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="">All</option>
              <?php foreach (['new','contacted','won','lost','spam'] as $s): ?>
                <option value="<?= h($s) ?>" <?= $status===$s?'selected':''; ?>><?= h(ucfirst($s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-6 col-md-3">
            <label class="form-label">Service</label>
            <select name="service" class="form-select">
              <option value="">All</option>
              <?php foreach ($services as $sv): ?>
                <option value="<?= h($sv) ?>" <?= $service===$sv?'selected':''; ?>><?= h($sv) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-6 col-md-1">
            <label class="form-label">From</label>
            <input type="date" name="from" value="<?= h($from) ?>" class="form-control">
          </div>

          <div class="col-6 col-md-1">
            <label class="form-label">To</label>
            <input type="date" name="to" value="<?= h($to) ?>" class="form-control">
          </div>

          <div class="col-12 col-md-1 d-flex gap-2">
            <button class="btn btn-primary w-100" type="submit">Go</button>
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
              <th>Lead</th>
              <th style="width:200px;">Service</th>
              <th style="width:140px;">Status</th>
              <th style="width:140px;">Source</th>
              <th style="width:190px;">Date</th>
              <th style="width:210px;" class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">No leads found.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td class="text-muted"><?= (int)$r['id'] ?></td>

                  <td>
                    <div class="fw-semibold"><?= h((string)($r['name'] ?? '')) ?></div>
                    <div class="small text-muted">
                      <?php if (!empty($r['email'])): ?>
                        <i class="bi bi-envelope me-1"></i><?= h((string)$r['email']) ?>
                      <?php endif; ?>
                      <?php if (!empty($r['phone'])): ?>
                        <span class="ms-2"><i class="bi bi-telephone me-1"></i><?= h((string)$r['phone']) ?></span>
                      <?php endif; ?>
                    </div>
                  </td>

                  <td><?= h((string)($r['service'] ?? '-')) ?></td>
                  <td>
                    <select class="form-select form-select-sm status-dropdown" 
                            data-lead-id="<?= (int)$r['id'] ?>"
                            onchange="updateLeadStatus(this)">
                      <?php foreach (['new','contacted','won','lost','spam'] as $s): ?>
                        <option value="<?= h($s) ?>" <?= ($r['status'] ?? 'new') === $s ? 'selected' : '' ?>>
                          <?= h(ucfirst($s)) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </td>
                  <td class="text-muted"><?= h((string)($r['source'] ?? 'website')) ?></td>
                  <td class="text-muted small"><?= h((string)($r['created_at'] ?? '')) ?></td>

                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-secondary"
                       href="view.php?id=<?= (int)$r['id'] ?>">
                      View
                    </a>

                    <form class="d-inline" method="post" action="delete.php"
                          onsubmit="return confirm('Delete this lead? This cannot be undone.');">
                      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php
        // Pagination links (preserve filters)
        $qsBase = $_GET;
        unset($qsBase['page']);
        $qsBaseStr = http_build_query($qsBase);
        $link = function(int $p) use ($qsBaseStr) {
          $q = $qsBaseStr ? ($qsBaseStr . '&') : '';
          return 'list.php?' . $q . 'page=' . $p;
        };

        $start = max(1, $page - 2);
        $end   = min($total_pages, $page + 2);
      ?>

      <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="text-muted small">Per page: <?= (int)$per_page ?></div>

        <nav aria-label="Leads pagination">
          <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= h($link(1)) ?>">First</a>
            </li>
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="<?= h($link(max(1, $page - 1))) ?>">Prev</a>
            </li>

            <?php for ($p = $start; $p <= $end; $p++): ?>
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

<script>
  // CSRF token from PHP
  const CSRF_TOKEN = <?= json_encode($csrf) ?>;

  // store original status for all dropdowns on load
  document.querySelectorAll('.status-dropdown').forEach(sel => {
    sel.dataset.originalValue = sel.value;
  });

  // Update lead status via AJAX
  function updateLeadStatus(selectElement) {
    const leadId = selectElement.dataset.leadId;
    const newStatus = selectElement.value;
    const oldStatus = selectElement.dataset.originalValue;

    // UI loading state
    selectElement.disabled = true;
    selectElement.classList.add('opacity-50');

    fetch('update_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        id: leadId,
        status: newStatus,
        csrf: CSRF_TOKEN
      })
    })
    .then(r => r.json())
    .then(data => {
      if (!data || !data.ok) {
        // revert if failed
        selectElement.value = oldStatus;
        flashSelect(selectElement, false);
        alert('Failed to update status: ' + (data?.error || 'Unknown error'));
        return;
      }

      // success: commit new status as original
      selectElement.dataset.originalValue = newStatus;
      flashSelect(selectElement, true);
    })
    .catch(() => {
      // revert on network error
      selectElement.value = oldStatus;
      flashSelect(selectElement, false);
      alert('Network error: Failed to update status');
    })
    .finally(() => {
      selectElement.disabled = false;
      selectElement.classList.remove('opacity-50');
    });
  }

  // small visual feedback (no hard-coded colors)
  function flashSelect(el, success) {
    el.classList.remove('border-success', 'border-danger');

    if (success) {
      el.classList.add('border-success');
      setTimeout(() => el.classList.remove('border-success'), 1200);
    } else {
      el.classList.add('border-danger');
      setTimeout(() => el.classList.remove('border-danger'), 2000);
    }
  }
</script>


<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
