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

$galleryStmt = db()->prepare('SELECT image_path FROM product_images WHERE product_id = :id ORDER BY id DESC');
$galleryStmt->execute([':id' => (int)$product['id']]);
$gallery = $galleryStmt->fetchAll();

$stockQty = $product['stock_qty'] !== null ? (int)$product['stock_qty'] : null;
$BASE = rtrim((string)BASE_URL, '/');
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
        <div class="project-hero-cover mb-3">
          <img src="<?= h(product_image_url((string)$product['main_image'])) ?>" alt="<?= h((string)$product['name']) ?>">
        </div>

        <?php if ($gallery): ?>
          <div class="d-flex flex-wrap gap-2 mb-4">
            <?php foreach ($gallery as $img): ?>
              <img src="<?= h(product_image_url((string)$img['image_path'])) ?>" alt="Gallery image" style="width:100px;height:100px;object-fit:cover;border-radius:12px;border:1px solid #e2e8f0;">
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

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

          <button
            type="button"
            class="btn btn-orange w-100"
            data-wa-order
            data-wa-number="<?= h(whatsapp_number()) ?>"
            data-product-name="<?= h((string)$product['name']) ?>"
            data-price-label="<?= h(format_currency((float)$product['price'])) ?>"
            data-discount-label="<?= !empty($product['discount_price']) ? h(format_currency((float)$product['discount_price'])) : '' ?>"
            data-product-link="<?= h($BASE) ?>/shop/product/<?= rawurlencode((string)$product['slug']) ?>"
            data-qty-input="#orderQty"
            data-note-input="#orderNote"
          >Order on WhatsApp</button>

          <a class="btn btn-outline-orange w-100 mt-2" href="<?= h($BASE) ?>/shop/category/<?= rawurlencode((string)$product['category_slug']) ?>">More in <?= h((string)$product['category_name']) ?></a>
        </div>
      </div>
    </div>
  </div>
</section>

<script src="<?= h($BASE) ?>/assets/js/shop.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
