<?php
// admin/settings/upload_brand_asset.php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';

// Initialize response
header('Content-Type: application/json');

try {
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error');
    }

    $type = $_POST['type'] ?? '';
    if (!in_array($type, ['logo', 'favicon'], true)) {
        throw new Exception('Invalid asset type');
    }

    $file = $_FILES['file'];
    $allowedTypes = $type === 'logo' 
        ? ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
        : ['image/x-icon', 'image/png', 'image/jpeg', 'image/gif'];

    if (!in_array($file['type'], $allowedTypes, true)) {
        throw new Exception('Invalid file type');
    }

    // Create upload directory
    $uploadDir = __DIR__ . '/../../assets/brand/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $type . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $uploadPath = $uploadDir . $filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to move uploaded file');
    }

    // Return success response with URL
    $publicUrl = 'assets/brand/' . $filename;
    echo json_encode([
        'success' => true,
        'url' => $publicUrl,
        'message' => 'File uploaded successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
