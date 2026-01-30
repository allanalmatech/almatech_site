<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/admin/includes/settings_lib.php';

$db = $GLOBALS['db'] ?? $mysqli ?? null;

// Get contact settings for WhatsApp
$whatsapp = setting_get($db, 'contact_whatsapp', '');

$maint = [
  'enabled' => true,
  'message' => 'We are updating the website. Please check back soon.',
  'custom_html' => '',
  'custom_css' => '',
  'custom_js' => '',
  'image_path' => '',
  'image_mode' => 'cover',
  'template_id' => null
];

$template = null;

if ($db instanceof mysqli) {
  $maint = array_merge($maint, setting_get_json($db, 'maintenance_settings', $maint));

  if (!empty($maint['template_id'])) {
    $stmt = $db->prepare("SELECT title, html, custom_css, custom_js FROM maintenance_templates WHERE id=? AND is_active=1 LIMIT 1");
    if ($stmt) {
      $tid = (int)$maint['template_id'];
      $stmt->bind_param("i", $tid);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($res && $res->num_rows > 0) {
        $template = $res->fetch_assoc();
      }
      $stmt->close();
    }
  }
}

$imagePath = !empty($maint['image_path']) ? $maint['image_path'] : '';
$mode = (string)($maint['image_mode'] ?? 'cover');

$fit = 'cover';
$pos = 'center';
if ($mode === 'contain') $fit = 'contain';
if ($mode === 'center-crop') { $fit = 'cover'; $pos = 'center'; }

$tplHtml = $template['html'] ?? '';
$tplCss  = $template['custom_css'] ?? '';
$tplJs   = $template['custom_js'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>We’ll be back soon</title>
  <style>
    :root { --bg:#0b1220; --fg:#ffffff; --muted:#b7c0d1; --card: rgba(255,255,255,.06); }
    body { margin:0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; background: var(--bg); color: var(--fg); }
    .wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
    .card { width:min(1000px, 100%); background: var(--card); border:1px solid rgba(255,255,255,.08); border-radius:18px; overflow:hidden; }
    .grid { display:grid; grid-template-columns: 1.1fr .9fr; }
    @media (max-width: 900px){ .grid{ grid-template-columns: 1fr; } }
    .left { padding:28px; }
    .right { background: rgba(0,0,0,.2); display:flex; align-items:center; justify-content:center; }
    .msg { color: var(--muted); line-height: 1.6; }
    .img { width:100%; height:100%; min-height:280px; }
    .img img { width:100%; height:100%; object-fit: <?= h($fit) ?>; object-position: <?= h($pos) ?>; display:block; }
    .pill { display:inline-block; padding:6px 10px; border-radius:999px; border:1px solid rgba(255,255,255,.15); color: var(--muted); font-size: 12px; }
    .btn { display:inline-block; margin-top: 14px; padding:12px 20px; border-radius:12px; border:2px solid var(--fg); color:var(--fg); text-decoration:none; cursor: pointer; font-weight: 600; background: transparent; transition: all 0.3s ease; }
    .btn:hover { background: var(--fg); color: var(--bg); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(255,255,255,0.2); }
    
    /* Modal Styles */
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; align-items: center; justify-content: center; }
    .modal.show { display: flex; }
    .modal-content { background: var(--bg); border-radius: 12px; padding: 30px; max-width: 500px; width: 90%; border: 1px solid rgba(255,255,255,0.1); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .modal-title { font-size: 20px; font-weight: 600; color: var(--fg); }
    .close-btn { background: none; border: none; color: var(--muted); font-size: 24px; cursor: pointer; padding: 0; }
    .close-btn:hover { color: var(--fg); }
    .form-group { margin-bottom: 20px; }
    .form-label { display: block; margin-bottom: 8px; color: var(--fg); font-weight: 500; }
    .form-control { width: 100%; padding: 12px; border: 1px solid rgba(255,255,255,0.2); border-radius: 8px; background: rgba(255,255,255,0.05); color: var(--fg); font-size: 14px; }
    .form-control:focus { outline: none; border-color: #ff7a18; box-shadow: 0 0 0 2px rgba(255,122,24,0.2); }
    textarea.form-control { resize: vertical; min-height: 100px; }
    .modal-footer { display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; }
    .btn-secondary { background: rgba(255,255,255,0.15); color: var(--fg); border: 1px solid rgba(255,255,255,0.25); font-weight: 500; }
    .btn-secondary:hover { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border-color: #dc3545; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(220,53,69,0.3); }
    .btn-whatsapp { background: linear-gradient(135deg, #25D366 0%, #128C7E 100%); color: white; border: 2px solid #25D366; font-weight: 600; }
    .btn-whatsapp:hover { background: linear-gradient(135deg, #128C7E 0%, #0F5132 100%); border-color: #128C7E; color: white; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(37,211,102,0.3); }
    /* Template/Custom CSS */
    <?= $tplCss ?>
    <?= (string)($maint['custom_css'] ?? '') ?>
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="grid">
        <div class="left">
          <span class="pill">Under Construction</span>
          <h1 style="margin:14px 0 10px;">Something nice is being prepared for you in the background 😊</h1>

          <p class="msg">
            <?= h((string)($maint['message'] ?? '')) ?>
          </p>

          <?php if (!empty($tplHtml)): ?>
            <div style="margin-top:16px;">
              <?= $tplHtml ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($maint['custom_html'])): ?>
            <div style="margin-top:16px;">
              <?= (string)$maint['custom_html'] ?>
            </div>
          <?php endif; ?>

          <button class="btn" onclick="openContactModal()">
            <i>💬</i> Contact Us
          </button>
        </div>

        <div class="right">
          <div class="img">
            <?php if ($imagePath): ?>
              <img src="<?= h($imagePath) ?>" alt="">
            <?php else: ?>
              <div style="height:100%;display:flex;align-items:center;justify-content:center;color:var(--muted);">
                No image set
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Contact Modal -->
  <div class="modal" id="contactModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title">Send us a message</h3>
        <button class="close-btn" onclick="closeContactModal()">&times;</button>
      </div>
      <form id="contactForm">
        <div class="form-group">
          <label class="form-label">Your Name</label>
          <input type="text" class="form-control" id="contactName" required>
        </div>
        <div class="form-group">
          <label class="form-label">Your Message</label>
          <textarea class="form-control" id="contactMessage" required placeholder="Type your message here..."></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="closeContactModal()">Cancel</button>
          <button type="submit" class="btn btn-whatsapp">Send via WhatsApp</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openContactModal() {
      document.getElementById('contactModal').classList.add('show');
    }

    function closeContactModal() {
      document.getElementById('contactModal').classList.remove('show');
      document.getElementById('contactForm').reset();
    }

    document.getElementById('contactForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const name = document.getElementById('contactName').value;
      const message = document.getElementById('contactMessage').value;
      const whatsapp = '<?= h($whatsapp) ?>';
      
      if (!whatsapp) {
        alert('WhatsApp number not configured. Please try again later.');
        return;
      }
      
      const whatsappMessage = `Hello! I'm ${name}. ${message}`;
      const whatsappUrl = `https://wa.me/${whatsapp}?text=${encodeURIComponent(whatsappMessage)}`;
      
      window.open(whatsappUrl, '_blank');
      closeContactModal();
    });

    // Close modal when clicking outside
    document.getElementById('contactModal').addEventListener('click', function(e) {
      if (e.target === this) {
        closeContactModal();
      }
    });

    <?= $tplJs ?>
    <?= (string)($maint['custom_js'] ?? '') ?>
  </script>
</body>
</html>
