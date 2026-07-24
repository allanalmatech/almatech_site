<?php
declare(strict_types=1);

if (!function_exists('env_load')) {
  function env_load($path = null) {
    static $loaded = [];

    if ($path === null) {
      $path = dirname(__DIR__) . '/.env';
    }

    $key = realpath($path) ?: $path;
    if (isset($loaded[$key])) {
      return;
    }
    $loaded[$key] = true;

    if (!is_file($path) || !is_readable($path)) {
      return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
      return;
    }

    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '' || strpos($line, '#') === 0) {
        continue;
      }

      if (strpos($line, 'export ') === 0) {
        $line = trim(substr($line, 7));
      }

      $pos = strpos($line, '=');
      if ($pos === false) {
        continue;
      }

      $name = trim(substr($line, 0, $pos));
      if ($name === '' || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name) !== 1) {
        continue;
      }

      $value = trim(substr($line, $pos + 1));
      if ($value !== '') {
        $quote = $value[0];
        if (($quote === '"' || $quote === "'") && substr($value, -1) === $quote) {
          $value = substr($value, 1, -1);
          if ($quote === '"') {
            $value = strtr($value, [
              '\\n' => "\n",
              '\\r' => "\r",
              '\\t' => "\t",
              '\\"' => '"',
              '\\\\' => '\\',
            ]);
          }
        } else {
          $commentPos = strpos($value, ' #');
          if ($commentPos !== false) {
            $value = rtrim(substr($value, 0, $commentPos));
          }
        }
      }

      if (getenv($name) === false) {
        putenv($name . '=' . $value);
      }
      if (!isset($_ENV[$name])) {
        $_ENV[$name] = $value;
      }
      if (!isset($_SERVER[$name])) {
        $_SERVER[$name] = $value;
      }
    }
  }
}

if (!function_exists('env_value')) {
  function env_value($key, $default = '') {
    env_load();

    $value = getenv($key);
    if ($value !== false) {
      return $value;
    }
    if (isset($_ENV[$key])) {
      return $_ENV[$key];
    }
    if (isset($_SERVER[$key])) {
      return $_SERVER[$key];
    }

    return $default;
  }
}

env_load();
