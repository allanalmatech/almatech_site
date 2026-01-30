<?php
// admin/leads/add.php
declare(strict_types=1);

$page_title    = "Add Lead | Admin";
$page_heading  = "Leads";
$page_subtitle = "Manually add a new lead";
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

$allowedStatus = ['new','contacted','won','lost','spam'];

$errors = [];
$ok_msg = '';

$data = [
  'name'    => '',
  'phone'   => '',
  'email'   => '',
  'service' => '',
  'subject' => '',
  'message' => '',
  'status'  => 'new',
  'source'  => 'admin',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $data['name']    = trim((string)($_POST['name'] ?? ''));
  $data['phone']   = trim((string)($_POST['phone'] ?? ''));
  $data['email']   = trim((string)($_POST['email'] ?? ''));
  $data['service'] = trim((string)($_POST['service'] ?? ''));
  $data['subject'] = trim((string)($_POST['subject'] ?? ''));
  $data['message'] = trim((string)($_POST['message'] ?? ''));
  $data['status']  = trim((string)($_POST['status'] ?? 'new'));
  $data['source']  = trim((string)($_POST['source'] ?? 'admin'));

  if ($data['name'] === '') $errors[] = "Name is required.";
  if ($data['status'] === '' || !in_array($data['status'], $allowedStatus, true)) $errors[] = "Invalid status.";

  if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email address.";
  }

  if (empty($errors)) {
    // supports both schemas:
    // - without updated_at (your current table)
    // - with updated_at (recommended)
    $hasUpdatedAt = false;
    $colRes = $db->query("SHOW COLUMNS FROM leads LIKE 'updated_at'");
    if ($colRes && $colRes->num_rows === 1) $hasUpdatedAt = true;

    if ($hasUpdatedAt) {
      $stmt = $db->prepare("
        INSERT INTO leads (name, phone, email, service, subject, message, status, source, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NULL)
      ");
      if ($stmt) {
        $stmt->bind_param(
          "ssssssss",
          $data['name'],
          $data['phone'],
          $data['email'],
          $data['service'],
          $data['subject'],
          $data['message'],
          $data['status'],
          $data['source']
        );
      }
    } else {
      $stmt = $db->prepare("
        INSERT INTO leads (name, phone, email, service, subject, message, status, source, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
      ");
      if ($stmt) {
        $stmt->bind_param(
          "ssssssss",
          $data['name'],
          $data['phone'],
          $data['email'],
          $data['service'],
          $data['subject'],
          $data['message'],
          $data['status'],
          $data['source']
        );
      }
    }

    if (!$stmt) {
      $errors[] = "DB error: " . $db->error;
    } else {
      if ($stmt->execute()) {
        $newId = (int)$stmt->insert_id;
        $stmt->close();
        header("Location: view.php?id=" . $newId);
        exit;
      } else {
        $errors[] = "Failed to create lead.";
        $stmt->close();
      }
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
        <h4 class="mb-1">Add Lead</h4>
        <div class="text-muted small">Manually create a lead (phone call, walk-in, WhatsApp, etc.).</div>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="list.php">← Back</a>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= h($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form class="card" method="post" action="">
      <div class="card-body">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

        <div class="row g-3">
          <div class="col-12 col-md-6">
            <label class="form-label">Name *</label>
            <input class="form-control" name="name" value="<?= h($data['name']) ?>" required>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Phone</label>
            <input class="form-control" name="phone" value="<?= h($data['phone']) ?>" placeholder="+2567...">
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Email</label>
            <input class="form-control" name="email" value="<?= h($data['email']) ?>" placeholder="name@example.com">
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Service</label>
            <input class="form-control" name="service" value="<?= h($data['service']) ?>" placeholder="Website Design / Marketing / IT Support">
          </div>

          <div class="col-12">
            <label class="form-label">Subject</label>
            <input class="form-control" name="subject" value="<?= h($data['subject']) ?>" placeholder="Short summary">
          </div>

          <div class="col-12">
            <label class="form-label">Message</label>
            <textarea class="form-control" name="message" rows="7" placeholder="Full enquiry..."><?= h($data['message']) ?></textarea>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Status *</label>
            <select class="form-select" name="status" required>
              <?php foreach ($allowedStatus as $s): ?>
                <option value="<?= h($s) ?>" <?= $data['status']===$s?'selected':''; ?>>
                  <?= h(ucfirst($s)) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label">Source</label>
            <input class="form-control" name="source" value="<?= h($data['source']) ?>" placeholder="admin / call / whatsapp / walk-in">
          </div>
        </div>
      </div>

      <div class="card-footer d-flex justify-content-between">
        <a class="btn btn-outline-secondary" href="list.php">Cancel</a>
        <button class="btn btn-primary" type="submit">Create Lead</button>
      </div>
    </form>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
