<?php
// admin/settings/analytics.php
declare(strict_types=1);

// Early authentication check before any HTML output
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_admin_login();

$page_title    = "Analytics Settings | Admin";
$page_heading  = "Analytics Settings";
$page_subtitle = "Configure Google Analytics 4 integration and reporting";
$active_admin  = "settings";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
require_once __DIR__ . '/../includes/settings_lib.php';
require_once __DIR__ . '/../../includes/db.php';

csrf_init();

$db = $GLOBALS['db'] ?? $mysqli ?? null;
if (!($db instanceof mysqli)) {
  http_response_code(500);
  exit('DB not available');
}

$csrf = csrf_token();

// Load current analytics settings
$ga4_property_id      = setting_get($db, 'ga4_property_id', '');
$ga4_service_json_path = setting_get($db, 'ga4_service_json_path', '');
$ga4_service_json     = setting_get($db, 'ga4_service_json', '');
$looker_embed_url     = setting_get($db, 'looker_embed_url', '');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  
  $ga4_property_id      = trim($_POST['ga4_property_id'] ?? '');
  $ga4_service_json_path = trim($_POST['ga4_service_json_path'] ?? '');
  $ga4_service_json     = trim($_POST['ga4_service_json'] ?? '');
  $looker_embed_url     = trim($_POST['looker_embed_url'] ?? '');
  
  // Validate GA4 property ID format
  if ($ga4_property_id && !preg_match('/^\d+$/', $ga4_property_id)) {
    $error = 'GA4 Property ID must be numeric (e.g., 123456789)';
  } else {
    // Save settings
    setting_set($db, 'ga4_property_id', $ga4_property_id);
    setting_set($db, 'ga4_service_json_path', $ga4_service_json_path);
    setting_set($db, 'ga4_service_json', $ga4_service_json);
    setting_set($db, 'looker_embed_url', $looker_embed_url);
    
    $success = 'Analytics settings saved successfully!';
  }
}

?>
<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <?php if (!empty($error)): ?>
    <div class="admin-card p-4 mb-3">
      <div class="alert alert-danger mb-0">
        <div class="fw-semibold mb-1">Error</div>
        <div class="small"><?= h($error) ?></div>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($success)): ?>
    <div class="admin-card p-4 mb-3">
      <div class="alert alert-success mb-0">
        <div class="fw-semibold mb-1">Success</div>
        <div class="small"><?= h($success) ?></div>
      </div>
    </div>
  <?php endif; ?>

  <form method="post" class="admin-card p-4 mb-3">
    <h4 class="mb-3">Google Analytics 4 Configuration</h4>
    
    <div class="row g-3">
      <div class="col-12">
        <label class="form-label fw-semibold">GA4 Property ID</label>
        <input type="text" name="ga4_property_id" value="<?= h($ga4_property_id) ?>" 
               class="form-control" placeholder="e.g., 123456789">
        <div class="form-text">Your Google Analytics 4 Property ID (numeric only)</div>
      </div>

      <div class="col-12">
        <label class="form-label fw-semibold">Service Account JSON Path</label>
        <input type="text" name="ga4_service_json_path" value="<?= h($ga4_service_json_path) ?>" 
               class="form-control" placeholder="e.g., /path/to/service-account.json">
        <div class="form-text">Server file path to your Google service account JSON file (recommended)</div>
      </div>

      <div class="col-12">
        <label class="form-label fw-semibold">Service Account JSON (Raw)</label>
        <textarea name="ga4_service_json" rows="8" class="form-control" 
                  placeholder="Paste your service account JSON content here..."><?= h($ga4_service_json) ?></textarea>
        <div class="form-text">Alternative to file path - paste the complete JSON content</div>
      </div>
    </div>
  </form>

  <form method="post" class="admin-card p-4 mb-3">
    <h4 class="mb-3">Looker Studio Integration</h4>
    
    <div class="row g-3">
      <div class="col-12">
        <label class="form-label fw-semibold">Looker Studio Embed URL</label>
        <input type="url" name="looker_embed_url" value="<?= h($looker_embed_url) ?>" 
               class="form-control" placeholder="https://lookerstudio.google.com/embed/...">
        <div class="form-text">Optional: Embed URL for Looker Studio dashboard</div>
      </div>
    </div>

    <div class="mt-4">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
      <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg me-1"></i> Save Settings
      </button>
      <a href="<?= ADMIN_URL ?>analytics/" class="btn btn-outline-secondary ms-2">
        <i class="bi bi-bar-chart me-1"></i> View Analytics Dashboard
      </a>
    </div>
  </form>

  <div class="admin-card p-4">
    <h4 class="mb-3">Setup Instructions</h4>
    
    <div class="row">
      <div class="col-md-6">
        <h6 class="fw-semibold mb-2">1. Create Google Service Account</h6>
        <ol class="small">
          <li>Go to <a href="https://console.cloud.google.com/" target="_blank">Google Cloud Console</a></li>
          <li>Create a new project or select existing one</li>
          <li>Enable "Google Analytics Data API"</li>
          <li>Create Service Account under "IAM & Admin" → "Service Accounts"</li>
          <li>Download JSON key file</li>
        </ol>
      </div>
      
      <div class="col-md-6">
        <h6 class="fw-semibold mb-2">2. Configure Google Analytics</h6>
        <ol class="small">
          <li>Go to <a href="https://analytics.google.com/" target="_blank">Google Analytics</a></li>
          <li>Select your GA4 property</li>
          <li>Go to "Admin" → "Property Access Management"</li>
          <li>Add your service account email with "Viewer" role</li>
          <li>Copy your Property ID (numbers only)</li>
        </ol>
      </div>
    </div>

    <div class="mt-3">
      <h6 class="fw-semibold mb-2">3. Upload Service Account File</h6>
      <p class="small mb-2">Upload your JSON key file to a secure location on your server and provide the file path above, or paste the JSON content directly.</p>
      <div class="alert alert-info small mb-0">
        <strong>Security Note:</strong> Keep your service account JSON file secure and never commit it to version control.
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
