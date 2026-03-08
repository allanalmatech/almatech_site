<?php
declare(strict_types=1);

$page_title = 'Search Products | Alma Tech Consults';
$active = 'shop';

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/gate.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$q = trim((string)($_GET['q'] ?? ''));
$hasSearch = $q !== '';
$sort = $_GET['sort'] ?? 'newest';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(1, (int)setting('products_per_page', '15'));
$showOutOfStock = setting('show_out_of_stock', '1') === '1';

$sortSql = 'p.created_at DESC';
if ($sort === 'price_asc') {
    $sortSql = 'COALESCE(p.discount_price, p.price) ASC';
} elseif ($sort === 'price_desc') {
    $sortSql = 'COALESCE(p.discount_price, p.price) DESC';
}

$where = ['p.status = 1', 'c.status = 1'];
$params = [];

if (!$showOutOfStock) {
    $where[] = '(p.stock_qty IS NULL OR p.stock_qty > 0)';
}

if ($hasSearch) {
    $where[] = '(p.name LIKE :q_name OR p.short_description LIKE :q_short OR p.description LIKE :q_desc)';
    $qLike = '%' . $q . '%';
    $params[':q_name'] = $qLike;
    $params[':q_short'] = $qLike;
    $params[':q_desc'] = $qLike;
}

$whereSql = implode(' AND ', $where);

$products = [];
$pagination = ['current_page' => 1, 'total_pages' => 0];

if ($hasSearch) {
    $countStmt = db()->prepare("SELECT COUNT(*) FROM products p INNER JOIN categories c ON c.id = p.category_id WHERE {$whereSql}");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pagination = pagination_meta($total, $page, $perPage);

    $stmt = db()->prepare("SELECT p.*, c.name AS category_name FROM products p INNER JOIN categories c ON c.id = p.category_id WHERE {$whereSql} ORDER BY {$sortSql} LIMIT :limit OFFSET :offset");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
    $stmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();
}

$BASE = rtrim((string)BASE_URL, '/');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'www.almatechconsults.com';
$ORIGIN = $scheme . '://' . $host;
$searchHeroCover = shop_hero_cover_url(setting('search_hero_cover', ''));
require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero<?= $searchHeroCover ? ' shop-hero-cover hero-scroll-blur' : '' ?>"<?= $searchHeroCover ? ' style="--shop-hero-cover:url(\'' . h($searchHeroCover) . '\');"' : '' ?>>
  <div class="container py-5">
    <div class="badge-soft mb-3"><i class="bi bi-search me-1"></i> Search Catalog</div>
    <h1 class="display-6 fw-bold mb-2">Find products quickly</h1>
    <p class="lead text-muted mb-0">Search by product name, details, or keywords.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <form class="row g-2 mb-4" id="shopSearchForm" autocomplete="off">
      <div class="col-md-6 position-relative">
        <input type="text" name="q" id="shopSearchInput" class="form-control" placeholder="Search products" value="<?= h($q) ?>">
        <div id="shopSuggestBox" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index:20; top:100%; left:0;"></div>
      </div>
      <div class="col-md-3">
        <select class="form-select" name="sort">
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
          <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price low to high</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price high to low</option>
        </select>
      </div>
      <div class="col-md-2 d-grid"><button class="btn btn-orange" type="submit">Search</button></div>
      <div class="col-md-1 d-grid"><a class="btn btn-outline-orange" href="<?= h($BASE) ?>/shop/request" title="Request a Gadget"><i class="bi bi-plus-circle"></i></a></div>
    </form>

    <div class="row g-3 g-lg-4">
      <?php if (!$hasSearch): ?>
        <div class="col-12">
          <div class="service-card p-4 text-center">
            <h2 class="h5 mb-2">Start by searching</h2>
            <p class="text-muted mb-0">Type keywords above to search for products and see matching results.</p>
          </div>
        </div>
      <?php elseif (!$products): ?>
        <div class="col-12">
          <div class="service-card p-4 text-center">
            <h2 class="h5 mb-2">No products matched your search</h2>
            <p class="text-muted mb-0">Try different keywords or browse categories from the shop home.</p>
          </div>
        </div>
      <?php endif; ?>

      <?php foreach ($products as $product): ?>
        <div class="col-md-6 col-lg-4">
          <div class="project-card h-100 d-flex flex-column">
            <div class="project-cover">
              <img src="<?= h(product_image_url((string)$product['main_image'])) ?>" alt="<?= h((string)$product['name']) ?>">
              <span class="project-badge"><?= h((string)$product['category_name']) ?></span>
            </div>
            <div class="project-body p-3 d-flex flex-column flex-grow-1">
              <h3 class="h5 fw-bold mb-2"><?= h((string)$product['name']) ?></h3>
              <p class="text-muted mb-3"><?= h((string)$product['short_description']) ?></p>
              <div class="mb-3">
                <div class="fw-semibold"><?= h(format_currency((float)$product['price'])) ?></div>
                <?php if (!empty($product['discount_price'])): ?><div class="small text-success">Sale: <?= h(format_currency((float)$product['discount_price'])) ?></div><?php endif; ?>
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

    <?php if ($hasSearch && $pagination['total_pages'] > 1): ?>
      <nav class="mt-4">
        <ul class="pagination mb-0">
          <li class="page-item <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= h('?' . http_build_query(['q' => $q, 'sort' => $sort, 'page' => $pagination['current_page'] - 1])) ?>">Previous</a></li>
          <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
            <li class="page-item <?= $p === $pagination['current_page'] ? 'active' : '' ?>"><a class="page-link" href="<?= h('?' . http_build_query(['q' => $q, 'sort' => $sort, 'page' => $p])) ?>"><?= $p ?></a></li>
          <?php endfor; ?>
          <li class="page-item <?= $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : '' ?>"><a class="page-link" href="<?= h('?' . http_build_query(['q' => $q, 'sort' => $sort, 'page' => $pagination['current_page'] + 1])) ?>">Next</a></li>
        </ul>
      </nav>
    <?php endif; ?>
  </div>
</section>

<script src="<?= h($BASE) ?>/assets/js/shop.js"></script>
<script>
(function () {
  var input = document.getElementById('shopSearchInput');
  var box = document.getElementById('shopSuggestBox');
  var form = document.getElementById('shopSearchForm');
  if (!input || !box || !form) return;

  var timer = null;
  var lastTerm = '';

  function hideBox() {
    box.classList.add('d-none');
    box.innerHTML = '';
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function renderItems(items) {
    if (!items.length) {
      hideBox();
      return;
    }

    var html = '';
    for (var i = 0; i < items.length; i++) {
      var item = items[i];
      html += '<a class="list-group-item list-group-item-action" href="' + item.url + '">'
        + '<div class="fw-semibold">' + escapeHtml(item.name) + '</div>'
        + '<div class="small text-muted">' + escapeHtml(item.category_name) + ' • ' + escapeHtml(item.price_label) + '</div>'
      + '</a>';
    }
    box.innerHTML = html;
    box.classList.remove('d-none');
  }

  function fetchSuggestions(term) {
    fetch('<?= h($BASE) ?>/shop/suggest.php?q=' + encodeURIComponent(term), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data || !data.items) {
          hideBox();
          return;
        }
        renderItems(data.items);
      })
      .catch(function () { hideBox(); });
  }

  input.addEventListener('input', function () {
    var term = input.value.trim();
    if (term.length < 2) {
      hideBox();
      return;
    }
    if (term === lastTerm) return;
    lastTerm = term;

    if (timer) clearTimeout(timer);
    timer = setTimeout(function () {
      fetchSuggestions(term);
    }, 220);
  });

  document.addEventListener('click', function (event) {
    if (!form.contains(event.target)) {
      hideBox();
    }
  });

  input.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') hideBox();
  });
})();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
