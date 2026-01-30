<?php
declare(strict_types=1);

$page_title = "Post | Alma Tech Consults";
$active = "blog";
require_once __DIR__ . '/includes/header.php';

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$slug = trim((string)($_GET['slug'] ?? ''));

/**
 * Same post data as blog.php (for now).
 * Later: fetch from DB with WHERE slug = ?
 */
$posts = [
  [
    'title' => 'Why Every Business Needs a Website in 2026',
    'slug' => 'why-every-business-needs-a-website-2026',
    'category' => 'Web',
    'excerpt' => 'A professional website builds trust, improves visibility, and helps you convert clients faster.',
    'date' => '2026-01-10',
    'cover' => 'assets/img/blog-1.jpg',
    'read_mins' => 4,
    'featured' => true,
    'content' => [
      [
        'h' => '1) Trust & Credibility',
        'p' => 'When someone hears about your business, the first thing they do is search online. A clean website makes your business look legitimate and professional.'
      ],
      [
        'h' => '2) Visibility on Google',
        'p' => 'A website helps you appear in Google searches when people look for services like yours. With good SEO, you get steady leads without constantly spending on ads.'
      ],
      [
        'h' => '3) A Website Works 24/7',
        'p' => 'Even when your office is closed, your website keeps explaining your services, showing your work, and collecting inquiries from customers.'
      ],
      [
        'h' => '4) Better Conversions',
        'p' => 'A website is not just “online presence.” It is a conversion tool: clear call-to-actions, WhatsApp buttons, and quote forms increase customers contacting you.'
      ],
      [
        'h' => '5) Add a Dashboard (Admin Panel)',
        'p' => 'With an admin dashboard, you can update services, portfolio, blog posts, and client messages without needing a developer every time.'
      ],
    ],
  ],
  [
    'title' => 'How to Choose the Right Laptop for Office Work',
    'slug' => 'choose-right-laptop-office',
    'category' => 'Hardware',
    'excerpt' => 'Learn what CPU, RAM, and storage specs matter most for business and school usage.',
    'date' => '2026-01-06',
    'cover' => 'assets/img/blog-2.jpg',
    'read_mins' => 5,
    'featured' => false,
    'content' => [
      ['h' => '1) RAM Matters', 'p' => 'For office work, 8GB RAM is the minimum. If you use heavy apps, go for 16GB.'],
      ['h' => '2) SSD vs HDD', 'p' => 'Choose SSD for speed. HDD is slower but can be used for backup storage.'],
      ['h' => '3) Processor', 'p' => 'For reliability, choose i5/Ryzen 5 or higher depending on budget and workload.'],
    ],
  ],
  [
    'title' => 'Social Media Marketing: What Actually Works',
    'slug' => 'social-media-marketing-what-works',
    'category' => 'Marketing',
    'excerpt' => 'Simple practical tips for creating posts, running ads, and tracking leads without wasting budget.',
    'date' => '2025-12-20',
    'cover' => 'assets/img/blog-3.jpg',
    'read_mins' => 6,
    'featured' => false,
    'content' => [
      ['h' => '1) Post Consistently', 'p' => 'Consistency beats perfection. Use a weekly content plan.'],
      ['h' => '2) Use Clear Offers', 'p' => 'Tell people exactly what you offer, price range, and how to contact you.'],
      ['h' => '3) Track Leads', 'p' => 'Use WhatsApp links, forms, and a simple lead tracker in your dashboard.'],
    ],
  ],
  [
    'title' => 'How to Keep Your Office Computers Fast & Secure',
    'slug' => 'keep-office-computers-fast-secure',
    'category' => 'IT Support',
    'excerpt' => 'Best practices for updates, antivirus, backups, and maintenance routines.',
    'date' => '2025-12-11',
    'cover' => 'assets/img/blog-4.jpg',
    'read_mins' => 5,
    'featured' => false,
    'content' => [
      ['h' => '1) Updates', 'p' => 'Keep Windows and apps updated for security patches.'],
      ['h' => '2) Antivirus', 'p' => 'Use a trusted antivirus and run scheduled scans.'],
      ['h' => '3) Backups', 'p' => 'Use external drives or cloud backups for critical files.'],
    ],
  ],
  [
    'title' => 'Branding Basics: Logo, Colors, and Consistency',
    'slug' => 'branding-basics-logo-colors-consistency',
    'category' => 'Branding',
    'excerpt' => 'What makes a brand look professional and how to stay consistent across platforms.',
    'date' => '2025-11-28',
    'cover' => 'assets/img/blog-5.jpg',
    'read_mins' => 4,
    'featured' => false,
    'content' => [
      ['h' => '1) A Simple Logo', 'p' => 'A clean logo that works in black and white is a strong foundation.'],
      ['h' => '2) Color System', 'p' => 'Pick 2–3 main colors and stick to them across designs.'],
      ['h' => '3) Consistency', 'p' => 'Use the same fonts, colors, and tone across all platforms.'],
    ],
  ],
];

// Find post by slug
$post = null;
foreach ($posts as $p) {
  if ((string)$p['slug'] === $slug) { $post = $p; break; }
}

if (!$post) {
  http_response_code(404);
}
?>

<?php if (!$post): ?>
  <section class="section">
    <div class="container">
      <div class="service-card">
        <h1 class="h3 fw-bold mb-2">Post not found</h1>
        <p class="text-muted mb-3">The post you’re looking for does not exist or the link is incorrect.</p>
        <a class="btn btn-orange" href="blog.php"><i class="bi bi-arrow-left me-1"></i> Back to Blog</a>
      </div>
    </div>
  </section>

<?php else: ?>

  <?php
    $page_title = $post['title'] . " | Alma Tech Consults";
    $cover = (string)$post['cover'];
    $has_img = is_file(__DIR__ . '/' . $cover);
    $wa_number = "256XXXXXXXXX"; // change (no +)
    $share_text = "Check this article: " . $post['title'];
    $wa_share = "https://wa.me/" . $wa_number . "?text=" . urlencode($share_text);
  ?>

  <!-- Hero -->
  <section class="hero">
    <div class="container py-5">
      <div class="mb-3 small text-muted">
        <a class="text-decoration-none" href="index.php">Home</a>
        <span class="mx-2">/</span>
        <a class="text-decoration-none" href="blog.php">Blog</a>
        <span class="mx-2">/</span>
        <span class="text-orange fw-semibold"><?= h($post['title']) ?></span>
      </div>

      <div class="row g-4 align-items-center">
        <div class="col-lg-8">
          <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="pill-tag"><?= h($post['category']) ?></span>
            <span class="muted-pill"><i class="bi bi-clock me-1"></i><?= (int)$post['read_mins'] ?> min read</span>
            <span class="muted-pill"><i class="bi bi-calendar3 me-1"></i><?= h($post['date']) ?></span>
          </div>

          <h1 class="display-6 fw-bold mb-3"><?= h($post['title']) ?></h1>
          <p class="lead text-muted mb-0"><?= h($post['excerpt']) ?></p>

          <div class="d-flex flex-wrap gap-2 mt-4">
            <a class="btn btn-orange btn-lg" href="contact.php?service=<?= urlencode("Consultation: " . $post['category']) ?>">
              Request Help <i class="bi bi-arrow-right ms-1"></i>
            </a>
            <a class="btn btn-outline-orange btn-lg" href="<?= h($wa_share) ?>" target="_blank" rel="noopener">
              Share on WhatsApp <i class="bi bi-whatsapp ms-1"></i>
            </a>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="post-hero-cover">
            <?php if ($has_img): ?>
              <img src="<?= h($cover) ?>" alt="<?= h($post['title']) ?>">
            <?php else: ?>
              <div class="project-cover-fallback">
                <i class="bi bi-image"></i>
                <div class="mt-2 fw-semibold">Cover image</div>
                <div class="small text-muted">Add an image in assets/img</div>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- Content -->
  <section class="section">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-8">

          <div class="service-card">
            <?php foreach (($post['content'] ?? []) as $block): ?>
              <h2 class="h5 fw-bold mt-3"><?= h($block['h'] ?? '') ?></h2>
              <p class="text-muted mb-0"><?= h($block['p'] ?? '') ?></p>
              <hr class="my-4">
            <?php endforeach; ?>

            <div class="small text-muted">
              Need this implemented? We can help you with a website, dashboard, marketing, branding, or ICT support.
            </div>
          </div>

        </div>

        <div class="col-lg-4">
          <div class="service-card mb-4">
            <h3 class="h6 fw-bold mb-3">Need a Website + Dashboard?</h3>
            <p class="text-muted small mb-3">
              We build public websites plus admin dashboards to manage services, projects, blog posts, and leads.
            </p>
            <a class="btn btn-orange w-100" href="contact.php?service=Website%20%2B%20Dashboard">Get a Quote</a>
          </div>

          <div class="service-card">
            <h3 class="h6 fw-bold mb-3">Related Posts</h3>
            <?php
              $related = [];
              foreach ($posts as $rp) {
                if ($rp['slug'] === $post['slug']) continue;
                if ($rp['category'] === $post['category']) $related[] = $rp;
              }
              if (!$related) {
                // fallback
                foreach ($posts as $rp) {
                  if ($rp['slug'] !== $post['slug']) $related[] = $rp;
                }
              }
              $related = array_slice($related, 0, 3);
            ?>

            <?php foreach ($related as $r): ?>
              <a class="related-link" href="post.php?slug=<?= urlencode($r['slug']) ?>">
                <div class="fw-semibold"><?= h($r['title']) ?></div>
                <div class="small text-muted"><?= h($r['category']) ?> • <?= h($r['date']) ?></div>
              </a>
            <?php endforeach; ?>
          </div>

        </div>
      </div>
    </div>
  </section>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
