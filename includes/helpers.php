<?php
declare(strict_types=1);

// h() function is now declared in header.php to avoid conflicts
if (!function_exists('h')) {
  function h($s): string { 
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); 
  }
}

function redirect(string $url): void {
  header("Location: " . $url);
  exit;
}

function flash_set(string $type, string $msg): void {
  $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function flash_get(): ?array {
  if (empty($_SESSION['flash'])) return null;
  $f = $_SESSION['flash'];
  unset($_SESSION['flash']);
  return $f;
}

// CSRF Functions
function csrf_init(): void {
  if (session_status() === PHP_SESSION_NONE) session_start();
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
}

function csrf_token(): string {
  return $_SESSION['csrf_token'] ?? '';
}

function csrf_validate(string $token): bool {
  return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function csrf_verify(): void {
  $token = $_POST['csrf'] ?? $_POST['csrf_token'] ?? '';
  if (!csrf_validate($token)) {
    http_response_code(403);
    exit('CSRF token validation failed. Please refresh and try again.');
  }
}
