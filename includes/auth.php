<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

function auth_uses_admins_table(): bool
{
    static $usesAdmins = null;

    if ($usesAdmins !== null) {
        return $usesAdmins;
    }

    try {
        $stmt = db()->query("SHOW TABLES LIKE 'admins'");
        $usesAdmins = (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        $usesAdmins = false;
    }

    return $usesAdmins;
}

function ensure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function is_logged_in(): bool
{
    ensure_session();
    return !empty($_SESSION['admin_id']) || !empty($_SESSION['admin']['id']);
}

function current_admin(): ?array
{
    ensure_session();
    if (!is_logged_in()) {
        return null;
    }

    $sessionAdminId = (int)($_SESSION['admin_id'] ?? $_SESSION['admin']['id'] ?? 0);
    if ($sessionAdminId <= 0) {
        return null;
    }

    if (auth_uses_admins_table()) {
        $stmt = db()->prepare('SELECT id, username, full_name, email, role FROM admins WHERE id = :id AND status = 1 LIMIT 1');
        $stmt->execute([':id' => $sessionAdminId]);
        $admin = $stmt->fetch();
    } else {
        $stmt = db()->prepare('SELECT id, name, email, role FROM admin_users WHERE id = :id AND is_active = 1 LIMIT 1');
        $stmt->execute([':id' => $sessionAdminId]);
        $row = $stmt->fetch();
        $admin = $row ? [
            'id' => (int)$row['id'],
            'username' => (string)$row['email'],
            'full_name' => (string)$row['name'],
            'email' => (string)$row['email'],
            'role' => (string)$row['role'],
        ] : null;
    }

    return $admin ?: null;
}

function require_admin_login(): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in to continue.');
        redirect_to(admin_url('login.php'));
    }
}

function admin_login_attempt(string $usernameOrEmail, string $password): bool
{
    $usernameOrEmail = trim($usernameOrEmail);
    if ($usernameOrEmail === '' || $password === '') {
        return false;
    }

    if (auth_uses_admins_table()) {
        $stmt = db()->prepare('SELECT * FROM admins WHERE status = 1 AND (username = :identity_username OR email = :identity_email) LIMIT 1');
        $stmt->execute([
            ':identity_username' => $usernameOrEmail,
            ':identity_email' => $usernameOrEmail,
        ]);
        $admin = $stmt->fetch();
    } else {
        $stmt = db()->prepare('SELECT * FROM admin_users WHERE is_active = 1 AND (email = :identity_email OR name = :identity_name) LIMIT 1');
        $stmt->execute([
            ':identity_email' => $usernameOrEmail,
            ':identity_name' => $usernameOrEmail,
        ]);
        $admin = $stmt->fetch();
    }

    if (!$admin || !password_verify($password, (string)$admin['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$admin['id'];
    $_SESSION['admin'] = [
        'id' => (int)$admin['id'],
        'name' => (string)($admin['full_name'] ?? $admin['name'] ?? $admin['username'] ?? 'Admin'),
        'email' => (string)($admin['email'] ?? ''),
        'role' => (string)($admin['role'] ?? 'admin'),
    ];

    if (auth_uses_admins_table()) {
        $update = db()->prepare('UPDATE admins SET last_login = NOW() WHERE id = :id');
        $update->execute([':id' => (int)$admin['id']]);
    }

    return true;
}

function admin_logout(): void
{
    ensure_session();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool)$params['secure'],
            (bool)$params['httponly']
        );
    }
    session_destroy();
}
