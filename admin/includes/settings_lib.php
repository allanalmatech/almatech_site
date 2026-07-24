<?php
// admin/includes/settings_lib.php
declare(strict_types=1);

function setting_get(mysqli $db, string $key, string $default = null) {
  $stmt = $db->prepare("SELECT `value` FROM settings WHERE `key` = ? LIMIT 1");
  if (!$stmt) return $default;
  $stmt->bind_param("s", $key);
  $stmt->execute();
  $stmt->bind_result($val);
  $out = null;
  if ($stmt->fetch()) $out = (string)$val;
  $stmt->close();
  return $out !== null ? $out : $default;
}

function setting_set(mysqli $db, string $key, $value): bool {
  $stmt = $db->prepare("
    INSERT INTO settings (`key`, `value`, updated_at)
    VALUES (?, ?, NOW())
    ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), updated_at = NOW()
  ");
  if (!$stmt) return false;
  $v = $value ?? '';
  $stmt->bind_param("ss", $key, $v);
  $ok = $stmt->execute();
  $stmt->close();
  return $ok;
}

function setting_get_json(mysqli $db, string $key, array $default = []): array {
  $raw = setting_get($db, $key, null);
  if (!$raw) return $default;
  $decoded = json_decode($raw, true);
  return is_array($decoded) ? $decoded : $default;
}

function setting_set_json(mysqli $db, string $key, array $value): bool {
  return setting_set($db, $key, json_encode($value, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
}
