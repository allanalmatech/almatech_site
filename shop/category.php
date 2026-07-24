<?php
declare(strict_types=1);

$page_title = 'Shop Category | Alma Tech Consults';
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
    exit('Category not found');
}

$categoryStmt = db()->prepare('SELECT * FROM categories WHERE slug = :slug AND status = 1 LIMIT 1');
$categoryStmt->execute([':slug' => $slug]);
$category = $categoryStmt->fetch();

if (!$category) {
    http_response_code(404);
    exit('Category not found');
}

$sort = $_GET['sort'] ?? 'newest';
$sortSql = 'p.created_at DESC';
if ($sort === 'price_asc') {
    $sortSql = 'COALESCE(p.discount_price, p.price) ASC';
} elseif ($sort === 'price_desc') {
    $sortSql = 'COALESCE(p.discount_price, p.price) DESC';
}

$showOutOfStock = setting('show_out_of_stock', '1') === '1';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(1, (int)setting('products_per_page', '15'));

$where = 'p.status = 1 AND p.category_id = :category_id';
if (!$showOutOfStock) {
    $where .= ' AND (p.stock_qty IS NULL OR p.stock_qty > 0)';
}

$countStmt = db()->prepare("SELECT COUNT(*) FROM products p WHERE {$where}");
$countStmt->execute([':category_id' => (int)$category['id']]);
$total = (int)$countStmt->fetchColumn();
$pagination = pagination_meta($total, $page, $perPage);

$productsStmt = db()->prepare("SELECT p.* FROM products p WHERE {$where} ORDER BY {$sortSql} LIMIT :limit OFFSET :offset");
$productsStmt->bindValue(':category_id', (int)$category['id'], PDO::PARAM_INT);
$productsStmt->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
$productsStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$productsStmt->execute();
$products = $productsStmt->fetchAll();

$BASE = rtrim((string)BASE_URL, '/');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'www.almatechconsults.com';
$ORIGIN = $scheme . '://' . $host;
$categoryHeroCover = '';
if (!empty($category['cover_image'])) {
    $categoryHeroCover = rtrim((string)$BASE, '/') . '/uploads/categories/' . rawurlencode((string)$category['cover_image']);
}
if ($categoryHeroCover === '') {
    $categoryHeroCover = shop_hero_cover_url(setting('shop_hero_cover', '')) ?? '';
}
$page_title = (string)$category['name'] . ' | Shop | Alma Tech Consults';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero<?= $categoryHeroCover !== '' ? ' shop-hero-cover hero-scroll-blur' : '' ?>"<?= $categoryHeroCover !== '' ? ' style="--shop-hero-cover:url(\'' . h($categoryHeroCover) . '\');"' : '' ?>>
  <div class="container py-5">
    <div class="badge-soft mb-3"><i class="bi bi-tags-fill me-1"></i> Category</div>
    <h1 class="display-6 fw-bold mb-2"><?= h((string)$category['name']) ?></h1>
    <p class="lead text-muted mb-0"><?= h((string)$category['description']) ?></p>
  </div>
</section>

<section class="section shop-page-section">
  <div class="container shop-page-container">
    <form class="row g-2 mb-4 align-items-end">
      <input type="hidden" name="slug" value="<?= h($slug) ?>">
      <div class="col-6 col-md-3">
        <select class="form-select" name="sort" onchange="this.form.submit()">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
          <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price low to high</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price high to low</option>
        </select>
      </div>
      <div class="col-auto">
        <a class="btn btn-outline-orange" href="<?= h($BASE) ?>/shop/">Back to Shop</a>
      </div>
      <div class="col-auto">
        <a class="btn btn-orange" href="<?= h($BASE) ?>/shop/request"><i class="bi bi-plus-circle me-1"></i>Request a Gadget</a>
      </div>
    </form>

    <div class="row g-3 g-lg-4">
      <?php if (!$products): ?>
        <div class="col-12">
          <div class="service-card p-4 text-center">
            <h2 class="h5 mb-2">No products available in this category</h2>
            <p class="text-muted mb-0">Try another category or check back later.</p>
          </div>
        </div>
      <?php endif; ?>

      <?php foreach ($products as $product): ?>
        <div class="col-md-6 col-lg-4">
          <div class="project-card h-100 d-flex flex-column">
            <div class="project-cover">
              <img src="<?= h(product_image_url((string)$product['main_image'])) ?>" alt="<?= h((string)$product['name']) ?>">
            </div>
            <div class="project-body p-3 d-flex flex-column flex-grow-1">
              <h3 class="h5 fw-bold mb-2"><?= h((string)$product['name']) ?></h3>
              <p class="text-muted mb-3"><?= h((string)$product['short_description']) ?></p>
              <div class="mb-3">
                <div class="fw-semibold"><?= h(format_currency((float)$product['price'])) ?></div>
                <?php if (!empty($product['discount_price'])): ?>
                  <div class="small text-success">Sale: <?= h(format_currency((float)$product['discount_price'])) ?></div>
                <?php endif; ?>
              </div>
              <div class="d-flex gap-2 mt-auto">
                <a href="<?= h($BASE) ?>/shop/product/<?= rawurlencode((string)$product['slug']) ?>" class="btn btn-outline-orange flex-fill"><i class="bi bi-eye me-1"></i>View</a>
                <button type="button" class="btn btn-whatsapp flex-fill" data-wa-order data-wa-number="<?= h((string)whatsapp_number()) ?>" data-product-name="<?= h((string)($product['name'] ?? 'Product')) ?>" data-price-label="<?= h(format_currency((float)($product['price'] ?? 0))) ?>" data-discount-label="<?= h(!empty($product['discount_price']) ? format_currency((float)$product['discount_price']) : '') ?>" data-product-link="<?= h($ORIGIN . $BASE . '/shop/product/' . rawurlencode((string)($product['slug'] ?? ''))) ?>"><i class="bi bi-whatsapp me-1"></i>Order</button>
                <button type="button" class="btn btn-share-icon" data-product-share data-share-title="<?= h((string)$product['name']) ?>" data-share-text="<?= h('Check out this product from Alma Tech Consults: ' . (string)$product['name']) ?>" data-share-url="<?= h($ORIGIN . $BASE . '/shop/product/' . rawurlencode((string)($product['slug'] ?? ''))) ?>" data-share-image="<?= h(product_image_url((string)$product['main_image'])) ?>" aria-label="Share <?= h((string)$product['name']) ?>" title="Share product"><i class="bi bi-share-fill"></i></button>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($pagination['total_pages'] > 1): ?>
      <nav class="mt-4">
        <ul class="pagination mb-0">
          <li class="page-item <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= h('?' . http_build_query(['sort' => $sort, 'slug' => $slug, 'page' => $pagination['current_page'] - 1])) ?>">Previous</a></li>
          <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
            <li class="page-item <?= $p === $pagination['current_page'] ? 'active' : '' ?>"><a class="page-link" href="<?= h('?' . http_build_query(['sort' => $sort, 'slug' => $slug, 'page' => $p])) ?>"><?= $p ?></a></li>
          <?php endfor; ?>
          <li class="page-item <?= $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : '' ?>"><a class="page-link" href="<?= h('?' . http_build_query(['sort' => $sort, 'slug' => $slug, 'page' => $pagination['current_page'] + 1])) ?>">Next</a></li>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</section>

<script src="<?= h($BASE) ?>/assets/js/shop.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
