<?php
// Login Calendox sesuai spesifikasi pengguna
// - Akun: DB _002008_pt, tabel ytrtyjkfaea_akun
// - Koneksi: conn_user.php
// - Role: baca tabel akun_akses (apps = 'Calendox', akses = 1 => Administrator; lainnya Tamu)

date_default_timezone_set('Asia/Jakarta');
session_start();

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
$_SESSION['username'] = $namaAkun;
$_SESSION['role'] = $role;
$_SESSION['level'] = $level;
$_SESSION['login_time'] = date('Y-m-d H:i:s');

// Redirect ke beranda Calendox
header('Location: index.php');
exit;
