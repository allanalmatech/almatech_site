<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('csrf_is_valid')) {
    function csrf_is_valid(?string $token): bool
    {
        if (!$token) {
            return false;
        }
        return hash_equals(csrf_token(), $token);
    }
}

if (!function_exists('csrf_require_valid_request')) {
    function csrf_require_valid_request(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!csrf_is_valid($token)) {
            http_response_code(419);
            exit('Invalid CSRF token. Refresh the page and retry.');
        }
    }
}
