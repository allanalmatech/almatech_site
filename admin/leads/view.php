<?php
// admin/leads/view.php
declare(strict_types=1);

$page_title    = "View Lead | Admin";
$page_heading  = "Leads";
$page_subtitle = "View enquiry details";
$active_admin  = "leads";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';

csrf_init();

$db = $GLOBALS['db'] ?? $mysqli ?? null;
if (!($db instanceof mysqli)) {
  http_response_code(500);
  exit('DB not available');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  exit('Invalid lead id');
}

$allowedStatus = ['new','contacted','won','lost','spam'];

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

// -------------------- Handle quick status update (POST) --------------------
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $newStatus = trim((string)($_POST['status'] ?? ''));
  if (!in_array($newStatus, $allowedStatus, true)) {
    $flash = ['type' => 'danger', 'msg' => 'Invalid status'];
  } else {
    $up = $db->prepare("UPDATE leads SET status = ?, updated_at = NOW() WHERE id = ? LIMIT 1");
    if (!$up) {
      $flash = ['type' => 'danger', 'msg' => 'DB error: ' . $db->error];
    } else {
      $up->bind_param("si", $newStatus, $id);
      if ($up->execute()) {
        $flash = ['type' => 'success', 'msg' => 'Status updated'];
      } else {
        $flash = ['type' => 'danger', 'msg' => 'Failed to update status'];
      }
      $up->close();
    }
  }
}

// -------------------- Load lead --------------------
$stmt = $db->prepare("
  SELECT id, name, phone, email, service, subject, message, status, source, created_at, updated_at
  FROM leads
  WHERE id = ?
  LIMIT 1
");
if (!$stmt) {
  http_response_code(500);
  exit('DB error: ' . $db->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($rid, $name, $phone, $email, $service, $subject, $message, $status, $source, $created_at, $updated_at);

$lead = null;
if ($stmt->fetch()) {
  $lead = [
    'id' => (int)$rid,
    'name' => (string)($name ?? ''),
    'phone' => (string)($phone ?? ''),
    'email' => (string)($email ?? ''),
    'service' => (string)($service ?? ''),
    'subject' => (string)($subject ?? ''),
    'message' => (string)($message ?? ''),
    'status' => (string)($status ?? 'new'),
    'source' => (string)($source ?? 'website'),
    'created_at' => (string)($created_at ?? ''),
    'updated_at' => (string)($updated_at ?? ''),
  ];
}
$stmt->close();

if (!$lead) {
  http_response_code(404);
  exit('Lead not found');
}

$csrf = csrf_token();

// WhatsApp helper
$waPhone = preg_replace('/\D+/', '', $lead['phone']);
if ($waPhone !== '' && str_starts_with($waPhone, '0')) {
  // optional local formatting; you may prefer +256 format in DB
}
$waText = "Hello " . $lead['name'] . ", we received your enquiry about " . ($lead['service'] ?: 'our services') . ". How can we help?";
$waLink = ($waPhone !== '')
  ? ("https://wa.me/" . $waPhone . "?text=" . urlencode($waText))
  : "";

// Mailto helper
$mailSubject = $lead['subject'] ?: ("Re: " . ($lead['service'] ?: "Your enquiry"));
$mailBody = "Hello " . $lead['name'] . ",\n\nThanks for reaching out. We received your message:\n\n" . $lead['message'] . "\n\nRegards,\nAlma Tech Consults";
$mailTo = ($lead['email'] !== '')
  ? ("mailto:" . rawurlencode($lead['email']) . "?subject=" . rawurlencode($mailSubject) . "&body=" . rawurlencode($mailBody))
  : "";
?>

<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
      <div>
        <h4 class="mb-1">Lead #<?= (int)$lead['id'] ?></h4>
        <div class="text-muted small">View details and take action (status, reply, delete).</div>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="list.php">← Back</a>
        <a class="btn btn-outline-secondary" href="edit.php?id=<?= (int)$lead['id'] ?>">Edit</a>
      </div>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['msg']) ?></div>
    <?php endif; ?>

    <div class="row g-3">
      <!-- Left: lead summary -->
      <div class="col-12 col-lg-4">
        <div class="card">
          <div class="card-body">
            <div class="fw-semibold mb-1"><?= h($lead['name']) ?></div>

            <div class="small text-muted mb-2">
              <div class="mb-1">
                <i class="bi bi-tag me-1"></i>
                <?= h($lead['service'] ?: '-') ?>
              </div>
              <div class="mb-1">
                <i class="bi bi-clock me-1"></i>
                Created: <?= h($lead['created_at']) ?>
              </div>
              <?php if (!empty($lead['updated_at'])): ?>
                <div class="mb-1">
                  <i class="bi bi-arrow-repeat me-1"></i>
                  Updated: <?= h($lead['updated_at']) ?>
                </div>
              <?php endif; ?>
              <div class="mb-1">
                <i class="bi bi-link-45deg me-1"></i>
                Source: <?= h($lead['source'] ?: 'website') ?>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2 mb-3">
              <?= status_badge($lead['status']) ?>
              <?php if ($lead['email'] !== ''): ?>
                <span class="badge bg-light text-dark border">
                  <i class="bi bi-envelope me-1"></i><?= h($lead['email']) ?>
                </span>
              <?php endif; ?>
              <?php if ($lead['phone'] !== ''): ?>
                <span class="badge bg-light text-dark border">
                  <i class="bi bi-telephone me-1"></i><?= h($lead['phone']) ?>
                </span>
              <?php endif; ?>
            </div>

            <div class="d-grid gap-2">
              <?php if ($mailTo !== ''): ?>
                <a class="btn btn-outline-primary" href="<?= h($mailTo) ?>">
                  <i class="bi bi-envelope me-1"></i>Email Reply
                </a>
              <?php else: ?>
                <button class="btn btn-outline-primary" type="button" disabled>
                  <i class="bi bi-envelope me-1"></i>Email Reply
                </button>
              <?php endif; ?>

              <?php if ($waLink !== ''): ?>
                <a class="btn btn-outline-success" target="_blank" rel="noopener" href="<?= h($waLink) ?>">
                  <i class="bi bi-whatsapp me-1"></i>WhatsApp
                </a>
              <?php else: ?>
                <button class="btn btn-outline-success" type="button" disabled>
                  <i class="bi bi-whatsapp me-1"></i>WhatsApp
                </button>
              <?php endif; ?>

              <?php if ($lead['phone'] !== ''): ?>
                <a class="btn btn-outline-secondary" href="tel:<?= h($lead['phone']) ?>">
                  <i class="bi bi-telephone me-1"></i>Call
                </a>
              <?php else: ?>
                <button class="btn btn-outline-secondary" type="button" disabled>
                  <i class="bi bi-telephone me-1"></i>Call
                </button>
              <?php endif; ?>
            </div>

            <hr>

            <form method="post" action="" class="mb-3">
              <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
              <label class="form-label">Quick status</label>
              <div class="d-flex gap-2">
                <select class="form-select" name="status">
                  <?php foreach ($allowedStatus as $s): ?>
                    <option value="<?= h($s) ?>" <?= $lead['status']===$s?'selected':''; ?>>
                      <?= h(ucfirst($s)) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" type="submit">Update</button>
              </div>
            </form>

            <form method="post" action="delete.php" onsubmit="return confirm('Delete this lead? This cannot be undone.');">
              <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
              <input type="hidden" name="id" value="<?= (int)$lead['id'] ?>">
              <button class="btn btn-outline-danger w-100" type="submit">
                Delete Lead
              </button>
            </form>
          </div>
        </div>
      </div>

      <!-- Right: message details -->
      <div class="col-12 col-lg-8">
        <div class="card">
          <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
              <div>
                <div class="fw-semibold">Subject</div>
                <div class="text-muted"><?= h($lead['subject'] ?: '—') ?></div>
              </div>
              <div class="text-end">
                <div class="fw-semibold">Status</div>
                <div><?= status_badge($lead['status']) ?></div>
              </div>
            </div>

            <div class="fw-semibold mb-2">Message</div>

            <?php if ($lead['message'] === ''): ?>
              <div class="text-muted">No message provided.</div>
            <?php else: ?>
              <div class="p-3 border rounded-3 bg-light" style="white-space:pre-wrap;">
                <?= h($lead['message']) ?>
              </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-2 mt-3">
              <a class="btn btn-outline-secondary" href="edit.php?id=<?= (int)$lead['id'] ?>">
                <i class="bi bi-pencil-square me-1"></i>Edit Lead
              </a>
              <a class="btn btn-outline-secondary" href="list.php">
                <i class="bi bi-list-ul me-1"></i>All Leads
              </a>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
