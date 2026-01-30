<?php
declare(strict_types=1);

// Early authentication check before any HTML output
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_admin_login();

require_once __DIR__ . '/../../includes/db.php';

$db = $GLOBALS['db'] ?? ($mysqli ?? null);
$errors = [];
$success = '';

// --- Ensure DB ---
if (!($db instanceof mysqli)) {
  die("DB not available");
}

// --- Page CRUD operations ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = (string)($_POST['csrf_token'] ?? '');

  if (!csrf_validate($token)) {
    $errors[] = "Security check failed. Please refresh and try again.";
  } else {
    $action = (string)($_POST['action'] ?? '');

    // Layout JSON (from modal controls)
    $layout_json = trim((string)($_POST['layout_json'] ?? ''));

    if ($action === 'add') {
      $title = trim((string)($_POST['title'] ?? ''));
      $slug  = trim((string)($_POST['slug'] ?? ''));
      $content = trim((string)($_POST['content'] ?? ''));
      $meta_title = trim((string)($_POST['meta_title'] ?? ''));
      $meta_description = trim((string)($_POST['meta_description'] ?? ''));
      $status = (string)($_POST['status'] ?? 'published');

      if ($title === '') {
        $errors[] = "Page title is required.";
      }

      if ($slug === '') {
        $slug = strtolower((string)preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug = trim($slug, '-');
      }

      // Check if slug already exists (excluding core pages)
      $core_pages = ['home', 'about', 'services', 'projects', 'blog', 'contact'];
      if (!in_array($slug, $core_pages, true)) {
        $stmt = $db->prepare("SELECT id FROM pages WHERE slug = ? LIMIT 1");
        if ($stmt) {
          $stmt->bind_param("s", $slug);
          $stmt->execute();
          if (method_exists($stmt, 'get_result')) {
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) $errors[] = "A page with this slug already exists.";
          } else {
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors[] = "A page with this slug already exists.";
          }
          $stmt->close();
        }
      }

      if (!$errors) {
        // NOTE: requires pages.layout_json column. If you don't have it, remove layout_json from SQL + bind.
        $stmt = $db->prepare("
          INSERT INTO pages (title, slug, content, layout_json, meta_title, meta_description, status, created_at, updated_at)
          VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        if (!$stmt) {
          $errors[] = "Prepare failed: " . $db->error;
        } else {
          $stmt->bind_param("sssssss", $title, $slug, $content, $layout_json, $meta_title, $meta_description, $status);
          if ($stmt->execute()) {
            $success = "Page created successfully!";
          } else {
            $errors[] = "Failed to create page: " . $stmt->error;
          }
          $stmt->close();
        }
      }
    }

    elseif ($action === 'edit') {
      $id = (int)($_POST['id'] ?? 0);
      $title = trim((string)($_POST['title'] ?? ''));
      $slug  = trim((string)($_POST['slug'] ?? ''));
      $content = trim((string)($_POST['content'] ?? ''));
      $meta_title = trim((string)($_POST['meta_title'] ?? ''));
      $meta_description = trim((string)($_POST['meta_description'] ?? ''));
      $status = (string)($_POST['status'] ?? 'published');

      if ($id <= 0) $errors[] = "Invalid page id.";
      if ($title === '') $errors[] = "Page title is required.";

      if ($slug === '') {
        $slug = strtolower((string)preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug = trim($slug, '-');
      }

      // Check if slug already exists (excluding current page and core pages)
      $core_pages = ['home', 'about', 'services', 'projects', 'blog', 'contact'];
      if (!in_array($slug, $core_pages, true)) {
        $stmt = $db->prepare("SELECT id FROM pages WHERE slug = ? AND id != ? LIMIT 1");
        if ($stmt) {
          $stmt->bind_param("si", $slug, $id);
          $stmt->execute();
          if (method_exists($stmt, 'get_result')) {
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) $errors[] = "A page with this slug already exists.";
          } else {
            $stmt->store_result();
            if ($stmt->num_rows > 0) $errors[] = "A page with this slug already exists.";
          }
          $stmt->close();
        }
      }

      if (!$errors) {
        // NOTE: requires pages.layout_json column. If you don't have it, remove layout_json from SQL + bind.
        $stmt = $db->prepare("
          UPDATE pages
          SET title = ?, slug = ?, content = ?, layout_json = ?, meta_title = ?, meta_description = ?, status = ?, updated_at = NOW()
          WHERE id = ?
        ");
        if (!$stmt) {
          $errors[] = "Prepare failed: " . $db->error;
        } else {
          $stmt->bind_param("sssssssi", $title, $slug, $content, $layout_json, $meta_title, $meta_description, $status, $id);
          if ($stmt->execute()) {
            $success = "Page updated successfully!";
          } else {
            $errors[] = "Failed to update page: " . $stmt->error;
          }
          $stmt->close();
        }
      }
    }

    elseif ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);

      $stmt = $db->prepare("SELECT slug FROM pages WHERE id = ? LIMIT 1");
      if ($stmt) {
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $page = null;
        if (method_exists($stmt, 'get_result')) {
          $res = $stmt->get_result();
          $page = $res ? $res->fetch_assoc() : null;
        } else {
          $stmt->bind_result($slugDb);
          if ($stmt->fetch()) $page = ['slug' => $slugDb];
        }
        $stmt->close();

        $core_pages = ['home', 'about', 'services', 'projects', 'blog', 'contact'];
        if ($page && in_array((string)$page['slug'], $core_pages, true)) {
          $errors[] = "Cannot delete core pages ({$page['slug']}).";
        } else {
          $stmt = $db->prepare("DELETE FROM pages WHERE id = ?");
          if ($stmt) {
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
              $success = "Page deleted successfully!";
            } else {
              $errors[] = "Failed to delete page: " . $stmt->error;
            }
            $stmt->close();
          }
        }
      }
    }
  }
}

// --- Load pages ---
$pages = [];
$stmt = $db->prepare("SELECT id, title, slug, status, created_at, updated_at FROM pages ORDER BY title ASC");
$stmt->execute();
if (method_exists($stmt, 'get_result')) {
  $pages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
  // fallback without mysqlnd
  $stmt->bind_result($id,$title,$slug,$status,$created_at,$updated_at);
  while ($stmt->fetch()) {
    $pages[] = compact('id','title','slug','status','created_at','updated_at');
  }
}
$stmt->close();

$page_title = "Page Management | Admin";
$page_heading = "Page Management";
$page_subtitle = "Create and manage custom pages for your website";
$active_admin = "pages";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';

// Core pages that cannot be deleted
$core_pages = ['home', 'about', 'services', 'projects', 'blog', 'contact'];
?>

<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <?php if ($errors || $success): ?>
    <div class="admin-card p-4 mb-4">
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
      <?php if ($success): ?>
        <div class="alert alert-success mb-0"><?= h($success) ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="admin-card p-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
      <div>
        <h4 class="mb-0">Pages</h4>
        <div class="text-muted small">Manage website pages (list, edit, delete, visibility).</div>
      </div>
      <div class="d-flex gap-2">
        <button class="btn btn-orange" data-bs-toggle="modal" data-bs-target="#addPageModal">
          <i class="bi bi-plus-circle me-1"></i> Add New Page
        </button>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:70px;">#</th>
            <th>Page</th>
            <th style="width:150px;">Slug</th>
            <th style="width:100px;">Status</th>
            <th style="width:150px;">Created</th>
            <th style="width:150px;">Updated</th>
            <th style="width:190px;" class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$pages): ?>
            <tr><td colspan="7" class="text-center py-5 text-muted">No pages found.</td></tr>
          <?php else: ?>
            <?php foreach ($pages as $p): ?>
              <tr>
                <td class="text-muted"><?= (int)$p['id'] ?></td>
                <td>
                  <div class="fw-semibold"><?= h((string)$p['title']) ?></div>
                  <div class="small text-muted"><code><?= h((string)$p['slug']) ?></code></div>
                </td>
                <td><code><?= h((string)$p['slug']) ?></code></td>
                <td>
                  <span class="badge bg-<?= ((string)$p['status'] === 'published') ? 'success' : 'secondary' ?>">
                    <?= h(ucfirst((string)$p['status'])) ?>
                  </span>
                </td>
                <td class="text-muted small"><?= h(date('M j, Y', strtotime((string)$p['created_at']))) ?></td>
                <td class="text-muted small"><?= h(date('M j, Y', strtotime((string)$p['updated_at']))) ?></td>
                <td class="text-end">
                  <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editPage(<?= (int)$p['id'] ?>)">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="viewPage(<?= (int)$p['id'] ?>)">
                      <i class="bi bi-eye"></i>
                    </button>
                    <?php if (!in_array((string)$p['slug'], $core_pages, true)): ?>
                      <button type="button" class="btn btn-sm btn-outline-danger" onclick="deletePage(<?= (int)$p['id'] ?>)">
                        <i class="bi bi-trash"></i>
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Add Page Modal -->
  <div class="modal fade" id="addPageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Add New Page</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form method="post" autocomplete="off" id="addPageForm">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="layout_json" id="add_layoutJson">

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Page Title *</label>
                <input type="text" class="form-control" name="title" id="add_title" placeholder="Enter page title" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Slug</label>
                <input type="text" class="form-control" name="slug" id="add_slug" placeholder="url-friendly-slug">
                <div class="form-text">Leave empty to auto-generate from title</div>
              </div>
              <div class="col-12">
                <label class="form-label">Content</label>
                <textarea class="form-control" name="content" id="add_content" rows="8" placeholder="Enter page content (HTML allowed)"></textarea>
              </div>
            </div>

            <hr class="my-3">
            <h6 class="mb-2">Layout Controls</h6>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Container Type</label>
                <select class="form-select" id="add_layoutContainer">
                  <option value="container">Normal (container)</option>
                  <option value="container-fluid">Full Width (container-fluid)</option>
                </select>
                <div class="form-text">Boxed or full-width container.</div>
              </div>

              <div class="col-md-4">
                <label class="form-label">Max Content Width (px)</label>
                <div class="d-flex align-items-center gap-2">
                  <input type="range" class="form-range flex-grow-1" min="680" max="1400" step="10" value="920" id="add_layoutWidthRange">
                  <input type="number" class="form-control" min="680" max="1400" step="10" value="920" id="add_layoutWidthInput" style="max-width:120px;">
                </div>
              </div>

              <div class="col-md-4">
                <label class="form-label">Content Padding (px)</label>
                <div class="d-flex align-items-center gap-2">
                  <input type="range" class="form-range flex-grow-1" min="0" max="80" step="2" value="32" id="add_layoutPaddingRange">
                  <input type="number" class="form-control" min="0" max="80" step="2" value="32" id="add_layoutPaddingInput" style="max-width:120px;">
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Editor Height (px)</label>
                <div class="d-flex align-items-center gap-2">
                  <input type="range" class="form-range flex-grow-1" min="200" max="900" step="10" value="320" id="add_editorHeightRange">
                  <input type="number" class="form-control" min="200" max="900" step="10" value="320" id="add_editorHeightInput" style="max-width:140px;">
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Live Preview</label>
                <div class="border rounded p-2 bg-light">
                  <div id="add_layoutPreview" class="border rounded bg-white" style="max-width:920px; padding:32px;">
                    <div class="text-muted small">Preview box</div>
                    <div class="fw-semibold">Your page content will render like this.</div>
                  </div>
                </div>
              </div>
            </div>

            <hr class="my-3">

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Meta Title</label>
                <input type="text" class="form-control" name="meta_title" placeholder="SEO title">
              </div>
              <div class="col-md-8">
                <label class="form-label">Meta Description</label>
                <input type="text" class="form-control" name="meta_description" placeholder="SEO description">
              </div>

              <div class="col-md-6">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                  <option value="published">Published</option>
                  <option value="draft">Draft</option>
                </select>
              </div>

              <div class="col-md-6 d-flex align-items-end justify-content-end gap-2">
                <button type="submit" class="btn btn-orange">
                  <i class="bi bi-save me-1"></i> Create Page
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Page Modal -->
  <div class="modal fade" id="editPageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Page</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form method="post" autocomplete="off" id="editPageForm">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
            <input type="hidden" name="id" id="editPageId">
            <input type="hidden" name="layout_json" id="edit_layoutJson">

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Page Title *</label>
                <input type="text" class="form-control" name="title" id="editPageTitle" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Slug</label>
                <input type="text" class="form-control" name="slug" id="editPageSlug">
              </div>
              <div class="col-12">
                <label class="form-label">Content</label>
                <textarea class="form-control" name="content" id="editPageContent" rows="8"></textarea>
              </div>
            </div>

            <hr class="my-3">
            <h6 class="mb-2">Layout Controls</h6>

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Container Type</label>
                <select class="form-select" id="edit_layoutContainer">
                  <option value="container">Normal (container)</option>
                  <option value="container-fluid">Full Width (container-fluid)</option>
                </select>
              </div>

              <div class="col-md-4">
                <label class="form-label">Max Content Width (px)</label>
                <div class="d-flex align-items-center gap-2">
                  <input type="range" class="form-range flex-grow-1" min="680" max="1400" step="10" value="920" id="edit_layoutWidthRange">
                  <input type="number" class="form-control" min="680" max="1400" step="10" value="920" id="edit_layoutWidthInput" style="max-width:120px;">
                </div>
              </div>

              <div class="col-md-4">
                <label class="form-label">Content Padding (px)</label>
                <div class="d-flex align-items-center gap-2">
                  <input type="range" class="form-range flex-grow-1" min="0" max="80" step="2" value="32" id="edit_layoutPaddingRange">
                  <input type="number" class="form-control" min="0" max="80" step="2" value="32" id="edit_layoutPaddingInput" style="max-width:120px;">
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Editor Height (px)</label>
                <div class="d-flex align-items-center gap-2">
                  <input type="range" class="form-range flex-grow-1" min="200" max="900" step="10" value="320" id="edit_editorHeightRange">
                  <input type="number" class="form-control" min="200" max="900" step="10" value="320" id="edit_editorHeightInput" style="max-width:140px;">
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Live Preview</label>
                <div class="border rounded p-2 bg-light">
                  <div id="edit_layoutPreview" class="border rounded bg-white" style="max-width:920px; padding:32px;">
                    <div class="text-muted small">Preview box</div>
                    <div class="fw-semibold">Your page content will render like this.</div>
                  </div>
                </div>
              </div>
            </div>

            <hr class="my-3">

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label">Meta Title</label>
                <input type="text" class="form-control" name="meta_title" id="editPageMetaTitle">
              </div>
              <div class="col-md-8">
                <label class="form-label">Meta Description</label>
                <input type="text" class="form-control" name="meta_description" id="editPageMetaDescription">
              </div>

              <div class="col-md-6">
                <label class="form-label">Status</label>
                <select class="form-select" name="status" id="editPageStatus">
                  <option value="published">Published</option>
                  <option value="draft">Draft</option>
                </select>
              </div>

              <div class="col-md-6 d-flex align-items-end justify-content-end gap-2">
                <button type="submit" class="btn btn-orange">
                  <i class="bi bi-save me-1"></i> Update Page
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- View Page Modal -->
  <div class="modal fade" id="viewPageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">View Page</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div id="viewPageContent"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts MUST be before footer (footer closes body/html) -->
  <script>
  function linkRangeAndInput(rangeEl, inputEl, onChange) {
    if (!rangeEl || !inputEl) return;
    rangeEl.addEventListener('input', () => { inputEl.value = rangeEl.value; onChange && onChange(); });
    inputEl.addEventListener('input', () => { rangeEl.value = inputEl.value; onChange && onChange(); });
  }

  function applyPreview(prefix) {
    const container = document.getElementById(prefix + '_layoutContainer')?.value || 'container';
    const w = parseInt(document.getElementById(prefix + '_layoutWidthInput')?.value || '920', 10);
    const p = parseInt(document.getElementById(prefix + '_layoutPaddingInput')?.value || '32', 10);

    const preview = document.getElementById(prefix + '_layoutPreview');
    if (preview) {
      preview.style.maxWidth = w + 'px';
      preview.style.padding = p + 'px';
      preview.dataset.container = container;
    }

    const hidden = document.getElementById(prefix + '_layoutJson');
    if (hidden) {
      hidden.value = JSON.stringify({ container: container, max_width: w, padding: p });
    }
  }

  function applyEditorHeight(prefix) {
    const h = parseInt(document.getElementById(prefix + '_editorHeightInput')?.value || '320', 10);
    const ta = document.getElementById(prefix === 'add' ? 'add_content' : 'editPageContent');
    if (ta) ta.style.height = h + 'px';
  }

  function initLayoutControls(prefix) {
    linkRangeAndInput(
      document.getElementById(prefix + '_layoutWidthRange'),
      document.getElementById(prefix + '_layoutWidthInput'),
      () => applyPreview(prefix)
    );

    linkRangeAndInput(
      document.getElementById(prefix + '_layoutPaddingRange'),
      document.getElementById(prefix + '_layoutPaddingInput'),
      () => applyPreview(prefix)
    );

    linkRangeAndInput(
      document.getElementById(prefix + '_editorHeightRange'),
      document.getElementById(prefix + '_editorHeightInput'),
      () => applyEditorHeight(prefix)
    );

    const sel = document.getElementById(prefix + '_layoutContainer');
    if (sel) sel.addEventListener('change', () => applyPreview(prefix));

    applyPreview(prefix);
    applyEditorHeight(prefix);
  }

  function editPage(id) {
    fetch('<?= ADMIN_URL ?>pages/get_page.php?id=' + id)
      .then(r => r.json())
      .then(data => {
        if (!data.success) { alert('Error loading page data'); return; }

        document.getElementById('editPageId').value = data.page.id;
        document.getElementById('editPageTitle').value = data.page.title;
        document.getElementById('editPageSlug').value = data.page.slug;
        document.getElementById('editPageContent').value = data.page.content || '';
        document.getElementById('editPageMetaTitle').value = data.page.meta_title || '';
        document.getElementById('editPageMetaDescription').value = data.page.meta_description || '';
        document.getElementById('editPageStatus').value = data.page.status || 'published';

        // Layout JSON -> controls
        let layout = {};
        try { layout = JSON.parse(data.page.layout_json || '{}'); } catch(e){}
        const container = layout.container || 'container';
        const maxw = parseInt(layout.max_width || '920', 10);
        const pad  = parseInt(layout.padding || '32', 10);

        document.getElementById('edit_layoutContainer').value = container;
        document.getElementById('edit_layoutWidthRange').value = maxw;
        document.getElementById('edit_layoutWidthInput').value = maxw;
        document.getElementById('edit_layoutPaddingRange').value = pad;
        document.getElementById('edit_layoutPaddingInput').value = pad;

        applyPreview('edit');

        const modal = new bootstrap.Modal(document.getElementById('editPageModal'));
        modal.show();
      })
      .catch(err => { console.error(err); alert('Error loading page data'); });
  }

  function viewPage(id) {
    fetch('<?= ADMIN_URL ?>pages/get_page.php?id=' + id)
      .then(r => r.json())
      .then(data => {
        if (!data.success) { alert('Error loading page data'); return; }
        document.getElementById('viewPageContent').innerHTML = data.page.content || '';
        new bootstrap.Modal(document.getElementById('viewPageModal')).show();
      })
      .catch(err => { console.error(err); alert('Error loading page data'); });
  }

  function deletePage(id) {
    if (!confirm('Are you sure you want to delete this page? This action cannot be undone.')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="${id}">
      <input type="hidden" name="csrf_token" value="${document.querySelector('input[name="csrf_token"]').value}">
    `;
    document.body.appendChild(form);
    form.submit();
  }

  document.addEventListener('DOMContentLoaded', function() {
    // init both modals controls
    initLayoutControls('add');
    initLayoutControls('edit');

    // auto slug for ADD modal only
    const titleInput = document.getElementById('add_title');
    const slugInput  = document.getElementById('add_slug');
    if (titleInput && slugInput) {
      titleInput.addEventListener('input', function() {
        if (!slugInput.value) {
          slugInput.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-+|-+$)/g,'');
        }
      });
    }

    // When opening Add modal, reset layout defaults each time
    const addModalEl = document.getElementById('addPageModal');
    if (addModalEl) {
      addModalEl.addEventListener('show.bs.modal', function(){
        document.getElementById('add_layoutContainer').value = 'container';
        document.getElementById('add_layoutWidthRange').value = 920;
        document.getElementById('add_layoutWidthInput').value = 920;
        document.getElementById('add_layoutPaddingRange').value = 32;
        document.getElementById('add_layoutPaddingInput').value = 32;
        document.getElementById('add_editorHeightRange').value = 320;
        document.getElementById('add_editorHeightInput').value = 320;
        applyPreview('add');
        applyEditorHeight('add');
      });
    }
  });
  </script>

</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
