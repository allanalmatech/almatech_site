<?php
// Load settings from database
require_once __DIR__ . '/../admin/includes/settings_lib.php';
require_once __DIR__ . '/db.php';

$db = $GLOBALS['db'] ?? $mysqli ?? null;

// Get company and contact settings
$company_name = setting_get($db, 'company_name', 'Alma Tech Consults');
$company_motto = setting_get($db, 'company_motto', 'ICT solutions, web development, branding, and digital growth services in Uganda.');
$address = setting_get($db, 'company_address', 'Mbarara, Uganda');
$email = setting_get($db, 'contact_email', 'info@almatechconsults.com');
$phone = setting_get($db, 'contact_phone', '+256 XXX XXX XXX');
$footer_note = setting_get($db, 'footer_note', 'Built with <span class="text-orange">❤</span> in Uganda.');

// Get social links
$social = setting_get_json($db, 'social_links', [
  'facebook' => '',
  'instagram' => '',
  'twitter' => '',
  'linkedin' => '',
  'youtube' => '',
  'tiktok' => '',
]);
?>

<footer class="footer">
  <div class="container py-5">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="fw-bold mb-2"><?= h($company_name) ?></div>
        <p class="text-muted mb-3"><?= h($company_motto) ?></p>
        <div class="d-flex gap-2">
          <?php if (!empty($social['facebook'])): ?>
            <?php 
            $fb_url = $social['facebook'];
            // Ensure URL has protocol
            if (!preg_match('/^https?:\/\//', $fb_url)) {
              $fb_url = 'https://' . $fb_url;
            }
            ?>
            <a class="footer-link" href="<?= h($fb_url) ?>" target="_blank"><i class="bi bi-facebook"></i></a>
          <?php endif; ?>
          <?php if (!empty($social['twitter'])): ?>
            <?php 
            $twitter_url = $social['twitter'];
            // Ensure URL has protocol
            if (!preg_match('/^https?:\/\//', $twitter_url)) {
              $twitter_url = 'https://' . $twitter_url;
            }
            ?>
            <a class="footer-link" href="<?= h($twitter_url) ?>" target="_blank"><i class="bi bi-twitter-x"></i></a>
          <?php endif; ?>
          <?php if (!empty($social['instagram'])): ?>
            <?php 
            $instagram_url = $social['instagram'];
            // Ensure URL has protocol
            if (!preg_match('/^https?:\/\//', $instagram_url)) {
              $instagram_url = 'https://' . $instagram_url;
            }
            ?>
            <a class="footer-link" href="<?= h($instagram_url) ?>" target="_blank"><i class="bi bi-instagram"></i></a>
          <?php endif; ?>
          <?php if (!empty($social['linkedin'])): ?>
            <?php 
            $linkedin_url = $social['linkedin'];
            // Ensure URL has protocol
            if (!preg_match('/^https?:\/\//', $linkedin_url)) {
              $linkedin_url = 'https://' . $linkedin_url;
            }
            ?>
            <a class="footer-link" href="<?= h($linkedin_url) ?>" target="_blank"><i class="bi bi-linkedin"></i></a>
          <?php endif; ?>
          <?php if (!empty($social['youtube'])): ?>
            <?php 
            $youtube_url = $social['youtube'];
            // Ensure URL has protocol
            if (!preg_match('/^https?:\/\//', $youtube_url)) {
              $youtube_url = 'https://' . $youtube_url;
            }
            ?>
            <a class="footer-link" href="<?= h($youtube_url) ?>" target="_blank"><i class="bi bi-youtube"></i></a>
          <?php endif; ?>
          <?php if (!empty($social['tiktok'])): ?>
            <?php 
            $tiktok_url = $social['tiktok'];
            // Ensure URL has protocol
            if (!preg_match('/^https?:\/\//', $tiktok_url)) {
              $tiktok_url = 'https://' . $tiktok_url;
            }
            ?>
            <a class="footer-link" href="<?= h($tiktok_url) ?>" target="_blank"><i class="bi bi-tiktok"></i></a>
          <?php endif; ?>
        </div>
      </div>

      <div class="col-6 col-lg-2">
        <div class="fw-semibold mb-2">Company</div>
        <ul class="list-unstyled small m-0">
          <li><a class="footer-a" href="about.php">About</a></li>
          <li><a class="footer-a" href="projects.php">Projects</a></li>
          <li><a class="footer-a" href="blog.php">Blog</a></li>
        </ul>
      </div>

      <div class="col-6 col-lg-2">
        <div class="fw-semibold mb-2">Services</div>
        <ul class="list-unstyled small m-0">
          <li><a class="footer-a" href="services.php">Web Design</a></li>
          <li><a class="footer-a" href="services.php">Marketing</a></li>
          <li><a class="footer-a" href="services.php">Branding</a></li>
          <li><a class="footer-a" href="services.php">IT Support</a></li>
        </ul>
      </div>

      <div class="col-lg-4">
        <div class="fw-semibold mb-2">Contact</div>
        <div class="small text-muted">
          <?php if (!empty($address)): ?>
            <div class="mb-1"><i class="bi bi-geo-alt me-1"></i> <?= h($address) ?></div>
          <?php endif; ?>
          <?php if (!empty($email)): ?>
            <div class="mb-1"><i class="bi bi-envelope me-1"></i> <?= h($email) ?></div>
          <?php endif; ?>
          <?php if (!empty($phone)): ?>
            <div class="mb-3"><i class="bi bi-telephone me-1"></i> <?= h($phone) ?></div>
          <?php endif; ?>
          <a class="btn btn-orange w-100" href="contact.php"><i class="bi bi-chat-dots me-1"></i> Send a Message</a>
        </div>
      </div>
    </div>

    <hr class="my-4">

    <div class="d-flex flex-column flex-md-row justify-content-between small text-muted gap-2">
      <div>© <?= date('Y') ?> <?= h($company_name) ?>. All rights reserved.</div>
      <div><?= $footer_note ?></div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/stats-counter.js"></script>
<script>
function slideById(id, dir=1){
  const el = document.getElementById(id);
  if(!el) return;
  el.scrollBy({ left: dir * Math.max(260, el.clientWidth * 0.7), behavior:'smooth' });
}
</script>

</body>
</html>
