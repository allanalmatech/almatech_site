<?php
// admin/leads/edit.php
declare(strict_types=1);

$page_title    = "Edit Lead | Admin";
$page_heading  = "Leads";
$page_subtitle = "Update lead details";
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

$errors = [];
$ok_msg = '';

// -------------------- Load lead --------------------
$stmt = $db->prepare("
  SELECT id, name, phone, email, service, subject, message, status, source, created_at
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
$stmt->bind_result($rid, $name, $phone, $email, $service, $subject, $message, $status, $source, $created_at);

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
  ];
}
$stmt->close();

if (!$lead) {
  http_response_code(404);
  exit('Lead not found');
}

// -------------------- Handle update --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $name    = trim((string)($_POST['name'] ?? ''));
  $phone   = trim((string)($_POST['phone'] ?? ''));
  $email   = trim((string)($_POST['email'] ?? ''));
  $service = trim((string)($_POST['service'] ?? ''));
  $subject = trim((string)($_POST['subject'] ?? ''));
  $message = trim((string)($_POST['message'] ?? ''));
  $status  = trim((string)($_POST['status'] ?? 'new'));
  $source  = trim((string)($_POST['source'] ?? 'website'));

  if ($name === '') $errors[] = "Name is required.";
  if ($status === '' || !in_array($status, $allowedStatus, true)) $errors[] = "Invalid status.";

  if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email address.";
  }

  if (empty($errors)) {
    $up = $db->prepare("
      UPDATE leads
      SET name=?, phone=?, email=?, service=?, subject=?, message=?, status=?, source=?, updated_at=NOW()
      WHERE id=?
      LIMIT 1
    ");
    if (!$up) {
      $errors[] = "DB error: " . $db->error;
    } else {
      $up->bind_param("ssssssssi", $name, $phone, $email, $service, $subject, $message, $status, $source, $id);
      if ($up->execute()) {
        $ok_msg = "Lead updated successfully.";
        // refresh local copy
        $lead['name'] = $name;
        $lead['phone'] = $phone;
        $lead['email'] = $email;
        $lead['service'] = $service;
        $lead['subject'] = $subject;
        $lead['message'] = $message;
        $lead['status'] = $status;
        $lead['source'] = $source;
      } else {
        $errors[] = "Failed to update lead.";
      }
      $up->close();
    }
  }
}

$csrf = csrf_token();
?>

<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
      <div>
        <h4 class="mb-1">Edit Lead</h4>
        <div class="text-muted small">Update details and status for this enquiry.</div>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="list.php">← Back</a>
        <a class="btn btn-outline-secondary" href="view.php?id=<?= (int)$lead['id'] ?>">View</a>
      </div>
    </div>

    <?php if (!empty($ok_msg)): ?>
      <div class="alert alert-success"><?= h($ok_msg) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= h($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="row g-3">
      <div class="col-12 col-lg-4">
        <div class="card">
          <div class="card-body">
            <div class="fw-semibold mb-1">Lead #<?= (int)$lead['id'] ?></div>
            <div class="small text-muted mb-2">
              <div><i class="bi bi-clock me-1"></i><?= h($lead['created_at']) ?></div>
              <div><i class="bi bi-link-45deg me-1"></i>Source: <?= h($lead['source'] ?: 'website') ?></div>
            </div>

            <div class="d-flex flex-wrap gap-2">
              <span class="badge bg-secondary"><?= h($lead['service'] ?: '-') ?></span>
              <span class="badge bg-info"><?= h($lead['status']) ?></span>
            </div>

            <hr>

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

      <div class="col-12 col-lg-8">
        <form class="card" method="post" action="">
          <div class="card-body">
            <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

            <div class="row g-3">
              <div class="col-12 col-md-6">
                <label class="form-label">Name *</label>
                <input class="form-control" name="name" value="<?= h($lead['name']) ?>" required>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Phone</label>
                <input class="form-control" name="phone" value="<?= h($lead['phone']) ?>">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Email</label>
                <input class="form-control" name="email" value="<?= h($lead['email']) ?>">
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Service</label>
                <input class="form-control" name="service" value="<?= h($lead['service']) ?>">
              </div>

              <div class="col-12">
                <label class="form-label">Subject</label>
                <input class="form-control" name="subject" value="<?= h($lead['subject']) ?>">
              </div>

              <div class="col-12">
                <label class="form-label">Message</label>
                <textarea class="form-control" name="message" rows="7"><?= h($lead['message']) ?></textarea>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Status *</label>
                <select class="form-select" name="status" required>
                  <?php foreach ($allowedStatus as $s): ?>
                    <option value="<?= h($s) ?>" <?= $lead['status']===$s?'selected':''; ?>>
                      <?= h(ucfirst($s)) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 col-md-6">
                <label class="form-label">Source</label>
                <input class="form-control" name="source" value="<?= h($lead['source']) ?>" placeholder="website / contact-form / whatsapp / call">
              </div>
            </div>
          </div>

          <div class="card-footer d-flex justify-content-between">
            <a class="btn btn-outline-secondary" href="list.php">Cancel</a>
            <button class="btn btn-primary" type="submit">Save Changes</button>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
