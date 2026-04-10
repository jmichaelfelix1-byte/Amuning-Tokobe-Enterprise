<?php
// Test PayMongo API Connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Simulate a logged-in user
$_SESSION['user_id'] = 1;
$_SESSION['user_email'] = 'test@example.com';
$_SESSION['user_name'] = 'Test User';

echo "<h2>Testing PayMongo Integration</h2>";
echo "<pre>";

// Test 1: Check if config file loads
echo "\n=== Test 1: Loading Configuration ===\n";
if (file_exists('includes/paymongo_config.php')) {
    echo "✓ paymongo_config.php exists\n";
    require_once 'includes/paymongo_config.php';
    echo "✓ Configuration loaded\n";
    echo "API URL: " . PAYMONGO_API_URL . "\n";
    echo "Public Key: " . substr(PAYMONGO_PUBLIC_KEY, 0, 10) . "...\n";
} else {
    echo "✗ paymongo_config.php NOT FOUND\n";
    exit;
}

// Test 2: Check database connection
echo "\n=== Test 2: Database Connection ===\n";
if (file_exists('includes/config.php')) {
    echo "✓ config.php exists\n";
    require_once 'includes/config.php';
    
    // Test connection
    if ($conn->connect_error) {
        echo "✗ Database connection error: " . $conn->connect_error . "\n";
    } else {
        echo "✓ Database connected\n";
        
        // Check if paymongo_checkouts table exists
        $result = $conn->query("SHOW TABLES LIKE 'paymongo_checkouts'");
        if ($result->num_rows > 0) {
            echo "✓ paymongo_checkouts table exists\n";
        } else {
            echo "✗ paymongo_checkouts table does NOT exist - need to run migration\n";
        }
    }
} else {
    echo "✗ config.php NOT FOUND\n";
    exit;
}

// Test 3: Test PayMongo cURL request
echo "\n=== Test 3: PayMongo API Connection ===\n";
echo "Creating test checkout session...\n\n";

$testCheckoutData = [
    'data' => [
        'attributes' => [
            'line_items' => [
                [
                    'amount' => 50000, // 500 PHP in cents
                    'currency' => 'PHP',
                    'description' => 'Test Payment',
                    'quantity' => 1,
                    'name' => 'Test Payment'
                ]
            ],
            'payment_method_types' => ['gcash'],
            'reference_number' => 'test_123',
            'description' => 'Test Payment from API Test',
            'success_url' => 'http://localhost/Amuning/public/payment.php?success=1',
            'cancel_url' => 'http://localhost/Amuning/public/payment.php?cancel=1',
            'client_key' => PAYMONGO_PUBLIC_KEY,
            'customer' => [
                'email' => 'test@example.com',
                'name' => 'Test User'
            ]
        ]
    ]
];

echo "Request Data:\n";
echo json_encode($testCheckoutData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

// Make cURL request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, PAYMONGO_API_URL . '/checkout_sessions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

$auth = base64_encode(PAYMONGO_SECRET_KEY . ':');
$headers = [
    'Authorization: Basic ' . $auth,
    'Content-Type: application/json'
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testCheckoutData));

echo "Sending request to: " . PAYMONGO_API_URL . '/checkout_sessions' . "\n";
echo "Auth: Basic " . substr($auth, 0, 20) . "...\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Status Code: " . $httpCode . "\n\n";

if ($curlError) {
    echo "✗ cURL Error: " . $curlError . "\n";
} else {
    echo "✓ Request completed\n";
    echo "\nResponse:\n";
    $responseData = json_decode($response, true);
    echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    
    if ($httpCode >= 200 && $httpCode < 300) {
        echo "\n✓ API request successful!\n";
        if (isset($responseData['data']['attributes']['checkout_url'])) {
            echo "Checkout URL: " . $responseData['data']['attributes']['checkout_url'] . "\n";
        }
    } else {
        echo "\n✗ API error - check response above\n";
    }
}

echo "\n</pre>";
?>
