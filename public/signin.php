<?php
// Start session and dependencies
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect logged in users
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'user') {
    header('Location: index.php');
    exit();
}

require_once 'includes/config.php';
require_once 'email_verification.php';

// Handle non-AJAX POST (fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $email = trim($_POST['username']);
    $password = $_POST['password'];
    try {
        $stmt = $conn->prepare("SELECT id, full_name, password, is_verified, verification_code, user_type, register_as, login_attempts, locked_until FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // Check active lockout
            if (!empty($user['locked_until'])) {
                $now = new DateTime('now');
                $lockedUntil = new DateTime($user['locked_until']);
                if ($lockedUntil > $now) {
                    $interval = $now->diff($lockedUntil);
                    $minutes = ($interval->h * 60) + $interval->i + ($interval->d * 24 * 60);
                    $_SESSION['error'] = "Account locked due to multiple failed attempts. Try again in {$minutes} minute(s).";
                    header('Location: signin.php');
                    exit();
                }
            }
            if ($user['register_as'] === 'email') {
                if (password_verify($password, $user['password'])) {
                    if ($user['is_verified'] == 0) {
                        $new_code = rand(100000, 999999);
                        $updateStmt = $conn->prepare("UPDATE users SET verification_code = ? WHERE email = ?");
                        $updateStmt->bind_param("ss", $new_code, $email);
                        if ($updateStmt->execute()) {
                            if (sendVerificationEmail($email, $user['full_name'], $new_code)) {
                                $_SESSION['success'] = 'A new verification code has been sent to your email!';
                                header('Location: verify.php?email=' . urlencode($email) . '&from=signin');
                                exit();
                            } else {
                                $_SESSION['error'] = 'Failed to send verification email. Please try again.';
                            }
                        } else {
                            $_SESSION['error'] = 'Error generating verification code. Please try again.';
                        }
                        $updateStmt->close();
                    } else {
                        // Reset attempts on successful login
                        $resetStmt = $conn->prepare("UPDATE users SET login_attempts = 0, last_failed_login = NULL, locked_until = NULL WHERE id = ?");
                        $resetStmt->bind_param("i", $user['id']);
                        $resetStmt->execute();
                        $resetStmt->close();

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_email'] = $email;
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['user_type'] = $user['user_type'];
                        $_SESSION['logged_in'] = true;
                        $redirect_url = ($user['user_type'] === 'admin') ? 'admin/dashboard.php' : 'index.php';
                        header('Location: ' . $redirect_url);
                        exit();
                    }
                } else {
                    // Wrong password: increment attempts and possibly lock
                    $attempts = (int)$user['login_attempts'] + 1;
                    $lockedUntil = null;

                    if ($attempts >= 7) {
                        $lockedUntil = (new DateTime('now'))->modify('+60 minutes')->format('Y-m-d H:i:s');
                    } elseif ($attempts >= 5) {
                        $lockedUntil = (new DateTime('now'))->modify('+30 minutes')->format('Y-m-d H:i:s');
                    } elseif ($attempts >= 3) {
                        $lockedUntil = (new DateTime('now'))->modify('+15 minutes')->format('Y-m-d H:i:s');
                    }

                    $updateStmt = $conn->prepare("UPDATE users SET login_attempts = ?, last_failed_login = NOW(), locked_until = ? WHERE id = ?");
                    $updateStmt->bind_param("isi", $attempts, $lockedUntil, $user['id']);
                    $updateStmt->execute();
                    $updateStmt->close();

                    if ($lockedUntil) {
                        $_SESSION['error'] = 'Too many failed attempts. Your account is temporarily locked.';
                    } else {
                        $_SESSION['error'] = 'Invalid password. Please try again.';
                    }
                }
            } else {
                $_SESSION['error'] = 'This account is registered with Google. Please sign in using Google.';
            }
        } else {
            $_SESSION['error'] = 'No account found with this email. Please sign up first.';
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error'] = 'An error occurred. Please try again.';
    }
}

$page_title = 'Sign In | Amuning Tokobe Enterprise';
$additional_css = ['login.css'];
$additional_js = ['jvs_loginpage.js'];
include 'includes/header.php';
?>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (isset($_SESSION['error'])): ?>
  <script>
    Swal.fire({icon: 'error', title: 'Error', text: <?php echo json_encode($_SESSION['error']); ?>, confirmButtonText: 'OK'});
  </script>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['success'])): ?>
  <script>
    Swal.fire({icon: 'success', title: 'Success', text: <?php echo json_encode($_SESSION['success']); ?>, confirmButtonText: 'OK'});
  </script>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_GET['message']) && $_GET['message'] === 'no_account_google'): ?>
  <script>
    Swal.fire({icon: 'error', title: 'No Account Found', text: 'No account found with this email. Please sign up first using Google or create an account with email.', confirmButtonText: 'OK'});
  </script>
<?php endif; ?>

<section class="login-section">
    <div class="login-container">
        <div class="welcome-text">
            <h1>Hello & Welcome</h1>
            <h2>Amuning Tokobe Enterprise</h2>
        </div>
        <div class="login-form">
            <form action="" method="post" id="signinForm">
                <div class="input-group">
                    <input type="email" id="username" name="username" placeholder="Email" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                <div class="input-group password-group">
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <i class="fas fa-eye password-toggle" data-target="password" role="button" aria-label="Show password"></i>
                </div>
                <div id="attemptsWarning" class="attempts-warning" style="display: none; margin-bottom: 15px; padding: 10px; background-color: #fff3f5; border-left: 4px solid #f5276c; border-radius: 4px; font-size: 14px; color: #d63447;">
                    <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                    <span id="attemptsText"></span>
                </div>
                <button type="submit" class="login-btn" name="login">Login</button>
                <div class="form-links">
                    <p>Don't have an account? <a href="signup.php">Sign up now</a></p>
                    <p><a href="forgot_password.php">Forgot your password?</a></p>
                </div>
                <div class="divider">
                    <span>Or</span>
                </div>
                <button type="button" class="google-btn">
                    <i class="fab fa-google"></i> Login with Google
                </button>
            </form>
        </div>
    </div>
</section>

<script src="assets/js/script.js"></script>
<script>
// Google Sign-in Button
document.querySelector('.google-btn').addEventListener('click', function(e) {
    e.preventDefault();
    // Redirect directly to google_auth.php for signin
    window.location.href = 'google_auth.php';
});

// Handle login form submission to show attempts warning
document.getElementById('signinForm').addEventListener('submit', function(e) {
    // Clear previous warnings
    document.getElementById('attemptsWarning').style.display = 'none';
    
    // The script.js will handle the AJAX submission
    // We'll listen for the response and update the warning
});

// Intercept Swal error messages to show attempts
const originalFire = Swal.fire;
let lastResponse = null;

// Override Swal.fire to capture error responses
Swal.fire = function(options) {
    // Check if this is an error response with attempts info
    if (options && options.icon === 'error' && lastResponse && lastResponse.attempts_remaining !== undefined) {
        const attemptsWarning = document.getElementById('attemptsWarning');
        const attemptsText = document.getElementById('attemptsText');
        
        if (lastResponse.attempts_remaining > 0 && !lastResponse.is_locked) {
            attemptsWarning.style.display = 'block';
            attemptsText.textContent = `${lastResponse.attempts_remaining} attempt${lastResponse.attempts_remaining === 1 ? '' : 's'} remaining before your account is locked.`;
        }
    }
    return originalFire.apply(Swal, arguments);
};

// Capture the response from the form submission
const originalSend = XMLHttpRequest.prototype.send;
XMLHttpRequest.prototype.send = function(data) {
    const self = this;
    const originalOnReadyStateChange = this.onreadystatechange;
    
    this.onreadystatechange = function() {
        if (self.readyState === 4) {
            try {
                const response = JSON.parse(self.responseText);
                lastResponse = response;
            } catch(e) {}
        }
        if (originalOnReadyStateChange) {
            originalOnReadyStateChange.apply(this, arguments);
        }
    };
    originalSend.apply(this, arguments);
};
</script>
</script>
<?php include 'includes/footer.php'; ?>