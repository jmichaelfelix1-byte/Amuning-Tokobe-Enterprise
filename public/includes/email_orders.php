<?php
/**
 * Order-related Email Functions
 * Handles printing order confirmations and related communications
 */

require_once 'email_config.php';
require_once 'email_templates.php';

/**
 * Send order confirmation email for printing services
 */
function sendOrderConfirmationEmail($userEmail, $userName, $orderDetails) {
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Order Confirmation - Amuning Tokobe Enterprise';

        // Email template
        $mail->Body = getOrderConfirmationTemplate($userName, $orderDetails);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        // Log the detailed error for debugging
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);

        // Try fallback method with regular PHP mail() as last resort
        try {
            $subject = 'Order Confirmation - Amuning Tokobe Enterprise';
            $headers = 'From: Amuning Tokobe Enterprise <noreply@amuning.com>' . "\r\n";
            $headers .= 'Reply-To: info@amuning.com' . "\r\n";
            $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

            $simpleMessage = "
            <html>
            <body>
                <h2>Order Confirmation</h2>
                <p>Hi {$userName},</p>
                <p>Your printing order has been received and is being processed.</p>
                <p>Service: {$orderDetails['service']}</p>
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
 * Send order completion notification
 */
function sendOrderCompletionEmail($userEmail, $userName, $orderDetails) {
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Order Ready for Pickup - Amuning Tokobe Enterprise';

        // Email template
        $mail->Body = getOrderCompletionTemplate($userName, $orderDetails);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return ['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()];
    }
}

/**
 * Send order processing notification
 */
function sendOrderProcessingEmail($userEmail, $userName, $orderDetails) {
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Order Now Processing - Amuning Tokobe Enterprise';

        // Email template
        $mail->Body = getOrderProcessingTemplate($userName, $orderDetails);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        // Log the detailed error for debugging
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);

        // Try fallback method with regular PHP mail() as last resort
        try {
            $subject = 'Order Now Processing - Amuning Tokobe Enterprise';
            $headers = 'From: Amuning Tokobe Enterprise <noreply@amuning.com>' . "\r\n";
            $headers .= 'Reply-To: info@amuning.com' . "\r\n";
            $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

            $simpleMessage = "
            <html>
            <body>
                <h2>Order Now Processing</h2>
                <p>Hi {$userName},</p>
                <p>Your printing order has been approved and is now being processed.</p>
                <p>Service: {$orderDetails['service']}</p>
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
