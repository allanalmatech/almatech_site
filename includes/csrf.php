<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_is_valid($token): bool
{
    if (!$token) {
        return false;
    }
    return hash_equals(csrf_token(), $token);
}

function csrf_require_valid_request()
{
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_is_valid($token)) {
        http_response_code(419);
        exit('Invalid CSRF token. Refresh the page and retry.');
    }
}
