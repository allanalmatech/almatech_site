<?php
// admin/projects/add.php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../config.php';

csrf_init();

$db = $GLOBALS['db'] ?? $mysqli ?? null;
if (!($db instanceof mysqli)) {
  http_response_code(500);
  exit('DB not available');
}

$allowedStatus = ['completed','ongoing','paused','draft'];
$errors = [];
$flash = null;

function slugify(string $text): string {
  $text = trim($text);
  $text = strtolower($text);
  $text = preg_replace('~[^a-z0-9]+~', '-', $text);
  $text = trim((string)$text, '-');
  return $text !== '' ? $text : 'project';
}

function unique_slug(mysqli $db, string $baseSlug): string {
  $slug = $baseSlug;
  $i = 2;
  while (true) {
    $st = $db->prepare("SELECT id FROM projects WHERE slug = ? LIMIT 1");
    $st->bind_param("s", $slug);
    $st->execute();
    $exists = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$exists) return $slug;
    $slug = $baseSlug . '-' . $i;
    $i++;
  }
}

$title = '';
$slug = '';
$short_desc = '';
$full_desc = '';
$category = '';
$status = 'draft';
$is_featured = 0;
$tech_stack_str = '';
$cover_image_url = '';
$project_url = '';
$use_project_url = 0;

// -------------------- Handle POST (Create) --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $title = trim((string)($_POST['title'] ?? ''));
  $slug  = trim((string)($_POST['slug'] ?? ''));
  $short_desc = trim((string)($_POST['short_desc'] ?? ''));
  $full_desc  = trim((string)($_POST['full_desc'] ?? ''));
  $category   = trim((string)($_POST['category'] ?? ''));
  $status     = trim((string)($_POST['status'] ?? 'draft'));
  $is_featured = !empty($_POST['is_featured']) ? 1 : 0;
  $tech_stack_str = trim((string)($_POST['tech_stack'] ?? ''));
  $use_project_url = !empty($_POST['use_project_url']) ? 1 : 0;
  $project_url = $use_project_url ? trim((string)($_POST['project_url'] ?? '')) : '';

  if ($title === '') $errors[] = "Title is required.";
  if ($short_desc === '') $errors[] = "Short description is required.";
  if ($status !== '' && !in_array($status, $allowedStatus, true)) $errors[] = "Invalid status.";

  // slug auto if empty
  if ($slug === '') {
    $slug = slugify($title);
  } else {
    $slug = slugify($slug);
  }

  // tech stack CSV -> JSON array
  $tech_stack = [];
  if ($tech_stack_str !== '') {
    $parts = array_filter(array_map('trim', explode(',', $tech_stack_str)));
    $tech_stack = array_values(array_unique($parts));
  }
  $tech_stack_json = $tech_stack ? json_encode($tech_stack, JSON_UNESCAPED_SLASHES) : null;

  // ensure unique slug
  if ($slug !== '') {
    $slug = unique_slug($db, $slug);
  }

  // Cover upload (optional) - store as RELATIVE URL (/uploads/...)
  $cover_image_url = '';

  if (!empty($_FILES['cover_image']['name'])) {
    $file = $_FILES['cover_image'];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      $errors[] = "Cover image upload failed.";
    } else {
      $tmp = (string)$file['tmp_name'];
      $size = (int)$file['size'];
      $maxSize = 3 * 1024 * 1024;

      if ($size > $maxSize) {
        $errors[] = "Cover image is too large (max 3MB).";
      } else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);

        $allowed = [
          'image/jpeg' => 'jpg',
          'image/png'  => 'png',
          'image/webp' => 'webp',
        ];

        if (!isset($allowed[$mime])) {
          $errors[] = "Only JPG, PNG, or WEBP images are allowed.";
        } else {
          $ext = $allowed[$mime];

          // filesystem folder
          $rootFs = realpath(__DIR__ . '/../../');
          $uploadDirFs = $rootFs . '/uploads/projects';
          if (!is_dir($uploadDirFs)) @mkdir($uploadDirFs, 0755, true);

          $filename = 'project_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
          $destFs = $uploadDirFs . '/' . $filename;

          if (!move_uploaded_file($tmp, $destFs)) {
            $errors[] = "Failed to save uploaded image.";
          } else {
            // ✅ RELATIVE path saved in DB
            $cover_image_url = '/uploads/projects/' . $filename;
          }
        }
      }
    }
  }

  if (empty($errors)) {
    $sql = "INSERT INTO projects
      (title, slug, short_desc, full_desc, category, project_url, status, is_featured, cover_image, tech_stack, created_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
      $errors[] = "DB error: " . $db->error;
    } else {
      $stmt->bind_param(
        "ssssssiss",
        $title,
        $slug,
        $short_desc,
        $full_desc,
        $category,
        $project_url,
        $status,
        $is_featured,
        $cover_image_url,
        $tech_stack_json
      );

      $ok = $stmt->execute();
      $newId = (int)$stmt->insert_id;
      $stmt->close();

      if (!$ok) {
        $errors[] = "Failed to create project.";
      } else {
        // redirect to edit page for further updates
        header("Location: " . ADMIN_URL . "projects/edit.php?id=" . $newId);
        exit;
      }
    }
  }
}

// Now include the admin layout after processing
$page_title = "Add Project | Admin";
$page_heading = "Projects";
$page_subtitle = "Create a new project";
$active_admin = "projects";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';

$csrf = csrf_token();
?>

<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
      <div>
        <h4 class="mb-1">Add Project</h4>
        <div class="text-muted small">Create a new project to show on the public website.</div>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="list.php">← Back to List</a>
      </div>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <div class="fw-semibold mb-2">Please fix the following:</div>
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= h($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form class="card" method="post" enctype="multipart/form-data">
      <div class="card-body">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

        <div class="row g-3">
          <div class="col-12 col-lg-8">
            <label class="form-label">Title *</label>
            <input class="form-control" name="title" value="<?= h($title) ?>" required>
          </div>

          <div class="col-12 col-lg-4">
            <label class="form-label">Status *</label>
            <select class="form-select" name="status" required>
              <?php foreach (['completed','ongoing','paused','draft'] as $s): ?>
                <option value="<?= h($s) ?>" <?= $status===$s?'selected':''; ?>><?= h(ucfirst($s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Slug (optional)</label>
            <input class="form-control" name="slug" value="<?= h($slug) ?>" placeholder="auto from title if blank">
            <div class="form-text">Example: <code>lukono-erp</code></div>
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Category</label>
            <input class="form-control" name="category" value="<?= h($category) ?>" placeholder="ERP, Website, Mobile App...">
          </div>

          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="use_project_url" id="use_project_url" value="1" <?= $use_project_url ? 'checked' : '' ?> onchange="toggleProjectUrl()">
              <label class="form-check-label" for="use_project_url">
                Use external URL for this project
              </label>
            </div>
          </div>

          <div class="col-12" id="project_url_field" style="<?= $use_project_url ? '' : 'display: none;' ?>">
            <label class="form-label">Project URL</label>
            <input class="form-control" name="project_url" value="<?= h($project_url) ?>" placeholder="https://example.com/project">
            <div class="form-text">External URL where users can view this project (e.g., GitHub, live demo, etc.)</div>
          </div>

          <div class="col-12">
            <label class="form-label">Short Description *</label>
            <input class="form-control" name="short_desc" value="<?= h($short_desc) ?>" required>
          </div>

          <div class="col-12">
            <label class="form-label">Full Description</label>
            <textarea class="form-control" name="full_desc" rows="6"><?= h($full_desc) ?></textarea>
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Tech Stack (comma-separated)</label>
            <input class="form-control" name="tech_stack" value="<?= h($tech_stack_str) ?>" placeholder="PHP, MySQL, Bootstrap, React...">
          </div>

          <div class="col-12 col-lg-6 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1" <?= $is_featured ? 'checked' : '' ?>>
              <label class="form-check-label" for="is_featured">
                Featured (show on homepage)
              </label>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label">Cover Image (optional)</label>
            <input type="file" class="form-control" name="cover_image" accept="image/*">
            <div class="form-text">JPG/PNG/WEBP, max 3MB. Stored as relative path.</div>

            <?php if ($cover_image_url !== ''): ?>
              <div class="mt-2">
                <div class="small text-muted mb-1">Preview:</div>
                <img src="<?= h($cover_image_url) ?>" alt="" style="max-height:120px;border-radius:10px;object-fit:cover;">
              </div>
            <?php endif; ?>
          </div>

        </div>
      </div>

      <div class="card-footer d-flex justify-content-between">
        <a class="btn btn-outline-secondary" href="list.php">Cancel</a>
        <button class="btn btn-primary" type="submit">Create Project</button>
      </div>
    </form>

  </div>
</div>

<script>
function toggleProjectUrl() {
  const checkbox = document.getElementById('use_project_url');
  const urlField = document.getElementById('project_url_field');

  if (checkbox.checked) {
    urlField.style.display = 'block';
  } else {
    urlField.style.display = 'none';
  }
}
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
