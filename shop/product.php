<?php
declare(strict_types=1);

$page_title = 'Product | Alma Tech Consults';
$active = 'shop';

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/gate.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$slug = trim((string)($_GET['slug'] ?? ''));
if ($slug === '') {
    http_response_code(404);
    exit('Product not found');
}

$hasViewsCountColumn = false;
$hasRatingColumns = false;
$hasProductRatingsTable = false;
$hasProductViewsTable = false;
try {
    $viewsColumn = db()->query("SHOW COLUMNS FROM products LIKE 'views_count'")->fetch();
    $ratingAvgColumn = db()->query("SHOW COLUMNS FROM products LIKE 'rating_avg'")->fetch();
    $ratingCountColumn = db()->query("SHOW COLUMNS FROM products LIKE 'rating_count'")->fetch();
    db()->exec(
        'CREATE TABLE IF NOT EXISTS product_ratings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id INT UNSIGNED NOT NULL,
            ip_address VARCHAR(64) NOT NULL,
            device_token VARCHAR(80) NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_product_ip_device (product_id, ip_address, device_token),
            KEY idx_product_ratings_product (product_id),
            CONSTRAINT fk_product_ratings_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    db()->exec(
        'CREATE TABLE IF NOT EXISTS product_views (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id INT UNSIGNED NOT NULL,
            ip_address VARCHAR(64) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_product_ip (product_id, ip_address),
            KEY idx_product_views_product (product_id),
            CONSTRAINT fk_product_views_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $ratingsTable = db()->query("SHOW TABLES LIKE 'product_ratings'")->fetch();
    $viewsTable = db()->query("SHOW TABLES LIKE 'product_views'")->fetch();
    $hasViewsCountColumn = (bool)$viewsColumn;
    $hasRatingColumns = (bool)$ratingAvgColumn && (bool)$ratingCountColumn;
    $hasProductRatingsTable = (bool)$ratingsTable;
    $hasProductViewsTable = (bool)$viewsTable;
} catch (Throwable $e) {
    $hasViewsCountColumn = false;
    $hasRatingColumns = false;
    $hasProductRatingsTable = false;
    $hasProductViewsTable = false;
}

$clientIp = trim((string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $clientIp = trim((string)$_SERVER['HTTP_CF_CONNECTING_IP']);
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $parts = explode(',', (string)$_SERVER['HTTP_X_FORWARDED_FOR']);
    $clientIp = trim((string)($parts[0] ?? $clientIp));
}

$deviceCookieName = 'alma_vote_device';
$deviceToken = trim((string)($_COOKIE[$deviceCookieName] ?? ''));
if ($deviceToken === '' || !preg_match('/^[a-f0-9]{32,80}$/', $deviceToken)) {
    try {
        $deviceToken = bin2hex(random_bytes(16));
    } catch (Throwable $e) {
        $deviceToken = sha1(uniqid('vote', true));
    }

    setcookie($deviceCookieName, $deviceToken, [
        'expires' => time() + (86400 * 365),
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

$showOutOfStock = setting('show_out_of_stock', '1') === '1';
$sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        WHERE p.slug = :slug AND p.status = 1 AND c.status = 1';
if (!$showOutOfStock) {
    $sql .= ' AND (p.stock_qty IS NULL OR p.stock_qty > 0)';
}
$sql .= ' LIMIT 1';

$stmt = db()->prepare($sql);
$stmt->execute([':slug' => $slug]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    exit('Product not found');
}

if ($hasViewsCountColumn && $hasProductViewsTable) {
    try {
        $pdo = db();
        $pdo->beginTransaction();

        $viewInsertStmt = $pdo->prepare('INSERT IGNORE INTO product_views (product_id, ip_address, created_at) VALUES (:product_id, :ip_address, NOW())');
        $viewInsertStmt->execute([
            ':product_id' => (int)$product['id'],
            ':ip_address' => $clientIp,
        ]);

        if ($viewInsertStmt->rowCount() > 0) {
            $viewUpdateStmt = $pdo->prepare('UPDATE products SET views_count = COALESCE(views_count, 0) + 1 WHERE id = :id LIMIT 1');
            $viewUpdateStmt->execute([':id' => (int)$product['id']]);
            $product['views_count'] = (int)($product['views_count'] ?? 0) + 1;
        } else {
            $product['views_count'] = (int)($product['views_count'] ?? 0);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        $product['views_count'] = (int)($product['views_count'] ?? 0);
    }
}

$currentUserRating = null;
if ($hasProductRatingsTable) {
    try {
        $myRatingStmt = db()->prepare('SELECT rating FROM product_ratings WHERE product_id = :product_id AND ip_address = :ip_address AND device_token = :device_token LIMIT 1');
        $myRatingStmt->execute([
            ':product_id' => (int)$product['id'],
            ':ip_address' => $clientIp,
            ':device_token' => $deviceToken,
        ]);
        $myRatingValue = $myRatingStmt->fetchColumn();
        if ($myRatingValue !== false) {
            $currentUserRating = max(1, min(5, (int)$myRatingValue));
        }
    } catch (Throwable $e) {
        $currentUserRating = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'rate') {
    $ratingValue = (int)($_POST['rating'] ?? 0);
    $rateStatus = 'invalid';

    if ($hasRatingColumns && $hasProductRatingsTable && $ratingValue >= 1 && $ratingValue <= 5) {
        try {
            $pdo = db();
            $pdo->beginTransaction();

            $existsStmt = $pdo->prepare('SELECT id FROM product_ratings WHERE product_id = :product_id AND ip_address = :ip_address AND device_token = :device_token LIMIT 1');
            $existsStmt->execute([
                ':product_id' => (int)$product['id'],
                ':ip_address' => $clientIp,
                ':device_token' => $deviceToken,
            ]);

            if ($existsStmt->fetch()) {
                $rateStatus = 'duplicate';
            } else {
                $insertVoteStmt = $pdo->prepare('INSERT INTO product_ratings (product_id, ip_address, device_token, rating, created_at) VALUES (:product_id, :ip_address, :device_token, :rating, NOW())');
                $insertVoteStmt->execute([
                    ':product_id' => (int)$product['id'],
                    ':ip_address' => $clientIp,
                    ':device_token' => $deviceToken,
                    ':rating' => $ratingValue,
                ]);

                $rateStmt = $pdo->prepare(
                    'UPDATE products
                     SET rating_avg = ((COALESCE(rating_avg, 0) * COALESCE(rating_count, 0)) + :rating) / (COALESCE(rating_count, 0) + 1),
                         rating_count = COALESCE(rating_count, 0) + 1,
                         updated_at = NOW()
                     WHERE id = :id
                     LIMIT 1'
                );
                $rateStmt->execute([
                    ':rating' => $ratingValue,
                    ':id' => (int)$product['id'],
                ]);
                $rateStatus = 'success';
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            $rateStatus = 'error';
        }
    }

    $redirectTarget = strtok((string)($_SERVER['REQUEST_URI'] ?? ''), '?');
    if (!$redirectTarget) {
        $redirectTarget = '/shop/product/' . rawurlencode((string)$product['slug']);
    }
    header('Location: ' . $redirectTarget . '?rate_status=' . urlencode($rateStatus));
    exit;
}

$galleryStmt = db()->prepare('SELECT image_path FROM product_images WHERE product_id = :id ORDER BY id DESC');
$galleryStmt->execute([':id' => (int)$product['id']]);
$gallery = $galleryStmt->fetchAll();

$videoRaw = trim((string)($product['video_url'] ?? ''));
$videoCandidates = [];
if ($videoRaw !== '') {
    $videoUrls = array_values(array_filter(array_map('trim', explode(',', $videoRaw)), static function ($url) {
        return $url !== '';
    }));

    foreach ($videoUrls as $videoUrl) {
        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{6,})~i', $videoUrl, $m)) {
            $videoCandidates[] = ['kind' => 'embed', 'src' => 'https://www.youtube.com/embed/' . $m[1]];
        } elseif (preg_match('~vimeo\.com/(\d+)~i', $videoUrl, $m)) {
            $videoCandidates[] = ['kind' => 'embed', 'src' => 'https://player.vimeo.com/video/' . $m[1]];
        } elseif (preg_match('#^https?://#i', $videoUrl)) {
            $videoCandidates[] = ['kind' => 'file', 'src' => $videoUrl];
        }
    }
}

$galleryImages = [];
$seenImages = [];

$mainImage = trim((string)($product['main_image'] ?? ''));
if ($mainImage !== '' && !isset($seenImages[$mainImage])) {
    $galleryImages[] = $mainImage;
    $seenImages[$mainImage] = true;
}

foreach ($gallery as $imgRow) {
    $path = trim((string)($imgRow['image_path'] ?? ''));
    if ($path === '' || isset($seenImages[$path])) {
        continue;
    }

    $galleryImages[] = $path;
    $seenImages[$path] = true;
}

$primaryImage = (string)($galleryImages[0] ?? $product['main_image'] ?? '');
$primaryImageUrl = product_image_url($primaryImage);

$galleryItems = [];
foreach ($galleryImages as $index => $imagePath) {
    $galleryItems[] = [
        'type' => 'image',
        'src' => product_image_url((string)$imagePath),
        'alt' => (string)$product['name'] . ' image ' . ($index + 1),
    ];
}

if ($videoCandidates) {
    $primaryVideo = $videoCandidates[0];
    $fileFallbackSources = [];
    if (($primaryVideo['kind'] ?? '') === 'file') {
        foreach ($videoCandidates as $candidate) {
            if (($candidate['kind'] ?? '') !== 'file') {
                continue;
            }
            $src = (string)($candidate['src'] ?? '');
            if ($src === '' || in_array($src, $fileFallbackSources, true)) {
                continue;
            }
            $fileFallbackSources[] = $src;
        }
    }

    $galleryItems[] = [
        'type' => 'video',
        'src' => (string)($primaryVideo['src'] ?? ''),
        'video_kind' => (string)($primaryVideo['kind'] ?? 'file'),
        'sources' => $fileFallbackSources,
        'poster' => $primaryImageUrl,
    ];
}

$stockQty = $product['stock_qty'] !== null ? (int)$product['stock_qty'] : null;
$viewsCount = (int)($product['views_count'] ?? 0);
$ratingCount = (int)($product['rating_count'] ?? 0);
$ratingAverage = (float)($product['rating_avg'] ?? 0);
$roundedRating = (int)round($ratingAverage);
$BASE = rtrim((string)BASE_URL, '/');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'www.almatechconsults.com';
$ORIGIN = $scheme . '://' . $host;
$canonical_url = $ORIGIN . $BASE . '/shop/product/' . rawurlencode((string)($product['slug'] ?? ''));
$meta_description = trim((string)($product['short_description'] ?? ''));
if ($meta_description === '') {
    $meta_description = trim(strip_tags((string)($product['description'] ?? '')));
}
if (strlen($meta_description) > 180) {
    $meta_description = substr($meta_description, 0, 177) . '...';
}
$meta_image = $primaryImageUrl;
if (!preg_match('#^https?://#i', $meta_image)) {
    $meta_image = $ORIGIN . '/' . ltrim($meta_image, '/');
}
$page_title = (string)$product['name'] . ' | Shop | Alma Tech Consults';

require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero">
  <div class="container py-5">
    <div class="mb-3 small text-muted">
      <a class="text-decoration-none" href="<?= h($BASE) ?>/index.php">Home</a>
      <span class="mx-2">/</span>
      <a class="text-decoration-none" href="<?= h($BASE) ?>/shop/">Shop</a>
      <span class="mx-2">/</span>
      <a class="text-decoration-none" href="<?= h($BASE) ?>/shop/category/<?= rawurlencode((string)$product['category_slug']) ?>"><?= h((string)$product['category_name']) ?></a>
      <span class="mx-2">/</span>
      <span class="text-orange fw-semibold"><?= h((string)$product['name']) ?></span>
    </div>
    <h1 class="display-6 fw-bold mb-2"><?= h((string)$product['name']) ?></h1>
    <p class="lead text-muted mb-0"><?= h((string)$product['short_description']) ?></p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="product-gallery" data-product-gallery>
          <div class="project-hero-cover product-gallery-main mb-3" data-gallery-main>
            <img src="<?= h($primaryImageUrl) ?>" alt="<?= h((string)$product['name']) ?>" data-gallery-main-image>

            <div class="product-gallery-main-video d-none" data-gallery-main-video-wrap>
              <video class="d-none" data-gallery-main-video controls playsinline preload="metadata"></video>
              <iframe class="d-none" data-gallery-main-embed title="<?= h((string)$product['name']) ?> video" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
            </div>
          </div>

          <?php if (count($galleryItems) > 1): ?>
            <div class="product-gallery-thumbs mb-4" data-gallery-thumbs aria-label="Product gallery thumbnails">
              <?php foreach ($galleryItems as $index => $item): ?>
                <button
                  type="button"
                  class="product-gallery-thumb<?= $index === 0 ? ' is-active' : '' ?>"
                  data-gallery-thumb
                  data-media-type="<?= h((string)$item['type']) ?>"
                  <?php if ($item['type'] === 'image'): ?>
                    data-image-src="<?= h((string)$item['src']) ?>"
                    data-image-alt="<?= h((string)$item['alt']) ?>"
                    aria-label="View image <?= $index + 1 ?>"
                  <?php else: ?>
                    data-video-kind="<?= h((string)$item['video_kind']) ?>"
                    data-video-src="<?= h((string)$item['src']) ?>"
                    data-video-sources="<?= h(json_encode($item['sources'] ?? [], JSON_UNESCAPED_SLASHES)) ?>"
                    aria-label="Play product video"
                  <?php endif; ?>
                  <?= $index === 0 ? 'aria-current="true"' : '' ?>
                >
                  <img src="<?= h((string)($item['type'] === 'video' ? $item['poster'] : $item['src'])) ?>" alt="<?= h((string)$product['name']) ?> thumbnail <?= $index + 1 ?>">
                  <?php if ($item['type'] === 'video'): ?>
                    <span class="product-gallery-thumb-icon" aria-hidden="true"><i class="bi bi-play-circle-fill"></i></span>
                  <?php endif; ?>
                </button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="service-card p-4">
          <h2 class="h5 fw-bold mb-3">Product Details</h2>
          <p class="text-muted mb-0"><?= nl2br(h((string)$product['description'])) ?></p>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="hero-card p-4 mb-4">
          <div class="h4 fw-bold mb-1"><?= h(format_currency((float)$product['price'])) ?></div>
          <?php if (!empty($product['discount_price'])): ?>
            <div class="text-success fw-semibold mb-2">Sale: <?= h(format_currency((float)$product['discount_price'])) ?></div>
          <?php endif; ?>
          <div class="small text-muted mb-3"><?= h(stock_label($stockQty)) ?></div>

          <div class="product-meta-strip mb-3">
            <span class="product-rating-display" title="Average rating">
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <i class="bi <?= $s <= $roundedRating ? 'bi-star-fill' : 'bi-star' ?>"></i>
              <?php endfor; ?>
              <span class="ms-1"><?= h(number_format($ratingAverage, 1)) ?></span>
              <span class="text-muted">(<?= $ratingCount ?>)</span>
            </span>
            <span class="product-views-display" title="Views"><i class="bi bi-eye me-1"></i><?= $viewsCount ?></span>
          </div>

          <div class="product-rating-box mb-3">
            <div class="small fw-semibold mb-2">Rate this product</div>
            <?php $rateStatus = trim((string)($_GET['rate_status'] ?? '')); ?>
            <?php if ($rateStatus === 'success'): ?>
              <div class="small text-success mb-2">Thanks for your rating.</div>
            <?php elseif ($rateStatus === 'duplicate'): ?>
              <div class="small text-warning mb-2">You already rated this product on this device/IP.</div>
            <?php elseif ($rateStatus === 'error'): ?>
              <div class="small text-danger mb-2">Unable to save your rating right now.</div>
            <?php endif; ?>
            <?php if ($currentUserRating !== null): ?>
              <div class="small text-muted mb-2">You gave this a <?= $currentUserRating ?> star rating.</div>
              <div class="product-rating-display" aria-label="Your rating">
                <?php for ($r = 1; $r <= 5; $r++): ?>
                  <i class="bi <?= $r <= $currentUserRating ? 'bi-star-fill' : 'bi-star' ?>"></i>
                <?php endfor; ?>
              </div>
            <?php else: ?>
              <form method="post" class="d-flex align-items-center gap-2 flex-wrap">
                <input type="hidden" name="action" value="rate">
                <div class="product-rate-options" role="radiogroup" aria-label="Rate product">
                  <?php for ($r = 5; $r >= 1; $r--): ?>
                    <label class="product-rate-option" title="<?= $r ?> star<?= $r > 1 ? 's' : '' ?>">
                      <input type="radio" name="rating" value="<?= $r ?>" required>
                      <i class="bi bi-star-fill"></i>
                    </label>
                  <?php endfor; ?>
                </div>
                <button type="submit" class="btn btn-outline-orange btn-sm">Submit</button>
              </form>
            <?php endif; ?>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-4">
              <label class="form-label">Qty</label>
              <input id="orderQty" type="number" class="form-control" min="1" value="1">
            </div>
            <div class="col-8">
              <label class="form-label">Note / Variant</label>
              <input id="orderNote" type="text" class="form-control" placeholder="Color, size, spec">
            </div>
          </div>

          <div class="d-flex gap-2">
            <button
              type="button"
              class="btn btn-whatsapp flex-fill"
              data-wa-order
              data-wa-number="<?= h(whatsapp_number()) ?>"
              data-product-name="<?= h((string)$product['name']) ?>"
              data-price-label="<?= h(format_currency((float)$product['price'])) ?>"
              data-discount-label="<?= !empty($product['discount_price']) ? h(format_currency((float)$product['discount_price'])) : '' ?>"
              data-product-link="<?= h($ORIGIN . $BASE . '/shop/product/' . rawurlencode((string)($product['slug'] ?? ''))) ?>"
              data-qty-input="#orderQty"
              data-note-input="#orderNote"
            >Order on WhatsApp</button>

            <button
              type="button"
              class="btn btn-share-icon"
              data-product-share
              data-share-title="<?= h((string)$product['name']) ?>"
              data-share-text="<?= h('Check out this product from Alma Tech Consults: ' . (string)$product['name']) ?>"
              data-share-url="<?= h($ORIGIN . $BASE . '/shop/product/' . rawurlencode((string)($product['slug'] ?? ''))) ?>"
              data-share-image="<?= h(product_image_url((string)$product['main_image'])) ?>"
              aria-label="Share <?= h((string)$product['name']) ?>"
              title="Share product"
            ><i class="bi bi-share-fill"></i></button>
          </div>

          <a class="btn btn-outline-orange w-100 mt-2" href="<?= h($BASE) ?>/shop/category/<?= rawurlencode((string)$product['category_slug']) ?>">More in <?= h((string)$product['category_name']) ?></a>
          <a class="btn btn-orange w-100 mt-2" href="<?= h($BASE) ?>/shop/request"><i class="bi bi-plus-circle me-1"></i>Request Different Gadget</a>
        </div>
      </div>
    </div>
  </div>
</section>

<script src="<?= h($BASE) ?>/assets/js/shop.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
