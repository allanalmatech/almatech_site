<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_admin_login();

require_once __DIR__ . '/../../includes/db.php';

$db = $GLOBALS['db'] ?? $mysqli ?? null;

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM pages WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $page = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($page) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'page' => $page
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Page not found'
        ]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid page ID'
    ]);
}
?>
