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

    return $filename;
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
