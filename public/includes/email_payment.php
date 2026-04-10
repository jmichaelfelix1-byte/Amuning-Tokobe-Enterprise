<?php
/**
 * Payment-related Email Functions
 * Handles payment confirmation, pending review, and approval emails
 */

require_once 'email_config.php';
require_once 'email_templates.php';
require_once 'pdf_generator.php';

/**
 * Send payment pending review email for photo bookings
 */
function sendPhotoPaymentPendingEmail($userEmail, $userName, $bookingDetails) {
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Payment Submitted - Photo Booking Pending Review';

        // Email template
        $mail->Body = getPhotoPaymentPendingTemplate($userName, $bookingDetails);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        // Log the detailed error for debugging
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);

        // Try fallback method with regular PHP mail() as last resort
        try {
            $subject = 'Payment Submitted - Photo Booking Pending Review';
            $headers = 'From: Amuning Tokobe Enterprise <noreply@amuning.com>' . "\r\n";
            $headers .= 'Reply-To: info@amuning.com' . "\r\n";
            $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

            $simpleMessage = "
            <html>
            <body>
                <h2>Payment Submitted</h2>
                <p>Hi {$userName},</p>
                <p>Your payment for photo booking has been submitted and is pending review.</p>
                <p>Booking ID: {$bookingDetails['id']}</p>
                <p>Thank you for choosing Amuning Tokobe Enterprise!</p>
            </body>
            </html>";

            if (mail($userEmail, $subject, $simpleMessage, $headers)) {
                return ['success' => true, 'message' => 'Email sent via fallback method'];
            }
        } catch (Exception $fallbackError) {
            error_log('Fallback mail error: ' . $fallbackError->getMessage());
        }

        return ['success' => false, 'message' => 'Email sending failed. Please contact support if this persists.'];
    }
}

/**
 * Send payment approval notification
 */
function sendPaymentApprovalEmail($userEmail, $userName, $paymentDetails) {
    require_once 'pdf_generator.php';
    
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Payment Approved - Ready for Processing!';

        // Email template
        $mail->Body = getPaymentApprovalTemplate($userName, $paymentDetails);

        // Generate and attach PDF receipt if we have order details
        $pdfPath = false;
        if (!empty($paymentDetails['reference']) || !empty($paymentDetails['reference_id'])) {
            // Determine order type and fetch full order details
            $orderType = isset($paymentDetails['service_type']) ? 'printing_order' : 'photo_booking';
            $orderId = $paymentDetails['reference'] ?? $paymentDetails['reference_id'];
            
            // Fetch complete order details from database
            global $conn;
            $tableName = ($orderType === 'printing_order') ? 'printing_orders' : 'photo_bookings';
            $stmt = $conn->prepare("SELECT * FROM $tableName WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $orderId);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $orderData = $result->fetch_assoc();
                    // Add payment and user info to order data
                    $orderData['user_name'] = $userName;
                    $orderData['user_email'] = $userEmail;
                    $orderData['payment_method'] = $paymentDetails['payment_method'] ?? 'Bank Transfer';
                    $orderData['amount'] = $paymentDetails['amount'] ?? $paymentDetails['estimated_price'] ?? 0;
                    
                    // Generate PDF
                    $pdfPath = generateOrderReceiptPDF($orderData, $orderType);
                }
                $stmt->close();
            }
        }

        // Attach PDF if successfully generated
        if ($pdfPath && file_exists($pdfPath)) {
            $mail->addAttachment($pdfPath, 'Order_Receipt_' . ($paymentDetails['reference'] ?? date('Y-m-d-His')) . '.pdf');
        }

        $mail->send();
        
        $result = ['success' => true, 'message' => 'Email sent successfully', 'pdf_path' => $pdfPath];
        return $result;
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return ['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()];
    }
}

/**
 * Send payment rejection notification
 */
function sendPaymentRejectionEmail($userEmail, $userName, $paymentDetails, $reason = '') {
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Payment Review - Additional Information Required';

        // Email template
        $mail->Body = getPaymentRejectionTemplate($userName, $paymentDetails, $reason);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return ['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()];
    }
}

/**
 * Send payment processing notification email
 */
function sendPaymentProcessingEmail($userEmail, $userName, $paymentDetails) {
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Payment Processing - Now Processing Your Order!';

        // Email template
        $mail->Body = getPaymentProcessingTemplate($userName, $paymentDetails);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        // Log the detailed error for debugging
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);

        // Try fallback method with regular PHP mail() as last resort
        try {
            $subject = 'Payment Processing - Now Processing Your Order!';
            $headers = 'From: Amuning Tokobe Enterprise <noreply@amuning.com>' . "\r\n";
            $headers .= 'Reply-To: info@amuning.com' . "\r\n";
            $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

            $simpleMessage = "
            <html>
            <body>
                <h2>Payment Now Processing</h2>
                <p>Hi {$userName},</p>
                <p>Great news! Your payment has been approved and is now being processed.</p>
                <p>Payment ID: {$paymentDetails['id']}</p>
                <p>Service Type: {$paymentDetails['service_type']}</p>
                <p>Thank you for choosing Amuning Tokobe Enterprise!</p>
            </body>
            </html>";

            if (mail($userEmail, $subject, $simpleMessage, $headers)) {
                return ['success' => true, 'message' => 'Email sent via fallback method'];
            }
        } catch (Exception $fallbackError) {
            error_log('Fallback mail error: ' . $fallbackError->getMessage());
        }

        return ['success' => false, 'message' => 'Email sending failed. Please contact support if this persists.'];
    }
}

/**
 * Send payment pending review email for printing orders
 */
function sendOrderPaymentPendingEmail($userEmail, $userName, $orderDetails) {
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Payment Submitted - Printing Order Pending Review';

        // Email template
        $mail->Body = getOrderPaymentPendingTemplate($userName, $orderDetails);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        // Log the detailed error for debugging
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);

        // Try fallback method with regular PHP mail() as last resort
        try {
            $subject = 'Payment Submitted - Printing Order Pending Review';
            $headers = 'From: Amuning Tokobe Enterprise <noreply@amuning.com>' . "\r\n";
            $headers .= 'Reply-To: info@amuning.com' . "\r\n";
            $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

            $simpleMessage = "
            <html>
            <body>
                <h2>Payment Submitted</h2>
                <p>Hi {$userName},</p>
                <p>Your payment for printing order has been submitted and is pending review.</p>
                <p>Order ID: {$orderDetails['id']}</p>
                <p>Thank you for choosing Amuning Tokobe Enterprise!</p>
            </body>
            </html>";

            if (mail($userEmail, $subject, $simpleMessage, $headers)) {
                return ['success' => true, 'message' => 'Email sent via fallback method'];
            }
        } catch (Exception $fallbackError) {
            error_log('Fallback mail error: ' . $fallbackError->getMessage());
        }

        return ['success' => false, 'message' => 'Email sending failed. Please contact support if this persists.'];
    }
}

/**
 * Send order ready for pickup notification email
 */
function sendOrderReadyPickupEmail($userEmail, $userName, $orderDetails) {
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Prepare order details for PDF - map field names to what PDF generator expects
        $pdfOrderDetails = $orderDetails;
        
        // Ensure we have all required fields mapped
        if (!isset($pdfOrderDetails['user_email']) && isset($pdfOrderDetails['email'])) {
            $pdfOrderDetails['user_email'] = $pdfOrderDetails['email'];
        } else if (!isset($pdfOrderDetails['user_email'])) {
            $pdfOrderDetails['user_email'] = $userEmail;
        }
        
        if (!isset($pdfOrderDetails['full_name']) && !isset($pdfOrderDetails['user_name'])) {
            $pdfOrderDetails['full_name'] = $userName;
        }
        
        // Ensure created_at exists
        if (!isset($pdfOrderDetails['created_at']) && isset($pdfOrderDetails['order_date'])) {
            $pdfOrderDetails['created_at'] = $pdfOrderDetails['order_date'];
        }
        
        // Generate PDF receipt
        $pdfPath = false;
        if (!empty($pdfOrderDetails['id'])) {
            $pdfPath = generateOrderReceiptPDF($pdfOrderDetails, 'printing_order');
        }

        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Order Ready for Pickup - Amuning Tokobe Enterprise';

        // Email template
        $mail->Body = getOrderReadyPickupTemplate($userName, $orderDetails);

        // Attach PDF if successfully generated
        if ($pdfPath && file_exists($pdfPath)) {
            $mail->addAttachment($pdfPath, 'Order_Receipt_' . ($orderDetails['id'] ?? date('Y-m-d-His')) . '.pdf');
        }

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully', 'pdf_path' => $pdfPath];
    } catch (Exception $e) {
        // Log the detailed error for debugging
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);

        // Try fallback method with regular PHP mail() as last resort
        try {
            $subject = 'Order Ready for Pickup - Amuning Tokobe Enterprise';
            $headers = 'From: Amuning Tokobe Enterprise <noreply@amuning.com>' . "\r\n";
            $headers .= 'Reply-To: info@amuning.com' . "\r\n";
            $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

            $simpleMessage = "
            <html>
            <body>
                <h2>Order Ready for Pickup</h2>
                <p>Hi {$userName},</p>
                <p>Great news! Your printing order has been completed and is ready for pickup.</p>
                <p>Order ID: {$orderDetails['id']}</p>
                <p>Service: {$orderDetails['service']}</p>
                <p>Please bring valid ID and this email when picking up your order.</p>
                <p>Thank you for choosing Amuning Tokobe Enterprise!</p>
            </body>
            </html>";

            if (mail($userEmail, $subject, $simpleMessage, $headers)) {
                return ['success' => true, 'message' => 'Email sent via fallback method', 'pdf_path' => false];
            }
        } catch (Exception $fallbackError) {
            error_log('Fallback mail error: ' . $fallbackError->getMessage());
        }

        return ['success' => false, 'message' => 'Email sending failed. Please contact support if this persists.'];
    }
}

/**
 * Send booking approval notification email
 */
function sendBookingApprovalEmail($userEmail, $userName, $bookingDetails) {
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Booking Approved - Your Event Photography is Confirmed!';

        // Email template
        $mail->Body = getBookingApprovalTemplate($userName, $bookingDetails);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        // Log the detailed error for debugging
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);

        // Try fallback method with regular PHP mail() as last resort
        try {
            $subject = 'Booking Approved - Your Event Photography is Confirmed!';
            $headers = 'From: Amuning Tokobe Enterprise <noreply@amuning.com>' . "\r\n";
            $headers .= 'Reply-To: info@amuning.com' . "\r\n";
            $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

            $simpleMessage = "
            <html>
            <body>
                <h2>Booking Approved!</h2>
                <p>Hi {$userName},</p>
                <p>Great news! Your photography booking has been approved and confirmed.</p>
                <p>Event: {$bookingDetails['event_type']}</p>
                <p>Date: {$bookingDetails['event_date']}</p>
                <p>Thank you for choosing Amuning Tokobe Enterprise!</p>
            </body>
            </html>";

            if (mail($userEmail, $subject, $simpleMessage, $headers)) {
                return ['success' => true, 'message' => 'Email sent via fallback method'];
            }
        } catch (Exception $fallbackError) {
            error_log('Fallback mail error: ' . $fallbackError->getMessage());
        }

        return ['success' => false, 'message' => 'Email sending failed. Please contact support if this persists.'];
    }
}

/**
 * Send booking rejection notification email
 */
function sendBookingRejectionEmail($userEmail, $userName, $bookingDetails, $reason = '') {
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Booking Review Required - Amuning Tokobe Enterprise';

        // Email template
        $mail->Body = getBookingRejectionTemplate($userName, $bookingDetails, $reason);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        // Log the detailed error for debugging
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);

        // Try fallback method with regular PHP mail() as last resort
        try {
            $subject = 'Booking Review Required - Amuning Tokobe Enterprise';
            $headers = 'From: Amuning Tokobe Enterprise <noreply@amuning.com>' . "\r\n";
            $headers .= 'Reply-To: info@amuning.com' . "\r\n";
            $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

            $simpleMessage = "
            <html>
            <body>
                <h2>Booking Review Required</h2>
                <p>Hi {$userName},</p>
                <p>We have reviewed your booking submission but need additional information.</p>
                <p>Booking ID: {$bookingDetails['id']}</p>
                <p>Event: {$bookingDetails['event_type']}</p>
                <p>Please contact us for corrections.</p>
                <p>Thank you for choosing Amuning Tokobe Enterprise!</p>
            </body>
            </html>";

            if (mail($userEmail, $subject, $simpleMessage, $headers)) {
                return ['success' => true, 'message' => 'Email sent via fallback method'];
            }
        } catch (Exception $fallbackError) {
            error_log('Fallback mail error: ' . $fallbackError->getMessage());
        }

        return ['success' => false, 'message' => 'Email sending failed. Please contact support if this persists.'];
    }
}
