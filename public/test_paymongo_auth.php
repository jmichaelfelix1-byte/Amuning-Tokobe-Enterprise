<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/paymongo_config.php';

echo "<h2>PayMongo Authentication Test</h2>";
echo "<pre>";

// Test credentials
$sk = PAYMONGO_SECRET_KEY;
$pk = PAYMONGO_PUBLIC_KEY;

echo "Secret Key: " . substr($sk, 0, 10) . "...\n";
echo "Public Key: " . substr($pk, 0, 10) . "...\n\n";

// Test Base64 encoding
$auth_string = $sk . ':';
$encoded = base64_encode($auth_string);

echo "Auth String: " . $auth_string . "\n";
echo "Base64 Encoded: " . $encoded . "\n";
echo "Authorization Header: Basic " . $encoded . "\n\n";

// Test simple API call
echo "=== Testing Simple API Call ===\n";
echo "Endpoint: " . PAYMONGO_API_URL . "/checkout_sessions\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, PAYMONGO_API_URL . '/checkout_sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$headers = [
    'Authorization: Basic ' . $encoded,
    'Content-Type: application/json'
];

echo "Headers sent:\n";
print_r($headers);

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Send empty POST to test auth
$testData = [
    'data' => [
        'attributes' => [
            'line_items' => [],
            'payment_method_types' => ['gcash'],
        ]
    ]
];

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

echo "\n\nResponse HTTP Code: " . $httpCode . "\n";
echo "cURL Error: " . ($curlError ? $curlError : "None") . "\n\n";
echo "Response Body:\n";
echo $response;

echo "\n\n</pre>";
?>
