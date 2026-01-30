<?php
declare(strict_types=1);

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "almatech_site";

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
  $GLOBALS['db'] = null;
} else {
  $mysqli->set_charset("utf8mb4");
  $GLOBALS['db'] = $mysqli;
}
