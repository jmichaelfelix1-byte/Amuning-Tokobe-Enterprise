<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/email_payment.php';
require_once '../../includes/email_orders.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$userEmail = isset($_POST['email']) ? trim($_POST['email']) : '';
$userName = isset($_POST['name']) ? trim($_POST['name']) : '';
$paymentDetailsJson = isset($_POST['payment_details']) ? $_POST['payment_details'] : '';
$userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
$paymentMethod = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : 'online';

if (!$userEmail || !$userName || !$paymentDetailsJson || !$action) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    $paymentDetails = json_decode($paymentDetailsJson, true);
    if (!$paymentDetails) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment details']);
        exit();
    }

    // Send the appropriate email
    if ($action === 'approve') {
        $result = sendPaymentApprovalEmail($userEmail, $userName, $paymentDetails);
        
        // If email sent successfully and we have user_id, create notification with PDF
        if ($result['success'] && $userId > 0 && !empty($result['pdf_path'])) {
            createPaymentNotificationWithPDF($userId, $paymentDetails, $result['pdf_path']);
        }
    } elseif ($action === 'reject') {
        // Check if this is booking rejection (has event_type) or order rejection (has service)
        if (isset($paymentDetails['event_type']) && isset($paymentDetails['venue'])) {
            $result = sendBookingRejectionEmail($userEmail, $userName, $paymentDetails, $reason);
        } elseif (isset($paymentDetails['service']) && isset($paymentDetails['size'])) {
            // This is a printing order rejection - use order rejection template
            require_once '../../includes/email_templates.php';
            $mail = createMailer();
            if (!$mail) {
                $result = ['success' => false, 'message' => 'Email system not available'];
            } else {
                try {
                    $mail->addAddress($userEmail, $userName);
                    $mail->isHTML(true);
                    $mail->Subject = 'Order Review Required - Amuning Tokobe Enterprise';
                    $mail->Body = getOrderRejectionTemplate($userName, $paymentDetails, $reason);
                    $mail->send();
                    $result = ['success' => true, 'message' => 'Email sent successfully'];
                } catch (Exception $e) {
                    error_log('Order rejection email error: ' . $mail->ErrorInfo);
                    $result = ['success' => false, 'message' => 'Email sending failed'];
                }
            }
        } else {
            $result = sendPaymentRejectionEmail($userEmail, $userName, $paymentDetails, $reason);
        }
    } elseif ($action === 'order_placed') {
        $result = sendOrderConfirmationEmail($userEmail, $userName, $paymentDetails);
        
        if ($result['success'] && $userId > 0) {
            createOrderStatusNotification($userId, $paymentDetails, 'order_placed', 'Order Placed', 'Your printing order has been successfully placed! We will review it and notify you once validated.');
        }
    } elseif ($action === 'order_validated') {
        // Send custom email for validation
        $mail = createMailer();
        if ($mail) {
            try {
                $mail->addAddress($userEmail, $userName);
                $mail->isHTML(true);
                $mail->Subject = 'Order Validated - Ready for Processing';
                $paymentMethodCheck = $paymentDetails['payment_method'] ?? 'online';
                $paymentNote = ($paymentMethodCheck === 'in_person') 
                    ? '<p>Your order will be processed and you will pay when you pick up your order.</p>'
                    : '<p>Please proceed to payment to proceed with processing.</p>';
                $mail->Body = getOrderPaymentPendingTemplate($userName, $paymentDetails) . $paymentNote;
                $mail->send();
                $result = ['success' => true, 'message' => 'Email sent successfully'];
            } catch (Exception $e) {
                error_log('PHPMailer Error: ' . $mail->ErrorInfo);
                $result = ['success' => false, 'message' => 'Email sending failed'];
            }
        } else {
            $result = ['success' => false, 'message' => 'Email system not available'];
        }
        
        if ($result['success'] && $userId > 0) {
            createOrderStatusNotification($userId, $paymentDetails, 'order_validated', 'Order Validated', 'Your order has been validated and is ready to proceed.');
        }
    } elseif ($action === 'order_processing') {
        $result = sendOrderProcessingEmail($userEmail, $userName, $paymentDetails);
        
        if ($result['success'] && $userId > 0) {
            $paymentMethodCheck = $paymentDetails['payment_method'] ?? 'online';
            $message = ($paymentMethodCheck === 'online') 
                ? 'Your payment has been received. Your order is now being processed.'
                : 'Your order is now being processed. You will pay when you pick up your order.';
            createOrderStatusNotification($userId, $paymentDetails, 'order_processing', 'Order Processing', $message);
        }
    } elseif ($action === 'ready_for_pickup' || $action === 'ready_pickup') {
        $result = sendOrderReadyPickupEmail($userEmail, $userName, $paymentDetails);
        
        // If email sent successfully and we have user_id and PDF, create notification with PDF
        if ($result['success'] && $userId > 0 && !empty($result['pdf_path'])) {
            createOrderCompletionNotificationWithPDF($userId, $paymentDetails, $result['pdf_path']);
        }
    } elseif ($action === 'order_completed') {
        // For order completed, generate receipt PDF and send
        $mail = createMailer();
        if ($mail) {
            try {
                $pdfPath = false;
                if (!empty($paymentDetails['id'])) {
                    $pdfPath = generateOrderReceiptPDF($paymentDetails, 'printing_order');
                }
                
                $mail->addAddress($userEmail, $userName);
                $mail->isHTML(true);
                $mail->Subject = 'Order Completed - Receipt Included';
                $mail->Body = getOrderReadyPickupTemplate($userName, $paymentDetails) . 
                              "<p><strong>Receipt is attached to this email.</strong></p>";
                
                if ($pdfPath && file_exists($pdfPath)) {
                    $mail->addAttachment($pdfPath, 'Order_Receipt_' . ($paymentDetails['id'] ?? date('Y-m-d-His')) . '.pdf');
                }
                
                $mail->send();
                $result = ['success' => true, 'message' => 'Email sent successfully', 'pdf_path' => $pdfPath];
                
                if ($userId > 0 && !empty($pdfPath)) {
                    createOrderCompletionNotificationWithPDF($userId, $paymentDetails, $pdfPath);
                }
            } catch (Exception $e) {
                error_log('PHPMailer Error: ' . $mail->ErrorInfo);
                $result = ['success' => false, 'message' => 'Email sending failed'];
            }
        } else {
            $result = ['success' => false, 'message' => 'Email system not available'];
        }
    } elseif ($action === 'booking_approved') {
        $result = sendBookingApprovalEmail($userEmail, $userName, $paymentDetails);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit();
    }

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * Create a notification with PDF attachment for payment approval
 */
function createPaymentNotificationWithPDF($userId, $paymentDetails, $pdfPath) {
    global $conn;
    
    try {
        // Determine order type and order ID
        $orderType = isset($paymentDetails['service']) ? 'printing_order' : 'photo_booking';
        $orderId = $paymentDetails['reference'] ?? $paymentDetails['reference_id'] ?? 0;
        
        if ($orderId <= 0) {
            return false;
        }
        
        $title = 'Payment Approved - Order Receipt';
        $message = 'Your payment has been approved! Your order receipt is attached.';
        $notificationType = 'payment_approved';
        
        // Insert notification with PDF attachment
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (user_id, order_type, order_id, notification_type, title, message, attachment_path, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, FALSE, NOW())
        ");
        
        if (!$stmt) {
            error_log('Notification insert preparation failed: ' . $conn->error);
            return false;
        }
        
        $stmt->bind_param(
            "iisssss",
            $userId,
            $orderType,
            $orderId,
            $notificationType,
            $title,
            $message,
            $pdfPath
        );
        
        if (!$stmt->execute()) {
            error_log('Notification insert execution failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $stmt->close();
        return true;
    } catch (Exception $e) {
        error_log('Error creating payment notification: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create a notification with PDF attachment for order completion
 */
function createOrderCompletionNotificationWithPDF($userId, $orderDetails, $pdfPath) {
    global $conn;
    
    try {
        // Determine order type and order ID
        $orderType = isset($orderDetails['service']) ? 'printing_order' : 'photo_booking';
        $orderId = $orderDetails['id'] ?? $orderDetails['reference'] ?? 0;
        
        if ($orderId <= 0) {
            return false;
        }
        
        $title = 'Order Complete';
        $message = 'Your order has been completed and is ready for pickup!';
        $notificationType = 'order_completed';
        $oldStatus = 'Pending';  // Orders start as pending
        $newStatus = 'Completed';
        
        // Insert notification with PDF attachment AND status information
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (user_id, order_type, order_id, notification_type, title, message, old_status, new_status, attachment_path, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, FALSE, NOW())
        ");
        
        if (!$stmt) {
            error_log('Order completion notification insert preparation failed: ' . $conn->error);
            return false;
        }
        
        $stmt->bind_param(
            "iisssssss",
            $userId,
            $orderType,
            $orderId,
            $notificationType,
            $title,
            $message,
            $oldStatus,
            $newStatus,
            $pdfPath
        );
        
        if (!$stmt->execute()) {
            error_log('Order completion notification insert execution failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $stmt->close();
        return true;
    } catch (Exception $e) {
        error_log('Error creating order completion notification: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create a notification for order status changes
 */
function createOrderStatusNotification($userId, $orderDetails, $notificationType, $title, $message) {
    global $conn;
    
    try {
        // Determine order type and order ID
        $orderType = isset($orderDetails['service']) ? 'printing_order' : 'photo_booking';
        $orderId = $orderDetails['id'] ?? $orderDetails['reference'] ?? 0;
        
        if ($orderId <= 0) {
            return false;
        }
        
        // Insert notification
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (user_id, order_type, order_id, notification_type, title, message, is_read, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, FALSE, NOW())
        ");
        
        if (!$stmt) {
            error_log('Order status notification insert preparation failed: ' . $conn->error);
            return false;
        }
        
        $stmt->bind_param(
            "iissss",
            $userId,
            $orderType,
            $orderId,
            $notificationType,
            $title,
            $message
        );
        
        if (!$stmt->execute()) {
            error_log('Order status notification insert execution failed: ' . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $stmt->close();
        return true;
    } catch (Exception $e) {
        error_log('Error creating order status notification: ' . $e->getMessage());
        return false;
    }
}
?>

