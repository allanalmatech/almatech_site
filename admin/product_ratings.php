<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';

require_admin_login();
$admin = current_admin();

try {
    $ratingAvgColumn = db()->query("SHOW COLUMNS FROM products LIKE 'rating_avg'")->fetch();
    if (!$ratingAvgColumn) {
        db()->exec('ALTER TABLE products ADD COLUMN rating_avg DECIMAL(3,2) NOT NULL DEFAULT 0.00 AFTER video_url');
    }

    $ratingCountColumn = db()->query("SHOW COLUMNS FROM products LIKE 'rating_count'")->fetch();
    if (!$ratingCountColumn) {
        db()->exec('ALTER TABLE products ADD COLUMN rating_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER rating_avg');
    }

    $viewsCountColumn = db()->query("SHOW COLUMNS FROM products LIKE 'views_count'")->fetch();
    if (!$viewsCountColumn) {
        db()->exec('ALTER TABLE products ADD COLUMN views_count INT UNSIGNED NOT NULL DEFAULT 0 AFTER rating_count');
    }

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
} catch (Throwable $e) {
    set_flash('danger', 'Unable to initialize ratings report tables/columns: ' . $e->getMessage());
}

function mask_ip_address(string $ip): string
{
    $ip = trim($ip);
    if ($ip === '') {
        return '-';
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0] . '.' . $parts[1] . '.x.x';
        }
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $parts = explode(':', $ip);
        return implode(':', array_slice($parts, 0, 3)) . ':x:x:x:x:x';
    }

    return 'x.x.x.x';
}

$q = trim((string)($_GET['q'] ?? ''));
$ratingFilter = (int)($_GET['rating'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = '(p.name LIKE :q OR p.slug LIKE :q OR pr.ip_address LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

if ($ratingFilter >= 1 && $ratingFilter <= 5) {
    $where[] = 'pr.rating = :rating';
    $params[':rating'] = $ratingFilter;
}

$whereSql = implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM product_ratings pr INNER JOIN products p ON p.id = pr.product_id WHERE {$whereSql}");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$pagination = pagination_meta($totalItems, $page, $perPage);

$listSql = "SELECT pr.*, p.name AS product_name, p.slug AS product_slug
            FROM product_ratings pr
            INNER JOIN products p ON p.id = pr.product_id
            WHERE {$whereSql}
            ORDER BY pr.created_at DESC
            LIMIT :limit OFFSET :offset";
$listStmt = db()->prepare($listSql);
foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value);
}
$listStmt->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$ratings = $listStmt->fetchAll();

$summary = db()->query('SELECT COUNT(*) AS total_votes, AVG(rating) AS avg_vote FROM product_ratings')->fetch() ?: ['total_votes' => 0, 'avg_vote' => 0];
$topViewed = db()->query('SELECT name, views_count FROM products ORDER BY views_count DESC, id DESC LIMIT 1')->fetch();
$topRated = db()->query('SELECT name, rating_avg, rating_count FROM products WHERE rating_count > 0 ORDER BY rating_avg DESC, rating_count DESC, id DESC LIMIT 1')->fetch();

$productStats = db()->query('SELECT id, name, slug, views_count, rating_avg, rating_count FROM products ORDER BY views_count DESC, rating_count DESC, id DESC LIMIT 15')->fetchAll();

$page_title = 'Product Ratings & Views | Admin';
$page_heading = 'Product Ratings & Views';
$page_subtitle = 'Monitor product engagement and ratings';
$active_admin = 'shop_ratings';

require_once __DIR__ . '/includes/admin_header.php';
require_once __DIR__ . '/includes/admin_sidebar.php';

$flash = get_flash();
?>

<div class="admin-main">
    <?php require_once __DIR__ . '/includes/admin_topbar.php'; ?>

    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flash['type']) ?> mb-3"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <div class="admin-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Ratings & Views Report</h4>
            <a class="btn btn-outline-orange" href="<?= e(admin_url('products.php')) ?>"><i class="bi bi-arrow-left me-1"></i>Back to Products</a>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card table-card p-3 h-100">
                    <div class="small text-muted mb-1">Total Votes</div>
                    <div class="h4 mb-0"><?= (int)($summary['total_votes'] ?? 0) ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card table-card p-3 h-100">
                    <div class="small text-muted mb-1">Average Vote</div>
                    <div class="h4 mb-0"><?= number_format((float)($summary['avg_vote'] ?? 0), 2) ?>/5</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card table-card p-3 h-100">
                    <div class="small text-muted mb-1">Top Viewed Product</div>
                    <div class="fw-semibold"><?= e((string)($topViewed['name'] ?? 'N/A')) ?></div>
                    <div class="small text-muted"><?= (int)($topViewed['views_count'] ?? 0) ?> views</div>
                </div>
            </div>
        </div>

        <div class="card table-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Per-Product Stats</h5>
                <?php if ($topRated): ?>
                    <div class="small text-muted">Top rated: <strong><?= e((string)$topRated['name']) ?></strong> (<?= number_format((float)$topRated['rating_avg'], 2) ?>/5, <?= (int)$topRated['rating_count'] ?> votes)</div>
                <?php endif; ?>
            </div>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Views</th>
                            <th>Average Rating</th>
                            <th>Votes</th>
                            <th class="text-end">Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$productStats): ?>
                            <tr><td colspan="5" class="text-center text-muted py-3">No product stats yet.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($productStats as $stat): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= e((string)$stat['name']) ?></div>
                                    <div class="small text-muted">/shop/product/<?= e((string)$stat['slug']) ?></div>
                                </td>
                                <td><?= (int)($stat['views_count'] ?? 0) ?></td>
                                <td><?= number_format((float)($stat['rating_avg'] ?? 0), 2) ?>/5</td>
                                <td><?= (int)($stat['rating_count'] ?? 0) ?></td>
                                <td class="text-end"><a class="btn btn-sm btn-outline-orange" href="<?= e(app_url('shop/product/' . rawurlencode((string)$stat['slug']))) ?>" target="_blank" rel="noopener">View Page</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card table-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Votes Log (One per Device + IP)</h5>
            </div>

            <form class="row g-2 mb-3" method="get">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search product, slug, or IP...">
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="rating">
                        <option value="0">All Ratings</option>
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <option value="<?= $i ?>" <?= $ratingFilter === $i ? 'selected' : '' ?>><?= $i ?> Stars</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button class="btn btn-outline-orange" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a class="btn btn-outline-secondary" href="<?= e(admin_url('product_ratings.php')) ?>">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Rating</th>
                            <th>IP (masked)</th>
                            <th>Device</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$ratings): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No rating votes found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($ratings as $row): ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?= e((string)$row['product_name']) ?></div>
                                    <div class="small text-muted">/shop/product/<?= e((string)$row['product_slug']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-warning text-dark"><?= (int)$row['rating'] ?> / 5</span>
                                </td>
                                <td><?= e(mask_ip_address((string)$row['ip_address'])) ?></td>
                                <td><code><?= e(substr((string)$row['device_token'], 0, 10)) ?>...</code></td>
                                <td><?= e((string)$row['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($pagination['total_pages'] > 1): ?>
                <nav>
                    <ul class="pagination justify-content-end mb-0">
                        <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                            <li class="page-item <?= $p === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="<?= e(pagination_url(['q' => $q, 'rating' => $ratingFilter], $p)) ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
