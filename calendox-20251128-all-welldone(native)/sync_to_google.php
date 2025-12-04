<?php
header('Content-Type: application/json');

require_once __DIR__ . '/google-config.php';
require_once __DIR__ . '/connection.php'; // MySQLi $conn

// Check Google API client
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  echo json_encode(['success' => false, 'message' => 'Google API Client not installed. Run: composer require google/apiclient:^2.15']);
  exit;
}
require_once __DIR__ . '/vendor/autoload.php';

// Ensure appointments table has google_event_id column
try {
  $conn->query("ALTER TABLE appointments ADD COLUMN google_event_id VARCHAR(64) NULL AFTER id");
} catch (Throwable $ignore) { /* ignore if exists */ }

// Read appointment id
$appointmentId = intval($_POST['appointment_id'] ?? $_GET['appointment_id'] ?? 0);
if ($appointmentId <= 0) {
  echo json_encode(['success' => false, 'message' => 'appointment_id tidak valid']);
  exit;
}

// Fetch appointment
$stmt = $conn->prepare('SELECT id, google_event_id, title, pic_name, start_date, end_date, start_time, end_time, attachment_filename FROM appointments WHERE id = ?');
$stmt->bind_param('i', $appointmentId);
$stmt->execute();
$result = $stmt->get_result();
$ap = $result->fetch_assoc();
$stmt->close();

if (!$ap) {
  echo json_encode(['success' => false, 'message' => 'Appointment tidak ditemukan']);
  exit;
}

// Fetch participants
$participants = [];
$ps = $conn->prepare('SELECT name, email FROM participant WHERE appointment_id = ?');
$ps->bind_param('i', $appointmentId);
$ps->execute();
$resP = $ps->get_result();
while ($row = $resP->fetch_assoc()) {
  if (!empty($row['email'])) {
    $participants[] = ['email' => $row['email'], 'displayName' => $row['name']];
  }
}
$ps->close();

// Build RFC3339 dateTimes
date_default_timezone_set(APP_TIMEZONE);
function toRFC3339($date, $time) {
  $dt = new DateTime($date . ' ' . $time);
  return $dt->format(DateTime::RFC3339);
}

if (empty($ap['start_date']) || empty($ap['start_time']) || empty($ap['end_date']) || empty($ap['end_time'])) {
  echo json_encode(['success' => false, 'message' => 'Tanggal/waktu event tidak lengkap']);
  exit;
}

$startRFC = toRFC3339($ap['start_date'], $ap['start_time']);
$endRFC   = toRFC3339($ap['end_date'], $ap['end_time']);

// Prepare Google client/service
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope(Google_Service_Calendar::CALENDAR);

$token = loadGoogleToken();
if (!$token) {
  echo json_encode(['success' => false, 'message' => 'Belum terhubung ke Google. Klik Connect Google dahulu.']);
  exit;
}
$client->setAccessToken($token);
if ($client->isAccessTokenExpired()) {
  $refreshToken = $client->getRefreshToken();
  if ($refreshToken) {
    $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
    saveGoogleToken($newToken);
    $client->setAccessToken($newToken);
  } else {
    echo json_encode(['success' => false, 'message' => 'Token kadaluarsa dan tidak ada refresh token. Hubungkan ulang Google.']);
    exit;
  }
}

$service = new Google_Service_Calendar($client);

// Bangun URL lampiran absolut jika ada
$attachmentUrl = '';
if (!empty($ap['attachment_filename'])) {
  $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
  $attachmentUrl = $baseUrl . '/uploads/appointments/' . $ap['id'] . '/' . $ap['attachment_filename'];
}
// Gabungkan description (PIC + Lampiran)
$descParts = [];
if (!empty($ap['pic_name'])) $descParts[] = 'PIC: ' . $ap['pic_name'];
if (!empty($attachmentUrl))  $descParts[] = 'Lampiran: ' . $attachmentUrl;
$description = !empty($descParts) ? implode("\n", $descParts) : null;

// Build event payload
$event = new Google_Service_Calendar_Event([
  'summary' => $ap['title'] ?? 'Untitled',
  'description' => $description,
  'start' => ['dateTime' => $startRFC, 'timeZone' => APP_TIMEZONE],
  'end' => ['dateTime' => $endRFC, 'timeZone' => APP_TIMEZONE],
  'attendees' => $participants,
]);

try {
  if (!empty($ap['google_event_id'])) {
    // Update existing event
    $updated = $service->events->update(GOOGLE_CALENDAR_ID, $ap['google_event_id'], $event);
    echo json_encode(['success' => true, 'message' => 'Event Google diperbarui', 'google_event_id' => $updated->id]);
  } else {
    // Insert new event
    $created = $service->events->insert(GOOGLE_CALENDAR_ID, $event);
    // Save google_event_id
    $upd = $conn->prepare('UPDATE appointments SET google_event_id = ? WHERE id = ?');
    $gid = $created->id;
    $upd->bind_param('si', $gid, $appointmentId);
    $upd->execute();
    $upd->close();
    echo json_encode(['success' => true, 'message' => 'Event Google dibuat', 'google_event_id' => $created->id]);
  }
} catch (Throwable $e) {
  echo json_encode(['success' => false, 'message' => 'Gagal sinkron: ' . $e->getMessage()]);
}