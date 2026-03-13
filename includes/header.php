<?php
declare(strict_types=1);

/* -------------------------------------------------
 * Helpers
 * ------------------------------------------------- */
if (!function_exists('h')) {
  function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
  }
}

if (!isset($page_title)) {
  $page_title = 'Alma Tech Consults';
}

/* -------------------------------------------------
 * DB
 * ------------------------------------------------- */
$db = $GLOBALS['db'] ?? ($mysqli ?? null);

/* -------------------------------------------------
 * Load settings lib + gate
 * ------------------------------------------------- */
if ($db instanceof mysqli) {
  require_once __DIR__ . '/../admin/includes/settings_lib.php';
}

require_once __DIR__ . '/gate.php';
gate_check($db); // MUST RUN BEFORE OUTPUT

/* -------------------------------------------------
 * BASE URL (stable)
 * ------------------------------------------------- */
if (!defined('BASE_URL')) {
  $envBase = rtrim((string)(getenv('BASE_URL') ?: ''), '/');

  if ($envBase !== '') {
    define('BASE_URL', $envBase === '/' ? '' : $envBase);
  } else {
    $projectRoot = str_replace('\\', '/', dirname(__DIR__));
    $docRoot = str_replace('\\', '/', (string)($_SERVER['DOCUMENT_ROOT'] ?? ''));

    $base = '';
    if ($docRoot !== '' && strpos($projectRoot, rtrim($docRoot, '/')) === 0) {
      $base = substr($projectRoot, strlen(rtrim($docRoot, '/')));
    }

    $base = '/' . trim((string)$base, '/');
    define('BASE_URL', $base === '/' ? '' : $base);
  }
}

// Make BASE_URL available as variable too
$BASE = BASE_URL;

/* -------------------------------------------------
 * Navigation defaults
 * ------------------------------------------------- */
$visible_links_default = [
  'home'         => true,
  'about'        => true,
  'services'     => true,
  'projects'     => true,
  'shop'         => false,
  'blog'         => true,
  'team'         => true,
  'testimonials' => true,
  'contact'      => true,
];

$nav_items = [
  'home'         => ['title'=>'Home',         'href'=> $BASE . '/index.php'],
  'about'        => ['title'=>'About',        'href'=> $BASE . '/about.php'],
  'services'     => ['title'=>'Services',     'href'=> $BASE . '/services.php'],
  'projects'     => ['title'=>'Projects',     'href'=> $BASE . '/projects.php'],
  'blog'         => ['title'=>'Blog',         'href'=> $BASE . '/blog.php'],
  'team'         => ['title'=>'Team',         'href'=> $BASE . '/team.php'],
  'testimonials' => ['title'=>'Testimonials', 'href'=> $BASE . '/testimonials.php'],
  'contact'      => ['title'=>'Contact',      'href'=> $BASE . '/contact.php'],
];
$nav_order_default = array_keys($visible_links_default);

/* -------------------------------------------------
 * Load nav settings from DB
 * ------------------------------------------------- */
$visible_links = $visible_links_default;
$nav_order     = $nav_order_default;

if ($db instanceof mysqli && function_exists('setting_get_json')) {
  $v = setting_get_json($db, 'visible_links', $visible_links_default);
  if (is_array($v)) $visible_links = $v;

  $o = setting_get_json($db, 'nav_order', $nav_order_default);
  if (is_array($o)) $nav_order = $o;
}

/* -------------------------------------------------
 * Brand assets
 * ------------------------------------------------- */
$brand_logo    = '';
$brand_favicon = '';
$recaptcha_site_key = '';

if ($db instanceof mysqli && function_exists('setting_get')) {
  $brand_logo    = setting_get($db, 'brand_logo', '');
  $brand_favicon = setting_get($db, 'brand_favicon', '');
  $recaptcha_site_key = trim((string)setting_get($db, 'recaptcha_site_key', ''));
}

$base_path = rtrim((string)BASE_URL, '/');
$logo_url    = $brand_logo ? (strpos($brand_logo, 'http') === 0 ? $brand_logo : $base_path . '/' . ltrim($brand_logo, '/')) : '';
$favicon_url = $brand_favicon ? (strpos($brand_favicon, 'http') === 0 ? $brand_favicon : $base_path . '/' . ltrim($brand_favicon, '/')) : '';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
$origin = $scheme . '://' . $host;
$requestUri = (string)($_SERVER['REQUEST_URI'] ?? '/');

$canonical_url_value = trim((string)($canonical_url ?? ($origin . $requestUri)));
if ($canonical_url_value !== '' && !preg_match('#^https?://#i', $canonical_url_value)) {
  $canonical_url_value = $origin . '/' . ltrim($canonical_url_value, '/');
}

$meta_description_value = trim((string)($meta_description ?? 'Professional digital services from Alma Tech Consults.'));
$meta_image_value = trim((string)($meta_image ?? $logo_url));
if ($meta_image_value !== '' && !preg_match('#^https?://#i', $meta_image_value)) {
  $meta_image_value = $origin . '/' . ltrim($meta_image_value, '/');
}

/* -------------------------------------------------
 * Topbar social links
 * ------------------------------------------------- */
$topbar_social = [
  'facebook' => '',
  'instagram' => '',
  'linkedin' => '',
  'tiktok' => '',
];

$topbar_location = 'Mbarara, Uganda';
$topbar_map_embed = '';

if ($db instanceof mysqli && function_exists('setting_get_json')) {
  $savedSocial = setting_get_json($db, 'social_links', []);
  if (is_array($savedSocial)) {
    foreach ($topbar_social as $key => $_) {
      $topbar_social[$key] = trim((string)($savedSocial[$key] ?? ''));
    }
  }
}

$shop_href = rtrim((string)$BASE, '/') . '/shop/';

if ($db instanceof mysqli && function_exists('setting_get')) {
  $topbar_location = trim((string)setting_get($db, 'contact_location', $topbar_location));
  $topbar_map_embed = trim((string)setting_get($db, 'contact_map_embed', ''));
}

$topbar_map_src = '';
if ($topbar_map_embed !== '') {
  if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $topbar_map_embed, $matches)) {
    $topbar_map_src = trim((string)($matches[1] ?? ''));
  } elseif (preg_match('#^https?://#i', $topbar_map_embed)) {
    $topbar_map_src = $topbar_map_embed;
  }
}

if (!function_exists('normalize_social_url')) {
  function normalize_social_url(string $url): string {
    $url = trim($url);
    if ($url === '') {
      return '';
    }
    return preg_match('/^https?:\/\//i', $url) ? $url : ('https://' . $url);
  }
}

/* -------------------------------------------------
 * Base nav items
 * ------------------------------------------------- */
$nav_items = [
  'home'         => ['title'=>'Home',         'href'=> $BASE . '/index.php'],
  'about'        => ['title'=>'About',        'href'=> $BASE . '/about.php'],
  'services'     => ['title'=>'Services',     'href'=> $BASE . '/services.php'],
  'projects'     => ['title'=>'Projects',     'href'=> $BASE . '/projects.php'],
  'blog'         => ['title'=>'Blog',         'href'=> $BASE . '/blog.php'],
  'team'         => ['title'=>'Team',         'href'=> $BASE . '/team.php'],
  'testimonials' => ['title'=>'Testimonials', 'href'=> $BASE . '/testimonials.php'],
  'contact'      => ['title'=>'Contact',      'href'=> $BASE . '/contact.php'],
];

/* -------------------------------------------------
 * Custom pages (page.php?slug=…)
 * ------------------------------------------------- */
if ($db instanceof mysqli) {
  $stmt = $db->prepare("SELECT title, slug FROM pages WHERE status='published'");
  if ($stmt) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($p = $res->fetch_assoc()) {
      $slug = (string)$p['slug'];
      if ($slug && !isset($nav_items[$slug])) {
        $nav_items[$slug] = [
          'title' => $p['title'],
          'href'  => $BASE . '/page.php?slug=' . urlencode($slug),
        ];
        if (!in_array($slug, $nav_order, true)) {
          $nav_order[] = $slug;
        }
      }
    }
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= h($page_title) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= h($meta_description_value) ?>">
  <link rel="canonical" href="<?= h($canonical_url_value) ?>">

  <meta property="og:type" content="website">
  <meta property="og:title" content="<?= h($page_title) ?>">
  <meta property="og:description" content="<?= h($meta_description_value) ?>">
  <meta property="og:url" content="<?= h($canonical_url_value) ?>">
  <?php if ($meta_image_value !== ''): ?>
    <meta property="og:image" content="<?= h($meta_image_value) ?>">
    <meta property="og:image:secure_url" content="<?= h($meta_image_value) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="<?= h($meta_image_value) ?>">
  <?php else: ?>
    <meta name="twitter:card" content="summary">
  <?php endif; ?>
  <meta name="twitter:title" content="<?= h($page_title) ?>">
  <meta name="twitter:description" content="<?= h($meta_description_value) ?>">

  <?php if ($favicon_url): ?>
    <link rel="icon" href="<?= h($favicon_url) ?>">
  <?php endif; ?>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= rtrim((string)BASE_URL, '/') ?>/assets/css/main.css">
  <?php if (($active ?? '') === 'shop'): ?>
    <link rel="stylesheet" href="<?= rtrim((string)BASE_URL, '/') ?>/assets/css/shop.css">
  <?php endif; ?>
  <?php if ($recaptcha_site_key !== ''): ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= h($recaptcha_site_key) ?>"></script>
    <script>
      window.RECAPTCHA_SITE_KEY = <?= json_encode($recaptcha_site_key) ?>;
    </script>
  <?php endif; ?>
</head>
<body>

<!-- Top bar -->
<div class="topbar py-2 d-none d-lg-block">
  <div class="container d-flex justify-content-between small">
    <div>
      <button type="button" class="topbar-location-link btn btn-link p-0 border-0 text-decoration-none" data-bs-toggle="modal" data-bs-target="#shopLocationModal" aria-label="Open shop location map">
        <i class="bi bi-geo-alt"></i> <?= h($topbar_location) ?>
      </button>
    </div>
    <div class="d-flex gap-2">
      <?php if (!empty($topbar_social['facebook'])): ?>
        <a href="<?= h(normalize_social_url($topbar_social['facebook'])) ?>" class="topbar-link" target="_blank" rel="noopener" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
      <?php endif; ?>
      <?php if (!empty($topbar_social['instagram'])): ?>
        <a href="<?= h(normalize_social_url($topbar_social['instagram'])) ?>" class="topbar-link" target="_blank" rel="noopener" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
      <?php endif; ?>
      <?php if (!empty($topbar_social['linkedin'])): ?>
        <a href="<?= h(normalize_social_url($topbar_social['linkedin'])) ?>" class="topbar-link" target="_blank" rel="noopener" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
      <?php endif; ?>
      <?php if (!empty($topbar_social['tiktok'])): ?>
        <a href="<?= h(normalize_social_url($topbar_social['tiktok'])) ?>" class="topbar-link" target="_blank" rel="noopener" aria-label="TikTok"><i class="bi bi-tiktok"></i></a>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="modal fade glass-modal" id="shopLocationModal" tabindex="-1" aria-labelledby="shopLocationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="shopLocationModalLabel"><i class="bi bi-geo-alt-fill me-1 text-orange"></i> Shop Location</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0">
        <?php if ($topbar_map_src !== ''): ?>
          <iframe
            src="<?= h($topbar_map_src) ?>"
            width="100%"
            height="420"
            style="border:0; display:block;"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"></iframe>
        <?php else: ?>
          <div class="p-4">
            <div class="fw-semibold mb-2">Map not configured yet</div>
            <div class="text-muted small mb-0">Please set <code>Contact Map Embed</code> in Admin Settings to show the exact shop location.</div>
          </div>
        <?php endif; ?>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg bg-white sticky-top nav-shadow">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= h($BASE) ?>/index.php">
      <?php if ($logo_url): ?>
        <img src="<?= h($logo_url) ?>" style="height:32px">
      <?php else: ?>
        <span class="brand-dot"></span>
      <?php endif; ?>
      Alma Tech Consults
    </a>

    <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav ms-auto gap-lg-2">
        <?php foreach ($nav_order as $slug): ?>
          <?php
            if (empty($visible_links[$slug])) continue;
            if (!isset($nav_items[$slug])) continue;
            $item = $nav_items[$slug];
            $activeClass = (($active ?? '') === $slug) ? 'active' : '';
          ?>
          <li class="nav-item">
            <a class="nav-link <?= $activeClass ?>" href="<?= h($item['href']) ?>">
              <?= h($item['title']) ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>

      <div class="ms-lg-3 d-flex gap-2">
        <?php if (!empty($visible_links['shop'] ?? false)): ?>
          <a href="<?= h($shop_href) ?>" class="btn btn-outline-orange" title="Shop" aria-label="Shop">
            <i class="bi bi-cart3 me-1"></i> Shop
          </a>
        <?php endif; ?>
        <a href="<?= h($BASE) ?>/contact.php" class="btn btn-whatsapp-icon rounded-circle">
          <i class="bi bi-whatsapp"></i>
        </a>
      </div>
    </div>
  </div>
</nav>
