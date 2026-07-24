<?php
declare(strict_types=1);

/**
 * admin/analytics/index.php
 * GA4 Analytics dashboard (Service Account, no SDK)
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_admin_login();

require_once __DIR__ . '/../../includes/db.php';

$settings_lib = __DIR__ . '/../includes/settings_lib.php';
if (is_file($settings_lib)) require_once $settings_lib;

$db = $GLOBALS['db'] ?? ($mysqli ?? null);

// -------------------------
// Fallback helpers (if missing)
// -------------------------
if (!function_exists('h')) {
  function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('setting_get')) {
  function setting_get($db, string $key, $default = '') { return $default; }
}

// CSRF (only if your helpers.php doesn't already provide)
if (!function_exists('csrf_init')) {
  function csrf_init() {
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
  }
}
if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    csrf_init();
    return (string)($_SESSION['csrf'] ?? '');
  }
}
if (!function_exists('csrf_verify')) {
  function csrf_verify() {
    csrf_init();
    $token = (string)($_POST['csrf'] ?? $_GET['csrf'] ?? '');
    if (!$token || !hash_equals((string)($_SESSION['csrf'] ?? ''), $token)) {
      http_response_code(403);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
      exit;
    }
  }
}

csrf_init();

// -------------------------
// Settings
// -------------------------
$ga4_property_id  = trim((string) setting_get($db, 'ga4_property_id', ''));
$sa_json_path     = trim((string) setting_get($db, 'ga4_service_json_path', ''));
$sa_json_raw      = (string) setting_get($db, 'ga4_service_json', '');
$looker_embed_url = trim((string) setting_get($db, 'looker_embed_url', ''));

// -------------------------
// Utils
// -------------------------
function base64url_encode(string $data): string {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function http_json_post(string $url, array $headers, string $body, int $timeout = 20): array {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_TIMEOUT        => $timeout,
  ]);
  $resp = curl_exec($ch);
  $err  = curl_error($ch);
  $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return [$code, $resp, $err];
}

function load_service_account(string $path, string $raw): array {
  $json = '';
  if ($path !== '' && is_file($path)) {
    $json = (string) file_get_contents($path);
  } elseif (trim($raw) !== '') {
    $json = $raw;
  }
  $data = $json ? json_decode($json, true) : null;
  return is_array($data) ? $data : [];
}

function get_google_access_token(array $sa): array {
  $client_email = (string)($sa['client_email'] ?? '');
  $private_key  = (string)($sa['private_key'] ?? '');
  $token_uri    = (string)($sa['token_uri'] ?? 'https://oauth2.googleapis.com/token');

  if ($client_email === '' || $private_key === '') {
    return [false, '', 'Service account JSON missing client_email/private_key.'];
  }

  $now = time();
  $header = ['alg' => 'RS256', 'typ' => 'JWT'];
  $claim  = [
    'iss'   => $client_email,
    'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
    'aud'   => $token_uri,
    'iat'   => $now,
    'exp'   => $now + 3600,
  ];

  $segments = [
    base64url_encode(json_encode($header)),
    base64url_encode(json_encode($claim)),
  ];
  $signing_input = implode('.', $segments);

  $signature = '';
  if (!openssl_sign($signing_input, $signature, $private_key, 'sha256')) {
    return [false, '', 'OpenSSL could not sign JWT (check private_key).'];
  }

  $jwt = $signing_input . '.' . base64url_encode($signature);

  $postBody = http_build_query([
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion'  => $jwt,
  ]);

  list($code, $resp, $err) = http_json_post(
    $token_uri,
    ['Content-Type: application/x-www-form-urlencoded'],
    $postBody
  );

  if ($err) return [false, '', 'cURL error: ' . $err];

  $data = json_decode((string)$resp, true);
  if ($code !== 200 || !is_array($data) || empty($data['access_token'])) {
    $msg = (is_array($data) && !empty($data['error_description']))
      ? (string)$data['error_description']
      : 'Failed to get access token. HTTP ' . $code;
    return [false, '', $msg];
  }

  return [true, (string)$data['access_token'], ''];
}

function ga4_run_report(string $accessToken, string $propertyId, string $startDate, string $endDate): array {
  $url = 'https://analyticsdata.googleapis.com/v1beta/properties/' . rawurlencode($propertyId) . ':runReport';

  $payload = [
    'dateRanges' => [
      ['startDate' => $startDate, 'endDate' => $endDate]
    ],
    'dimensions' => [
      ['name' => 'date']
    ],
    'metrics' => [
      ['name' => 'sessions'],
      ['name' => 'totalUsers'],
      ['name' => 'screenPageViews'],
    ],
    'orderBys' => [
      ['dimension' => ['dimensionName' => 'date']]
    ],
  ];

  list($code, $resp, $err) = http_json_post(
    $url,
    [
      'Content-Type: application/json',
      'Authorization: Bearer ' . $accessToken,
    ],
    json_encode($payload)
  );

  if ($err) return ['error' => 'cURL error: ' . $err];

  $data = json_decode((string)$resp, true);
  if ($code !== 200 || !is_array($data)) {
    $msg = 'GA4 API error. HTTP ' . $code;
    if (is_array($data) && isset($data['error']['message'])) $msg .= ' - ' . $data['error']['message'];
    return ['error' => $msg];
  }

  return $data;
}

function normalize_dates(string $range, string $from, string $to): array {
  $today = new DateTime('now');
  $end = $today->format('Y-m-d');

  if ($range === '7d') {
    $start = (new DateTime('now'))->modify('-6 days')->format('Y-m-d');
    return [$start, $end];
  }
  if ($range === '30d') {
    $start = (new DateTime('now'))->modify('-29 days')->format('Y-m-d');
    return [$start, $end];
  }

  $startOk = (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $from);
  $endOk   = (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $to);

  $start = $startOk ? $from : (new DateTime('now'))->modify('-6 days')->format('Y-m-d');
  $end2  = $endOk ? $to : $end;

  if ($start > $end2) { $tmp = $start; $start = $end2; $end2 = $tmp; }

  return [$start, $end2];
}

// -------------------------
// AJAX (same file)
// -------------------------
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
  csrf_verify();
  header('Content-Type: application/json; charset=utf-8');

  if ($ga4_property_id === '') {
    echo json_encode(['ok' => false, 'error' => 'Missing setting: ga4_property_id']);
    exit;
  }

  $range = (string)($_GET['range'] ?? '7d');
  $from  = (string)($_GET['from'] ?? '');
  $to    = (string)($_GET['to'] ?? '');

  list($startDate, $endDate) = normalize_dates($range, $from, $to);

  $sa = load_service_account($sa_json_path, $sa_json_raw);
  if (!$sa) {
    echo json_encode(['ok' => false, 'error' => 'Missing/invalid service account JSON. Set ga4_service_json_path or ga4_service_json.']);
    exit;
  }

  list($tokOk, $token, $tokErr) = get_google_access_token($sa);
  if (!$tokOk) {
    echo json_encode(['ok' => false, 'error' => $tokErr]);
    exit;
  }

  $report = ga4_run_report($token, $ga4_property_id, $startDate, $endDate);
  if (isset($report['error'])) {
    echo json_encode(['ok' => false, 'error' => $report['error']]);
    exit;
  }

  $rows = $report['rows'] ?? [];
  $labels = [];
  $sessions = [];
  $users = [];
  $views = [];
  $sumSessions = 0; $sumUsers = 0; $sumViews = 0;

  foreach ($rows as $r) {
    $d = (string)($r['dimensionValues'][0]['value'] ?? '');
    $label = (preg_match('/^\d{8}$/', $d))
      ? (substr($d,0,4).'-'.substr($d,4,2).'-'.substr($d,6,2))
      : $d;

    $m0 = (int)($r['metricValues'][0]['value'] ?? 0);
    $m1 = (int)($r['metricValues'][1]['value'] ?? 0);
    $m2 = (int)($r['metricValues'][2]['value'] ?? 0);

    $labels[] = $label;
    $sessions[] = $m0;
    $users[] = $m1;
    $views[] = $m2;

    $sumSessions += $m0;
    $sumUsers += $m1;
    $sumViews += $m2;
  }

  echo json_encode([
    'ok' => true,
    'startDate' => $startDate,
    'endDate' => $endDate,
    'kpi' => [
      'sessions' => $sumSessions,
      'users' => $sumUsers,
      'views' => $sumViews,
    ],
    'series' => [
      'labels' => $labels,
      'sessions' => $sessions,
      'users' => $users,
      'views' => $views,
    ],
  ]);
  exit;
}

// -------------------------
// UI layout (admin header/sidebar/footer)
// -------------------------
$page_title = "Analytics | Admin";
$page_heading = "Analytics Dashboard";
$page_subtitle = "Google Analytics 4 data and insights";
$active_admin = "analytics";

require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../includes/admin_sidebar.php';

?>

<style>
/* ===== Admin Card Style (like Pages screen) ===== */
.admin-card {
  background: #fff;
  border: 1px solid rgba(15, 23, 42, 0.06);
  border-radius: 18px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.admin-card-header {
  padding: 18px 20px 10px;
}

.admin-card-body {
  padding: 18px 20px;
}

.admin-card-title {
  margin: 0;
  font-weight: 700;
  font-size: 1.1rem;
}

.admin-card-subtitle {
  margin: 4px 0 0;
  color: rgba(15, 23, 42, 0.6);
  font-size: .9rem;
}

/* KPI cards like management UI */
.kpi-card {
  background: #fff;
  border: 1px solid rgba(15, 23, 42, 0.06);
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
  padding: 16px 16px;
  height: 100%;
}

.kpi-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
}

.kpi-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  background: rgba(13, 110, 253, 0.08);
  color: #0d6efd;
  font-size: 18px;
}

.kpi-label {
  font-size: .92rem;
  color: rgba(15, 23, 42, 0.65);
  margin: 0;
}

.kpi-value {
  font-size: 1.8rem;
  font-weight: 800;
  letter-spacing: -0.02em;
  margin: 0;
}

.kpi-meta {
  margin-top: 8px;
  font-size: .85rem;
  color: rgba(15, 23, 42, 0.55);
}
</style>

<div class="admin-main">
  <?php require_once __DIR__ . '/../includes/admin_topbar.php'; ?>

  <?php if ($ga4_property_id === '' || ($sa_json_path === '' && trim($sa_json_raw) === '')): ?>
    <div class="admin-card p-4">
      <div class="fw-semibold mb-1">Analytics not configured</div>
      <div class="small">
        Add settings:
        <ul class="mb-0">
          <li><code>ga4_property_id</code></li>
          <li><code>ga4_service_json_path</code> (recommended)</li>
          <li>or <code>ga4_service_json</code></li>
        </ul>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($looker_embed_url): ?>
    <div class="admin-card p-4 mb-3">
      <div class="fw-semibold mb-2">Looker Studio Report</div>
      <div class="ratio ratio-16x9">
        <iframe src="<?= h($looker_embed_url) ?>" style="border:0" allowfullscreen></iframe>
      </div>
    </div>
  <?php endif; ?>

  <!-- GA4 Overview (same style as Pages screen card) -->
  <div class="admin-card p-4 mb-3">
    <div class="d-flex flex-wrap align-items-end justify-content-between gap-3">
      <div>
        <h3 class="admin-card-title mb-1">GA4 Overview</h3>
        <div class="admin-card-subtitle">Choose range then refresh</div>
      </div>

      <input type="hidden" id="csrf" value="<?= h(csrf_token()) ?>">

      <div class="d-flex flex-wrap gap-2 align-items-end">
        <select id="range" class="form-select" style="min-width: 180px">
          <option value="7d" selected>Last 7 days</option>
          <option value="30d">Last 30 days</option>
          <option value="custom">Custom</option>
        </select>

        <div class="d-flex gap-2" id="customWrap" style="display:none">
          <input type="date" id="from" class="form-control">
          <input type="date" id="to" class="form-control">
        </div>
      </div>
    </div>
  </div>

  <!-- KPI Cards (styled like Pages screen) -->
  <div class="row g-3 g-lg-4 mb-3">
    <div class="col-md-4">
      <div class="kpi-card">
        <div class="kpi-top">
          <div>
            <p class="kpi-label">Sessions</p>
            <p class="kpi-value" id="kpiSessions">—</p>
          </div>
          <div class="kpi-icon"><i class="bi bi-activity"></i></div>
        </div>
        <div class="kpi-meta">Total sessions in selected range</div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="kpi-card">
        <div class="kpi-top">
          <div>
            <p class="kpi-label">Users</p>
            <p class="kpi-value" id="kpiUsers">—</p>
          </div>
          <div class="kpi-icon" style="background: rgba(25,135,84,.10); color:#198754;">
            <i class="bi bi-people"></i>
          </div>
        </div>
        <div class="kpi-meta">Total users in selected range</div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="kpi-card">
        <div class="kpi-top">
          <div>
            <p class="kpi-label">Page Views</p>
            <p class="kpi-value" id="kpiViews">—</p>
          </div>
          <div class="kpi-icon" style="background: rgba(255,193,7,.14); color:#b78103;">
            <i class="bi bi-eye"></i>
          </div>
        </div>
        <div class="kpi-meta">Total views in selected range</div>
      </div>
    </div>
  </div>

  <!-- Trend Chart (same container card style as Pages screen) -->
  <div class="admin-card p-4">
    <h3 class="admin-card-title mb-1">Trend</h3>
    <div class="admin-card-subtitle mb-3">Sessions, users and views over time</div>

    <canvas id="trendChart" height="110"></canvas>
    <div class="small text-danger mt-2" id="errBox" style="display:none"></div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  const rangeEl = document.getElementById('range');
  const customWrap = document.getElementById('customWrap');
  const fromEl = document.getElementById('from');
  const toEl = document.getElementById('to');
  const csrf = document.getElementById('csrf').value;

  const kpiSessions = document.getElementById('kpiSessions');
  const kpiUsers = document.getElementById('kpiUsers');
  const kpiViews = document.getElementById('kpiViews');
  const dateLabel = document.getElementById('dateLabel');
  const errBox = document.getElementById('errBox');

  function fmt(n) {
    try { return new Intl.NumberFormat().format(Number(n || 0)); }
    catch(e) { return String(n || 0); }
  }

  rangeEl.addEventListener('change', () => {
    customWrap.style.display = (rangeEl.value === 'custom') ? 'flex' : 'none';
  });

  let chart;
  function renderChart(labels, sessions, users, views) {
    const ctx = document.getElementById('trendChart').getContext('2d');
    if (chart) chart.destroy();

    chart = new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [
          { label: 'Sessions', data: sessions, tension: 0.3 },
          { label: 'Users', data: users, tension: 0.3 },
          { label: 'Views', data: views, tension: 0.3 },
        ]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'top' },
          tooltip: { mode: 'index', intersect: false }
        },
        interaction: { mode: 'index', intersect: false },
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  async function loadReport() {
    errBox.style.display = 'none';
    errBox.textContent = '';

    const range = rangeEl.value;
    const params = new URLSearchParams();
    params.set('ajax', '1');
    params.set('csrf', csrf);
    params.set('range', range);

    if (range === 'custom') {
      if (!fromEl.value || !toEl.value) {
        errBox.style.display = 'block';
        errBox.textContent = 'Choose both start and end date.';
        return;
      }
      params.set('from', fromEl.value);
      params.set('to', toEl.value);
    }

    kpiSessions.textContent = '…';
    kpiUsers.textContent = '…';
    kpiViews.textContent = '…';
    dateLabel.textContent = 'Loading…';

    const url = 'index.php?' + params.toString();
    const res = await fetch(url, { method: 'GET', credentials: 'same-origin' });
    const data = await res.json();

    if (!data.ok) {
      errBox.style.display = 'block';
      errBox.textContent = data.error || 'Failed to load analytics.';
      kpiSessions.textContent = '—';
      kpiUsers.textContent = '—';
      kpiViews.textContent = '—';
      dateLabel.textContent = '—';
      return;
    }

    kpiSessions.textContent = fmt(data.kpi.sessions);
    kpiUsers.textContent = fmt(data.kpi.users);
    kpiViews.textContent = fmt(data.kpi.views);
    dateLabel.textContent = `${data.startDate} → ${data.endDate}`;

    renderChart(
      data.series.labels || [],
      data.series.sessions || [],
      data.series.users || [],
      data.series.views || []
    );
  }

  document.getElementById('btnRefresh').addEventListener('click', loadReport);

  // initial load
  loadReport();
</script>
