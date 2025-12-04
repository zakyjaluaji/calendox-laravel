<?php

include "connection.php";

// Pastikan kolom attachment_filename tersedia pada tabel appointments
try {
    $conn->query("ALTER TABLE appointments ADD COLUMN attachment_filename VARCHAR(255) NULL AFTER end_time");
} catch (Throwable $ignore) { /* kolom mungkin sudah ada */
}

// Pastikan kolom color tersedia pada tabel appointments
try {
    $conn->query("ALTER TABLE appointments ADD COLUMN color VARCHAR(32) NULL AFTER attachment_filename");
} catch (Throwable $ignore) { /* kolom mungkin sudah ada */
}

// Palet warna yang diizinkan (hex)
$ALLOWED_COLORS = [
    '#3b82f6', // Blue
    '#1e3a8a', // Blue Dark
    '#6366f1', // Indigo
    '#8b5cf6', // Violet
    '#14b8a6', // Teal
    '#10b981', // Emerald
    '#f59e0b', // Amber
    '#f43f5e', // Rose
    '#ef4444', // Red
    '#64748b', // Slate
];
// Lowercase untuk pembanding
$ALLOWED_COLORS_LOWER = array_map('strtolower', $ALLOWED_COLORS);

// Helper: validasi upload PDF dengan batas ukuran
function validatePdfUpload(array $file, int $maxBytes, string &$message): bool
{
    $message = '';
    if (!isset($file['error'])) {
        $message = 'Data upload tidak ditemukan.';
        return false;
    }
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        // Tidak ada file yang diunggah (bukan kesalahan)
        return false;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errMap = [
            UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas server.',
            UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas form.',
            UPLOAD_ERR_PARTIAL => 'File terunggah sebagian.',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diunggah.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary hilang.',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk.',
            UPLOAD_ERR_EXTENSION => 'Upload dibatalkan oleh extension.',
        ];
        $message = 'Upload gagal: ' . ($errMap[$file['error']] ?? 'Kesalahan tidak diketahui');
        return false;
    }
    $size = intval($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        $message = 'Ukuran file melebihi batas 5MB.';
        return false;
    }
    $name = (string)($file['name'] ?? '');
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        $message = 'File harus berekstensi .pdf';
        return false;
    }
    $tmp = (string)($file['tmp_name'] ?? '');
    $mime = '';
    if (function_exists('finfo_open')) {
        $f = finfo_open(FILEINFO_MIME_TYPE);
        if ($f) {
            $mime = finfo_file($f, $tmp) ?: '';
            finfo_close($f);
        }
    } elseif (function_exists('mime_content_type')) {
        $mime = mime_content_type($tmp) ?: '';
    }
    $allowed = ['application/pdf', 'application/x-pdf', 'application/acrobat', 'application/octet-stream'];
    if ($mime && !in_array($mime, $allowed, true)) {
        $message = 'Tipe file tidak valid (wajib PDF).';
        return false;
    }
    return true;
}

function sanitizePdfFilename(string $name): string
{
    $base = pathinfo($name, PATHINFO_FILENAME);
    $base = preg_replace('/[^A-Za-z0-9._-]/', '_', $base);
    if ($base === '') $base = 'attachment';
    return $base . '.pdf';
}

// Optional: Google client for deletion of linked Google events
require_once __DIR__ . '/google-config.php';
// Load Google API client only when needed (in delete flow)

$successMsg = '';
$errorMsg = '';
$eventsFromDB = [];

// ✅ Handle Add Appointment
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === "add") {
    $course      = trim($_POST["title"] ?? '');
    $instructor  = trim($_POST["pic_name"] ?? '');
    $start       = $_POST["start_date"] ?? '';
    $end         = $_POST["end_date"] ?? '';
    $startTime   = $_POST["start_time"] ?? '';
    $endTime     = $_POST["end_time"] ?? '';
    $color       = trim($_POST["color"] ?? '');
    // Enforce allowed palette
    if (!$color || !in_array(strtolower($color), $ALLOWED_COLORS_LOWER, true)) {
        $color = $ALLOWED_COLORS[0]; // default
    }
    $uploadMsg   = '';

    // Validasi file bila diunggah
    $hasFile = isset($_FILES['attachment']) && ($_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE);
    if ($hasFile) {
        $valid = validatePdfUpload($_FILES['attachment'], 5 * 1024 * 1024, $uploadMsg);
        if (!$valid) {
            error_log('[ADD] Upload tidak valid: ' . $uploadMsg);
            header("Location: " . $_SERVER["PHP_SELF"] . "?error=1");
            exit;
        }
    }

    if ($course && $instructor && $start && $end && $startTime && $endTime) {
        $stmt = $conn->prepare(
            "INSERT INTO appointments (title, pic_name, start_date, end_date, start_time, end_time, color) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("sssssss", $course, $instructor, $start, $end, $startTime, $endTime, $color);
        $stmt->execute();
        $newId = $conn->insert_id;
        $stmt->close();

        // Simpan file bila ada
        if ($hasFile) {
            $dir = __DIR__ . '/uploads/appointments/' . $newId;
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0777, true)) {
                    error_log('[ADD] Gagal membuat folder: ' . $dir);
                }
            }
            $safeName = sanitizePdfFilename($_FILES['attachment']['name'] ?? 'attachment.pdf');
            $target = $dir . '/' . $safeName;
            $tmp = $_FILES['attachment']['tmp_name'] ?? '';
            if (!move_uploaded_file($tmp, $target)) {
                error_log('[ADD] Gagal memindahkan file ke: ' . $target);
            } else {
                $us = $conn->prepare('UPDATE appointments SET attachment_filename = ? WHERE id = ?');
                $us->bind_param('si', $safeName, $newId);
                $us->execute();
                $us->close();
            }
        }

        header("Location: " . $_SERVER["PHP_SELF"] . "?success=1");
        exit;
    } else {
        header("Location: " . $_SERVER["PHP_SELF"] . "?error=1");
        exit;
    }
}

// ✏️ Handle Edit Appointment
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === "edit") {
    $id          = $_POST["event_id"] ?? null;
    $course      = trim($_POST["title"] ?? '');
    $instructor  = trim($_POST["pic_name"] ?? '');
    $start       = $_POST["start_date"] ?? '';
    $end         = $_POST["end_date"] ?? '';
    $startTime   = $_POST["start_time"] ?? '';
    $endTime     = $_POST["end_time"] ?? '';
    $color       = trim($_POST["color"] ?? '');
    // Enforce allowed palette
    if (!$color || !in_array(strtolower($color), $ALLOWED_COLORS_LOWER, true)) {
        $color = $ALLOWED_COLORS[0]; // default
    }
    $uploadMsg   = '';

    // Validasi file bila diunggah
    $hasFile = isset($_FILES['attachment']) && ($_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE);
    if ($hasFile) {
        $valid = validatePdfUpload($_FILES['attachment'], 5 * 1024 * 1024, $uploadMsg);
        if (!$valid) {
            error_log('[EDIT] Upload tidak valid: ' . $uploadMsg);
            header("Location: " . $_SERVER["PHP_SELF"] . "?error=2");
            exit;
        }
    }

    if ($id && $course && $instructor && $start && $end && $startTime && $endTime) {
        // Ambil nama file lama (jika ada)
        $oldFile = null;
        try {
            $gs = $conn->prepare('SELECT attachment_filename FROM appointments WHERE id = ?');
            $gs->bind_param('i', $id);
            $gs->execute();
            $gr = $gs->get_result();
            $row = $gr ? $gr->fetch_assoc() : null;
            $gs->close();
            if ($row && !empty($row['attachment_filename'])) {
                $oldFile = $row['attachment_filename'];
            }
        } catch (Throwable $e) {
            // abaikan
        }

        $stmt = $conn->prepare(
            "UPDATE appointments SET title = ?, pic_name = ?, start_date = ?, end_date = ?, start_time = ?, end_time = ?, color = ? 
             WHERE id = ?"
        );
        $stmt->bind_param("sssssssi", $course, $instructor, $start, $end, $startTime, $endTime, $color, $id);
        $stmt->execute();
        $stmt->close();

        // Simpan file baru bila ada
        if ($hasFile) {
            $dir = __DIR__ . '/uploads/appointments/' . intval($id);
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0777, true)) {
                    error_log('[EDIT] Gagal membuat folder: ' . $dir);
                }
            }
            $safeName = sanitizePdfFilename($_FILES['attachment']['name'] ?? 'attachment.pdf');
            $target = $dir . '/' . $safeName;
            $tmp = $_FILES['attachment']['tmp_name'] ?? '';
            if (!move_uploaded_file($tmp, $target)) {
                error_log('[EDIT] Gagal memindahkan file ke: ' . $target);
            } else {
                // Hapus file lama jika beda nama
                if ($oldFile && $oldFile !== $safeName) {
                    $oldPath = $dir . '/' . $oldFile;
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $us = $conn->prepare('UPDATE appointments SET attachment_filename = ? WHERE id = ?');
                $us->bind_param('si', $safeName, $id);
                $us->execute();
                $us->close();
            }
        }

        header("Location: " . $_SERVER["PHP_SELF"] . "?success=2");
        exit;
    } else {
        header("Location: " . $_SERVER["PHP_SELF"] . "?error=2");
        exit;
    }
}

// 🗑️ Handle Delete Appointment
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === "delete") {
    $id = $_POST["event_id"] ?? null;

    if ($id) {
        // Attempt to delete linked Google Calendar event first
        try {
            // Fetch google_event_id for this appointment
            $gidStmt = $conn->prepare("SELECT google_event_id FROM appointments WHERE id = ? LIMIT 1");
            $gidStmt->bind_param("i", $id);
            $gidStmt->execute();
            $gidRes = $gidStmt->get_result();
            $gidRow = $gidRes ? $gidRes->fetch_assoc() : null;
            $gidStmt->close();

            $googleEventId = $gidRow && !empty($gidRow['google_event_id']) ? $gidRow['google_event_id'] : null;

            if ($googleEventId) {
                // Ensure Google API client exists
                if (file_exists(__DIR__ . '/vendor/autoload.php')) {
                    require_once __DIR__ . '/vendor/autoload.php';

                    $client = new \Google_Client();
                    $client->setClientId(GOOGLE_CLIENT_ID);
                    $client->setClientSecret(GOOGLE_CLIENT_SECRET);
                    $client->setRedirectUri(GOOGLE_REDIRECT_URI);
                    $client->addScope(\Google\Service\Calendar::CALENDAR);

                    $token = loadGoogleToken();
                    if ($token) {
                        $client->setAccessToken($token);
                        if ($client->isAccessTokenExpired()) {
                            $refreshToken = $client->getRefreshToken();
                            if ($refreshToken) {
                                $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                                if (is_array($newToken)) {
                                    saveGoogleToken($newToken);
                                    $client->setAccessToken($newToken);
                                }
                            }
                        }

                        $service = new \Google\Service\Calendar($client);
                        try {
                            $service->events->delete(GOOGLE_CALENDAR_ID, $googleEventId);
                        } catch (\Throwable $ge) {
                            // Swallow errors: continue deleting locally
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore Google deletion errors; proceed with local deletion
        }

        // Hapus peserta terkait terlebih dahulu untuk memastikan konsistensi
        // (tambahan defensif selain FK ON DELETE CASCADE)
        $stmtP = $conn->prepare("DELETE FROM participant WHERE appointment_id = ?");
        $stmtP->bind_param("i", $id);
        $stmtP->execute();
        $stmtP->close();

        $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header("Location: " . $_SERVER["PHP_SELF"] . "?success=3");
        exit;
    }
}

// ✅ Success & Error Messages
if (isset($_GET["success"])) {
    switch ($_GET["success"]) {
        case '1':
            $successMsg = "✅ Jadwal berhasil ditambahkan";
            break;
        case '2':
            $successMsg = "✅ Jadwal berhasil diperbarui";
            break;
        case '3':
            $successMsg = "🗑️ Jadwal berhasil dihapus";
            break;
        default:
            $successMsg = '';
            break;
    }
}

if (isset($_GET["error"])) {
    $errorMsg = '❗ Terjadi kesalahan. Silakan periksa masukan Anda.';
}

// 📅 Fetch Appointments from DB and spread by date
$result = $conn->query("SELECT * FROM appointments");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $start = new DateTime($row["start_date"]);
        $end   = new DateTime($row["end_date"]);
        while ($start <= $end) {
            $eventsFromDB[] = [
                "id"          => $row["id"],
                "title"       => "{$row['title']} - {$row['pic_name']}",
                "date"        => $start->format('Y-m-d'),
                "start"       => $row["start_date"],
                "end"         => $row["end_date"],
                "start_time"  => $row["start_time"],
                "end_time"    => $row["end_time"],
                "color"       => isset($row['color']) ? $row['color'] : null,
                // Lampiran (opsional)
                "attachment_filename" => isset($row['attachment_filename']) ? $row['attachment_filename'] : null,
                "attachment_url" => (!empty($row['attachment_filename'])) ? ("uploads/appointments/" . $row['id'] . "/" . $row['attachment_filename']) : ''
            ];
            $start->modify('+1 day');
        }
    }
}
$conn->close();
