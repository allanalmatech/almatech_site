<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config.php';

require_admin_login();
$admin = current_admin();
$hasCoverImage = table_has_column('categories', 'cover_image');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid_request();
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $status = (($_POST['status'] ?? 'active') === 'active') ? 1 : 0;

        if ($name === '') {
            set_flash('danger', 'Category name is required.');
            redirect_to(admin_url('categories.php'));
        }

        $baseSlug = slugify($name);
        $slug = make_unique_slug('categories', $baseSlug, $id > 0 ? $id : null);

        $coverImage = null;
        $oldCoverImage = null;
        if ($hasCoverImage) {
            if ($id > 0) {
                $oldStmt = db()->prepare('SELECT cover_image FROM categories WHERE id = :id LIMIT 1');
                $oldStmt->execute([':id' => $id]);
                $oldCoverImage = $oldStmt->fetchColumn() ?: null;
            }
            if (isset($_FILES['cover_image'])) {
                try {
                    $coverImage = validate_category_image_upload($_FILES['cover_image']);
                } catch (Throwable $e) {
                    set_flash('danger', $e->getMessage());
                    redirect_to(admin_url('categories.php' . ($id > 0 ? '?edit=' . $id : '')));
                }
            }
        }

        if ($id > 0) {
            if ($hasCoverImage) {
                $stmt = db()->prepare('UPDATE categories SET name = :name, slug = :slug, description = :description, status = :status, cover_image = :cover_image WHERE id = :id');
                $stmt->execute([
                    ':name' => $name,
                    ':slug' => $slug,
                    ':description' => $description,
                    ':status' => $status,
                    ':cover_image' => $coverImage ?: $oldCoverImage,
                    ':id' => $id,
                ]);
            } else {
                $stmt = db()->prepare('UPDATE categories SET name = :name, slug = :slug, description = :description, status = :status WHERE id = :id');
                $stmt->execute([
                    ':name' => $name,
                    ':slug' => $slug,
                    ':description' => $description,
                    ':status' => $status,
                    ':id' => $id,
                ]);
            }

            if ($coverImage && $oldCoverImage && $coverImage !== $oldCoverImage) {
                remove_category_image_file((string)$oldCoverImage);
            }
            set_flash('success', 'Category updated successfully.');
        } else {
            if ($hasCoverImage) {
                $stmt = db()->prepare('INSERT INTO categories (name, slug, description, status, cover_image, created_at) VALUES (:name, :slug, :description, :status, :cover_image, NOW())');
                $stmt->execute([
                    ':name' => $name,
                    ':slug' => $slug,
                    ':description' => $description,
                    ':status' => $status,
                    ':cover_image' => $coverImage,
                ]);
            } else {
                $stmt = db()->prepare('INSERT INTO categories (name, slug, description, status, created_at) VALUES (:name, :slug, :description, :status, NOW())');
                $stmt->execute([
                    ':name' => $name,
                    ':slug' => $slug,
                    ':description' => $description,
                    ':status' => $status,
                ]);
            }
            set_flash('success', 'Category added successfully.');
        }

        redirect_to(admin_url('categories.php'));
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);

        $coverImage = null;
        if ($hasCoverImage) {
            $coverStmt = db()->prepare('SELECT cover_image FROM categories WHERE id = :id LIMIT 1');
            $coverStmt->execute([':id' => $id]);
            $coverImage = $coverStmt->fetchColumn() ?: null;
        }

        $countStmt = db()->prepare('SELECT COUNT(*) FROM products WHERE category_id = :id');
        $countStmt->execute([':id' => $id]);

        if ((int)$countStmt->fetchColumn() > 0) {
            set_flash('warning', 'Cannot delete this category because it still has products.');
            redirect_to(admin_url('categories.php'));
        }

        $stmt = db()->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute([':id' => $id]);

        if ($hasCoverImage && $coverImage) {
            remove_category_image_file((string)$coverImage);
        }

        set_flash('success', 'Category deleted successfully.');
        redirect_to(admin_url('categories.php'));
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editCategory = null;

if ($editId > 0) {
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $editId]);
    $editCategory = $stmt->fetch();
}

$categories = db()->query(
    'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
     FROM categories c ORDER BY c.created_at DESC'
)->fetchAll();

$page_title = 'Shop Categories | Admin';
$page_heading = 'Shop Categories';
$page_subtitle = 'Create and organize product categories';
$active_admin = 'shop_categories';

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
<div class="card table-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h2 class="h5 mb-0">Category List</h2>
        <button class="btn btn-orange" data-bs-toggle="modal" data-bs-target="#categoryModal"><i class="bi bi-plus-circle me-1"></i>Add Category</button>
    </div>

    <div class="table-responsive mt-3">
        <table class="table table-striped align-middle">
            <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Status</th>
                <th>Products</th>
                <th>Created</th>
                <?php if ($hasCoverImage): ?><th>Cover</th><?php endif; ?>
                <th class="text-end">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$categories): ?>
                <tr><td colspan="<?= $hasCoverImage ? '7' : '6' ?>" class="text-center text-muted py-4">No categories yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($categories as $category): ?>
                <tr>
                    <td>
                        <div class="fw-semibold"><?= e($category['name']) ?></div>
                        <div class="small text-muted"><?= e((string)$category['description']) ?></div>
                    </td>
                    <td><?= e($category['slug']) ?></td>
                    <td>
                        <span class="badge bg-<?= (int)$category['status'] === 1 ? 'success' : 'secondary' ?>">
                            <?= (int)$category['status'] === 1 ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td><?= (int)$category['product_count'] ?></td>
                    <td><?= e((string)$category['created_at']) ?></td>
                    <?php if ($hasCoverImage): ?>
                        <td>
                            <?php if (!empty($category['cover_image'])): ?>
                                <img src="<?= e(app_url('uploads/categories/' . $category['cover_image'])) ?>" alt="<?= e($category['name']) ?>" style="width:56px;height:42px;object-fit:cover;border-radius:6px;">
                            <?php else: ?>
                                <span class="text-muted small">None</span>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-orange" href="<?= e(admin_url('categories.php?edit=' . (int)$category['id'])) ?>"><i class="bi bi-pencil me-1"></i>Edit</a>
                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-id="<?= (int)$category['id'] ?>" data-name="<?= e($category['name']) ?>"><i class="bi bi-trash me-1"></i>Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade <?= $editCategory ? 'show' : '' ?>" id="categoryModal" tabindex="-1" <?= $editCategory ? 'style="display:block" aria-modal="true" role="dialog"' : '' ?>>
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><?= $editCategory ? 'Edit Category' : 'Add Category' ?></h5>
                    <a href="<?= e(admin_url('categories.php')) ?>" class="btn-close"></a>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int)($editCategory['id'] ?? 0) ?>">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" value="<?= e($editCategory['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" rows="3" name="description"><?= e($editCategory['description'] ?? '') ?></textarea>
                    </div>
                    <?php if ($hasCoverImage): ?>
                        <div class="mb-3">
                            <label class="form-label">Category Hero Cover Image (optional)</label>
                            <input type="file" class="form-control" name="cover_image" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Recommended size: <strong>1600 x 420 px</strong>. Allowed: JPG, PNG, WEBP. Max size: 2MB.</div>
                            <?php if (!empty($editCategory['cover_image'])): ?>
                                <div class="mt-2">
                                    <img src="<?= e(app_url('uploads/categories/' . $editCategory['cover_image'])) ?>" alt="Current cover" style="width:120px;height:72px;object-fit:cover;border-radius:8px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning py-2 small mb-3">
                            Hero cover upload is not enabled yet. Run SQL: <code>ALTER TABLE categories ADD COLUMN cover_image VARCHAR(255) NULL AFTER description;</code>
                        </div>
                    <?php endif; ?>
                    <div>
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active" <?= (!isset($editCategory['status']) || (int)$editCategory['status'] === 1) ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= (isset($editCategory['status']) && (int)$editCategory['status'] === 0) ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <a class="btn btn-outline-secondary" href="<?= e(admin_url('categories.php')) ?>">Cancel</a>
                    <button class="btn btn-orange" type="submit"><i class="bi bi-save me-1"></i>Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteCategoryId">
                    <p class="mb-0">Delete <strong id="deleteCategoryName"></strong>? This cannot be undone.</p>
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
    var deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        document.getElementById('deleteCategoryId').value = button.getAttribute('data-id');
        document.getElementById('deleteCategoryName').textContent = button.getAttribute('data-name');
    });
});
</script>

</div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
