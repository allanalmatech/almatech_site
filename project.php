<?php
declare(strict_types=1);

$page_title = "Project | Alma Tech Consults";
$active = "projects";

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/config.php';

$db = $GLOBALS['db'] ?? $mysqli ?? null;

// Get slug from URL
$slug = trim((string)($_GET['slug'] ?? ''));

if (!$slug) {
  http_response_code(404);
  require_once __DIR__ . '/includes/header.php';
  echo '<div class="container py-5"><h1>Project not found</h1><p>No project slug provided.</p></div>';
  require_once __DIR__ . '/includes/footer.php';
  exit;
}

if (!($db instanceof mysqli)) {
  $project = null;
} else {
  // Fetch project from database
  $stmt = $db->prepare("
    SELECT * FROM projects 
    WHERE slug = ? AND status = 'completed' 
    LIMIT 1
  ");
  
  if ($stmt) {
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();
    $stmt->close();
  } else {
    $project = null;
  }
}

if (!$project) {
  http_response_code(404);
  require_once __DIR__ . '/includes/header.php';
  echo '<div class="container py-5"><h1>Project not found</h1><p>This project does not exist or is not published.</p></div>';
  require_once __DIR__ . '/includes/footer.php';
  exit;
}

// Decode tech stack
$tech_stack_arr = [];
if (!empty($project['tech_stack'])) {
  $decoded = json_decode((string)$project['tech_stack'], true);
  if (is_array($decoded)) $tech_stack_arr = $decoded;
}

$page_title = $project['title'] . ' | Alma Tech Consults';
require_once __DIR__ . '/includes/header.php';

?>

<?php if (!$project): ?>
  <section class="section">
    <div class="container">
      <div class="service-card">
        <h1 class="h3 fw-bold mb-2">Project not found</h1>
        <p class="text-muted mb-3">The project you’re looking for does not exist or the link is incorrect.</p>
        <a class="btn btn-orange" href="projects.php"><i class="bi bi-arrow-left me-1"></i> Back to Projects</a>
      </div>
    </div>
  </section>

<?php else: ?>

  <?php
    $cover = (string)$project['cover_image'];
    $image_url = !empty($cover) ? BASE_URL . ltrim($cover, '/') : null;
  ?>

  <!-- Hero -->
  <section class="hero">
    <div class="container py-5">
      <div class="mb-3 small text-muted">
        <a class="text-decoration-none" href="index.php">Home</a>
        <span class="mx-2">/</span>
        <a class="text-decoration-none" href="projects.php">Projects</a>
        <span class="mx-2">/</span>
        <span class="text-orange fw-semibold"><?= h($project['title']) ?></span>
      </div>

      <div class="row g-4 align-items-center">
        <div class="col-lg-7">
          <span class="pill-tag"><?= h($project['category'] ?? 'Other') ?></span>
          <h1 class="display-6 fw-bold mt-3 mb-3"><?= h($project['title']) ?></h1>
          <p class="lead text-muted mb-4"><?= h($project['short_desc']) ?></p>

          <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-orange btn-lg" href="contact.php?service=<?= urlencode($project['title']) ?>">
              Request Similar Project <i class="bi bi-arrow-right ms-1"></i>
            </a>
            <a class="btn btn-outline-orange btn-lg" href="projects.php">
              Back to Projects
            </a>
          </div>

          <div class="d-flex flex-wrap gap-3 mt-4 small text-muted">
            <div><i class="bi bi-calendar3 me-1 text-orange"></i><?= date('Y', strtotime($project['created_at'])) ?></div>
            <div><i class="bi bi-building me-1 text-orange"></i>Client</div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="project-hero-cover">
            <?php if ($image_url): ?>
              <img src="<?= h($image_url) ?>" alt="<?= h($project['title']) ?>">
            <?php else: ?>
              <div class="project-cover-fallback">
                <i class="bi bi-image"></i>
                <div class="mt-2 fw-semibold">Project Cover</div>
                <div class="small text-muted">No image uploaded</div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Content -->
  <section class="section">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-8">

          <?php if (!empty($project['full_desc'])): ?>
            <div class="service-card mb-4">
              <h2 class="h4 fw-bold mb-3">Project Details</h2>
              <div class="text-muted"><?= nl2br(h($project['full_desc'])) ?></div>
            </div>
          <?php endif; ?>

        </div>

        <div class="col-lg-4">
          <?php if (!empty($tech_stack_arr)): ?>
            <div class="service-card mb-4">
              <h3 class="h6 fw-bold mb-3">Tech Stack</h3>
              <div class="d-flex flex-wrap gap-2">
                <?php foreach ($tech_stack_arr as $t): ?>
                  <span class="chip"><?= h($t) ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="cta p-4">
            <div class="fw-semibold mb-2">Want this exact setup?</div>
            <div class="small text-muted mb-3">We can build your public website + admin dashboard to manage everything.</div>
            <a class="btn btn-orange w-100 btn-lg" href="contact.php?service=Website%20%2B%20Dashboard">
              Get a Quote <i class="bi bi-arrow-right ms-1"></i>
            </a>
          </div>

        </div>
      </div>
    </div>
  </section>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
