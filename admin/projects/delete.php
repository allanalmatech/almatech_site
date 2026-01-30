<?php
// admin/projects/delete.php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../config.php';

csrf_init();

$db = $GLOBALS['db'] ?? $mysqli ?? null;
if (!($db instanceof mysqli)) {
  http_response_code(500);
  exit('DB not available');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method not allowed');
}

csrf_verify();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  exit('Invalid project id');
}

// -------------------- Load project (for image cleanup) --------------------
$stmt = $db->prepare("SELECT cover_image FROM projects WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) {
  http_response_code(404);
  exit('Project not found');
}

// -------------------- Delete DB row --------------------
$stmt = $db->prepare("DELETE FROM projects WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
  http_response_code(500);
  exit('Failed to delete project');
}

// -------------------- Optional: delete cover image file --------------------
if (!empty($project['cover_image'])) {
  // Example stored path: /uploads/projects/project_1_20260101.jpg
  $relativePath = parse_url($project['cover_image'], PHP_URL_PATH);

  if ($relativePath) {
    $fsPath = realpath(__DIR__ . '/../../') . $relativePath;

    if (is_file($fsPath)) {
      @unlink($fsPath);
    }
  }
}

// -------------------- Redirect back to list --------------------
header("Location: " . ADMIN_URL . "projects/list.php");
exit;
