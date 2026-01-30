<?php
declare(strict_types=1);

function ensure_dir(string $dir): void {
  if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
  }
}

function safe_slug(string $s): string {
  $s = trim(mb_strtolower($s));
  $s = preg_replace('/[^\p{L}\p{N}]+/u', '-', $s) ?? '';
  $s = trim($s, '-');
  return $s ?: 'item';
}

function unique_slug(mysqli $db, string $table, string $slug, ?int $ignoreId = null): string {
  $base = $slug;
  $i = 0;

  while (true) {
    $try = $i === 0 ? $base : $base . '-' . $i;

    if ($ignoreId) {
      $sql = "SELECT id FROM {$table} WHERE slug=? AND id<>? LIMIT 1";
      $st = $db->prepare($sql);
      $st->bind_param("si", $try, $ignoreId);
    } else {
      $sql = "SELECT id FROM {$table} WHERE slug=? LIMIT 1";
      $st = $db->prepare($sql);
      $st->bind_param("s", $try);
    }

    $st->execute();
    $res = $st->get_result();
    if ($res && $res->num_rows === 0) return $try;

    $i++;
    if ($i > 500) return $base . '-' . bin2hex(random_bytes(2));
  }
}

function upload_image_or_null(array $file, string $destDir, array $allowed = ['image/jpeg','image/png','image/webp'], int $maxBytes = 3000000): ?string {
  if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    return null;
  }
  if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    throw new RuntimeException("Upload failed.");
  }
  if (($file['size'] ?? 0) > $maxBytes) {
    throw new RuntimeException("Image too large. Max " . (int)($maxBytes/1000000) . "MB");
  }

  $tmp = (string)$file['tmp_name'];
  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime  = finfo_file($finfo, $tmp);
  finfo_close($finfo);

  if (!in_array($mime, $allowed, true)) {
    throw new RuntimeException("Invalid image type.");
  }

  $ext = match($mime) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    default => 'img'
  };

  ensure_dir($destDir);

  $name = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
  $path = rtrim($destDir, '/\\') . DIRECTORY_SEPARATOR . $name;

  if (!move_uploaded_file($tmp, $path)) {
    throw new RuntimeException("Could not save file.");
  }

  return $name; // store filename only
}

function delete_file_if_exists(string $dir, ?string $filename): void {
  if (!$filename) return;
  $p = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
  if (is_file($p)) @unlink($p);
}
