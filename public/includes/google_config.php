<?php
// Load environment variables
if (file_exists(dirname(dirname(__FILE__)) . '/.env')) {
    $env = parse_ini_file(dirname(dirname(__FILE__)) . '/.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

// Use environment variables
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID'));
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET'));
define('GOOGLE_REDIRECT_URI', 'https://amuning-tokobe-enterprise.onrender.com/google_login/callback.php');
?>
