<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

$db_host = (string)env_value('DB_HOST', 'localhost');
$db_user = (string)env_value('DB_USER', '');
$db_pass = (string)env_value('DB_PASS', '');
$db_name = (string)env_value('DB_NAME', '');
$db_port = (int)env_value('DB_PORT', '3306');

try {
  $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
} catch (Throwable $e) {
  $mysqli = null;
}

if (!$mysqli instanceof mysqli || $mysqli->connect_errno) {
  $GLOBALS['db'] = null;
} else {
  $mysqli->set_charset("utf8mb4");
  $GLOBALS['db'] = $mysqli;
}
