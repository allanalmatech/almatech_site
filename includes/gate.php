<?php
// includes/gate.php
declare(strict_types=1);

/**
 * Check maintenance mode and redirect if enabled
 * This function can be called explicitly or the file will run on include
 */
function gate_check($db) {
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }

  if (!($db instanceof mysqli)) {
    return; // if DB not available, don't block the site
  }

  // Allow admins to bypass maintenance
  $isAdmin = !empty($_SESSION['admin']['role']) && $_SESSION['admin']['role'] === 'admin';

  // Read maintenance settings
  if (function_exists('setting_get_json')) {
    $maint = setting_get_json($db, 'maintenance_settings', ['enabled' => false]);
  } else {
    $maint = ['enabled' => false];
  }

  if (!$isAdmin && !empty($maint['enabled'])) {
    require __DIR__ . '/../maintenance.php';
    exit;
  }
}

// Auto-run when included (for backward compatibility)
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../admin/includes/settings_lib.php';

$db = $GLOBALS['db'] ?? $mysqli ?? null;
gate_check($db);
