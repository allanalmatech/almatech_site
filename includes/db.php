<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

$db_host = (string)env_value('DB_HOST', 'localhost');
$db_user = (string)env_value('DB_USER', '');
$db_pass = (string)env_value('DB_PASS', '');
$db_name = (string)env_value('DB_NAME', '');
$db_port = (int)env_value('DB_PORT', '3306');

$GLOBALS['db_error'] = null;

try {
  $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
  if ($mysqli->connect_errno) {
    $GLOBALS['db_error'] = 'MySQL error ' . $mysqli->connect_errno . ': ' . $mysqli->connect_error;
  }
} catch (Throwable $e) {
  $mysqli = null;
  $GLOBALS['db_error'] = $e->getMessage();
}

if (!$mysqli instanceof mysqli || $mysqli->connect_errno) {
  if ($GLOBALS['db_error'] === null) {
    $GLOBALS['db_error'] = 'Unknown database connection failure.';
  }
  if (!is_file(dirname(__DIR__) . '/.env')) {
    $GLOBALS['db_error'] .= ' No .env file found in the site root, so DB_* resolved to empty values.';
  }
  error_log('[db] connection failed - ' . $GLOBALS['db_error'] . ' [host=' . $db_host . ' db=' . $db_name . ' user=' . $db_user . ']');
  $GLOBALS['db'] = null;
} else {
  $mysqli->set_charset("utf8mb4");
  $GLOBALS['db'] = $mysqli;
}
