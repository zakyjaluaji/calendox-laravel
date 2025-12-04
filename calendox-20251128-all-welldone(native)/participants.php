<?php

include "connection.php";

header('Content-Type: application/json');

$success = false;
$message = '';

// Pastikan tabel participant ada dan memiliki kolom email
try {
    $ddl = "CREATE TABLE IF NOT EXISTS participant (
        id INT AUTO_INCREMENT PRIMARY KEY,
        appointment_id INT NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX (appointment_id),
        CONSTRAINT fk_participant_appointment FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    $conn->query($ddl);
    // Jika tabel sudah ada tanpa kolom email, tambahkan
    try { $conn->query("ALTER TABLE participant ADD COLUMN email VARCHAR(255) NULL AFTER name"); } catch (Throwable $ignore) {}
    // Pastikan kombinasi (appointment_id, email) unik untuk mencegah peserta ganda di event yang sama
    try { $conn->query("ALTER TABLE participant ADD UNIQUE KEY idx_unique_event_email (appointment_id, email)"); } catch (Throwable $ignore) {}
} catch (Throwable $e) {
    // Abaikan kesalahan pembuatan tabel, akan terdeteksi saat insert
}

try {
    $action = $_POST['action'] ?? '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_participant') {
        $eventId = intval($_POST['event_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($eventId <= 0 || $name === '' || $email === '') {
            throw new Exception('Input tidak valid. Mohon pilih event, isi nama dan email peserta.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email tidak valid.');
        }

        // Optional: cek apakah appointment ada
        $check = $conn->prepare('SELECT id FROM appointments WHERE id = ?');
        $check->bind_param('i', $eventId);
        $check->execute();
        $check->store_result();
        if ($check->num_rows === 0) {
            $check->close();
            throw new Exception('Event tidak ditemukan.');
        }
        $check->close();

        // Cegah duplikasi peserta berdasarkan email pada event yang sama
        $dup = $conn->prepare('SELECT id FROM participant WHERE appointment_id = ? AND email = ? LIMIT 1');
        $dup->bind_param('is', $eventId, $email);
        $dup->execute();
        $dup->store_result();
        if ($dup->num_rows > 0) {
            $dup->close();
            throw new Exception('Peserta dengan email ini sudah terdaftar pada event.');
        }
        $dup->close();

        // Simpan ke tabel participant (dengan email)
        $stmt = $conn->prepare('INSERT INTO participant (appointment_id, name, email) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $eventId, $name, $email);
        $stmt->execute();
        $stmt->close();

        $success = true;
        $message = 'Peserta berhasil disimpan.';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'list_participants') {
        $appointmentId = intval($_POST['appointment_id'] ?? 0);
        if ($appointmentId <= 0) {
            throw new Exception('Parameter appointment_id tidak valid.');
        }
        $stmt = $conn->prepare('SELECT id, name, email, created_at FROM participant WHERE appointment_id = ? ORDER BY created_at DESC');
        $stmt->bind_param('i', $appointmentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();

        $success = true;
        echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_participant') {
        $participantId = intval($_POST['participant_id'] ?? 0);
        if ($participantId <= 0) {
            throw new Exception('Parameter participant_id tidak valid.');
        }
        $stmt = $conn->prepare('DELETE FROM participant WHERE id = ?');
        $stmt->bind_param('i', $participantId);
        $stmt->execute();
        $stmt->close();

        $success = true;
        $message = 'Peserta dihapus.';
    } else {
        throw new Exception('Metode atau aksi tidak valid.');
    }
} catch (Throwable $e) {
    $success = false;
    $message = $e->getMessage();
}

echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);