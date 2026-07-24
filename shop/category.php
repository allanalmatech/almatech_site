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
$coverStyle = '';
if (!empty($category['cover_image'])) {
    $coverStyle = "background-image: linear-gradient(rgba(11,18,32,.55), rgba(11,18,32,.45)), url('" . h($BASE . '/uploads/categories/' . rawurlencode((string)$category['cover_image'])) . "'); background-size: cover; background-position: center; color: #fff;";
}
$page_title = (string)$category['name'] . ' | Shop | Alma Tech Consults';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero" style="<?= $coverStyle ?>">
  <div class="container py-5">
    <div class="badge-soft mb-3"><i class="bi bi-tags-fill me-1"></i> Category</div>
    <h1 class="display-6 fw-bold mb-2"><?= h((string)$category['name']) ?></h1>
    <p class="lead <?= $coverStyle !== '' ? '' : 'text-muted' ?> mb-0"><?= h((string)$category['description']) ?></p>
  </div>
</section>

<section class="section">
  <div class="container">
    <form class="row g-2 mb-4">
      <input type="hidden" name="slug" value="<?= h($slug) ?>">
      <div class="col-md-3">
        <select class="form-select" name="sort" onchange="this.form.submit()">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
          <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price low to high</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price high to low</option>
        </select>
      </div>
      <div class="col-md-3">
        <a class="btn btn-outline-orange w-100" href="<?= h($BASE) ?>/shop/">Back to Shop</a>
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
              <div class="d-grid gap-2 mt-auto">
                <a href="<?= h($BASE) ?>/shop/product/<?= rawurlencode((string)$product['slug']) ?>" class="btn btn-outline-orange"><i class="bi bi-eye me-1"></i>View Product</a>
                <button type="button" class="btn btn-orange" data-wa-order data-wa-number="<?= h(whatsapp_number()) ?>" data-product-name="<?= h((string)$product['name']) ?>" data-price-label="<?= h(format_currency((float)$product['price'])) ?>" data-discount-label="<?= !empty($product['discount_price']) ? h(format_currency((float)$product['discount_price'])) : '' ?>" data-product-link="<?= h($BASE) ?>/shop/product/<?= rawurlencode((string)$product['slug']) ?>"><i class="bi bi-whatsapp me-1"></i>Place Order</button>
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
