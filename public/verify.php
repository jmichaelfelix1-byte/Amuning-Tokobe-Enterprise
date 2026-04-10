<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';

$page_title = 'Verify Email | Amuning Tokobe Enterprise';
$additional_css = ['login.css'];
include 'includes/header.php';

$message = '';
$email = $_GET['email'] ?? '';

if (!$email) {
    echo '<script>Swal.fire({icon: "error", title: "Error", text: "No email provided. Please sign up again."});</script>';
}

// Display success message if redirected from signin
if (isset($_GET['from']) && $_GET['from'] === 'signin') {
    echo '<script>Swal.fire({icon: "success", title: "Code Sent", text: "A new verification code has been sent to your email. Please check your inbox."});</script>';
}

// Display session messages
if (isset($_SESSION['success'])) {
    echo '<script>Swal.fire({icon: "success", title: "Success", text: "' . $_SESSION['success'] . '"});</script>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<script>Swal.fire({icon: "error", title: "Error", text: "' . $_SESSION['error'] . '"});</script>';
    unset($_SESSION['error']);
}

if (isset($_POST['verify']) && $email) {
    $inputCode = trim($_POST['code']);

    $stmt = $conn->prepare("SELECT id, verification_code, verification_code_expires_at, is_verified, full_name FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        // Check if verification code has expired
        if ($result['verification_code_expires_at'] && strtotime($result['verification_code_expires_at']) < time()) {
            echo '<script>Swal.fire({icon: "error", title: "Code Expired", text: "Your verification code has expired. Please request a new one."});</script>';
        } else if ($result['verification_code'] == $inputCode) {
            $update = $conn->prepare("UPDATE users SET is_verified=1, verification_code=NULL, verification_code_expires_at=NULL WHERE email=?");
            $update->bind_param("s", $email);
            if ($update->execute()) {
                // Auto-login after verification
                $_SESSION['user_id'] = $result['id'];
                $_SESSION['user_email'] = $email;
                $_SESSION['full_name'] = $result['full_name'];
                $_SESSION['user_type'] = 'user';
                $_SESSION['logged_in'] = true;
                
                echo '<script>
                    Swal.fire({
                        icon: "success",
                        title: "Verification Successful!",
                        text: "Email verified successfully! Redirecting to home page...",
                        timer: 2000,
                        showConfirmButton: false
                    });
                    setTimeout(function() {
                        window.location.href = "index.php";
                    }, 2000);
                </script>';
            } else {
                echo '<script>Swal.fire({icon: "error", title: "Verification Failed", text: "Verification failed. Please try again."});</script>';
            }
        } else {
            echo '<script>alert("Invalid verification code. Please try again.");</script>';
        }
    } else {
        echo '<script>Swal.fire({icon: "error", title: "Email Not Found", text: "Email not found. Please sign up again."});</script>';
    }
}
?>

<section class="login-section">
    <div class="login-container">
        <div class="welcome-text">
            <h1>Verify Your Email</h1>
            <h2>Amuning Tokobe Enterprise</h2>
            <p>Please enter the verification code sent to: <strong><?php echo htmlspecialchars($email); ?></strong></p>
            <p style="color: #666; font-size: 14px;">You need to verify your email before you can access your account.</p>
            <p style="color: #e74c3c; font-size: 14px; font-weight: 500;"><i class="fas fa-clock"></i> Verification code expires in 1 minute</p>
        </div>
        <div class="login-form">
            <form method="POST" id="verifyForm">
                <div class="input-group">
                    <input type="text" name="code" placeholder="Enter 6-digit verification code" required maxlength="6" pattern="[0-9]{6}" title="Please enter exactly 6 digits">
                </div>
                <button type="submit" name="verify" class="login-btn">Verify Email</button>
                <div class="form-links">
                    <p>Didn't receive the code? <a href="resend_verification.php?email=<?php echo urlencode($email); ?>">Resend code</a></p>
                    <p><a href="signin.php">Back to Sign In</a></p>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>