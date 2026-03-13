<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../config.php';

require_admin_login();
$admin = current_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid_request();

    $uploadedShopHeroCover = null;
    $uploadedSearchHeroCover = null;

    try {
        $whatsappNumber = preg_replace('/\D+/', '', (string)($_POST['whatsapp_number'] ?? ''));
        $currencyLabel = strtoupper(trim((string)($_POST['currency_label'] ?? 'UGX')));
        $productsPerPage = max(1, min(60, (int)($_POST['products_per_page'] ?? 15)));
        $showOutOfStock = isset($_POST['show_out_of_stock']) ? '1' : '0';
        $watermarkEnabled = isset($_POST['image_watermark_enabled']) ? '1' : '0';
        $watermarkText = trim((string)($_POST['image_watermark_text'] ?? ''));
        $watermarkPosition = trim((string)($_POST['image_watermark_position'] ?? 'bottom-right'));

        $allowedWatermarkPositions = [
            'top-left',
            'top-center',
            'top-right',
            'center-left',
            'center',
            'center-right',
            'bottom-left',
            'bottom-center',
            'bottom-right',
        ];
        if (!in_array($watermarkPosition, $allowedWatermarkPositions, true)) {
            $watermarkPosition = 'bottom-right';
        }

        $existingShopHeroCover = trim(setting('shop_hero_cover', ''));
        $removeShopHeroCover = isset($_POST['remove_shop_hero_cover']);
        $shopHeroCoverValue = $removeShopHeroCover ? '' : $existingShopHeroCover;

        $uploadedShopHeroCover = validate_shop_hero_cover_upload($_FILES['shop_hero_cover'] ?? []);
        if ($uploadedShopHeroCover !== null) {
            $shopHeroCoverValue = 'uploads/shop/' . $uploadedShopHeroCover;
        }

        $existingSearchHeroCover = trim(setting('search_hero_cover', ''));
        $removeSearchHeroCover = isset($_POST['remove_search_hero_cover']);
        $searchHeroCoverValue = $removeSearchHeroCover ? '' : $existingSearchHeroCover;

        $uploadedSearchHeroCover = validate_shop_hero_cover_upload($_FILES['search_hero_cover'] ?? []);
        if ($uploadedSearchHeroCover !== null) {
            $searchHeroCoverValue = 'uploads/shop/' . $uploadedSearchHeroCover;
        }

        $toSave = [
            'whatsapp_number' => $whatsappNumber !== '' ? $whatsappNumber : '256772985659',
            'currency_label' => $currencyLabel !== '' ? $currencyLabel : 'UGX',
            'products_per_page' => (string)$productsPerPage,
            'show_out_of_stock' => $showOutOfStock,
            'image_watermark_enabled' => $watermarkEnabled,
            'image_watermark_text' => $watermarkText,
            'image_watermark_position' => $watermarkPosition,
            'shop_hero_cover' => $shopHeroCoverValue,
            'search_hero_cover' => $searchHeroCoverValue,
        ];

        if (settings_uses_legacy_columns()) {
            $stmt = db()->prepare('INSERT INTO settings (`key`, `value`, updated_at) VALUES (:setting_key, :setting_value, NOW()) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()');
        } else {
            $stmt = db()->prepare('INSERT INTO settings (setting_key, setting_value, updated_at) VALUES (:setting_key, :setting_value, NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()');
        }

        foreach ($toSave as $key => $value) {
            $stmt->execute([
                ':setting_key' => $key,
                ':setting_value' => $value,
            ]);
        }

        if ($uploadedShopHeroCover !== null || $removeShopHeroCover) {
            remove_shop_hero_cover_file($existingShopHeroCover);
        }

        if ($uploadedSearchHeroCover !== null || $removeSearchHeroCover) {
            remove_shop_hero_cover_file($existingSearchHeroCover);
        }

        refresh_settings_cache();
        set_flash('success', 'Settings updated successfully.');
    } catch (Throwable $e) {
        if ($uploadedShopHeroCover !== null) {
            remove_shop_hero_cover_file('uploads/shop/' . $uploadedShopHeroCover);
        }
        if ($uploadedSearchHeroCover !== null) {
            remove_shop_hero_cover_file('uploads/shop/' . $uploadedSearchHeroCover);
        }
        set_flash('danger', $e->getMessage());
    }

    redirect_to(admin_url('settings.php'));
}

$current = [
    'whatsapp_number' => setting('whatsapp_number', '256772985659'),
    'currency_label' => setting('currency_label', 'UGX'),
    'products_per_page' => setting('products_per_page', '15'),
    'show_out_of_stock' => setting('show_out_of_stock', '1'),
    'image_watermark_enabled' => setting('image_watermark_enabled', '0'),
    'image_watermark_text' => setting('image_watermark_text', 'Alma Tech Consults'),
    'image_watermark_position' => setting('image_watermark_position', 'bottom-right'),
    'shop_hero_cover' => setting('shop_hero_cover', ''),
    'search_hero_cover' => setting('search_hero_cover', ''),
];

$shopHeroCoverPreview = shop_hero_cover_url($current['shop_hero_cover']);
$searchHeroCoverPreview = shop_hero_cover_url($current['search_hero_cover']);

$page_title = 'Shop Settings | Admin';
$page_heading = 'Shop Settings';
$page_subtitle = 'Configure WhatsApp, catalog display, and shop hero images';
$active_admin = 'shop_settings';

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
<div class="card table-card p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h2 class="h5 mb-0">Shop Settings</h2>
        <div class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-orange" href="<?= e(ADMIN_URL . 'categories.php') ?>"><i class="bi bi-tags me-1"></i>Categories</a>
            <a class="btn btn-sm btn-outline-orange" href="<?= e(ADMIN_URL . 'products.php') ?>"><i class="bi bi-box-seam me-1"></i>Products</a>
            <a class="btn btn-sm btn-outline-orange" href="<?= e(ADMIN_URL . 'requests.php') ?>"><i class="bi bi-inboxes me-1"></i>Requests</a>
        </div>
    </div>
    <form method="post" class="row g-3" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="col-md-6">
            <label class="form-label">WhatsApp Number</label>
            <input type="text" class="form-control" name="whatsapp_number" value="<?= e($current['whatsapp_number']) ?>" placeholder="256772985659" required>
            <div class="form-text">Use international format without + sign.</div>
        </div>

        <div class="col-md-3">
            <label class="form-label">Currency Label</label>
            <input type="text" class="form-control" name="currency_label" value="<?= e($current['currency_label']) ?>" maxlength="10" required>
        </div>

        <div class="col-md-3">
            <label class="form-label">Products Per Page</label>
            <input type="number" class="form-control" name="products_per_page" value="<?= e($current['products_per_page']) ?>" min="1" max="60" required>
        </div>

        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="show_out_of_stock" id="showOutStock" <?= $current['show_out_of_stock'] === '1' ? 'checked' : '' ?>>
                <label class="form-check-label" for="showOutStock">Show out-of-stock products in public shop</label>
            </div>
        </div>

        <div class="col-12 mt-2">
            <div class="border rounded p-3 bg-light-subtle">
                <div class="fw-semibold mb-2">Auto Watermark for Uploaded Images</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="image_watermark_enabled" id="imageWatermarkEnabled" <?= $current['image_watermark_enabled'] === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="imageWatermarkEnabled">Enable auto watermark</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Watermark Text</label>
                        <input type="text" class="form-control" name="image_watermark_text" value="<?= e($current['image_watermark_text']) ?>" placeholder="Alma Tech Consults" maxlength="80">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Watermark Position</label>
                        <select class="form-select" name="image_watermark_position">
                            <?php
                            $watermarkPositions = [
                                'top-left' => 'Top Left',
                                'top-center' => 'Top Center',
                                'top-right' => 'Top Right',
                                'center-left' => 'Center Left',
                                'center' => 'Center',
                                'center-right' => 'Center Right',
                                'bottom-left' => 'Bottom Left',
                                'bottom-center' => 'Bottom Center',
                                'bottom-right' => 'Bottom Right',
                            ];
                            foreach ($watermarkPositions as $value => $label):
                            ?>
                                <option value="<?= e($value) ?>" <?= $current['image_watermark_position'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-text mt-2">Applies to new JPG/PNG/WEBP uploads through the shop/product image upload flow.</div>
            </div>
        </div>

        <div class="col-12">
            <label class="form-label">Shop Hero Cover</label>
            <input type="file" class="form-control" name="shop_hero_cover" accept="image/jpeg,image/png,image/webp">
            <div class="form-text">Recommended: 1600x700 (JPG, PNG, WEBP up to 2MB).</div>
            <?php if ($shopHeroCoverPreview): ?>
                <div class="mt-3">
                    <img src="<?= e($shopHeroCoverPreview) ?>" alt="Shop hero cover preview" style="max-width: 320px; width: 100%; border-radius: 12px; border: 1px solid #e2e8f0;">
                </div>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="remove_shop_hero_cover" id="removeShopHeroCover">
                    <label class="form-check-label" for="removeShopHeroCover">Remove current hero cover</label>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-12">
            <label class="form-label">Search Catalog Hero Cover</label>
            <input type="file" class="form-control" name="search_hero_cover" accept="image/jpeg,image/png,image/webp">
            <div class="form-text">Recommended: 1600x700 (JPG, PNG, WEBP up to 2MB).</div>
            <?php if ($searchHeroCoverPreview): ?>
                <div class="mt-3">
                    <img src="<?= e($searchHeroCoverPreview) ?>" alt="Search hero cover preview" style="max-width: 320px; width: 100%; border-radius: 12px; border: 1px solid #e2e8f0;">
                </div>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="remove_search_hero_cover" id="removeSearchHeroCover">
                    <label class="form-check-label" for="removeSearchHeroCover">Remove current search hero cover</label>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-12">
            <button type="submit" class="btn btn-orange"><i class="bi bi-save me-1"></i>Save Settings</button>
        </div>
    </form>
</div>

</div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
