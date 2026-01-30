<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$admin = $_SESSION['admin'] ?? ['name' => 'Admin', 'role' => 'admin'];

if (!isset($page_title)) $page_title = "Admin | Alma Tech Consults";
if (!isset($active_admin)) $active_admin = "dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title><?= h($page_title) ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/almatech_site/assets/css/main.css">
  <link rel="stylesheet" href="/almatech_site/assets/css/admin.css">
  <link rel="stylesheet" href="/almatech_site/assets/css/admin_sidebar.css">
</head>
<body class="admin-shell">

<!-- Mobile topbar -->
<nav class="navbar navbar-light bg-white border-bottom d-lg-none">
  <div class="container-fluid">
    <button class="btn btn-outline-orange" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar">
      <i class="bi bi-list"></i>
    </button>

    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= ADMIN_URL ?>dashboard.php">
      <span class="brand-dot"></span> Alma Admin
    </a>

    <a class="btn btn-outline-orange" href="<?= ADMIN_URL ?>logout.php" title="Logout">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
</nav>

<div class="admin-wrap">
