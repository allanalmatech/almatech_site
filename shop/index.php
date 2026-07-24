<?php
declare(strict_types=1);

$page_title = 'Shop | Alma Tech Consults';
$active = 'shop';

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/gate.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$showOutOfStock = setting('show_out_of_stock', '1') === '1';

$categories = db()->query('SELECT id, name, slug FROM categories WHERE status = 1 ORDER BY name ASC')->fetchAll();

$featuredSql = 'SELECT p.*, c.name AS category_name
                FROM products p
                INNER JOIN categories c ON c.id = p.category_id
                WHERE p.status = 1 AND c.status = 1 AND p.featured = 1';
if (!$showOutOfStock) {
    $featuredSql .= ' AND (p.stock_qty IS NULL OR p.stock_qty > 0)';
}
$featuredSql .= ' ORDER BY p.created_at DESC LIMIT 12';

$products = db()->query($featuredSql)->fetchAll();
$BASE = rtrim((string)BASE_URL, '/');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'www.almatechconsults.com';
$ORIGIN = $scheme . '://' . $host;
$shopHeroCover = shop_hero_cover_url(setting('shop_hero_cover', ''));

require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero<?= $shopHeroCover ? ' shop-hero-cover hero-scroll-blur' : '' ?>"<?= $shopHeroCover ? ' style="--shop-hero-cover:url(\'' . h($shopHeroCover) . '\');"' : '' ?>>
  <div class="container py-5">
    <div class="row g-4 align-items-center">
      <div class="col-lg-8">
        <div class="badge-soft mb-3"><i class="bi bi-bag-fill me-1"></i> Shop Catalog</div>
        <h1 class="display-6 fw-bold mb-3">Discover products and order on WhatsApp.</h1>
        <p class="lead text-muted mb-0">Browse featured items by category and send your order instantly.</p>
      </div>
      <div class="col-lg-4 shop-hero-glass-col d-none d-lg-block">
        <div class="hero-card p-4">
          <div class="fw-semibold mb-2 text-orange">Need help choosing?</div>
          <p class="text-muted small mb-3">Message our team and we will guide you to the right product.</p>
          <a href="<?= h($BASE) ?>/shop/search" class="btn btn-orange w-100 btn-lg shop-search-glass-btn"><i class="bi bi-search me-1"></i>Search Products</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section shop-page-section">
  <div class="container shop-page-container">
    <div class="d-flex flex-wrap gap-2 mb-4">
      <a class="btn btn-filter" href="<?= h($BASE) ?>/shop/">All</a>
      <?php foreach ($categories as $category): ?>
        <a class="btn btn-filter" href="<?= h($BASE) ?>/shop/category/<?= rawurlencode((string)$category['slug']) ?>"><?= h((string)$category['name']) ?></a>
      <?php endforeach; ?>
      <a class="btn btn-outline-orange" href="<?= h($BASE) ?>/shop/search">Advanced Search</a>
      <a class="btn btn-orange" href="<?= h($BASE) ?>/shop/request"><i class="bi bi-plus-circle me-1"></i>Request a Gadget</a>
    </div>

    <div class="row g-3 g-lg-4">
      <?php if (!$products): ?>
        <div class="col-12">
          <div class="service-card p-4 text-center">
            <h2 class="h5 mb-2">No featured products yet</h2>
            <p class="text-muted mb-0">Featured products will appear here after they are enabled in admin.</p>
          </div>
        </div>
      <?php endif; ?>

      <?php foreach ($products as $product): ?>
        <div class="col-md-6 col-lg-4">
          <div class="project-card h-100 d-flex flex-column">
            <div class="project-cover">
              <img src="<?= h(product_image_url((string)$product['main_image'])) ?>" alt="<?= h((string)$product['name']) ?>" loading="lazy" decoding="async">
              <span class="project-badge"><?= h((string)$product['category_name']) ?></span>
            </div>

            <div class="project-body d-flex flex-column flex-grow-1 p-3">
              <h3 class="h5 fw-bold mb-2"><?= h((string)$product['name']) ?></h3>
              <p class="text-muted mb-3"><?= h((string)$product['short_description']) ?></p>

              <div class="mb-3">
                <div class="fw-semibold"><?= h(format_currency((float)$product['price'])) ?></div>
                <?php if (!empty($product['discount_price'])): ?>
                  <div class="small text-success">Sale: <?= h(format_currency((float)$product['discount_price'])) ?></div>
                <?php endif; ?>
              </div>

              <div class="d-flex gap-2 mt-auto">
                <a class="btn btn-outline-orange flex-fill" href="<?= h($BASE) ?>/shop/product/<?= rawurlencode((string)$product['slug']) ?>"><i class="bi bi-eye me-1"></i>View</a>
                <button
                  type="button"
                  class="btn btn-whatsapp flex-fill"
                  data-wa-order
                  data-wa-number="<?= h(whatsapp_number()) ?>"
                  data-product-name="<?= h((string)$product['name']) ?>"
                  data-price-label="<?= h(format_currency((float)$product['price'])) ?>"
                  data-discount-label="<?= !empty($product['discount_price']) ? h(format_currency((float)$product['discount_price'])) : '' ?>"
                  data-product-link="<?= h($ORIGIN . $BASE . '/shop/product/' . rawurlencode((string)($product['slug'] ?? ''))) ?>"
                ><i class="bi bi-whatsapp me-1"></i>Order</button>
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
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<script src="<?= h($BASE) ?>/assets/js/shop.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
