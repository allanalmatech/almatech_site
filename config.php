<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/env.php';

// Base URL configuration
if (!defined('BASE_URL')) {
    define('BASE_URL', rtrim((string)env_value('BASE_URL', '/'), '/') . '/');
}
if (!defined('ADMIN_URL')) {
    define('ADMIN_URL', rtrim((string)env_value('ADMIN_URL', rtrim(BASE_URL, '/') . '/admin/'), '/') . '/');
}

$GLOBALS['BASE_URL'] = BASE_URL;
$GLOBALS['ADMIN_URL'] = ADMIN_URL;
