<?php
// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', '765395085085-4qok9ltliuvnuqfus56of2jj9592tb6o.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

// Use correct path for deployed environment
// In Docker, the document root is /var/www/html/public, so we don't need /Amuning
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'];
define('GOOGLE_REDIRECT_URI', $protocol . $domain . '/google_auth.php');

// Include Google API Client
require_once __DIR__ . '../../../google_login/vendor/autoload.php';

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");
?>
