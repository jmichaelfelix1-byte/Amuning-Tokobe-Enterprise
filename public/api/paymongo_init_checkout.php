<?php
/**
 * PayMongo Checkout Initialization
 * AJAX endpoint to create PayMongo checkout session for GCash payments
 */

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

session_start();
require_once '../includes/config.php';
require_once '../includes/paymongo_config.php';

// Validate session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please login first.']);
    exit();
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get POST data
$itemId = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
$itemType = isset($_POST['item_type']) ? $_POST['item_type'] : '';
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;

// Validate parameters
if ($itemId <= 0 || empty($itemType) || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit();
}

// Validate item type
if (!in_array($itemType, ['photo_booking', 'printing_order'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid item type']);
    exit();
}

try {
    $userId = $_SESSION['user_id'];
    $userEmail = $_SESSION['user_email'];
    $userName = $_SESSION['user_name'] ?? 'Valued Customer';
    
    // Create PayMongo checkout session
    $checkout = createPayMongoCheckout($itemId, $itemType, $amount, $userEmail, $userName);
    
    if (!$checkout) {
        throw new Exception('Failed to create PayMongo checkout session');
    }
    
    // Store checkout session info in database for verification later
    $checkoutId = $checkout['id'] ?? null;
    $checkoutUrl = $checkout['attributes']['checkout_url'] ?? null;
    
    if (!$checkoutId || !$checkoutUrl) {
        throw new Exception('Invalid checkout response from PayMongo');
    }
    
    // Store in database for reference
    $stmt = $conn->prepare("INSERT INTO paymongo_checkouts (user_id, item_id, item_type, checkout_id, amount, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("iissd", $userId, $itemId, $itemType, $checkoutId, $amount);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to store checkout session: ' . $stmt->error);
    }
    
    $stmt->close();
    
    // Return checkout URL to redirect user
    echo json_encode([
        'success' => true,
        'message' => 'Checkout session created successfully',
        'checkout_url' => $checkoutUrl,
        'checkout_id' => $checkoutId
    ]);
    
} catch (Exception $e) {
    error_log("PayMongo Initialization Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to initialize checkout: ' . $e->getMessage()
    ]);
}
?>
