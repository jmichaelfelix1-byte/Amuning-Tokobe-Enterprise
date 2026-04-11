<?php
// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', '497276011851-0289o7mu4tocr5k2frpes1955rpkedc8.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

// Hardcoded for Render deployment
define('GOOGLE_REDIRECT_URI', 'https://amuning-tokobe-enterprise.onrender.com/google_auth.php');

// Include Google API Client - use absolute path from root
require_once dirname(__DIR__, 2) . '/google_login/vendor/autoload.php';

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");
?>
