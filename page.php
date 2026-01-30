<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/gate.php';

$db = $GLOBALS['db'] ?? ($mysqli ?? null);

// --------------------------
// Resolve slug
// --------------------------
$slug = trim((string)($_GET['slug'] ?? ''));

if ($slug === '') {
  $path = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
  $path = trim($path, '/');

  $parts = $path !== '' ? explode('/', $path) : [];
  $last  = $parts ? end($parts) : '';

  $slug = $last ? preg_replace('~\.php$~i', '', (string)$last) : '';
}

if ($slug === '') {
  http_response_code(404);
  require_once __DIR__ . '/includes/404.php';
  exit;
}

// --------------------------
// Load page from DB
// --------------------------
$page = null;

if ($db instanceof mysqli) {
  $sql = "SELECT id, title, slug, content, layout_json, meta_title, meta_description, status
          FROM pages
          WHERE slug = ? AND status = 'published'
          LIMIT 1";

  $stmt = $db->prepare($sql);
  if ($stmt) {
    $stmt->bind_param("s", $slug);
    $stmt->execute();

    if (method_exists($stmt, 'get_result')) {
      $res  = $stmt->get_result();
      $page = $res ? $res->fetch_assoc() : null;
    } else {
      $stmt->bind_result($id, $title, $slugDb, $contentDb, $layoutJsonDb, $metaTitle, $metaDesc, $statusDb);
      if ($stmt->fetch()) {
        $page = [
          'id' => $id,
          'title' => $title,
          'slug' => $slugDb,
          'content' => $contentDb,
          'layout_json' => $layoutJsonDb,
          'meta_title' => $metaTitle,
          'meta_description' => $metaDesc,
          'status' => $statusDb,
        ];
      }
    }

    $stmt->close();
  }
}

// --------------------------
// If not in DB, fall back to core .php pages
// --------------------------
if (!$page) {
  $core_pages = ['home', 'about', 'services', 'projects', 'contact', 'team', 'blog', 'testimonials'];

  if (in_array($slug, $core_pages, true)) {
    $file_path = __DIR__ . '/' . $slug . '.php';
    if (is_file($file_path)) {
      include $file_path;
      exit;
    }
  }

  http_response_code(404);
  require_once __DIR__ . '/includes/404.php';
  exit;
}

// --------------------------
// Safe content/meta
// --------------------------
$title   = (string)($page['title'] ?? '');
$content = (string)($page['content'] ?? '');

$page_title = !empty($page['meta_title']) ? h((string)$page['meta_title']) : h($title);

$page_description = !empty($page['meta_description'])
  ? h((string)$page['meta_description'])
  : strip_tags(mb_substr($content, 0, 160));

// --------------------------
// Layout defaults + decode
// --------------------------
$layout = [
  'container' => 'container', // container | container-fluid
  'max_width' => 920,         // px
  'padding'   => 32,          // px
];

$layout_json_raw = (string)($page['layout_json'] ?? '');
if ($layout_json_raw !== '') {
  $decoded = json_decode($layout_json_raw, true);
  if (is_array($decoded)) {
    if (!empty($decoded['container']) && in_array($decoded['container'], ['container', 'container-fluid'], true)) {
      $layout['container'] = $decoded['container'];
    }
    if (isset($decoded['max_width'])) {
      $mw = (int)$decoded['max_width'];
      if ($mw >= 480 && $mw <= 2000) $layout['max_width'] = $mw;
    }
    if (isset($decoded['padding'])) {
      $pd = (int)$decoded['padding'];
      if ($pd >= 0 && $pd <= 120) $layout['padding'] = $pd;
    }
  }
}

$containerClass = $layout['container'];
$maxWidthPx     = (int)$layout['max_width'];
$paddingPx      = (int)$layout['padding'];

// --------------------------
// Render
// --------------------------
require_once __DIR__ . '/includes/header.php';
?>

<div class="section section-soft">
  <div class="<?= h($containerClass) ?>">
    <div class="row justify-content-center">
      <div class="col-12">
        <div class="mx-auto" style="max-width: <?= $maxWidthPx ?>px;">
          <div class="card border-0 shadow-sm">
            <div class="card-body" style="padding: <?= $paddingPx ?>px;">
              <h1 class="mb-4"><?= h($title) ?></h1>

              <div class="page-content">
                <?php if ($content !== ''): ?>
                  <?= $content /* trusted admin HTML */ ?>
                <?php else: ?>
                  <p class="text-muted mb-0">No content available for this page.</p>
                <?php endif; ?>
              </div>

            </div>
          </div>
        </div><!-- /.mx-auto -->
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
