<?php
declare(strict_types=1);

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('This site requires PHP 8.1 or newer. Current version: ' . PHP_VERSION);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(__DIR__) . '/includes/env.php';

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
    define('DB_HOST', (string)env_value('DB_HOST', 'localhost'));
}
if (!defined('DB_NAME')) {
    define('DB_NAME', (string)env_value('DB_NAME', ''));
}
if (!defined('DB_USER')) {
    define('DB_USER', (string)env_value('DB_USER', ''));
}
if (!defined('DB_PASS')) {
    define('DB_PASS', (string)env_value('DB_PASS', ''));
}
if (!defined('DB_PORT')) {
    define('DB_PORT', (int)env_value('DB_PORT', '3306'));
}

if (!defined('BASE_URL')) {
    $envBase = rtrim((string)env_value('BASE_URL', ''), '/');
    if ($envBase !== '') {
        $baseUrl = $envBase;
    } else {
        $projectRoot = str_replace('\\', '/', dirname(__DIR__));
        $docRoot = str_replace('\\', '/', (string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $baseUrl = '';

        if ($docRoot !== '' && strpos($projectRoot, rtrim($docRoot, '/')) === 0) {
            $baseUrl = substr($projectRoot, strlen(rtrim($docRoot, '/')));
        }

        $baseUrl = '/' . trim((string)$baseUrl, '/');
    }
    define('BASE_URL', rtrim($baseUrl, '/') . '/');
}
if (!defined('ADMIN_URL')) {
    define('ADMIN_URL', rtrim((string)env_value('ADMIN_URL', rtrim((string)BASE_URL, '/') . '/admin/'), '/') . '/');
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

$GLOBALS['BASE_URL'] = BASE_URL;
$GLOBALS['ADMIN_URL'] = ADMIN_URL;

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

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
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

function redirect_to(string $path)
{
    header('Location: ' . $path);
    exit;
}

function setting(string $key, string $default = null): string
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

function refresh_settings_cache()
{
    $GLOBALS['settings_cache'] = null;
}

function whatsapp_number(): string
{
    $value = setting('whatsapp_number', '256772985659');
    return preg_replace('/\D+/', '', $value) ?: '256772985659';
}
