<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'email_verification.php'; // You already use PHPMailer here

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);

    // Check if user exists
    $stmt = $conn->prepare("SELECT id, full_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(16)); // Generate secure reset token
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Store token and expiry in database
        $update = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE email=?");
        $update->bind_param("sss", $token, $expiry, $email);
        $update->execute();

        // Send reset link via email
        $reset_link = "http://localhost/Amuning/public/reset_password.php?token=" . urlencode($token);
        $subject = "Password Reset Request";
        $body = "
            <p>Hello {$user['full_name']},</p>
            <p>You requested to reset your password. Click the link below to reset it:</p>
            <p><a href='$reset_link'>$reset_link</a></p>
            <p>This link will expire in 1 hour.</p>
        ";

        if (sendGeneralEmail($email, $user['full_name'], $subject, $body)) {
            $_SESSION['success'] = "Password reset link has been sent to your email.";
        } else {
            $_SESSION['error'] = "Failed to send reset email. Please try again.";
        }

    } else {
        $_SESSION['error'] = "No account found with that email.";
    }
    header("Location: forgot_password.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password</title>
  <link rel="stylesheet" href="assets/css/auth_pages.css">
</head>
<body>
    <div class="auth-container">
    <h2>Forgot Password</h2>
    <p>Enter your email to receive a password reset link.</p>
    <form method="POST">
        <div class="input-group">
            <label>Email Address</label>
            <input type="email" name="email" placeholder="Enter your email" required>
        </div>
        <button type="submit" class="auth-btn">Send Reset Link</button>
    </form>
  </div>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Show server-side messages (if any) using SweetAlert2
        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: <?php echo json_encode($_SESSION['success']); ?>,
                confirmButtonText: 'OK'
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: <?php echo json_encode($_SESSION['error']); ?>,
                confirmButtonText: 'OK'
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>
</body>
</html>
