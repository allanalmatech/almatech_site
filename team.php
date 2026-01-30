<?php
declare(strict_types=1);

$page_title = "Team Member | Alma Tech Consults";
$active = "about";

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/gate.php';
require_once __DIR__ . '/includes/header.php';

if (!function_exists('h')) {
  function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$db = $GLOBALS['db'] ?? ($mysqli ?? null);
$slug = trim((string)($_GET['slug'] ?? ''));

// Base url (works whether BASE_URL is constant or global)
$BASE = rtrim((string)($GLOBALS['BASE_URL'] ?? (defined('BASE_URL') ? BASE_URL : '')), '/');

if ($slug === '' || !($db instanceof mysqli)) {
  http_response_code(404);
  echo '<div class="container py-5"><h1>Profile not found</h1></div>';
  require_once __DIR__ . '/includes/footer.php';
  exit;
}

// Only select what you need (matches your columns)
$stmt = $db->prepare("
  SELECT
    id,name,slug,role,short_desc,bio,photo,email,phone,linkedin,github,website,skills,status
  FROM team_members
  WHERE slug=? AND status='active'
  LIMIT 1
");
$stmt->bind_param("s", $slug);
$stmt->execute();
$m = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$m) {
  http_response_code(404);
  echo '<div class="container py-5"><h1>Profile not found</h1></div>';
  require_once __DIR__ . '/includes/footer.php';
  exit;
}

// Correct path: main uploads folder
$img = !empty($m['photo']) ? ($BASE . "uploads/team/" . rawurlencode((string)$m['photo'])) : '';

// Skills can be CSV or JSON - support both
$skills = [];
if (!empty($m['skills'])) {
  $raw = trim((string)$m['skills']);
  if ($raw !== '' && ($raw[0] === '[' || $raw[0] === '{')) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) $skills = array_filter(array_map('trim', $decoded));
  } else {
    $skills = array_filter(array_map('trim', explode(',', $raw)));
  }
}
?>

<section class="hero">
  <div class="container py-5">
    <div class="mb-3 small text-muted">
      <a class="text-decoration-none" href="index.php">Home</a>
      <span class="mx-2">/</span>
      <a class="text-decoration-none" href="about.php">About</a>
      <span class="mx-2">/</span>
      <span class="text-orange fw-semibold"><?= h((string)$m['name']) ?></span>
    </div>

    <div class="row g-4 align-items-center">
      <div class="col-lg-4">
        <div class="service-card">
          <div style="width:100%;aspect-ratio:1/1;border-radius:18px;overflow:hidden;background:#f2f2f2;">
            <?php if ($img): ?>
              <img src="<?= h($img) ?>" alt="<?= h((string)$m['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
            <?php else: ?>
              <div class="h-100 d-flex align-items-center justify-content-center text-muted">
                <i class="bi bi-person" style="font-size:40px;"></i>
              </div>
            <?php endif; ?>
          </div>

          <div class="mt-3">
            <div class="fw-bold h5 mb-0"><?= h((string)$m['name']) ?></div>
            <div class="text-orange fw-semibold"><?= h((string)$m['role']) ?></div>

            <?php if (!empty($m['short_desc'])): ?>
              <div class="text-muted small mt-2"><?= h((string)$m['short_desc']) ?></div>
            <?php endif; ?>

            <?php if (!empty($m['email']) || !empty($m['phone'])): ?>
              <hr>
              <?php if (!empty($m['email'])): ?>
                <div class="small text-muted"><i class="bi bi-envelope me-2"></i><?= h((string)$m['email']) ?></div>
              <?php endif; ?>
              <?php if (!empty($m['phone'])): ?>
                <div class="small text-muted mt-1"><i class="bi bi-telephone me-2"></i><?= h((string)$m['phone']) ?></div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-8">
        <div class="service-card">
          <h2 class="h5 fw-bold mb-2">Professional Profile</h2>

          <?php if (!empty($m['bio'])): ?>
            <div class="text-muted"><?= nl2br(h((string)$m['bio'])) ?></div>
          <?php else: ?>
            <div class="text-muted">No profile details added yet.</div>
          <?php endif; ?>

          <?php if (!empty($skills)): ?>
            <hr>
            <div class="fw-semibold mb-2">Skills</div>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach ($skills as $s): ?>
                <span class="chip"><?= h((string)$s) ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($m['linkedin']) || !empty($m['github']) || !empty($m['website'])): ?>
            <hr>
            <div class="d-flex flex-wrap gap-2">
              <?php if (!empty($m['linkedin'])): ?>
                <a class="btn btn-outline-orange btn-sm" href="<?= h((string)$m['linkedin']) ?>" target="_blank" rel="noopener">
                  <i class="bi bi-linkedin me-1"></i>LinkedIn
                </a>
              <?php endif; ?>

              <?php if (!empty($m['github'])): ?>
                <a class="btn btn-outline-orange btn-sm" href="<?= h((string)$m['github']) ?>" target="_blank" rel="noopener">
                  <i class="bi bi-github me-1"></i>GitHub
                </a>
              <?php endif; ?>

              <?php if (!empty($m['website'])): ?>
                <a class="btn btn-outline-orange btn-sm" href="<?= h((string)$m['website']) ?>" target="_blank" rel="noopener">
                  <i class="bi bi-link-45deg me-1"></i>Website
                </a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
