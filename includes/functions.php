<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function slugify(string $text): string
{
    $text = trim($text);
    $text = preg_replace('/[^\pL\pN]+/u', '-', $text) ?? '';
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
    $text = preg_replace('/[^a-zA-Z0-9-]/', '', $text) ?? '';
    $text = strtolower(trim($text, '-'));
    return $text !== '' ? $text : 'item';
}

function make_unique_slug(string $table, string $baseSlug, int $ignoreId = null): string
{
    $slug = $baseSlug;
    $i = 1;

    while (true) {
        $sql = "SELECT id FROM {$table} WHERE slug = :slug";
        if ($ignoreId !== null) {
            $sql .= ' AND id != :ignore_id';
        }

        $stmt = db()->prepare($sql);
        $stmt->bindValue(':slug', $slug);
        if ($ignoreId !== null) {
            $stmt->bindValue(':ignore_id', $ignoreId, PDO::PARAM_INT);
        }
        $stmt->execute();

        if (!$stmt->fetch()) {
            break;
        }

        $slug = $baseSlug . '-' . $i;
        $i++;
    }

    return $slug;
}

function currency_label(): string
{
    return setting('currency_label', 'UGX');
}

function format_currency($amount): string
{
    if ($amount === null) {
        return '-';
    }
    return currency_label() . ' ' . number_format($amount, 0);
}

function set_flash(string $type, string $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash()
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function pagination_meta(int $totalItems, int $currentPage, int $perPage): array
{
    $perPage = max(1, $perPage);
    $totalPages = max(1, (int)ceil($totalItems / $perPage));
    $currentPage = min(max(1, $currentPage), $totalPages);
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total' => $totalItems,
        'per_page' => $perPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
    ];
}

function pagination_url(array $query, int $page): string
{
    $query['page'] = max(1, $page);
    return '?' . http_build_query($query);
}

function stock_label($qty): string
{
    if ($qty === null) {
        return 'In stock';
    }
    return $qty > 0 ? 'In stock (' . $qty . ')' : 'Out of stock';
}

function validate_image_upload(array $file)
{
    return validate_image_upload_to_dir($file, UPLOAD_PRODUCTS_PATH, 'product_');
}

function validate_image_upload_to_dir(array $file, string $targetDir, string $prefix = 'upload_')
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed. Please try again.');
    }

    if (($file['size'] ?? 0) > (2 * 1024 * 1024)) {
        throw new RuntimeException('Image exceeds 2MB size limit.');
    }

    $tmp = (string)($file['tmp_name'] ?? '');
    if (!is_uploaded_file($tmp)) {
        throw new RuntimeException('Invalid upload source.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($tmp);
    $map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($map[$mime])) {
        throw new RuntimeException('Only JPG, PNG, and WEBP images are allowed.');
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0775, true);
    }

    $filename = uniqid($prefix, true) . '.' . $map[$mime];
    $target = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($tmp, $target)) {
        throw new RuntimeException('Failed to save uploaded image.');
    }

    apply_text_watermark_to_image($target, $mime);

    return $filename;
}

function image_watermark_settings(): array
{
    $defaults = [
        'enabled' => false,
        'text' => 'Alma Tech Consults',
        'position' => 'bottom-right',
    ];

    if (!function_exists('setting')) {
        return $defaults;
    }

    $enabled = setting('image_watermark_enabled', '0') === '1';
    $text = trim((string)setting('image_watermark_text', $defaults['text']));
    $position = trim((string)setting('image_watermark_position', $defaults['position']));

    $allowedPositions = [
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

    if (!in_array($position, $allowedPositions, true)) {
        $position = $defaults['position'];
    }

    return [
        'enabled' => $enabled,
        'text' => $text,
        'position' => $position,
    ];
}

function apply_text_watermark_to_image(string $imagePath, string $mime)
{
    if (!extension_loaded('gd') || !is_file($imagePath)) {
        return;
    }

    $settings = image_watermark_settings();
    if (empty($settings['enabled']) || trim((string)($settings['text'] ?? '')) === '') {
        return;
    }

    $image = null;
    if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
        $image = @imagecreatefromjpeg($imagePath);
    } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
        $image = @imagecreatefrompng($imagePath);
    } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $image = @imagecreatefromwebp($imagePath);
    }

    if (!$image) {
        return;
    }

    $width = (int)imagesx($image);
    $height = (int)imagesy($image);
    if ($width <= 0 || $height <= 0) {
        imagedestroy($image);
        return;
    }

    imagealphablending($image, true);
    imagesavealpha($image, true);

    $text = (string)$settings['text'];
    $font = 5;
    $textWidth = imagefontwidth($font) * strlen($text);
    $textHeight = imagefontheight($font);
    $padding = max(8, (int)floor(min($width, $height) * 0.03));

    $x = $padding;
    $y = $padding;
    switch ((string)$settings['position']) {
        case 'top-center':
            $x = (int)max($padding, floor(($width - $textWidth) / 2));
            $y = $padding;
            break;
        case 'top-right':
            $x = max($padding, $width - $textWidth - $padding);
            $y = $padding;
            break;
        case 'center-left':
            $x = $padding;
            $y = (int)max($padding, floor(($height - $textHeight) / 2));
            break;
        case 'center':
            $x = (int)max($padding, floor(($width - $textWidth) / 2));
            $y = (int)max($padding, floor(($height - $textHeight) / 2));
            break;
        case 'center-right':
            $x = max($padding, $width - $textWidth - $padding);
            $y = (int)max($padding, floor(($height - $textHeight) / 2));
            break;
        case 'bottom-left':
            $x = $padding;
            $y = max($padding, $height - $textHeight - $padding);
            break;
        case 'bottom-center':
            $x = (int)max($padding, floor(($width - $textWidth) / 2));
            $y = max($padding, $height - $textHeight - $padding);
            break;
        case 'bottom-right':
            $x = max($padding, $width - $textWidth - $padding);
            $y = max($padding, $height - $textHeight - $padding);
            break;
        case 'top-left':
        default:
            $x = $padding;
            $y = $padding;
            break;
    }

    $shadow = imagecolorallocatealpha($image, 0, 0, 0, 75);
    $color = imagecolorallocatealpha($image, 255, 255, 255, 60);
    imagestring($image, $font, $x + 1, $y + 1, $text, $shadow);
    imagestring($image, $font, $x, $y, $text, $color);

    if ($mime === 'image/jpeg' && function_exists('imagejpeg')) {
        @imagejpeg($image, $imagePath, 88);
    } elseif ($mime === 'image/png' && function_exists('imagepng')) {
        @imagepng($image, $imagePath, 6);
    } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
        @imagewebp($image, $imagePath, 86);
    }

    imagedestroy($image);
}

function remove_product_image_file($filename)
{
    if (!$filename) {
        return;
    }
    $path = UPLOAD_PRODUCTS_PATH . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}

function product_image_url($storedValue): string
{
    $fallback = app_url('uploads/products/placeholder.svg');
    $value = trim((string)$storedValue);
    if ($value === '') {
        return $fallback;
    }

    if (preg_match('#^https?://#i', $value)) {
        return $value;
    }

    $normalized = str_replace('\\', '/', ltrim($value, '/'));

    if (strpos($normalized, 'uploads/products/') === 0) {
        $path = dirname(__DIR__) . '/' . $normalized;
        if (is_file($path)) {
            return app_url($normalized);
        }
    }

    $filename = basename($normalized);
    if ($filename !== '') {
        $directPath = UPLOAD_PRODUCTS_PATH . $filename;
        if (is_file($directPath)) {
            return app_url('uploads/products/' . rawurlencode($filename));
        }
    }

    return $fallback;
}

function validate_category_image_upload(array $file)
{
    return validate_image_upload_to_dir($file, UPLOAD_CATEGORIES_PATH, 'category_');
}

function remove_category_image_file($filename)
{
    if (!$filename) {
        return;
    }
    $path = UPLOAD_CATEGORIES_PATH . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}

function validate_shop_hero_cover_upload(array $file)
{
    return validate_image_upload_to_dir($file, UPLOAD_SHOP_PATH, 'shop_hero_');
}

function remove_shop_hero_cover_file($storedValue)
{
    $value = trim((string)$storedValue);
    if ($value === '' || preg_match('#^https?://#i', $value)) {
        return;
    }

    $filename = basename(str_replace('\\', '/', $value));
    if ($filename === '') {
        return;
    }

    $path = UPLOAD_SHOP_PATH . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

function shop_hero_cover_url($storedValue)
{
    $value = trim((string)$storedValue);
    if ($value === '') {
        return null;
    }

    if (preg_match('#^https?://#i', $value)) {
        return $value;
    }

    $normalized = str_replace('\\', '/', ltrim($value, '/'));

    if (strpos($normalized, 'uploads/shop/') === 0) {
        $path = dirname(__DIR__) . '/' . $normalized;
        if (is_file($path)) {
            return app_url($normalized);
        }
    }

    $filename = basename($normalized);
    if ($filename !== '') {
        $path = UPLOAD_SHOP_PATH . $filename;
        if (is_file($path)) {
            return app_url('uploads/shop/' . rawurlencode($filename));
        }
    }

    return null;
}

function validate_request_image_upload(array $file)
{
    return validate_image_upload_to_dir($file, UPLOAD_REQUESTS_PATH, 'request_');
}

function remove_gadget_request_image_file($filename)
{
    if (!$filename) {
        return;
    }

    $path = UPLOAD_REQUESTS_PATH . basename($filename);
    if (is_file($path)) {
        @unlink($path);
    }
}

function gadget_request_image_url($storedValue): string
{
    $value = trim((string)$storedValue);
    if ($value === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $value)) {
        return $value;
    }

    $normalized = str_replace('\\', '/', ltrim($value, '/'));
    if (strpos($normalized, 'uploads/requests/') === 0) {
        $path = dirname(__DIR__) . '/' . $normalized;
        if (is_file($path)) {
            return app_url($normalized);
        }
    }

    $filename = basename($normalized);
    if ($filename !== '') {
        $path = UPLOAD_REQUESTS_PATH . $filename;
        if (is_file($path)) {
            return app_url('uploads/requests/' . rawurlencode($filename));
        }
    }

    return '';
}

function table_has_column(string $table, string $column): bool
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $safeTable = str_replace('`', '', $table);
        $safeColumn = str_replace(["\\", "'"], ['\\\\', "\\'"], $column);
        $sql = "SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'";
        $stmt = db()->query($sql);
        $cache[$key] = (bool)$stmt->fetch();
    } catch (Throwable $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
}
