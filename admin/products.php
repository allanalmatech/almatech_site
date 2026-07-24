<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config.php';

require_admin_login();
$admin = current_admin();

$categories = db()->query('SELECT id, name FROM categories WHERE status = 1 ORDER BY name ASC')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid_request();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $shortDescription = trim((string)($_POST['short_description'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $price = (float)($_POST['price'] ?? 0);
        $discountPriceRaw = trim((string)($_POST['discount_price'] ?? ''));
        $discountPrice = $discountPriceRaw !== '' ? (float)$discountPriceRaw : null;
        $stockRaw = trim((string)($_POST['stock_qty'] ?? ''));
        $stockQty = $stockRaw !== '' ? max(0, (int)$stockRaw) : null;
        $status = (($_POST['status'] ?? 'active') === 'active') ? 1 : 0;
        $featured = (($_POST['featured'] ?? '0') === '1') ? 1 : 0;

        if ($categoryId <= 0 || $name === '' || $price <= 0) {
            set_flash('danger', 'Category, name, and valid price are required.');
            redirect_to(admin_url('products.php'));
        }

        if ($discountPrice !== null && $discountPrice > $price) {
            set_flash('danger', 'Discount price must be less than or equal to price.');
            redirect_to(admin_url('products.php'));
        }

        $baseSlug = slugify($name);
        $slug = make_unique_slug('products', $baseSlug, $id > 0 ? $id : null);

        try {
            $pdo = db();
            $pdo->beginTransaction();

            $oldMainImage = null;
            if ($id > 0) {
                $oldStmt = $pdo->prepare('SELECT main_image FROM products WHERE id = :id LIMIT 1');
                $oldStmt->execute([':id' => $id]);
                $oldMainImage = $oldStmt->fetchColumn() ?: null;
            }

            $newMainImage = null;
            if (isset($_FILES['main_image'])) {
                $newMainImage = validate_image_upload($_FILES['main_image']);
            }

            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE products SET category_id = :category_id, name = :name, slug = :slug, short_description = :short_description, description = :description, price = :price, discount_price = :discount_price, stock_qty = :stock_qty, status = :status, featured = :featured, main_image = :main_image, updated_at = NOW() WHERE id = :id');
                $stmt->execute([
                    ':category_id' => $categoryId,
                    ':name' => $name,
                    ':slug' => $slug,
                    ':short_description' => $shortDescription,
                    ':description' => $description,
                    ':price' => $price,
                    ':discount_price' => $discountPrice,
                    ':stock_qty' => $stockQty,
                    ':status' => $status,
                    ':featured' => $featured,
                    ':main_image' => $newMainImage ?: $oldMainImage,
                    ':id' => $id,
                ]);
                $productId = $id;
            } else {
                if (!$newMainImage) {
                    throw new RuntimeException('Main image is required for new products.');
                }

                $stmt = $pdo->prepare('INSERT INTO products (category_id, name, slug, short_description, description, price, discount_price, stock_qty, status, featured, main_image, created_at, updated_at) VALUES (:category_id, :name, :slug, :short_description, :description, :price, :discount_price, :stock_qty, :status, :featured, :main_image, NOW(), NOW())');
                $stmt->execute([
                    ':category_id' => $categoryId,
                    ':name' => $name,
                    ':slug' => $slug,
                    ':short_description' => $shortDescription,
                    ':description' => $description,
                    ':price' => $price,
                    ':discount_price' => $discountPrice,
                    ':stock_qty' => $stockQty,
                    ':status' => $status,
                    ':featured' => $featured,
                    ':main_image' => $newMainImage,
                ]);
                $productId = (int)$pdo->lastInsertId();
            }

            if (!empty($_FILES['gallery_images']['name']) && is_array($_FILES['gallery_images']['name'])) {
                for ($i = 0; $i < count($_FILES['gallery_images']['name']); $i++) {
                    $single = [
                        'name' => $_FILES['gallery_images']['name'][$i],
                        'type' => $_FILES['gallery_images']['type'][$i],
                        'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                        'error' => $_FILES['gallery_images']['error'][$i],
                        'size' => $_FILES['gallery_images']['size'][$i],
                    ];

                    $filename = validate_image_upload($single);
                    if ($filename) {
                        $imgStmt = $pdo->prepare('INSERT INTO product_images (product_id, image_path, sort_order, created_at) VALUES (:product_id, :image_path, 0, NOW())');
                        $imgStmt->execute([
                            ':product_id' => $productId,
                            ':image_path' => $filename,
                        ]);
                    }
                }
            }

            $pdo->commit();

            if ($newMainImage && $oldMainImage && $newMainImage !== $oldMainImage) {
                remove_product_image_file($oldMainImage);
            }

            set_flash('success', $id > 0 ? 'Product updated successfully.' : 'Product created successfully.');
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            set_flash('danger', $e->getMessage());
        }

        redirect_to(admin_url('products.php'));
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        $stmt = db()->prepare('SELECT main_image FROM products WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $mainImage = $stmt->fetchColumn() ?: null;

        $galleryStmt = db()->prepare('SELECT image_path FROM product_images WHERE product_id = :id');
        $galleryStmt->execute([':id' => $id]);
        $gallery = $galleryStmt->fetchAll();

        db()->prepare('DELETE FROM product_images WHERE product_id = :id')->execute([':id' => $id]);
        db()->prepare('DELETE FROM products WHERE id = :id')->execute([':id' => $id]);

        remove_product_image_file($mainImage);
        foreach ($gallery as $img) {
            remove_product_image_file((string)$img['image_path']);
        }

        set_flash('success', 'Product deleted successfully.');
        redirect_to(admin_url('products.php'));
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editProduct = null;
$editGallery = [];

if ($editId > 0) {
    $stmt = db()->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $editId]);
    $editProduct = $stmt->fetch();

    $galleryStmt = db()->prepare('SELECT * FROM product_images WHERE product_id = :id ORDER BY id DESC');
    $galleryStmt->execute([':id' => $editId]);
    $editGallery = $galleryStmt->fetchAll();
}

$q = trim((string)($_GET['q'] ?? ''));
$filterCategory = (int)($_GET['category'] ?? 0);
$filterStatus = $_GET['status'] ?? '';
$filterFeatured = $_GET['featured'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(1, (int)setting('products_per_page', '15'));

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = '(p.name LIKE :q OR p.short_description LIKE :q OR p.slug LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}

if ($filterCategory > 0) {
    $where[] = 'p.category_id = :category_id';
    $params[':category_id'] = $filterCategory;
}

if ($filterStatus === 'active' || $filterStatus === 'inactive') {
    $where[] = 'p.status = :status';
    $params[':status'] = $filterStatus === 'active' ? 1 : 0;
}

if ($filterFeatured === '1' || $filterFeatured === '0') {
    $where[] = 'p.featured = :featured';
    $params[':featured'] = (int)$filterFeatured;
}

$whereSql = implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM products p WHERE {$whereSql}");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();

$pagination = pagination_meta($totalItems, $page, $perPage);

$listSql = "SELECT p.*, c.name AS category_name
            FROM products p
            INNER JOIN categories c ON c.id = p.category_id
            WHERE {$whereSql}
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset";

$listStmt = db()->prepare($listSql);
foreach ($params as $key => $value) {
    $listStmt->bindValue($key, $value);
}
$listStmt->bindValue(':limit', $pagination['per_page'], PDO::PARAM_INT);
$listStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
$listStmt->execute();
$products = $listStmt->fetchAll();

$page_title = 'Shop Products | Admin';
$page_heading = 'Shop Products';
$page_subtitle = 'Manage catalog items, pricing, and stock';
$active_admin = 'shop_products';

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
    <h4 class="mb-0">Products</h4>
    <button class="btn btn-orange" data-bs-toggle="modal" data-bs-target="#productFormModal">
        <i class="bi bi-plus-circle me-1"></i><?= $editProduct ? 'Edit Product' : 'Add Product' ?>
    </button>
</div>

<div class="card table-card p-4">
    <form class="row g-2 mb-3" method="get">
        <div class="col-md-3">
            <input type="text" class="form-control" name="q" placeholder="Search products..." value="<?= e($q) ?>">
        </div>
        <div class="col-md-2">
            <select class="form-select" name="category">
                <option value="0">All Categories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int)$category['id'] ?>" <?= $filterCategory === (int)$category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="status">
                <option value="">All Status</option>
                <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select" name="featured">
                <option value="">Featured/All</option>
                <option value="1" <?= $filterFeatured === '1' ? 'selected' : '' ?>>Featured</option>
                <option value="0" <?= $filterFeatured === '0' ? 'selected' : '' ?>>Not Featured</option>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-outline-orange" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
            <a class="btn btn-outline-secondary" href="<?= e(admin_url('products.php')) ?>">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Featured</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$products): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">No products found.</td></tr>
            <?php endif; ?>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= e(app_url('uploads/products/' . $product['main_image'])) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                            <div>
                                <div class="fw-semibold"><?= e($product['name']) ?></div>
                                <div class="small text-muted">/shop/product/<?= e($product['slug']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= e($product['category_name']) ?></td>
                    <td>
                        <div><?= e(format_currency((float)$product['price'])) ?></div>
                        <?php if (!empty($product['discount_price'])): ?>
                            <div class="small text-success">Sale: <?= e(format_currency((float)$product['discount_price'])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= e(stock_label(isset($product['stock_qty']) ? (int)$product['stock_qty'] : null)) ?></td>
                    <td><span class="badge bg-<?= (int)$product['status'] === 1 ? 'success' : 'secondary' ?>"><?= (int)$product['status'] === 1 ? 'Active' : 'Inactive' ?></span></td>
                    <td><span class="badge bg-<?= (int)$product['featured'] === 1 ? 'warning text-dark' : 'light text-dark' ?>"><?= (int)$product['featured'] === 1 ? 'Yes' : 'No' ?></span></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-orange" href="<?= e(admin_url('products.php?edit=' . (int)$product['id'])) ?>"><i class="bi bi-pencil me-1"></i>Edit</a>
                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteProductModal" data-id="<?= (int)$product['id'] ?>" data-name="<?= e($product['name']) ?>"><i class="bi bi-trash me-1"></i>Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    $queryBase = [
        'q' => $q,
        'category' => $filterCategory,
        'status' => $filterStatus,
        'featured' => $filterFeatured,
    ];
    ?>
    <nav class="mt-3">
        <ul class="pagination mb-0">
            <li class="page-item <?= $pagination['current_page'] <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e(pagination_url($queryBase, $pagination['current_page'] - 1)) ?>">Previous</a>
            </li>
            <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                <li class="page-item <?= $p === $pagination['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="<?= e(pagination_url($queryBase, $p)) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $pagination['current_page'] >= $pagination['total_pages'] ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= e(pagination_url($queryBase, $pagination['current_page'] + 1)) ?>">Next</a>
            </li>
        </ul>
    </nav>
</div>

<div class="modal fade <?= $editProduct ? 'show' : '' ?>" id="productFormModal" tabindex="-1" <?= $editProduct ? 'style="display:block" aria-modal="true" role="dialog"' : '' ?>>
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $editProduct ? 'Edit Product' : 'Add Product' ?></h5>
                    <a href="<?= e(admin_url('products.php')) ?>" class="btn-close"></a>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" value="<?= (int)($editProduct['id'] ?? 0) ?>">

                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Select category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?= (int)$category['id'] ?>" <?= (int)($editProduct['category_id'] ?? 0) === (int)$category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Product Name</label>
                            <input type="text" class="form-control" name="name" value="<?= e($editProduct['name'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Short Description</label>
                            <input type="text" class="form-control" name="short_description" value="<?= e($editProduct['short_description'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" rows="2" name="description"><?= e($editProduct['description'] ?? '') ?></textarea>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Price</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="price" value="<?= e((string)($editProduct['price'] ?? '')) ?>" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Discount Price</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="discount_price" value="<?= e((string)($editProduct['discount_price'] ?? '')) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Stock Qty</label>
                            <input type="number" min="0" class="form-control" name="stock_qty" value="<?= e((string)($editProduct['stock_qty'] ?? '')) ?>">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" <?= (!isset($editProduct['status']) || (int)$editProduct['status'] === 1) ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= (isset($editProduct['status']) && (int)$editProduct['status'] === 0) ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Featured</label>
                            <select class="form-select" name="featured">
                                <option value="0" <?= (!isset($editProduct['featured']) || (int)$editProduct['featured'] === 0) ? 'selected' : '' ?>>No</option>
                                <option value="1" <?= (isset($editProduct['featured']) && (int)$editProduct['featured'] === 1) ? 'selected' : '' ?>>Yes</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Main Image (JPG/PNG/WEBP, max 2MB)</label>
                            <input type="file" class="form-control" name="main_image" accept="image/jpeg,image/png,image/webp" <?= $editProduct ? '' : 'required' ?>>
                            <?php if (!empty($editProduct['main_image'])): ?>
                                <div class="small text-muted mt-1">Current: <?= e($editProduct['main_image']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-5">
                            <label class="form-label">Gallery Images (optional)</label>
                            <input type="file" class="form-control" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple>
                        </div>

                        <?php if ($editGallery): ?>
                            <div class="col-12">
                                <div class="small text-muted mb-2">Existing Gallery</div>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($editGallery as $img): ?>
                                        <img src="<?= e(app_url('uploads/products/' . $img['image_path'])) ?>" alt="" style="width:72px;height:72px;object-fit:cover;border-radius:8px;">
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="<?= e(admin_url('products.php')) ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button class="btn btn-orange" type="submit"><i class="bi bi-save me-1"></i>Save Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteProductModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteProductId">
                    <p class="mb-0">Delete <strong id="deleteProductName"></strong>? This cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var deleteModal = document.getElementById('deleteProductModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('deleteProductId').value = button.getAttribute('data-id');
        document.getElementById('deleteProductName').textContent = button.getAttribute('data-name');
    });
});
</script>

</div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
