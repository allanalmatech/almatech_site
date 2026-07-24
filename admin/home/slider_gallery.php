<?php
declare(strict_types=1);

// admin/home/slider_gallery.php (or wherever you placed it)

// Capture any output before JSON
ob_start();

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_admin_login();

// Clear any output from auth
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

$dir = __DIR__ . '/../../uploads/slider/';
$dir = str_replace('/', DIRECTORY_SEPARATOR, $dir);

// Ensure folder exists (common 500 fix)
if (!is_dir($dir)) {
  @mkdir($dir, 0775, true);
}

function is_allowed_ext(string $ext): bool {
  $ext = strtolower($ext);
  return in_array($ext, ['jpg','jpeg','png','webp','gif'], true);
}

function list_slider_images(string $dir): array {
  $out = [];
  if (!is_dir($dir)) return $out;

  foreach (new DirectoryIterator($dir) as $fi) {
    if ($fi->isDot() || !$fi->isFile()) continue;

    $name = $fi->getFilename();
    $ext  = pathinfo($name, PATHINFO_EXTENSION);

    if (!is_allowed_ext($ext)) continue;

    $out[] = [
      'name' => $name,
      'path' => 'uploads/slider/' . $name,
      'size' => (int)$fi->getSize(),
      'mtime' => (int)$fi->getMTime(),
    ];
  }

  usort($out, function ($a, $b) { return $b['mtime'] <=> $a['mtime']; });
  return $out;
}

function is_allowed_image_name(string $name): bool {
  if ($name === '' || $name !== basename($name)) return false;
  $ext = pathinfo($name, PATHINFO_EXTENSION);
  return is_allowed_ext($ext);
}

$action = (string)($_GET['action'] ?? 'list');

if ($action === 'list') {
  echo json_encode(['success' => true, 'images' => list_slider_images($dir)]);
  exit;
}

if ($action === 'delete') {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
  }

  $name = trim((string)($_POST['name'] ?? ''));
  if (!is_allowed_image_name($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid filename']);
    exit;
  }

  $file = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;

  if (!is_file($file)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'File not found']);
    exit;
  }

  $ok = @unlink($file);
  if (!$ok) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to delete file']);
    exit;
  }

  echo json_encode(['success' => true]);
  exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'Unknown action']);
