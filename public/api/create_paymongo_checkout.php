<?php
/**
 * PayMongo Checkout - Direct Redirect
 * Creates a PayMongo checkout session and immediately redirects to PayMongo payment page
 */

date_default_timezone_set('Asia/Manila');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
require_once '../includes/paymongo_config.php';

header('Content-Type: application/json');

// Validate session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login.']);
    exit();
}

// Get POST data
$itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$itemType = isset($_POST['item_type']) ? $_POST['item_type'] : '';
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;

// Validate parameters
if ($itemId <= 0 || empty($itemType) || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
    exit();
}

if (!in_array($itemType, ['photo_booking', 'printing_order'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid item type']);
    exit();
}

try {
    $userId = $_SESSION['user_id'];
    $userEmail = $_SESSION['user_email'] ?? 'noemail@amuning.com';
    $userName = $_SESSION['user_name'] ?? 'Valued Customer';
    
    error_log("Creating PayMongo checkout: itemId=$itemId, itemType=$itemType, amount=$amount, email=$userEmail");
    
    // Determine description
    $description = ($itemType === 'photo_booking') ? 
        'Photo Booking Payment - ID: ' . str_pad($itemId, 4, '0', STR_PAD_LEFT) :
        'Printing Order Payment - ID: ' . str_pad($itemId, 4, '0', STR_PAD_LEFT);
    
    // Generate success and cancel URLs
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $basePath = str_replace('\\', '/', dirname(dirname(__FILE__)));
    $basePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $basePath);
    
    $successUrl = $protocol . $host . $basePath . '/paymongo_success.php?reference_id=' . $itemId . '&item_type=' . $itemType;
    $cancelUrl = $protocol . $host . $basePath . '/payment.php?' . ($itemType === 'photo_booking' ? 'booking_id' : 'order_id') . '=' . $itemId;
    
    error_log("Success URL: $successUrl");
    error_log("Cancel URL: $cancelUrl");
    
    // Prepare checkout data
    $checkoutData = [
        'data' => [
            'attributes' => [
                'line_items' => [
                    [
                        'amount' => (int)($amount * 100), // Convert to cents
                        'currency' => PAYMONGO_CURRENCY,
                        'description' => $description,
                        'quantity' => 1,
                        'name' => $description
                    ]
                ],
                'payment_method_types' => [PAYMONGO_PAYMENT_METHOD],
                'reference_number' => $itemType . '_' . $itemId,
                'description' => $description,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'client_key' => PAYMONGO_PUBLIC_KEY,
                'customer' => [
                    'email' => $userEmail,
                    'name' => $userName
                ]
            ]
        ]
    ];
    
    // Make API request to PayMongo
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, PAYMONGO_API_URL . '/checkout_sessions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $auth = base64_encode(PAYMONGO_SECRET_KEY . ':');
    $headers = [
        'Authorization: Basic ' . $auth,
        'Content-Type: application/json'
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($checkoutData));
    
    error_log("Sending request to PayMongo API...");
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    error_log("PayMongo API Response Code: $httpCode");
    error_log("PayMongo API Response: $response");
    
    if ($curlError) {
        throw new Exception('Network Error: ' . $curlError);
    }
    
    $responseData = json_decode($response, true);
    
    if ($httpCode !== 201 && $httpCode !== 200) {
        $errorMsg = isset($responseData['error']['message']) ? $responseData['error']['message'] : 'PayMongo API error';
        throw new Exception("PayMongo Error ($httpCode): " . $errorMsg);
    }
    
    if (!isset($responseData['data']['attributes']['checkout_url'])) {
        throw new Exception('Invalid checkout response: missing checkout_url');
    }
    
    $checkoutUrl = $responseData['data']['attributes']['checkout_url'];
    $checkoutId = $responseData['data']['id'] ?? null;
    
    // Store checkout in database for reference
    if ($checkoutId) {
        $stmt = $conn->prepare("INSERT INTO paymongo_checkouts (user_id, item_id, item_type, checkout_id, amount, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
        if ($stmt) {
            $stmt->bind_param("iissd", $userId, $itemId, $itemType, $checkoutId, $amount);
            $stmt->execute();
            $stmt->close();
            error_log("Checkout stored in database: $checkoutId");
        }
    }
    
    // Return redirect URL
    echo json_encode([
        'success' => true,
        'checkout_url' => $checkoutUrl,
        'checkout_id' => $checkoutId
    ]);
    
} catch (Exception $e) {
    error_log("PayMongo Checkout Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
