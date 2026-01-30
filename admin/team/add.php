<?php
declare(strict_types=1);

// Early authentication check before any HTML output
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_admin_login();

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../includes/upload_lib.php';

$db = $GLOBALS['db'] ?? $mysqli ?? null;
$uploadDir = __DIR__ . '/../../uploads/team';

$err = '';
$ok = '';

$name = '';
$role = '';
$short_desc = '';
$bio = '';
$status = 'active';
$sort_order = 0;
$skills = '';
$socials = ['website'=>'','linkedin'=>'','github'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_validate((string)($_POST['csrf_token'] ?? ''))) {
    $err = "Security check failed. Please refresh and try again.";
  } else {

  $name = trim((string)($_POST['name'] ?? ''));
  $role = trim((string)($_POST['role'] ?? ''));
  $short_desc = trim((string)($_POST['short_desc'] ?? ''));
  $bio  = trim((string)($_POST['bio'] ?? ''));
  $status = in_array((string)($_POST['status'] ?? ''), ['active','inactive'], true) ? (string)$_POST['status'] : 'active';
  $sort_order = (int)($_POST['sort_order'] ?? 0);
  $skills = trim((string)($_POST['skills'] ?? ''));

  $socials['website']  = trim((string)($_POST['website'] ?? ''));
  $socials['linkedin'] = trim((string)($_POST['linkedin'] ?? ''));
  $socials['github'] = trim((string)($_POST['github'] ?? ''));

  if ($name === '' || $role === '') {
    $err = "Name and Role are required.";
  } else {
    try {
      $slug = unique_slug($db, 'team_members', safe_slug($name), null);
      $photoName = upload_image_or_null($_FILES['photo'] ?? [], $uploadDir);

      $socials_json = json_encode($socials, JSON_UNESCAPED_SLASHES);

    $sql = "INSERT INTO team_members
        (name, role, short_desc, bio, slug, photo, skills, status, sort_order, linkedin, github, website)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";

$st = $db->prepare($sql);
if (!$st) {
  throw new RuntimeException("Prepare failed: " . $db->error);
}

$st->bind_param(
  "ssssssssisss", // 8 strings, 1 int, 3 strings = 12
  $name,
  $role,
  $short_desc,
  $bio,
  $slug,
  $photoName,
  $skills,
  $status,
  $sort_order,
  $socials['linkedin'],
  $socials['github'],
  $socials['website']
);

$st->execute();


      header("Location: list.php");
      exit;
    } catch (Throwable $e) {
      $err = $e->getMessage();
    }
  }
  }
}

$page_title = "Add Team Member | Admin";
$page_heading = "Add Team Member";
$page_subtitle = "Create a new team member profile";
$active_admin = "team";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>
<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-0">Add Team Member</h4>
        <small class="text-muted">Create a new team member profile</small>
      </div>
      <a class="btn btn-outline-secondary" href="list.php">
        <i class="bi bi-arrow-left me-1"></i> Back
      </a>
    </div>

    <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Name *</label>
        <input class="form-control" name="name" value="<?= htmlspecialchars($name) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label">Role *</label>
        <input class="form-control" name="role" value="<?= htmlspecialchars($role) ?>" required>
      </div>

      <div class="col-md-8">
        <label class="form-label">Short Description</label>
        <input class="form-control" name="short_desc" value="<?= htmlspecialchars($short_desc) ?>" maxlength="255" placeholder="Brief description for team cards">
      </div>

      <div class="col-md-4">
        <label class="form-label">Status</label>
        <select class="form-select" name="status">
          <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
        </select>
      </div>

      <div class="col-12">
        <label class="form-label">Bio (Profile details)</label>
        <textarea class="form-control" name="bio" rows="6"><?= htmlspecialchars($bio) ?></textarea>
      </div>

      <div class="col-md-6">
        <label class="form-label">Skills (comma-separated)</label>
        <input class="form-control" name="skills" value="<?= htmlspecialchars($skills) ?>" placeholder="PHP, JavaScript, MySQL...">
      </div>

      <div class="col-md-6">
        <label class="form-label">Photo (jpg/png/webp)</label>
        <input type="file" class="form-control" name="photo" accept="image/*">
      </div>

      <div class="col-12"><hr></div>

      <div class="col-md-4">
        <label class="form-label">Website</label>
        <input class="form-control" name="website" value="<?= htmlspecialchars($socials['website']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">LinkedIn</label>
        <input class="form-control" name="linkedin" value="<?= htmlspecialchars($socials['linkedin']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">GitHub</label>
        <input class="form-control" name="github" value="<?= htmlspecialchars($socials['github']) ?>">
      </div>
    </div>

    <div class="mt-3 d-flex gap-2">
      <button class="btn btn-orange">
        <i class="bi bi-save me-1"></i> Save
      </button>
      <a class="btn btn-outline-secondary" href="list.php">Cancel</a>
    </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
