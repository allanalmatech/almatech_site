<?php
declare(strict_types=1);

// Early authentication check before any HTML output
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_admin_login();

require_once __DIR__ . '/../../includes/db.php';

$db = $GLOBALS['db'] ?? $mysqli ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  redirect('list.php');
}

$token = (string)($_POST['csrf_token'] ?? '');
$id = (int)($_POST['id'] ?? 0);

if (!csrf_validate($token)) {
  flash_set('danger', 'Security check failed. Please refresh and try again.');
  redirect('list.php');
}

if ($id <= 0) {
  flash_set('danger', 'Invalid testimonial selected.');
  redirect('list.php');
}

$stmt = $db->prepare("DELETE FROM testimonials WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$success = $stmt->execute();
$stmt->close();

if ($success) {
  flash_set('success', 'Testimonial deleted successfully.');
} else {
  flash_set('danger', 'Failed to delete testimonial.');
}

redirect('list.php');
