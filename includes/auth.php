<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function ensure_session(): void {
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
}

function is_logged_in(): bool {
  ensure_session();
  return !empty($_SESSION['admin']['id']);
}

function require_admin_login(): void {
  if (!is_logged_in()) {
    redirect("login.php");
  }
}

function admin_logout(): void {
  ensure_session();
  $_SESSION = [];
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
      $params["path"], $params["domain"], $params["secure"], $params["httponly"]
    );
  }
  session_destroy();
}
