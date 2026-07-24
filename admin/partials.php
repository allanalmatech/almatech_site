<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

function admin_layout_start(string $title, string $activeMenu, array $admin = null)
{
    $flash = get_flash();
    ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f5f7fb; }
        .admin-shell { min-height: 100vh; }
        .admin-sidebar { width: 260px; background: #1f2937; color: #fff; }
        .admin-sidebar .nav-link { color: rgba(255,255,255,.84); border-radius: .5rem; margin-bottom: .25rem; }
        .admin-sidebar .nav-link.active, .admin-sidebar .nav-link:hover { background: #374151; color: #fff; }
        .admin-content { flex: 1; }
        .stat-card { border: 0; border-radius: .75rem; box-shadow: 0 6px 24px rgba(15,23,42,.06); }
        .table-card { border: 0; border-radius: .75rem; box-shadow: 0 6px 24px rgba(15,23,42,.06); }
    </style>
</head>
<body>
<div class="d-flex admin-shell">
    <aside class="admin-sidebar p-3">
        <div class="mb-4">
            <a href="<?= e(admin_url('dashboard.php')) ?>" class="text-white text-decoration-none fw-bold fs-5">Shop Admin</a>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?>" href="<?= e(admin_url('dashboard.php')) ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
            <a class="nav-link <?= $activeMenu === 'categories' ? 'active' : '' ?>" href="<?= e(admin_url('categories.php')) ?>"><i class="bi bi-tags me-2"></i>Categories</a>
            <a class="nav-link <?= $activeMenu === 'products' ? 'active' : '' ?>" href="<?= e(admin_url('products.php')) ?>"><i class="bi bi-box-seam me-2"></i>Products</a>
            <a class="nav-link <?= $activeMenu === 'settings' ? 'active' : '' ?>" href="<?= e(admin_url('settings.php')) ?>"><i class="bi bi-gear me-2"></i>Settings</a>
            <hr class="text-secondary">
            <a class="nav-link" href="<?= e(admin_url('logout.php')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
        </nav>
    </aside>

    <main class="admin-content p-4 p-lg-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h4 mb-0"><?= e($title) ?></h1>
            <div class="text-muted small">Signed in as <?= e($admin['full_name'] ?? $admin['username'] ?? 'Admin') ?></div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
<?php
}

function admin_layout_end()
{
    ?>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
}
