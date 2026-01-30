<?php
// admin/posts/delete.php - Move to trash instead of permanent deletion
declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';

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
  exit('Invalid post id');
}

// -------------------- Check if post exists and get current status --------------------
$stmt = $db->prepare("SELECT id, status FROM posts WHERE id = ? LIMIT 1");
if (!$stmt) {
  http_response_code(500);
  exit('DB error: ' . $db->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($post_id, $current_status);
$exists = $stmt->fetch();
$stmt->close();

if (!$exists) {
  http_response_code(404);
  exit('Post not found');
}

// -------------------- Move to trash (update status) --------------------
$stmt = $db->prepare("UPDATE posts SET status = 'trashed', updated_at = NOW() WHERE id = ? LIMIT 1");
if (!$stmt) {
  http_response_code(500);
  exit('DB error: ' . $db->error);
}
$stmt->bind_param("i", $id);
$ok = $stmt->execute();

// Debug: Check if update worked
$affected_rows = $stmt->affected_rows;
$stmt->close();

if (!$ok) {
  http_response_code(500);
  exit('Failed to move post to trash');
}

// Debug output
error_log("Trash debug: ID=$id, Affected rows=$affected_rows, Success=$ok");

if ($affected_rows === 0) {
  http_response_code(500);
  exit('No rows affected - post may not exist or already trashed');
}

// -------------------- Redirect back with success message --------------------
header("Location: list.php?trashed=1");
exit;
