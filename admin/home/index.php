<?php
// admin/home/index.php
declare(strict_types=1);

$page_title = "Home Page Settings | Admin";
$page_heading = "Home Page";
$page_subtitle = "Edit hero, services preview, stats and CTA";
$active_admin = "home";

// Load config first (BASE_URL etc.)
require_once __DIR__ . '/../../config.php';

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../includes/settings_lib.php';

csrf_init();
$db = $GLOBALS['db'] ?? $mysqli ?? null;
if (!($db instanceof mysqli)) { http_response_code(500); exit('DB not available'); }

// Load current settings
$home = setting_get_json($db, 'home_settings', []);
if (!is_array($home)) $home = [];

// ---------------- Slider cleanup ----------------
$slider = $home['slider'] ?? [];
if (!is_array($slider)) $slider = [];

$slider['enabled'] = !empty($slider['enabled']);
$slider['interval'] = (int)($slider['interval'] ?? 5000);
if ($slider['interval'] < 1000) $slider['interval'] = 1000;
if ($slider['interval'] > 20000) $slider['interval'] = 20000;

$slider['transition'] = (string)($slider['transition'] ?? 'fade');
if (!in_array($slider['transition'], ['fade','slide'], true)) $slider['transition'] = 'fade';

$slider['min_height'] = (int)($slider['min_height'] ?? 420);
if ($slider['min_height'] < 260) $slider['min_height'] = 260;
if ($slider['min_height'] > 900) $slider['min_height'] = 900;

$slider['overlay_enabled'] = !empty($slider['overlay_enabled']);

$slider['overlay_opacity'] = (float)($slider['overlay_opacity'] ?? 0.45);
if ($slider['overlay_opacity'] < 0) $slider['overlay_opacity'] = 0;
if ($slider['overlay_opacity'] > 1) $slider['overlay_opacity'] = 1;

$slider['overlay_gradient'] = (string)($slider['overlay_gradient'] ?? 'linear-gradient(120deg, rgba(0,0,0,.65), rgba(0,0,0,.15))');

$slider['show_indicators'] = !empty($slider['show_indicators']);
$slider['show_arrows'] = !empty($slider['show_arrows']);

$slides = $slider['slides'] ?? [];
if (!is_array($slides)) $slides = [];

// Ensure we have at least 3 slide rows for UI
$rows = max(3, count($slides));
while (count($slides) < $rows) {
  $slides[] = [
    'is_active' => false,
    'sort_order' => count($slides) + 1,
    'image' => '',
    'alt' => '',
    'caption_title' => '',
    'caption_text' => '',
    'btn1_text' => '',
    'btn1_link' => '',
    'btn2_text' => '',
    'btn2_link' => '',
    'caption_align' => 'left',
  ];
}

$csrf = csrf_token();

// quick services textarea value
$quickServices = '';
$qs = $home['quick_request']['services'] ?? [];
if (is_array($qs)) {
  $quickServices = implode("\r\n", $qs);
}

$errors = [];
$ok = false;
?>

<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <div class="admin-card p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h4 class="mb-0">Home Page Settings</h4>
        <div class="text-muted small">Update what appears on index.php.</div>
      </div>
      <a class="btn btn-outline-secondary" target="_blank" href="<?= h(BASE_URL) ?>/index.php">Preview</a>
    </div>

    <?php if ($ok): ?>
      <div class="alert alert-success">Saved successfully.</div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <div class="fw-semibold mb-1">Fix the following:</div>
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="post" action="save.php" class="row g-3" enctype="multipart/form-data">
  <input type="hidden" name="csrf" value="<?= h($csrf) ?>">

  <!-- Quick jump icon tabs -->
  <div class="col-12">
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-sm btn-outline-secondary" href="#secHero"><i class="bi bi-stars me-1"></i>Hero</a>
      <a class="btn btn-sm btn-outline-secondary" href="#secSlider"><i class="bi bi-images me-1"></i>Slider</a>
      <a class="btn btn-sm btn-outline-secondary" href="#secQuick"><i class="bi bi-send me-1"></i>Quick</a>
      <a class="btn btn-sm btn-outline-secondary" href="#secServices"><i class="bi bi-grid me-1"></i>Services</a>
      <a class="btn btn-sm btn-outline-secondary" href="#secStats"><i class="bi bi-bar-chart me-1"></i>Stats</a>
      <a class="btn btn-sm btn-outline-secondary" href="#secCTA"><i class="bi bi-megaphone me-1"></i>CTA</a>

      <div class="ms-auto">
        
      </div>
    </div>
  </div>

  <!-- Accordion container -->
  <div class="col-12">
    <div class="accordion" id="homeSettingsAcc">

      <!-- ================= HERO ================= -->
      <div class="accordion-item" id="secHero">
        <h2 class="accordion-header">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#heroPanel">
            <i class="bi bi-stars me-2"></i> Hero
          </button>
        </h2>
        <div id="heroPanel" class="accordion-collapse collapse show" data-bs-parent="#homeSettingsAcc">
          <div class="accordion-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">Badge Icon</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-emoji-smile"></i></span>
                  <input class="form-control" name="home[hero][badge_icon]" value="<?= h((string)($home['hero']['badge_icon'] ?? '')) ?>" placeholder="bi bi-lightning-charge-fill">
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Badge Text</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-type"></i></span>
                  <input class="form-control" name="home[hero][badge_text]" value="<?= h((string)($home['hero']['badge_text'] ?? '')) ?>" placeholder="Fast, reliable ICT & digital solutions">
                </div>
              </div>

              <div class="col-12">
                <label class="form-label">Hero Title</label>
                <input class="form-control" name="home[hero][title]" value="<?= h((string)($home['hero']['title'] ?? '')) ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Hero Subtitle</label>
                <textarea class="form-control" rows="2" name="home[hero][subtitle]"><?= h((string)($home['hero']['subtitle'] ?? '')) ?></textarea>
              </div>

              <div class="col-md-3">
                <label class="form-label">Button 1</label>
                <input class="form-control mb-2" name="home[hero][btn1_text]" value="<?= h((string)($home['hero']['btn1_text'] ?? '')) ?>" placeholder="Text">
                <input class="form-control" name="home[hero][btn1_link]" value="<?= h((string)($home['hero']['btn1_link'] ?? '')) ?>" placeholder="Link">
              </div>
              <div class="col-md-3">
                <label class="form-label">Button 2</label>
                <input class="form-control mb-2" name="home[hero][btn2_text]" value="<?= h((string)($home['hero']['btn2_text'] ?? '')) ?>" placeholder="Text">
                <input class="form-control" name="home[hero][btn2_link]" value="<?= h((string)($home['hero']['btn2_link'] ?? '')) ?>" placeholder="Link">
              </div>

              <div class="col-md-6">
                <div class="alert alert-light border small mb-0">
                  <i class="bi bi-info-circle me-1"></i>
                  Use Bootstrap Icons classes e.g. <code>bi bi-lightning-charge-fill</code>
                </div>
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- ================= SLIDER ================= -->
      <div class="accordion-item mt-2" id="secSlider">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sliderPanel">
            <i class="bi bi-images me-2"></i> Home Slider
          </button>
        </h2>
        <div id="sliderPanel" class="accordion-collapse collapse" data-bs-parent="#homeSettingsAcc">
          <div class="accordion-body">
            <div class="row g-3">

              <div class="col-12">
                <div class="d-flex flex-wrap align-items-center gap-3">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="sliderEnabled"
                           name="home[slider][enabled]" value="1"
                           <?= !empty($home['slider']['enabled']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="sliderEnabled">
                      <strong>Enable slider</strong>
                    </label>
                  </div>

                  <div class="ms-auto d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" type="button" id="btnAddSlide">
                      <i class="bi bi-plus-circle me-1"></i> Add slide
                    </button>
                    <button class="btn btn-sm btn-success" type="button" id="btnApplySlider">
                      <i class="bi bi-check-circle me-1"></i> Apply Changes
                    </button>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= h(BASE_URL) ?>/index.php" target="_blank">
                      <i class="bi bi-eye me-1"></i> Preview
                    </a>
                  </div>
                </div>
              </div>

              <!-- slider settings compact -->
              <div class="col-md-3">
                <label class="form-label"><i class="bi bi-hourglass-split me-1"></i> Interval (ms)</label>
                <input class="form-control" type="number" min="1000" max="20000"
                       name="home[slider][interval]"
                       value="<?= h((string)($home['slider']['interval'] ?? 5000)) ?>">
              </div>

              <div class="col-md-3">
                <label class="form-label"><i class="bi bi-droplet-half me-1"></i> Transition</label>
                <?php $tr = (string)($home['slider']['transition'] ?? 'fade'); ?>
                <select class="form-select" name="home[slider][transition]">
                  <option value="fade" <?= $tr==='fade'?'selected':'' ?>>Fade</option>
                  <option value="slide" <?= $tr==='slide'?'selected':'' ?>>Slide</option>
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label"><i class="bi bi-arrows-expand me-1"></i> Min height (px)</label>
                <input class="form-control" type="number" min="260" max="900"
                       name="home[slider][min_height]"
                       value="<?= h((string)($home['slider']['min_height'] ?? 420)) ?>">
              </div>

              <div class="col-md-3">
                <label class="form-label"><i class="bi bi-toggles me-1"></i> Controls</label>
                <div class="d-flex gap-3">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="sliderIndicators" name="home[slider][show_indicators]" value="1"
                      <?= !empty($home['slider']['show_indicators']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="sliderIndicators">Dots</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="sliderArrows" name="home[slider][show_arrows]" value="1"
                      <?= !empty($home['slider']['show_arrows']) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="sliderArrows">Arrows</label>
                  </div>
                </div>
              </div>

              <!-- overlay -->
              <div class="col-12">
                <div class="border rounded p-3">
                  <div class="d-flex align-items-center justify-content-between">
                    <div class="fw-semibold"><i class="bi bi-layers me-1"></i> Overlay</div>
                    <div class="form-check form-switch">
                      <input class="form-check-input" type="checkbox" id="overlayEnabled" name="home[slider][overlay_enabled]" value="1"
                        <?= !empty($home['slider']['overlay_enabled']) ? 'checked' : '' ?>>
                      <label class="form-check-label" for="overlayEnabled">Enable</label>
                    </div>
                  </div>

                  <div class="row g-2 mt-1">
                    <div class="col-md-3">
                      <label class="form-label">Opacity</label>
                      <input class="form-control" type="number" step="0.05" min="0" max="1"
                             name="home[slider][overlay_opacity]"
                             value="<?= h((string)($home['slider']['overlay_opacity'] ?? 0.45)) ?>">
                    </div>
                    <div class="col-md-9">
                      <label class="form-label">Gradient CSS</label>
                      <input class="form-control"
                             name="home[slider][overlay_gradient]"
                             value="<?= h((string)($home['slider']['overlay_gradient'] ?? 'linear-gradient(120deg, rgba(0,0,0,.65), rgba(0,0,0,.15))')) ?>">
                      <div class="small text-muted mt-1">
                        Example: <code>linear-gradient(120deg, rgba(0,0,0,.65), rgba(0,0,0,.15))</code>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Slides (drag + compact cards) -->
              <?php
                $slides = $home['slider']['slides'] ?? [];
                if (!is_array($slides)) $slides = [];
                $rows = max(3, count($slides)); // cleaner default
              ?>

              <div class="col-12">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="fw-semibold"><i class="bi bi-collection-play me-1"></i> Slides</div>
                  <div class="small text-muted">Drag to reorder • Click a slide to edit</div>
                </div>

                <div class="mt-2" id="slidesList">
                  <?php for ($i=0; $i<$rows; $i++):
                    $s = $slides[$i] ?? [
                      'is_active' => false,
                      'sort_order' => $i+1,
                      'image' => '',
                      'alt' => '',
                      'caption_title' => '',
                      'caption_text' => '',
                      'btn1_text' => '',
                      'btn1_link' => '',
                      'btn2_text' => '',
                      'btn2_link' => '',
                      'caption_align' => 'left',
                    ];
                    $imgVal = (string)($s['image'] ?? '');
                    $imgPreview = $imgVal ? (strpos($imgVal,'uploads/')===0 ? (rtrim((string)BASE_URL, '/').'/'.$imgVal) : (rtrim((string)BASE_URL, '/').'/uploads/slider/'.ltrim($imgVal,'/'))) : '';
                  ?>
                  <div class="slide-card border rounded p-2 mb-2" data-slide-card>
                    <div class="d-flex align-items-center gap-2">
                      <button type="button" class="btn btn-sm btn-light slide-handle" title="Drag">
                        <i class="bi bi-grip-vertical"></i>
                      </button>

                      <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox"
                               name="slides[<?= $i ?>][is_active]" value="1"
                               <?= !empty($s['is_active']) ? 'checked' : '' ?>>
                      </div>

                      <div class="slide-thumb">
                        <?php if ($imgPreview): ?>
                          <img src="<?= h($imgPreview) ?>" alt="" class="slide-thumb-img">
                        <?php else: ?>
                          <div class="slide-thumb-fallback"><i class="bi bi-image"></i></div>
                        <?php endif; ?>
                      </div>

                      <div class="flex-grow-1">
                        <div class="fw-semibold small">
                          <?= h((string)($s['caption_title'] ?: 'Slide '.($i+1))) ?>
                        </div>
                        <div class="text-muted small">
                          <?= h((string)($s['caption_text'] ?: 'No caption text')) ?>
                        </div>
                      </div>

                      <div class="d-flex align-items-center gap-2">
                        <input class="form-control form-control-sm" style="width:90px"
                               type="number" name="slides[<?= $i ?>][sort_order]"
                               value="<?= h((string)($s['sort_order'] ?? ($i+1))) ?>"
                               title="Order">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-slide-toggle>
                          <i class="bi bi-sliders"></i>
                        </button>
                      </div>
                    </div>

                    <!-- Details -->
                    <div class="slide-details mt-2 d-none" data-slide-details>
  <div class="row g-3">

    <!-- Image + Preview -->
    <?php
      $imgVal = (string)($s['image'] ?? '');
      $imgPublic = $imgVal;
      if ($imgPublic !== '' && strpos($imgPublic, 'uploads/') !== 0) {
        $imgPublic = 'uploads/slider/' . ltrim($imgPublic, '/');
      }
      $imgPreviewUrl = $imgPublic ? (BASE_URL . $imgPublic) : '';
      $imgExists = $imgPublic && is_file(__DIR__ . '/../../' . $imgPublic);
    ?>
    <div class="col-12">
      <div class="d-flex align-items-center justify-content-between">
        <label class="form-label small mb-0">Image</label>

        <div class="d-flex gap-2">
          <!-- Upload trigger -->
          <button class="btn btn-sm btn-outline-secondary" type="button" data-upload-image title="Upload image">
            <i class="bi bi-upload"></i>
          </button>

          <!-- Gallery picker -->
          <button class="btn btn-sm btn-outline-secondary" type="button" data-pick-image title="Choose from gallery">
            <i class="bi bi-images"></i>
          </button>

          <!-- Clear -->
          <button class="btn btn-sm btn-outline-danger" type="button" data-clear-image title="Clear image">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
      </div>

      <div class="d-flex gap-3 align-items-start mt-2">
        <!-- Preview -->
        <div class="border rounded overflow-hidden" style="width:84px;height:56px;flex:0 0 auto;background:#f5f6f8;">
          <?php if ($imgExists && $imgPreviewUrl): ?>
            <img src="<?= h($imgPreviewUrl) ?>" alt="Preview" style="width:100%;height:100%;object-fit:cover;display:block;">
          <?php else: ?>
            <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
              <i class="bi bi-image"></i>
            </div>
          <?php endif; ?>
        </div>

        <!-- Path input -->
        <div class="flex-grow-1">
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
            <input
              class="form-control"
              name="slides[<?= $i ?>][image]"
              value="<?= h($imgVal) ?>"
              placeholder="Choose from gallery or upload (auto saved to /uploads/slider/)"
              data-slide-image-input>
          </div>

          <input type="file" accept="image/*" class="d-none" name="slide_image_<?= $i ?>" data-image-file>

          <div class="small text-muted mt-1">
            Tip: You can reuse images from the gallery. Uploads go to <code>/uploads/slider/</code>.
          </div>
        </div>
      </div>
    </div>

    <!-- Alt text -->
    <div class="col-md-6">
      <label class="form-label small">Alt text</label>
      <div class="input-group input-group-sm">
        <span class="input-group-text"><i class="bi bi-type"></i></span>
        <input class="form-control" name="slides[<?= $i ?>][alt]" value="<?= h((string)($s['alt'] ?? '')) ?>" placeholder="Describe the image">
      </div>
    </div>

    <!-- Align -->
    <div class="col-md-6">
      <label class="form-label small">Caption Align</label>
      <?php $al = (string)($s['caption_align'] ?? 'left'); ?>
      <div class="input-group input-group-sm">
        <span class="input-group-text"><i class="bi bi-text-left"></i></span>
        <select class="form-select" name="slides[<?= $i ?>][caption_align]">
          <option value="left" <?= $al==='left'?'selected':'' ?>>Left</option>
          <option value="center" <?= $al==='center'?'selected':'' ?>>Center</option>
          <option value="right" <?= $al==='right'?'selected':'' ?>>Right</option>
        </select>
      </div>
    </div>

    <!-- Caption -->
    <div class="col-md-6">
      <label class="form-label small">Caption Title</label>
      <div class="input-group input-group-sm">
        <span class="input-group-text"><i class="bi bi-chat-left-text"></i></span>
        <input class="form-control" name="slides[<?= $i ?>][caption_title]" value="<?= h((string)($s['caption_title'] ?? '')) ?>" placeholder="Headline">
      </div>
    </div>

    <div class="col-md-6">
      <label class="form-label small">Caption Text</label>
      <div class="input-group input-group-sm">
        <span class="input-group-text"><i class="bi bi-paragraph"></i></span>
        <input class="form-control" name="slides[<?= $i ?>][caption_text]" value="<?= h((string)($s['caption_text'] ?? '')) ?>" placeholder="Short supporting text">
      </div>
    </div>

    <!-- Buttons -->
    <div class="col-md-6">
      <label class="form-label small">Button 1</label>
      <div class="row g-2">
        <div class="col-5">
          <input class="form-control form-control-sm" name="slides[<?= $i ?>][btn1_text]" value="<?= h((string)($s['btn1_text'] ?? '')) ?>" placeholder="Text">
        </div>
        <div class="col-7">
          <input class="form-control form-control-sm" name="slides[<?= $i ?>][btn1_link]" value="<?= h((string)($s['btn1_link'] ?? '')) ?>" placeholder="Link e.g services.php">
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <label class="form-label small">Button 2</label>
      <div class="row g-2">
        <div class="col-5">
          <input class="form-control form-control-sm" name="slides[<?= $i ?>][btn2_text]" value="<?= h((string)($s['btn2_text'] ?? '')) ?>" placeholder="Text">
        </div>
        <div class="col-7">
          <input class="form-control form-control-sm" name="slides[<?= $i ?>][btn2_link]" value="<?= h((string)($s['btn2_link'] ?? '')) ?>" placeholder="Link e.g contact.php">
        </div>
      </div>
    </div>

  </div>
</div>

                  </div>
                  <?php endfor; ?>
                </div>

              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- ================= QUICK REQUEST ================= -->
      <div class="accordion-item mt-2" id="secQuick">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#quickPanel">
            <i class="bi bi-send me-2"></i> Quick Request Form
          </button>
        </h2>
        <div id="quickPanel" class="accordion-collapse collapse" data-bs-parent="#homeSettingsAcc">
          <div class="accordion-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">Form Title</label>
                <input class="form-control" name="home[quick_request][title]" value="<?= h((string)($home['quick_request']['title'] ?? '')) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Form Subtitle</label>
                <input class="form-control" name="home[quick_request][subtitle]" value="<?= h((string)($home['quick_request']['subtitle'] ?? '')) ?>">
              </div>
              <div class="col-12">
                <label class="form-label">Services Dropdown (one per line)</label>
                <textarea class="form-control" rows="4" name="quick_services"><?= h($quickServices) ?></textarea>
              </div>
              <div class="col-12">
                <label class="form-label">Consent Text</label>
                <input class="form-control" name="home[quick_request][consent_text]" value="<?= h((string)($home['quick_request']['consent_text'] ?? '')) ?>">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= SERVICES PREVIEW ================= -->
      <div class="accordion-item mt-2" id="secServices">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#servicesPanel">
            <i class="bi bi-grid me-2"></i> Services Preview
          </button>
        </h2>
        <div id="servicesPanel" class="accordion-collapse collapse" data-bs-parent="#homeSettingsAcc">
          <div class="accordion-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">Section Title</label>
                <input class="form-control" name="home[services_preview][title]" value="<?= h((string)($home['services_preview']['title'] ?? '')) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Section Subtitle</label>
                <input class="form-control" name="home[services_preview][subtitle]" value="<?= h((string)($home['services_preview']['subtitle'] ?? '')) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">View All Text</label>
                <input class="form-control" name="home[services_preview][view_all_text]" value="<?= h((string)($home['services_preview']['view_all_text'] ?? '')) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">View All Link</label>
                <input class="form-control" name="home[services_preview][view_all_link]" value="<?= h((string)($home['services_preview']['view_all_link'] ?? '')) ?>">
              </div>

              <?php
              $cards = $home['services_preview']['cards'] ?? [];
              if (!is_array($cards)) $cards = [];
              for ($i=0; $i<4; $i++):
                $c = $cards[$i] ?? ['icon'=>'','title'=>'','desc'=>''];
              ?>
              <div class="col-12">
                <details class="border rounded p-3">
                  <summary class="fw-semibold" style="cursor:pointer">
                    <i class="bi bi-card-text me-1"></i> Card <?= $i+1 ?>
                  </summary>
                  <div class="row g-2 mt-2">
                    <div class="col-md-3">
                      <label class="form-label small">Icon</label>
                      <input class="form-control form-control-sm" name="cards[<?= $i ?>][icon]" value="<?= h((string)($c['icon'] ?? '')) ?>" placeholder="bi bi-globe2">
                    </div>
                    <div class="col-md-3">
                      <label class="form-label small">Title</label>
                      <input class="form-control form-control-sm" name="cards[<?= $i ?>][title]" value="<?= h((string)($c['title'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label small">Description</label>
                      <input class="form-control form-control-sm" name="cards[<?= $i ?>][desc]" value="<?= h((string)($c['desc'] ?? '')) ?>">
                    </div>
                  </div>
                </details>
              </div>
              <?php endfor; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- ================= STATS ================= -->
      <div class="accordion-item mt-2" id="secStats">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#statsPanel">
            <i class="bi bi-bar-chart me-2"></i> Stats
          </button>
        </h2>
        <div id="statsPanel" class="accordion-collapse collapse" data-bs-parent="#homeSettingsAcc">
          <div class="accordion-body">
            <div class="row g-2">
              <?php
              $stats = $home['stats'] ?? [];
              if (!is_array($stats)) $stats = [];
              for ($i=0; $i<4; $i++):
                $st = $stats[$i] ?? ['value'=>'','label'=>''];
              ?>
              <div class="col-md-3">
                <label class="form-label">Stat <?= $i+1 ?></label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-hash"></i></span>
                  <input class="form-control" name="stats[<?= $i ?>][value]" value="<?= h((string)($st['value'] ?? '')) ?>" placeholder="100+">
                </div>
                <input class="form-control mt-2" name="stats[<?= $i ?>][label]" value="<?= h((string)($st['label'] ?? '')) ?>" placeholder="Clients Supported">
              </div>
              <?php endfor; ?>
            </div>
            <div class="small text-muted mt-2">
              Tip: values like <code>100+</code> are okay; the counter animates the number part.
            </div>
          </div>
        </div>
      </div>

      <!-- ================= CTA ================= -->
      <div class="accordion-item mt-2" id="secCTA">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ctaPanel">
            <i class="bi bi-megaphone me-2"></i> CTA
          </button>
        </h2>
        <div id="ctaPanel" class="accordion-collapse collapse" data-bs-parent="#homeSettingsAcc">
          <div class="accordion-body">
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label">CTA Title</label>
                <input class="form-control" name="home[cta][title]" value="<?= h((string)($home['cta']['title'] ?? '')) ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">CTA Button Text</label>
                <input class="form-control" name="home[cta][btn_text]" value="<?= h((string)($home['cta']['btn_text'] ?? '')) ?>">
              </div>
              <div class="col-12">
                <label class="form-label">CTA Subtitle</label>
                <input class="form-control" name="home[cta][subtitle]" value="<?= h((string)($home['cta']['subtitle'] ?? '')) ?>">
              </div>
              <div class="col-12">
                <label class="form-label">CTA Button Link</label>
                <input class="form-control" name="home[cta][btn_link]" value="<?= h((string)($home['cta']['btn_link'] ?? '')) ?>">
              </div>
            </div>
          </div>
        </div>
      </div>

    </div><!-- /accordion -->
  </div>

  <div class="col-12 d-flex gap-2 mt-2">
    <button class="btn btn-primary" type="submit">
      <i class="bi bi-save me-1"></i> Save Changes
    </button>
  </div>
</form>

<!-- Minimal UI helpers: drag reorder + slide details toggle + image chooser -->
<style>
  .slide-thumb{width:52px;height:40px;border-radius:10px;overflow:hidden;background:#f1f3f5;flex:0 0 auto;display:flex;align-items:center;justify-content:center}
  .slide-thumb-img{width:100%;height:100%;object-fit:cover;display:block}
  .slide-thumb-fallback{color:#8a8f98;font-size:18px}
  .slide-handle{cursor:grab}
  .slide-card{background:#fff}
  .slide-card.dragging{opacity:.6}
  
  .gallery-image-item {
    cursor: pointer;
    transition: all 0.2s;
  }
  .gallery-image-item:hover {
    border-color: #0d6efd !important;
    box-shadow: 0 0.125rem 0.25rem rgba(13,110,253,0.25);
    transform: scale(1.02);
  }
  .gallery-image-item img {
    height: 120px;
    object-fit: cover;
    width: 100%;
  }
</style>

<script>
(function(){
  "use strict";

  // Toggle slide details
  document.addEventListener("click", function(e){
    const btn = e.target.closest("[data-slide-toggle]");
    if(!btn) return;
    const card = btn.closest("[data-slide-card]");
    const details = card.querySelector("[data-slide-details]");
    details.classList.toggle("d-none");
  });

  // Upload image button
  document.addEventListener("click", function(e){
    const uploadBtn = e.target.closest("[data-upload-image]");
    if(!uploadBtn) return;
    const card = uploadBtn.closest("[data-slide-card]");
    const fileInput = card.querySelector("[data-image-file]");
    if(fileInput) fileInput.click();
  });

  // Apply slider changes via AJAX
  const btnApply = document.getElementById("btnApplySlider");
  if(btnApply){
    btnApply.addEventListener("click", function(){
      // Show loading state
      const originalText = btnApply.innerHTML;
      btnApply.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Saving...';
      btnApply.disabled = true;

      // Collect all form data
      const formData = new FormData(document.querySelector('form'));
      
      // Send AJAX request
      fetch('save.php', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if(data.success) {
          // Show success message
          showAlert('Slider settings saved successfully!', 'success');
          // Reload current page to show updated data
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          showAlert(data.error || 'Error saving slider settings', 'danger');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving slider settings', 'danger');
      })
      .finally(() => {
        // Restore button state
        btnApply.innerHTML = originalText;
        btnApply.disabled = false;
      });
    });
  }

  // Gallery picker functionality
  document.addEventListener("click", function(e){
    const pickBtn = e.target.closest("[data-pick-image]");
    if(!pickBtn) return;
    
    // Open gallery modal
    openGalleryModal();
  });

  function openGalleryModal() {
    // Create modal if it doesn't exist
    let modal = document.getElementById('galleryModal');
    if(!modal) {
      modal = createGalleryModal();
      document.body.appendChild(modal);
    }
    
    // Load gallery images
    loadGalleryImages();
    
    // Show modal
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
  }

  function createGalleryModal() {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'galleryModal';
    modal.innerHTML = `
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Choose from Gallery</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div id="galleryImages" class="row g-3">
              <div class="col-12 text-center">
                <div class="spinner-border" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
    return modal;
  }

  function loadGalleryImages() {
    fetch('<?= h(BASE_URL) ?>admin/home/slider_gallery.php?action=list')
      .then(response => response.json())
      .then(data => {
        const container = document.getElementById('galleryImages');
        if(data.success && data.images.length > 0) {
          container.innerHTML = data.images.map(img => `
            <div class="col-md-3 col-sm-4 col-6">
              <div class="gallery-image-item border rounded p-2 cursor-pointer" data-image="${img.name}">
                <img src="<?= h(rtrim((string)BASE_URL, '/')) ?>/uploads/slider/${img.name}" class="img-fluid" alt="${img.name}">
                <div class="small text-muted mt-1 text-truncate">${img.name}</div>
              </div>
            </div>
          `).join('');
          
          // Add click handlers
          container.querySelectorAll('.gallery-image-item').forEach(item => {
            item.addEventListener('click', function() {
              selectGalleryImage(this.dataset.image);
            });
          });
        } else {
          container.innerHTML = '<div class="col-12 text-center text-muted">No images found in gallery</div>';
        }
      })
      .catch(error => {
        console.error('Error loading gallery:', error);
        document.getElementById('galleryImages').innerHTML = '<div class="col-12 text-center text-danger">Error loading gallery</div>';
      });
  }

  function selectGalleryImage(imageName) {
    // Get the current slide card that triggered the gallery
    const currentCard = document.querySelector('[data-slide-details]:not(.d-none)') || document.querySelector('[data-slide-card]');
    if(!currentCard) return;
    
    // Set the image input value
    const imageInput = currentCard.querySelector('[data-slide-image-input]');
    if(imageInput) {
      imageInput.value = imageName;
      
      // Update preview
      const preview = currentCard.querySelector("img[alt='Preview']");
      if(preview) {
        preview.src = '<?= rtrim((string)BASE_URL, '/') ?>/uploads/slider/' + imageName;
      }
    }
    
    // Close modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('galleryModal'));
    if(modal) modal.hide();
  }

  function showAlert(message, type) {
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alert.innerHTML = `
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
      if(alert.parentNode) {
        alert.parentNode.removeChild(alert);
      }
    }, 5000);
  }

  // Clear image button
  document.addEventListener("click", function(e){
    const clearBtn = e.target.closest("[data-clear-image]");
    if(!clearBtn) return;
    const card = clearBtn.closest("[data-slide-card]");
    const txt = card.querySelector("[data-slide-image-input]");
    const preview = card.querySelector("img[alt='Preview']");
    if(txt) txt.value = "";
    if(preview) {
      const wrapper = preview.parentElement;
      wrapper.innerHTML = '<div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted"><i class="bi bi-image"></i></div>';
    }
  });

  // File input change handler
  document.addEventListener("change", function(e){
    const fileInput = e.target.closest("[data-image-file]");
    if(!fileInput) return;
    const card = fileInput.closest("[data-slide-card]");
    const txt = card.querySelector("[data-slide-image-input]");
    const file = fileInput.files && fileInput.files[0];
    if(!file) return;

    // set only filename; upload happens on form submit
    txt.value = file.name;

    // preview immediately
    const preview = card.querySelector("img[alt='Preview']");
    if(preview) {
      const url = URL.createObjectURL(file);
      preview.src = url;
    }
  });

  // Drag & drop reorder (updates sort_order automatically)
  const list = document.getElementById("slidesList");
  if(!list) return;

  let dragEl = null;

  function renumberOrders(){
    const cards = list.querySelectorAll("[data-slide-card]");
    cards.forEach((card, idx) => {
      const orderInput = card.querySelector('input[name*="[sort_order]"]');
      if(orderInput) orderInput.value = (idx + 1);
    });
  }

  list.addEventListener("dragstart", function(e){
    const card = e.target.closest("[data-slide-card]");
    if(!card) return;
    dragEl = card;
    card.classList.add("dragging");
    e.dataTransfer.effectAllowed = "move";
  });

  list.addEventListener("dragend", function(e){
    const card = e.target.closest("[data-slide-card]");
    if(card) card.classList.remove("dragging");
    dragEl = null;
    renumberOrders();
  });

  list.querySelectorAll("[data-slide-card]").forEach(card => {
    card.setAttribute("draggable", "true");
  });

  list.addEventListener("dragover", function(e){
    e.preventDefault();
    const after = getDragAfterElement(list, e.clientY);
    if(!dragEl) return;
    if(after == null) list.appendChild(dragEl);
    else list.insertBefore(dragEl, after);
  });

  function getDragAfterElement(container, y){
    const els = [...container.querySelectorAll("[data-slide-card]:not(.dragging)")];
    return els.reduce((closest, child) => {
      const box = child.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      if(offset < 0 && offset > closest.offset) return { offset, element: child };
      return closest;
    }, { offset: Number.NEGATIVE_INFINITY }).element;
  }

  // Add slide button: clones last slide card UI (client-side only)
  const btnAdd = document.getElementById("btnAddSlide");
  if(btnAdd){
    btnAdd.addEventListener("click", function(){
      const cards = list.querySelectorAll("[data-slide-card]");
      const last = cards[cards.length - 1];
      if(!last) return;

      // Get the next index for the new slide
      const nextIndex = cards.length;
      
      // Clone last and clear values
      const clone = last.cloneNode(true);
      
      // Update all input names with new index
      clone.querySelectorAll("input, select, textarea").forEach(inp => {
        if(inp.name) {
          // Replace slides[old_index] with slides[new_index]
          inp.name = inp.name.replace(/slides\[\d+\]/, `slides[${nextIndex}]`);
        }
        
        if(inp.type === "checkbox") inp.checked = false;
        else inp.value = "";
      });
      
      // Update data attributes
      clone.querySelectorAll("[data-slide-card]").forEach(el => {
        el.setAttribute("data-slide-card", nextIndex);
      });
      
      // Reset preview
      const preview = clone.querySelector("img[alt='Preview']");
      if(preview) {
        const wrapper = preview.parentElement;
        wrapper.innerHTML = '<div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted"><i class="bi bi-image"></i></div>';
      }
      
      clone.querySelector("[data-slide-details]").classList.add("d-none");

      // Add to list
      list.appendChild(clone);

      // make draggable
      clone.setAttribute("draggable","true");
      renumberOrders();
    });
  }
})();
</script>


<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
