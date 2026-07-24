<?php
declare(strict_types=1);

// Early authentication check before any HTML output
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_admin_login();

require_once __DIR__ . '/../../includes/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();
csrf_init();

$title = $short_desc = $description = '';
$is_active = 1;
$errors = [];

/* ✅ HANDLE POST BEFORE ANY HTML OUTPUT */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (!csrf_validate((string)($_POST['csrf_token'] ?? ''))) {
    $errors[] = "Security check failed. Refresh and try again.";
  }

  $title = trim((string)($_POST['title'] ?? ''));
  $short_desc = trim((string)($_POST['short_desc'] ?? ''));
  $description = trim((string)($_POST['description'] ?? ''));
  $is_active = isset($_POST['is_active']) ? 1 : 0;

  if ($title === '') $errors[] = "Service title is required.";
  if ($short_desc === '') $errors[] = "Short description is required.";
  if ($description === '') $errors[] = "Full description is required.";

  // Optional upload
  $icon = null;
  if (!$errors && !empty($_FILES['icon']['name'])) {
    $ext = strtolower(pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION));
    $allowed = ['png','jpg','jpeg','svg','webp'];

    if (!in_array($ext, $allowed, true)) {
      $errors[] = "Invalid image format.";
    } else {
      $dir = __DIR__ . '/../../uploads/services/';
      if (!is_dir($dir)) mkdir($dir, 0755, true);

      $icon = uniqid('service_', true) . '.' . $ext;
      $dest = $dir . $icon;

      if (!move_uploaded_file($_FILES['icon']['tmp_name'], $dest)) {
        $errors[] = "Image upload failed.";
      }
    }
  }

  if (!$errors) {
    $slug = slugify($title);

    // ensure unique slug
    $stmt = $mysqli->prepare("SELECT id FROM services WHERE slug = ? LIMIT 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $check = $stmt->get_result();
    $exists = $check ? $check->fetch_assoc() : null;
    $stmt->close();

    if ($exists) {
      $errors[] = "A service with this title already exists. Change the title.";
    } else {
      $stmt = $mysqli->prepare(
        "INSERT INTO services (title, slug, short_desc, description, icon, is_active)
         VALUES (?,?,?,?,?,?)"
      );
      $stmt->bind_param("sssssi", $title, $slug, $short_desc, $description, $icon, $is_active);
      $stmt->execute();
      $stmt->close();

      flash_set("success", "Service added successfully.");
      redirect("list.php"); // ✅ now safe, no output yet
    }
  }
}

/* ✅ NOW LOAD LAYOUT */
$page_title = "Add Service | Admin";
$page_heading = "Add New Service";
$page_subtitle = "Create a new service for the website";
$active_admin = "services";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';

?>

<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4" style="max-width:820px">
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

      <?php if ($errors): ?>
        <div class="alert alert-danger">
          <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
              <li><?= h($e) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label fw-semibold">Service Title</label>
        <input type="text" name="title" class="form-control"
               value="<?= h($title) ?>" placeholder="e.g. Website Design">
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Short Description</label>
        <input type="text" name="short_desc" class="form-control"
               value="<?= h($short_desc) ?>" placeholder="Short summary shown on cards">
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Full Description</label>
        <textarea name="description" class="form-control" rows="6"
                  placeholder="Detailed service description"><?= h($description) ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Service Icon / Image (optional)</label>
        <input type="file" name="icon" class="form-control">
      </div>

      <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" name="is_active"
               id="activeCheck" <?= $is_active ? 'checked' : '' ?>>
        <label class="form-check-label" for="activeCheck">
          Active (visible on website)
        </label>
      </div>

      <div class="d-flex gap-2">
        <button class="btn btn-orange">
          <i class="bi bi-save me-1"></i> Save Service
        </button>
        <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
      </div>

    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
