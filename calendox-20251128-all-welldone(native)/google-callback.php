<?php
require_once __DIR__ . '/google-config.php';

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  echo '<h3>Google API Client not found.</h3>';
  echo '<p>Please install via Composer:</p>';
  echo '<pre>composer require google/apiclient:^2.15</pre>';
  exit;
}
require_once __DIR__ . '/vendor/autoload.php';

if (!isset($_GET['code'])) {
  echo 'Missing authorization code.';
  exit;
}

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);

$token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
if (isset($token['error'])) {
  echo 'Error fetching access token: ' . htmlspecialchars($token['error_description'] ?? $token['error']);
  exit;
}

saveGoogleToken($token);

echo '<h3>Google Calendar connected successfully.</h3>';
echo '<p>Token saved. You can now sync events.</p>';
echo '<p><a href="./">Return to Calendar</a></p>';