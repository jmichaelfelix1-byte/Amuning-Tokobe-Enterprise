<?php
// Load environment variables from .env
if (file_exists(dirname(dirname(dirname(__FILE__))) . '/.env')) {
    $env = parse_ini_file(dirname(dirname(dirname(__FILE__))) . '/.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

// Get credentials from environment
$clientId = getenv('GOOGLE_CLIENT_ID');
$clientSecret = getenv('GOOGLE_CLIENT_SECRET');

// Check if credentials are loaded
if (!$clientId || !$clientSecret) {
    error_log("Error: Google credentials not found in environment variables");
    die("Google OAuth credentials not configured");
}

// Define constants
define('GOOGLE_CLIENT_ID', $clientId);
define('GOOGLE_CLIENT_SECRET', $clientSecret);
define('GOOGLE_REDIRECT_URI', 'https://amuning-tokobe-enterprise.onrender.com/google_auth.php');

// Initialize Google Client
require_once dirname(dirname(dirname(__FILE__))) . '/send_email/vendor/autoload.php';

use Google\Client;

$client = new Client();
$client->setClientId(GOOGLE_CLIENT_ID);
$client->setClientSecret(GOOGLE_CLIENT_SECRET);
$client->setRedirectUri(GOOGLE_REDIRECT_URI);
$client->addScope('email');
$client->addScope('profile');
$client->setAccessType('offline');
$client->setPrompt('consent');

// Make $client available globally
global $client;
?>
