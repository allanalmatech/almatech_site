<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';

csrf_init();
$db = $GLOBALS['db'] ?? $mysqli ?? null;
if (!($db instanceof mysqli)) { http_response_code(500); exit('DB not available'); }

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = (string)($_POST['csrf'] ?? '');
  $id = (int)($_POST['id'] ?? 0);

  if (!csrf_validate($token)) {
    $errors[] = "Security check failed. Please refresh and try again.";
  } elseif ($id <= 0) {
    $errors[] = "Invalid template ID.";
  } else {
    // Check if template exists
    $stmt = $db->prepare("SELECT id, title FROM maintenance_templates WHERE id = ? LIMIT 1");
    if ($stmt) {
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $result = $stmt->get_result();
      $template = $result ? $result->fetch_assoc() : null;
      $stmt->close();

      if (!$template) {
        $errors[] = "Template not found.";
      } else {
        // Delete the template
        $stmt = $db->prepare("DELETE FROM maintenance_templates WHERE id = ?");
        if ($stmt) {
          $stmt->bind_param("i", $id);
          if ($stmt->execute()) {
            $success = "Template '{$template['title']}' deleted successfully!";
          } else {
            $errors[] = "Failed to delete template.";
          }
          $stmt->close();
        } else {
          $errors[] = "Database error preparing delete.";
        }
      }
    } else {
      $errors[] = "Database error checking template.";
    }
  }
}

// Redirect back to list
if ($success) {
  flash_set('success', $success);
  redirect('list.php');
} else {
  if ($errors) {
    flash_set('danger', implode('<br>', $errors));
  }
  redirect('list.php');
}
?>