<?php
declare(strict_types=1);

$page_title = "About Us | Alma Tech Consults";
$active = "about";

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/gate.php';
require_once __DIR__ . '/includes/header.php';

// Helper (in case header isn't loaded in future)
if (!function_exists('h')) {
  function h($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
  }
}

// mysqli only
$db = $GLOBALS['db'] ?? ($mysqli ?? null);
$db_ok = ($db instanceof mysqli);

// -------------------------------------
// DB UNAVAILABLE → graceful message
// -------------------------------------
if (!$db_ok) {
  ?>
  <section class="section">
    <div class="container">
      <div class="alert alert-warning">
        <h4 class="mb-1">Service temporarily unavailable</h4>
        <p class="mb-0">
          Our About page is currently unavailable because the database connection
          could not be established. Please try again shortly.
        </p>
      </div>
    </div>
  </section>
  <?php
  require_once __DIR__ . '/includes/footer.php';
  exit;
}

$aboutHeroCover = '';
$aboutHeroTextClass = 'text-muted';
if (function_exists('setting_get')) {
  $aboutCover = trim((string)setting_get($db, 'about_cover_image', ''));
  if ($aboutCover !== '') {
    $aboutHeroCover = (strpos($aboutCover, 'http') === 0)
      ? $aboutCover
      : rtrim((string)BASE_URL, '/') . '/' . ltrim($aboutCover, '/');
    $aboutHeroTextClass = '';
  }
}

// -------------------------------------
// FETCH ABOUT SETTINGS (REQUIRED)
// -------------------------------------
$about = null;

$stmt = $db->prepare("SELECT `value` FROM settings WHERE `key`='about_settings' LIMIT 1");
if ($stmt) {
  $stmt->execute();

  $row = null;
  if (method_exists($stmt, 'get_result')) {
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
  } else {
    $stmt->bind_result($val);
    if ($stmt->fetch()) {
      $row = ['value' => $val];
    }
  }

  $stmt->close();

  if ($row && isset($row['value'])) {
    $decoded = json_decode((string)$row['value'], true);
    if (is_array($decoded)) $about = $decoded;
  }
}

if (!$about) {
  $about = [
    'hero_title' => 'About Us',
    'hero_subtitle' => 'We help businesses build, fix, and scale with technology.',
    'story' => 'We deliver modern websites, branding, ICT support, and digital growth strategies that are practical for real businesses in Uganda.',
    'mission' => 'To provide dependable ICT and digital services that help organizations operate efficiently, look professional online, and grow through technology.',
    'vision' => 'To be a leading technology partner for businesses across Uganda and the region—delivering systems, websites, and support that last.',
    'values' => "Integrity\nQuality\nSpeed\nSupport"
  ];
}

// -------------------------------------
// TEAM
// -------------------------------------
$team = [];
$stmt = $db->prepare("
  SELECT id, name, slug, role, short_desc, photo
  FROM team_members
  WHERE status='active'
  ORDER BY sort_order ASC, id DESC
");
if ($stmt) {
  $stmt->execute();

  if (method_exists($stmt, 'get_result')) {
    $res = $stmt->get_result();
    $team = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  } else {
    $stmt->bind_result($id,$name,$slug,$role,$short_desc,$photo);
    while ($stmt->fetch()) {
      $team[] = [
        'id' => $id,
        'name' => $name,
        'slug' => $slug,
        'role' => $role,
        'short_desc' => $short_desc,
        'photo' => $photo,
      ];
    }
  }

  $stmt->close();
}

// -------------------------------------
// TESTIMONIALS
// -------------------------------------
$testimonials = [];
$stmt = $db->prepare("
  SELECT *
  FROM testimonials
  WHERE status IN ('active','published')
  ORDER BY is_featured DESC, sort_order ASC, id DESC
  LIMIT 20
");
if ($stmt) {
  $stmt->execute();

  if (method_exists($stmt, 'get_result')) {
    $res = $stmt->get_result();
    $testimonials = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
  } else {
    // Fallback: fetch row-by-row without get_result
    $meta = $stmt->result_metadata();
    $fields = $meta ? $meta->fetch_fields() : [];
    $row = [];
    $bind = [];
    foreach ($fields as $f) {
      $row[$f->name] = null;
      $bind[] = &$row[$f->name];
    }
    if ($bind) {
      call_user_func_array([$stmt, 'bind_result'], $bind);
      while ($stmt->fetch()) {
        $testimonials[] = $row;
        $row = array_map(function ($v) { return $v; }, $row); // detach
      }
    }
  }

  $stmt->close();
}
?>

<!-- ================= HERO ================= -->
<section class="hero<?= $aboutHeroCover !== '' ? ' hero-cover-blur hero-scroll-blur' : '' ?>"<?= $aboutHeroCover !== '' ? ' style="--hero-cover-image:url(\'' . h($aboutHeroCover) . '\');"' : '' ?>>
  <div class="container py-5">
    <div class="row g-4 align-items-center">
      <div class="col-lg-7">
        <div class="badge-soft mb-3">
          <i class="bi bi-people-fill me-1"></i>
          <?= h($about['hero_title'] ?? 'About Us') ?>
        </div>
        <h1 class="display-6 fw-bold mb-3">
          <?= h($about['hero_subtitle'] ?? '') ?>
        </h1>
        <?php if (!empty($about['story'])): ?>
          <p class="lead <?= $aboutHeroTextClass ?> mb-0">
            <?= nl2br(h((string)$about['story'])) ?>
          </p>
        <?php endif; ?>
      </div>

      <div class="col-lg-5">
        <div class="hero-card p-4">
          <div class="fw-semibold mb-2">What we're known for</div>
          <ul class="list-unstyled m-0 small text-muted">
            <li class="mb-2"><i class="bi bi-check-circle text-orange me-2"></i> Fast turnaround & clear communication</li>
            <li class="mb-2"><i class="bi bi-check-circle text-orange me-2"></i> Clean, responsive design</li>
            <li class="mb-2"><i class="bi bi-check-circle text-orange me-2"></i> Reliable support after delivery</li>
            <li class="mb-0"><i class="bi bi-check-circle text-orange me-2"></i> Business-focused digital strategy</li>
          </ul>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ================= MISSION / VISION ================= -->
<section class="section">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-6">
        <h2 class="h4 fw-bold mb-2">Our Mission</h2>
        <p class="text-muted mb-0"><?= nl2br(h((string)($about['mission'] ?? ''))) ?></p>
      </div>
      <div class="col-lg-6">
        <h2 class="h4 fw-bold mb-2">Our Vision</h2>
        <p class="text-muted mb-0"><?= nl2br(h((string)($about['vision'] ?? ''))) ?></p>
      </div>
    </div>
  </div>
</section>

<!-- ================= VALUES ================= -->
<section class="section section-soft">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end gap-3 mb-3">
      <div>
        <h2 class="h3 fw-bold mb-1">Our Values</h2>
        <p class="text-muted mb-0">The principles that guide everything we do.</p>
      </div>
    </div>

    <?php
      $valuesData = [];
      $valuesInput = array_filter(array_map('trim', explode("\n", (string)($about['values'] ?? ''))));

      foreach ($valuesInput as $valueLine) {
        $parts = explode(':', $valueLine, 3);

        if (count($parts) >= 2) {
          $valuesData[] = [
            'name' => trim($parts[0]),
            'description' => trim($parts[1]),
            'icon' => isset($parts[2]) ? trim($parts[2]) : 'bi-check2-circle'
          ];
        } else {
          $valuesData[] = [
            'name' => trim($parts[0]),
            'description' => 'Core value that defines our approach to excellence.',
            'icon' => 'bi-check2-circle'
          ];
        }
      }
    ?>

    <div class="slider-container">
      <div class="slider-row" id="valuesSlider">
        <?php if (empty($valuesData)): ?>
          <div class="service-card" style="min-width:280px;">No values set yet.</div>
        <?php else: ?>
          <?php foreach ($valuesData as $value): ?>
            <div class="service-card" style="width:280px;">
              <div class="service-icon">
                <i class="bi <?= h($value['icon']) ?>"></i>
              </div>
              <h3 class="h6 fw-bold mt-3"><?= h($value['name']) ?></h3>
              <p class="text-muted small mb-0"><?= h($value['description']) ?></p>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="slider-nav" id="valuesSliderDots">
        <!-- Dots will be generated by JavaScript -->
      </div>
    </div>
  </div>
</section>

<!-- ================= TEAM ================= -->
<section class="section">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end gap-3 mb-3">
      <div>
        <h2 class="h3 fw-bold mb-1">Our Team</h2>
        <p class="text-muted mb-0">The people building and supporting your systems.</p>
      </div>
    </div>

    <div class="slider-container">
      <div class="slider-row" id="teamSlider">
        <?php if (!$team): ?>
          <div class="service-card" style="min-width:320px;">No team members yet.</div>
        <?php else: ?>
          <?php foreach ($team as $m): ?>
            <?php
              $img = !empty($m['photo'])
                ? (rtrim((string)BASE_URL, '/') . "/uploads/team/" . rawurlencode((string)$m['photo']))
                : '';
            ?>
            <a class="text-decoration-none" href="team.php?slug=<?= h((string)$m['slug']) ?>" style="width:320px;">
              <div class="service-card h-100">
                <div class="d-flex gap-3 align-items-center">
                  <div style="width:64px;height:64px;border-radius:18px;overflow:hidden;background:#f2f2f2;flex:0 0 auto;">
                    <?php if ($img): ?>
                      <img src="<?= h($img) ?>" alt="<?= h((string)$m['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
                    <?php else: ?>
                      <div class="h-100 d-flex align-items-center justify-content-center text-muted">
                        <i class="bi bi-person"></i>
                      </div>
                    <?php endif; ?>
                  </div>

                  <div class="flex-grow-1">
                    <div class="fw-bold text-orange"><?= h((string)$m['name']) ?></div>
                    <div class="text-orange small fw-semibold"><?= h((string)$m['role']) ?></div>
                  </div>
                </div>

                <?php if (!empty($m['short_desc'])): ?>
                  <p class="text-muted small mt-3 mb-0"><?= h((string)$m['short_desc']) ?></p>
                <?php endif; ?>

                <div class="mt-3 small text-orange fw-semibold">
                  View Profile <i class="bi bi-arrow-right ms-1"></i>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="slider-nav" id="teamSliderDots">
        <!-- Dots will be generated by JavaScript -->
      </div>
    </div>
  </div>
</section>

<!-- ================= TESTIMONIALS ================= -->
<section class="section section-soft">
  <div class="container">
    <div class="d-flex justify-content-between align-items-end gap-3 mb-3">
      <div>
        <h2 class="h3 fw-bold mb-1">Testimonials</h2>
        <p class="text-muted mb-0">What clients say after working with us.</p>
      </div>
    </div>

    <div class="slider-container">
      <div class="slider-row" id="testSlider">
        <?php if (!$testimonials): ?>
          <div class="service-card" style="min-width:360px;">No testimonials yet.</div>
        <?php else: ?>
          <?php foreach ($testimonials as $t): ?>
            <div class="service-card" style="width:360px;">
              <div class="d-flex gap-3 align-items-center mb-2">
                <div style="width:52px;height:52px;border-radius:16px;overflow:hidden;background:#f2f2f2;">
                  <?php if (!empty($t['photo'])): ?>
                    <img src="<?= rtrim((string)BASE_URL, '/') ?>/uploads/testimonials/<?= h((string)$t['photo']) ?>"
                         alt="<?= h((string)($t['client_name'] ?? ($t['name'] ?? 'Client'))) ?>"
                         style="width:100%;height:100%;object-fit:cover;">
                  <?php else: ?>
                    <div class="h-100 d-flex align-items-center justify-content-center text-muted">
                      <i class="bi bi-chat-quote"></i>
                    </div>
                  <?php endif; ?>
                </div>
                <div>
                  <div class="fw-bold">
                    <?= h((string)($t['client_name'] ?? ($t['name'] ?? 'Client'))) ?>
                  </div>
                  <div class="small text-muted">
                    <?php
                      $title = (string)($t['client_title'] ?? ($t['title'] ?? ''));
                      $company = (string)($t['company'] ?? '');
                      echo h(trim($title));
                      if ($company !== '') echo ' • ' . h($company);
                    ?>
                  </div>
                </div>
              </div>

              <?php if (!empty($t['rating'])): ?>
                <div class="mb-2">
                  <?php
                    $rating = (int)$t['rating'];
                    for ($i = 1; $i <= 5; $i++):
                  ?>
                    <i class="bi bi-star-fill text-orange<?= $i <= $rating ? '' : '-50' ?>" style="font-size:14px;"></i>
                  <?php endfor; ?>
                </div>
              <?php endif; ?>

              <div class="text-muted">
                <?= nl2br(h((string)($t['message'] ?? ($t['content'] ?? '')))) ?>
              </div>

              <?php
                $showLink = !empty($t['show_project_link']) || !empty($t['project_id']);
                $hasSlug  = !empty($t['project_slug']);
              ?>
              <?php if ($showLink && $hasSlug): ?>
                <div class="mt-3">
                  <a class="btn btn-sm btn-outline-orange" href="project.php?slug=<?= h((string)$t['project_slug']) ?>">
                    View Project: <?= h((string)($t['project_title'] ?? 'Project')) ?>
                  </a>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="slider-nav" id="testSliderDots">
        <!-- Dots will be generated by JavaScript -->
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
// Auto-scroll functionality for sliders
let autoScrollIntervals = {};

function startAutoScroll(sliderId, interval = 3000) {
  if (autoScrollIntervals[sliderId]) clearInterval(autoScrollIntervals[sliderId]);
  autoScrollIntervals[sliderId] = setInterval(() => slideById(sliderId, 1), interval);
}

function stopAutoScroll(sliderId) {
  if (autoScrollIntervals[sliderId]) {
    clearInterval(autoScrollIntervals[sliderId]);
    delete autoScrollIntervals[sliderId];
  }
}

function slideById(sliderId, direction) {
  const slider = document.getElementById(sliderId);
  if (!slider) return;

  const scrollAmount = sliderId === 'valuesSlider' ? 300 : 340;
  const newScroll = slider.scrollLeft + (scrollAmount * direction);

  slider.scrollTo({ left: newScroll, behavior: 'smooth' });

  setTimeout(() => {
    if (slider.scrollLeft >= slider.scrollWidth - slider.clientWidth) {
      slider.scrollTo({ left: 0, behavior: 'smooth' });
    }
  }, 500);
}

document.addEventListener('DOMContentLoaded', function() {
  const sliders = document.querySelectorAll('.slider-row');
  sliders.forEach(slider => {
    slider.style.scrollbarWidth = 'none';
    slider.style.msOverflowStyle = 'none';

    const style = document.createElement('style');
    style.textContent = `
      #${slider.id}::-webkit-scrollbar { display: none; }
    `;
    document.head.appendChild(style);
  });

  const cards = document.querySelectorAll('.service-card');
  cards.forEach(card => {
    card.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-5px)';
      this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
    });
    card.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0)';
      this.style.boxShadow = '';
    });
  });

  const heroCard = document.querySelector('.hero-card');
  if (heroCard) {
    heroCard.addEventListener('mouseenter', function() {
      this.style.transform = 'translateY(-3px)';
      this.style.boxShadow = '0 10px 30px rgba(0,0,0,0.1)';
    });
    heroCard.addEventListener('mouseleave', function() {
      this.style.transform = 'translateY(0)';
      this.style.boxShadow = '';
    });
  }

  // Generate dots and handle dot navigation for sliders
  const sliderConfigs = [
    { id: 'valuesSlider', dotsId: 'valuesSliderDots', interval: 4000 },
    { id: 'teamSlider', dotsId: 'teamSliderDots', interval: 3000 },
    { id: 'testSlider', dotsId: 'testSliderDots', interval: 3000 }
  ];

  sliderConfigs.forEach(config => {
    const slider = document.getElementById(config.id);
    const dotsContainer = document.getElementById(config.dotsId);
    
    if (!slider || !dotsContainer) return;

    // Generate dots based on number of cards
    const cards = slider.querySelectorAll('.service-card');
    const dotCount = Math.max(1, Math.ceil(cards.length / getVisibleCards(slider)));
    
    dotsContainer.innerHTML = '';
    for (let i = 0; i < dotCount; i++) {
      const dot = document.createElement('button');
      dot.className = 'slider-dot';
      dot.type = 'button';
      dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
      dot.addEventListener('click', () => scrollToSlide(slider, i));
      dotsContainer.appendChild(dot);
    }

    // Update active dot on scroll
    slider.addEventListener('scroll', () => updateActiveDot(slider, dotsContainer));
    
    // Start auto-scroll
    startAutoScroll(config.id, config.interval);
    
    // Pause on hover
    slider.addEventListener('mouseenter', () => stopAutoScroll(config.id));
    slider.addEventListener('mouseleave', () => startAutoScroll(config.id, config.interval));
    
    dotsContainer.addEventListener('mouseenter', () => stopAutoScroll(config.id));
    dotsContainer.addEventListener('mouseleave', () => startAutoScroll(config.id, config.interval));
    
    // Initialize dots
    updateActiveDot(slider, dotsContainer);
  });

  function getVisibleCards(slider) {
    const sliderWidth = slider.clientWidth;
    const cardWidth = 280; // Default card width
    return Math.floor(sliderWidth / cardWidth);
  }

  function scrollToSlide(slider, slideIndex) {
    const cards = slider.querySelectorAll('.service-card');
    if (slideIndex >= cards.length) return;
    
    const card = cards[slideIndex];
    const scrollLeft = card.offsetLeft - (slider.clientWidth - card.clientWidth) / 2;
    
    slider.scrollTo({ left: scrollLeft, behavior: 'smooth' });
  }

  function updateActiveDot(slider, dotsContainer) {
    const cards = slider.querySelectorAll('.service-card');
    const sliderCenter = slider.scrollLeft + slider.clientWidth / 2;
    
    let activeIndex = 0;
    cards.forEach((card, index) => {
      const cardCenter = card.offsetLeft + card.clientWidth / 2;
      if (Math.abs(cardCenter - sliderCenter) < Math.abs(cards[activeIndex].offsetLeft + cards[activeIndex].clientWidth / 2 - sliderCenter)) {
        activeIndex = index;
      }
    });
    
    const dots = dotsContainer.querySelectorAll('.slider-dot');
    dots.forEach((dot, index) => {
      dot.classList.toggle('active', index === activeIndex);
    });
  }
});
</script>
