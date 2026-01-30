<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';

$name = "Admin";
$email = "admin@almatechconsults.com";
$password = "Admin12345"; // change immediately
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $mysqli->prepare("INSERT INTO admin_users (name,email,password_hash,role,is_active) VALUES (?,?,?,?,1)");
$stmt->bind_param("ssss", $name, $email, $hash, $role);
$role = "admin";
$stmt->execute();

echo "Admin created. Email: {$email} Password: {$password}. DELETE seed_admin.php now.";
