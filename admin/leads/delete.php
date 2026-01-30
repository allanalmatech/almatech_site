<?php
// admin/leads/delete.php
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
  exit('Invalid lead id');
}

// Ensure exists (optional, nicer UX)
$chk = $db->prepare("SELECT id FROM leads WHERE id = ? LIMIT 1");
if (!$chk) {
  http_response_code(500);
  exit('DB error: ' . $db->error);
}
$chk->bind_param("i", $id);
$chk->execute();
$chk->store_result();
$exists = ($chk->num_rows === 1);
$chk->close();

if (!$exists) {
  header("Location: list.php");
  exit;
}

$stmt = $db->prepare("DELETE FROM leads WHERE id = ? LIMIT 1");
if (!$stmt) {
  http_response_code(500);
  exit('DB error: ' . $db->error);
}
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: list.php?deleted=1");
exit;
