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
    $baseUrl = rtrim((string)(getenv('BASE_URL') ?: '/almatech_site_final'), '/');
    define('BASE_URL', $baseUrl === '/' ? '' : $baseUrl);
}
if (!defined('ADMIN_URL')) {
    define('ADMIN_URL', rtrim((string)BASE_URL, '/') . '/admin/');
}
