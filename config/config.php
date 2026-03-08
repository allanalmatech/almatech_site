<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        return strpos($haystack, $needle) === 0;
    }
}

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        return strpos($haystack, $needle) !== false;
    }
}

if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
}
if (!defined('DB_NAME')) {
    define('DB_NAME', getenv('DB_NAME') ?: 'almatech_site');
}
if (!defined('DB_USER')) {
    define('DB_USER', getenv('DB_USER') ?: 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', getenv('DB_PASS') ?: '');
}

if (!defined('BASE_URL')) {
    $baseUrl = rtrim((string)(getenv('BASE_URL') ?: '/almatech_site_final'), '/');
    define('BASE_URL', $baseUrl === '/' ? '' : $baseUrl);
}
if (!defined('UPLOAD_PRODUCTS_PATH')) {
    define('UPLOAD_PRODUCTS_PATH', dirname(__DIR__) . '/uploads/products/');
}
if (!defined('UPLOAD_CATEGORIES_PATH')) {
    define('UPLOAD_CATEGORIES_PATH', dirname(__DIR__) . '/uploads/categories/');
}
if (!defined('UPLOAD_SHOP_PATH')) {
    define('UPLOAD_SHOP_PATH', dirname(__DIR__) . '/uploads/shop/');
}
if (!defined('UPLOAD_REQUESTS_PATH')) {
    define('UPLOAD_REQUESTS_PATH', dirname(__DIR__) . '/uploads/requests/');
}

$GLOBALS['settings_cache'] = null;

function settings_uses_legacy_columns(): bool
{
    static $legacy = null;

    if ($legacy !== null) {
        return $legacy;
    }

    try {
        $stmt = db()->query("SHOW COLUMNS FROM settings LIKE 'setting_key'");
        $hasNew = (bool)$stmt->fetch();
        $legacy = !$hasNew;
    } catch (Throwable $e) {
        $legacy = true;
    }

    return $legacy;
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function app_url(string $path = ''): string
{
    return rtrim((string)BASE_URL, '/') . '/' . ltrim($path, '/');
}

function admin_url(string $path = ''): string
{
    return app_url('admin/' . ltrim($path, '/'));
}

function shop_url(string $path = ''): string
{
    return app_url('shop/' . ltrim($path, '/'));
}

function redirect_to(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function setting(string $key, ?string $default = null): string
{
    if (!is_array($GLOBALS['settings_cache'])) {
        $GLOBALS['settings_cache'] = [];
        try {
            if (settings_uses_legacy_columns()) {
                $stmt = db()->query('SELECT `key`, `value` FROM settings');
                foreach ($stmt as $row) {
                    $GLOBALS['settings_cache'][(string)$row['key']] = (string)$row['value'];
                }
            } else {
                $stmt = db()->query('SELECT setting_key, setting_value FROM settings');
                foreach ($stmt as $row) {
                    $GLOBALS['settings_cache'][(string)$row['setting_key']] = (string)$row['setting_value'];
                }
            }
        } catch (Throwable $e) {
            $GLOBALS['settings_cache'] = [];
        }
    }

    if (array_key_exists($key, $GLOBALS['settings_cache'])) {
        return (string)$GLOBALS['settings_cache'][$key];
    }

    return (string)($default ?? '');
}

function refresh_settings_cache(): void
{
    $GLOBALS['settings_cache'] = null;
}

function whatsapp_number(): string
{
    $value = setting('whatsapp_number', '256772985659');
    return preg_replace('/\D+/', '', $value) ?: '256772985659';
}
