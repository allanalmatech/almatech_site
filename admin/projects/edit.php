<?php
// admin/projects/edit.php
declare(strict_types=1);

$page_title = "Edit Project | Admin";
$page_heading = "Projects";
$page_subtitle = "Update project details";
$active_admin = "projects";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../config.php';

csrf_init();

$db = $GLOBALS['db'] ?? $mysqli ?? null;
if (!($db instanceof mysqli)) {
  http_response_code(500);
  exit('DB not available');
}

$base = rtrim((string)($GLOBALS['BASE_URL'] ?? ''), '/');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  exit("Missing project id");
}

function status_badge(string $status): string {
  $map = [
    'completed' => 'success',
    'ongoing'   => 'primary',
    'paused'    => 'warning',
    'draft'     => 'secondary',
  ];
  $cls = $map[$status] ?? 'dark';
  return '<span class="badge bg-' . $cls . '">' . h($status) . '</span>';
}

$allowedStatus = ['completed','ongoing','paused','draft'];
$errors = [];
$flash = null;

// -------------------- Load project --------------------
$stmt = $db->prepare("SELECT id, title, slug, short_desc, full_desc, category, project_url, cover_image, tech_stack, created_at, status, is_featured FROM projects WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
  http_response_code(404);
  exit("Project not found");
}

// Decode tech stack (optional)
$tech_stack_arr = [];
if (!empty($project['tech_stack'])) {
  $decoded = json_decode((string)$project['tech_stack'], true);
  if (is_array($decoded)) $tech_stack_arr = $decoded;
}
$tech_stack_str = implode(', ', array_map('strval', $tech_stack_arr));

// -------------------- Handle POST (Update) --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify(); // expects helpers.php to validate posted csrf

  $title = trim((string)($_POST['title'] ?? ''));
  $slug  = trim((string)($_POST['slug'] ?? ''));
  $short_desc = trim((string)($_POST['short_desc'] ?? ''));
  $full_desc  = trim((string)($_POST['full_desc'] ?? ''));
  $category   = trim((string)($_POST['category'] ?? ''));
  $status     = trim((string)($_POST['status'] ?? 'draft'));
  $is_featured = !empty($_POST['is_featured']) ? 1 : 0;
  $use_project_url = !empty($_POST['use_project_url']) ? 1 : 0;
  $project_url = $use_project_url ? trim((string)($_POST['project_url'] ?? '')) : '';

  $tech_stack_input = trim((string)($_POST['tech_stack'] ?? ''));
  $tech_stack = [];

  if ($title === '') $errors[] = "Title is required.";
  if ($slug === '') $errors[] = "Slug is required.";
  if ($short_desc === '') $errors[] = "Short description is required.";
  if ($status !== '' && !in_array($status, $allowedStatus, true)) $errors[] = "Invalid status.";

  // Tech stack CSV -> JSON array
  if ($tech_stack_input !== '') {
    $parts = array_filter(array_map('trim', explode(',', $tech_stack_input)));
    $tech_stack = array_values(array_unique($parts));
  }

  // Unique slug check (ignore current id)
  if ($slug !== '') {
    $st = $db->prepare("SELECT id FROM projects WHERE slug = ? AND id <> ? LIMIT 1");
    $st->bind_param("si", $slug, $id);
    $st->execute();
    $exists = $st->get_result()->fetch_assoc();
    $st->close();

    if ($exists) $errors[] = "Slug already exists. Choose a unique slug.";
  }

  // -------------------- Cover image upload (optional) --------------------
  $cover_image = (string)($project['cover_image'] ?? '');

  if (!empty($_FILES['cover_image']['name'])) {
    $file = $_FILES['cover_image'];

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      $errors[] = "Cover image upload failed.";
    } else {
      $tmp = (string)$file['tmp_name'];
      $size = (int)$file['size'];
      $maxSize = 3 * 1024 * 1024; // 3MB

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

          // Save inside /uploads/projects
          $uploadDirFs = realpath(__DIR__ . '/../../') . '/uploads/projects';
          if (!is_dir($uploadDirFs)) {
            @mkdir($uploadDirFs, 0755, true);
          }

          $filename = 'project_' . $id . '_' . date('Ymd_His') . '.' . $ext;
          $destFs = $uploadDirFs . '/' . $filename;

          if (!move_uploaded_file($tmp, $destFs)) {
            $errors[] = "Failed to save uploaded image.";
          } else {
            // Store relative path for database
            $cover_image = '/uploads/projects/' . $filename;
          }
        }
      }
    }
  }

  // -------------------- Update DB --------------------
  if (empty($errors)) {
    $tech_stack_json = $tech_stack ? json_encode($tech_stack, JSON_UNESCAPED_SLASHES) : null;

    $sql = "UPDATE projects
            SET title=?, slug=?, short_desc=?, full_desc=?, category=?, project_url=?, status=?, is_featured=?, cover_image=?, tech_stack=?
            WHERE id=? LIMIT 1";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
      $errors[] = "DB error: " . $db->error;
    } else {
      $stmt->bind_param(
        "sssssssissi",
        $title,
        $slug,
        $short_desc,
        $full_desc,
        $category,
        $project_url,
        $status,
        $is_featured,
        $cover_image,
        $tech_stack_json,
        $id
      );
      $ok = $stmt->execute();
      $stmt->close();

      if (!$ok) {
        $errors[] = "Failed to update project.";
      } else {
        // Reload updated project
        $stmt = $db->prepare("SELECT * FROM projects WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $project = $stmt->get_result()->fetch_assoc() ?: $project;
        $stmt->close();

        // rebuild tech stack string
        $tech_stack_arr = [];
        if (!empty($project['tech_stack'])) {
          $decoded = json_decode((string)$project['tech_stack'], true);
          if (is_array($decoded)) $tech_stack_arr = $decoded;
        }
        $tech_stack_str = implode(', ', array_map('strval', $tech_stack_arr));

        $flash = ['type' => 'success', 'msg' => 'Project updated successfully.'];
      }
    }
  } else {
    // keep entered values on error
    $project['title'] = $title;
    $project['slug'] = $slug;
    $project['short_desc'] = $short_desc;
    $project['full_desc'] = $full_desc;
    $project['category'] = $category;
    $project['status'] = $status;
    $project['is_featured'] = $is_featured;
    $project['cover_image'] = $cover_image;
    $tech_stack_str = $tech_stack_input;
  }
}

$csrf = csrf_token();
?>

<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
      <div>
        <h4 class="mb-1">Edit Project</h4>
        <div class="text-muted small">
          Update details shown on the public website.
          <?= status_badge((string)($project['status'] ?? 'draft')) ?>
        </div>
      </div>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="list.php">← Back to List</a>
        <a class="btn btn-outline-secondary" href="<?= BASE_URL ?>projects/<?= h((string)$project['slug']) ?>" target="_blank" rel="noopener">
          View Public
        </a>
      </div>
    </div>

    <?php if (!empty($flash)): ?>
      <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['msg']) ?></div>
    <?php endif; ?>

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
        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">

        <div class="row g-3">
          <div class="col-12 col-lg-8">
            <label class="form-label">Title *</label>
            <input class="form-control" name="title" value="<?= h((string)$project['title']) ?>" required>
          </div>

          <div class="col-12 col-lg-4">
            <label class="form-label">Status *</label>
            <select class="form-select" name="status" required>
              <?php foreach (['completed','ongoing','paused','draft'] as $s): ?>
                <option value="<?= h($s) ?>" <?= ((string)$project['status'] === $s) ? 'selected' : '' ?>>
                  <?= h(ucfirst($s)) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Slug (URL) *</label>
            <input class="form-control" name="slug" value="<?= h((string)$project['slug']) ?>" required>
            <div class="form-text">Example: <code>lukono-erp</code></div>
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Category</label>
            <input class="form-control" name="category" value="<?= h((string)($project['category'] ?? '')) ?>" placeholder="ERP, Website, Mobile App...">
          </div>

          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="use_project_url" id="use_project_url" value="1" <?= !empty($project['project_url']) ? 'checked' : '' ?> onchange="toggleProjectUrl()">
                <label class="form-check-label" for="use_project_url">
                  Use external URL for this project
                </label>
            </div>
          </div>

          <div class="col-12" id="project_url_field" style="<?= !empty($project['project_url']) ? '' : 'display: none;' ?>">
            <label class="form-label">Project URL</label>
            <input class="form-control" name="project_url" value="<?= h((string)($project['project_url'] ?? '')) ?>" placeholder="https://example.com/project">
            <div class="form-text">External URL where users can view this project (e.g., GitHub, live demo, etc.)</div>
          </div>

          <div class="col-12">
            <label class="form-label">Short Description *</label>
            <input class="form-control" name="short_desc" value="<?= h((string)$project['short_desc']) ?>" required>
          </div>

          <div class="col-12">
            <label class="form-label">Full Description</label>
            <textarea class="form-control" name="full_desc" rows="6"><?= h((string)($project['full_desc'] ?? '')) ?></textarea>
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Tech Stack (comma-separated)</label>
            <input class="form-control" name="tech_stack" value="<?= h($tech_stack_str) ?>" placeholder="PHP, MySQL, Bootstrap, React...">
          </div>

          <div class="col-12 col-lg-6 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" value="1"
                <?= !empty($project['is_featured']) ? 'checked' : '' ?>>
              <label class="form-check-label" for="is_featured">
                Featured (show on homepage)
              </label>
            </div>
          </div>

          <div class="col-12 col-lg-7">
            <label class="form-label">Cover Image</label>
            <input type="file" class="form-control" name="cover_image" accept="image/*">
            <div class="form-text">JPG/PNG/WEBP, max 3MB.</div>
          </div>

          <div class="col-12 col-lg-5">
            <label class="form-label">Current Cover</label>
            <div class="border rounded p-2 d-flex align-items-center justify-content-center bg-light" style="min-height:110px;">
              <?php if (!empty($project['cover_image'])): ?>
                <img src="<?= BASE_URL . ltrim((string)$project['cover_image'], '/') ?>" alt="" style="max-height:110px;max-width:100%;object-fit:cover;border-radius:10px;">
              <?php else: ?>
                <div class="text-muted small">No cover image</div>
              <?php endif; ?>
            </div>
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
