<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';

$settingsLib = __DIR__ . '/includes/settings_lib.php';
if (is_file($settingsLib)) {
    require_once $settingsLib;
} else {
    $adminSettingsLib = __DIR__ . '/admin/includes/settings_lib.php';
    if (is_file($adminSettingsLib)) {
        require_once $adminSettingsLib;
    }
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$db = $GLOBALS['db'] ?? ($mysqli ?? null);
$secret = '';

if ($db instanceof mysqli && function_exists('setting_get')) {
    $secret = trim((string)setting_get($db, 'recaptcha_secret_key', ''));
}

$token = trim((string)($_POST['token'] ?? ''));
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');

if ($secret === '' || $token === '') {
    echo json_encode(['success' => false]);
    exit;
}

$action = trim((string)($_POST['action'] ?? 'quick_request'));
$ok = recaptcha_verify_token($secret, $token, $ip, $action);
echo json_encode(['success' => $ok]);
