<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../send_email/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../send_email/vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../send_email/vendor/phpmailer/phpmailer/src/Exception.php';

/**
 * Send Email Verification Code
 */
function sendVerificationEmail($email, $name, $code) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'amuningtokobeenterprise@gmail.com';
        $mail->Password = 'qavj rnzc edez ilqh'; // Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('amuningtokobeenterprise@gmail.com', 'Amuning Tokobe Enterprise');
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Verify your Amuning Account';
        $mail->Body = "
            <div style='font-family: Poppins, sans-serif; background: #f8f9fa; padding: 30px;'>
                <div style='max-width: 600px; margin: auto; background: white; border-radius: 12px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);'>
                    <h2 style='color: #e91e63; text-align:center;'>🔐 Email Verification</h2>
                    <p>Hi <strong>$name</strong>,</p>
                    <p>Thank you for signing up with <strong>Amuning Tokobe Enterprise</strong>! Please verify your email by entering this code on the verification page:</p>
                    <div style='font-size: 24px; font-weight: bold; text-align:center; background:#fce4ec; color:#e91e63; padding:10px; border-radius:8px;'>
                        $code
                    </div>
                    <p style='margin-top:20px;'>This code will expire in 1 minute.</p>
                    <p>Thanks,<br><strong>Amuning Tokobe Team</strong></p>
                </div>
            </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Verification email failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send General Email (e.g., Forgot Password)
 */
function sendGeneralEmail($to, $name, $subject, $bodyHtml) {
    $mail = new PHPMailer(true); // ✅ fixed line

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'amuningtokobeenterprise@gmail.com';
        $mail->Password = 'qavj rnzc edez ilqh';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('amuningtokobeenterprise@gmail.com', 'Amuning Tokobe Enterprise');
        $mail->addAddress($to, $name);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $bodyHtml;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: " . $mail->ErrorInfo);
        return false;
    }
}
?>