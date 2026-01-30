<?php
// admin/leads/update_status.php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';

csrf_init();

$db = $GLOBALS['db'] ?? $mysqli ?? null;
if (!($db instanceof mysqli)) {
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => 'DB not available']);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

// CSRF (supports both normal form posts and fetch/ajax)
try {
  csrf_verify();
} catch (Throwable $e) {
  http_response_code(403);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
  exit;
}

$id     = (int)($_POST['id'] ?? 0);
$status = trim((string)($_POST['status'] ?? ''));

$allowed = ['new','contacted','won','lost','spam'];
if ($id <= 0) {
  http_response_code(400);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => 'Invalid id']);
  exit;
}
if (!in_array($status, $allowed, true)) {
  http_response_code(400);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => 'Invalid status']);
  exit;
}

$stmt = $db->prepare("UPDATE leads SET status = ?, updated_at = NOW() WHERE id = ? LIMIT 1");
if (!$stmt) {
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => 'DB error: ' . $db->error]);
  exit;
}
$stmt->bind_param("si", $status, $id);
$ok = $stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

if (!$ok) {
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => false, 'error' => 'Failed to update']);
  exit;
}

// If id doesn't exist, affected_rows can be 0 (also 0 if same status). We'll verify existence.
if ($affected === 0) {
  $chk = $db->prepare("SELECT id FROM leads WHERE id = ? LIMIT 1");
  if ($chk) {
    $chk->bind_param("i", $id);
    $chk->execute();
    $chk->store_result();
    $exists = $chk->num_rows === 1;
    $chk->close();
    if (!$exists) {
      http_response_code(404);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok' => false, 'error' => 'Lead not found']);
      exit;
    }
  }
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
  'ok' => true,
  'id' => $id,
  'status' => $status
]);
