<?php
/**
 * Booking-related Email Functions
 * Handles photo booking confirmations and related communications
 */

require_once 'email_config.php';
require_once 'email_templates.php';

/**
 * Send photo booking confirmation email
 */
function sendPhotoBookingReceivedEmail($userEmail, $userName, $bookingDetails) {
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Photo Booking Received - Next Steps | Amuning Tokobe Enterprise';

        // Email template
        $mail->Body = getPhotoBookingReceivedTemplate($userName, $bookingDetails);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        // Log the detailed error for debugging
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);

        // Try fallback method with regular PHP mail() as last resort
        try {
            $subject = 'Photo Booking Received - Next Steps | Amuning Tokobe Enterprise';
            $headers = 'From: Amuning Tokobe Enterprise <noreply@amuning.com>' . "\r\n";
            $headers .= 'Reply-To: info@amuning.com' . "\r\n";
            $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

            $simpleMessage = "
            <html>
            <body>
                <h2>Photo Booking Received</h2>
                <p>Hi {$userName},</p>
                <p>Your photo booking has been received and is being processed.</p>
                <p>Event Type: {$bookingDetails['event_type']}</p>
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
 * Send booking reminder email (day before event)
 */
function sendBookingReminderEmail($userEmail, $userName, $bookingDetails) {
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Photography Session Reminder - Tomorrow!';

        // Email template
        $mail->Body = getBookingReminderTemplate($userName, $bookingDetails);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return ['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()];
    }
}

/**
 * Send booking processing notification
 */
function sendBookingProcessingEmail($userEmail, $userName, $bookingDetails) {
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Booking Now Processing - Amuning Tokobe Enterprise';

        // Email template
        $mail->Body = getBookingProcessingTemplate($userName, $bookingDetails);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        // Log the detailed error for debugging
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);

        // Try fallback method with regular PHP mail() as last resort
        try {
            $subject = 'Booking Now Processing - Amuning Tokobe Enterprise';
            $headers = 'From: Amuning Tokobe Enterprise <noreply@amuning.com>' . "\r\n";
            $headers .= 'Reply-To: info@amuning.com' . "\r\n";
            $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";

            $simpleMessage = "
            <html>
            <body>
                <h2>Booking Now Processing</h2>
                <p>Hi {$userName},</p>
                <p>Your photo booking has been approved and is now being processed.</p>
                <p>Event Type: {$bookingDetails['event_type']}</p>
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
 * Send booking cancellation notification
 */
function sendBookingCancellationEmail($userEmail, $userName, $bookingDetails, $reason = '') {
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Booking Cancellation Notice';

        // Email template
        $mail->Body = getBookingCancellationTemplate($userName, $bookingDetails, $reason);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return ['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()];
    }
}

/**
 * Send booking validated notification email
 */
function sendPhotoBookingValidatedEmail($userEmail, $userName, $bookingDetails) {
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Photo Booking Validated - Ready for Payment';

        // Email template
        $mail->Body = getPhotoBookingValidatedTemplate($userName, $bookingDetails);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return ['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()];
    }
}

/**
 * Send booking completed confirmation email
 */
function sendPhotoBookingCompletedEmail($userEmail, $userName, $bookingDetails) {
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Photo Booking Completed - Thank You! | Amuning Tokobe Enterprise';

        // Email template
        $mail->Body = getPhotoBookingCompletedTemplate($userName, $bookingDetails);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return ['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()];
    }
}

/**
 * Send booking confirmed email (payment received)
 */
function sendPhotoBookingBookedEmail($userEmail, $userName, $bookingDetails) {
    $mail = createMailer();

    if (!$mail) {
        return ['success' => false, 'message' => 'Email system not available'];
    }

    try {
        // Recipients
        $mail->addAddress($userEmail, $userName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Photo Booking Confirmed - We Are Ready!';

        // Email template
        $mail->Body = getPhotoBookingBookedTemplate($userName, $bookingDetails);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return ['success' => false, 'message' => 'Email sending failed: ' . $e->getMessage()];
    }
}
