<?php
// admin/posts/edit.php  (with cover image upload + relative path storage)
declare(strict_types=1);

$page_title = "Edit Post | Admin";
$page_heading = "Posts";
$page_subtitle = "Update blog post";
$active_admin = "posts";

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
  exit('Invalid post id');
}

$allowedStatus = ['published','draft','archived'];
$errors = [];
$flash = null;

// -------------------- Load post (NO get_result: works on all hosts) --------------------
$post = null;
$stmt = $db->prepare("SELECT id, title, slug, excerpt, content, category, status, cover_image, published_at, is_featured, created_at, updated_at FROM posts WHERE id = ? LIMIT 1");
if (!$stmt) {
  http_response_code(500);
  exit('DB error: ' . $db->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($pid, $title, $slug, $excerpt, $content, $category, $status, $cover_image, $published_at, $is_featured, $created_at, $updated_at);
if ($stmt->fetch()) {
  $post = [
    'id' => $pid,
    'title' => $title,
    'slug' => $slug,
    'excerpt' => $excerpt,
    'content' => $content,
    'category' => $category,
    'status' => $status,
    'cover_image' => $cover_image,
    'published_at' => $published_at,
    'is_featured' => $is_featured,
    'created_at' => $created_at,
    'updated_at' => $updated_at,
  ];
}
$stmt->close();

if (!$post) {
  http_response_code(404);
  exit('Post not found');
}

// -------------------- Handle POST --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();

  $title   = trim((string)($_POST['title'] ?? ''));
  $slug    = trim((string)($_POST['slug'] ?? ''));
  $excerpt = trim((string)($_POST['excerpt'] ?? ''));
  $content = trim((string)($_POST['content'] ?? ''));
  $category = trim((string)($_POST['category'] ?? ''));
  $status   = trim((string)($_POST['status'] ?? 'draft'));
  $published_at_in = trim((string)($_POST['published_at'] ?? ''));
  $is_featured = (int)($_POST['is_featured'] ?? 0);

  if ($title === '') $errors[] = "Title is required.";
  if ($slug === '')  $errors[] = "Slug is required.";
  if (!in_array($status, $allowedStatus, true)) $errors[] = "Invalid status.";

  // Unique slug (ignore this post)
  if ($slug !== '') {
    $st = $db->prepare("SELECT id FROM posts WHERE slug = ? AND id <> ? LIMIT 1");
    if ($st) {
      $st->bind_param("si", $slug, $id);
      $st->execute();
      $st->store_result();
      if ($st->num_rows > 0) $errors[] = "Slug already exists.";
      $st->close();
    }
  }

  // -------------------- Cover image upload (optional) --------------------
  // Store as RELATIVE path e.g. /uploads/posts/file.jpg
  $new_cover_image = (string)($post['cover_image'] ?? '');

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

          $rootFs = realpath(__DIR__ . '/../../');
          $uploadDirFs = $rootFs . '/uploads/posts';
          if (!is_dir($uploadDirFs)) @mkdir($uploadDirFs, 0755, true);

          $filename = 'post_' . $id . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
          $destFs = $uploadDirFs . '/' . $filename;

          if (!move_uploaded_file($tmp, $destFs)) {
            $errors[] = "Failed to save uploaded image.";
          } else {
            // Optional: delete old image file (only if it was inside /uploads/posts/)
            if (!empty($new_cover_image)) {
              $oldPath = ltrim((string)$new_cover_image, '/');
              if (strpos($oldPath, 'uploads/posts/') === 0) {
                $oldFs = $rootFs . '/' . $oldPath;
                if (is_file($oldFs)) @unlink($oldFs);
              }
            }

            $new_cover_image = '/uploads/posts/' . $filename;
          }
        }
      }
    }
  }

  // Normalize published_at (datetime-local -> DATETIME)
  $pub = null;
  if ($published_at_in !== '') {
    // expected: YYYY-MM-DDTHH:MM
    $pub = str_replace('T', ' ', $published_at_in) . ':00';
  }

  if (empty($errors)) {
    $sql = "
      UPDATE posts SET
        title = ?,
        slug = ?,
        excerpt = ?,
        content = ?,
        category = ?,
        status = ?,
        cover_image = ?,
        published_at = ?,
        is_featured = ?
      WHERE id = ? LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    if (!$stmt) {
      $errors[] = "DB error: " . $db->error;
    } else {
      $stmt->bind_param(
        "ssssssssii",
        $title,
        $slug,
        $excerpt,
        $content,
        $category,
        $status,
        $new_cover_image,
        $pub,
        $is_featured,
        $id
      );

      if ($stmt->execute()) {
        $flash = ['type' => 'success', 'msg' => 'Post updated successfully.'];
        $stmt->close();

        // Reload (again, bind_result)
        $stmt = $db->prepare("SELECT id, title, slug, excerpt, content, category, status, cover_image, published_at, is_featured, created_at, updated_at FROM posts WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($pid, $title, $slug, $excerpt, $content, $category, $status, $cover_image, $published_at, $is_featured, $created_at, $updated_at);
        if ($stmt->fetch()) {
          $post = [
            'id' => $pid,
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'content' => $content,
            'category' => $category,
            'status' => $status,
            'cover_image' => $cover_image,
            'published_at' => $published_at,
            'is_featured' => $is_featured,
            'created_at' => $created_at,
            'updated_at' => $updated_at,
          ];
        }
        $stmt->close();
      } else {
        $errors[] = "Failed to update post.";
        $stmt->close();
      }
    }
  } else {
    // Preserve input on error
    $post['title'] = $title;
    $post['slug'] = $slug;
    $post['excerpt'] = $excerpt;
    $post['content'] = $content;
    $post['category'] = $category;
    $post['status'] = $status;
    $post['published_at'] = $pub;
    $post['cover_image'] = $new_cover_image;
  }
}

$csrf = csrf_token();
?>

<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">

    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <h4 class="mb-1">Edit Post</h4>
        <div class="text-muted small">Update content shown on the website.</div>
      </div>
      <a class="btn btn-outline-secondary" href="list.php">← Back to Posts</a>
    </div>

    <?php if ($flash): ?>
      <div class="alert alert-success"><?= h($flash['msg']) ?></div>
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

    <!-- IMPORTANT: enctype for file upload -->
    <form class="card" method="post" enctype="multipart/form-data">
      <div class="card-body">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

        <div class="row g-3">
          <div class="col-12 col-lg-8">
            <label class="form-label">Title *</label>
            <input class="form-control" name="title" value="<?= h((string)$post['title']) ?>" required>
          </div>

          <div class="col-12 col-lg-4">
            <label class="form-label">Status *</label>
            <select class="form-select" name="status">
              <?php foreach ($allowedStatus as $s): ?>
                <option value="<?= h($s) ?>" <?= ((string)$post['status'] === $s) ? 'selected' : '' ?>>
                  <?= h(ucfirst($s)) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-lg-4">
            <label class="form-label">Featured</label>
            <select class="form-select" name="is_featured">
              <option value="0" <?= (($post['is_featured'] ?? 0) == 0) ? 'selected' : '' ?>>No</option>
              <option value="1" <?= (($post['is_featured'] ?? 0) == 1) ? 'selected' : '' ?>>Yes</option>
            </select>
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Slug *</label>
            <input class="form-control" name="slug" value="<?= h((string)$post['slug']) ?>" required>
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Category</label>
            <input class="form-control" name="category" value="<?= h((string)($post['category'] ?? '')) ?>">
          </div>

          <div class="col-12">
            <label class="form-label">Excerpt</label>
            <textarea class="form-control" name="excerpt" rows="2"><?= h((string)($post['excerpt'] ?? '')) ?></textarea>
          </div>

          <div class="col-12">
            <label class="form-label">Content</label>
            <textarea class="form-control" name="content" rows="10"><?= h((string)($post['content'] ?? '')) ?></textarea>
          </div>

          <div class="col-12 col-lg-6">
            <label class="form-label">Published At</label>
            <input type="datetime-local" class="form-control" name="published_at"
              value="<?= !empty($post['published_at']) ? date('Y-m-d\TH:i', strtotime((string)$post['published_at'])) : '' ?>">
          </div>

          <!-- Cover image upload + preview -->
          <div class="col-12 col-lg-6">
            <label class="form-label">Cover Image</label>
            <input type="file" class="form-control" name="cover_image" accept="image/*">
            <div class="form-text">JPG/PNG/WEBP, max 3MB. Saved as relative path (/uploads/posts/...).</div>

            <div class="mt-2 border rounded p-2 bg-light d-flex align-items-center justify-content-center" style="min-height:110px;">
              <?php if (!empty($post['cover_image'])): ?>
                <img src="<?= h(BASE_URL . ltrim((string)$post['cover_image'], '/')) ?>" alt="" style="max-height:110px;max-width:100%;object-fit:cover;border-radius:10px;">
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

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
