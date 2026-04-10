<?php
/**
 * PayMongo Webhook Handler
 * Processes webhook events from PayMongo for payment confirmations
 */

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

require_once '../includes/config.php';
require_once '../includes/paymongo_config.php';
require_once '../includes/email_config.php';
require_once '../includes/email_payment.php';

// Get raw request body
$rawBody = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PAYMONGO_SIGNATURE'] ?? '';

if (empty($signature)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing signature']);
    exit();
}

// Verify webhook signature
if (!verifyPayMongoWebhookSignature($rawBody, $signature)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid signature']);
    exit();
}

try {
    $payload = json_decode($rawBody, true);
    
    if (!$payload || !isset($payload['data'])) {
        throw new Exception('Invalid payload');
    }
    
    $event = $payload['data'];
    $eventType = $event['attributes']['type'] ?? null;
    $eventData = $event['attributes']['data'] ?? [];
    
    // Log webhook event
    error_log("PayMongo Webhook: " . $eventType . " - " . json_encode($event));
    
    // Process different event types
    switch ($eventType) {
        case 'payment.paid':
            handlePaymentPaid($eventData);
            break;
            
        case 'payment.failed':
            handlePaymentFailed($eventData);
            break;
            
        case 'checkout.session.expired':
            handleCheckoutExpired($eventData);
            break;
            
        default:
            error_log("Unknown webhook event type: " . $eventType);
    }
    
    // Return success response
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Webhook processed']);
    
} catch (Exception $e) {
    error_log("PayMongo Webhook Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Handle payment.paid webhook event
 */
function handlePaymentPaid($eventData) {
    global $conn;
    
    try {
        $paymentId = $eventData['id'] ?? null;
        $paymentAttributes = $eventData['attributes'] ?? [];
        $amount = ($paymentAttributes['amount'] ?? 0) / 100; // Convert from cents
        $status = $paymentAttributes['status'] ?? null;
        
        if (!$paymentId || $status !== 'paid') {
            throw new Exception('Invalid payment data');
        }
        
        // Find checkout session with this payment intention
        $checkoutStmt = $conn->prepare("SELECT * FROM paymongo_checkouts WHERE status = 'pending' ORDER BY created_at DESC LIMIT 10");
        if (!$checkoutStmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $checkoutStmt->execute();
        $checkoutResult = $checkoutStmt->get_result();
        $checkoutFound = false;
        
        while ($checkout = $checkoutResult->fetch_assoc()) {
            // Verify checkout matches amount
            if (abs($checkout['amount'] - $amount) < 0.01) {
                $checkoutFound = true;
                break;
            }
        }
        
        $checkoutStmt->close();
        
        if (!$checkoutFound) {
            throw new Exception('Matching checkout not found');
        }
        
        $itemId = $checkout['item_id'];
        $itemType = $checkout['item_type'];
        $userId = $checkout['user_id'];
        
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Fetch item details
            $tableName = ($itemType === 'photo_booking') ? 'photo_bookings' : 'printing_orders';
            $itemStmt = $conn->prepare("SELECT * FROM $tableName WHERE id = ?");
            if (!$itemStmt) {
                throw new Exception('Failed to prepare item query');
            }
            
            $itemStmt->bind_param("i", $itemId);
            $itemStmt->execute();
            $itemResult = $itemStmt->get_result();
            
            if ($itemResult->num_rows === 0) {
                throw new Exception('Item not found');
            }
            
            $item = $itemResult->fetch_assoc();
            $itemStmt->close();
            
            // Fetch user email
            $userStmt = $conn->prepare("SELECT email, full_name FROM users WHERE id = ?");
            if (!$userStmt) {
                throw new Exception('Failed to prepare user query');
            }
            
            $userStmt->bind_param("i", $userId);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            
            if ($userResult->num_rows === 0) {
                throw new Exception('User not found');
            }
            
            $userData = $userResult->fetch_assoc();
            $userEmail = $userData['email'];
            $userName = $userData['full_name'];
            $userStmt->close();
            
            // Insert payment record
            $paymentStmt = $conn->prepare("
                INSERT INTO payments (user_id, user_email, payment_type, reference_id, amount, payment_method, transaction_number, proof_of_payment, notes, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'GCash', ?, NULL, ?, 'paid', NOW())
            ");
            
            if (!$paymentStmt) {
                throw new Exception('Failed to prepare payment statement');
            }
            
            $notes = 'PayMongo GCash Payment - Event: ' . $paymentId;
            $paymentStmt->bind_param("isisds", $userId, $userEmail, $itemType, $itemId, $amount, $paymentId, $notes);
            
            if (!$paymentStmt->execute()) {
                throw new Exception('Failed to insert payment record');
            }
            
            $dbPaymentId = $conn->insert_id;
            $paymentStmt->close();
            
            // Update item status
            $updateStmt = $conn->prepare("UPDATE $tableName SET status = 'validated' WHERE id = ?");
            if (!$updateStmt) {
                throw new Exception('Failed to prepare update statement');
            }
            
            $updateStmt->bind_param("i", $itemId);
            
            if (!$updateStmt->execute()) {
                throw new Exception('Failed to update item status');
            }
            
            $updateStmt->close();
            
            // Update checkout status
            $checkoutUpdateStmt = $conn->prepare("UPDATE paymongo_checkouts SET status = 'paid', payment_id = ? WHERE checkout_id = ?");
            if (!$checkoutUpdateStmt) {
                throw new Exception('Failed to prepare checkout update');
            }
            
            $checkoutUpdateStmt->bind_param("is", $dbPaymentId, $checkout['checkout_id']);
            
            if (!$checkoutUpdateStmt->execute()) {
                throw new Exception('Failed to update checkout status');
            }
            
            $checkoutUpdateStmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Send confirmation email
            $emailDetails = $item;
            $emailDetails['id'] = $itemId;
            $emailDetails['payment_method'] = 'GCash';
            $emailDetails['transaction_number'] = $paymentId;
            $emailDetails['estimated_price'] = $amount;
            
            if ($itemType === 'photo_booking' && function_exists('sendPhotoPaymentConfirmationEmail')) {
                @sendPhotoPaymentConfirmationEmail($userEmail, $userName, $emailDetails);
            } elseif ($itemType === 'printing_order' && function_exists('sendOrderPaymentConfirmationEmail')) {
                @sendOrderPaymentConfirmationEmail($userEmail, $userName, $emailDetails);
            }
            
            error_log("Payment confirmed via webhook - Payment ID: " . $dbPaymentId);
            
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Payment.paid webhook error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Handle payment.failed webhook event
 */
function handlePaymentFailed($eventData) {
    global $conn;
    
    try {
        $paymentId = $eventData['id'] ?? null;
        $failureMessage = $eventData['attributes']['failure_message'] ?? 'Unknown error';
        
        error_log("Payment failed - ID: " . $paymentId . " - Message: " . $failureMessage);
        
        // Find and update related checkout
        $checkoutStmt = $conn->prepare("UPDATE paymongo_checkouts SET status = 'failed', notes = ? WHERE payment_intent_id = ?");
        if ($checkoutStmt) {
            $checkoutStmt->bind_param("ss", $failureMessage, $paymentId);
            $checkoutStmt->execute();
            $checkoutStmt->close();
        }
        
    } catch (Exception $e) {
        error_log("Payment.failed webhook error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Handle checkout.session.expired webhook event
 */
function handleCheckoutExpired($eventData) {
    global $conn;
    
    try {
        $checkoutId = $eventData['id'] ?? null;
        
        error_log("Checkout expired - ID: " . $checkoutId);
        
        // Update checkout status
        $checkoutStmt = $conn->prepare("UPDATE paymongo_checkouts SET status = 'expired' WHERE checkout_id = ?");
        if ($checkoutStmt) {
            $checkoutStmt->bind_param("s", $checkoutId);
            $checkoutStmt->execute();
            $checkoutStmt->close();
        }
        
    } catch (Exception $e) {
        error_log("Checkout.expired webhook error: " . $e->getMessage());
        throw $e;
    }
}
?>
