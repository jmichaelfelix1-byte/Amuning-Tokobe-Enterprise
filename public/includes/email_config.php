<?php
/**
 * Email Configuration and PHPMailer Setup
 * Contains PHPMailer initialization and shared email configuration
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer classes with error handling
$baseDir = dirname(dirname(dirname(__FILE__)));
$vendorPath = $baseDir . '/send_email/vendor/autoload.php';

if (file_exists($vendorPath)) {
    try {
        require_once $vendorPath;
        // Check if PHPMailer class exists
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            throw new Exception('PHPMailer class not found after autoload');
        }
    } catch (Exception $e) {
        // Define dummy functions if PHPMailer fails to load
        function sendPhotoPaymentPendingEmail($email, $name, $details) {
            return ['success' => false, 'message' => 'Email system unavailable: ' . $e->getMessage()];
        }
        function sendOrderConfirmationEmail($email, $name, $details) {
            return ['success' => false, 'message' => 'Email system unavailable: ' . $e->getMessage()];
        }
        function sendPhotoBookingReceivedEmail($email, $name, $details) {
            return ['success' => false, 'message' => 'Email system unavailable: ' . $e->getMessage()];
        }
    }
} else {
    // Define dummy functions if vendor path doesn't exist
    function sendPhotoPaymentPendingEmail($email, $name, $details) {
        return ['success' => false, 'message' => 'Email system not configured'];
    }
    function sendOrderConfirmationEmail($email, $name, $details) {
        return ['success' => false, 'message' => 'Email system not configured'];
    }
    function sendPhotoBookingReceivedEmail($email, $name, $details) {
        return ['success' => false, 'message' => 'Email system not configured'];
    }
}

/**
 * Create and configure PHPMailer instance
 */
function createMailer() {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return null;
    }

    $mail = new PHPMailer(true);

    // Server settings - Try SSL on port 465 (alternative to TLS on 587)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'amuningtokobeenterprise@gmail.com';
    $mail->Password   = 'dlbz cynr pkfv rpfo';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use SMTPS instead of STARTTLS
    $mail->Port       = 465; // Use port 465 instead of 587

    // Additional SSL options
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Default sender
    $mail->setFrom('amuningtokobeenterprise@gmail.com', 'Amuning Tokobe Enterprise');

    return $mail;
}
