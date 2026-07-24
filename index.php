<?php
declare(strict_types=1);

$page_title = "Alma Tech Consults | ICT, Web, Branding & Digital Growth";
$active = "home";

// Load config first (BASE_URL etc.)
require_once __DIR__ . '/config.php';

// Core includes
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/gate.php';

// Settings lib (prefer public)
$settingsLib = __DIR__ . '/includes/settings_lib.php';
if (is_file($settingsLib)) {
  require_once $settingsLib;
} else {
  $adminSettingsLib = __DIR__ . '/admin/includes/settings_lib.php';
  if (is_file($adminSettingsLib)) require_once $adminSettingsLib;
}

// Header (after $page_title/$active set)
require_once __DIR__ . '/includes/header.php';

// DB handle
$db = $GLOBALS['db'] ?? ($mysqli ?? null);

// Load settings (safe)
$home = [];
if ($db instanceof mysqli && function_exists('setting_get_json')) {
  $home = setting_get_json($db, 'home_settings', []);
}
if (!is_array($home)) $home = [];

// Load contact settings for WhatsApp
$contact_whatsapp = '';
if ($db instanceof mysqli && function_exists('setting_get')) {
  $contact_whatsapp = setting_get($db, 'contact_whatsapp', '+256755123456');
}

// ---------------- Defaults ----------------
$defaults = [
  'hero' => [
    'badge_icon' => 'bi bi-lightning-charge-fill',
    'badge_text' => 'Fast, reliable ICT & digital solutions',
    'title'      => 'We build websites, brands, and systems that grow your business.',
    'subtitle'   => 'From web design and digital marketing to IT support and connectivity — we help you look professional online and perform better offline.',
    'btn1_text'  => 'Explore Services',
    'btn1_link'  => 'services.php',
    'btn2_text'  => 'View Our Work',
    'btn2_link'  => 'projects.php',
  ],
  'quick_request' => [
    'title'        => 'Quick Request',
    'subtitle'     => 'Response within hours',
    'services'     => [
      'Website Design',
      'Digital Marketing',
      'Branding & Graphics',
      'IT Support & Maintenance',
      'Internet / Connectivity',
    ],
    'consent_text' => 'By submitting, you agree to be contacted for service delivery.',
  ],
  'services_preview' => [
    'title'         => 'Our Services',
    'subtitle'      => 'Everything you need to launch, fix, or scale your business tech.',
    'view_all_text' => 'View all',
    'view_all_link' => 'services.php',
    'cards' => [
      ['icon'=>'bi bi-globe2','title'=>'Website Design','desc'=>'Responsive websites that load fast and convert visitors into customers.'],
      ['icon'=>'bi bi-megaphone','title'=>'Digital Marketing','desc'=>'Google visibility, social media ads, and growth strategy.'],
      ['icon'=>'bi bi-palette2','title'=>'Branding & Design','desc'=>'Logos, posters, company profiles, and social media content.'],
      ['icon'=>'bi bi-tools','title'=>'IT Support','desc'=>'Repairs, setup, software support, maintenance, and upgrades.'],
    ],
  ],
  'stats' => [
    ['value'=>'127', 'label'=>'Happy Clients', 'icon'=>'bi-people-fill'],
    ['value'=>'183', 'label'=>'Projects Delivered', 'icon'=>'bi-briefcase-fill'],
    ['value'=>'24/7', 'label'=>'Support Available', 'icon'=>'bi-headset'],
    ['value'=>'8+', 'label'=>'Years Experience', 'icon'=>'bi-award-fill']
  ],
  'cta' => [
    'title'    => 'Need a website or system with a dashboard?',
    'subtitle' => 'We can build a public site + admin panel where you manage services, projects, blog, leads, and users.',
    'btn_text' => 'Start a Project',
    'btn_link' => 'contact.php',
  ],
  'slider' => [
    'enabled'          => false,
    'interval'         => 5000,
    'transition'       => 'fade',   // fade|slide
    'overlay_enabled'  => true,
    'overlay_opacity'  => 0.45,     // 0..1
    'overlay_gradient' => 'linear-gradient(120deg, rgba(0,0,0,.65), rgba(0,0,0,.15))',
    'show_indicators'  => true,
    'show_arrows'      => true,
    'slides' => [
      [
        'is_active'     => true,
        'sort_order'    => 1,
        'image'         => '',
        'alt'           => 'Alma Tech Consults',
        'caption_title' => 'Build your business online',
        'caption_text'  => 'Websites, dashboards, ICT support, branding, and growth.',
        'btn1_text'     => 'Explore Services',
        'btn1_link'     => 'services.php',
        'btn2_text'     => 'Contact Us',
        'btn2_link'     => 'contact.php',
        'caption_align' => 'left', // left|center|right
      ],
    ],
  ],
];

// Merge home with defaults (deep merge for known keys)
$hero  = array_merge($defaults['hero'], is_array($home['hero'] ?? null) ? $home['hero'] : []);
$qr    = array_merge($defaults['quick_request'], is_array($home['quick_request'] ?? null) ? $home['quick_request'] : []);
$sp    = array_merge($defaults['services_preview'], is_array($home['services_preview'] ?? null) ? $home['services_preview'] : []);
$cta   = array_merge($defaults['cta'], is_array($home['cta'] ?? null) ? $home['cta'] : []);

$stats = $home['stats'] ?? [];
if (!is_array($stats) || empty($stats)) $stats = $defaults['stats'];

// Services preview cards - fetch from database
$cards = [];
if ($db instanceof mysqli) {
  $stmt = $db->prepare("
    SELECT title, slug, short_desc, icon, is_active
    FROM services
    WHERE is_active = 1
    ORDER BY id DESC
    LIMIT 4
  ");
  if ($stmt) {
    $stmt->execute();
    
    if (method_exists($stmt, 'get_result')) {
      $res = $stmt->get_result();
      $services = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    } else {
      $stmt->bind_result($title, $slug, $short_desc, $icon, $is_active);
      $services = [];
      while ($stmt->fetch()) {
        $services[] = [
          'title' => $title,
          'slug' => $slug,
          'short_desc' => $short_desc,
          'icon' => $icon,
          'is_active' => $is_active
        ];
      }
    }
    $stmt->close();
    
    // Convert to card format
    foreach ($services as $service) {
      $desc = !empty($service['short_desc']) ? $service['short_desc'] : 
               (isset($service['description']) ? substr(strip_tags($service['description']), 0, 100) . '...' : '');
      
      // Handle icon vs image
      $iconValue = $service['icon'] ?? '';
      $hasImage = false;
      $imageUrl = '';
      
      if ($iconValue !== '') {
        // Check if icon is an uploaded file
        $iconFilePath = __DIR__ . '/uploads/services/' . $iconValue;
        $hasImage = is_file($iconFilePath);
        if ($hasImage) {
          $imageUrl = BASE_URL . 'uploads/services/' . rawurlencode($iconValue);
        }
      }
      
      $cards[] = [
        'icon' => $iconValue,
        'title' => $service['title'] ?? 'Service',
        'desc' => $desc,
        'slug' => $service['slug'] ?? '',
        'has_image' => $hasImage,
        'image_url' => $imageUrl
      ];
    }
  }
}

// Fallback to defaults if no services found
if (empty($cards)) {
  $cards = $sp['cards'] ?? [];
  // Ensure fallback cards have required keys
  foreach ($cards as &$card) {
    $card['has_image'] = false;
    $card['image_url'] = '';
  }
}

// Slider merge + sanitize
$slider = array_merge($defaults['slider'], is_array($home['slider'] ?? null) ? $home['slider'] : []);

// If no slides in database, add some defaults
if (empty($home['slider']['slides']) || !is_array($home['slider']['slides'])) {
  $slider['slides'] = $defaults['slider']['slides'];
} else {
  $slider['slides'] = $home['slider']['slides'];
}

$slider['transition'] = ($slider['transition'] === 'slide') ? 'slide' : 'fade';
$slider['interval'] = max(1000, (int)($slider['interval'] ?? 5000));

$overlayOpacity = (float)($slider['overlay_opacity'] ?? 0.45);
if ($overlayOpacity < 0) $overlayOpacity = 0;
if ($overlayOpacity > 1) $overlayOpacity = 1;
$slider['overlay_opacity'] = $overlayOpacity;

$slides = $slider['slides'] ?? [];
if (!is_array($slides)) $slides = [];

$cleanSlides = [];
foreach ($slides as $s) {
  if (!is_array($s)) continue;
  if (empty($s['is_active'])) continue;

  $align = (string)($s['caption_align'] ?? 'left');
  if (!in_array($align, ['left','center','right'], true)) $align = 'left';

  $cleanSlides[] = [
    'sort_order'    => (int)($s['sort_order'] ?? 0),
    'image'         => trim((string)($s['image'] ?? '')),
    'alt'           => trim((string)($s['alt'] ?? '')),
    'caption_title' => trim((string)($s['caption_title'] ?? '')),
    'caption_text'  => trim((string)($s['caption_text'] ?? '')),
    'btn1_text'     => trim((string)($s['btn1_text'] ?? '')),
    'btn1_link'     => trim((string)($s['btn1_link'] ?? '')),
    'btn2_text'     => trim((string)($s['btn2_text'] ?? '')),
    'btn2_link'     => trim((string)($s['btn2_link'] ?? '')),
    'caption_align' => $align,
  ];
}
usort($cleanSlides, function ($a, $b) { return $a['sort_order'] <=> $b['sort_order']; });
$slider['slides'] = $cleanSlides;
?>

<?php if (!empty($slider['enabled']) && !empty($slider['slides'])): ?>
<section class="home-slider-wrap">
  <div id="homeSlider"
       class="carousel <?= ($slider['transition'] === 'fade') ? 'carousel-fade' : '' ?> slide"
       data-bs-ride="carousel"
       data-bs-interval="<?= (int)$slider['interval'] ?>">

    <?php if (!empty($slider['show_indicators'])): ?>
      <div class="carousel-indicators">
        <?php foreach ($slider['slides'] as $i => $_): ?>
          <button type="button"
                  data-bs-target="#homeSlider"
                  data-bs-slide-to="<?= (int)$i ?>"
                  class="<?= $i === 0 ? 'active' : '' ?>"
                  aria-current="<?= $i === 0 ? 'true' : 'false' ?>"
                  aria-label="Slide <?= (int)($i + 1) ?>"></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="carousel-inner">
      <?php foreach ($slider['slides'] as $i => $s): ?>
        <?php
          $img = (string)$s['image'];

          // normalize to uploads/slider/...
          $imgPath = $img;
          if ($imgPath !== '' && strpos($imgPath, 'uploads/') !== 0) {
            $imgPath = 'uploads/slider/' . ltrim($imgPath, '/');
          }

          $fileOk = ($imgPath !== '' && is_file(__DIR__ . '/' . $imgPath));
          $fullImg = $fileOk ? (BASE_URL . $imgPath) : '';

          $align = (string)$s['caption_align'];
          $alignClass = $align === 'center' ? 'text-center' : ($align === 'right' ? 'text-end' : 'text-start');

          $textBoxClass = 'home-slide-text';
          if ($align === 'center') $textBoxClass .= ' mx-auto text-center';
          if ($align === 'right')  $textBoxClass .= ' ms-auto text-end';
        ?>

        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
          <div class="home-slide position-relative">

            <?php if ($fileOk): ?>
              <img class="home-slide-img" src="<?= h($fullImg) ?>" alt="<?= h((string)$s['alt']) ?>">
            <?php else: ?>
              <div class="home-slide-fallback">
                <i class="bi bi-image"></i>
                <div class="small mt-2">Slider image missing</div>
              </div>
            <?php endif; ?>

            <?php if (!empty($slider['overlay_enabled'])): ?>
              <div class="home-slide-overlay"
                   style="background: <?= h((string)$slider['overlay_gradient']) ?>; opacity: <?= h((string)$slider['overlay_opacity']) ?>;"></div>
            <?php endif; ?>

            <div class="home-slide-content">
              <div class="container">
                <div class="<?= $alignClass ?>">
                  <div class="<?= h($textBoxClass) ?>">

                    <?php if ($s['caption_title'] !== ''): ?>
                      <h2 class="home-slide-title"><?= h((string)$s['caption_title']) ?></h2>
                    <?php endif; ?>

                    <?php if ($s['caption_text'] !== ''): ?>
                      <p class="home-slide-sub"><?= h((string)$s['caption_text']) ?></p>
                    <?php endif; ?>

                    <div class="d-flex gap-2 flex-wrap <?= $align === 'center' ? 'justify-content-center' : ($align === 'right' ? 'justify-content-end' : 'justify-content-start') ?>">
                      <?php if ($s['btn1_text'] !== '' && $s['btn1_link'] !== ''): ?>
                        <a href="<?= h((string)$s['btn1_link']) ?>" class="btn btn-orange btn-lg">
                          <?= h((string)$s['btn1_text']) ?> <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                      <?php endif; ?>

                      <?php if ($s['btn2_text'] !== '' && $s['btn2_link'] !== ''): ?>
                        <a href="<?= h((string)$s['btn2_link']) ?>" class="btn btn-outline-light btn-lg">
                          <?= h((string)$s['btn2_text']) ?>
                        </a>
                      <?php endif; ?>
                    </div>

                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (!empty($slider['show_arrows'])): ?>
      <button class="carousel-control-prev" type="button" data-bs-target="#homeSlider" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#homeSlider" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
      </button>
    <?php endif; ?>

  </div>
</section>
<?php endif; ?>


<!-- Hero (keep this section ONLY if you want it below slider) -->
<header class="hero">
  <div class="container py-5">
    <div class="row align-items-center g-4">

      <div class="col-lg-6">
        <?php if (!empty($hero['badge_text'])): ?>
          <div class="badge-soft mb-3">
            <?php if (!empty($hero['badge_icon'])): ?>
              <i class="<?= h((string)$hero['badge_icon']) ?> me-1"></i>
            <?php endif; ?>
            <?= h((string)$hero['badge_text']) ?>
          </div>
        <?php endif; ?>

        <h1 class="display-5 fw-bold mb-3"><?= h((string)$hero['title']) ?></h1>

        <?php if (!empty($hero['subtitle'])): ?>
          <p class="lead text-muted mb-4"><?= h((string)$hero['subtitle']) ?></p>
        <?php endif; ?>

        <div class="global-stat-alert bg-light border border-orange rounded-3 p-3 mb-4">
          <div class="d-flex align-items-center">
            <div class="stat-icon-small me-3">
              <i class="bi bi-globe2"></i>
            </div>
            <div class="flex-grow-1">
              <div class="fw-bold text-orange mb-1">The Digital Opportunity</div>
              <div class="small text-muted">
                Of the 7.4 Billion people in this world, <strong>3.5 Billion people (47%)</strong> are online every day. 
                They're searching for information, sharing on social media, or shopping on e-commerce websites. 
                <strong>You could be having what they want, but do you have a website?</strong>
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex flex-wrap gap-2">
          <?php if (!empty($hero['btn1_text'])): ?>
            <a href="<?= h((string)($hero['btn1_link'] ?: 'services.php')) ?>" class="btn btn-orange btn-lg">
              <?= h((string)$hero['btn1_text']) ?> <i class="bi bi-arrow-right ms-1"></i>
            </a>
          <?php endif; ?>

          <?php if (!empty($hero['btn2_text'])): ?>
            <a href="<?= h((string)($hero['btn2_link'] ?: 'projects.php')) ?>" class="btn btn-orange btn-lg">
              <?= h((string)$hero['btn2_text']) ?>
            </a>
          <?php endif; ?>
        </div>

        <div class="row g-3 mt-4">
          <div class="col-6">
            <div class="mini-card">
              <div class="mini-icon"><i class="bi bi-shield-check"></i></div>
              <div>
                <div class="fw-semibold">Trusted Support</div>
                <div class="small text-muted">Maintenance & reliability first</div>
              </div>
            </div>
          </div>
          <div class="col-6">
            <div class="mini-card">
              <div class="mini-icon"><i class="bi bi-graph-up-arrow"></i></div>
              <div>
                <div class="fw-semibold">Growth Focused</div>
                <div class="small text-muted">SEO, ads & conversion design</div>
              </div>
            </div>
          </div>
        </div>

      </div>

      <div class="col-lg-6">
        <div class="hero-card p-4 p-md-5">
          <div class="hero-card-inner">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div class="fw-semibold"><?= h((string)($qr['title'] ?? 'Quick Request')) ?></div>
              <span class="small text-muted"><?= h((string)($qr['subtitle'] ?? 'Response within hours')) ?></span>
            </div>

            <form id="quickRequestForm" class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Name</label>
                <input class="form-control" id="qrName" name="name" placeholder="Your name" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input class="form-control" id="qrPhone" name="phone" placeholder="+256..." required>
              </div>

              <div class="col-12">
                <label class="form-label">Service Needed</label>
                <select class="form-select" id="qrService" name="service" required>
                  <option value="">Select a service</option>
                  <?php
                    $qrServices = $qr['services'] ?? [];
                    if (!is_array($qrServices)) $qrServices = [];
                    foreach ($qrServices as $sv):
                  ?>
                    <option value="<?= h((string)$sv) ?>"><?= h((string)$sv) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12">
                <button type="submit" class="btn btn-success w-100">
                  <i class="bi bi-whatsapp me-2"></i> Send via WhatsApp
                </button>
              </div>

              <div class="col-12">
                <div class="small text-muted"><?= h((string)($qr['consent_text'] ?? 'We will respond via WhatsApp within hours')) ?></div>
              </div>
            </form>

          </div>
        </div>
      </div>

    </div>
  </div>
</header>

<!-- Quick Request WhatsApp Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('quickRequestForm');
  if (form) {
    form.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const name = document.getElementById('qrName').value.trim();
      const phone = document.getElementById('qrPhone').value.trim();
      const service = document.getElementById('qrService').value;
      
      if (!name || !phone || !service) {
        alert('Please fill in all fields');
        return;
      }
      
      // Use WhatsApp number from database settings
      const businessPhone = '<?= h((string)($contact_whatsapp ?? '+256755123456')) ?>';
      
      // Create message
      const message = `Hello! I'd like to request a quote:\n\n` +
                     `Name: ${name}\n` +
                     `Phone: ${phone}\n` +
                     `Service: ${service}\n\n` +
                     `Please contact me soon. Thank you!`;
      
      // Encode message for URL
      const encodedMessage = encodeURIComponent(message);
      
      // Create WhatsApp URL
      const whatsappUrl = `https://wa.me/${businessPhone.replace(/[^\d]/g, '')}?text=${encodedMessage}`;
      
      // Open WhatsApp
      window.open(whatsappUrl, '_blank');
    });
  }
});
</script>


<!-- Services preview -->
<section class="section">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end gap-3 mb-4">
      <div>
        <h2 class="h3 fw-bold mb-1"><?= h((string)$sp['title']) ?></h2>
        <p class="text-muted mb-0"><?= h((string)$sp['subtitle']) ?></p>
      </div>
      <a class="btn btn-orange btn-lg d-none d-lg-block" href="<?= h((string)$sp['view_all_link']) ?>">
        <?= h((string)$sp['view_all_text']) ?>
      </a>
    </div>

    <div class="row g-3 g-lg-4">
      <?php foreach ($cards as $card): if (!is_array($card)) continue; ?>
        <div class="col-md-6 col-lg-3">
          <?php if (!empty($card['slug'])): ?>
            <a href="service.php?slug=<?= h((string)$card['slug']) ?>" class="text-decoration-none">
          <?php endif; ?>
          <div class="service-card h-100">
            <div class="service-icon" style="overflow:hidden;">
              <?php if (!empty($card['has_image']) && !empty($card['image_url'])): ?>
                <img src="<?= h((string)$card['image_url']) ?>" alt="<?= h((string)($card['title'] ?? 'Service')) ?>" 
                     style="width:100%;height:100%;object-fit:cover;display:block;">
              <?php else: ?>
                <i class="<?= h((string)($card['icon'] ?? 'bi bi-star')) ?>"></i>
              <?php endif; ?>
            </div>
            <h3 class="h6 fw-bold text-orange mt-3"><?= h((string)($card['title'] ?? 'Service')) ?></h3>
            <p class="text-muted small mb-0"><?= h((string)($card['desc'] ?? '')) ?></p>
          </div>
          <?php if (!empty($card['slug'])): ?>
            </a>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- View All button for small screens -->
    <div class="text-center d-lg-none mt-4">
      <a class="btn btn-orange btn-lg w-100" href="<?= h((string)$sp['view_all_link']) ?>">
        <?= h((string)$sp['view_all_text']) ?>
      </a>
    </div>

  </div>
</section>

<!-- Stats -->
<section class="section section-soft">
  <div class="container">
    <div class="row g-4">
      <?php foreach ($stats as $st): if (!is_array($st)) continue; ?>
        <div class="col-6 col-lg-3">
          <div class="stat-card text-center">
            <div class="stat-icon mb-3">
              <i class="<?= h((string)($st['icon'] ?? 'bi-star-fill')) ?>"></i>
            </div>
            <div class="stat-num" data-stat-counter data-count="<?= h((string)($st['value'] ?? '0')) ?>" data-duration="2500">
              <?= h((string)($st['value'] ?? '0')) ?>
            </div>
            <div class="stat-label"><?= h((string)($st['label'] ?? '')) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="section">
  <div class="container">
    <div class="cta p-4 p-md-5">
      <div class="row align-items-center g-3">
        <div class="col-lg-8">
          <h2 class="h4 fw-bold mb-2"><?= h((string)$cta['title']) ?></h2>
          <p class="mb-0 text-muted"><?= h((string)$cta['subtitle']) ?></p>
        </div>
        <div class="col-lg-4 text-lg-end">
          <a href="<?= h((string)$cta['btn_link']) ?>" class="btn btn-orange btn-lg w-100 w-lg-auto">
            <?= h((string)$cta['btn_text']) ?> <i class="bi bi-arrow-right ms-1"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
