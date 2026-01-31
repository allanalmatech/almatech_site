<?php
declare(strict_types=1);

// Early authentication check before any HTML output
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_admin_login();

require_once __DIR__ . '/../../includes/db.php';

$db = $GLOBALS['db'] ?? $mysqli ?? null;

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  flash_set('danger', 'Invalid testimonial selected.');
  redirect('list.php');
}

$stmt = $db->prepare("SELECT * FROM testimonials WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
  flash_set('danger', 'Testimonial not found.');
  redirect('list.php');
}

$errors = [];

$client_name = (string)($row['client_name'] ?? '');
$client_title = (string)($row['client_title'] ?? '');
$company = (string)($row['company'] ?? '');
$rating = (string)($row['rating'] ?? '');
$message = (string)$row['message'];
$status = (string)$row['status'];
$sort_order = (int)$row['sort_order'];
$project_id = (string)($row['project_id'] ?? '');
$show_project_link = (int)($row['show_project_link'] ?? 0);
$photo = (string)($row['photo'] ?? '');
// Debug: Log the photo filename retrieved from database
error_log("Testimonial photo filename from DB: " . $photo);

// Load projects list if exists
$projects = [];
try {
  $res = $db->query("SHOW TABLES LIKE 'projects'");
  if ($res && $res->num_rows > 0) {
    $r2 = $db->query("SELECT id, title FROM projects ORDER BY id DESC LIMIT 500");
    if ($r2) $projects = $r2->fetch_all(MYSQLI_ASSOC);
  }
} catch (Throwable $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = (string)($_POST['csrf_token'] ?? '');
  
  if (!csrf_validate($token)) {
    $errors[] = "Security check failed. Please refresh and try again.";
  } else {
    $client_name = trim((string)($_POST['client_name'] ?? ''));
    $client_title = trim((string)($_POST['client_title'] ?? ''));
    $company = trim((string)($_POST['company'] ?? ''));
    $rating = trim((string)($_POST['rating'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));
    $status = in_array((string)($_POST['status'] ?? ''), ['published','draft'], true) ? (string)$_POST['status'] : 'draft';
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    $show_project_link = isset($_POST['show_project_link']) ? 1 : 0;
    $project_id = trim((string)($_POST['project_id'] ?? ''));
    $pid = $project_id !== '' ? (int)$project_id : null;
    if ($show_project_link === 0) $pid = null;

    // Handle photo upload
    $newPhoto = $photo; // Keep existing photo by default
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
      $uploadDir = __DIR__ . '/../../uploads/testimonials';
      if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
      }
      
      $fileInfo = pathinfo($_FILES['photo']['name']);
      $extension = strtolower($fileInfo['extension'] ?? '');
      if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $filename = date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 8) . '.' . $extension;
        // Ensure filename is URL-safe
        $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $filename);
        $targetPath = $uploadDir . '/' . $filename;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
          // Delete old photo if exists
          if (!empty($photo)) {
            $oldPhotoPath = $uploadDir . '/' . $photo;
            if (file_exists($oldPhotoPath)) {
              unlink($oldPhotoPath);
            }
          }
          $newPhoto = $filename;
          // Debug: Log the filename being stored
          error_log("Testimonial photo filename stored: " . $filename);
        }
      }
    }
    if ($client_name === '' || $message === '') {
      $errors[] = "Client name and message are required.";
    } else {
      $ratingInt = null;
      if ($rating !== '') {
        $ri = (int)$rating;
        if ($ri < 1 || $ri > 5) {
          $errors[] = "Rating must be 1 to 5.";
        } else {
          $ratingInt = $ri;
        }
      }

      if (empty($errors)) {
        $sql = "UPDATE testimonials
        SET client_name=?, client_title=?, company=?, rating=?, message=?,
            project_id=?, show_project_link=?, status=?, sort_order=?, photo=?
        WHERE id=?";

$st2 = $db->prepare($sql);

$st2->bind_param(
  "sssisiisisi",
  $client_name,        // s
  $client_title,       // s
  $company,            // s
  $ratingInt,          // i (nullable)
  $message,            // s
  $pid,                // i (nullable)
  $show_project_link,  // i
  $status,             // s
  $sort_order,         // i
  $newPhoto,           // s  ✅ THIS WAS YOUR PROBLEM
  $id                  // i
);

$st2->execute();
$st2->close();

        flash_set('success', 'Testimonial updated successfully.');
        redirect('list.php');
        exit;
      }
    }
  }
}

$page_title = "Edit Testimonial | Admin";
$page_heading = "Edit Testimonial";
$page_subtitle = "Update client testimonial";
$active_admin = "testimonials";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>
<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-0">Edit Testimonial</h4>
        <small class="text-muted">Update client testimonial</small>
      </div>
      <a class="btn btn-outline-secondary" href="list.php">
        <i class="bi bi-arrow-left me-1"></i> Back
      </a>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Fix the following:</div>
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= h($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Client Name *</label>
        <input class="form-control" name="client_name" value="<?= htmlspecialchars($client_name) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Client Title</label>
        <input class="form-control" name="client_title" value="<?= htmlspecialchars($client_title) ?>">
      </div>

      <div class="col-md-6">
        <label class="form-label">Company</label>
        <input class="form-control" name="company" value="<?= htmlspecialchars($company) ?>">
      </div>

      <div class="col-md-3">
        <label class="form-label">Rating (1–5)</label>
        <input type="number" class="form-control" name="rating" value="<?= htmlspecialchars($rating) ?>" min="1" max="5">
      </div>

      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
          <option value="draft" <?= $status==='draft'?'selected':'' ?>>Draft</option>
          <option value="published" <?= $status==='published'?'selected':'' ?>>Published</option>
        </select>
      </div>

      <div class="col-md-3">
        <label class="form-label">Sort Order</label>
        <input type="number" class="form-control" name="sort_order" value="<?= (int)$sort_order ?>">
      </div>

      <div class="col-md-9">
        <label class="form-label">Current Photo</label>
        <div class="d-flex align-items-center gap-3">
          <div style="width:64px;height:64px;border-radius:50%;overflow:hidden;background:#f2f2f2;">
            <?php if ($photo): ?>
              <img src="<?= BASE_URL ?>uploads/testimonials/<?= htmlspecialchars($photo) ?>" alt="photo" style="width:100%;height:100%;object-fit:cover;" onerror="console.log('Image failed to load:', this.src);">
            <?php else: ?>
              <div class="h-100 d-flex align-items-center justify-content-center text-muted">
                <i class="bi bi-person"></i>
              </div>
            <?php endif; ?>
          </div>
          <div class="text-muted small">
            Upload a new file below to replace the current one.
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <label class="form-label">Replace Photo</label>
        <input class="form-control" type="file" name="photo" accept="image/*">
        <small class="text-muted">Optional: JPG, PNG, GIF, WebP</small>
      </div>

      <div class="col-12">
        <label class="form-label">Message *</label>
        <textarea class="form-control" name="message" rows="5" required><?= htmlspecialchars($message) ?></textarea>
      </div>

      <div class="col-12"><hr></div>

      <div class="col-md-4">
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" id="show_project_link" name="show_project_link" value="1" <?= $show_project_link === 1 ? 'checked' : '' ?>>
          <label class="form-check-label" for="show_project_link">Show project link</label>
        </div>
      </div>

      <div class="col-md-8">
        <label class="form-label">Select Project (optional)</label>
        <select class="form-select" name="project_id">
          <option value="">-- None --</option>
          <?php foreach ($projects as $p): ?>
            <option value="<?= (int)$p['id'] ?>" <?= ((string)$p['id'] === (string)$project_id) ? 'selected':'' ?>>
              <?= htmlspecialchars((string)$p['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="mt-3 d-flex gap-2">
      <button class="btn btn-orange">
        <i class="bi bi-save me-1"></i> Update
      </button>
      <a class="btn btn-outline-secondary" href="list.php">Cancel</a>
    </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
