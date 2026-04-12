<?php
/**
 * Email Configuration and PHPMailer Setup
 * Credentials loaded from environment variables
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load environment variables
if (file_exists(dirname(dirname(dirname(__FILE__))) . '/.env')) {
    $env = parse_ini_file(dirname(dirname(dirname(__FILE__))) . '/.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

// Include PHPMailer classes with error handling
$baseDir = dirname(dirname(dirname(__FILE__)));
$vendorPath = $baseDir . '/send_email/vendor/autoload.php';

if (file_exists($vendorPath)) {
    try {
        require_once $vendorPath;
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            throw new Exception('PHPMailer class not found after autoload');
        }
    } catch (Exception $e) {
        // PHPMailer loading failed - define stub functions
        if (!function_exists('sendPhotoPaymentPendingEmail')) {
            function sendPhotoPaymentPendingEmail($email, $name, $details) {
                return ['success' => false, 'message' => 'Email system unavailable'];
            }
        }
        if (!function_exists('sendOrderConfirmationEmail')) {
            function sendOrderConfirmationEmail($email, $name, $details) {
                return ['success' => false, 'message' => 'Email system unavailable'];
            }
        }
        if (!function_exists('sendPhotoBookingReceivedEmail')) {
            function sendPhotoBookingReceivedEmail($email, $name, $details) {
                return ['success' => false, 'message' => 'Email system unavailable'];
            }
        }
    }
} else {
    // PHPMailer path doesn't exist - define stub functions
    if (!function_exists('sendPhotoPaymentPendingEmail')) {
        function sendPhotoPaymentPendingEmail($email, $name, $details) {
            return ['success' => false, 'message' => 'Email system not configured'];
        }
    }
    if (!function_exists('sendOrderConfirmationEmail')) {
        function sendOrderConfirmationEmail($email, $name, $details) {
            return ['success' => false, 'message' => 'Email system not configured'];
        }
    }
    if (!function_exists('sendPhotoBookingReceivedEmail')) {
        function sendPhotoBookingReceivedEmail($email, $name, $details) {
            return ['success' => false, 'message' => 'Email system not configured'];
        }
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

    // Get credentials from environment variables
    $gmailUser = getenv('GMAIL_USERNAME');
    $gmailPass = getenv('GMAIL_PASSWORD');

    if (!$gmailUser || !$gmailPass) {
        error_log("Error: Gmail credentials not configured in environment variables");
        return null;
    }

    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $gmailUser;
    $mail->Password   = $gmailPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->Timeout    = 5;

    // SSL options
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer'       => true,
            'verify_peer_name'  => true,
            'allow_self_signed' => false
        )
    );

    // Default sender
    $mail->setFrom($gmailUser, 'Amuning Tokobe Enterprise');

    return $mail;
}
?>
