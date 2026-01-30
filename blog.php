<?php
declare(strict_types=1);

$page_title = "Blog | Alma Tech Consults";
$active = "blog";

// ✅ Load core first (so header has DB + constants)
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/gate.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/header.php';

// Local helper (if you want a separate one)
if (!function_exists('h2')) {
  function h2($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

$q   = trim((string)($_GET['q'] ?? ''));
$cat = trim((string)($_GET['cat'] ?? ''));

$db = $GLOBALS['db'] ?? ($mysqli ?? null);
if (!($db instanceof mysqli)) {
  echo '<div class="container py-5"><h2>Database Error</h2><p>Unable to connect to database.</p></div>';
  require_once __DIR__ . '/includes/footer.php';
  exit;
}

$allowedCat = ($cat === 'All') ? '' : $cat;

// -------------------- Helper: excerpt + reading time --------------------
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

// -------------------- Categories --------------------
$categories = ['All'];
$catRes = $db->query("SELECT DISTINCT category FROM posts WHERE category IS NOT NULL AND category<>'' AND status='published' ORDER BY category ASC");
if ($catRes) {
  while ($r = $catRes->fetch_assoc()) {
    $categories[] = (string)$r['category'];
  }
  $catRes->free();
}

// -------------------- Featured (latest featured published) --------------------
$featured = null;

$stmt = $db->prepare("
  SELECT id, title, slug, category, excerpt, content, published_at, cover_image
  FROM posts
  WHERE status='published' AND is_featured=1
  ORDER BY published_at DESC
  LIMIT 1
");
if ($stmt) {
  $stmt->execute();

  if (method_exists($stmt, 'get_result')) {
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    if ($row) {
      $featured = [
        'id' => (int)$row['id'],
        'title' => (string)$row['title'],
        'slug' => (string)$row['slug'],
        'category' => (string)($row['category'] ?: 'Uncategorized'),
        'excerpt' => (string)($row['excerpt'] ?: ''),
        'content' => (string)($row['content'] ?: ''),
        'date' => (string)($row['published_at'] ?: ''),
        'cover' => (string)($row['cover_image'] ?: ''),
      ];
    }
  } else {
    // Fallback: bind_result
    $stmt->bind_result($fid, $ftitle, $fslug, $fcat, $fexcerpt, $fcontent, $fpub, $fcover);
    if ($stmt->fetch()) {
      $featured = [
        'id' => (int)$fid,
        'title' => (string)$ftitle,
        'slug' => (string)$fslug,
        'category' => (string)($fcat ?: 'Uncategorized'),
        'excerpt' => (string)($fexcerpt ?: ''),
        'content' => (string)($fcontent ?: ''),
        'date' => (string)($fpub ?: ''),
        'cover' => (string)($fcover ?: ''),
      ];
    }
  }

  $stmt->close();
}

// -------------------- Fetch posts (published + filters) --------------------
$where = "p.status='published'";
$types = "";
$params = [];

if ($q !== '') {
  $where .= " AND (p.title LIKE CONCAT('%', ?, '%') OR p.excerpt LIKE CONCAT('%', ?, '%') OR p.content LIKE CONCAT('%', ?, '%'))";
  $types .= "sss";
  $params[] = $q; $params[] = $q; $params[] = $q;
}

if ($allowedCat !== '') {
  $where .= " AND p.category = ?";
  $types .= "s";
  $params[] = $allowedCat;
}

$sql = "
  SELECT p.id, p.title, p.slug, p.category, p.excerpt, p.content, p.published_at, p.cover_image, p.is_featured
  FROM posts p
  WHERE $where
  ORDER BY p.is_featured DESC, p.published_at DESC
";

$stmt = $db->prepare($sql);
if (!$stmt) {
  echo '<div class="container py-5"><h2>Database Error</h2><p>Query preparation failed.</p></div>';
  require_once __DIR__ . '/includes/footer.php';
  exit;
}

if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();

$posts = [];
if (method_exists($stmt, 'get_result')) {
  $res = $stmt->get_result();
  if ($res) {
    while ($row = $res->fetch_assoc()) {
      $contentRow = (string)($row['content'] ?? '');
      $posts[] = [
        'id' => (int)$row['id'],
        'title' => (string)$row['title'],
        'slug' => (string)$row['slug'],
        'category' => (string)(($row['category'] ?? '') ?: 'Uncategorized'),
        'excerpt' => (string)(($row['excerpt'] ?? '') ?: ''),
        'content' => $contentRow,
        'date' => (string)(($row['published_at'] ?? '') ?: ''),
        'cover' => (string)(($row['cover_image'] ?? '') ?: ''),
        'read_mins' => reading_mins($contentRow),
        'featured' => ((int)($row['is_featured'] ?? 0) === 1),
      ];
    }
    $res->free();
  }
} else {
  // bind_result fallback
  $stmt->bind_result($id, $title, $slug, $category, $excerpt, $content, $published_at, $cover_image, $is_featured);
  while ($stmt->fetch()) {
    $posts[] = [
      'id' => (int)$id,
      'title' => (string)$title,
      'slug' => (string)$slug,
      'category' => (string)($category ?: 'Uncategorized'),
      'excerpt' => (string)($excerpt ?: ''),
      'content' => (string)($content ?: ''),
      'date' => (string)($published_at ?: ''),
      'cover' => (string)($cover_image ?: ''),
      'read_mins' => reading_mins((string)$content),
      'featured' => ((int)$is_featured === 1),
    ];
  }
}

$stmt->close();

// If there is no featured in DB, pick first featured from list
if (!$featured) {
  foreach ($posts as $p) {
    if (!empty($p['featured'])) { $featured = $p; break; }
  }
}

$filteredCount = count($posts);
?>

<!-- Page Hero -->
<section class="hero">
  <div class="container py-5">
    <div class="row g-4 align-items-center">
      <div class="col-lg-8">
        <div class="badge-soft mb-3">
          <i class="bi bi-journal-text me-1"></i> Blog & Updates
        </div>
        
        <!-- Search for small devices -->
        <div id="mobileSearch" class="mb-3 d-none">
          <form method="get" action="blog.php">
            <div class="input-group">
              <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search posts (e.g. website, marketing, laptop)…" class="form-control">
              <button type="submit" class="btn btn-orange">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </form>
        </div>
        
        <h1 class="display-6 fw-bold mb-3">Tips, guides, and updates from Alma Tech.</h1>
        <p class="lead text-muted mb-0">
          Learn about websites, marketing, ICT support, and technology for business growth in Uganda.
        </p>
      </div>

      <div class="col-lg-4">
        <div class="hero-card p-4">
          <div class="fw-semibold mb-2">Looking for help?</div>
          <p class="text-muted small mb-3">We can build your website + admin dashboard or support your ICT setup.</p>
          <a href="contact.php" class="btn btn-orange w-100 btn-lg">
            Talk to Us <i class="bi bi-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- Search -->
    <div class="row mt-4">
      <div class="col-lg-8">
        <form class="searchbar" method="get" action="blog.php">
          <input type="text" name="q" value="<?= h2($q) ?>" placeholder="Search posts (e.g. website, marketing, laptop)…">
          <input type="hidden" name="cat" value="<?= h2($cat) ?>">
          <button class="btn btn-orange" type="submit"><i class="bi bi-search"></i></button>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- Featured post -->
<?php if ($featured): ?>
<section class="section">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end gap-3 mb-3">
      <div>
        <h2 class="h3 fw-bold mb-1">Featured</h2>
        <p class="text-muted mb-0">A highlight article to start with.</p>
      </div>
      <a class="btn btn-outline-orange" href="blog-post.php?slug=<?= urlencode((string)$featured['slug']) ?>">Read</a>
    </div>

    <?php
      $cover = (string)($featured['cover'] ?? '');
      $coverPath = $cover !== '' ? ltrim($cover, '/') : '';
      $has_img = $coverPath !== '' && is_file(__DIR__ . '/' . $coverPath);
    ?>
    <div class="featured-card">
      <div class="row g-0 align-items-stretch">
        <div class="col-lg-5">
          <div class="featured-cover">
            <?php if ($has_img): ?>
              <img src="<?= h2(BASE_URL . ltrim($cover, '/')) ?>" alt="<?= h2((string)$featured['title']) ?>">
            <?php else: ?>
              <div class="project-cover-fallback">
                <i class="bi bi-image"></i>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-lg-7">
          <div class="p-4 p-md-5 h-100 d-flex flex-column">
            <div class="d-flex flex-wrap gap-2 mb-2">
              <span class="pill-tag"><?= h2((string)$featured['category']) ?></span>
              <span class="muted-pill"><i class="bi bi-clock me-1"></i><?= (int)reading_mins((string)($featured['content'] ?? '')) ?> min read</span>
              <span class="muted-pill"><i class="bi bi-calendar3 me-1"></i><?= h2((string)($featured['date'] ?: '')) ?></span>
            </div>

            <h3 class="h4 fw-bold mb-2"><?= h2((string)$featured['title']) ?></h3>
            <p class="text-muted mb-4"><?= h2(make_excerpt((string)($featured['excerpt'] ?? ''), (string)($featured['content'] ?? ''), 180)) ?></p>

            <div class="mt-auto">
              <a class="btn btn-orange btn-lg" href="blog-post.php?slug=<?= urlencode((string)$featured['slug']) ?>">
                Read Article <i class="bi bi-arrow-right ms-1"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>
<?php endif; ?>

<!-- Posts list -->
<section class="section section-soft">
  <div class="container">

    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-end gap-3 mb-4">
      <div>
        <h2 class="h3 fw-bold mb-1">All Posts</h2>
        <p class="text-muted mb-0">
          <?= (int)$filteredCount ?> post(s)
          <?php if ($cat && $cat !== 'All'): ?> in <span class="text-orange fw-semibold"><?= h2($cat) ?></span><?php endif; ?>
          <?php if ($q): ?> matching “<span class="text-orange fw-semibold"><?= h2($q) ?></span>”<?php endif; ?>
        </p>
      </div>

      <div class="d-flex flex-wrap gap-2">
        <?php foreach ($categories as $c): ?>
          <?php
            $isActive = ($cat === '' && $c === 'All') || ($cat === $c);
            $url = 'blog.php?cat=' . urlencode($c) . ($q ? '&q=' . urlencode($q) : '');
          ?>
          <a class="btn btn-filter <?= $isActive ? 'active' : '' ?>" href="<?= h2($url) ?>"><?= h2($c) ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="row g-3 g-lg-4">
      <?php $featuredSlug = $featured ? (string)$featured['slug'] : ''; ?>

      <?php if (!$posts): ?>
        <div class="col-12">
          <div class="service-card">
            <div class="fw-semibold mb-1">No posts found</div>
            <div class="text-muted">Try a different search or category.</div>
          </div>
        </div>
      <?php endif; ?>

      <?php foreach ($posts as $p): ?>
        <?php if ($featuredSlug !== '' && (string)$p['slug'] === $featuredSlug) continue; ?>

        <?php
          $cover = (string)$p['cover'];
          $coverPath = $cover !== '' ? ltrim($cover, '/') : '';
          $has_img = $coverPath !== '' && is_file(__DIR__ . '/' . $coverPath);
        ?>
        <div class="col-md-6 col-lg-4">
          <div class="blog-card h-100">
            <div class="blog-cover">
              <?php if ($has_img): ?>
                <img src="<?= h2(BASE_URL . ltrim($cover, '/')) ?>" alt="<?= h2((string)$p['title']) ?>">
              <?php else: ?>
                <div class="project-cover-fallback">
                  <i class="bi bi-image"></i>
                </div>
              <?php endif; ?>
              <span class="project-badge"><?= h2((string)$p['category']) ?></span>
            </div>

            <div class="p-3 p-md-4 d-flex flex-column h-100">
              <div class="d-flex flex-wrap gap-2 mb-2">
                <span class="muted-pill"><i class="bi bi-clock me-1"></i><?= (int)$p['read_mins'] ?> min</span>
                <span class="muted-pill"><i class="bi bi-calendar3 me-1"></i><?= h2((string)$p['date']) ?></span>
              </div>

              <h3 class="h5 fw-bold mb-2"><?= h2((string)$p['title']) ?></h3>
              <p class="text-muted mb-3"><?= h2(make_excerpt((string)$p['excerpt'], (string)$p['content'], 160)) ?></p>

              <div class="mt-auto">
                <a class="btn btn-orange w-100" href="blog-post.php?slug=<?= urlencode((string)$p['slug']) ?>">
                  Read More <i class="bi bi-arrow-right ms-1"></i>
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<!-- CTA -->
<section class="section">
  <div class="container">
    <div class="cta p-4 p-md-5">
      <div class="row align-items-center g-3">
        <div class="col-lg-8">
          <h2 class="h4 fw-bold mb-2">Want your business online professionally?</h2>
          <p class="mb-0 text-muted">We build fast websites and systems with admin dashboards to manage everything.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="contact.php" class="btn btn-orange btn-lg w-100 w-lg-auto">
            Get Started <i class="bi bi-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
// Handle search bar visibility for small devices
function handleSearchBarVisibility() {
  const mobileSearch = document.getElementById('mobileSearch');
  const mainSearch = document.querySelector('.searchbar');
  
  if (!mobileSearch || !mainSearch) return;
  
  if (window.innerWidth < 992) { // Small devices (lg breakpoint)
    // Show mobile search, hide main search
    mobileSearch.classList.remove('d-none');
    mainSearch.classList.add('d-none');
  } else {
    // Hide mobile search, show main search
    mobileSearch.classList.add('d-none');
    mainSearch.classList.remove('d-none');
  }
}

// Handle on load and resize
document.addEventListener('DOMContentLoaded', handleSearchBarVisibility);
window.addEventListener('resize', handleSearchBarVisibility);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
