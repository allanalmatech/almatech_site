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
  flash_set('danger', 'Invalid team member selected.');
  redirect('list.php');
}

// Get team member info to delete photo
$stmt = $db->prepare("SELECT photo FROM team_members WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$member = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$member) {
  flash_set('danger', 'Team member not found.');
  redirect('list.php');
}

// Delete team member
$stmt = $db->prepare("DELETE FROM team_members WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$success = $stmt->execute();
$stmt->close();

if ($success) {
  // Delete photo file if exists
  if (!empty($member['photo'])) {
    $photoPath = __DIR__ . '/../../uploads/team/' . $member['photo'];
    if (file_exists($photoPath)) {
      unlink($photoPath);
    }
  }
  
  flash_set('success', 'Team member deleted successfully.');
} else {
  flash_set('danger', 'Failed to delete team member.');
}

redirect('list.php');
