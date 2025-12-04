<?php
// Login Calendox sesuai spesifikasi pengguna
// - Akun: DB _002008_pt, tabel ytrtyjkfaea_akun
// - Koneksi: conn_user.php
// - Role: baca tabel akun_akses (apps = 'Calendox', akses = 1 => Administrator; lainnya Tamu)

date_default_timezone_set('Asia/Jakarta');
session_start();

// SSO berbasis JWT via GET ?token=<JWT>
// Menggunakan library firebase/php-jwt (autoload dari vendor/)
if (isset($_GET['token'])) {
  $jwtToken = trim($_GET['token']);
  if ($jwtToken === '') {
    header('Location: login.php?error=1');
    exit;
  }

  require_once __DIR__ . '/vendor/autoload.php';
  // Gunakan FQCN untuk kelas JWT dan Key dari firebase/php-jwt

  // Konfigurasi JWT: default HS256 via env CALENDOX_JWT_SECRET
  $algo = getenv('CALENDOX_JWT_ALGO');
  if ($algo === false || $algo === '') {
    $algo = 'HS256';
  }

  $keyMaterial = null;
  if ($algo === 'HS256') {
    $secret = getenv('CALENDOX_JWT_SECRET');
    if (!$secret) {
      // Fallback: coba ambil dari google-config.php jika disediakan
      $secret = null;
      $cfgPath = __DIR__ . '/google-config.php';
      if (file_exists($cfgPath)) {
        include $cfgPath;
        if (isset($JWT_SECRET) && is_string($JWT_SECRET) && $JWT_SECRET !== '') {
          $secret = $JWT_SECRET;
        }
      }
    }
    if (!$secret) {
      error_log('JWT SSO error: HS256 tanpa CALENDOX_JWT_SECRET');
      header('Location: login.php?error=1');
      exit;
    }
    $keyMaterial = new \Firebase\JWT\Key($secret, 'HS256');
  } elseif ($algo === 'RS256') {
    $pubPath = getenv('CALENDOX_JWT_PUBLIC');
    if (!$pubPath || !file_exists($pubPath)) {
      // Fallback: coba file default di root proyek
      $pubPath = __DIR__ . '/jwt-public.pem';
    }
    if (!file_exists($pubPath)) {
      error_log('JWT SSO error: RS256 tanpa public key');
      header('Location: login.php?error=1');
      exit;
    }
    $publicKey = file_get_contents($pubPath);
    $keyMaterial = new \Firebase\JWT\Key($publicKey, 'RS256');
  } else {
    error_log('JWT SSO error: algoritma tidak didukung ' . $algo);
    header('Location: login.php?error=1');
    exit;
  }

  try {
    $decoded = \Firebase\JWT\JWT::decode($jwtToken, $keyMaterial);
    $claims = (array) $decoded;

    // Ambil identitas dari claims: email / akun / sub
    $akunIdentifier = '';
    if (isset($claims['email']) && is_string($claims['email'])) {
      $akunIdentifier = trim($claims['email']);
    } elseif (isset($claims['akun']) && is_string($claims['akun'])) {
      $akunIdentifier = trim($claims['akun']);
    } elseif (isset($claims['sub']) && is_string($claims['sub'])) {
      $akunIdentifier = trim($claims['sub']);
    }

    if ($akunIdentifier === '') {
      error_log('JWT SSO error: klaim identitas tidak ditemukan');
      header('Location: login.php?error=1');
      exit;
    }

    include 'conn_user.php';

    $role = 'Tamu';
    $level = 'Guest';
    $apps = 'Calendox';

    // Tentukan role berdasarkan tabel akun_akses
    $stmtSSO = $koneksi->prepare("SELECT akses FROM akun_akses WHERE email = ? AND apps = ? LIMIT 1");
    if ($stmtSSO) {
      $stmtSSO->bind_param('ss', $akunIdentifier, $apps);
      $stmtSSO->execute();
      $stmtSSO->bind_result($aksesSSO);
      if ($stmtSSO->fetch()) {
        if (intval($aksesSSO) === 1) {
          $role = 'Pengelola';
          $level = 'Admin';
        } else {
          $role = 'Tamu';
          $level = 'Guest';
        }
      }
      $stmtSSO->close();
    }

    // Regenerasi sesi untuk mencegah fixation
    if (function_exists('session_regenerate_id')) {
      session_regenerate_id(true);
    }

    // Simpan sesi menggunakan identifier dari JWT
    $_SESSION['username'] = $akunIdentifier;
    $_SESSION['role'] = $role;
    $_SESSION['level'] = $level;
    $_SESSION['login_time'] = date('Y-m-d H:i:s');

    header('Location: index.php');
    exit;
  } catch (Exception $e) {
    error_log('JWT SSO decode error: ' . $e->getMessage());
    header('Location: login.php?error=1');
    exit;
  }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: login.php?error=1');
  exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
  header('Location: login.php?error=1');
  exit;
}

include 'conn_user.php';

// Autentikasi menggunakan hash: md5(username . password)
$passHash = md5($username . $password);
$stmt = $koneksi->prepare("SELECT nama_akun, sandi FROM ytrtyjkfaea_akun WHERE nama_akun = ? AND sandi = ? LIMIT 1");
if (!$stmt) {
  error_log('Login prepare failed: ' . $koneksi->error);
  header('Location: login.php?error=1');
  exit;
}
$stmt->bind_param('ss', $username, $passHash);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows !== 1) {
  $stmt->close();
  header('Location: login.php?error=1');
  exit;
}

$stmt->bind_result($namaAkun, $sandi);
$stmt->fetch();
$stmt->close();

// Tentukan role berdasarkan tabel akun_akses
$role = 'Tamu';
$level = 'Guest';
$apps = 'Calendox';

$stmt2 = $koneksi->prepare("SELECT akses FROM akun_akses WHERE email = ? AND apps = ? LIMIT 1");
if ($stmt2) {
  $stmt2->bind_param('ss', $namaAkun, $apps);
  $stmt2->execute();
  $stmt2->bind_result($akses);
  if ($stmt2->fetch()) {
    if (intval($akses) === 1) {
      $role = 'Pengelola';
      $level = 'Admin';
    } else {
      $role = 'Tamu';
      $level = 'Guest';
    }
  }
  // echo intval($akses);
  // exit();
  $stmt2->close();
}

// Simpan sesi
if (function_exists('session_regenerate_id')) {
  session_regenerate_id(true);
}
$_SESSION['username'] = $namaAkun;
$_SESSION['role'] = $role;
$_SESSION['level'] = $level;
$_SESSION['login_time'] = date('Y-m-d H:i:s');

// Redirect ke beranda Calendox
header('Location: index.php');
exit;
