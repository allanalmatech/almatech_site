<?php
declare(strict_types=1);

/**
 * Deployment diagnostic for the Alma Tech site.
 *
 * Run from the CLI on the server:
 *   php check_deploy.php
 *
 * It never prints DB_PASS. Safe to run on shared hosting.
 */

$root = __DIR__;

require_once $root . '/includes/env.php';

function line(string $label, string $value): void
{
    printf("  %-34s %s\n", $label . ':', $value);
}

echo "=== Runtime ===\n";
line('PHP version', PHP_VERSION);
line('PHP SAPI', PHP_SAPI);
line('mysqli loaded', extension_loaded('mysqli') ? 'yes' : 'NO');
line('pdo_mysql loaded', extension_loaded('pdo_mysql') ? 'yes' : 'NO');

echo "\n=== .env discovery ===\n";
$envPath = $root . '/.env';
line('expected path', $envPath);
line('exists', is_file($envPath) ? 'yes' : 'NO');
line('readable', is_readable($envPath) ? 'yes' : 'NO');
if (is_file($envPath)) {
    line('size', (string)filesize($envPath) . ' bytes');
    line('modified', gmdate('Y-m-d H:i:s', (int)filemtime($envPath)) . ' UTC');
    $perms = @fileperms($envPath);
    line('permissions', $perms ? substr(sprintf('%o', $perms), -4) : 'unknown');
}

echo "\n=== Values PHP actually resolves ===\n";
line('DB_HOST', (string)env_value('DB_HOST', '<<EMPTY>>'));
line('DB_PORT', (string)env_value('DB_PORT', '<<EMPTY>>'));
line('DB_NAME', (string)env_value('DB_NAME', '<<EMPTY>>'));
line('DB_USER', (string)env_value('DB_USER', '<<EMPTY>>'));
line('DB_PASS', env_value('DB_PASS', '') === '' ? '<<EMPTY>>' : '<<set, hidden>>');

echo "\n=== Connection attempts ===\n";
$host  = (string)env_value('DB_HOST', 'localhost');
$port  = (int)env_value('DB_PORT', '3306');
$name  = (string)env_value('DB_NAME', '');
$user  = (string)env_value('DB_USER', '');
$pass  = (string)env_value('DB_PASS', '');

// Try the configured host, then fall back to 127.0.0.1 for socket problems.
$candidates = [$host];
if ($host === 'localhost') {
    $candidates[] = '127.0.0.1';
}

foreach (array_unique($candidates) as $candidate) {
    echo "  -- host: $candidate\n";

    try {
        $m = @new mysqli($candidate, $user, $pass, $name, $port);
        if ($m->connect_errno) {
            line('  mysqli', 'FAILED ' . $m->connect_errno . ': ' . $m->connect_error);
        } else {
            line('  mysqli', 'OK (server ' . $m->server_info . ')');
            $m->close();
        }
    } catch (Throwable $e) {
        line('  mysqli', 'THREW ' . get_class($e) . ': ' . $e->getMessage());
    }

    try {
        $pdo = new PDO(
            "mysql:host={$candidate};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        line('  PDO', 'OK');
    } catch (Throwable $e) {
        line('  PDO', 'FAILED: ' . $e->getMessage());
    }
}

echo "\n=== BASE_URL detection ===\n";
line('DOCUMENT_ROOT', (string)($_SERVER['DOCUMENT_ROOT'] ?? '<<not set>>'));
line('project dir', $root);
line('BASE_URL env', (string)env_value('BASE_URL', '<<EMPTY - will auto-detect>>'));

echo "\nDone.\n";