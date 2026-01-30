<?php
declare(strict_types=1);

$page_title = "Page Not Found | Alma Tech Consults";
$page_description = "The page you are looking for could not be found.";

require_once __DIR__ . '/header.php';
?>

<!-- 404 Error Page -->
<div class="section section-soft">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8 text-center">
        <div class="error-404-content">
          <div class="error-code mb-4">
            <h1 class="display-1 fw-bold" style="color: #ff7a18;">404</h1>
          </div>
          
          <div class="error-message mb-4">
            <h2 class="mb-3">Page Not Found</h2>
            <p class="lead text-muted">
              The page you are looking for might have been removed, had its name changed, 
              or is temporarily unavailable.
            </p>
          </div>
          
          <div class="error-actions">
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
              <a href="index.php" class="btn" style="background-color: #ff7a18; color: white; border-color: #ff7a18;">
                <i class="bi bi-house me-2"></i> Go Home
              </a>
              <a href="contact.php" class="btn btn-outline-secondary" style="border-color: #ff7a18; color: #ff7a18;">
                <i class="bi bi-envelope me-2"></i> Contact Us
              </a>
            </div>
          </div>
          
          <div class="error-help mt-5">
            <div class="row text-start">
              <div class="col-md-4 mb-3">
                <h5><i class="bi bi-search me-2"></i> Search</h5>
                <p class="text-muted small">Try searching for what you're looking for.</p>
              </div>
              <div class="col-md-4 mb-3">
                <h5><i class="bi bi-grid me-2"></i> Navigation</h5>
                <p class="text-muted small">Use the main navigation to find what you need.</p>
              </div>
              <div class="col-md-4 mb-3">
                <h5><i class="bi bi-question-circle me-2"></i> Help</h5>
                <p class="text-muted small">Contact us if you need assistance finding something.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.error-404-content {
  padding: 60px 0;
}

.error-code h1 {
  font-size: 8rem;
  line-height: 1;
}

.error-message h2 {
  font-size: 2.5rem;
  font-weight: 600;
}

.error-actions .btn {
  min-width: 140px;
}

.error-actions .btn[style*="#ff7a18"] {
  background-color: #ff7a18 !important;
  color: white !important;
  border-color: #ff7a18 !important;
  transition: all 0.3s ease !important;
}

.error-actions .btn[style*="#ff7a18"]:hover {
  background-color: #e56914 !important;
  color: white !important;
  border-color: #e56914 !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(255, 122, 24, 0.3);
}

.error-actions .btn[style*="border-color: #ff7a18"] {
  border-color: #ff7a18 !important;
  color: #ff7a18 !important;
  background-color: transparent !important;
  transition: all 0.3s ease !important;
}

.error-actions .btn[style*="border-color: #ff7a18"]:hover {
  background-color: #ff7a18 !important;
  color: white !important;
  border-color: #ff7a18 !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(255, 122, 24, 0.3);
}

.error-help h5 {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.error-help .text-muted {
  font-size: 0.9rem;
  line-height: 1.4;
}

@media (max-width: 768px) {
  .error-404-content {
    padding: 40px 0;
  }
  
  .error-code h1 {
    font-size: 6rem;
  }
  
  .error-message h2 {
    font-size: 2rem;
  }
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
