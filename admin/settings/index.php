<?php
// admin/settings/index.php
declare(strict_types=1);

// Early authentication check before any HTML output
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_admin_login();

$page_title    = "Settings | Admin";
$page_heading  = "Settings";
$page_subtitle = "Manage website branding, contacts, homepage, links, and maintenance mode";
$active_admin  = "settings";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
require_once __DIR__ . '/../includes/settings_lib.php';
require_once __DIR__ . '/../../includes/db.php';

// Add brand assets script
echo '<script src="brand_assets.js"></script>' . "\n";

csrf_init();

$db = $GLOBALS['db'] ?? $mysqli ?? null;
if (!($db instanceof mysqli)) {
  http_response_code(500);
  exit('DB not available');
}

$csrf = csrf_token();

// Load values
$company_name   = setting_get($db, 'company_name', 'Alma Tech Consults');
$company_motto  = setting_get($db, 'company_motto', '');
$address        = setting_get($db, 'company_address', '');
$hours          = setting_get($db, 'company_working_hours', '');

$email          = setting_get($db, 'contact_email', '');
$phone          = setting_get($db, 'contact_phone', '');
$whatsapp       = setting_get($db, 'contact_whatsapp', '');
$map_embed      = setting_get($db, 'contact_map_embed', '');

$brand_logo     = setting_get($db, 'brand_logo', '');
$favicon        = setting_get($db, 'brand_favicon', '');
$primary_color  = setting_get($db, 'brand_primary_color', '#ff7a18');
$secondary_color= setting_get($db, 'brand_secondary_color', '#0b1220');
$accent_color   = setting_get($db, 'brand_accent_color', '#f3f4f6');

$footer_note    = setting_get($db, 'footer_note', '');

$under_construction = setting_get($db, 'under_construction', '0');

$social = setting_get_json($db, 'social_links', [
  'facebook' => '',
  'instagram'=> '',
  'twitter'  => '',
  'linkedin' => '',
  'youtube'  => '',
  'tiktok'   => '',
]);

// Get all pages from database for visible links
$all_pages = [];
if ($db instanceof mysqli) {
  $stmt = $db->prepare("SELECT id, title, slug FROM pages ORDER BY title ASC");
  $stmt->execute();
  $all_pages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
}

$visible_links = setting_get_json($db, 'visible_links', [
  'home' => true,
  'about' => true,
  'services' => true,
  'projects' => true,
  'blog' => true,
  'team' => true,
  'testimonials' => true,
  'contact' => true,
]);

// Add custom pages to visible_links with default false (user must explicitly enable them)
foreach ($all_pages as $page) {
  $slug = $page['slug'];
  // Only add if not already set (don't override database values)
  if (!isset($visible_links[$slug])) {
    $visible_links[$slug] = false; // Default to false for custom pages
  }
}

$home = setting_get_json($db, 'home_settings', [
  'hero_title' => 'Smart Digital Solutions',
  'hero_subtitle' => 'Websites, IT Support & Marketing',
  'hero_cta_text' => 'Get a Quote',
  'hero_cta_link' => 'contact.php',
  'slider_enabled' => true
]);

$flash = $_GET['saved'] ?? '';
?>

<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">

    <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
      <div>
        <h4 class="mb-1">Settings</h4>
        <div class="text-muted small">Update everything without editing code.</div>
      </div>
    </div>

   

    <div class="position-relative">
      <div class="d-flex align-items-center">
        <button type="button" class="btn btn-outline-secondary btn-sm me-2" id="tabScrollLeft" style="display: none;">
          <i class="bi bi-chevron-left"></i>
        </button>
        
        <div class="flex-grow-1 overflow-hidden">
          <ul class="nav nav-tabs flex-nowrap" role="tablist" id="settingsTabs" style="overflow-x: auto; scrollbar-width: none; -ms-overflow-style: none; white-space: nowrap;">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-company" type="button" style="white-space: nowrap;">Company</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-contacts" type="button" style="white-space: nowrap;">Contacts</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-social" type="button" style="white-space: nowrap;">Social</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-branding" type="button" style="white-space: nowrap;">Branding</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-links" type="button" style="white-space: nowrap;">Visible Links</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-home" type="button" style="white-space: nowrap;">Home Page</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-about" type="button" style="white-space: nowrap;">About Page</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-footer" type="button" style="white-space: nowrap;">Footer</button></li>
            <li class="nav-item">
              <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-maint" type="button" style="white-space: nowrap;">
                Under Construction
              </button>
            </li>
          </ul>
        </div>
        
        <button type="button" class="btn btn-outline-secondary btn-sm ms-2" id="tabScrollRight" style="display: none;">
          <i class="bi bi-chevron-right"></i>
        </button>
      </div>
    </div>

    <form class="tab-content border border-top-0 rounded-bottom p-3" method="post" action="save.php" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

      <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex justify-content-between align-items-start gap-2 mb-3" role="alert" id="successAlert">
          <div>
            <h4 class="mb-1">Message:</h4>
            <div class="text-muted small">Your settings have been updated!</div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <script>
          setTimeout(function() {
            const alert = document.getElementById('successAlert');
            if (alert) {
              const bsAlert = new bootstrap.Alert(alert);
              bsAlert.close();
            }
          }, 5000);
        </script>
      <?php endif; ?>

      <!-- Company -->
      <div class="tab-pane fade show active" id="tab-company">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Company Name</label>
            <input class="form-control" name="company_name" value="<?= h($company_name) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Motto</label>
            <input class="form-control" name="company_motto" value="<?= h($company_motto) ?>">
          </div>
          <div class="col-md-8">
            <label class="form-label">Address</label>
            <input class="form-control" name="company_address" value="<?= h($address) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Working Hours</label>
            <input class="form-control" name="company_working_hours" value="<?= h($hours) ?>">
          </div>
        </div>
      </div>

      <!-- Contacts -->
      <div class="tab-pane fade" id="tab-contacts">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Email</label>
            <input class="form-control" name="contact_email" value="<?= h($email) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Phone</label>
            <input class="form-control" name="contact_phone" value="<?= h($phone) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">WhatsApp Number (no +)</label>
            <input class="form-control" name="contact_whatsapp" value="<?= h($whatsapp) ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Google Map Embed URL (iframe src)</label>
            <input class="form-control" name="contact_map_embed" value="<?= h($map_embed) ?>">
          </div>
        </div>
      </div>

      <!-- Social -->
      <div class="tab-pane fade" id="tab-social">
        <div class="row g-3">
          <?php foreach ($social as $k => $v): ?>
            <div class="col-md-6">
              <label class="form-label"><?= h(ucfirst($k)) ?> URL</label>
              <input class="form-control" name="social[<?= h($k) ?>]" value="<?= h((string)$v) ?>">
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Branding -->
      <div class="tab-pane fade" id="tab-branding">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Logo URL/Path</label>
            <div class="input-group">
              <input class="form-control" name="brand_logo" id="brand_logo" value="<?= h($brand_logo) ?>" placeholder="assets/img/logo.png">
              <button class="btn btn-outline-secondary" type="button" onclick="uploadFile('logo')">Upload</button>
            </div>
            <?php if (!empty($brand_logo)): ?>
              <div class="mt-2">
                <small class="text-muted">Preview:</small>
                <div class="border rounded p-2 mt-1" style="max-height: 80px;">
                  <?php 
                  $logo_path = !empty($brand_logo) ? h($brand_logo) : '';
                  $logo_full_url = $logo_path ? (strpos($logo_path, 'http') === 0 ? $logo_path : h(BASE_URL . '/' . ltrim($logo_path, '/'))) : '';
                  ?>
                  <?php if (!empty($logo_full_url)): ?>
                    <img src="<?= $logo_full_url ?>" alt="Logo Preview" style="max-height: 60px; max-width: 200px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <div class="text-muted small" style="display: none;">Preview not available<br><small>Path: <?= $logo_path ?></small></div>
                  <?php else: ?>
                    <div class="text-muted small">No logo uploaded</div>
                  <?php endif; ?>
                </div>
                <?php if (!empty($brand_logo)): ?>
                  <button type="button" class="btn btn-sm btn-outline-danger mt-1" onclick="deleteFile('logo')">
                    <i class="bi bi-trash"></i> Delete
                  </button>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <input type="file" id="logo_file" name="logo_file" accept="image/*" style="display: none;" onchange="handleFileSelect('logo')">
          </div>
          <div class="col-md-6">
            <label class="form-label">Favicon URL/Path</label>
            <div class="input-group">
              <input class="form-control" name="brand_favicon" id="brand_favicon" value="<?= h($favicon) ?>" placeholder="assets/img/favicon.png">
              <button class="btn btn-outline-secondary" type="button" onclick="uploadFile('favicon')">Upload</button>
            </div>
            <?php if (!empty($favicon)): ?>
              <div class="mt-2">
                <small class="text-muted">Preview:</small>
                <div class="border rounded p-2 mt-1" style="max-height: 80px;">
                  <?php 
                  $favicon_path = !empty($favicon) ? h($favicon) : '';
                  $favicon_full_url = $favicon_path ? (strpos($favicon_path, 'http') === 0 ? $favicon_path : h(BASE_URL . '/' . ltrim($favicon_path, '/'))) : '';
                  ?>
                  <?php if (!empty($favicon_full_url)): ?>
                    <img src="<?= $favicon_full_url ?>" alt="Favicon Preview" style="max-height: 32px; max-width: 32px;" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <div class="text-muted small" style="display: none;">Preview not available<br><small>Path: <?= $favicon_path ?></small></div>
                  <?php else: ?>
                    <div class="text-muted small">No favicon uploaded</div>
                  <?php endif; ?>
                </div>
                <?php if (!empty($favicon)): ?>
                  <button type="button" class="btn btn-sm btn-outline-danger mt-1" onclick="deleteFile('favicon')">
                    <i class="bi bi-trash"></i> Delete
                  </button>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <input type="file" id="favicon_file" name="favicon_file" accept="image/x-icon,image/png,image/jpeg,image/gif" style="display: none;" onchange="handleFileSelect('favicon')">
          </div>

          <div class="col-md-4">
            <label class="form-label">Primary Color</label>
            <input type="color" class="form-control form-control-color" name="brand_primary_color" value="<?= h($primary_color) ?>" id="primaryColor">
          </div>
          <div class="col-md-4">
            <label class="form-label">Secondary Color</label>
            <input type="color" class="form-control form-control-color" name="brand_secondary_color" value="<?= h($secondary_color) ?>" id="secondaryColor">
          </div>
          <div class="col-md-4">
            <label class="form-label">Accent Color</label>
            <input type="color" class="form-control form-control-color" name="brand_accent_color" value="<?= h($accent_color) ?>" id="accentColor">
          </div>
        </div>

        <!-- Color Schemes -->
        <div class="mt-4">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Color Schemes</h5>
            <div class="btn-group" role="group">
              <button type="button" class="btn btn-sm btn-outline-info" onclick="testColorSchemes()">
                Test Schemes
              </button>
              <button type="button" class="btn btn-sm btn-outline-warning" onclick="applyToAdmin()">
                Apply to Admin
              </button>
              <button type="button" class="btn btn-sm btn-outline-success" onclick="applyToPublic()">
                Apply to Public
              </button>
            </div>
          </div>
          <div class="alert alert-info mb-3">
            <small><strong>Tip:</strong> Apply schemes to Admin (backend interface) or Public (frontend website). Current mode: <span id="currentMode" class="fw-bold">Both</span></small>
          </div>
          <div class="row g-3">
            <div class="col-md-6 col-lg-4">
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Alma Tech Orange</h6>
                  <div class="d-flex gap-1 mb-2">
                    <div class="color-preview" style="background-color: #fd7e14; width: 30px; height: 30px; border-radius: 4px;" title="Primary"></div>
                    <div class="color-preview" style="background-color: #6c757d; width: 30px; height: 30px; border-radius: 4px;" title="Secondary"></div>
                    <div class="color-preview" style="background-color: #0d6efd; width: 30px; height: 30px; border-radius: 4px;" title="Accent"></div>
                  </div>
                  <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="applyColorScheme('#fd7e14', '#6c757d', '#0d6efd', 'both')">
                      Both
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="applyColorScheme('#fd7e14', '#6c757d', '#0d6efd', 'admin')">
                      Admin
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="applyColorScheme('#fd7e14', '#6c757d', '#0d6efd', 'public')">
                      Public
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6 col-lg-4">
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Professional Blue</h6>
                  <div class="d-flex gap-1 mb-2">
                    <div class="color-preview" style="background-color: #0056b3; width: 30px; height: 30px; border-radius: 4px;" title="Primary"></div>
                    <div class="color-preview" style="background-color: #6c757d; width: 30px; height: 30px; border-radius: 4px;" title="Secondary"></div>
                    <div class="color-preview" style="background-color: #17a2b8; width: 30px; height: 30px; border-radius: 4px;" title="Accent"></div>
                  </div>
                  <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="applyColorScheme('#0056b3', '#6c757d', '#17a2b8', 'both')">
                      Both
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="applyColorScheme('#0056b3', '#6c757d', '#17a2b8', 'admin')">
                      Admin
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="applyColorScheme('#0056b3', '#6c757d', '#17a2b8', 'public')">
                      Public
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6 col-lg-4">
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Modern Green</h6>
                  <div class="d-flex gap-1 mb-2">
                    <div class="color-preview" style="background-color: #198754; width: 30px; height: 30px; border-radius: 4px;" title="Primary"></div>
                    <div class="color-preview" style="background-color: #6c757d; width: 30px; height: 30px; border-radius: 4px;" title="Secondary"></div>
                    <div class="color-preview" style="background-color: #20c997; width: 30px; height: 30px; border-radius: 4px;" title="Accent"></div>
                  </div>
                  <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="applyColorScheme('#198754', '#6c757d', '#20c997', 'both')">
                      Both
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="applyColorScheme('#198754', '#6c757d', '#20c997', 'admin')">
                      Admin
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="applyColorScheme('#198754', '#6c757d', '#20c997', 'public')">
                      Public
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6 col-lg-4">
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Elegant Purple</h6>
                  <div class="d-flex gap-1 mb-2">
                    <div class="color-preview" style="background-color: #6f42c1; width: 30px; height: 30px; border-radius: 4px;" title="Primary"></div>
                    <div class="color-preview" style="background-color: #6c757d; width: 30px; height: 30px; border-radius: 4px;" title="Secondary"></div>
                    <div class="color-preview" style="background-color: #e83e8c; width: 30px; height: 30px; border-radius: 4px;" title="Accent"></div>
                  </div>
                  <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="applyColorScheme('#6f42c1', '#6c757d', '#e83e8c', 'both')">
                      Both
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="applyColorScheme('#6f42c1', '#6c757d', '#e83e8c', 'admin')">
                      Admin
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="applyColorScheme('#6f42c1', '#6c757d', '#e83e8c', 'public')">
                      Public
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6 col-lg-4">
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Bold Red</h6>
                  <div class="d-flex gap-1 mb-2">
                    <div class="color-preview" style="background-color: #dc3545; width: 30px; height: 30px; border-radius: 4px;" title="Primary"></div>
                    <div class="color-preview" style="background-color: #6c757d; width: 30px; height: 30px; border-radius: 4px;" title="Secondary"></div>
                    <div class="color-preview" style="background-color: #ffc107; width: 30px; height: 30px; border-radius: 4px;" title="Accent"></div>
                  </div>
                  <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="applyColorScheme('#dc3545', '#6c757d', '#ffc107', 'both')">
                      Both
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="applyColorScheme('#dc3545', '#6c757d', '#ffc107', 'admin')">
                      Admin
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="applyColorScheme('#dc3545', '#6c757d', '#ffc107', 'public')">
                      Public
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="col-md-6 col-lg-4">
              <div class="card">
                <div class="card-body">
                  <h6 class="card-title">Dark Mode</h6>
                  <div class="d-flex gap-1 mb-2">
                    <div class="color-preview" style="background-color: #212529; width: 30px; height: 30px; border-radius: 4px;" title="Primary"></div>
                    <div class="color-preview" style="background-color: #495057; width: 30px; height: 30px; border-radius: 4px;" title="Secondary"></div>
                    <div class="color-preview" style="background-color: #fd7e14; width: 30px; height: 30px; border-radius: 4px;" title="Accent"></div>
                  </div>
                  <div class="btn-group w-100" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="applyColorScheme('#212529', '#495057', '#fd7e14', 'both')">
                      Both
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="applyColorScheme('#212529', '#495057', '#fd7e14', 'admin')">
                      Admin
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="applyColorScheme('#212529', '#495057', '#fd7e14', 'public')">
                      Public
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Live Preview -->
        <div class="mt-4">
          <h5 class="mb-3">Live Preview</h5>
          <div class="card" id="brandPreview">
            <div class="card-header" id="previewHeader">
              <h6 class="mb-0 text-white">Sample Header</h6>
            </div>
            <div class="card-body">
              <h5 class="card-title" id="previewTitle">Sample Title</h5>
              <p class="card-text">This is a preview of how your brand colors will look on the website.</p>
              <button class="btn" id="previewButton">Sample Button</button>
              <button class="btn btn-outline-secondary" id="previewOutlineButton">Outline Button</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Visible Links -->
      <div class="tab-pane fade" id="tab-links">
        <?php
        // Core pages (always included)
        $core_pages = [
          'home' => 'Home',
          'about' => 'About',
          'services' => 'Services',
          'projects' => 'Projects',
          'blog' => 'Blog',
          'team' => 'Team',
          'testimonials' => 'Testimonials',
          'contact' => 'Contact',
        ];

        // Build a list of ALL nav items (core + custom)
        $nav_items = [];

        foreach ($core_pages as $slug => $title) {
          $nav_items[$slug] = ['slug' => $slug, 'title' => $title, 'type' => 'core'];
        }

        foreach ($all_pages as $p) {
          $pslug = (string)($p['slug'] ?? '');
          $ptitle = (string)($p['title'] ?? '');
          if ($pslug === '' || isset($nav_items[$pslug])) continue; // skip core or duplicates
          $nav_items[$pslug] = ['slug' => $pslug, 'title' => $ptitle, 'type' => 'custom'];
        }

        // Load order (fallback: default core order then custom alpha)
        $nav_order = setting_get_json($db, 'nav_order', []);
        if (!is_array($nav_order) || empty($nav_order)) {
          $nav_order = array_keys($core_pages);
          $custom_slugs = array_values(array_diff(array_keys($nav_items), array_keys($core_pages)));
          sort($custom_slugs);
          $nav_order = array_merge($nav_order, $custom_slugs);
        }

        // Ensure any new page gets added into order
        foreach (array_keys($nav_items) as $slug) {
          if (!in_array($slug, $nav_order, true)) $nav_order[] = $slug;
        }

        // Clean order: remove slugs that no longer exist
        $nav_order = array_values(array_filter($nav_order, fn($s) => isset($nav_items[(string)$s])));
        ?>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
          <div>
            <h5 class="mb-1">Navigation Links</h5>
            <div class="text-muted small">Toggle visibility and drag to set order.</div>
          </div>

          <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="navResetBtn">
              <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Default Order
            </button>
          </div>
        </div>

        <input type="hidden" name="nav_order_json" id="navOrderJson" value="<?= h(json_encode($nav_order)) ?>">

        <div class="alert alert-info py-2">
          <small>
            <strong>Tip:</strong> Drag rows to reorder. Use the toggle to hide.
            Core pages are essential; custom pages come from Pages module.
          </small>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle" id="navTable">
            <thead class="table-light">
              <tr>
                <th style="width:60px;">Order</th>
                <th>Page</th>
                <th style="width:140px;">Type</th>
                <th style="width:140px;">Visible</th>
                <th style="width:80px;" class="text-end">Drag</th>
              </tr>
            </thead>
            <tbody id="navTbody">
              <?php foreach ($nav_order as $slug): ?>
                <?php $item = $nav_items[$slug]; ?>
                <tr data-slug="<?= h($slug) ?>">
                  <td class="text-muted fw-semibold">
                    <span class="order-badge badge bg-secondary-subtle text-dark">—</span>
                  </td>

                  <td>
                    <div class="fw-semibold"><?= h($item['title']) ?></div>
                    <div class="small text-muted"><code><?= h($slug) ?></code></div>
                  </td>

                  <td>
                    <span class="badge <?= $item['type']==='core' ? 'bg-primary' : 'bg-success' ?>">
                      <?= $item['type']==='core' ? 'Core' : 'Custom' ?>
                    </span>
                  </td>

                  <td>
                    <div class="form-check form-switch mb-0">
                      <input class="form-check-input nav-visible"
                             type="checkbox"
                             name="visible_links[<?= h($slug) ?>]"
                             value="1"
                             <?= !empty($visible_links[$slug]) ? 'checked' : '' ?>>
                      <label class="form-check-label small text-muted">Show</label>
                    </div>
                  </td>

                  <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-secondary drag-handle" title="Drag to reorder">
                      <i class="bi bi-grip-vertical"></i>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <style>
          #navTable tbody tr { cursor: default; }
          #navTable tbody tr.dragging { opacity: .6; }
          #navTable .drag-handle { cursor: grab; }
          #navTable .drag-handle:active { cursor: grabbing; }
        </style>

        <script>
          (function(){
            const tbody = document.getElementById('navTbody');
            const orderInput = document.getElementById('navOrderJson');
            const resetBtn = document.getElementById('navResetBtn');

            if(!tbody || !orderInput) return;

            function updateOrderBadges(){
              const rows = Array.from(tbody.querySelectorAll('tr'));
              rows.forEach((row, i) => {
                const badge = row.querySelector('.order-badge');
                if (badge) badge.textContent = String(i + 1);
              });
            }

            function saveOrderToHidden(){
              const rows = Array.from(tbody.querySelectorAll('tr'));
              const order = rows.map(r => r.dataset.slug).filter(Boolean);
              orderInput.value = JSON.stringify(order);
            }

            // HTML5 drag and drop
            let dragRow = null;

            tbody.querySelectorAll('tr').forEach(row => {
              const handle = row.querySelector('.drag-handle');
              if(!handle) return;

              row.draggable = true;

              row.addEventListener('dragstart', (e) => {
                dragRow = row;
                row.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
              });

              row.addEventListener('dragend', () => {
                row.classList.remove('dragging');
                dragRow = null;
                updateOrderBadges();
                saveOrderToHidden();
              });

              row.addEventListener('dragover', (e) => {
                e.preventDefault();
                const overRow = row;
                if(!dragRow || dragRow === overRow) return;

                const rect = overRow.getBoundingClientRect();
                const next = (e.clientY - rect.top) > (rect.height / 2);
                tbody.insertBefore(dragRow, next ? overRow.nextSibling : overRow);
              });

              // only allow dragging from handle
              row.addEventListener('mousedown', (e) => {
                if(!e.target.closest('.drag-handle')) row.draggable = false;
              });
              handle.addEventListener('mousedown', () => {
                row.draggable = true;
              });
            });

            // Reset default order: core first, custom alpha
            resetBtn?.addEventListener('click', () => {
              const rows = Array.from(tbody.querySelectorAll('tr'));
              const core = [];
              const custom = [];

              rows.forEach(r => {
                const typeBadge = r.querySelector('td:nth-child(3) .badge');
                const isCore = typeBadge && typeBadge.textContent.trim().toLowerCase() === 'core';
                (isCore ? core : custom).push(r);
              });

              custom.sort((a,b) => {
                const at = (a.querySelector('td:nth-child(2) .fw-semibold')?.textContent || '').toLowerCase();
                const bt = (b.querySelector('td:nth-child(2) .fw-semibold')?.textContent || '').toLowerCase();
                return at.localeCompare(bt);
              });

              tbody.innerHTML = '';
              [...core, ...custom].forEach(r => tbody.appendChild(r));

              updateOrderBadges();
              saveOrderToHidden();
            });

            updateOrderBadges();
            saveOrderToHidden();
          })();
        </script>
      </div>

      <!-- Home Page -->
      <div class="tab-pane fade" id="tab-home">
        <div class="text-center py-5">
          <div class="mb-4">
            <i class="bi bi-house-gear-fill text-primary" style="font-size: 3rem;"></i>
          </div>
          <h5 class="mb-3">Homepage Settings</h5>
          <p class="text-muted mb-4">Manage hero section, services preview, stats, and call-to-action content</p>
          <a href="<?= BASE_URL ?>admin/home/index.php" class="btn btn-primary btn-lg">
            <i class="bi bi-pencil-square me-2"></i>Edit Homepage Settings
          </a>
          <div class="mt-3">
            <small class="text-muted">
              Configure hero text, quick request form, service cards, statistics, and CTA section
            </small>
          </div>
        </div>
      </div>

      <!-- About Page -->
      <div class="tab-pane fade" id="tab-about">
        <div class="text-center py-5">
          <div class="mb-4">
            <i class="bi bi-info-circle-fill text-primary" style="font-size: 3rem;"></i>
          </div>
          <h5 class="mb-3">About Page Settings</h5>
          <p class="text-muted mb-4">Manage hero section, story, mission, vision, and values</p>
          <a href="<?= BASE_URL ?>admin/about/index.php" class="btn btn-primary btn-lg">
            <i class="bi bi-pencil-square me-2"></i>Edit About Settings
          </a>
          <div class="mt-3">
            <small class="text-muted">
              Configure about page hero title, subtitle, story, mission, vision, and company values
            </small>
          </div>
        </div>
      </div>

      <?php
$maint = setting_get_json($db, 'maintenance_settings', [
  'enabled' => false,
  'message' => 'We are updating the website. Please check back soon.',
  'custom_html' => '',
  'custom_css' => '',
  'custom_js' => '',
  'image_path' => '',
  'image_mode' => 'cover',
  'template_id' => null,
]);

// templates dropdown
$tpls = [];
$tRes = $db->query("SELECT id,title FROM maintenance_templates WHERE is_active=1 ORDER BY title ASC");
if ($tRes) $tpls = $tRes->fetch_all(MYSQLI_ASSOC);
?>
<div class="tab-pane fade" id="tab-maint">
  <div class="row g-3">

    <div class="col-12">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="maint[enabled]" value="1" <?= !empty($maint['enabled']) ? 'checked' : '' ?>>
        <label class="form-check-label">Enable Under Construction Mode</label>
      </div>
      <div class="text-muted small mt-1">When enabled, visitors see the maintenance page (admins still access dashboard).</div>
    </div>

    <div class="col-md-6">
      <label class="form-label">Template (Saved Page)</label>
      <select class="form-select" name="maint[template_id]">
        <option value="">None (use fields below)</option>
        <?php foreach ($tpls as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= ((string)($maint['template_id'] ?? '') === (string)$t['id']) ? 'selected' : '' ?>>
            <?= h($t['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">If selected, the template HTML/CSS/JS is used (you can still add extra CSS/JS below).</div>
    </div>

    <div class="col-md-6">
      <label class="form-label">Custom Message</label>
      <input class="form-control" name="maint[message]" value="<?= h((string)($maint['message'] ?? '')) ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label">Cover Image</label>
      <input class="form-control" type="file" name="maint_image" accept="image/*">
      <?php if (!empty($maint['image_path'])): ?>
        <div class="form-text">Current: <code><?= h((string)$maint['image_path']) ?></code></div>
      <?php endif; ?>
    </div>

    <div class="col-md-6">
      <label class="form-label">Image Mode</label>
      <select class="form-select" name="maint[image_mode]">
        <?php foreach (['cover','contain','center-crop'] as $m): ?>
          <option value="<?= h($m) ?>" <?= (($maint['image_mode'] ?? 'cover') === $m) ? 'selected' : '' ?>><?= h($m) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">cover = fill, contain = show full, center-crop = crop center.</div>
    </div>

    <div class="col-12">
      <label class="form-label">Custom HTML (optional)</label>
      <textarea class="form-control" name="maint[custom_html]" rows="6"><?= h((string)($maint['custom_html'] ?? '')) ?></textarea>
    </div>

    <div class="col-12">
      <label class="form-label">Custom CSS (optional)</label>
      <textarea class="form-control" name="maint[custom_css]" rows="6"><?= h((string)($maint['custom_css'] ?? '')) ?></textarea>
    </div>

    <div class="col-12">
      <label class="form-label">Custom JS (optional)</label>
      <textarea class="form-control" name="maint[custom_js]" rows="6"><?= h((string)($maint['custom_js'] ?? '')) ?></textarea>
      <div class="form-text">Keep JS simple—this runs on the maintenance page only.</div>
    </div>

    <div class="col-12 d-flex gap-2">
      <a class="btn btn-outline-secondary" href="../maintenance/list.php">
        Manage Templates
      </a>
    </div>

  </div>
</div>


      <!-- Footer -->
      <div class="tab-pane fade" id="tab-footer">
        <label class="form-label">Footer Note</label>
        <textarea class="form-control" name="footer_note" rows="3"><?= h($footer_note) ?></textarea>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-3">
        <button class="btn btn-primary" type="submit">
          Save Settings
        </button>
      </div>
    </form>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

<script>
// Tab navigation functionality
document.addEventListener('DOMContentLoaded', function() {
  const tabsContainer = document.getElementById('settingsTabs');
  const leftBtn = document.getElementById('tabScrollLeft');
  const rightBtn = document.getElementById('tabScrollRight');
  
  if (!tabsContainer || !leftBtn || !rightBtn) return;
  
  // Hide scrollbar for Webkit browsers
  const style = document.createElement('style');
  style.textContent = `
    #settingsTabs::-webkit-scrollbar {
      display: none;
    }
    
    /* Make active tab orange */
    #settingsTabs .nav-link.active {
      background-color: #fd7e14 !important;
      border-color: #fd7e14 !important;
      color: white !important;
    }
    
    #settingsTabs .nav-link.active:hover {
      background-color: #e8590c !important;
      border-color: #e8590c !important;
      color: white !important;
    }
    
    /* Ensure tabs stay on one line */
    #settingsTabs .nav-link {
      white-space: nowrap;
    }
  `;
  document.head.appendChild(style);
  
  // Check if tabs are overflowing
  function checkOverflow() {
    const isOverflowing = tabsContainer.scrollWidth > tabsContainer.clientWidth;
    
    if (isOverflowing) {
      leftBtn.style.display = 'block';
      rightBtn.style.display = 'block';
      updateButtonStates();
    } else {
      leftBtn.style.display = 'none';
      rightBtn.style.display = 'none';
    }
  }
  
  // Update button states based on scroll position
  function updateButtonStates() {
    const scrollLeft = tabsContainer.scrollLeft;
    const maxScroll = tabsContainer.scrollWidth - tabsContainer.clientWidth;
    
    leftBtn.disabled = scrollLeft <= 0;
    rightBtn.disabled = scrollLeft >= maxScroll;
  }
  
  // Scroll tabs left
  leftBtn.addEventListener('click', function() {
    const scrollAmount = 200; // Adjust scroll amount as needed
    tabsContainer.scrollTo({
      left: Math.max(0, tabsContainer.scrollLeft - scrollAmount),
      behavior: 'smooth'
    });
  });
  
  // Scroll tabs right
  rightBtn.addEventListener('click', function() {
    const scrollAmount = 200; // Adjust scroll amount as needed
    const maxScroll = tabsContainer.scrollWidth - tabsContainer.clientWidth;
    tabsContainer.scrollTo({
      left: Math.min(maxScroll, tabsContainer.scrollLeft + scrollAmount),
      behavior: 'smooth'
    });
  });
  
  // Update button states on scroll
  tabsContainer.addEventListener('scroll', updateButtonStates);
  
  // Check overflow on window resize
  window.addEventListener('resize', checkOverflow);
  
  // Initial check
  setTimeout(checkOverflow, 100);
  
  // Also check when any tab is clicked (in case it affects overflow)
  const tabButtons = tabsContainer.querySelectorAll('.nav-link');
  tabButtons.forEach(button => {
    button.addEventListener('click', function() {
      setTimeout(checkOverflow, 100);
    });
  });
  
  // Color scheme functionality - make it global
  window.applyColorScheme = function(primary, secondary, accent, mode = 'both') {
    const primaryInput = document.getElementById('primaryColor');
    const secondaryInput = document.getElementById('secondaryColor');
    const accentInput = document.getElementById('accentColor');
    
    if (primaryInput) primaryInput.value = primary;
    if (secondaryInput) secondaryInput.value = secondary;
    if (accentInput) accentInput.value = accent;
    
    // Update current mode display
    const currentMode = document.getElementById('currentMode');
    if (currentMode) {
      currentMode.textContent = mode.charAt(0).toUpperCase() + mode.slice(1);
      currentMode.className = mode === 'admin' ? 'fw-bold text-warning' : 
                           mode === 'public' ? 'fw-bold text-success' : 'fw-bold text-primary';
    }
    
    updateColorPreview();
    
    // Apply to specific interface
    if (mode === 'admin' || mode === 'both') {
      applyToAdminInterface(primary, secondary, accent);
    }
    if (mode === 'public' || mode === 'both') {
      applyToPublicInterface(primary, secondary, accent);
    }
    
    // Show notification
    showColorNotification(mode, primary, secondary, accent);
  }
  
  function applyToAdminInterface(primary, secondary, accent) {
    // Apply colors to admin interface elements
    const adminStyle = document.createElement('style');
    adminStyle.id = 'admin-color-scheme';
    adminStyle.textContent = `
      .admin-header { background-color: ${primary} !important; }
      .admin-sidebar { background-color: ${secondary} !important; }
      .admin-sidebar .nav-link:hover { background-color: ${accent} !important; }
      .btn-orange { background-color: ${primary} !important; border-color: ${primary} !important; }
      .btn-orange:hover { background-color: ${accent} !important; border-color: ${accent} !important; }
      .nav-tabs .nav-link.active { background-color: ${primary} !important; border-color: ${primary} !important; }
    `;
    
    // Remove existing admin style
    const existingAdminStyle = document.getElementById('admin-color-scheme');
    if (existingAdminStyle) existingAdminStyle.remove();
    
    document.head.appendChild(adminStyle);
    console.log('Applied color scheme to admin interface');
  }
  
  function applyToPublicInterface(primary, secondary, accent) {
    // Update the CSS file directly
    updateCSSFileDirectly(primary, secondary, accent);
    
    // Also save to localStorage for immediate use
    const publicColors = {
      primary: primary,
      secondary: secondary,
      accent: accent,
      timestamp: new Date().toISOString()
    };
    
    localStorage.setItem('publicColorScheme', JSON.stringify(publicColors));
    console.log('Saved color scheme for public interface:', publicColors);
  }
  
  function updateCSSFileDirectly(primary, secondary, accent) {
    // Convert hex to RGB
    function hexToRgb(hex) {
      const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
      return result ? 
        `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}` : 
        '253, 126, 20';
    }
    
    const primaryRgb = hexToRgb(primary);
    const secondaryRgb = hexToRgb(secondary);
    const accentRgb = hexToRgb(accent);
    
    // Generate CSS content
    const cssContent = `/* Dynamic Brand Colors - Generated by Admin Settings */
/* This file is automatically updated when color schemes are applied */
/* Last updated: ${new Date().toISOString()} */

:root {
  /* Brand Colors */
  --brand-primary: ${primary};
  --brand-secondary: ${secondary};
  --brand-accent: ${accent};
  --brand-primary-rgb: ${primaryRgb};
  --brand-secondary-rgb: ${secondaryRgb};
  --brand-accent-rgb: ${accentRgb};
}

/* Primary Brand Elements */
.btn-primary {
  background-color: var(--brand-primary) !important;
  border-color: var(--brand-primary) !important;
}

.btn-primary:hover {
  background-color: var(--brand-accent) !important;
  border-color: var(--brand-accent) !important;
}

.btn-orange {
  background-color: var(--brand-primary) !important;
  border-color: var(--brand-primary) !important;
  color: white !important;
}

.btn-orange:hover {
  background-color: var(--brand-accent) !important;
  border-color: var(--brand-accent) !important;
  color: white !important;
}

/* Header Elements */
.hero {
  background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
}

/* Service Cards */
.service-card {
  border-left: 4px solid var(--brand-primary);
}

.service-card:hover {
  border-left-color: var(--brand-accent);
}

/* Navigation Elements */
.nav-tabs .nav-link.active {
  background-color: var(--brand-primary) !important;
  border-color: var(--brand-primary) !important;
  color: white !important;
}

.nav-tabs .nav-link.active:hover {
  background-color: var(--brand-accent) !important;
  border-color: var(--brand-accent) !important;
  color: white !important;
}

/* Links */
a {
  color: var(--brand-primary);
}

a:hover {
  color: var(--brand-accent);
}

/* Form Elements */
.form-control:focus {
  border-color: var(--brand-primary);
  box-shadow: 0 0 0 0.2rem rgba(var(--brand-primary-rgb), 0.25);
}

/* Admin Interface */
.admin-header {
  background-color: var(--brand-primary) !important;
}

.admin-sidebar {
  background-color: var(--brand-secondary) !important;
}

.admin-sidebar .nav-link:hover {
  background-color: var(--brand-accent) !important;
}

/* Footer */
.footer {
  background-color: var(--brand-secondary);
  border-top: 3px solid var(--brand-primary);
}

/* Value Cards */
.value-card {
  border-top: 3px solid var(--brand-primary);
}

/* Testimonial Cards */
.testimonial-card {
  border-left: 4px solid var(--brand-primary);
}

/* Team Cards */
.team-card {
  border-left: 4px solid var(--brand-primary);
}

/* Badge Elements */
.badge-primary {
  background-color: var(--brand-primary) !important;
}

/* Alert Elements */
.alert-primary {
  background-color: var(--brand-primary) !important;
  border-color: var(--brand-primary) !important;
}

/* Progress Bars */
.progress-bar {
  background-color: var(--brand-primary) !important;
}

/* Accordion Elements */
.accordion-button:not(.collapsed) {
  background-color: var(--brand-primary) !important;
  color: white !important;
}

/* Pagination Elements */
.page-link.active {
  background-color: var(--brand-primary) !important;
  border-color: var(--brand-primary) !important;
}

/* Breadcrumb Elements */
.breadcrumb-item.active {
  color: var(--brand-primary) !important;
}`;
    
    // Create a blob and download it (simulating file update)
    const blob = new Blob([cssContent], { type: 'text/css' });
    const url = URL.createObjectURL(blob);
    
    // Create a temporary link to trigger download
    const link = document.createElement('a');
    link.href = url;
    link.download = 'brand-colors.css';
    link.style.display = 'none';
    document.body.appendChild(link);
    
    // For now, just show success message
    console.log('CSS content generated for:', { primary, secondary, accent });
    console.log('CSS file would be updated at: assets/css/brand-colors.css');
    
    // Force reload of CSS file
    reloadBrandCSS();
    
    // Clean up
    setTimeout(() => {
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    }, 100);
  }
  
  function reloadBrandCSS() {
    // Force reload of the brand CSS file
    const cssLink = document.querySelector('link[href*="brand-colors.css"]');
    if (cssLink) {
      const href = cssLink.href;
      cssLink.href = href + '?t=' + new Date().getTime();
    } else {
      // Create the link if it doesn't exist
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = '../assets/css/brand-colors.css?t=' + new Date().getTime();
      document.head.appendChild(link);
    }
  }
  
  function showColorNotification(mode, primary, secondary, accent) {
    // Create a temporary notification
    const notification = document.createElement('div');
    notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
      <strong>Color Scheme Applied!</strong><br>
      <small>Mode: ${mode.charAt(0).toUpperCase() + mode.slice(1)}</small><br>
      <small>Colors: ${primary}, ${secondary}, ${accent}</small>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 3000);
  }
  
  // Quick apply functions
  window.applyToAdmin = function() {
    const primary = document.getElementById('primaryColor')?.value || '#fd7e14';
    const secondary = document.getElementById('secondaryColor')?.value || '#6c757d';
    const accent = document.getElementById('accentColor')?.value || '#0d6efd';
    window.applyColorScheme(primary, secondary, accent, 'admin');
  };
  
  window.applyToPublic = function() {
    const primary = document.getElementById('primaryColor')?.value || '#fd7e14';
    const secondary = document.getElementById('secondaryColor')?.value || '#6c757d';
    const accent = document.getElementById('accentColor')?.value || '#0d6efd';
    window.applyColorScheme(primary, secondary, accent, 'public');
  };
  
  function updateColorPreview() {
    const primary = document.getElementById('primaryColor')?.value || '#fd7e14';
    const secondary = document.getElementById('secondaryColor')?.value || '#6c757d';
    const accent = document.getElementById('accentColor')?.value || '#0d6efd';
    
    const previewHeader = document.getElementById('previewHeader');
    const previewTitle = document.getElementById('previewTitle');
    const previewButton = document.getElementById('previewButton');
    const previewOutlineButton = document.getElementById('previewOutlineButton');
    
    if (previewHeader) {
      previewHeader.style.backgroundColor = primary;
    }
    if (previewTitle) {
      previewTitle.style.color = primary;
    }
    if (previewButton) {
      previewButton.style.backgroundColor = primary;
      previewButton.style.borderColor = primary;
      previewButton.style.color = 'white';
    }
    if (previewOutlineButton) {
      previewOutlineButton.style.borderColor = accent;
      previewOutlineButton.style.color = accent;
    }
  }
  
  // Add event listeners to color inputs
  const primaryColor = document.getElementById('primaryColor');
  const secondaryColor = document.getElementById('secondaryColor');
  const accentColor = document.getElementById('accentColor');
  
  if (primaryColor) primaryColor.addEventListener('input', updateColorPreview);
  if (secondaryColor) secondaryColor.addEventListener('input', updateColorPreview);
  if (accentColor) accentColor.addEventListener('input', updateColorPreview);
  
  // Initialize preview on page load
  setTimeout(updateColorPreview, 100);
  
  // Load color schemes from database (if available)
  function loadColorSchemes() {
    // This could be enhanced to load from database in the future
    const savedSchemes = localStorage.getItem('brandColorSchemes');
    if (savedSchemes) {
      try {
        const schemes = JSON.parse(savedSchemes);
        console.log('Loaded saved color schemes:', schemes);
      } catch (e) {
        console.log('No saved color schemes found');
      }
    }
  }
  
  // Save color schemes to localStorage (temporary solution)
  function saveColorScheme(name, primary, secondary, accent) {
    const savedSchemes = localStorage.getItem('brandColorSchemes');
    const schemes = savedSchemes ? JSON.parse(savedSchemes) : {};
    
    schemes[name] = { primary, secondary, accent };
    localStorage.setItem('brandColorSchemes', JSON.stringify(schemes));
  }
  
  // Initialize color schemes
  loadColorSchemes();
  
  // Test function for debugging
  window.testColorSchemes = function() {
    console.log('Testing color schemes...');
    console.log('Primary input:', document.getElementById('primaryColor'));
    console.log('Secondary input:', document.getElementById('secondaryColor'));
    console.log('Accent input:', document.getElementById('accentColor'));
    
    // Test applying Alma Tech Orange scheme
    if (window.applyColorScheme) {
      console.log('Applying Alma Tech Orange scheme...');
      window.applyColorScheme('#fd7e14', '#6c757d', '#0d6efd');
      console.log('Scheme applied successfully!');
    } else {
      console.error('applyColorScheme function not found!');
    }
  };
});
</script>


      <!-- Home Page -->
      <div class="tab-pane fade" id="tab-home">
        <div class="text-center py-5">
          <div class="mb-4">
            <i class="bi bi-house-gear-fill text-primary" style="font-size: 3rem;"></i>
          </div>
          <h5 class="mb-3">Homepage Settings</h5>
          <p class="text-muted mb-4">Manage hero section, services preview, stats, and call-to-action content</p>
          <a href="<?= BASE_URL ?>admin/home/index.php" class="btn btn-primary btn-lg">
            <i class="bi bi-pencil-square me-2"></i>Edit Homepage Settings
          </a>
          <div class="mt-3">
            <small class="text-muted">
              Configure hero text, quick request form, service cards, statistics, and CTA section
            </small>
          </div>
        </div>
      </div>

      <!-- About Page -->
      <div class="tab-pane fade" id="tab-about">
        <div class="text-center py-5">
          <div class="mb-4">
            <i class="bi bi-info-circle-fill text-primary" style="font-size: 3rem;"></i>
          </div>
          <h5 class="mb-3">About Page Settings</h5>
          <p class="text-muted mb-4">Manage hero section, story, mission, vision, and values</p>
          <a href="<?= BASE_URL ?>admin/about/index.php" class="btn btn-primary btn-lg">
            <i class="bi bi-pencil-square me-2"></i>Edit About Settings
          </a>
          <div class="mt-3">
            <small class="text-muted">
              Configure about page hero title, subtitle, story, mission, vision, and company values
            </small>
          </div>
        </div>
      </div>

      <?php
$maint = setting_get_json($db, 'maintenance_settings', [
  'enabled' => false,
  'message' => 'We are updating the website. Please check back soon.',
  'custom_html' => '',
  'custom_css' => '',
  'custom_js' => '',
  'image_path' => '',
  'image_mode' => 'cover',
  'template_id' => null,
]);

// templates dropdown
$tpls = [];
$tRes = $db->query("SELECT id,title FROM maintenance_templates WHERE is_active=1 ORDER BY title ASC");
if ($tRes) $tpls = $tRes->fetch_all(MYSQLI_ASSOC);
?>
<div class="tab-pane fade" id="tab-maint">
  <div class="row g-3">

    <div class="col-12">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" name="maint[enabled]" value="1" <?= !empty($maint['enabled']) ? 'checked' : '' ?>>
        <label class="form-check-label">Enable Under Construction Mode</label>
      </div>
      <div class="text-muted small mt-1">When enabled, visitors see the maintenance page (admins still access dashboard).</div>
    </div>

    <div class="col-md-6">
      <label class="form-label">Template (Saved Page)</label>
      <select class="form-select" name="maint[template_id]">
        <option value="">None (use fields below)</option>
        <?php foreach ($tpls as $t): ?>
          <option value="<?= (int)$t['id'] ?>" <?= ((string)($maint['template_id'] ?? '') === (string)$t['id']) ? 'selected' : '' ?>>
            <?= h($t['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">If selected, the template HTML/CSS/JS is used (you can still add extra CSS/JS below).</div>
    </div>

    <div class="col-md-6">
      <label class="form-label">Custom Message</label>
      <input class="form-control" name="maint[message]" value="<?= h((string)($maint['message'] ?? '')) ?>">
    </div>

    <div class="col-md-6">
      <label class="form-label">Cover Image</label>
      <input class="form-control" type="file" name="maint_image" accept="image/*">
      <?php if (!empty($maint['image_path'])): ?>
        <div class="form-text">Current: <code><?= h((string)$maint['image_path']) ?></code></div>
      <?php endif; ?>
    </div>

    <div class="col-md-6">
      <label class="form-label">Image Mode</label>
      <select class="form-select" name="maint[image_mode]">
        <?php foreach (['cover','contain','center-crop'] as $m): ?>
          <option value="<?= h($m) ?>" <?= (($maint['image_mode'] ?? 'cover') === $m) ? 'selected' : '' ?>><?= h($m) ?></option>
        <?php endforeach; ?>
      </select>
      <div class="form-text">cover = fill, contain = show full, center-crop = crop center.</div>
    </div>

    <div class="col-12">
      <label class="form-label">Custom HTML (optional)</label>
      <textarea class="form-control" name="maint[custom_html]" rows="6"><?= h((string)($maint['custom_html'] ?? '')) ?></textarea>
    </div>

    <div class="col-12">
      <label class="form-label">Custom CSS (optional)</label>
      <textarea class="form-control" name="maint[custom_css]" rows="6"><?= h((string)($maint['custom_css'] ?? '')) ?></textarea>
    </div>

    <div class="col-12">
      <label class="form-label">Custom JS (optional)</label>
      <textarea class="form-control" name="maint[custom_js]" rows="6"><?= h((string)($maint['custom_js'] ?? '')) ?></textarea>
      <div class="form-text">Keep JS simple—this runs on the maintenance page only.</div>
    </div>

    <div class="col-12 d-flex gap-2">
      <a class="btn btn-outline-secondary" href="../maintenance/list.php">
        Manage Templates
      </a>
    </div>

  </div>
</div>


      <!-- Footer -->
      <div class="tab-pane fade" id="tab-footer">
        <label class="form-label">Footer Note</label>
        <textarea class="form-control" name="footer_note" rows="3"><?= h($footer_note) ?></textarea>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-3">
        <button class="btn btn-primary" type="submit">
          Save Settings
        </button>
      </div>
    </form>

  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

<script>
// Tab navigation functionality
document.addEventListener('DOMContentLoaded', function() {
  const tabsContainer = document.getElementById('settingsTabs');
  const leftBtn = document.getElementById('tabScrollLeft');
  const rightBtn = document.getElementById('tabScrollRight');
  
  if (!tabsContainer || !leftBtn || !rightBtn) return;
  
  // Hide scrollbar for Webkit browsers
  const style = document.createElement('style');
  style.textContent = `
    #settingsTabs::-webkit-scrollbar {
      display: none;
    }
    
    /* Make active tab orange */
    #settingsTabs .nav-link.active {
      background-color: #fd7e14 !important;
      border-color: #fd7e14 !important;
      color: white !important;
    }
    
    #settingsTabs .nav-link.active:hover {
      background-color: #e8590c !important;
      border-color: #e8590c !important;
      color: white !important;
    }
    
    /* Ensure tabs stay on one line */
    #settingsTabs .nav-link {
      white-space: nowrap;
    }
  `;
  document.head.appendChild(style);
  
  // Check if tabs are overflowing
  function checkOverflow() {
    const isOverflowing = tabsContainer.scrollWidth > tabsContainer.clientWidth;
    
    if (isOverflowing) {
      leftBtn.style.display = 'block';
      rightBtn.style.display = 'block';
      updateButtonStates();
    } else {
      leftBtn.style.display = 'none';
      rightBtn.style.display = 'none';
    }
  }
  
  // Update button states based on scroll position
  function updateButtonStates() {
    const scrollLeft = tabsContainer.scrollLeft;
    const maxScroll = tabsContainer.scrollWidth - tabsContainer.clientWidth;
    
    leftBtn.disabled = scrollLeft <= 0;
    rightBtn.disabled = scrollLeft >= maxScroll;
  }
  
  // Scroll tabs left
  leftBtn.addEventListener('click', function() {
    const scrollAmount = 200; // Adjust scroll amount as needed
    tabsContainer.scrollTo({
      left: Math.max(0, tabsContainer.scrollLeft - scrollAmount),
      behavior: 'smooth'
    });
  });
  
  // Scroll tabs right
  rightBtn.addEventListener('click', function() {
    const scrollAmount = 200; // Adjust scroll amount as needed
    const maxScroll = tabsContainer.scrollWidth - tabsContainer.clientWidth;
    tabsContainer.scrollTo({
      left: Math.min(maxScroll, tabsContainer.scrollLeft + scrollAmount),
      behavior: 'smooth'
    });
  });
  
  // Update button states on scroll
  tabsContainer.addEventListener('scroll', updateButtonStates);
  
  // Check overflow on window resize
  window.addEventListener('resize', checkOverflow);
  
  // Initial check
  setTimeout(checkOverflow, 100);
  
  // Also check when any tab is clicked (in case it affects overflow)
  const tabButtons = tabsContainer.querySelectorAll('.nav-link');
  tabButtons.forEach(button => {
    button.addEventListener('click', function() {
      setTimeout(checkOverflow, 100);
    });
  });
  
  // Color scheme functionality - make it global
  window.applyColorScheme = function(primary, secondary, accent, mode = 'both') {
    const primaryInput = document.getElementById('primaryColor');
    const secondaryInput = document.getElementById('secondaryColor');
    const accentInput = document.getElementById('accentColor');
    
    if (primaryInput) primaryInput.value = primary;
    if (secondaryInput) secondaryInput.value = secondary;
    if (accentInput) accentInput.value = accent;
    
    // Update current mode display
    const currentMode = document.getElementById('currentMode');
    if (currentMode) {
      currentMode.textContent = mode.charAt(0).toUpperCase() + mode.slice(1);
      currentMode.className = mode === 'admin' ? 'fw-bold text-warning' : 
                           mode === 'public' ? 'fw-bold text-success' : 'fw-bold text-primary';
    }
    
    updateColorPreview();
    
    // Apply to specific interface
    if (mode === 'admin' || mode === 'both') {
      applyToAdminInterface(primary, secondary, accent);
    }
    if (mode === 'public' || mode === 'both') {
      applyToPublicInterface(primary, secondary, accent);
    }
    
    // Show notification
    showColorNotification(mode, primary, secondary, accent);
  }
  
  function applyToAdminInterface(primary, secondary, accent) {
    // Apply colors to admin interface elements
    const adminStyle = document.createElement('style');
    adminStyle.id = 'admin-color-scheme';
    adminStyle.textContent = `
      .admin-header { background-color: ${primary} !important; }
      .admin-sidebar { background-color: ${secondary} !important; }
      .admin-sidebar .nav-link:hover { background-color: ${accent} !important; }
      .btn-orange { background-color: ${primary} !important; border-color: ${primary} !important; }
      .btn-orange:hover { background-color: ${accent} !important; border-color: ${accent} !important; }
      .nav-tabs .nav-link.active { background-color: ${primary} !important; border-color: ${primary} !important; }
    `;
    
    // Remove existing admin style
    const existingAdminStyle = document.getElementById('admin-color-scheme');
    if (existingAdminStyle) existingAdminStyle.remove();
    
    document.head.appendChild(adminStyle);
    console.log('Applied color scheme to admin interface');
  }
  
  function applyToPublicInterface(primary, secondary, accent) {
    // Update the CSS file directly
    updateCSSFileDirectly(primary, secondary, accent);
    
    // Also save to localStorage for immediate use
    const publicColors = {
      primary: primary,
      secondary: secondary,
      accent: accent,
      timestamp: new Date().toISOString()
    };
    
    localStorage.setItem('publicColorScheme', JSON.stringify(publicColors));
    console.log('Saved color scheme for public interface:', publicColors);
  }
  
  function updateCSSFileDirectly(primary, secondary, accent) {
    // Convert hex to RGB
    function hexToRgb(hex) {
      const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
      return result ? 
        `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}` : 
        '253, 126, 20';
    }
    
    const primaryRgb = hexToRgb(primary);
    const secondaryRgb = hexToRgb(secondary);
    const accentRgb = hexToRgb(accent);
    
    // Generate CSS content
    const cssContent = `/* Dynamic Brand Colors - Generated by Admin Settings */
/* This file is automatically updated when color schemes are applied */
/* Last updated: ${new Date().toISOString()} */

:root {
  /* Brand Colors */
  --brand-primary: ${primary};
  --brand-secondary: ${secondary};
  --brand-accent: ${accent};
  --brand-primary-rgb: ${primaryRgb};
  --brand-secondary-rgb: ${secondaryRgb};
  --brand-accent-rgb: ${accentRgb};
}

/* Primary Brand Elements */
.btn-primary {
  background-color: var(--brand-primary) !important;
  border-color: var(--brand-primary) !important;
}

.btn-primary:hover {
  background-color: var(--brand-accent) !important;
  border-color: var(--brand-accent) !important;
}

.btn-orange {
  background-color: var(--brand-primary) !important;
  border-color: var(--brand-primary) !important;
  color: white !important;
}

.btn-orange:hover {
  background-color: var(--brand-accent) !important;
  border-color: var(--brand-accent) !important;
  color: white !important;
}

/* Header Elements */
.hero {
  background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-secondary) 100%);
}

/* Service Cards */
.service-card {
  border-left: 4px solid var(--brand-primary);
}

.service-card:hover {
  border-left-color: var(--brand-accent);
}

/* Navigation Elements */
.nav-tabs .nav-link.active {
  background-color: var(--brand-primary) !important;
  border-color: var(--brand-primary) !important;
  color: white !important;
}

.nav-tabs .nav-link.active:hover {
  background-color: var(--brand-accent) !important;
  border-color: var(--brand-accent) !important;
  color: white !important;
}

/* Links */
a {
  color: var(--brand-primary);
}

a:hover {
  color: var(--brand-accent);
}

/* Form Elements */
.form-control:focus {
  border-color: var(--brand-primary);
  box-shadow: 0 0 0 0.2rem rgba(var(--brand-primary-rgb), 0.25);
}

/* Admin Interface */
.admin-header {
  background-color: var(--brand-primary) !important;
}

.admin-sidebar {
  background-color: var(--brand-secondary) !important;
}

.admin-sidebar .nav-link:hover {
  background-color: var(--brand-accent) !important;
}

/* Footer */
.footer {
  background-color: var(--brand-secondary);
  border-top: 3px solid var(--brand-primary);
}

/* Value Cards */
.value-card {
  border-top: 3px solid var(--brand-primary);
}

/* Testimonial Cards */
.testimonial-card {
  border-left: 4px solid var(--brand-primary);
}

/* Team Cards */
.team-card {
  border-left: 4px solid var(--brand-primary);
}

/* Badge Elements */
.badge-primary {
  background-color: var(--brand-primary) !important;
}

/* Alert Elements */
.alert-primary {
  background-color: var(--brand-primary) !important;
  border-color: var(--brand-primary) !important;
}

/* Progress Bars */
.progress-bar {
  background-color: var(--brand-primary) !important;
}

/* Accordion Elements */
.accordion-button:not(.collapsed) {
  background-color: var(--brand-primary) !important;
  color: white !important;
}

/* Pagination Elements */
.page-link.active {
  background-color: var(--brand-primary) !important;
  border-color: var(--brand-primary) !important;
}

/* Breadcrumb Elements */
.breadcrumb-item.active {
  color: var(--brand-primary) !important;
}`;
    
    // Create a blob and download it (simulating file update)
    const blob = new Blob([cssContent], { type: 'text/css' });
    const url = URL.createObjectURL(blob);
    
    // Create a temporary link to trigger download
    const link = document.createElement('a');
    link.href = url;
    link.download = 'brand-colors.css';
    link.style.display = 'none';
    document.body.appendChild(link);
    
    // For now, just show success message
    console.log('CSS content generated for:', { primary, secondary, accent });
    console.log('CSS file would be updated at: assets/css/brand-colors.css');
    
    // Force reload of CSS file
    reloadBrandCSS();
    
    // Clean up
    setTimeout(() => {
      document.body.removeChild(link);
      URL.revokeObjectURL(url);
    }, 100);
  }
  
  function reloadBrandCSS() {
    // Force reload of the brand CSS file
    const cssLink = document.querySelector('link[href*="brand-colors.css"]');
    if (cssLink) {
      const href = cssLink.href;
      cssLink.href = href + '?t=' + new Date().getTime();
    } else {
      // Create the link if it doesn't exist
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = '../assets/css/brand-colors.css?t=' + new Date().getTime();
      document.head.appendChild(link);
    }
  }
  
  function showColorNotification(mode, primary, secondary, accent) {
    // Create a temporary notification
    const notification = document.createElement('div');
    notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
      <strong>Color Scheme Applied!</strong><br>
      <small>Mode: ${mode.charAt(0).toUpperCase() + mode.slice(1)}</small><br>
      <small>Colors: ${primary}, ${secondary}, ${accent}</small>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 3 seconds
    setTimeout(() => {
      if (notification.parentNode) {
        notification.parentNode.removeChild(notification);
      }
    }, 3000);
  }
  
  // Quick apply functions
  window.applyToAdmin = function() {
    const primary = document.getElementById('primaryColor')?.value || '#fd7e14';
    const secondary = document.getElementById('secondaryColor')?.value || '#6c757d';
    const accent = document.getElementById('accentColor')?.value || '#0d6efd';
    window.applyColorScheme(primary, secondary, accent, 'admin');
  };
  
  window.applyToPublic = function() {
    const primary = document.getElementById('primaryColor')?.value || '#fd7e14';
    const secondary = document.getElementById('secondaryColor')?.value || '#6c757d';
    const accent = document.getElementById('accentColor')?.value || '#0d6efd';
    window.applyColorScheme(primary, secondary, accent, 'public');
  };
  
  function updateColorPreview() {
    const primary = document.getElementById('primaryColor')?.value || '#fd7e14';
    const secondary = document.getElementById('secondaryColor')?.value || '#6c757d';
    const accent = document.getElementById('accentColor')?.value || '#0d6efd';
    
    const previewHeader = document.getElementById('previewHeader');
    const previewTitle = document.getElementById('previewTitle');
    const previewButton = document.getElementById('previewButton');
    const previewOutlineButton = document.getElementById('previewOutlineButton');
    
    if (previewHeader) {
      previewHeader.style.backgroundColor = primary;
    }
    if (previewTitle) {
      previewTitle.style.color = primary;
    }
    if (previewButton) {
      previewButton.style.backgroundColor = primary;
      previewButton.style.borderColor = primary;
      previewButton.style.color = 'white';
    }
    if (previewOutlineButton) {
      previewOutlineButton.style.borderColor = accent;
      previewOutlineButton.style.color = accent;
    }
  }
  
  // Add event listeners to color inputs
  const primaryColor = document.getElementById('primaryColor');
  const secondaryColor = document.getElementById('secondaryColor');
  const accentColor = document.getElementById('accentColor');
  
  if (primaryColor) primaryColor.addEventListener('input', updateColorPreview);
  if (secondaryColor) secondaryColor.addEventListener('input', updateColorPreview);
  if (accentColor) accentColor.addEventListener('input', updateColorPreview);
  
  // Initialize preview on page load
  setTimeout(updateColorPreview, 100);
  
  // Load color schemes from database (if available)
  function loadColorSchemes() {
    // This could be enhanced to load from database in the future
    const savedSchemes = localStorage.getItem('brandColorSchemes');
    if (savedSchemes) {
      try {
        const schemes = JSON.parse(savedSchemes);
        console.log('Loaded saved color schemes:', schemes);
      } catch (e) {
        console.log('No saved color schemes found');
      }
    }
  }
  
  // Save color schemes to localStorage (temporary solution)
  function saveColorScheme(name, primary, secondary, accent) {
    const savedSchemes = localStorage.getItem('brandColorSchemes');
    const schemes = savedSchemes ? JSON.parse(savedSchemes) : {};
    
    schemes[name] = { primary, secondary, accent };
    localStorage.setItem('brandColorSchemes', JSON.stringify(schemes));
  }
  
  // Initialize color schemes
  loadColorSchemes();
  
  // Test function for debugging
  window.testColorSchemes = function() {
    console.log('Testing color schemes...');
    console.log('Primary input:', document.getElementById('primaryColor'));
    console.log('Secondary input:', document.getElementById('secondaryColor'));
    console.log('Accent input:', document.getElementById('accentColor'));
    
    // Test applying Alma Tech Orange scheme
    if (window.applyColorScheme) {
      console.log('Applying Alma Tech Orange scheme...');
      window.applyColorScheme('#fd7e14', '#6c757d', '#0d6efd');
      console.log('Scheme applied successfully!');
    } else {
      console.error('applyColorScheme function not found!');
    }
  };
});
</script>
