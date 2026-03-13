<?php
declare(strict_types=1);

// h() function is now declared in header.php to avoid conflicts
if (!function_exists('h')) {
  function h($s): string { 
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); 
  }
}

if (!function_exists('redirect')) {
  function redirect(string $url): void {
    header("Location: " . $url);
    exit;
  }
}

if (!function_exists('flash_set')) {
  function flash_set(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
  }
}

if (!function_exists('flash_get')) {
  function flash_get(): ?array {
    if (empty($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
  }
}

// CSRF Functions
if (!function_exists('csrf_init')) {
  function csrf_init(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
  }
}

if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    csrf_init();
    return $_SESSION['csrf_token'] ?? '';
  }
}

if (!function_exists('csrf_validate')) {
  function csrf_validate(string $token): bool {
    if (function_exists('csrf_is_valid')) {
      return csrf_is_valid($token);
    }
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
  }
}

if (!function_exists('csrf_verify')) {
  function csrf_verify(): void {
    $token = $_POST['csrf'] ?? $_POST['csrf_token'] ?? '';
    if (!csrf_validate($token)) {
      http_response_code(403);
      exit('CSRF token validation failed. Please refresh and try again.');
    }
  }
}

if (!function_exists('recaptcha_verify_token')) {
  function recaptcha_verify_token(string $secretKey, string $token, ?string $remoteIp = null, string $expectedAction = '', float $minScore = 0.3): bool {
    $secretKey = trim($secretKey);
    $token = trim($token);
    if ($token === '') {
      return false;
    }

    if ($secretKey === '') {
      return false;
    }

    $payload = http_build_query([
      'secret' => $secretKey,
      'response' => $token,
      'remoteip' => (string)$remoteIp,
    ]);

    $responseBody = '';

    if (function_exists('curl_init')) {
      $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
      if ($ch !== false) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        $responseBody = (string)curl_exec($ch);
        curl_close($ch);
      }
    }

    if ($responseBody === '') {
      $context = stream_context_create([
        'http' => [
          'method' => 'POST',
          'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
          'content' => $payload,
          'timeout' => 12,
        ],
      ]);
      $fallback = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $context);
      $responseBody = $fallback !== false ? (string)$fallback : '';
    }

    if ($responseBody === '') {
      return false;
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded) || empty($decoded['success'])) {
      return false;
    }

    if ($expectedAction !== '') {
      $actualAction = trim((string)($decoded['action'] ?? ''));
      if ($actualAction !== '' && !hash_equals($expectedAction, $actualAction)) {
        return false;
      }
    }

    $score = isset($decoded['score']) ? (float)$decoded['score'] : 1.0;
    return $score >= $minScore;
  }
}



if (!function_exists('captcha_create')) {
  function captcha_create(string $formKey): array {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    $a = random_int(2, 9);
    $b = random_int(1, 8);
    $operator = random_int(0, 1) === 1 ? '+' : '-';
    if ($operator === '-' && $b > $a) {
      $tmp = $a;
      $a = $b;
      $b = $tmp;
    }

    $answer = $operator === '+' ? ($a + $b) : ($a - $b);
    $token = bin2hex(random_bytes(8));

    if (!isset($_SESSION['captcha']) || !is_array($_SESSION['captcha'])) {
      $_SESSION['captcha'] = [];
    }

    $_SESSION['captcha'][$formKey] = [
      'token' => $token,
      'answer' => (string)$answer,
      'expires_at' => time() + 900,
    ];

    return [
      'question' => $a . ' ' . $operator . ' ' . $b . ' = ?',
      'token' => $token,
    ];
  }
}

if (!function_exists('captcha_render')) {
  function captcha_render(string $formKey, string $label = 'Captcha', bool $required = true): string {
    $captcha = captcha_create($formKey);
    $requiredAttr = $required ? ' required' : '';
    return '<div class="col-md-6">'
      . '<label class="form-label">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ' *</label>'
      . '<input type="hidden" name="captcha_token" value="' . htmlspecialchars((string)$captcha['token'], ENT_QUOTES, 'UTF-8') . '">'
      . '<input class="form-control" name="captcha_answer" placeholder="Solve: ' . htmlspecialchars((string)$captcha['question'], ENT_QUOTES, 'UTF-8') . '"' . $requiredAttr . '>'
      . '</div>';
  }
}

if (!function_exists('captcha_validate_submission')) {
  function captcha_validate_submission(string $formKey, ?string $token, ?string $answer): bool {
    if (session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    $row = $_SESSION['captcha'][$formKey] ?? null;
    unset($_SESSION['captcha'][$formKey]);

    if (!is_array($row)) {
      return false;
    }

    $validToken = hash_equals((string)($row['token'] ?? ''), (string)$token);
    $validTime = ((int)($row['expires_at'] ?? 0) >= time());
    $validAnswer = hash_equals(trim((string)($row['answer'] ?? '')), trim((string)$answer));

    return $validToken && $validTime && $validAnswer;
  }
}
