<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['admin']['id'])) {
  redirect("dashboard.php");
}

csrf_init();
$flash = flash_get();

$email = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_validate((string)($_POST['csrf_token'] ?? ''))) {
    $errors[] = "Security check failed. Refresh and try again.";
  }

  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Enter a valid email.";
  }
  if ($password === '') {
    $errors[] = "Enter your password.";
  }

  if (!$errors) {
    $stmt = $mysqli->prepare("SELECT id, name, email, password_hash, role, is_active FROM admin_users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$user || (int)$user['is_active'] !== 1) {
      $errors[] = "Invalid login details.";
    } else {
      if (!password_verify($password, (string)$user['password_hash'])) {
        $errors[] = "Invalid login details.";
      } else {
        session_regenerate_id(true);
        $_SESSION['admin'] = [
          'id' => (int)$user['id'],
          'name' => (string)$user['name'],
          'email' => (string)$user['email'],
          'role' => (string)$user['role'],
        ];
        flash_set("success", "Welcome back, " . $_SESSION['admin']['name'] . "!");
        redirect("dashboard.php");
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Admin Login | Alma Tech Consults</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-bg">

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-10 col-lg-6 col-xl-5">

      <div class="admin-card p-4 p-md-5">
        <div class="text-center mb-4">
          <div class="admin-logo mx-auto mb-2"><i class="bi bi-shield-lock"></i></div>
          <h1 class="h4 fw-bold mb-1">Admin Login</h1>
          <p class="text-muted mb-0">Sign in to manage the website.</p>
        </div>

        <?php if ($flash): ?>
          <div class="alert alert-<?= h($flash['type']) ?>"><?= h($flash['msg']) ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Fix the following:</div>
            <ul class="mb-0">
              <?php foreach ($errors as $e): ?>
                <li><?= h($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" class="row g-3" novalidate>
          <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

          <div class="col-12">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" value="<?= h($email) ?>" placeholder="admin@..." required>
          </div>

          <div class="col-12">
            <label class="form-label">Password</label>
            <div class="input-group">
              <input class="form-control" type="password" name="password" placeholder="••••••••" required>
              <span class="input-group-text"><i class="bi bi-key"></i></span>
            </div>
          </div>

          <div class="col-12">
            <button class="btn btn-orange btn-lg w-100">
              Sign In <i class="bi bi-arrow-right ms-1"></i>
            </button>
          </div>

          <div class="col-12 small text-muted text-center">
            Protected area • Alma Tech Consults
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

</body>
</html>
