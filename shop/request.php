<?php
declare(strict_types=1);

$page_title = 'Request a Gadget | Alma Tech Consults';
$active = 'shop';

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/gate.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';

$BASE = rtrim((string)BASE_URL, '/');
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'www.almatechconsults.com';
$ORIGIN = $scheme . '://' . $host;
$recaptchaSiteKey = trim((string)setting('recaptcha_site_key', ''));
$recaptchaSecretKey = trim((string)setting('recaptcha_secret_key', ''));
$mathFallbackEnabled = setting('recaptcha_fallback_math_enabled', '0') === '1';
$useRecaptcha = ($recaptchaSiteKey !== '');

db()->exec("CREATE TABLE IF NOT EXISTS gadget_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(140) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    email VARCHAR(190) NULL,
    gadget_name VARCHAR(190) NOT NULL,
    description TEXT NOT NULL,
    image_path VARCHAR(255) NULL,
    status ENUM('pending', 'done') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_gadget_requests_status (status),
    INDEX idx_gadget_requests_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$errors = [];
$form = [
    'full_name' => '',
    'phone' => '',
    'email' => '',
    'gadget_name' => '',
    'description' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require_valid_request();

    if ($useRecaptcha) {
        $captchaToken = (string)($_POST['recaptcha_token'] ?? ($_POST['g-recaptcha-response'] ?? ''));
        $remoteIp = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $recaptchaOk = recaptcha_verify_token($recaptchaSecretKey, $captchaToken, $remoteIp, 'shop_request_submit', 0.3);
        if (!$recaptchaOk) {
            if ($mathFallbackEnabled) {
                $captchaFallbackOk = captcha_validate_submission('shop_gadget_request', (string)($_POST['captcha_token'] ?? ''), (string)($_POST['captcha_answer'] ?? ''));
                if (!$captchaFallbackOk) {
                    $errors[] = 'Security verification failed. Please solve the captcha and try again.';
                }
            } else {
                $errors[] = 'reCAPTCHA check failed. Please refresh and try again.';
            }
        }
    } else {
        if (!captcha_validate_submission('shop_gadget_request', (string)($_POST['captcha_token'] ?? ''), (string)($_POST['captcha_answer'] ?? ''))) {
            $errors[] = 'Captcha check failed. Please try again.';
        }
    }

    $form['full_name'] = trim((string)($_POST['full_name'] ?? ''));
    $form['phone'] = trim((string)($_POST['phone'] ?? ''));
    $form['email'] = trim((string)($_POST['email'] ?? ''));
    $form['gadget_name'] = trim((string)($_POST['gadget_name'] ?? ''));
    $form['description'] = trim((string)($_POST['description'] ?? ''));

    if ($form['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }

    $phoneDigits = preg_replace('/\D+/', '', $form['phone']);
    if ($phoneDigits === '' || strlen($phoneDigits) < 9) {
        $errors[] = 'Please provide a valid phone number.';
    }

    if ($form['gadget_name'] === '') {
        $errors[] = 'Gadget name is required.';
    }

    if ($form['description'] === '') {
        $errors[] = 'Please add a short description of what you need.';
    }

    if ($form['email'] !== '' && !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if (!$errors) {
        $uploadedImage = null;

        try {
            if (isset($_FILES['reference_image'])) {
                $uploadedImage = validate_request_image_upload($_FILES['reference_image']);
            }

            $stmt = db()->prepare('INSERT INTO gadget_requests (full_name, phone, email, gadget_name, description, image_path, status, created_at, updated_at) VALUES (:full_name, :phone, :email, :gadget_name, :description, :image_path, :status, NOW(), NOW())');
            $stmt->execute([
                ':full_name' => $form['full_name'],
                ':phone' => $form['phone'],
                ':email' => $form['email'] !== '' ? $form['email'] : null,
                ':gadget_name' => $form['gadget_name'],
                ':description' => $form['description'],
                ':image_path' => $uploadedImage,
                ':status' => 'pending',
            ]);

            $requestId = (int)db()->lastInsertId();
            $requestUrl = $ORIGIN . $BASE . '/shop/request';
            $imageUrl = $uploadedImage ? ($ORIGIN . app_url('uploads/requests/' . rawurlencode($uploadedImage))) : '';

            $messageLines = [
                'Hello, I have submitted a gadget request.',
                '',
                'Request ID: #' . $requestId,
                'Name: ' . $form['full_name'],
                'Phone: ' . $form['phone'],
                'Email: ' . ($form['email'] !== '' ? $form['email'] : '-'),
                'Gadget: ' . $form['gadget_name'],
                'Description: ' . $form['description'],
                'Request Page: ' . $requestUrl,
            ];

            if ($imageUrl !== '') {
                $messageLines[] = 'Image: ' . $imageUrl;
            }

            $waUrl = 'https://wa.me/' . rawurlencode(whatsapp_number()) . '?text=' . rawurlencode(implode("\n", $messageLines));

            $_SESSION['gadget_request_whatsapp_url'] = $waUrl;
            set_flash('success', 'Request submitted successfully. We are opening WhatsApp to notify our sales team.');
            redirect_to(shop_url('request'));
        } catch (Throwable $e) {
            if ($uploadedImage) {
                remove_gadget_request_image_file($uploadedImage);
            }
            $errors[] = $e->getMessage();
        }
    }
}

$flash = get_flash();
$whatsAppRedirectUrl = '';
if (!empty($_SESSION['gadget_request_whatsapp_url'])) {
    $whatsAppRedirectUrl = (string)$_SESSION['gadget_request_whatsapp_url'];
    unset($_SESSION['gadget_request_whatsapp_url']);
}

require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero">
  <div class="container py-5">
    <div class="badge-soft mb-3"><i class="bi bi-cpu me-1"></i> Gadget Request</div>
    <h1 class="display-6 fw-bold mb-2">Request any gadget you need</h1>
    <p class="lead text-muted mb-0">Tell us what you are looking for, upload a reference image (max 2MB), and we will follow up quickly.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if ($flash): ?>
      <div class="alert alert-<?= h((string)$flash['type']) ?> mb-4"><?= h((string)$flash['message']) ?></div>
    <?php endif; ?>

    <?php if ($errors): ?>
      <div class="alert alert-danger mb-4">
        <div class="fw-semibold mb-1">Please fix the following:</div>
        <ul class="mb-0">
          <?php foreach ($errors as $error): ?>
            <li><?= h((string)$error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="row g-4">
      <div class="col-lg-7">
        <div class="hero-card p-4 p-lg-5">
          <form method="post" enctype="multipart/form-data" class="row g-3" data-recaptcha-v3="1" data-recaptcha-action="shop_request_submit">
            <?= csrf_field() ?>
            <input type="hidden" name="recaptcha_token" value="">

            <div class="col-md-6">
              <label class="form-label">Full Name</label>
              <input type="text" class="form-control" name="full_name" value="<?= h($form['full_name']) ?>" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Phone Number</label>
              <input type="text" class="form-control" name="phone" value="<?= h($form['phone']) ?>" placeholder="e.g. 256772985659" required>
            </div>

            <div class="col-12">
              <label class="form-label">Email (optional)</label>
              <input type="email" class="form-control" name="email" value="<?= h($form['email']) ?>">
            </div>

            <div class="col-12">
              <label class="form-label">Gadget Name</label>
              <input type="text" class="form-control" name="gadget_name" value="<?= h($form['gadget_name']) ?>" placeholder="e.g. iPhone 14 Pro Max, Dell Latitude 5430" required>
            </div>

            <div class="col-12">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description" rows="5" placeholder="Specs, color, storage, budget, or any specific details" required><?= h($form['description']) ?></textarea>
            </div>

            <div class="col-12">
              <label class="form-label">Reference Image (optional, max 2MB)</label>
              <input type="file" class="form-control" name="reference_image" accept="image/jpeg,image/png,image/webp">
              <div class="form-text">Accepted formats: JPG, PNG, WEBP</div>
            </div>

            <?php if ($useRecaptcha): ?>
              <div class="col-12">
                <label class="form-label">Human Verification *</label>
                <div class="small text-muted">Protected by Google reCAPTCHA.</div>
              </div>
              <?php if ($mathFallbackEnabled): ?>
                <?= captcha_render('shop_gadget_request', 'Fallback Captcha (if reCAPTCHA fails)', false) ?>
              <?php endif; ?>
            <?php else: ?>
              <?= captcha_render('shop_gadget_request', 'Human Verification') ?>
            <?php endif; ?>

            <div class="col-12 d-flex gap-2 flex-wrap">
              <button type="submit" class="btn btn-orange"><i class="bi bi-send me-1"></i>Submit Request</button>
              <a href="<?= h($BASE) ?>/shop/" class="btn btn-outline-orange">Back to Shop</a>
            </div>
          </form>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="service-card p-4 h-100">
          <h2 class="h5 fw-bold mb-3">How it works</h2>
          <ol class="text-muted mb-4 ps-3">
            <li class="mb-2">Tell us the gadget and exact details.</li>
            <li class="mb-2">Upload an image reference (if available).</li>
            <li>We receive your request and notify our sales team.</li>
            <li>Our sales team will contact you to confirm the details.</li>
            <li>Once confirmed, we will start the process of delivering the gadget to you.</li>
          </ol>
          <!--<div class="small text-muted">Status starts as <span class="badge bg-warning text-dark">Pending</span> and is updated by our admin team.</div>-->
        </div>
      </div>
    </div>
  </div>
</section>

<?php if ($useRecaptcha): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var form = document.querySelector('form[data-recaptcha-v3]');
if (!form || !window.RECAPTCHA_SITE_KEY) {
    return;
  }

  var allowMathFallback = <?= $mathFallbackEnabled ? 'true' : 'false' ?>;

  form.addEventListener('submit', function (event) {
    if (form.dataset.recaptchaDone === '1') {
      form.dataset.recaptchaDone = '0';
      return;
    }

    event.preventDefault();
    var tokenInput = form.querySelector('input[name="recaptcha_token"]');
    var action = form.getAttribute('data-recaptcha-action') || 'submit';
    var fallbackInput = form.querySelector('input[name="captcha_answer"]');

    if (!window.grecaptcha || typeof window.grecaptcha.execute !== 'function') {
      if (allowMathFallback && fallbackInput && String(fallbackInput.value || '').trim() !== '') {
        if (tokenInput) {
          tokenInput.value = '';
        }
        form.dataset.recaptchaDone = '1';
        form.submit();
        return;
      }
      alert(allowMathFallback ? 'reCAPTCHA is still loading. Please wait a moment or solve fallback captcha, then submit again.' : 'reCAPTCHA is still loading. Please wait a moment and submit again.');
      return;
    }

    window.grecaptcha.ready(async function () {
      try {
        var token = await window.grecaptcha.execute(window.RECAPTCHA_SITE_KEY, { action: action });
        if (tokenInput) {
          tokenInput.value = token;
        }
        form.dataset.recaptchaDone = '1';
        form.submit();
      } catch (error) {
        if (tokenInput) {
          tokenInput.value = '';
        }
        if (allowMathFallback && fallbackInput && String(fallbackInput.value || '').trim() === '') {
          alert('reCAPTCHA is unavailable. Please solve the fallback captcha field, then submit again.');
          return;
        }
        if (!allowMathFallback) {
          alert('reCAPTCHA check failed. Please refresh and try again.');
          return;
        }
        form.dataset.recaptchaDone = '1';
        form.submit();
      }
    });
  });
});
</script>
<?php endif; ?>

<?php if ($whatsAppRedirectUrl !== ''): ?>
<script>
(function () {
  var url = <?= json_encode($whatsAppRedirectUrl) ?>;
  var opened = window.open(url, '_blank', 'noopener');
  if (!opened) {
    alert('Popup blocked. Please allow popups and click the link below.');
    var container = document.createElement('div');
    container.className = 'alert alert-info mt-3';
    container.innerHTML = 'Open WhatsApp: <a href="' + url + '" target="_blank" rel="noopener">Click here</a>';
    document.body.prepend(container);
  }
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
