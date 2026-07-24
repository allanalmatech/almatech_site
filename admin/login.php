<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

if (is_logged_in()) {
    redirect_to(admin_url('dashboard.php'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid_request();

    $identity = trim((string)($_POST['identity'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (admin_login_attempt($identity, $password)) {
        set_flash('success', 'Welcome back.');
        redirect_to(admin_url('dashboard.php'));
    }

    set_flash('danger', 'Invalid login credentials.');
    redirect_to(admin_url('login.php'));
}

$flash = get_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { min-height: 100vh; background: linear-gradient(135deg, #f8fafc, #dbeafe); }
        .login-card { max-width: 420px; width: 100%; border: 0; border-radius: 1rem; box-shadow: 0 10px 35px rgba(15,23,42,.14); }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3">
    <div class="card login-card p-4">
        <h1 class="h4 mb-3">Shop Admin Login</h1>
        <?php if ($flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Username or Email</label>
                <input type="text" name="identity" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Sign In</button>
        </form>
    </div>
</body>
</html>
