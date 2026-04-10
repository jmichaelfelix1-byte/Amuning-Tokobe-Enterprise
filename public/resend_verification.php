<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'email_verification.php';
require_once 'includes/config.php';

$email = $_GET['email'] ?? '';

if ($email) {
    $code = rand(100000, 999999);
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 minute')); // Verification code expires in 1 minute
    
    try {
        // Update verification code in database
        $stmt = $conn->prepare("UPDATE users SET verification_code = ?, verification_code_expires_at = ? WHERE email = ?");
        $stmt->bind_param("sss", $code, $expires_at, $email);
        
        if ($stmt->execute()) {
            // Get user name for email - FIXED COLUMN NAME
            $nameStmt = $conn->prepare("SELECT full_name FROM users WHERE email = ?");
            $nameStmt->bind_param("s", $email);
            $nameStmt->execute();
            $result = $nameStmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                if (sendVerificationEmail($email, $user['full_name'], $code)) {
                    $_SESSION['success'] = 'New verification code sent to your email!';
                } else {
                    $_SESSION['error'] = 'Failed to send verification email. Please try again.';
                }
            } else {
                $_SESSION['error'] = 'User not found.';
            }
            $nameStmt->close();
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Resend verification error: " . $e->getMessage());
        $_SESSION['error'] = 'An error occurred. Please try again.';
    }
    
    header('Location: verify.php?email=' . urlencode($email));
    exit();
} else {
    header('Location: signin.php');
    exit();
}
?>