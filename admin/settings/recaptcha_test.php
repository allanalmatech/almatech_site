<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_admin_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

$csrf = (string)($_POST['csrf'] ?? '');
if (!csrf_validate($csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Security check failed. Refresh and try again.']);
    exit;
}

$token = trim((string)($_POST['token'] ?? ''));
$action = trim((string)($_POST['action'] ?? 'admin_recaptcha_test'));
$secretKey = trim((string)($_POST['secret_key'] ?? ''));
$remoteIp = (string)($_SERVER['REMOTE_ADDR'] ?? '');

if ($token === '') {
    echo json_encode(['success' => false, 'error' => 'Missing token.']);
    exit;
}

if ($secretKey === '') {
    echo json_encode([
        'success' => false,
        'method' => 'siteverify',
        'message' => 'Missing Secret Key. For reCAPTCHA v2/v3 you must set Google reCAPTCHA Secret Key.',
    ]);
    exit;
}

$ok = recaptcha_verify_token($secretKey, $token, $remoteIp, $action, 0.3);

echo json_encode([
    'success' => $ok,
    'method' => 'siteverify',
    'message' => $ok
        ? 'Verification succeeded via standard siteverify (v2/v3).'
        : 'Verification failed via standard siteverify. Check site key, secret key, action, and allowed domains.',
]);
