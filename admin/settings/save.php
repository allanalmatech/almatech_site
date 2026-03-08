<?php
// admin/settings/save.php
declare(strict_types=1);

require_once __DIR__ . '/../includes/settings_lib.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';

csrf_init();

function is_ajax(): bool {
  return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function json_fail(int $code, string $msg): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['success' => false, 'error' => $msg]);
  exit;
}

function json_ok(string $msg = 'Saved'): void {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['success' => true, 'message' => $msg]);
  exit;
}

$db = $GLOBALS['db'] ?? ($mysqli ?? null);
if (!($db instanceof mysqli)) {
  if (is_ajax()) json_fail(500, 'DB not available');
  http_response_code(500);
  exit('DB not available');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  if (is_ajax()) json_fail(405, 'Method not allowed');
  http_response_code(405);
  exit('Method not allowed');
}

try {
  csrf_verify();
} catch (Exception $e) {
  if (is_ajax()) json_fail(403, 'Security check failed');
  http_response_code(403);
  exit('Security check failed');
}

/**
 * -------------------------
 * GLOBAL SITE SETTINGS
 * -------------------------
 */
setting_set($db, 'company_name', trim((string)($_POST['company_name'] ?? '')));
setting_set($db, 'company_motto', trim((string)($_POST['company_motto'] ?? '')));
setting_set($db, 'company_address', trim((string)($_POST['company_address'] ?? '')));
setting_set($db, 'company_working_hours', trim((string)($_POST['company_working_hours'] ?? '')));

setting_set($db, 'contact_email', trim((string)($_POST['contact_email'] ?? '')));
setting_set($db, 'contact_phone', trim((string)($_POST['contact_phone'] ?? '')));
setting_set($db, 'contact_whatsapp', trim((string)($_POST['contact_whatsapp'] ?? '')));
setting_set($db, 'contact_map_embed', trim((string)($_POST['contact_map_embed'] ?? '')));
setting_set($db, 'recaptcha_site_key', trim((string)($_POST['recaptcha_site_key'] ?? '')));
setting_set($db, 'recaptcha_secret_key', trim((string)($_POST['recaptcha_secret_key'] ?? '')));

// Handle file uploads for brand assets
$brand_logo = trim((string)($_POST['brand_logo'] ?? ''));
$brand_favicon = trim((string)($_POST['brand_favicon'] ?? ''));

// Process logo upload
if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
  $uploadDir = __DIR__ . '/../../assets/brand/';
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
  }
  
  $file = $_FILES['logo_file'];
  $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
  
  if (in_array($file['type'], $allowedTypes) && $file['size'] <= 5 * 1024 * 1024) {
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'logo_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $uploadPath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
      $brand_logo = 'assets/brand/' . $filename;
    }
  }
}

// Process favicon upload
if (isset($_FILES['favicon_file']) && $_FILES['favicon_file']['error'] === UPLOAD_ERR_OK) {
  $uploadDir = __DIR__ . '/../../assets/brand/';
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
  }
  
  $file = $_FILES['favicon_file'];
  $allowedTypes = ['image/x-icon', 'image/png', 'image/jpeg', 'image/gif', 'image/vnd.microsoft.icon'];
  
  if (in_array($file['type'], $allowedTypes) && $file['size'] <= 2 * 1024 * 1024) {
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'favicon_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $uploadPath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
      $brand_favicon = 'assets/brand/' . $filename;
    }
  }
}

setting_set($db, 'brand_logo', $brand_logo);
setting_set($db, 'brand_favicon', $brand_favicon);
setting_set($db, 'brand_primary_color', trim((string)($_POST['brand_primary_color'] ?? '#ff7a18')));
setting_set($db, 'brand_secondary_color', trim((string)($_POST['brand_secondary_color'] ?? '#0b1220')));
setting_set($db, 'brand_accent_color', trim((string)($_POST['brand_accent_color'] ?? '#f3f4f6')));

setting_set($db, 'footer_note', trim((string)($_POST['footer_note'] ?? '')));

/**
 * -------------------------
 * PAGE COVER IMAGES
 * -------------------------
 */
function save_page_cover_image(mysqli $db, string $settingKey, string $fileKey, string $removeKey): void {
  $current = trim((string)setting_get($db, $settingKey, ''));
  $remove = !empty($_POST[$removeKey]);
  $next = $remove ? '' : $current;

  if (isset($_FILES[$fileKey]) && is_array($_FILES[$fileKey])) {
    $err = (int)($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_OK && is_uploaded_file((string)$_FILES[$fileKey]['tmp_name'])) {
      $tmp = (string)$_FILES[$fileKey]['tmp_name'];
      $size = (int)($_FILES[$fileKey]['size'] ?? 0);
      if ($size <= 4 * 1024 * 1024) {
        $mime = '';
        if (function_exists('finfo_open')) {
          $finfo = finfo_open(FILEINFO_MIME_TYPE);
          $mime = $finfo ? (string)finfo_file($finfo, $tmp) : '';
          if ($finfo) finfo_close($finfo);
        }

        $allowed = [
          'image/jpeg' => 'jpg',
          'image/jpg'  => 'jpg',
          'image/pjpeg'=> 'jpg',
          'image/png'  => 'png',
          'image/webp' => 'webp',
        ];

        if (isset($allowed[$mime])) {
          $uploadDir = __DIR__ . '/../../uploads/page_covers/';
          $uploadDir = str_replace('/', DIRECTORY_SEPARATOR, $uploadDir);
          if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
          }

          $name = $settingKey . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
          $dest = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
          if (move_uploaded_file($tmp, $dest)) {
            $next = 'uploads/page_covers/' . $name;
          }
        }
      }
    }
  }

  if ($current !== '' && $current !== $next && strpos($current, 'uploads/page_covers/') === 0) {
    $old = __DIR__ . '/../../' . str_replace('/', DIRECTORY_SEPARATOR, $current);
    if (is_file($old)) {
      @unlink($old);
    }
  }

  setting_set($db, $settingKey, $next);
}

save_page_cover_image($db, 'about_cover_image', 'about_cover_image_file', 'remove_about_cover_image');
save_page_cover_image($db, 'services_cover_image', 'services_cover_image_file', 'remove_services_cover_image');
save_page_cover_image($db, 'projects_cover_image', 'projects_cover_image_file', 'remove_projects_cover_image');
save_page_cover_image($db, 'blog_cover_image', 'blog_cover_image_file', 'remove_blog_cover_image');
save_page_cover_image($db, 'contact_cover_image', 'contact_cover_image_file', 'remove_contact_cover_image');

/**
 * -------------------------
 * SOCIAL LINKS (JSON)
 * -------------------------
 */
$social = $_POST['social'] ?? [];
if (!is_array($social)) $social = [];

$cleanSocial = [];
foreach (['facebook','instagram','twitter','linkedin','youtube','tiktok'] as $k) {
  $cleanSocial[$k] = trim((string)($social[$k] ?? ''));
}
setting_set_json($db, 'social_links', $cleanSocial);

/**
 * -------------------------
 * NAV VISIBILITY + ORDER (JSON)
 * -------------------------
 */
$visible = $_POST['visible_links'] ?? [];
if (!is_array($visible)) $visible = [];

$visible_norm = [];
foreach ($visible as $k => $v) {
  $visible_norm[(string)$k] = true; // checkbox present => true
}
setting_set_json($db, 'visible_links', $visible_norm);

$orderJson = (string)($_POST['nav_order_json'] ?? '[]');
$order = json_decode($orderJson, true);
if (!is_array($order)) $order = [];
setting_set_json($db, 'nav_order', array_values($order));

/**
 * -------------------------
 * MAINTENANCE SETTINGS (JSON) + IMAGE UPLOAD
 * -------------------------
 */
$maint = $_POST['maint'] ?? [];
if (!is_array($maint)) $maint = [];

$current = setting_get_json($db, 'maintenance_settings', []);

$enabled     = !empty($maint['enabled']);
$message     = trim((string)($maint['message'] ?? ''));
$custom_html = (string)($maint['custom_html'] ?? '');
$custom_css  = (string)($maint['custom_css'] ?? '');
$custom_js   = (string)($maint['custom_js'] ?? '');
$image_mode  = trim((string)($maint['image_mode'] ?? 'cover'));
$template_id = ($maint['template_id'] ?? '') === '' ? null : (int)$maint['template_id'];

$image_path = (string)($current['image_path'] ?? '');

// upload image if provided
if (isset($_FILES['maint_image']) && is_array($_FILES['maint_image'])) {
  $err = (int)($_FILES['maint_image']['error'] ?? UPLOAD_ERR_NO_FILE);

  if ($err === UPLOAD_ERR_OK && is_uploaded_file($_FILES['maint_image']['tmp_name'])) {
    $uploadDir = __DIR__ . '/../../uploads/maintenance/';
    $uploadDir = str_replace('/', DIRECTORY_SEPARATOR, $uploadDir);

    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0775, true);
    }

    $tmp = $_FILES['maint_image']['tmp_name'];

    $mime = '';
    if (function_exists('finfo_open')) {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mime = $finfo ? (string)finfo_file($finfo, $tmp) : '';
      if ($finfo) finfo_close($finfo);
    }

    $allowed = [
      'image/jpeg' => 'jpg',
      'image/jpg'  => 'jpg',
      'image/pjpeg'=> 'jpg',
      'image/png'  => 'png',
      'image/webp' => 'webp',
    ];

    if (isset($allowed[$mime])) {
      $ext  = $allowed[$mime];
      $name = 'maint_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
      $dest = rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;

      if (move_uploaded_file($tmp, $dest)) {
        $image_path = 'uploads/maintenance/' . $name; // public relative path
      }
    }
  }
}

$cleanMaint = [
  'enabled'     => $enabled,
  'message'     => $message,
  'custom_html' => $custom_html,
  'custom_css'  => $custom_css,
  'custom_js'   => $custom_js,
  'image_path'  => $image_path,
  'image_mode'  => in_array($image_mode, ['cover','contain','center-crop'], true) ? $image_mode : 'cover',
  'template_id' => $template_id,
];

setting_set_json($db, 'maintenance_settings', $cleanMaint);

/**
 * -------------------------
 * RESPONSE
 * -------------------------
 */
if (is_ajax()) {
  json_ok('Settings saved successfully');
}

header('Location: index.php?saved=1');
exit;
