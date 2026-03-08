<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect_to(admin_url('dashboard.php'));
}

redirect_to(admin_url('login.php'));
