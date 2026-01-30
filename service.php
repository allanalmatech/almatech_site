<?php
declare(strict_types=1);

$page_title = "Service | Alma Tech Consults";
$active = "services";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/db.php';

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$slug = trim((string)($_GET['slug'] ?? ''));

$service = null;
if ($slug !== '') {
  $stmt = $mysqli->prepare("SELECT id, title, slug, short_desc, description, icon FROM services WHERE slug = ? AND is_active = 1 LIMIT 1");
  $stmt->bind_param("s", $slug);
  $stmt->execute();
  $res = $stmt->get_result();
  $service = $res ? $res->fetch_assoc() : null;
  $stmt->close();
}

if (!$service) {
  http_response_code(404);
}
?>

<?php if (!$service): ?>
  <section class="section">
    <div class="container">
      <div class="service-card">
        <h1 class="h3 fw-bold mb-2">Service not found</h1>
        <p class="text-muted mb-3">The service you’re looking for does not exist or is not available.</p>
        <a class="btn btn-orange" href="services.php"><i class="bi bi-arrow-left me-1"></i> Back to Services</a>
      </div>
    </div>
  </section>

<?php else: ?>
  <?php
    $title = (string)$service['title'];
    $short = (string)($service['short_desc'] ?? '');
    $desc  = (string)($service['description'] ?? '');
    $icon  = (string)($service['icon'] ?? '');

    $iconFilePath = __DIR__ . '/uploads/services/' . $icon;
    $hasImage = ($icon !== '' && is_file($iconFilePath));
    $iconUrl = 'uploads/services/' . $icon;

    // WhatsApp (replace with your number)
    $wa_number = "256XXXXXXXXX"; // no +
    $wa_text = "Hello Alma Tech Consults, I need help with: " . $title;
    $wa_link = "https://wa.me/" . $wa_number . "?text=" . urlencode($wa_text);
  ?>

  <!-- Hero -->
  <section class="hero">
    <div class="container py-5">
      <div class="mb-3 small text-muted">
        <a class="text-decoration-none" href="index.php">Home</a>
        <span class="mx-2">/</span>
        <a class="text-decoration-none" href="services.php">Services</a>
        <span class="mx-2">/</span>
        <span class="text-orange fw-semibold"><?= h($title) ?></span>
      </div>

      <div class="row g-4 align-items-center">
        <div class="col-lg-7">
          <div class="badge-soft mb-3">
            <i class="bi bi-grid-1x2-fill me-1"></i> Service
          </div>

          <h1 class="display-6 fw-bold mb-3"><?= h($title) ?></h1>

          <?php if ($short !== ''): ?>
            <p class="lead text-muted mb-4"><?= h($short) ?></p>
          <?php endif; ?>

          <div class="d-flex flex-wrap gap-2">
            <a href="contact.php?service=<?= urlencode($title) ?>" class="btn btn-orange btn-lg">
              Request Quote <i class="bi bi-arrow-right ms-1"></i>
            </a>
            <a href="<?= h($wa_link) ?>" target="_blank" rel="noopener" class="btn btn-outline-orange btn-lg">
              WhatsApp <i class="bi bi-whatsapp ms-1"></i>
            </a>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="post-hero-cover">
            <?php if ($hasImage): ?>
              <img src="<?= h($iconUrl) ?>" alt="<?= h($title) ?>">
            <?php else: ?>
              <div class="project-cover-fallback">
                <i class="bi bi-gear-wide-connected"></i>
                <div class="mt-2 fw-semibold"><?= h($title) ?></div>
                <div class="small text-muted">Add an image in uploads/services</div>
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
          <div class="service-card">
            <h2 class="h5 fw-bold mb-2">Service Details</h2>

            <?php if (trim($desc) !== ''): ?>
              <div class="text-muted" style="line-height:1.8;">
                <?= nl2br(h($desc)) ?>
              </div>
            <?php else: ?>
              <p class="text-muted mb-0">
                More details will be added soon. Contact us to get a quote for this service.
              </p>
            <?php endif; ?>

            <hr class="my-4">

            <div class="d-flex flex-wrap gap-2">
              <a class="btn btn-orange" href="contact.php?service=<?= urlencode($title) ?>">
                Get a Quote
              </a>
              <a class="btn btn-outline-orange" href="services.php">
                Browse Other Services
              </a>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="service-card">
            <div class="fw-bold mb-2">Quick Request</div>
            <p class="text-muted small mb-3">
              Tell us what you need and we’ll respond quickly with a clear quote and timeline.
            </p>
            <a href="contact.php?service=<?= urlencode($title) ?>" class="btn btn-orange w-100">
              Request Quote <i class="bi bi-arrow-right ms-1"></i>
            </a>
          </div>

          <div class="service-card mt-3">
            <div class="fw-bold mb-2">Need a dashboard?</div>
            <p class="text-muted small mb-3">
              We can build a website + admin dashboard so you can manage services, projects, blog posts, and leads.
            </p>
            <a href="contact.php?service=<?= urlencode("Website + Admin Dashboard") ?>" class="btn btn-outline-orange w-100">
              Ask About Packages
            </a>
          </div>
        </div>

      </div>
    </div>
  </section>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
