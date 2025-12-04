<?php
// Konfigurasi database
$db_host = "103.104.177.233";
$db_user = "tik_151_151";
$db_pass = "Padh@ngMbeLOK__";
$db_name = "isd";

// Koneksi menggunakan MySQLi
$koneksi = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Cek error koneksi
if ($koneksi->connect_errno) {
    error_log('Gagal melakukan koneksi ke Database: ' . $koneksi->connect_error);
    echo 'Gagal melakukan koneksi ke Database. Silakan hubungi administrator.';
    exit;
}

// Set charset
$koneksi->set_charset('utf8mb4');
