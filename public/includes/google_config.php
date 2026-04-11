<?php
// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', '497276011851-0289o7mu4tocr5k2frpes1955rpkedc8.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

// Dynamic redirect URI - uses current domain instead of localhost
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'];
define('GOOGLE_REDIRECT_URI', $protocol . $domain . '/Amuning/public/google_auth.php');

// Include Google API Client
require_once __DIR__ . '../../../google_login/vendor/autoload.php';

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");
?>
