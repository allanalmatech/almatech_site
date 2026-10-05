<?php
declare(strict_types=1);

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('This site requires PHP 8.1 or newer. Current version: ' . PHP_VERSION);
}

require_once __DIR__ . '/includes/env.php';

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

// Base URL configuration
if (!defined('BASE_URL')) {
    $envBase = rtrim((string)env_value('BASE_URL', ''), '/');
    if ($envBase !== '') {
        $baseUrl = $envBase;
    } else {
        $projectRoot = str_replace('\\', '/', __DIR__);
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

$GLOBALS['BASE_URL'] = BASE_URL;
$GLOBALS['ADMIN_URL'] = ADMIN_URL;
