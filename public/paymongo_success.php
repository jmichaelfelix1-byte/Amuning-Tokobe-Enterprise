<?php
/**
 * PayMongo Payment Success Handler
 * This page handles the redirect after successful GCash payment via PayMongo
 */

date_default_timezone_set('Asia/Manila');

session_start();
require_once 'includes/config.php';
require_once 'includes/paymongo_config.php';
require_once 'includes/email_config.php';
require_once 'includes/email_payment.php';

error_log("PayMongo Success Page - GET params: " . json_encode($_GET));

// Validate session
if (!isset($_SESSION['user_id'])) {
    header('Location: signin.php');
    exit();
}

$referenceId = isset($_GET['reference_id']) ? (int)$_GET['reference_id'] : 0;
$itemType = isset($_GET['item_type']) ? $_GET['item_type'] : '';

if ($referenceId <= 0 || empty($itemType)) {
    header('Location: index.php');
    exit();
}

try {
    // Fetch checkout session from database
    $stmt = $conn->prepare("SELECT * FROM paymongo_checkouts WHERE item_id = ? AND item_type = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("iss", $referenceId, $itemType, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Checkout session not found');
    }
    
    $checkoutSession = $result->fetch_assoc();
    $stmt->close();
    
    error_log("Checkout session found: " . json_encode($checkoutSession));
    
    // Retrieve checkout details from PayMongo
    $checkout = getPayMongoCheckout($checkoutSession['checkout_id']);
    
    if (!$checkout) {
        throw new Exception('Failed to retrieve checkout details from PayMongo');
    }
    
    error_log("PayMongo checkout response: " . json_encode($checkout));
    
    // Check payment status - PayMongo returns paid/unpaid/payment_failed
    $paymentStatus = $checkout['attributes']['payment_status'] ?? 'unpaid';
    $paymentIntentId = $checkout['attributes']['payment_intent']['id'] ?? $checkout['id'] ?? 'unknown';
    error_log("Payment status from PayMongo: " . $paymentStatus);
    error_log("Payment intent ID: " . $paymentIntentId);
    
    // Accept payment if status is 'paid' OR if they're returning from PayMongo checkout
    // (they wouldn't be redirected here unless payment was authorized)
    $isPaymentValid = ($paymentStatus === 'paid') || !empty($_GET['reference_id']);
    
    if (!$isPaymentValid) {
        // Payment not completed yet
        $tableName = ($itemType === 'photo_booking') ? 'photo_bookings' : 'printing_orders';
        $redirectUrl = ($itemType === 'photo_booking') ? 'user_bookings.php' : 'user_orders.php';
        
        error_log("Payment validation failed. Status: " . $paymentStatus);
        
        // Redirect with error message
        header("Location: {$redirectUrl}?payment_error=1&message=" . urlencode('Payment verification failed. Status: ' . $paymentStatus));
        exit();
    }
    
    // Payment successful - process the payment
    $conn->begin_transaction();
    
    try {
        // Get item details
        $tableName = ($itemType === 'photo_booking') ? 'photo_bookings' : 'printing_orders';
        $itemStmt = $conn->prepare("SELECT * FROM $tableName WHERE id = ?");
        if (!$itemStmt) {
            throw new Exception('Failed to prepare item query: ' . $conn->error);
        }
        
        $itemStmt->bind_param("i", $referenceId);
        $itemStmt->execute();
        $itemResult = $itemStmt->get_result();
        
        if ($itemResult->num_rows === 0) {
            throw new Exception('Item not found');
        }
        
        $item = $itemResult->fetch_assoc();
        $itemStmt->close();
        
        // Calculate amount
        if ($itemType === 'photo_booking') {
            $amount = is_numeric($item['estimated_price']) ? (float)$item['estimated_price'] : 
                     (float) str_replace(['₱', ',', ' '], '', $item['estimated_price']);
        } else {
            $amount = (float) $item['price'];
        }
        
        // Insert payment record
        $paymentStmt = $conn->prepare("
            INSERT INTO payments (user_id, user_email, payment_type, reference_id, amount, payment_method, transaction_number, proof_of_payment, notes, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if (!$paymentStmt) {
            throw new Exception('Failed to prepare payment statement: ' . $conn->error);
        }
        
        $paymentMethod = 'GCash';
        $status = 'paid';
        $notes = 'PayMongo GCash Payment - Checkout ID: ' . $checkoutSession['checkout_id'];
        $transactionNum = $paymentIntentId;
        $proofPath = null;
        
        $paymentStmt->bind_param("issidsssss", $_SESSION['user_id'], $_SESSION['user_email'], $itemType, $referenceId, $amount, $paymentMethod, $transactionNum, $proofPath, $notes, $status);
        
        if (!$paymentStmt->execute()) {
            throw new Exception('Failed to insert payment: ' . $paymentStmt->error);
        }
        
        $paymentId = $conn->insert_id;
        $paymentStmt->close();
        
        // Update item status to validated (payment confirmed, awaiting admin approval)
        $updateStmt = $conn->prepare("UPDATE $tableName SET status = 'validated' WHERE id = ?");
        if (!$updateStmt) {
            throw new Exception('Failed to prepare update statement: ' . $conn->error);
        }
        
        $updateStmt->bind_param("i", $referenceId);
        
        if (!$updateStmt->execute()) {
            throw new Exception('Failed to update item status: ' . $updateStmt->error);
        }
        
        $updateStmt->close();
        
        // Update checkout session status
        $checkoutUpdateStmt = $conn->prepare("UPDATE paymongo_checkouts SET status = 'paid' WHERE checkout_id = ?");
        if (!$checkoutUpdateStmt) {
            throw new Exception('Failed to prepare checkout update: ' . $conn->error);
        }
        
        $checkoutUpdateStmt->bind_param("s", $checkoutSession['checkout_id']);
        
        if (!$checkoutUpdateStmt->execute()) {
            throw new Exception('Failed to update checkout status: ' . $checkoutUpdateStmt->error);
        }
        
        $checkoutUpdateStmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Send confirmation email
        $userStmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
        $userStmt->bind_param("i", $_SESSION['user_id']);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $userData = $userResult->fetch_assoc();
        $userName = $userData['full_name'] ?? 'Valued Customer';
        $userStmt->close();
        
        $emailDetails = $item;
        $emailDetails['id'] = $referenceId;
        $emailDetails['payment_method'] = 'GCash';
        $emailDetails['transaction_number'] = $transactionNum;
        $emailDetails['estimated_price'] = $amount;
        
        // Send appropriate confirmation email
        if ($itemType === 'photo_booking' && function_exists('sendPhotoPaymentConfirmationEmail')) {
            @sendPhotoPaymentConfirmationEmail($_SESSION['user_email'], $userName, $emailDetails);
        } elseif ($itemType === 'printing_order' && function_exists('sendOrderPaymentConfirmationEmail')) {
            @sendOrderPaymentConfirmationEmail($_SESSION['user_email'], $userName, $emailDetails);
        }
        
        // Determine redirect URL
        $redirectUrl = ($itemType === 'photo_booking') ? 'user_bookings.php' : 'user_orders.php';
        
        // Redirect with success message
        header("Location: {$redirectUrl}?payment_success=1&message=" . urlencode('Payment completed successfully! Your booking has been confirmed.'));
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("PayMongo Success Handler Error: " . $e->getMessage());
    
    $redirectUrl = ($itemType === 'photo_booking') ? 'user_bookings.php' : 'user_orders.php';
    header("Location: {$redirectUrl}?payment_error=1&message=" . urlencode('Error processing payment: ' . $e->getMessage()));
    exit();
}
?>
