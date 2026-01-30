<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) session_start();

function csrf_token(): string {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return (string)$_SESSION['csrf_token'];
}

function csrf_field(): string {
  $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
  return '<input type="hidden" name="csrf_token" value="'.$t.'">';
}

function csrf_verify_or_die(): void {
  $posted = (string)($_POST['csrf_token'] ?? '');
  $sess   = (string)($_SESSION['csrf_token'] ?? '');
  if (!$posted || !$sess || !hash_equals($sess, $posted)) {
    http_response_code(403);
    die("Invalid CSRF token.");
  }
}
