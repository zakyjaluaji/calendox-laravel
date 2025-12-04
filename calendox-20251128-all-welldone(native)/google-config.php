<?php
// Google API configuration (fill in your credentials)
define('GOOGLE_CLIENT_ID', '409698924574-2ccrqpsf9032l74o639ph0hl3lakojp2.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');
define('GOOGLE_REDIRECT_URI', 'https://cal.isi-ska.id/google-callback.php');

// Calendar to use (usually the primary email of the Google account)
define('GOOGLE_CALENDAR_ID', 'primary'); // or set a specific calendar email

// Where to store OAuth tokens locally (server absolute path)
define('GOOGLE_TOKEN_PATH', '/var/www/html/berkas/rest/google-token.json');

// Timezone used for events
define('APP_TIMEZONE', 'Asia/Jakarta');

// Helper: load token if exists
function loadGoogleToken()
{
  if (file_exists(GOOGLE_TOKEN_PATH)) {
    $json = file_get_contents(GOOGLE_TOKEN_PATH);
    $data = json_decode($json, true);
    if (is_array($data)) return $data;
  }
  return null;
}

function saveGoogleToken(array $token)
{
  $dir = dirname(GOOGLE_TOKEN_PATH);
  if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
  }
  file_put_contents(GOOGLE_TOKEN_PATH, json_encode($token));
}

// JWT secret fallback untuk SSO (dipakai jika env CALENDOX_JWT_SECRET tidak tersedia)
// Catatan: untuk produksi, sebaiknya set via environment, bukan hardcode.
$JWT_SECRET = "c9f3a1b2d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8091a2b3c4d5e6f7a80192b3c4d5";
