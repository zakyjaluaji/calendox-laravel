<?php
// Gunakan koneksi yang sesuai. Jika aplikasi ini berjalan terpisah,
// pastikan file koneksi merujuk ke DB yang memiliki tabel `user`.
include "conn.php";
if (isset($_POST['search'])) {
    header('Content-Type: application/json');
    $search = isset($_POST['search']) ? trim($_POST['search']) : '';

    // Jika kosong, kembalikan array kosong untuk menghindari query berat
    if ($search === '') {
        echo json_encode([]);
        exit;
    }

    $term = "%" . $search . "%";
    $stmt = $koneksi->prepare("SELECT user_id, fullname, username, email FROM user WHERE fullname LIKE ? OR username LIKE ? OR email LIKE ? LIMIT 8");
    if (!$stmt) {
        error_log('Error prepare MySQLi cari-pic: ' . $koneksi->error);
        echo json_encode([]);
        exit;
    }
    $stmt->bind_param("sss", $term, $term, $term);
    if (!$stmt->execute()) {
        error_log('Error execute MySQLi cari-pic: ' . $stmt->error);
        echo json_encode([]);
        $stmt->close();
        exit;
    }
    $result = $stmt->get_result();
    $response = [];
    while ($row = $result->fetch_assoc()) {
        $response[] = [
            "value" => $row['email'],
            "label" => $row['fullname'] . " (" . $row['email'] . ")",
            "name"  => $row['fullname']
        ];
    }
    $stmt->close();
    echo json_encode($response);
}
