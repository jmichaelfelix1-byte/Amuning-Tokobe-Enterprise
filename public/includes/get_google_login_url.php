<?php
header('Content-Type: application/json');

require_once 'config.php';
require_once 'google_config.php';

try {
    // Generate Google login URL
    $authUrl = $client->createAuthUrl();
    
    echo json_encode([
        'success' => true,
        'url' => $authUrl
    ]);
} catch (Exception $e) {
    error_log("Google Auth Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to generate Google login URL'
    ]);
}
?>
