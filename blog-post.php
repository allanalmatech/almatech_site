<?php
declare(strict_types=1);

$page_title = "Blog Post | Alma Tech Consults";
$active = "blog";

// ✅ Load core first (so header has DB + constants)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/gate.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/header.php';

// Helper function
if (!function_exists('h')) {
  function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$slug = trim((string)($_GET['slug'] ?? ''));

// Database connection
$db = $GLOBALS['db'] ?? ($mysqli ?? null);
if (!($db instanceof mysqli)) {
  echo '<div class="container py-5"><h2>Database Error</h2><p>Unable to connect to database.</p></div>';
  require_once __DIR__ . '/includes/footer.php';
  exit;
}

// Fetch blog post from database
$post = null;
$stmt = $db->prepare("SELECT id, title, slug, category, excerpt, content, published_at, cover_image, is_featured FROM posts WHERE slug = ? AND status = 'published' LIMIT 1");
if ($stmt) {
  $stmt->bind_param('s', $slug);
  $stmt->execute();
  
  if (method_exists($stmt, 'get_result')) {
    $res = $stmt->get_result();
    $post = $res ? $res->fetch_assoc() : null;
  } else {
    // Fallback for older MySQLi versions
    $stmt->bind_result($id, $title, $slug_db, $category, $excerpt, $content, $published_at, $cover_image, $is_featured);
    if ($stmt->fetch()) {
      $post = [
        'id' => $id,
        'title' => $title,
        'slug' => $slug_db,
        'category' => $category,
        'excerpt' => $excerpt,
        'content' => $content,
        'published_at' => $published_at,
        'cover_image' => $cover_image,
        'is_featured' => $is_featured
      ];
    }
  }
  $stmt->close();
}

// If no post found, show 404
if (!$post) {
  ?>
  <section class="section">
    <div class="container">
      <div class="text-center py-5">
        <h1 class="display-4 fw-bold mb-3">Post Not Found</h1>
        <p class="lead text-muted mb-4">The blog post you're looking for doesn't exist or has been removed.</p>
        <a href="blog.php" class="btn btn-orange">Back to Blog</a>
      </div>
    </div>
  </section>
  <?php
  require_once __DIR__ . '/includes/footer.php';
  exit;
}

// Update page title
$page_title = h($post['title']) . " | Alma Tech Consults";

// Helper functions
function make_excerpt(string $excerpt, string $content, int $len = 160): string {
  $txt = trim($excerpt);
  if ($txt !== '') return $txt;

  $txt = trim(strip_tags($content));
  if ($txt === '') return '';
  if (mb_strlen($txt) <= $len) return $txt;

  $chunk = mb_substr($txt, 0, $len);
  $pos = mb_strrpos($chunk, ' ');
  if ($pos !== false) $chunk = mb_substr($chunk, 0, $pos);
  return $chunk . '…';
}

function reading_mins(string $content): int {
  $words = str_word_count(strip_tags($content));
  return max(1, (int)ceil($words / 200));
}

// Parse content blocks (assuming content is stored as JSON or structured format)
$content_blocks = [];
if (!empty($post['content'])) {
  // Try to decode as JSON first
  $decoded = json_decode($post['content'], true);
  if (is_array($decoded)) {
    $content_blocks = $decoded;
  } else {
    // If not JSON, treat as plain text and split into paragraphs
    $paragraphs = preg_split('/\n\s*\n/', $post['content']);
    foreach ($paragraphs as $i => $para) {
      if (trim($para)) {
        $content_blocks[] = [
          'type' => 'paragraph',
          'content' => trim($para)
        ];
      }
    }
  }
}

$excerpt = make_excerpt($post['excerpt'] ?? '', $post['content'] ?? '');
$read_mins = reading_mins($post['content'] ?? '');
$cover_url = !empty($post['cover_image']) ? h(BASE_URL . '/' . ltrim($post['cover_image'], '/')) : '';
$category = h($post['category'] ?? 'Uncategorized');
$date = date('F j, Y', strtotime($post['published_at'] ?? 'now'));
?>

<!-- ================= BLOG POST HERO ================= -->
<section class="section section-soft">
  <div class="container">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
        <li class="breadcrumb-item"><a href="blog.php">Blog</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= h($post['title']) ?></li>
      </ol>
    </nav>

    <div class="row justify-content-center">
      <div class="col-lg-8">
        <article class="blog-post">
          <?php if ($cover_url): ?>
            <div class="blog-cover mb-4">
              <img src="<?= $cover_url ?>" alt="<?= h($post['title']) ?>" class="img-fluid rounded">
            </div>
          <?php endif; ?>

          <div class="blog-meta mb-3">
            <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
              <span><i class="bi bi-calendar3 me-1"></i><?= $date ?></span>
              <span><i class="bi bi-folder me-1"></i><?= $category ?></span>
              <span><i class="bi bi-clock me-1"></i><?= $read_mins ?> min read</span>
            </div>
          </div>

          <h1 class="display-5 fw-bold mb-4"><?= h($post['title']) ?></h1>

          <?php if (!empty($post['excerpt'])): ?>
            <div class="lead text-muted mb-4"><?= h($post['excerpt']) ?></div>
          <?php endif; ?>

          <div class="blog-content">
            <?php if (!empty($content_blocks)): ?>
              <?php foreach ($content_blocks as $block): ?>
                <?php if (isset($block['type']) && $block['type'] === 'heading'): ?>
                  <h2 class="h4 fw-bold mt-4 mb-3"><?= h($block['content'] ?? '') ?></h2>
                <?php elseif (isset($block['type']) && $block['type'] === 'paragraph'): ?>
                  <p class="mb-3"><?= nl2br(h($block['content'] ?? '')) ?></p>
                <?php elseif (isset($block['type']) && $block['type'] === 'list'): ?>
                  <ul class="mb-3">
                    <?php if (is_array($block['content'] ?? [])): ?>
                      <?php foreach ($block['content'] as $item): ?>
                        <li><?= h($item) ?></li>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </ul>
                <?php elseif (isset($block['h']) && isset($block['p'])): ?>
                  <!-- Legacy format from post.php -->
                  <h2 class="h4 fw-bold mt-4 mb-3"><?= h($block['h']) ?></h2>
                  <p class="mb-3"><?= nl2br(h($block['p'])) ?></p>
                <?php else: ?>
                  <!-- Default paragraph -->
                  <p class="mb-3"><?= nl2br(h(is_string($block) ? $block : ($block['content'] ?? ''))) ?></p>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php else: ?>
              <!-- Fallback: display content as plain text -->
              <div class="blog-content-text"><?= nl2br(h($post['content'])) ?></div>
            <?php endif; ?>
          </div>

          </article>

        <div class="blog-navigation mt-5">
          <div class="row">
            <div class="col-6">
              <a href="blog.php" class="btn btn-outline-orange w-100">
                <i class="bi bi-arrow-left me-2"></i> Back to Blog
              </a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="blog-sidebar">
          <!-- Related Posts could go here -->
          <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
              <h5 class="card-title h6 fw-bold mb-3">About Alma Tech</h5>
              <p class="card-text small text-muted">
                We help businesses build, fix, and scale with technology. 
                From web development to digital marketing, we've got you covered.
              </p>
              <a href="about.php" class="btn btn-sm btn-orange">Learn More</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
