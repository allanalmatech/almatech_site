<?php
declare(strict_types=1);

$page_title = "Services | Alma Tech Consults";
$active = "services";

// ✅ Load core first so header has DB + constants
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/gate.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/header.php';

// Fallback escape helper if helpers.php didn’t define it
if (!function_exists('h')) {
  function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

// mysqli handle
$db = $GLOBALS['db'] ?? ($mysqli ?? null);
if (!($db instanceof mysqli)) {
  echo '<div class="container py-5"><h2>Database Error</h2><p>Unable to connect to database.</p></div>';
  require_once __DIR__ . '/includes/footer.php';
  exit;
}

$servicesHeroCover = '';
$servicesHeroTextClass = 'text-muted';
if (function_exists('setting_get')) {
  $servicesCover = trim((string)setting_get($db, 'services_cover_image', ''));
  if ($servicesCover !== '') {
    $servicesHeroCover = (strpos($servicesCover, 'http') === 0)
      ? $servicesCover
      : rtrim((string)BASE_URL, '/') . '/' . ltrim($servicesCover, '/');
    $servicesHeroTextClass = '';
  }
}

/**
 * Helper: build a safe preview snippet from description
 */
function excerpt(string $text, int $max = 120): string {
  $text = trim(strip_tags($text));
  if ($text === '') return '';
  if (mb_strlen($text) <= $max) return $text;
  $cut = mb_substr($text, 0, $max);
  $pos = mb_strrpos($cut, ' ');
  if ($pos !== false) $cut = mb_substr($cut, 0, $pos);
  return $cut . '…';
}

/**
 * Fetch active services from DB (mysqli safe, works without get_result too)
 */
$services = [];
$stmt = $db->prepare("
  SELECT id, title, slug, short_desc, description, icon
  FROM services
  WHERE is_active = 1
  ORDER BY id DESC
");

if ($stmt) {
  $stmt->execute();

  if (method_exists($stmt, 'get_result')) {
    $res = $stmt->get_result();
    $services = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  } else {
    // Fallback bind_result
    $stmt->bind_result($id, $title, $slug, $short_desc, $description, $icon);
    while ($stmt->fetch()) {
      $services[] = [
        'id' => (int)$id,
        'title' => (string)$title,
        'slug' => (string)$slug,
        'short_desc' => (string)$short_desc,
        'description' => (string)$description,
        'icon' => (string)$icon,
      ];
    }
  }

  $stmt->close();
}
?>

<!-- Page Hero -->
<section class="hero<?= $servicesHeroCover !== '' ? ' hero-cover-blur hero-scroll-blur' : '' ?>"<?= $servicesHeroCover !== '' ? ' style="--hero-cover-image:url(\'' . h($servicesHeroCover) . '\');"' : '' ?>>
  <div class="container py-5">
    <div class="row g-4 align-items-center">
      <div class="col-lg-7">
        <div class="badge-soft mb-3">
          <i class="bi bi-grid-1x2-fill me-1"></i> Services
        </div>
        <h1 class="display-6 fw-bold mb-3">Everything you need for business technology & growth.</h1>
        <p class="lead <?= $servicesHeroTextClass ?> mb-4">
          We deliver ICT solutions, web systems, branding, marketing, and support — with a focus on quality, speed, and results.
        </p>

        <div class="d-flex flex-wrap gap-2">
          <a href="contact.php" class="btn btn-orange btn-lg">
            Get a Quote <i class="bi bi-arrow-right ms-1"></i>
          </a>
          <a href="#service-list" class="btn btn-outline-orange btn-lg">
            Browse Services
          </a>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="hero-card p-4">
          <div class="fw-semibold mb-2">How we work</div>
          <ul class="list-unstyled m-0 small text-muted">
            <li class="mb-2"><i class="bi bi-check-circle text-orange me-2"></i> Understand your needs</li>
            <li class="mb-2"><i class="bi bi-check-circle text-orange me-2"></i> Provide a clear quote & timeline</li>
            <li class="mb-2"><i class="bi bi-check-circle text-orange me-2"></i> Deliver and test quality</li>
            <li class="mb-0"><i class="bi bi-check-circle text-orange me-2"></i> Support after delivery</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Services Grid -->
<section id="service-list" class="section">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h2 class="h3 fw-bold mb-1">Our Services</h2>
        <p class="text-muted mb-0">Choose a service and request a quote. We respond quickly.</p>
      </div>

      <a href="contact.php" class="btn btn-outline-orange d-none d-lg-block">
        <i class="bi bi-chat-dots me-1"></i> Talk to Us
      </a>
    </div>

    <div class="row g-3 g-lg-4">

      <?php if (!$services): ?>
        <div class="col-12">
          <div class="service-card">
            <div class="fw-semibold mb-1">No services yet</div>
            <div class="text-muted">Add services from the Admin Dashboard → Services.</div>
          </div>
        </div>
      <?php endif; ?>

      <?php foreach ($services as $s): ?>
        <?php
          $title = (string)($s['title'] ?? '');
          $slug  = (string)($s['slug'] ?? '');
          $short = (string)($s['short_desc'] ?? '');
          $desc  = (string)($s['description'] ?? '');
          $icon  = (string)($s['icon'] ?? '');

          // If icon is an uploaded file, show it as image
          $iconFilePath = __DIR__ . '/uploads/services/' . $icon;
          $hasImage = ($icon !== '' && is_file($iconFilePath));
          $iconUrl = rtrim((string)BASE_URL, '/') . '/uploads/services/' . rawurlencode($icon);

          $summary = $short !== '' ? $short : excerpt($desc, 140);
        ?>

        <div class="col-md-6 col-lg-4">
          <div class="service-card h-100">

            <div class="d-flex justify-content-between align-items-start">
              <div class="service-icon" style="overflow:hidden;">
                <?php if ($hasImage): ?>
                  <img src="<?= h($iconUrl) ?>" alt="<?= h($title) ?>"
                       style="width:100%;height:100%;object-fit:cover;display:block;">
                <?php else: ?>
                  <i class="bi bi-stars"></i>
                <?php endif; ?>
              </div>
              <span class="pill-tag">Service</span>
            </div>

            <h3 class="h5 fw-bold mt-3 mb-2"><?= h($title) ?></h3>
            <p class="text-muted mb-3"><?= h($summary) ?></p>

            <div class="mt-auto">
              <a class="service-read-more text-orange fw-semibold" href="service.php?slug=<?= urlencode($slug) ?>">
                Read more
              </a>
            </div>

          </div>
        </div>
      <?php endforeach; ?>

    </div>

    <!-- Talk to Us button for small screens -->
    <div class="text-center d-lg-none mt-4">
      <a href="contact.php" class="btn btn-outline-orange btn-lg w-100">
        <i class="bi bi-chat-dots me-1"></i> Talk to Us
      </a>
    </div>
  </div>
</section>

<!-- Packages (optional section) -->
<section class="section section-soft">
  <div class="container">
    <div class="row g-4 align-items-center">
      <div class="col-lg-7">
        <h2 class="h3 fw-bold mb-2">Need a package (website + dashboard)?</h2>
        <p class="text-muted mb-0">
          We can build you a public website plus an admin dashboard to manage services, projects, blog posts, and client messages.
        </p>
      </div>
      <div class="col-lg-5">
        <div class="cta p-4">
          <div class="fw-semibold mb-2">Quick request</div>
          <div class="small text-muted mb-3">Tell us what you need and we’ll suggest the best package.</div>
          <a href="contact.php" class="btn btn-orange w-100 btn-lg">
            Start Now <i class="bi bi-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
