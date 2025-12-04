<?php
// Koneksi ke database _002008_pt untuk modul akun pengguna
$db_host = "103.104.177.233";
$db_user = "tik_151_151";
// Gunakan password yang sama dengan kredensial aktif pada server
$db_pass = "Padh@ngMbeLOK__";
$db_name = "_002008_pt";

// Gunakan MySQLi
$host = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$host) {
  echo "Koneksi gagal!" . mysqli_connect_error();
  die();
}

// Alias agar konsisten dengan file lain yang menggunakan $koneksi
$koneksi = $host;
// Pastikan charset konsisten
mysqli_set_charset($koneksi, 'utf8mb4');
?>