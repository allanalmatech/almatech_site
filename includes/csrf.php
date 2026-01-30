<?php
declare(strict_types=1);

function csrf_init(): void {
  if (session_status() === PHP_SESSION_NONE) session_start();
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
}

function csrf_token(): string {
  csrf_init();
  return (string)$_SESSION['csrf_token'];
}

function csrf_validate(string $token): bool {
  csrf_init();
  return hash_equals((string)$_SESSION['csrf_token'], $token);
}
