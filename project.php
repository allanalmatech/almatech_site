<?php
declare(strict_types=1);

$page_title = "Project | Alma Tech Consults";
$active = "projects";

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';

$db = $GLOBALS['db'] ?? $mysqli ?? null;

// Get slug from URL
$slug = trim((string)($_GET['slug'] ?? ''));

if (!$slug) {
  http_response_code(404);
  require_once __DIR__ . '/includes/header.php';
  ?>
  <section class="section">
    <div class="container py-5">
      <div class="service-card p-4">
        <h1 class="h3 fw-bold mb-2">Project not found</h1>
        <p class="text-muted mb-3">No project slug was provided.</p>
        <a class="btn btn-orange" href="projects.php"><i class="bi bi-arrow-left me-1"></i> Back to Projects</a>
      </div>
    </div>
  </section>
  <?php
  require_once __DIR__ . '/includes/footer.php';
  exit;
}

// Load project from database
$project = null;

if ($db instanceof mysqli) {
  $stmt = $db->prepare("
    SELECT id, title, slug, short_desc, full_desc, category, cover_image, project_url, tech_stack, created_at
    FROM projects
    WHERE slug = ? AND status = 'completed'
    LIMIT 1
  ");
  
  if ($stmt) {
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $res = $stmt->get_result();
    $project = $res ? $res->fetch_assoc() : null;
    $stmt->close();
  }
}

if (!$project) {
  http_response_code(404);
  require_once __DIR__ . '/includes/header.php';
  ?>
  <section class="section">
    <div class="container py-5">
      <div class="service-card p-4">
        <h1 class="h3 fw-bold mb-2">Project not found</h1>
        <p class="text-muted mb-3">This project does not exist or is not published.</p>
        <a class="btn btn-orange" href="projects.php"><i class="bi bi-arrow-left me-1"></i> Back to Projects</a>
      </div>
    </div>
  </section>
  <?php
  require_once __DIR__ . '/includes/footer.php';
  exit;
}

// Tech stack decode (JSON)
$tech_stack_arr = [];
if (!empty($project['tech_stack'])) {
  $decoded = json_decode((string)$project['tech_stack'], true);
  if (is_array($decoded)) $tech_stack_arr = $decoded;
}

// URLs
$BASE = defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '';
$cover = trim((string)($project['cover_image'] ?? ''));
$image_url = $cover !== '' ? ($BASE . '/' . ltrim($cover, '/')) : '';

// Update title after we have project
$page_title = (string)$project['title'] . ' | Alma Tech Consults';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="hero">
  <div class="container py-5">

    <!-- Breadcrumb -->
    <div class="mb-3 small text-muted">
      <a class="text-decoration-none" href="index.php">Home</a>
      <span class="mx-2">/</span>
      <a class="text-decoration-none" href="projects.php">Projects</a>
      <span class="mx-2">/</span>
      <span class="text-orange fw-semibold"><?= h((string)$project['title']) ?></span>
    </div>

    <div class="row g-4 align-items-center">
      <div class="col-lg-7">
        <div class="badge-soft mb-3">
          <i class="bi bi-briefcase-fill me-1"></i> <?= h((string)($project['category'] ?? 'Project')) ?>
        </div>

        <h1 class="display-6 fw-bold mb-3"><?= h((string)$project['title']) ?></h1>

        <?php if (!empty($project['short_desc'])): ?>
          <p class="lead text-muted mb-4"><?= h((string)$project['short_desc']) ?></p>
        <?php else: ?>
          <p class="lead text-muted mb-4">A completed project delivered by Alma Tech Consults.</p>
        <?php endif; ?>

        <div class="d-flex flex-wrap gap-2">
          <?php
          // Use external URL if available, otherwise no "View Project" button
          $project_url = !empty($project['project_url']) ? (string)$project['project_url'] : null;
          if ($project_url): ?>
            <a class="btn btn-orange btn-lg" href="<?= h($project_url) ?>" target="_blank" rel="noopener">
              <i class="bi bi-box-arrow-up-right me-1"></i> View Project
            </a>
          <?php endif; ?>

          <a class="btn btn-orange btn-lg" href="contact.php?service=<?= urlencode((string)$project['title']) ?>">
            Request Similar Project <i class="bi bi-arrow-right ms-1"></i>
          </a>
          <a class="btn btn-outline-orange btn-lg" href="projects.php">
            <i class="bi bi-arrow-left me-1"></i> Back to Projects
          </a>
        </div>

        <div class="meta-row mt-4">
          <div><i class="bi bi-calendar3 me-1"></i><?= !empty($project['created_at']) ? h(date('Y', strtotime((string)$project['created_at']))) : h(date('Y')) ?></div>
          <div><i class="bi bi-building me-1"></i>Client</div>
          <?php if (!empty($tech_stack_arr)): ?>
            <div><i class="bi bi-code-slash me-1"></i><?= h((string)count($tech_stack_arr)) ?> Technologies</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="hero-card p-4">
          <div class="fw-semibold mb-2">Project Highlights</div>
          <ul class="list-unstyled m-0 small text-muted">
            <li class="mb-2"><i class="bi bi-check-circle text-orange me-2"></i> Clean UI & responsive layout</li>
            <li class="mb-2"><i class="bi bi-check-circle text-orange me-2"></i> Secure backend & structured code</li>
            <li class="mb-2"><i class="bi bi-check-circle text-orange me-2"></i> Performance & SEO-ready pages</li>
            <li class="mb-0"><i class="bi bi-check-circle text-orange me-2"></i> Support & maintenance available</li>
          </ul>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- Main Content -->
<section class="section">
  <div class="container">

    <div class="row g-4">
      <!-- Left -->
      <div class="col-lg-8">

        <!-- Cover -->
        <div class="project-cover-wrap mb-4">
          <?php if (!empty($image_url)): ?>
            <img class="project-cover-img" src="<?= h($image_url) ?>" alt="<?= h((string)$project['title']) ?>">
          <?php else: ?>
            <div class="project-cover-fallback">
              <div style="font-size:2rem;"><i class="bi bi-image"></i></div>
              <div class="mt-2 fw-bold">Project Cover</div>
              <div class="small text-muted">No image uploaded</div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Details -->
        <div class="service-card p-4">
          <h2 class="h4 fw-bold mb-3">Project Details</h2>

          <?php if (!empty($project['full_desc'])): ?>
            <div class="text-muted" style="line-height:1.75;">
              <?= nl2br(h((string)$project['full_desc'])) ?>
            </div>
          <?php else: ?>
            <p class="text-muted mb-0">
              More details will be added soon. If you need a similar solution, request a quote and we'll share the full scope.
            </p>
          <?php endif; ?>
        </div>

      </div>

      <!-- Right -->
      <div class="col-lg-4">

        <?php if (!empty($tech_stack_arr)): ?>
          <div class="service-card p-4 mb-4">
            <h3 class="h6 fw-bold mb-3">Tech Stack</h3>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach ($tech_stack_arr as $t): ?>
                <?php if (is_string($t) && trim($t) !== ''): ?>
                  <span class="chip"><i class="bi bi-code-slash"></i><?= h(trim($t)) ?></span>
                <?php endif; ?>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <div class="cta p-4">
          <div class="fw-semibold mb-2">Want this exact setup?</div>
          <div class="small text-muted mb-3">We can build your public website + admin dashboard to manage everything.</div>
          <a class="btn btn-orange w-100 btn-lg" href="contact.php?service=Website%20%2B%20Dashboard">
            Get a Quote <i class="bi bi-arrow-right ms-1"></i>
          </a>

          <div class="small text-muted mt-3">
            Prefer WhatsApp? Use the contact page and we'll respond fast.
          </div>
        </div>

      </div>
    </div>

  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<!-- Responsive JavaScript for Project Page -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Check screen width and adjust layout
  function adjustLayoutForMobile() {
    const isMobile = window.innerWidth < 768;
    const isTablet = window.innerWidth >= 768 && window.innerWidth < 1024;
    
    // Hide elements on mobile (keep project cover visible)
    const elementsToHideOnMobile = [
      '.meta-row',
      '.hero-card',
      '.hero .col-lg-7 .d-flex.gap-2'
    ];
    
    const elementsToHideOnSmallMobile = [
      '.breadcrumb',
      '.cta'
    ];
    
    // Show/hide based on screen size
    elementsToHideOnMobile.forEach((selector) => {
      const elements = document.querySelectorAll(selector);
      elements.forEach((el) => {
        el.style.display = isMobile ? 'none' : '';
      });
    });
    
    elementsToHideOnSmallMobile.forEach((selector) => {
      const elements = document.querySelectorAll(selector);
      elements.forEach((el) => {
        el.style.display = window.innerWidth < 480 ? 'none' : '';
      });
    });
    
    // Reorder content for mobile
    if (isMobile) {
      reorderContentForMobile();
    } else {
      restoreOriginalOrder();
    }
    
    // Adjust button sizes
    adjustButtonsForMobile(isMobile, isTablet);
    
    // Adjust tech stack display
    adjustTechStackForMobile(isMobile);
  }
  
  // Reorder content for mobile (move buttons below description)
  function reorderContentForMobile() {
    const heroLeftCol = document.querySelector('.hero .col-lg-7');
    const projectDetails = document.querySelector('.service-card:has(h2)');
    
    if (heroLeftCol && projectDetails) {
      // Move buttons from hero section to below project details on mobile
      const buttonGroup = heroLeftCol.querySelector('.d-flex.gap-2');
      const projectDetailsSection = document.querySelector('.section .col-lg-8');
      
      // Check if mobile buttons already exist to prevent duplication
      const existingMobileButtons = document.querySelector('.mobile-buttons');
      
      if (buttonGroup && projectDetailsSection && !existingMobileButtons) {
        // Create mobile button container
        const mobileButtons = document.createElement('div');
        mobileButtons.className = 'mobile-buttons mt-4';
        mobileButtons.innerHTML = buttonGroup.outerHTML;
        
        // Insert buttons after project details
        projectDetailsSection.appendChild(mobileButtons);
        
        // Hide original buttons
        buttonGroup.style.display = 'none';
      }
    }
  }
  
  // Restore original order for desktop
  function restoreOriginalOrder() {
    // Remove mobile buttons
    const mobileButtons = document.querySelector('.mobile-buttons');
    if (mobileButtons) {
      mobileButtons.remove();
    }
    
    // Show original buttons
    const originalButtonGroup = document.querySelector('.hero .col-lg-7 .d-flex.gap-2');
    if (originalButtonGroup) {
      originalButtonGroup.style.display = '';
    }
  }
  
  // Adjust button sizes and layout
  function adjustButtonsForMobile(isMobile, isTablet) {
    const buttons = document.querySelectorAll('.btn-lg');
    buttons.forEach(btn => {
      if (isMobile) {
        btn.classList.remove('btn-lg');
        btn.classList.add('btn-sm');
      } else if (isTablet) {
        btn.classList.remove('btn-sm');
        btn.classList.add('btn-md');
      } else {
        btn.classList.remove('btn-sm', 'btn-md');
        btn.classList.add('btn-lg');
      }
    });
    
    // Stack buttons vertically on mobile
    const buttonGroups = document.querySelectorAll('.d-flex.gap-2');
    buttonGroups.forEach(group => {
      if (isMobile && group.querySelector('.btn')) {
        group.classList.remove('d-flex');
        group.classList.add('d-grid', 'gap-2');
      } else if (!isMobile) {
        group.classList.remove('d-grid');
        group.classList.add('d-flex');
      }
    });
  }
  
  // Adjust tech stack display
  function adjustTechStackForMobile(isMobile) {
    const techStackContainer = document.querySelector('.service-card:has(.chip)');
    if (techStackContainer) {
      const chips = techStackContainer.querySelectorAll('.chip');
      if (isMobile) {
        // Limit chips shown on mobile
        chips.forEach((chip, index) => {
          if (index > 3) chip.style.display = 'none';
        });
        
        // Add "show more" button if there are more than 4 chips
        if (chips.length > 4 && !techStackContainer.querySelector('.show-more-chips')) {
          const showMoreBtn = document.createElement('button');
          showMoreBtn.className = 'btn btn-sm btn-outline-orange show-more-chips mt-2';
          showMoreBtn.textContent = `+${chips.length - 4} more`;
          showMoreBtn.onclick = function() {
            chips.forEach(chip => chip.style.display = '');
            this.remove();
          };
          techStackContainer.appendChild(showMoreBtn);
        }
      } else {
        // Show all chips on desktop
        chips.forEach(chip => chip.style.display = '');
        const showMoreBtn = techStackContainer.querySelector('.show-more-chips');
        if (showMoreBtn) showMoreBtn.remove();
      }
    }
  }
  
  // Add mobile-specific CSS
  function addMobileCSS() {
    const mobileCSS = `
      <style id="mobile-optimizations">
        @media (max-width: 767px) {
          .hero {
            padding: 2rem 0 !important;
          }
          
          .hero h1 {
            font-size: 1.75rem !important;
            margin-top: 1rem !important;
          }
          
          .hero .lead {
            font-size: 1rem !important;
          }
          
          .service-card {
            padding: 1rem !important;
            margin-bottom: 1rem !important;
          }
          
          .project-cover-img {
            height: 200px !important;
          }
          
          .chip {
            font-size: 0.7rem !important;
            padding: 4px 8px !important;
          }
          
          .cta {
            padding: 1rem !important;
          }
          
          .section {
            padding: 2rem 0 !important;
          }
        }
        
        @media (max-width: 480px) {
          .hero h1 {
            font-size: 1.5rem !important;
          }
          
          .container {
            padding-left: 1rem !important;
            padding-right: 1rem !important;
          }
        }
      </style>
    `;
    
    // Remove existing mobile CSS if present
    const existingCSS = document.getElementById('mobile-optimizations');
    if (existingCSS) existingCSS.remove();
    
    // Add new mobile CSS
    document.head.insertAdjacentHTML('beforeend', mobileCSS);
  }
  
  // Initialize
  adjustLayoutForMobile();
  addMobileCSS();
  
  // Listen for resize events
  let resizeTimer;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function() {
      adjustLayoutForMobile();
    }, 250);
  });
});
</script>
</script>
