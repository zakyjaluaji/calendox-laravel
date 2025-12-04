<?php
// Generator token JWT untuk uji SSO CalendoX
// Jalankan: http://127.0.0.1:8080/generate-test-token.php?email=you@example.com
// Salin token yang dihasilkan ke: http://127.0.0.1:8080/proseslogin.php?token=<TOKEN>

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/vendor/autoload.php';

function respond($text, $status = 200) {
  http_response_code($status);
  header('Content-Type: text/plain; charset=UTF-8');
  echo $text;
  exit;
}

$algo = getenv('CALENDOX_JWT_ALGO');
if ($algo === false || $algo === '') {
  $algo = 'HS256';
}

$email = isset($_GET['email']) ? trim($_GET['email']) : '';
if ($email === '') {
  $email = 'guest@example.com';
}

// Siapkan key materi
$signingKey = null;
$signingAlgo = null;

if ($algo === 'HS256') {
  $secret = getenv('CALENDOX_JWT_SECRET');
  if (!$secret) {
    // Fallback ke google-config.php
    $secret = null;
    $cfg = __DIR__ . '/google-config.php';
    if (file_exists($cfg)) {
      include $cfg;
      if (isset($JWT_SECRET) && is_string($JWT_SECRET) && $JWT_SECRET !== '') {
        $secret = $JWT_SECRET;
      }
    }
  }
  if (!$secret) {
    respond("HS256 secret tidak tersedia. Set env CALENDOX_JWT_SECRET atau isi $JWT_SECRET di google-config.php.", 500);
  }
  $signingKey = $secret;
  $signingAlgo = 'HS256';
} elseif ($algo === 'RS256') {
  // Butuh private key untuk membuat token RS256; gunakan env CALENDOX_JWT_PRIVATE
  $privPath = getenv('CALENDOX_JWT_PRIVATE');
  if (!$privPath || !file_exists($privPath)) {
    respond('RS256 memerlukan CALENDOX_JWT_PRIVATE (path ke private key PEM) untuk menandatangani token.', 500);
  }
  $signingKey = file_get_contents($privPath);
  $signingAlgo = 'RS256';
} else {
  respond('Algoritma tidak didukung untuk generator: ' . $algo, 400);
}

$now = time();
$claims = [
  'email' => $email,
  'iss' => 'calendox',
  'aud' => 'calendox',
  'iat' => $now,
  'nbf' => $now - 5,
  'exp' => $now + 600 // berlaku 10 menit
];

try {
  $token = \Firebase\JWT\JWT::encode($claims, $signingKey, $signingAlgo);
  $url = 'http://127.0.0.1:8080/proseslogin.php?token=' . $token;
  respond("TOKEN=\n$token\n\nCoba buka:\n$url\n");
} catch (Exception $e) {
  respond('Gagal membuat token: ' . $e->getMessage(), 500);
}