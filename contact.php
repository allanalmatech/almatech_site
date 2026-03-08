<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/gate.php';

$page_title = "Contact | Alma Tech Consults";
$active = "contact";
require_once __DIR__ . '/includes/header.php';

// CSRF
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$db = $GLOBALS['db'] ?? $mysqli ?? null;

$contactHeroCover = '';
$contactHeroTextClass = 'text-muted';
if ($db instanceof mysqli && function_exists('setting_get')) {
  $contactCover = trim((string)setting_get($db, 'contact_cover_image', ''));
  if ($contactCover !== '') {
    $contactHeroCover = (strpos($contactCover, 'http') === 0)
      ? $contactCover
      : rtrim((string)BASE_URL, '/') . '/' . ltrim($contactCover, '/');
    $contactHeroTextClass = '';
  }
}

// -------------------- Load services from database --------------------  
$services = [];
if ($db instanceof mysqli) {
  $stmt = $db->prepare("SELECT title FROM services WHERE is_active = 1 ORDER BY title ASC");
  if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
      while ($row = $result->fetch_assoc()) {
        $services[] = $row['title'];
      }
    }
    $stmt->close();
  }
}

// -------------------- Load contact settings from DB --------------------
$contact_location = 'Mbarara, Uganda';
$contact_email    = 'info@almatechconsults.com';
$contact_phone    = '+256 XXX XXX XXX';
$wa_number        = '256XXXXXXXXX'; // no '+'
$map_embed        = ''; // Will load from database
$recaptcha_site_key = '';
$recaptcha_secret_key = '';

if ($db instanceof mysqli) {
  // Load contact-specific settings
  $keys = [
    'contact_location',
    'contact_email',
    'contact_phone',
    'contact_whatsapp',
    'contact_map_embed',
    'recaptcha_site_key',
    'recaptcha_secret_key',
  ];

  $placeholders = implode(',', array_fill(0, count($keys), '?'));
  $types = str_repeat('s', count($keys));

  $stmt = $db->prepare("SELECT `key`, `value` FROM settings WHERE `key` IN ($placeholders)");
  if ($stmt) {
    $stmt->bind_param($types, ...$keys);
    $stmt->execute();
    $res = $stmt->get_result();
    $kv = [];
    while ($row = $res->fetch_assoc()) {
      $kv[(string)$row['key']] = (string)($row['value'] ?? '');
    }
    $stmt->close();

    if (!empty($kv['contact_location'])) $contact_location = $kv['contact_location'];
    if (!empty($kv['contact_email']))    $contact_email    = $kv['contact_email'];
    if (!empty($kv['contact_phone']))    $contact_phone    = $kv['contact_phone'];
    if (!empty($kv['contact_whatsapp'])) $wa_number        = $kv['contact_whatsapp'];
    if (isset($kv['contact_map_embed'])) $map_embed        = $kv['contact_map_embed'];
    if (isset($kv['recaptcha_site_key'])) $recaptcha_site_key = trim((string)$kv['recaptcha_site_key']);
    if (isset($kv['recaptcha_secret_key'])) $recaptcha_secret_key = trim((string)$kv['recaptcha_secret_key']);
  }
}

$useRecaptcha = ($recaptcha_site_key !== '' && $recaptcha_secret_key !== '');

// -------------------- Form values --------------------
$errors = [];
$ok = false;

$name    = trim((string)($_POST['name'] ?? ($_GET['name'] ?? '')));
$phone   = trim((string)($_POST['phone'] ?? ($_GET['phone'] ?? '')));
$email   = trim((string)($_POST['email'] ?? ''));
$service = trim((string)($_POST['service'] ?? ($_GET['service'] ?? '')));
$subject = trim((string)($_POST['subject'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

// WhatsApp link (prefilled)
$wa_number_digits = preg_replace('/\D+/', '', $wa_number);
$wa_text = "Hello Alma Tech Consults.%0A%0A" .
           "Name: " . rawurlencode($name ?: '___') . "%0A" .
           "Phone: " . rawurlencode($phone ?: '___') . "%0A" .
           "Email: " . rawurlencode($email ?: 'Not provided') . "%0A" .
           "Service: " . rawurlencode($service ?: 'Not selected') . "%0A" .
           "Subject: " . rawurlencode($subject ?: 'Not provided') . "%0A%0A" .
           "Message:%0A" . rawurlencode($message ?: 'I need help with your services.');
$wa_link = "https://wa.me/" . $wa_number_digits . "?text=" . $wa_text;

// -------------------- Handle POST --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // CSRF
  $posted_csrf = (string)($_POST['csrf_token'] ?? '');
  if (!$posted_csrf || !hash_equals($csrf, $posted_csrf)) {
    $errors[] = "Security check failed. Refresh and try again.";
  }

  if ($useRecaptcha) {
    $captchaToken = (string)($_POST['recaptcha_token'] ?? ($_POST['g-recaptcha-response'] ?? ''));
    $remoteIp = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if (!recaptcha_verify_token($recaptcha_secret_key, $captchaToken, $remoteIp, 'contact_submit')) {
      $errors[] = "reCAPTCHA check failed. Please try again.";
    }
  } else {
    if (!captcha_validate_submission('contact_form', (string)($_POST['captcha_token'] ?? ''), (string)($_POST['captcha_answer'] ?? ''))) {
      $errors[] = "Captcha check failed. Please try again.";
    }
  }

  // Validate
  if ($name === '' || mb_strlen($name) < 2) $errors[] = "Please enter your name.";
  if ($phone === '' || mb_strlen($phone) < 7) $errors[] = "Please enter a valid phone number.";
  if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email (or leave it blank).";
  if ($service === '') $errors[] = "Please select a service.";
  if ($message === '' || mb_strlen($message) < 10) $errors[] = "Please write a short message (at least 10 characters).";

  if (!$errors) {

    // Save to leads table
    if ($db instanceof mysqli) {
      // if your leads table does NOT have updated_at, this still works
      $stmt = $db->prepare("
        INSERT INTO leads (name, phone, email, service, subject, message, status, source, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'new', 'contact-form', NOW())
      ");

      if ($stmt) {
        $stmt->bind_param("ssssss", $name, $phone, $email, $service, $subject, $message);
        $stmt->execute();
        $stmt->close();
      }
    }

    // Optional email notify (uses settings email)
    if (!empty($contact_email)) {
      $mail_subject = "New Contact Lead: " . ($service ?: "Service Request");
      $body =
        "New Lead\n\n" .
        "Name: {$name}\n" .
        "Phone: {$phone}\n" .
        "Email: {$email}\n" .
        "Service: {$service}\n" .
        "Subject: {$subject}\n\n" .
        "Message:\n{$message}\n";

      @mail($contact_email, $mail_subject, $body);
    }

    $ok = true;
    $name = $phone = $email = $service = $subject = $message = '';
  }
}
?>

<!-- Page Hero -->
<section class="hero<?= $contactHeroCover !== '' ? ' hero-cover-blur hero-scroll-blur' : '' ?>"<?= $contactHeroCover !== '' ? ' style="--hero-cover-image:url(\'' . h($contactHeroCover) . '\');"' : '' ?>>
  <div class="container py-5">
    <div class="row g-4 align-items-center">
      <div class="col-lg-7">
        <div class="badge-soft mb-3">
          <i class="bi bi-chat-dots-fill me-1"></i> Contact Us
        </div>

        <h1 class="display-6 fw-bold mb-3">Let’s talk about your project.</h1>
        <p class="lead <?= $contactHeroTextClass ?> mb-0">
          Send a message and we’ll respond quickly. You can also reach us on WhatsApp for fast support.
        </p>
      </div>

      <div class="col-lg-5">
        <div class="hero-card p-4">
          <div class="fw-semibold mb-2">Quick Contacts</div>
          <div class="small text-muted">
            <div class="mb-2"><i class="bi bi-geo-alt me-1 text-orange"></i><?= h($contact_location) ?></div>
            <div class="mb-2"><i class="bi bi-envelope me-1 text-orange"></i><?= h($contact_email) ?></div>
            <div class="mb-3"><i class="bi bi-telephone me-1 text-orange"></i><?= h($contact_phone) ?></div>

          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Contact form + Map -->
<section class="section">
  <div class="container">
    <div class="row g-4">

      <div class="col-lg-7">
        <div class="service-card">
          <h2 class="h4 fw-bold mb-3">Send us a message</h2>

          <?php if ($ok): ?>
            <div class="alert alert-success">✅ Message received! We’ll get back to you shortly.</div>
          <?php endif; ?>

          <?php if ($errors): ?>
            <div class="alert alert-danger">
              <div class="fw-semibold mb-1">Please fix the following:</div>
              <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                  <li><?= h($e) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <form method="post" class="row g-3" novalidate data-recaptcha-enterprise="1" data-recaptcha-action="contact_submit">
            <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
            <input type="hidden" name="recaptcha_token" value="">

            <div class="col-md-6">
              <label class="form-label">Your Name *</label>
              <input class="form-control" name="name" value="<?= h($name) ?>" placeholder="e.g. Allan" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Phone Number *</label>
              <input class="form-control" name="phone" value="<?= h($phone) ?>" placeholder="+256..." required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Email (optional)</label>
              <input class="form-control" name="email" value="<?= h($email) ?>" placeholder="you@email.com">
            </div>

            <div class="col-md-6">
              <label class="form-label">Service *</label>
              <select class="form-select" name="service" required>
                <option value="">Select a service</option>
                <?php
                  // Display services from database
                  foreach ($services as $service_title):
                    $sel = ($service === $service_title) ? 'selected' : '';
                ?>
                  <option <?= $sel ?> value="<?= h($service_title) ?>"><?= h($service_title) ?></option>
                <?php endforeach; ?>
                
                <!-- Add "Other" as the last option -->
                <option value="Other" <?= ($service === 'Other') ? 'selected' : '' ?>>Other</option>
              </select>
            </div>

            <div class="col-12">
              <label class="form-label">Subject (optional)</label>
              <input class="form-control" name="subject" value="<?= h($subject) ?>" placeholder="e.g. Need a website + dashboard">
            </div>

            <div class="col-12">
              <label class="form-label">Message *</label>
              <textarea class="form-control" name="message" rows="5" placeholder="Tell us what you need..." required><?= h($message) ?></textarea>
              <div class="form-text">Include your budget range and timeline if you can.</div>
            </div>

            <?php if ($useRecaptcha): ?>
              <div class="col-12">
                <label class="form-label">Human Verification *</label>
                <div class="small text-muted">Protected by Google reCAPTCHA.</div>
              </div>
            <?php else: ?>
              <?= captcha_render('contact_form', 'Human Verification') ?>
            <?php endif; ?>

            <div class="col-12 d-flex flex-wrap gap-2">
              <button class="btn btn-orange btn-lg" type="submit">
                Send Message <i class="bi bi-send ms-1"></i>
              </button>

              <a class="btn btn-whatsapp btn-lg" href="<?= h($wa_link) ?>" target="_blank" rel="noopener" data-wa-compose data-wa-number="<?= h($wa_number_digits) ?>">
                WhatsApp Instead <i class="bi bi-arrow-right ms-1"></i>
              </a>
            </div>
          </form>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="service-card mb-3">
          <h3 class="h6 fw-bold mb-2">Office Location</h3>

          <div class="map-embed">
            <?php if (!empty(trim($map_embed))): ?>
              <!-- DB-provided embed code -->
              <?php 
              // Check if it's a full iframe or just a URL
              if (strpos($map_embed, '<iframe') === 0) {
                // It's a full iframe, modify to fit well and add zoom
                $modified_iframe = $map_embed;
                // Update width to 100% for responsive design
                $modified_iframe = preg_replace('/width="\d+"/', 'width="100%"', $modified_iframe);
                // Update height to better size
                $modified_iframe = preg_replace('/height="\d+"/', 'height="400"', $modified_iframe);
                // Add zoom controls if not present
                if (strpos($modified_iframe, 'zoom') === false) {
                    // For Google Maps, add zoom parameter to the URL
                    $modified_iframe = preg_replace('/(pb=![^"]*)/', '$1&z=16', $modified_iframe);
                }
                // Add responsive styling
                $modified_iframe = str_replace('style="border:0;"', 'style="border:0; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"', $modified_iframe);
                echo $modified_iframe;
              } else {
                // It's just a URL, create iframe with zoom and styling
                $map_url = $map_embed;
                // Add zoom if not present
                if (strpos($map_url, 'z=') === false) {
                    $map_url .= (strpos($map_url, '?') !== false ? '&' : '?') . 'z=16';
                }
                ?>
                <iframe
                  src="<?= h($map_url) ?>"
                  width="100%"
                  height="400"
                  style="border:0; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);"
                  allowfullscreen=""
                  loading="lazy"
                  referrerpolicy="no-referrer-when-downgrade"></iframe>
                <?php
              }
              ?>
            <?php else: ?>
              <div class="map-placeholder">
                <i class="bi bi-map"></i>
                <div class="mt-2 fw-semibold">Map not set yet</div>
                <div class="small text-muted">Admin can add a Google Maps embed link in Settings.</div>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>

    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var recaptchaForm = document.querySelector('form[data-recaptcha-enterprise]');
  if (recaptchaForm && window.RECAPTCHA_SITE_KEY && window.grecaptcha && window.grecaptcha.enterprise) {
    recaptchaForm.addEventListener('submit', function (event) {
      if (recaptchaForm.dataset.recaptchaDone === '1') {
        recaptchaForm.dataset.recaptchaDone = '0';
        return;
      }

      event.preventDefault();
      var tokenInput = recaptchaForm.querySelector('input[name="recaptcha_token"]');
      var action = recaptchaForm.getAttribute('data-recaptcha-action') || 'submit';

      window.grecaptcha.enterprise.ready(async function () {
        try {
          var token = await window.grecaptcha.enterprise.execute(window.RECAPTCHA_SITE_KEY, { action: action });
          if (tokenInput) {
            tokenInput.value = token;
          }
          recaptchaForm.dataset.recaptchaDone = '1';
          recaptchaForm.submit();
        } catch (error) {
          alert('reCAPTCHA failed to load. Please refresh and try again.');
        }
      });
    });
  }

  var waBtn = document.querySelector('[data-wa-compose]');
  var form = document.querySelector('form[novalidate]');
  if (!waBtn || !form) return;

  function readField(name) {
    var el = form.querySelector('[name="' + name + '"]');
    return el ? String(el.value || '').trim() : '';
  }

  function buildMessage() {
    var name = readField('name') || '___';
    var phone = readField('phone') || '___';
    var email = readField('email') || 'Not provided';
    var service = readField('service') || 'Not selected';
    var subject = readField('subject') || 'Not provided';
    var message = readField('message') || 'I need help with your services.';

    return [
      'Hello Alma Tech Consults.',
      '',
      'Name: ' + name,
      'Phone: ' + phone,
      'Email: ' + email,
      'Service: ' + service,
      'Subject: ' + subject,
      '',
      'Message:',
      message
    ].join('\n');
  }

  waBtn.addEventListener('click', function (event) {
    event.preventDefault();
    var number = waBtn.getAttribute('data-wa-number') || '';
    var url = 'https://wa.me/' + encodeURIComponent(number) + '?text=' + encodeURIComponent(buildMessage());
    window.open(url, '_blank', 'noopener');
  });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
