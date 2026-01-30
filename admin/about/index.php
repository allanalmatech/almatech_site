<?php
declare(strict_types=1);

// Early authentication check before any HTML output
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_admin_login();

require_once __DIR__ . '/../../includes/db.php';

$db = $GLOBALS['db'] ?? ($mysqli ?? null);
$errors = [];
$loaded_from_db = false;

// Graceful DB check
if (!($db instanceof mysqli)) {
  $page_title = "About Settings | Admin";
  $page_heading = "About Settings";
  $page_subtitle = "Manage about page content";
  $active_admin = "about";

  require_once __DIR__ . '/../includes/admin_header.php';
  require_once __DIR__ . '/../includes/admin_sidebar.php';
  ?>
  <div class="admin-main">
    <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>
    <div class="admin-card p-4">
      <div class="alert alert-danger mb-0">
        <div class="fw-semibold mb-1">Database unreachable</div>
        <div class="small">We can’t load or save About settings right now because the database connection is not available.</div>
      </div>
    </div>
  </div>
  <?php
  require_once __DIR__ . '/../includes/admin_footer.php';
  exit;
}

// Defaults if not found
$about = [
  'hero_title'    => 'About Us',
  'hero_subtitle' => '',
  'story'         => '',
  'mission'       => '',
  'vision'        => '',
  'values'        => ''
];

// -------------------------------
// LOAD from DB (NO mysqlnd needed)
// -------------------------------
try {
  $sql = "SELECT `value` FROM settings WHERE `key`=? LIMIT 1";
  $stmt = $db->prepare($sql);
  if (!$stmt) {
    throw new RuntimeException("Prepare failed: " . $db->error);
  }

  $k = 'about_settings';
  $stmt->bind_param("s", $k);
  $stmt->execute();
  $stmt->bind_result($val);
  if ($stmt->fetch()) {
    $decoded = json_decode((string)$val, true);
    if (is_array($decoded)) {
      $about = array_merge($about, $decoded);
      $loaded_from_db = true;
    }
  }
  $stmt->close();
} catch (Throwable $e) {
  $errors[] = "Failed to load About settings: " . $e->getMessage();
}

// -------------------------------
// SAVE
// -------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = (string)($_POST['csrf_token'] ?? '');

  if (!csrf_validate($token)) {
    $errors[] = "Security check failed. Please refresh and try again.";
  } else {
    $about['hero_title']    = trim((string)($_POST['hero_title'] ?? ''));
    $about['hero_subtitle'] = trim((string)($_POST['hero_subtitle'] ?? ''));
    $about['story']         = trim((string)($_POST['story'] ?? ''));
    $about['mission']       = trim((string)($_POST['mission'] ?? ''));
    $about['vision']        = trim((string)($_POST['vision'] ?? ''));
    $about['values']        = trim((string)($_POST['values'] ?? ''));

    if ($about['hero_title'] === '') {
      $errors[] = "Hero title is required.";
    } else {
      try {
        $jsonValue = json_encode($about, JSON_UNESCAPED_UNICODE);
        if ($jsonValue === false) {
          throw new RuntimeException("JSON encode failed.");
        }

        $sql = "
          INSERT INTO settings (`key`, `value`, `updated_at`)
          VALUES ('about_settings', ?, NOW())
          ON DUPLICATE KEY UPDATE
            `value` = VALUES(`value`),
            `updated_at` = NOW()
        ";
        $stmt = $db->prepare($sql);
        if (!$stmt) {
          throw new RuntimeException("Prepare failed: " . $db->error);
        }

        $stmt->bind_param("s", $jsonValue);
        $stmt->execute();
        $stmt->close();

        flash_set('success', 'About settings updated successfully.');
        redirect('index.php');
      } catch (Throwable $e) {
        $errors[] = "Failed to save settings: " . $e->getMessage();
      }
    }
  }
}

$page_title = "About Settings | Admin";
$page_heading = "About Settings";
$page_subtitle = "Manage about page content (Team & Testimonials are managed separately)";
$active_admin = "about";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
?>
<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="mb-0">About Settings</h4>
        <small class="text-muted">
          This editor updates <code>settings</code> → <code>key=about_settings</code>.
          Team Members & Testimonials remain in their own modules.
        </small>

        <?php if (!$loaded_from_db): ?>
          <div class="alert alert-warning mt-2 mb-0">
            <small><strong>Note:</strong> Nothing loaded from DB yet (using defaults). Save to create/update DB record.</small>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Fix the following:</div>
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= h($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Hero Title *</label>
          <input type="text" name="hero_title" value="<?= h((string)$about['hero_title']) ?>" class="form-control" placeholder="Enter hero title..." required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Hero Subtitle</label>
          <input class="form-control" name="hero_subtitle" value="<?= h((string)$about['hero_subtitle']) ?>">
        </div>

        <div class="col-12">
          <label class="form-label">Our Story</label>
          <textarea class="form-control" name="story" rows="6"><?= h((string)$about['story']) ?></textarea>
          <div class="form-text">This shows under the hero section if provided.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Mission</label>
          <textarea class="form-control" name="mission" rows="5"><?= h((string)$about['mission']) ?></textarea>
        </div>

        <div class="col-md-6">
          <label class="form-label">Vision</label>
          <textarea class="form-control" name="vision" rows="5"><?= h((string)$about['vision']) ?></textarea>
        </div>

        <div class="col-12">
          <label class="form-label">Values</label>
          <div id="valuesContainer" class="border rounded p-3 mb-3">
            <!-- Values will be rendered here -->
          </div>
          <button type="button" class="btn btn-sm btn-outline-primary mb-2" onclick="addValue()">
            <i class="bi bi-plus-circle me-1"></i> Add Value
          </button>
          <textarea name="values" id="valuesTextarea" class="form-control d-none" rows="5"><?= h((string)$about['values']) ?></textarea>
          <div class="form-text">Add company values with custom icons and descriptions.</div>
        </div>
      </div>

      <div class="mt-3 d-flex gap-2">
        <button class="btn btn-orange">
          <i class="bi bi-save me-1"></i> Save Settings
        </button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

<!-- Icon Chooser Modal -->
<div class="modal fade" id="iconChooserModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Choose Icon</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" class="form-control mb-3" placeholder="Search icons..." id="iconSearch">
        <div id="iconGrid" class="row g-2" style="max-height: 400px; overflow-y: auto;">
          <!-- Icons will be loaded here -->
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

<script>
// Popular Bootstrap Icons for chooser
const popularIcons = [
  'bi-star-fill', 'bi-heart-fill', 'bi-shield-check', 'bi-award-fill',
  'bi-lightning-fill', 'bi-trophy-fill', 'bi-gem', 'bi-flag-fill',
  'bi-check-circle-fill', 'bi-exclamation-circle-fill', 'bi-info-circle-fill',
  'bi-headset', 'bi-people-fill', 'bi-briefcase-fill', 'bi-house-fill',
  'bi-envelope-fill', 'bi-phone-fill', 'bi-globe', 'bi-map-pin',
  'bi-clock-fill', 'bi-calendar-fill', 'bi-graph-up', 'bi-currency-dollar',
  'bi-tools', 'bi-wrench', 'bi-gear-fill', 'bi-lightbulb-fill',
  'bi-book-fill', 'bi-mortarboard-fill', 'bi-palette-fill', 'bi-camera-fill'
];

let currentValueIndex = 0;

// Parse and render values on page load
function renderValues() {
  const container = document.getElementById('valuesContainer');
  const textarea = document.getElementById('valuesTextarea');
  const values = textarea.value.trim().split('\n').filter(v => v.trim());
  
  container.innerHTML = '';
  
  values.forEach((value, index) => {
    const parts = value.split(':').map(p => p.trim());
    const name = parts[0] || '';
    const description = parts[1] || '';
    const icon = parts[2] || 'bi-check2-circle';
    
    const valueHtml = `
      <div class="card mb-3" data-index="${index}">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Value Name</label>
              <input type="text" class="form-control value-name" value="${name}" placeholder="e.g., Integrity">
            </div>
            <div class="col-md-5">
              <label class="form-label">Description</label>
              <input type="text" class="form-control value-description" value="${description}" placeholder="e.g., We maintain honesty in all dealings">
            </div>
            <div class="col-md-3">
              <label class="form-label">Icon</label>
              <div class="input-group">
                <input type="text" class="form-control value-icon" value="${icon}" readonly>
                <button type="button" class="btn btn-outline-secondary" onclick="openIconChooser(${index})">
                  <i class="bi bi-search"></i>
                </button>
              </div>
              <div class="mt-1">
                <i class="${icon} me-1"></i> Preview
              </div>
            </div>
            <div class="col-md-1">
              <label class="form-label">&nbsp;</label><br>
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeValue(${index})">
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    `;
    container.innerHTML += valueHtml;
  });
  
  currentValueIndex = values.length;
}

// Add new value
function addValue() {
  const container = document.getElementById('valuesContainer');
  const index = currentValueIndex++;
  
  const valueHtml = `
    <div class="card mb-3" data-index="${index}">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Value Name</label>
            <input type="text" class="form-control value-name" placeholder="e.g., Integrity">
          </div>
          <div class="col-md-5">
            <label class="form-label">Description</label>
            <input type="text" class="form-control value-description" placeholder="e.g., We maintain honesty in all dealings">
          </div>
          <div class="col-md-3">
            <label class="form-label">Icon</label>
            <div class="input-group">
              <input type="text" class="form-control value-icon" value="bi-check2-circle" readonly>
              <button type="button" class="btn btn-outline-secondary" onclick="openIconChooser(${index})">
                <i class="bi bi-search"></i>
              </button>
            </div>
            <div class="mt-1">
              <i class="bi-check2-circle me-1"></i> Preview
            </div>
          </div>
          <div class="col-md-1">
            <label class="form-label">&nbsp;</label><br>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeValue(${index})">
              <i class="bi bi-trash"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  `;
  container.innerHTML += valueHtml;
}

// Remove value
function removeValue(index) {
  const card = document.querySelector(`[data-index="${index}"]`);
  if (card) {
    card.remove();
    updateTextarea();
  }
}

// Open icon chooser
function openIconChooser(index) {
  currentValueIndex = index;
  const modal = new bootstrap.Modal(document.getElementById('iconChooserModal'));
  loadIcons();
  modal.show();
}

// Load icons into chooser
function loadIcons() {
  const grid = document.getElementById('iconGrid');
  const search = document.getElementById('iconSearch');
  
  grid.innerHTML = '';
  
  const filteredIcons = popularIcons.filter(icon => 
    icon.toLowerCase().includes(search.value.toLowerCase())
  );
  
  filteredIcons.forEach(icon => {
    const iconHtml = `
      <div class="col-2 text-center">
        <button type="button" class="btn btn-outline-secondary w-100 p-2" onclick="selectIcon('${icon}')">
          <i class="${icon} fs-4"></i>
          <div class="small text-muted mt-1">${icon.replace('bi-', '')}</div>
        </button>
      </div>
    `;
    grid.innerHTML += iconHtml;
  });
}

// Select icon
function selectIcon(icon) {
  const cards = document.querySelectorAll('#valuesContainer .card');
  cards.forEach(card => {
    const index = parseInt(card.dataset.index);
    if (index === currentValueIndex) {
      const iconInput = card.querySelector('.value-icon');
      const preview = card.querySelector('.mt-1 i');
      iconInput.value = icon;
      preview.className = `${icon} me-1`;
    }
  });
  
  const modal = bootstrap.Modal.getInstance(document.getElementById('iconChooserModal'));
  modal.hide();
  updateTextarea();
}

// Update textarea with current values
function updateTextarea() {
  const cards = document.querySelectorAll('#valuesContainer .card');
  const values = [];
  
  cards.forEach(card => {
    const name = card.querySelector('.value-name').value.trim();
    const description = card.querySelector('.value-description').value.trim();
    const icon = card.querySelector('.value-icon').value.trim();
    
    if (name) {
      values.push(`${name}:${description}:${icon}`);
    }
  });
  
  document.getElementById('valuesTextarea').value = values.join('\n');
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
  renderValues();
  
  // Add event listeners for real-time updates
  document.getElementById('valuesContainer').addEventListener('input', function(e) {
    if (e.target.classList.contains('value-name') || 
        e.target.classList.contains('value-description')) {
      updateTextarea();
    }
  });
  
  // Icon search
  document.getElementById('iconSearch').addEventListener('input', loadIcons);
});
</script>
