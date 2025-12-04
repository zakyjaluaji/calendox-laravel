<?php
header('Content-Type: application/json');

require_once __DIR__ . '/google-config.php';
require_once __DIR__ . '/connection.php'; // MySQLi $conn
use Google\Service\Calendar;
use Google\Service\Calendar\Event as GoogleCalendarEvent;

// Check Google API client
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  echo json_encode(['success' => false, 'message' => 'Google API Client not installed. Run: composer require google/apiclient:^2.15']);
  exit;
}
require_once __DIR__ . '/vendor/autoload.php';

// Ensure appointments table has google_event_id column
try {
  $conn->query("ALTER TABLE appointments ADD COLUMN google_event_id VARCHAR(64) NULL AFTER id");
} catch (Throwable $ignore) { /* ignore if exists */
}

// Prepare Google client/service
$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope(Calendar::CALENDAR);

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
    // Tambahan: tangani kegagalan refresh token
    if (isset($newToken['error'])) {
      echo json_encode([
        'success' => false,
        'message' => 'Refresh token tidak valid. Hubungkan ulang Google (jalankan Connect Google). Detail: ' . ($newToken['error_description'] ?? $newToken['error']),
      ]);
      exit;
    }
    saveGoogleToken($newToken);
    $client->setAccessToken($newToken);
  } else {
    echo json_encode(['success' => false, 'message' => 'Token kadaluarsa dan tidak ada refresh token. Hubungkan ulang Google.']);
    exit;
  }
}

$service = new Calendar($client);

// Optional: push local selected appointment to Google FIRST (local priority)
$appointmentId = intval($_POST['appointment_id'] ?? $_GET['appointment_id'] ?? 0);
$pushed = null;
if ($appointmentId > 0) {
  // Fetch local appointment
  $stmt = $conn->prepare('SELECT id, google_event_id, title, pic_name, start_date, end_date, start_time, end_time, attachment_filename FROM appointments WHERE id = ?');
  $stmt->bind_param('i', $appointmentId);
  $stmt->execute();
  $result = $stmt->get_result();
  $ap = $result->fetch_assoc();
  $stmt->close();

  // Fetch participants (attendees)
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

  if ($ap) {
    // Build RFC3339
    $sRFC = (new DateTime($ap['start_date'] . ' ' . $ap['start_time']))->format(DateTime::RFC3339);
    $eRFC = (new DateTime($ap['end_date'] . ' ' . $ap['end_time']))->format(DateTime::RFC3339);

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

    $event = new GoogleCalendarEvent([
      'summary' => $ap['title'] ?? 'Untitled',
      'description' => $description,
      'start' => ['dateTime' => $sRFC, 'timeZone' => APP_TIMEZONE],
      'end' => ['dateTime' => $eRFC, 'timeZone' => APP_TIMEZONE],
      'attendees' => $participants,
    ]);

    try {
      if (!empty($ap['google_event_id'])) {
        $updated = $service->events->update(GOOGLE_CALENDAR_ID, $ap['google_event_id'], $event);
        $pushed = $updated->id;
      } else {
        $created = $service->events->insert(GOOGLE_CALENDAR_ID, $event);
        $gid = $created->id;
        // Save google_event_id back to DB
        $upd = $conn->prepare('UPDATE appointments SET google_event_id = ? WHERE id = ?');
        $upd->bind_param('si', $gid, $appointmentId);
        $upd->execute();
        $upd->close();
        $pushed = $gid;
      }
    } catch (Throwable $e) {
      echo json_encode([
        'success' => false,
        'message' => 'Gagal sinkron lokal→Google: ' . $e->getMessage(),
      ]);
      exit;
    }
  }
}

// Time window: -1 month to +1 month from now (request)
date_default_timezone_set(APP_TIMEZONE);
$now = new DateTime('now');
$min = (clone $now)->modify('-1 month');
$max = (clone $now)->modify('+1 month');
$timeMin = $min->format(DateTime::RFC3339);
$timeMax = $max->format(DateTime::RFC3339);

// Push ALL local events within window to Google first (local priority)
try {
  $minDate = $min->format('Y-m-d');
  $maxDate = $max->format('Y-m-d');
  // Push ALL local events within window to Google first (local priority)
  $q = $conn->prepare('SELECT id, google_event_id, title, pic_name, start_date, end_date, start_time, end_time, attachment_filename FROM appointments WHERE end_date >= ? AND start_date <= ?');
  $q->bind_param('ss', $minDate, $maxDate);
  $q->execute();
  $resLocal = $q->get_result();
  $pushedCount = 0;
  while ($ap = $resLocal->fetch_assoc()) {
    if (empty($ap['start_date']) || empty($ap['start_time']) || empty($ap['end_date']) || empty($ap['end_time'])) {
      continue;
    }
    // Hitung RFC3339 untuk start/end
    $sRFC = (new DateTime($ap['start_date'] . ' ' . $ap['start_time']))->format(DateTime::RFC3339);
    $eRFC = (new DateTime($ap['end_date'] . ' ' . $ap['end_time']))->format(DateTime::RFC3339);

    // Ambil participants (attendees) untuk appointment ini
    $participants = [];
    $ps = $conn->prepare('SELECT name, email FROM participant WHERE appointment_id = ?');
    $ps->bind_param('i', $ap['id']);
    $ps->execute();
    $resP = $ps->get_result();
    while ($row = $resP->fetch_assoc()) {
      if (!empty($row['email'])) {
        $participants[] = ['email' => $row['email'], 'displayName' => $row['name']];
      }
    }
    $ps->close();

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

    $event = new GoogleCalendarEvent([
      'summary' => $ap['title'] ?? 'Untitled',
      'description' => $description,
      'start' => ['dateTime' => $sRFC, 'timeZone' => APP_TIMEZONE],
      'end' => ['dateTime' => $eRFC, 'timeZone' => APP_TIMEZONE],
      'attendees' => $participants,
    ]);

    try {
      if (!empty($ap['google_event_id'])) {
        $updated = $service->events->update(GOOGLE_CALENDAR_ID, $ap['google_event_id'], $event);
        if ($updated && $updated->id) $pushedCount++;
      } else {
        $created = $service->events->insert(GOOGLE_CALENDAR_ID, $event);
        $gid = $created->id;
        $upd = $conn->prepare('UPDATE appointments SET google_event_id = ? WHERE id = ?');
        $upd->bind_param('si', $gid, $ap['id']); // perbaikan: gunakan $ap['id']
        $upd->execute();
        $upd->close();
        if ($gid) $pushedCount++;
      }
    } catch (Throwable $e) {
      // continue; collect errors later if needed
    }
  }
  $q->close();
} catch (Throwable $e) {
  echo json_encode(['success' => false, 'message' => 'Gagal push batch lokal→Google: ' . $e->getMessage()]);
  exit;
}

// List events from Google
$optParams = [
  'timeMin' => $timeMin,
  'timeMax' => $timeMax,
  'singleEvents' => true,
  'orderBy' => 'startTime',
];

// Tambahan: bungkus pemanggilan listEvents agar error jadi JSON
try {
  $events = $service->events->listEvents(GOOGLE_CALENDAR_ID, $optParams);
} catch (Throwable $e) {
  echo json_encode([
    'success' => false,
    'message' => 'Gagal menarik event dari Google: ' . $e->getMessage(),
  ]);
  exit;
}
$imported = 0;
$participantsImported = 0;

// Helper to parse PIC from description "PIC: Name"
function parsePicFromDescription($desc)
{
  if (!$desc) return '';
  $desc = (string)$desc;
  if (stripos($desc, 'PIC:') !== false) {
    // e.g., "PIC: John Doe" take part after colon
    $parts = explode('PIC:', $desc, 2);
    $val = trim($parts[1] ?? '');
    // stop at newline if present
    $val = preg_split('/\r?\n/', $val)[0];
    return $val;
  }
  return '';
}

// Upsert attendees (participants)
function upsertParticipant($conn, $appointmentId, $name, $email)
{
  if (!$email) return 0;
  $check = $conn->prepare('SELECT id FROM participant WHERE appointment_id = ? AND email = ?');
  $check->bind_param('is', $appointmentId, $email);
  $check->execute();
  $res = $check->get_result();
  $exists = $res && $res->fetch_assoc();
  $check->close();
  if ($exists) return 0;

  $ins = $conn->prepare('INSERT INTO participant (appointment_id, name, email) VALUES (?, ?, ?)');
  $ins->bind_param('iss', $appointmentId, $name, $email);
  $ok = $ins->execute();
  $ins->close();
  return $ok ? 1 : 0;
}

// Import: only INSERT new local rows; do not override existing local data (prioritaskan lokal)
foreach ($events->getItems() as $item) {
  $gid = $item->getId();
  $summary = $item->getSummary();
  $description = $item->getDescription();
  $pic = parsePicFromDescription($description);

  $start = $item->getStart();
  $end   = $item->getEnd();

  $startDate = '';
  $startTime = '';
  $endDate = '';
  $endTime = '';

  if ($start->getDateTime()) {
    $sdt = new DateTime($start->getDateTime());
    $startDate = $sdt->format('Y-m-d');
    $startTime = $sdt->format('H:i');
  } else {
    // all-day event: start date yang diberikan Google adalah hari aktual
    $startDate = $start->getDate();
    $startTime = '00:00';
  }

  if ($end->getDateTime()) {
    $edt = new DateTime($end->getDateTime());
    $endDate = $edt->format('Y-m-d');
    $endTime = $edt->format('H:i');
  } else {
    // all-day event: end date dari Google bersifat eksklusif (hari berikutnya)
    // kurangi 1 hari agar sesuai dengan hari terakhir aktual
    $endExclusive = new DateTime($end->getDate());
    $endExclusive->modify('-1 day');
    $endDate = $endExclusive->format('Y-m-d');
    $endTime = '23:59';
  }

  // Check if appointment exists by google_event_id
  $check = $conn->prepare('SELECT id FROM appointments WHERE google_event_id = ?');
  $check->bind_param('s', $gid);
  $check->execute();
  $res = $check->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $check->close();

  if ($row && isset($row['id'])) {
    $appointmentId = intval($row['id']);
    // Do NOT update local fields to preserve local priority
  } else {
    // Insert new
    $ins = $conn->prepare('INSERT INTO appointments (title, pic_name, start_date, end_date, start_time, end_time, google_event_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $title = $summary ?: 'Untitled';
    $ins->bind_param('sssssss', $title, $pic, $startDate, $endDate, $startTime, $endTime, $gid);
    if ($ins->execute()) {
      $imported++;
      $appointmentId = $ins->insert_id;
    } else {
      $appointmentId = 0;
    }
    $ins->close();
  }

  // Upsert participants (attendees)
  if (!empty($appointmentId)) {
    $attendees = $item->getAttendees();
    if (is_array($attendees)) {
      foreach ($attendees as $att) {
        $name = is_array($att) ? ($att['displayName'] ?? '') : (method_exists($att, 'getDisplayName') ? $att->getDisplayName() : '');
        $email = is_array($att) ? ($att['email'] ?? '') : (method_exists($att, 'getEmail') ? $att->getEmail() : '');
        $participantsImported += upsertParticipant($conn, $appointmentId, $name, $email);
      }
    }
  }
}


echo json_encode([
  'success' => true,
  'message' => 'Sinkron dua arah selesai.',
  'imported' => $imported,
  'participants_imported' => $participantsImported,
  'pushed_google_event_id' => $pushed,
  'pushed_count' => isset($pushedCount) ? $pushedCount : null,
]);
