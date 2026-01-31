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
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
  define('BASE_URL', $scheme . '://' . $host . $base);
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

if ($db instanceof mysqli && function_exists('setting_get')) {
  $brand_logo    = setting_get($db, 'brand_logo', '');
  $brand_favicon = setting_get($db, 'brand_favicon', '');
}

$logo_url    = $brand_logo ? (strpos($brand_logo, 'http') === 0 ? $brand_logo : BASE_URL . '/' . ltrim($brand_logo, '/')) : '';
$favicon_url = $brand_favicon ? (strpos($brand_favicon, 'http') === 0 ? $brand_favicon : BASE_URL . '/' . ltrim($brand_favicon, '/')) : '';

/* -------------------------------------------------
 * Base nav items
 * ------------------------------------------------- */
$nav_items = [
  'home'         => ['title'=>'Home',         'href'=>'index.php'],
  'about'        => ['title'=>'About',        'href'=>'about.php'],
  'services'     => ['title'=>'Services',     'href'=>'services.php'],
  'projects'     => ['title'=>'Projects',     'href'=>'projects.php'],
  'blog'         => ['title'=>'Blog',         'href'=>'blog.php'],
  'team'         => ['title'=>'Team',         'href'=>'team.php'],
  'testimonials' => ['title'=>'Testimonials', 'href'=>'testimonials.php'],
  'contact'      => ['title'=>'Contact',      'href'=>'contact.php'],
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
          'href'  => 'page.php?slug=' . urlencode($slug),
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

  <?php if ($favicon_url): ?>
    <link rel="icon" href="<?= h($favicon_url) ?>">
  <?php endif; ?>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
</head>
<body>

<!-- Top bar -->
<div class="topbar py-2 d-none d-lg-block">
  <div class="container d-flex justify-content-between small">
    <div>
      <i class="bi bi-geo-alt"></i> Mbarara, Uganda
    </div>
    <div class="d-flex gap-2">
      <a href="#" class="topbar-link"><i class="bi bi-facebook"></i></a>
      <a href="#" class="topbar-link"><i class="bi bi-instagram"></i></a>
      <a href="#" class="topbar-link"><i class="bi bi-linkedin"></i></a>
    </div>
  </div>
</div>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg bg-white sticky-top nav-shadow">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
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
        <a href="contact.php" class="btn btn-outline-orange">Get a Quote</a>
        <a href="contact.php" class="btn btn-whatsapp-icon rounded-circle">
          <i class="bi bi-whatsapp"></i>
        </a>
      </div>
    </div>
  </div>
</nav>