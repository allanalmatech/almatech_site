<?php
declare(strict_types=1);

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
    $envBase = rtrim((string)(getenv('BASE_URL') ?: ''), '/');
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
    define('BASE_URL', $baseUrl === '/' ? '' : $baseUrl);
}
if (!defined('ADMIN_URL')) {
    define('ADMIN_URL', rtrim((string)BASE_URL, '/') . '/admin/');
}
