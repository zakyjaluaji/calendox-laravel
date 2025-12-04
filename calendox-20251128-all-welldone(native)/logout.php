<?php
session_start();
// Bersihkan semua sesi yang digunakan di login baru
$_SESSION = [];
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000,
    $params["path"], $params["domain"], $params["secure"], $params["httponly"]
  );
}
session_destroy();
// Kembali ke halaman login dalam folder yang sama
header('Location: login.php');
exit;
