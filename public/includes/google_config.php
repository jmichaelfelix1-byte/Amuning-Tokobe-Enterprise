<?php
// Google OAuth Configuration
define('GOOGLE_CLIENT_ID', '765395085085-4qok9ltliuvnuqfus56of2jj9592tb6o.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', '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');

// Hardcoded for Render deployment
define('GOOGLE_REDIRECT_URI', 'https://amuning-tokobe-enterprise.onrender.com/google_auth.php');

// Include Google API Client - FIXED PATH
require_once __DIR__ . '../../google_login/vendor/autoload.php';

$client = new Google_Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope("email");
$client->addScope("profile");
?>
