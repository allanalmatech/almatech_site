<?php
declare(strict_types=1);

$page_title   = "Edit Maintenance Template | Admin";
$active_admin = "settings";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';

csrf_init();
$db = $GLOBALS['db'] ?? ($mysqli ?? null);
if (!($db instanceof mysqli)) { http_response_code(500); exit('DB not available'); }

$errors   = [];
$success  = '';
$template = null;

// Get template ID
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(404);
  exit('Template not found');
}

// Load template
$stmt = $db->prepare("SELECT * FROM maintenance_templates WHERE id = ? LIMIT 1");
if ($stmt) {
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $res = $stmt->get_result();
  $template = $res ? $res->fetch_assoc() : null;
  $stmt->close();
}

if (!$template) {
  http_response_code(404);
  exit('Template not found');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = (string)($_POST['csrf_token'] ?? '');

  if (!csrf_validate($token)) {
    $errors[] = "Security check failed. Please refresh and try again.";
  } else {
    $title   = trim((string)($_POST['title'] ?? ''));
    $slug    = trim((string)($_POST['slug'] ?? ''));
    $content = trim((string)($_POST['content'] ?? ''));
    $css     = trim((string)($_POST['css'] ?? ''));
    $js      = trim((string)($_POST['js'] ?? ''));
    $is_active = !empty($_POST['is_active']);

    if ($title === '') {
      $errors[] = "Template title is required.";
    }

    if ($slug === '') {
      $slug = strtolower((string)preg_replace('/[^a-z0-9]+/', '-', $title));
      $slug = trim($slug, '-');
    }

    // Check if slug already exists (excluding current template)
    $chk = $db->prepare("SELECT id FROM maintenance_templates WHERE slug = ? AND id != ? LIMIT 1");
    if ($chk) {
      $chk->bind_param("si", $slug, $id);
      $chk->execute();
      $r = $chk->get_result();
      if ($r && $r->num_rows > 0) $errors[] = "A template with this slug already exists.";
      $chk->close();
    } else {
      $errors[] = "Database error preparing slug check.";
    }

    if (!$errors) {
      // ✅ IMPORTANT: use correct column names (content, css, js)
      $upd = $db->prepare("
        UPDATE maintenance_templates
        SET title = ?, slug = ?, content = ?, css = ?, js = ?, is_active = ?, updated_at = NOW()
        WHERE id = ?
      ");

      if ($upd) {
        $activeVal = $is_active ? 1 : 0;
        $upd->bind_param("sssssii", $title, $slug, $content, $css, $js, $activeVal, $id);

        if ($upd->execute()) {
          $success = "Template updated successfully!";

          // Reload template
          $reload = $db->prepare("SELECT * FROM maintenance_templates WHERE id = ? LIMIT 1");
          if ($reload) {
            $reload->bind_param("i", $id);
            $reload->execute();
            $rr = $reload->get_result();
            $template = $rr ? $rr->fetch_assoc() : $template;
            $reload->close();
          }
        } else {
          $errors[] = "Failed to update template.";
        }

        $upd->close();
      } else {
        $errors[] = "Database error preparing update.";
      }
    }
  }
}

$page_heading  = "Edit Maintenance Template";
$page_subtitle = "Modify under construction page template";
?>

<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h4 class="mb-0">Edit Maintenance Template</h4>
        <div class="text-muted small">Modify under construction page template.</div>
      </div>
      <a class="btn btn-outline-secondary" href="list.php">← Back to Templates</a>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Fix the following:</div>
        <ul class="mb-0">
          <?php foreach ($errors as $error): ?>
            <li><?= h($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="alert alert-success"><?= h($success) ?></div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Template Title *</label>
          <input type="text" class="form-control" name="title"
                 value="<?= h($_POST['title'] ?? (string)$template['title']) ?>"
                 required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Slug</label>
          <input type="text" class="form-control" name="slug"
                 value="<?= h($_POST['slug'] ?? (string)$template['slug']) ?>">
          <div class="form-text">Leave empty to auto-generate from title</div>
        </div>
      </div>

      <div class="row g-3 mt-0">
        <div class="col-12">
          <label class="form-label">HTML Content</label>
          <textarea class="form-control" name="content" rows="10"
                    placeholder="Enter HTML content"><?= h($_POST['content'] ?? (string)($template['content'] ?? '')) ?></textarea>
        </div>
      </div>

      <div class="row g-3 mt-0">
        <div class="col-md-6">
          <label class="form-label">Custom CSS (optional)</label>
          <textarea class="form-control" name="css" rows="8"
                    placeholder="CSS"><?= h($_POST['css'] ?? (string)($template['css'] ?? '')) ?></textarea>
        </div>

        <div class="col-md-6">
          <label class="form-label">Custom JavaScript (optional)</label>
          <textarea class="form-control" name="js" rows="8"
                    placeholder="JS"><?= h($_POST['js'] ?? (string)($template['js'] ?? '')) ?></textarea>
        </div>
      </div>

      <div class="row g-3 mt-0 align-items-center">
        <div class="col-md-6">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1"
              <?= (!empty($_POST['is_active']) || !empty($template['is_active'])) ? 'checked' : '' ?>>
            <label class="form-check-label">Active</label>
          </div>
          <div class="form-text">Enable this template for use</div>
        </div>

        <div class="col-md-6 d-flex justify-content-end gap-2">
          <button type="submit" class="btn btn-orange">
            <i class="bi bi-save me-1"></i> Update Template
          </button>
          <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
        </div>
      </div>
    </form>
  </div>

  <?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
</div>
