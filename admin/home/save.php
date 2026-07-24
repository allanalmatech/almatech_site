<?php
declare(strict_types=1);

// admin/home/save.php - Dedicated home page settings save handler

require_once __DIR__ . '/../includes/settings_lib.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';

csrf_init();

$db = $GLOBALS['db'] ?? $mysqli ?? null;
if (!($db instanceof mysqli)) {
  $error = 'DB not available';
  if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
  }
  http_response_code(500);
  exit($error);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $error = 'Method not allowed';
  if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
  }
  http_response_code(405);
  exit($error);
}

try {
  csrf_verify();
} catch (Exception $e) {
  $error = 'Security check failed: ' . $e->getMessage();
  if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
  }
  http_response_code(403);
  exit($error);
}

// Load current home settings
$home = setting_get_json($db, 'home_settings', []);
if (!is_array($home)) $home = [];

// Get posted data
$posted = $_POST['home'] ?? [];
if (!is_array($posted)) $posted = [];

// ---------------- HERO ----------------
$hero = $home['hero'] ?? [];
$hero['badge_icon'] = trim((string)($posted['hero']['badge_icon'] ?? 'bi bi-lightning-charge-fill'));
$hero['badge_text'] = trim((string)($posted['hero']['badge_text'] ?? 'Fast, reliable ICT & digital solutions'));
$hero['title'] = trim((string)($posted['hero']['title'] ?? ''));
$hero['subtitle'] = trim((string)($posted['hero']['subtitle'] ?? ''));
$hero['btn1_text'] = trim((string)($posted['hero']['btn1_text'] ?? ''));
$hero['btn1_link'] = trim((string)($posted['hero']['btn1_link'] ?? ''));
$hero['btn2_text'] = trim((string)($posted['hero']['btn2_text'] ?? ''));
$hero['btn2_link'] = trim((string)($posted['hero']['btn2_link'] ?? ''));

// ---------------- SLIDER ----------------
$slider = $home['slider'] ?? [];
$slider['enabled'] = !empty($posted['slider']['enabled']);
$slider['interval'] = (int)($posted['slider']['interval'] ?? 5000);
if ($slider['interval'] < 1000) $slider['interval'] = 1000;
if ($slider['interval'] > 20000) $slider['interval'] = 20000;

$slider['transition'] = (string)($posted['slider']['transition'] ?? 'fade');
if (!in_array($slider['transition'], ['fade','slide'], true)) $slider['transition'] = 'fade';

$slider['min_height'] = (int)($posted['slider']['min_height'] ?? 420);
if ($slider['min_height'] < 260) $slider['min_height'] = 260;
if ($slider['min_height'] > 900) $slider['min_height'] = 900;

$slider['overlay_enabled'] = !empty($posted['slider']['overlay_enabled']);
$slider['overlay_opacity'] = (float)($posted['slider']['overlay_opacity'] ?? 0.45);
if ($slider['overlay_opacity'] < 0) $slider['overlay_opacity'] = 0;
if ($slider['overlay_opacity'] > 1) $slider['overlay_opacity'] = 1;

$slider['overlay_gradient'] = (string)($posted['slider']['overlay_gradient'] ?? 'linear-gradient(120deg, rgba(0,0,0,.65), rgba(0,0,0,.15))');
$slider['show_indicators'] = !empty($posted['slider']['show_indicators']);
$slider['show_arrows'] = !empty($posted['slider']['show_arrows']);

// Handle slides and file uploads
$slides = $_POST['slides'] ?? [];
if (!is_array($slides)) $slides = [];

$uploadDir = __DIR__ . '/../../uploads/slider/';
$uploadDir = str_replace('/', DIRECTORY_SEPARATOR, $uploadDir);
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0775, true);
}

$cleanSlides = [];
foreach ($slides as $index => $row) {
  if (!is_array($row)) continue;

  // Handle file upload for this slide
  $imagePath = trim((string)($row['image'] ?? ''));
  
  // Check if there's a file upload for this slide
  $fileKey = "slide_image_{$index}";
  if (isset($_FILES[$fileKey]) && is_array($_FILES[$fileKey])) {
    $err = (int)($_FILES[$fileKey]['error'] ?? UPLOAD_ERR_NO_FILE);
    
    if ($err === UPLOAD_ERR_OK && is_uploaded_file($_FILES[$fileKey]['tmp_name'])) {
      $tmp = $_FILES[$fileKey]['tmp_name'];
      $name = $_FILES[$fileKey]['name'];
      $mime = mime_content_type($tmp);
      
      $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp'
      ];
      
      if (isset($allowed[$mime])) {
        $ext = $allowed[$mime];
        $newName = 'slide_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $uploadDir . $newName;
        
        if (move_uploaded_file($tmp, $dest)) {
          $imagePath = 'uploads/slider/' . $newName;
        }
      }
    }
  }

  $cleanSlides[] = [
    'is_active' => !empty($row['is_active']),
    'sort_order' => (int)($row['sort_order'] ?? 0),
    'image' => $imagePath,
    'alt' => trim((string)($row['alt'] ?? '')),
    'caption_title' => trim((string)($row['caption_title'] ?? '')),
    'caption_text' => trim((string)($row['caption_text'] ?? '')),
    'btn1_text' => trim((string)($row['btn1_text'] ?? '')),
    'btn1_link' => trim((string)($row['btn1_link'] ?? '')),
    'btn2_text' => trim((string)($row['btn2_text'] ?? '')),
    'btn2_link' => trim((string)($row['btn2_link'] ?? '')),
    'caption_align' => in_array(($row['caption_align'] ?? 'left'), ['left','center','right'], true) ? (string)$row['caption_align'] : 'left',
  ];
}

usort($cleanSlides, function ($a, $b) { return $a['sort_order'] <=> $b['sort_order']; });
$slider['slides'] = $cleanSlides;

// ---------------- QUICK REQUEST ----------------
$qr = $home['quick_request'] ?? [];
$qr['enabled'] = !empty($posted['quick_request']['enabled']);
$qr['title'] = trim((string)($posted['quick_request']['title'] ?? ''));
$qr['subtitle'] = trim((string)($posted['quick_request']['subtitle'] ?? ''));
$qr['btn_text'] = trim((string)($posted['quick_request']['btn_text'] ?? ''));
$qr['btn_link'] = trim((string)($posted['quick_request']['btn_link'] ?? ''));

// Services list (textarea -> array)
$svRaw = (string)($_POST['quick_services'] ?? '');
$svLines = array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $svRaw)));
$qr['services'] = array_values($svLines);

// ---------------- SERVICES PREVIEW ----------------
$sp = $home['services_preview'] ?? [];
$sp['enabled'] = !empty($posted['services_preview']['enabled']);
$sp['title'] = trim((string)($posted['services_preview']['title'] ?? ''));
$sp['subtitle'] = trim((string)($posted['services_preview']['subtitle'] ?? ''));
$sp['view_all_text'] = trim((string)($posted['services_preview']['view_all_text'] ?? ''));
$sp['view_all_link'] = trim((string)($posted['services_preview']['view_all_link'] ?? ''));

// Service cards (4)
$cards = $_POST['cards'] ?? [];
if (!is_array($cards)) $cards = [];
$cleanCards = [];
foreach ($cards as $c) {
  if (!is_array($c)) continue;
  $cleanCards[] = [
    'icon' => trim((string)($c['icon'] ?? 'bi-star')),
    'title' => trim((string)($c['title'] ?? '')),
    'desc' => trim((string)($c['desc'] ?? '')),
    'btn_text' => trim((string)($c['btn_text'] ?? '')),
    'btn_link' => trim((string)($c['btn_link'] ?? '')),
  ];
}
$sp['cards'] = $cleanCards;

// ---------------- STATS ----------------
$stats = $_POST['stats'] ?? [];
if (!is_array($stats)) $stats = [];
$cleanStats = [];
foreach ($stats as $row) {
  if (!is_array($row)) continue;
  $val = trim((string)($row['value'] ?? ''));
  $lab = trim((string)($row['label'] ?? ''));
  if ($val === '' && $lab === '') continue;
  $cleanStats[] = [
    'value' => $val,
    'label' => $lab,
    'icon' => trim((string)($row['icon'] ?? 'bi-graph-up')),
  ];
}

// ---------------- CTA ----------------
$cta = $home['cta'] ?? [];
$cta['enabled'] = !empty($posted['cta']['enabled']);
$cta['title'] = trim((string)($posted['cta']['title'] ?? ''));
$cta['subtitle'] = trim((string)($posted['cta']['subtitle'] ?? ''));
$cta['btn_text'] = trim((string)($posted['cta']['btn_text'] ?? ''));
$cta['btn_link'] = trim((string)($posted['cta']['btn_link'] ?? ''));

// ---------------- FINAL SAVE ----------------
$cleanHome = [
  'hero' => $hero,
  'slider' => $slider,
  'quick_request' => $qr,
  'services_preview' => $sp,
  'stats' => $cleanStats,
  'cta' => $cta,
];

setting_set_json($db, 'home_settings', $cleanHome);

// Return JSON response for AJAX requests
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
  header('Content-Type: application/json');
  echo json_encode(['success' => true, 'message' => 'Home page settings saved successfully']);
  exit;
}

// Redirect for regular form submissions
header('Location: index.php?saved=1');
exit;
