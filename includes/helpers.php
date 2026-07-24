<?php
declare(strict_types=1);

// h() function is now declared in header.php to avoid conflicts
if (!function_exists('h')) {
  function h($s): string { 
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); 
  }
}

if (!function_exists('redirect')) {
  function redirect(string $url) {
    header("Location: " . $url);
    exit;
  }
}

if (!function_exists('flash_set')) {
  function flash_set(string $type, string $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
  }
}

if (!function_exists('flash_get')) {
  function flash_get() {
    if (empty($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
  }
}

// CSRF Functions
if (!function_exists('csrf_init')) {
  function csrf_init() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
  }
}

if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    csrf_init();
    return $_SESSION['csrf_token'] ?? '';
  }
}

if (!function_exists('csrf_validate')) {
  function csrf_validate(string $token): bool {
    if (function_exists('csrf_is_valid')) {
      return csrf_is_valid($token);
    }
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
  }
}

if (!function_exists('csrf_verify')) {
  function csrf_verify() {
    $token = $_POST['csrf'] ?? $_POST['csrf_token'] ?? '';
    if (!csrf_validate($token)) {
      http_response_code(403);
      exit('CSRF token validation failed. Please refresh and try again.');
    }
  }
}
