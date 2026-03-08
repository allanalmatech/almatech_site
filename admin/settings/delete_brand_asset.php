<?php
// admin/settings/delete_brand_asset.php
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

    $type = $_POST['type'] ?? '';
    $url = $_POST['url'] ?? '';

    if (!in_array($type, ['logo', 'favicon'], true)) {
        throw new Exception('Invalid asset type');
    }

    if (empty($url)) {
        throw new Exception('No URL provided');
    }

    // Security: Only allow files in assets/brand directory
    if (strpos((string)$url, 'assets/brand/') !== 0) {
        throw new Exception('Invalid file path');
    }

    // Convert URL to file path
    $filePath = __DIR__ . '/../../' . $url;
    $filePath = str_replace('/', DIRECTORY_SEPARATOR, $filePath);

    // Check if file exists and delete it
    if (file_exists($filePath) && is_file($filePath)) {
        if (!unlink($filePath)) {
            throw new Exception('Failed to delete file');
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'File deleted successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
