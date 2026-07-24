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

    $whatsappNumber = preg_replace('/\D+/', '', (string)($_POST['whatsapp_number'] ?? ''));
    $currencyLabel = strtoupper(trim((string)($_POST['currency_label'] ?? 'UGX')));
    $productsPerPage = max(1, min(60, (int)($_POST['products_per_page'] ?? 15)));
    $showOutOfStock = isset($_POST['show_out_of_stock']) ? '1' : '0';

    $toSave = [
        'whatsapp_number' => $whatsappNumber !== '' ? $whatsappNumber : '256772985659',
        'currency_label' => $currencyLabel !== '' ? $currencyLabel : 'UGX',
        'products_per_page' => (string)$productsPerPage,
        'show_out_of_stock' => $showOutOfStock,
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

    refresh_settings_cache();
    set_flash('success', 'Settings updated successfully.');
    redirect_to(admin_url('settings.php'));
}

$current = [
    'whatsapp_number' => setting('whatsapp_number', '256772985659'),
    'currency_label' => setting('currency_label', 'UGX'),
    'products_per_page' => setting('products_per_page', '15'),
    'show_out_of_stock' => setting('show_out_of_stock', '1'),
];

$page_title = 'Shop Settings | Admin';
$page_heading = 'Shop Settings';
$page_subtitle = 'Configure WhatsApp and catalog display options';
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
        </div>
    </div>
    <form method="post" class="row g-3">
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

        <div class="col-12">
            <button type="submit" class="btn btn-orange"><i class="bi bi-save me-1"></i>Save Settings</button>
        </div>
    </form>
</div>

</div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
