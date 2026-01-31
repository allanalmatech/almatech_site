<?php
declare(strict_types=1);

$page_title = "Projects | Alma Tech Consults";
$active = "projects";

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/gate.php';

$db = $GLOBALS['db'] ?? $mysqli ?? null;

// -------------------------------
// Fetch projects + categories
// -------------------------------
$projects = [];
$categories = ['All'];

if ($db instanceof mysqli) {

  $sql = "
    SELECT title, slug, short_desc, category, cover_image, created_at
    FROM projects
    WHERE status = 'completed'
    ORDER BY created_at DESC
  ";

  $stmt = $db->prepare($sql);

  if ($stmt) {
    $stmt->execute();
    $stmt->bind_result($title, $slug, $short_desc, $category, $cover_image, $created_at);

    while ($stmt->fetch()) {
      $projects[] = [
        'title'    => (string)$title,
        'slug'     => (string)$slug,
        'desc'     => (string)$short_desc,
        'category' => $category ? (string)$category : 'Other',
        'year'     => $created_at ? date('Y', strtotime((string)$created_at)) : date('Y'),
        'client'   => 'Client', // optional: replace with DB field later
        'cover'    => !empty($cover_image) ? (string)$cover_image : 'assets/img/project-placeholder-1.jpg',
      ];
    }

    $stmt->close();
  }

  // categories
  $unique = [];
  foreach ($projects as $p) {
    $cat = (string)$p['category'];
    if (!in_array($cat, $unique, true)) {
      $unique[] = $cat;
    }
  }
  $categories = array_merge(['All'], $unique);
}

require_once __DIR__ . '/includes/header.php';

// Base URL safe join
$BASE = defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '';
?>

<!-- Page Hero -->
<section class="hero">
  <div class="container py-5">
    <div class="row g-4 align-items-center">
      <div class="col-lg-8">
        <div class="badge-soft mb-3">
          <i class="bi bi-briefcase-fill me-1"></i> Portfolio
        </div>
        <h1 class="display-6 fw-bold mb-3">Projects we’ve delivered for clients.</h1>
        <p class="lead text-muted mb-0">
          Explore our work across websites, branding, digital marketing, IT support, and connectivity solutions.
        </p>
      </div>

      <div class="col-lg-4">
        <div class="hero-card p-4">
          <div class="fw-semibold mb-2">Want something similar?</div>
          <p class="text-muted small mb-3">Tell us what you need and we’ll recommend the best approach and quote.</p>
          <a href="contact.php" class="btn btn-orange w-100 btn-lg">
            Get a Quote <i class="bi bi-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Projects -->
<section class="section">
  <div class="container">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
      <div>
        <h2 class="h3 fw-bold mb-1">All Projects</h2>
        <p class="text-muted mb-0">Filter by category to find what you need.</p>
      </div>

      <div class="d-flex flex-wrap gap-2" id="projectFilters">
        <?php foreach ($categories as $c): ?>
          <button type="button"
                  class="btn btn-filter <?= $c === 'All' ? 'active' : '' ?>"
                  data-filter="<?= h($c) ?>">
            <?= h($c) ?>
          </button>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="row g-3 g-lg-4" id="projectGrid">
      <?php foreach ($projects as $p): ?>
        <?php
          $cat = (string)$p['category'];
          $cover = (string)$p['cover'];

          // safe join for image + project url
          $image_url = $BASE ? ($BASE . '/' . ltrim($cover, '/')) : ltrim($cover, '/');
          $project_url = $BASE ? ($BASE . '/projects/' . rawurlencode((string)$p['slug'])) : ('projects/' . rawurlencode((string)$p['slug']));
        ?>
        <div class="col-md-6 col-lg-4 project-item" data-category="<?= h($cat) ?>">
          <div class="project-card h-100 d-flex flex-column">
            <div class="project-cover">
              <?php if (!empty($image_url)): ?>
                <img src="<?= h($image_url) ?>" alt="<?= h($p['title']) ?>">
              <?php else: ?>
                <div class="project-cover-fallback">
                  <i class="bi bi-image"></i>
                </div>
              <?php endif; ?>
              <span class="project-badge"><?= h($cat) ?></span>
            </div>

            <div class="project-body d-flex flex-column flex-grow-1">
              <h5 class="fw-bold mb-2"><?= h($p['title']) ?></h5>
              <p class="text-muted mb-3"><?= h($p['desc']) ?></p>

              <div class="project-meta mt-auto mb-3">
                <span><i class="bi bi-calendar3 me-1"></i><?= h($p['year']) ?></span>
                <span><i class="bi bi-building me-1"></i><?= h($p['client']) ?></span>
              </div>

              <div class="d-grid gap-2">
                <a href="<?= h($project_url) ?>" class="btn btn-orange w-100">
                  View Project
                </a>
                <a href="contact.php?service=<?= urlencode((string)$p['title']) ?>" class="btn btn-outline-orange w-100">
                  Get Quote
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <?php if (empty($projects)): ?>
        <div class="col-12">
          <div class="text-center py-5">
            <h3 class="mb-2">No projects found</h3>
            <p class="text-muted mb-0">Check back later for our latest work.</p>
          </div>
        </div>
      <?php endif; ?>
    </div>

  </div>
</section>

<!-- CTA -->
<section class="section section-soft">
  <div class="container">
    <div class="cta p-4 p-md-5">
      <div class="row align-items-center g-3">
        <div class="col-lg-8">
          <h2 class="h4 fw-bold mb-2">Need a website + dashboard like these projects?</h2>
          <p class="mb-0 text-muted">We can build a complete system: public site + admin panel to manage content, leads, and updates.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="contact.php" class="btn btn-orange btn-lg w-100 w-lg-auto">
            Start a Project <i class="bi bi-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Page styles (keep here or move to your CSS file) -->
<style>
  .project-card{
    border:1px solid #e2e8f0;
    border-radius:18px;
    overflow:hidden;
    background:#fff;
    transition:transform .15s ease, box-shadow .15s ease;
  }
  .project-card:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 30px rgba(15,23,42,.10);
  }
  .project-cover{
    position:relative;
    height:210px;
    background:#fff7ed;
  }
  .project-cover img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
  }
  .project-cover-fallback{
    width:100%;
    height:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#fff7ed;
    color:#f97316;
    font-size:2rem;
  }
  .project-badge{
    position:absolute;
    top:12px;
    left:12px;
    padding:6px 10px;
    border-radius:999px;
    background:rgba(255,255,255,.92);
    border:1px solid rgba(249,115,22,.22);
    color:#c2410c;
    font-weight:800;
    font-size:.75rem;
  }
  .project-body{ padding:18px; }
  .project-meta{
    display:flex;
    gap:14px;
    font-size:.875rem;
    color:#64748b;
    flex-wrap:wrap;
  }
  .project-meta i{ color:#f97316; }

  .btn-orange{
    background:#f97316 !important;
    border-color:#f97316 !important;
    color:#fff !important;
  }
  .btn-orange:hover{
    background:#ea580c !important;
    border-color:#ea580c !important;
    color:#fff !important;
  }
  .btn-outline-orange{
    background:transparent !important;
    border:1px solid #f97316 !important;
    color:#f97316 !important;
  }
  .btn-outline-orange:hover{
    background:#fff7ed !important;
    color:#ea580c !important;
    border-color:#ea580c !important;
  }

  /* Filter button styling (if you don't already have it) */
  .btn-filter{
    border:1px solid #e2e8f0;
    background:#fff;
    border-radius:999px;
    padding:.45rem .85rem;
    font-weight:600;
  }
  .btn-filter.active{
    border-color:#f97316;
    background:#fff7ed;
    color:#c2410c;
  }
</style>

<!-- Simple filter JS -->
<script>
  (function(){
    const filters = document.querySelectorAll('#projectFilters .btn-filter');
    const items = document.querySelectorAll('#projectGrid .project-item');

    function setActive(btn){
      filters.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    }

    function applyFilter(cat){
      items.forEach(it => {
        const c = it.getAttribute('data-category');
        const shouldShow = (cat === 'All' || c === cat);
        it.style.display = shouldShow ? '' : 'none';
      });
    }

    filters.forEach(btn => {
      btn.addEventListener('click', () => {
        const cat = btn.getAttribute('data-filter') || 'All';
        setActive(btn);
        applyFilter(cat);
      });
    });
  })();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>