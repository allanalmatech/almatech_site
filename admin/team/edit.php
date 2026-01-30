<?php
declare(strict_types=1);

// Early authentication check before any HTML output
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_admin_login();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../includes/upload_lib.php';

$db = $GLOBALS['db'] ?? $mysqli ?? null;
if (!($db instanceof mysqli)) {
  http_response_code(500);
  die("DB not available");
}

$uploadDir = __DIR__ . '/../../uploads/team';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  flash_set('danger', 'Invalid team member selected.');
  redirect('list.php');
}

// Load team member
$stmt = $db->prepare("SELECT * FROM team_members WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$member = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$member) {
  flash_set('danger', 'Team member not found.');
  redirect('list.php');
}

// Initialize variables
$name = (string)($member['name'] ?? '');
$role = (string)($member['role'] ?? '');
$short_desc = (string)($member['short_desc'] ?? '');
$bio = (string)($member['bio'] ?? '');
$skills = (string)($member['skills'] ?? '');
$status = (string)($member['status'] ?? 'active');
$sort_order = (int)($member['sort_order'] ?? 0);
$current_photo = (string)($member['photo'] ?? '');

// Social media
$socials = [
  'website'  => (string)($member['website'] ?? ''),
  'linkedin' => (string)($member['linkedin'] ?? ''),
  'github'   => (string)($member['github'] ?? ''),
];

$errors = [];

// Handle POST (update) - BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = (string)($_POST['csrf_token'] ?? '');

  if (!csrf_validate($token)) {
    $errors[] = "Security check failed. Please refresh and try again.";
  } else {
    $name = trim((string)($_POST['name'] ?? ''));
    $role = trim((string)($_POST['role'] ?? ''));
    $short_desc = trim((string)($_POST['short_desc'] ?? ''));
    $bio = trim((string)($_POST['bio'] ?? ''));
    $status = in_array((string)($_POST['status'] ?? ''), ['active','inactive'], true) ? (string)$_POST['status'] : 'active';
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $skills = trim((string)($_POST['skills'] ?? ''));

    $socials['website']  = trim((string)($_POST['website'] ?? ''));
    $socials['linkedin'] = trim((string)($_POST['linkedin'] ?? ''));
    $socials['github']   = trim((string)($_POST['github'] ?? ''));

    if ($name === '' || $role === '') {
      $errors[] = "Name and Role are required.";
    } else {
      try {
        // Handle photo upload
        $new_photo = $current_photo;
        if (!empty($_FILES['photo']['name'])) {
          $uploaded = upload_image_or_null($_FILES['photo'], $uploadDir);
          if ($uploaded) {
            $new_photo = $uploaded;

            // Delete old photo if different
            if ($new_photo !== $current_photo && $current_photo) {
              $old_path = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $current_photo;
              if (is_file($old_path)) {
                @unlink($old_path);
              }
            }
          }
        }

        // Update team member
        $stmt = $db->prepare(
          "UPDATE team_members
           SET name=?, role=?, short_desc=?, bio=?, photo=?, skills=?, status=?, sort_order=?, linkedin=?, github=?, website=?
           WHERE id=?"
        );

        // ✅ FIXED: 12 bind vars => 12 type chars (7s + i + 3s + i)
        $stmt->bind_param(
          "sssssssisssi",
          $name,
          $role,
          $short_desc,
          $bio,
          $new_photo,
          $skills,
          $status,
          $sort_order,
          $socials['linkedin'],
          $socials['github'],
          $socials['website'],
          $id
        );

        $stmt->execute();
        $stmt->close();

        flash_set('success', 'Team member updated successfully.');
        redirect('list.php');
      } catch (Throwable $e) {
        $errors[] = $e->getMessage();
      }
    }
  }
}

$page_title = "Edit Team Member | Admin";
$page_heading = "Edit Team Member";
$page_subtitle = "Update team member profile";
$active_admin = "team";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';

// Compute correct preview URL for photo
$baseUrl = rtrim((string)($GLOBALS['BASE_URL'] ?? (defined('BASE_URL') ? BASE_URL : '')), '/') . '/';

// If your uploads are in /admin/uploads/team/, change here accordingly.
// Your current script uses /uploads/team/ (public root).
$photoUrl = $current_photo ? ($baseUrl . "uploads/team/" . rawurlencode($current_photo)) : '';
?>
<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-0">Edit Team Member</h4>
        <small class="text-muted">Update team member profile</small>
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
          <label class="form-label">Name *</label>
          <input type="text" name="name" class="form-control" value="<?= h($name) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Role *</label>
          <input type="text" name="role" class="form-control" value="<?= h($role) ?>" required>
        </div>

        <div class="col-md-8">
          <label class="form-label">Short Description</label>
          <input type="text" name="short_desc" class="form-control" value="<?= h($short_desc) ?>" maxlength="255" placeholder="Brief description for team cards">
        </div>

        <div class="col-md-4">
          <label class="form-label">Status</label>
          <select class="form-select" name="status">
            <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
            <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Sort Order</label>
          <input type="number" class="form-control" name="sort_order" value="<?= (int)$sort_order ?>">
        </div>

        <div class="col-md-9">
          <label class="form-label">Current Photo</label>
          <div class="d-flex align-items-center gap-3">
            <div style="width:64px;height:64px;border-radius:18px;overflow:hidden;background:#f2f2f2;">
              <?php if ($photoUrl): ?>
                <img src="<?= h($photoUrl) ?>" alt="photo" style="width:100%;height:100%;object-fit:cover;">
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
          <input type="file" class="form-control" name="photo" accept="image/*">
        </div>

        <div class="col-12">
          <label class="form-label">Bio (Profile details)</label>
          <textarea class="form-control" name="bio" rows="6"><?= h($bio) ?></textarea>
        </div>

        <div class="col-md-6">
          <label class="form-label">Skills (comma-separated)</label>
          <input class="form-control" name="skills" value="<?= h($skills) ?>" placeholder="PHP, JavaScript, MySQL...">
        </div>

        <div class="col-12"><hr></div>

        <div class="col-md-4">
          <label class="form-label">Website</label>
          <input class="form-control" name="website" value="<?= h($socials['website']) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">LinkedIn</label>
          <input class="form-control" name="linkedin" value="<?= h($socials['linkedin']) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">GitHub</label>
          <input class="form-control" name="github" value="<?= h($socials['github']) ?>">
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
