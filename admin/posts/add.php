<?php
// admin/posts/add.php
declare(strict_types=1);

$page_title = "Add Post | Admin";
$page_heading = "Posts";
$page_subtitle = "Create a new blog post";
$active_admin = "posts";

require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';

csrf_init();

$db = $GLOBALS['db'] ?? $mysqli ?? null;
if (!($db instanceof mysqli)) {
  http_response_code(500);
  exit('DB not available');
}

$allowedStatus = ['published','draft','archived'];
$errors = [];

// Defaults
$title = '';
$slug = '';
$excerpt = '';
$content = '';
$category = '';
$status = 'draft';
$published_at = date('Y-m-d\TH:i'); // Default to current date and time
$cover_image = ''; // relative path: /uploads/posts/...
$is_featured = 0; // Default to not featured

function slugify(string $s): string {
  $s = strtolower(trim($s));
  $s = preg_replace('~[^a-z0-9]+~', '-', $s);
  return trim($s, '-') ?: 'post';
}

function unique_slug(mysqli $db, string $base): string {
  $slug = $base; $i = 2;
  while (true) {
    $st = $db->prepare("SELECT id FROM posts WHERE slug=? LIMIT 1");
    $st->bind_param("s", $slug);
    $st->execute();
    $st->store_result();
    if ($st->num_rows === 0) { $st->close(); return $slug; }
    $st->close();
    $slug = $base . '-' . $i++;
  }
}

// -------------------- Handle POST (before any HTML output) --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $title   = trim((string)($_POST['title'] ?? ''));
  $slug    = trim((string)($_POST['slug'] ?? ''));
  $excerpt = trim((string)($_POST['excerpt'] ?? ''));
  $content = trim((string)($_POST['content'] ?? ''));
  $category = trim((string)($_POST['category'] ?? ''));
  $status   = trim((string)($_POST['status'] ?? 'draft'));
  $published_at = trim((string)($_POST['published_at'] ?? ''));
  $is_featured = (int)($_POST['is_featured'] ?? 0);

  if ($title === '') $errors[] = "Title is required.";
  if (!in_array($status, $allowedStatus, true)) $errors[] = "Invalid status.";

  // Slug
  $slug = $slug !== '' ? slugify($slug) : slugify($title);
  $slug = unique_slug($db, $slug);

  // Cover image upload (optional)
  if (!empty($_FILES['cover_image']['name'])) {
    $f = $_FILES['cover_image'];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      $errors[] = "Cover image upload failed.";
    } else {
      if ($f['size'] > 3 * 1024 * 1024) {
        $errors[] = "Cover image too large (max 3MB).";
      } else {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $map = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if (!isset($map[$mime])) {
          $errors[] = "Only JPG, PNG or WEBP allowed.";
        } else {
          $root = realpath(__DIR__ . '/../../');
          $dir  = $root . '/uploads/posts';
          if (!is_dir($dir)) @mkdir($dir, 0755, true);
          $name = 'post_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $map[$mime];
          if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
            $errors[] = "Failed to save image.";
          } else {
            $cover_image = '/uploads/posts/' . $name; // relative
          }
        }
      }
    }
  }

  // Normalize published_at
  $pub = null;
  if ($published_at !== '') {
    $pub = str_replace('T', ' ', $published_at) . ':00';
  }

  if (empty($errors)) {
    $sql = "
      INSERT INTO posts
        (title, slug, excerpt, content, category, status, cover_image, published_at, is_featured, created_at)
      VALUES (?,?,?,?,?,?,?,?,?,NOW())
    ";
    $st = $db->prepare($sql);
    if (!$st) {
      $errors[] = "DB error: " . $db->error;
    } else {
      $st->bind_param(
        "ssssssssi",
        $title, $slug, $excerpt, $content, $category, $status, $cover_image, $pub, $is_featured
      );
      if ($st->execute()) {
        $newId = (int)$st->insert_id;
        $st->close();
        header("Location: edit.php?id=" . $newId);
        exit;
      } else {
        $errors[] = "Failed to create post.";
        $st->close();
      }
    }
  }
}

$csrf = csrf_token();

// -------------------- Now include headers (after POST processing) --------------------
require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>

<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <h4 class="mb-1">Add Post</h4>
        <div class="text-muted small">Create a new blog post.</div>
      </div>
      <a class="btn btn-outline-secondary" href="list.php">← Back to Posts</a>
    </div>

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
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
            <select class="form-select" name="status">
              <?php foreach ($allowedStatus as $s): ?>
                <option value="<?= h($s) ?>" <?= $status===$s?'selected':'' ?>><?= h(ucfirst($s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-lg-4">
            <label class="form-label">Featured</label>
            <select class="form-select" name="is_featured">
              <option value="0">No</option>
              <option value="1" <?= $is_featured==1?'selected':'' ?>>Yes</option>
            </select>
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Slug (optional)</label>
            <input class="form-control" name="slug" value="<?= h($slug) ?>" placeholder="auto from title">
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Category</label>
            <input class="form-control" name="category" value="<?= h($category) ?>">
          </div>

          <div class="col-12">
            <label class="form-label">Excerpt</label>
            <textarea class="form-control" name="excerpt" rows="2"><?= h($excerpt) ?></textarea>
          </div>

          <div class="col-12">
            <label class="form-label">Content</label>
            <textarea class="form-control" name="content" rows="10"><?= h($content) ?></textarea>
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Published At</label>
            <input type="datetime-local" class="form-control" name="published_at" value="<?= h($published_at) ?>">
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Cover Image</label>
            <input type="file" class="form-control" name="cover_image" accept="image/*">
            <div class="form-text">JPG/PNG/WEBP, max 3MB. Saved as /uploads/posts/…</div>
            <?php if ($cover_image): ?>
              <div class="mt-2">
                <img src="<?= h($cover_image) ?>" style="max-height:110px;border-radius:10px;">
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="card-footer d-flex justify-content-between">
        <a class="btn btn-outline-secondary" href="list.php">Cancel</a>
        <button class="btn btn-primary" type="submit">Create Post</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
