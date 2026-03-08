<?php
declare(strict_types=1);

// Early authentication check before any HTML output
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_admin_login();

require_once __DIR__ . '/../../includes/db.php';
csrf_init();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  flash_set('danger', 'Invalid service selected.');
  redirect('list.php');
}

// Load record
$stmt = $mysqli->prepare("SELECT * FROM services WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$service = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$service) {
  flash_set('danger', 'Service not found.');
  redirect('list.php');
}

$title = (string)$service['title'];
$short_desc = (string)$service['short_desc'];
$description = (string)$service['description'];
$is_active = (int)$service['is_active'] === 1 ? 1 : 0;
$current_icon = (string)($service['icon'] ?? '');

$errors = [];

// Handle POST (save) - BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = (string)($_POST['csrf_token'] ?? '');
  
  if (!csrf_validate($token)) {
    $errors[] = "Security check failed. Refresh and try again.";
  } else {
    $title = trim((string)($_POST['title'] ?? ''));
    $short_desc = trim((string)($_POST['short_desc'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '') $errors[] = "Service title is required.";
    if ($short_desc === '') $errors[] = "Short description is required.";
    if ($description === '') $errors[] = "Full description is required.";

    // Handle image upload
    $new_icon = $current_icon;
    if (!$errors && !empty($_FILES['icon']['name'])) {
      $ext = strtolower(pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION));
      $allowed = ['png','jpg','jpeg','svg','webp'];

      if (!in_array($ext, $allowed, true)) {
        $errors[] = "Invalid image format.";
      } else {
        $dir = __DIR__ . '/../../uploads/services/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $new_icon = uniqid('service_', true) . '.' . $ext;
        $dest = $dir . $new_icon;

        if (!move_uploaded_file($_FILES['icon']['tmp_name'], $dest)) {
          $errors[] = "Image upload failed.";
        } else {
          // Delete old icon if different
          if ($current_icon && $current_icon !== $new_icon) {
            $old_path = $dir . $current_icon;
            if (file_exists($old_path)) {
              unlink($old_path);
            }
          }
        }
      }
    }

    if (!$errors) {
      $slug = slugify($title);

      // Check if slug conflicts with other services
      $stmt = $mysqli->prepare("SELECT id FROM services WHERE slug = ? AND id != ? LIMIT 1");
      $stmt->bind_param("si", $slug, $id);
      $stmt->execute();
      $check = $stmt->get_result();
      $exists = $check ? $check->fetch_assoc() : null;
      $stmt->close();

      if ($exists) {
        $errors[] = "A service with this title already exists. Choose a different title.";
      } else {
        $stmt = $mysqli->prepare(
          "UPDATE services SET title = ?, slug = ?, short_desc = ?, description = ?, icon = ?, is_active = ? WHERE id = ?"
        );
        $stmt->bind_param("sssssii", $title, $slug, $short_desc, $description, $new_icon, $is_active, $id);
        $stmt->execute();
        $stmt->close();

        flash_set("success", "Service updated successfully.");
        redirect("list.php"); // ✅ Safe - no HTML output yet
      }
    }
  }
}

$page_title = "Edit Service | Admin";
$page_heading = "Edit Service";
$page_subtitle = "Update service details";
$active_admin = "services";

// HTML layout only after all processing is complete
require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';

$iconUrl = ($current_icon !== '') ? ('../../uploads/services/' . $current_icon) : '';
$iconPath = ($current_icon !== '') ? (__DIR__ . '/../../uploads/services/' . $current_icon) : '';
$hasIcon = ($current_icon !== '' && is_file($iconPath));
?>

<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <div class="fw-bold">Edit Service</div>
        <div class="small text-muted">Update details and save changes.</div>
      </div>
      <a class="btn btn-outline-secondary" href="list.php">Back</a>
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

    <form method="post" enctype="multipart/form-data" class="row g-3">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

      <div class="col-12">
        <label class="form-label fw-semibold">Service Title</label>
        <input type="text" name="title" class="form-control" value="<?= h($title) ?>" placeholder="e.g. Website Design">
      </div>

      <div class="col-12">
        <label class="form-label fw-semibold">Short Description</label>
        <input type="text" name="short_desc" class="form-control" value="<?= h($short_desc) ?>" placeholder="Shown on cards">
      </div>

      <div class="col-12">
        <label class="form-label fw-semibold">Full Description</label>
        <textarea name="description" class="form-control" rows="6" placeholder="Detailed description..."><?= h($description) ?></textarea>
      </div>

      <div class="col-12">
        <label class="form-label fw-semibold">Current Icon / Image</label>
        <div class="d-flex align-items-center gap-3">
          <div style="width:64px;height:64px;border-radius:16px;border:1px solid rgba(15,23,42,.08);background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;">
            <?php if ($hasIcon): ?>
              <img src="<?= h($iconUrl) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
              <i class="bi bi-image text-muted"></i>
            <?php endif; ?>
          </div>
          <div class="text-muted small">
            Upload a new file below to replace the current one.
          </div>
        </div>
      </div>

      <div class="col-12">
        <label class="form-label fw-semibold">Replace Icon / Image (optional)</label>
        <input type="file" name="icon" class="form-control">
        <div class="form-text">Allowed: png, jpg, jpeg, svg, webp</div>
      </div>

      <div class="col-12">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_active" id="activeCheck" <?= $is_active ? 'checked' : '' ?>>
          <label class="form-check-label" for="activeCheck">
            Active (visible on website)
          </label>
        </div>
      </div>

      <div class="col-12 d-flex gap-2 pt-2">
        <button class="btn btn-orange">
          <i class="bi bi-save me-1"></i> Save Changes
        </button>
        <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
      </div>

    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
