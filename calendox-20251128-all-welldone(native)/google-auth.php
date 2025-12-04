<?php
require_once __DIR__ . '/google-config.php';

// Attempt to load Google API PHP Client
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  echo '<h3>Google API Client not found.</h3>';
  echo '<p>Please install via Composer:</p>';
  echo '<pre>composer require google/apiclient:^2.15</pre>';
  exit;
}

require_once __DIR__ . '/vendor/autoload.php';

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope(\Google\Service\Calendar::CALENDAR);
$client->setAccessType('offline');
$client->setPrompt('consent');

$authUrl = $client->createAuthUrl();
// In some embedded preview environments, automatic redirect may be blocked.
// Provide optional link mode via query param `link=1`.
if (isset($_GET['link']) && $_GET['link'] === '1') {
  echo '<h3>Google OAuth</h3>';
  echo '<p>Klik tombol di bawah untuk melanjutkan ke halaman izin Google.</p>';
  echo '<p><a class="submit-btn" style="background:#10b981;color:#fff;padding:8px 12px;text-decoration:none" href="' . htmlspecialchars($authUrl, ENT_QUOTES, 'UTF-8') . '">🔗 Lanjutkan ke Google</a></p>';
  echo '<p><a href="./">Kembali</a></p>';
  exit;
}
header('Location: ' . $authUrl);
exit;
