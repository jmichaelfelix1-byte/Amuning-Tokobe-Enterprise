<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'user') {
    header('Location: index.php');
    exit();
}

require_once 'email_verification.php';
require_once 'includes/config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $verification_code = rand(100000, 999999); // 6-digit code
    
    // Validate passwords match
    if ($password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match. Please try again.';
    } else {
        try {
            // Check if email already exists
            $checkStmt = $conn->prepare("SELECT id, register_as FROM users WHERE email = ?");
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if ($user['register_as'] === 'google') {
                    $_SESSION['error'] = 'This email is already registered with Google. Please sign in using Google.';
                } else {
                    $_SESSION['error'] = 'This email is already registered. Please sign in instead.';
                }
                header('Location: signin.php');
                exit();
            } else {
                // Insert new user - match your exact database structure
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $user_type = 'user';
                $register_as = 'email';
                
                $stmt = $conn->prepare("INSERT INTO users (email, password, user_type, register_as, full_name, verification_code, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssi", $email, $hashed_password, $user_type, $register_as, $full_name, $verification_code, $is_verified);
                
                $is_verified = 0; // Set to 0 for unverified
                
                if ($stmt->execute()) {
                    // Send verification email
                    if (sendVerificationEmail($email, $full_name, $verification_code)) {
                        // SUCCESS: Redirect to verification page
                        $_SESSION['temp_email'] = $email;
                        header('Location: verify.php?email=' . urlencode($email) . '&from=signup');
                        exit();
                    } else {
                        $_SESSION['error'] = 'Failed to send verification email. Please try again.';
                    }
                } else {
                    error_log("Database error: " . $stmt->error);
                    $_SESSION['error'] = 'Registration failed. Please try again. Error: ' . $stmt->error;
                }
                $stmt->close();
            }
            $checkStmt->close();
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred. Please try again.';
        }
    }
}

$page_title = 'Sign Up | Amuning Tokobe Enterprise';
$additional_css = ['login.css'];
$additional_js = ['jvs_loginpage.js'];
include 'includes/header.php';
?>

<!-- Terms and Conditions Modal -->
<div id="termsModal" class="terms-modal">
    <div class="terms-modal-content">
        <div class="terms-modal-header">
            <h2>Terms and Conditions</h2>
            <button class="terms-modal-close" aria-label="Close modal">&times;</button>
        </div>
        <div class="terms-modal-body">
            <h3>1. Acceptance of Terms</h3>
            <p>By accessing and using this website, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>

            <h3>2. Use License</h3>
            <p>Permission is granted to temporarily download one copy of the materials (information or software) on Amuning Tokobe Enterprise website for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:</p>
            <ul>
                <li>Modifying or copying the materials</li>
                <li>Using the materials for any commercial purpose or for any public display</li>
                <li>Attempting to decompile, reverse engineering, disassembling, or otherwise reducing the software to perceivable form</li>
                <li>Removing any copyright or other proprietary notations from the materials</li>
                <li>Transferring the materials to another person or "mirroring" the materials on any other server</li>
            </ul>

            <h3>3. Disclaimer</h3>
            <p>The materials on Amuning Tokobe Enterprise website are provided on an 'as is' basis. Amuning Tokobe Enterprise makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>

            <h3>4. Limitations</h3>
            <p>In no event shall Amuning Tokobe Enterprise or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on Amuning Tokobe Enterprise website, even if Amuning Tokobe Enterprise or an authorized representative has been notified orally or in writing of the possibility of such damage.</p>

            <h3>5. Accuracy of Materials</h3>
            <p>The materials appearing on Amuning Tokobe Enterprise website could include technical, typographical, or photographic errors. Amuning Tokobe Enterprise does not warrant that any of the materials on its website are accurate, complete, or current. Amuning Tokobe Enterprise may make changes to the materials contained on its website at any time without notice.</p>

            <h3>6. Links</h3>
            <p>Amuning Tokobe Enterprise has not reviewed all of the sites linked to its website and is not responsible for the contents of any such linked site. The inclusion of any link does not imply endorsement by Amuning Tokobe Enterprise of the site. Use of any such linked website is at the user's own risk.</p>

            <h3>7. Modifications</h3>
            <p>Amuning Tokobe Enterprise may revise these terms of service for its website at any time without notice. By using this website, you are agreeing to be bound by the then current version of these terms of service.</p>

            <h3>8. Governing Law</h3>
            <p>These terms and conditions are governed by and construed in accordance with the laws of the jurisdiction where Amuning Tokobe Enterprise operates, and you irrevocably submit to the exclusive jurisdiction of the courts in that location.</p>

            <h3>9. Payment and Bookings</h3>
            <p>All payments and bookings are final. Cancellations must be made within the specified timeframe as outlined in our cancellation policy. Refunds will be processed according to our refund policy. You agree to provide accurate information when making bookings and payments.</p>

            <h3>10. User Responsibilities</h3>
            <p>You agree to use this website only for lawful purposes and in a way that does not infringe upon the rights of others or restrict their use and enjoyment of the website. Prohibited behavior includes harassing or causing distress or inconvenience to any person, transmitting offensive or disruptive messages, or disrupting the normal flow of dialogue within our website.</p>
        </div>
        <div class="terms-modal-footer">
            <button class="terms-disagree-btn" id="termsDisagreeBtn">Close</button>
            <button class="terms-agree-btn" id="termsAgreeBtn">I Agree</button>
        </div>
    </div>
</div>

<script src="assets/js/script.js"></script>
<section class="login-section">
    <div class="login-container">
        <div class="welcome-text">
            <h1>Create Your Account</h1>
            <h2>Join Amuning Tokobe Enterprise</h2>
            <p>Sign up to manage your orders, save your favorite designs, and stay updated.</p>
        </div>
        <div class="login-form">
            <form action="" method="post" id="signupForm" novalidate>
                <div class="input-group">
                    <input type="text" id="full_name" name="full_name" placeholder="Full name" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>
                <div class="input-group">
                    <input type="email" id="email" name="email" placeholder="Email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="input-group password-group">
                    <input type="password" id="signup_password" name="password" placeholder="Password" required>
                    <i class="fas fa-eye password-toggle"  data-target="signup_password" role="button" aria-label="Show password"></i>
                    
                </div>
                <small style="color: #ecececff; font-size: 12px; margin-top: 5px; display: block;">
                    Password must contain: 8+ characters, 1 uppercase letter, 1 special character
                    </small>
                <div class="input-group password-group">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" minlength="8" required>
                    <i class="fas fa-eye password-toggle" data-target="confirm_password" role="button" aria-label="Show password"></i>
                </div>
                
                <button type="submit" class="login-btn" name="submit" id="submitBtn" disabled>Create account</button>
                <div class="form-links">
                    <p>Already have an account? <a href="signin.php">Login</a></p>
                </div>
                <div class="terms">
                    <div class="terms-checkbox-wrapper">
                        <input type="checkbox" id="accept_terms" name="accept_terms" required>
                        <label for="accept_terms">I agree to the <a href="#" id="termsLink" class="terms-link">Terms and Conditions</a></label>
                    </div>
                </div>
                <div class="divider">
                    <span>Or</span>
                </div>
                <button type="button" class="google-btn">
                    <i class="fab fa-google"></i> Continue with Google
                </button>
            </form>
        </div>
    </div>
</section>
<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const termsModal = document.getElementById('termsModal');
const termsLink = document.getElementById('termsLink');
const termsAgreeBtn = document.getElementById('termsAgreeBtn');
const termsDisagreeBtn = document.getElementById('termsDisagreeBtn');
const termsCloseBtn = document.querySelector('.terms-modal-close');
const acceptTermsCheckbox = document.getElementById('accept_terms');
const submitBtn = document.getElementById('submitBtn');

// Update submit button state based on checkbox
function updateSubmitButtonState() {
    if (acceptTermsCheckbox.checked) {
        submitBtn.disabled = false;
    } else {
        submitBtn.disabled = true;
    }
}

// Listen for checkbox changes
acceptTermsCheckbox.addEventListener('change', updateSubmitButtonState);

// Open modal when terms link is clicked
termsLink.addEventListener('click', (e) => {
    e.preventDefault();
    termsModal.classList.add('active');
});

// Close modal functions
function closeTermsModal() {
    termsModal.classList.remove('active');
}

termsCloseBtn.addEventListener('click', closeTermsModal);
termsDisagreeBtn.addEventListener('click', closeTermsModal);

// I Agree button - close modal and check the checkbox
termsAgreeBtn.addEventListener('click', () => {
    acceptTermsCheckbox.checked = true;
    updateSubmitButtonState();
    closeTermsModal();
});

// Close modal when clicking outside the modal content
termsModal.addEventListener('click', (e) => {
    if (e.target === termsModal) {
        closeTermsModal();
    }
});

// Password toggle functionality
document.querySelectorAll('.password-toggle').forEach(toggle => {
    toggle.addEventListener('click', function() {
        const targetId = this.getAttribute('data-target');
        const passwordInput = document.getElementById(targetId);
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            this.classList.remove('fa-eye');
            this.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            this.classList.remove('fa-eye-slash');
            this.classList.add('fa-eye');
        }
    });
});

// Google Sign-up Button
document.querySelector('.google-btn').addEventListener('click', function(e) {
    e.preventDefault();
    // Redirect directly to google_auth.php with signup flag
    window.location.href = 'google_auth.php?signup=1';
});

// Form submission
document.getElementById('signupForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Check if terms and conditions are accepted
    if (!acceptTermsCheckbox.checked) {
        Swal.fire({
            icon: 'warning',
            title: 'Terms Required',
            text: 'Please agree to the terms and conditions to continue.',
            confirmButtonColor: '#f5276c',
            confirmButtonText: 'OK'
        }).then(() => {
            acceptTermsCheckbox.focus();
        });
        return;
    }
    
    const formData = new FormData(this);
    
    fetch('process_signup.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: data.message,
                confirmButtonColor: '#f5276c',
                confirmButtonText: 'OK'
            }).then(() => {
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Signup Error',
                text: data.message,
                confirmButtonColor: '#f5276c',
                confirmButtonText: 'OK'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred. Please try again.',
            confirmButtonColor: '#f5276c',
            confirmButtonText: 'OK'
        });
        console.error('Error:', error);
    });
});
</script>

<!-- Terms and Conditions Modal -->
<div id="termsModal" class="terms-modal">
    <div class="terms-modal-content">
        <div class="terms-modal-header">
            <h2>Terms and Conditions</h2>
            <button class="terms-modal-close" aria-label="Close modal">&times;</button>
        </div>
        <div class="terms-modal-body">
            <h3>1. Acceptance of Terms</h3>
            <p>By accessing and using this website, you accept and agree to be bound by the terms and provision of this agreement. If you do not agree to abide by the above, please do not use this service.</p>

            <h3>2. Use License</h3>
            <p>Permission is granted to temporarily download one copy of the materials (information or software) on Amuning Tokobe Enterprise website for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title, and under this license you may not:</p>
            <ul>
                <li>Modifying or copying the materials</li>
                <li>Using the materials for any commercial purpose or for any public display</li>
                <li>Attempting to decompile, reverse engineering, disassembling, or otherwise reducing the software to perceivable form</li>
                <li>Removing any copyright or other proprietary notations from the materials</li>
                <li>Transferring the materials to another person or "mirroring" the materials on any other server</li>
            </ul>

            <h3>3. Disclaimer</h3>
            <p>The materials on Amuning Tokobe Enterprise website are provided on an 'as is' basis. Amuning Tokobe Enterprise makes no warranties, expressed or implied, and hereby disclaims and negates all other warranties including, without limitation, implied warranties or conditions of merchantability, fitness for a particular purpose, or non-infringement of intellectual property or other violation of rights.</p>

            <h3>4. Limitations</h3>
            <p>In no event shall Amuning Tokobe Enterprise or its suppliers be liable for any damages (including, without limitation, damages for loss of data or profit, or due to business interruption) arising out of the use or inability to use the materials on Amuning Tokobe Enterprise website, even if Amuning Tokobe Enterprise or an authorized representative has been notified orally or in writing of the possibility of such damage.</p>

            <h3>5. Accuracy of Materials</h3>
            <p>The materials appearing on Amuning Tokobe Enterprise website could include technical, typographical, or photographic errors. Amuning Tokobe Enterprise does not warrant that any of the materials on its website are accurate, complete, or current. Amuning Tokobe Enterprise may make changes to the materials contained on its website at any time without notice.</p>

            <h3>6. Links</h3>
            <p>Amuning Tokobe Enterprise has not reviewed all of the sites linked to its website and is not responsible for the contents of any such linked site. The inclusion of any link does not imply endorsement by Amuning Tokobe Enterprise of the site. Use of any such linked website is at the user's own risk.</p>

            <h3>7. Modifications</h3>
            <p>Amuning Tokobe Enterprise may revise these terms of service for its website at any time without notice. By using this website, you are agreeing to be bound by the then current version of these terms of service.</p>

            <h3>8. Governing Law</h3>
            <p>These terms and conditions are governed by and construed in accordance with the laws of the jurisdiction where Amuning Tokobe Enterprise operates, and you irrevocably submit to the exclusive jurisdiction of the courts in that location.</p>

            <h3>9. Payment and Bookings</h3>
            <p>All payments and bookings are final. Cancellations must be made within the specified timeframe as outlined in our cancellation policy. Refunds will be processed according to our refund policy. You agree to provide accurate information when making bookings and payments.</p>

            <h3>10. User Responsibilities</h3>
            <p>You agree to use this website only for lawful purposes and in a way that does not infringe upon the rights of others or restrict their use and enjoyment of the website. Prohibited behavior includes harassing or causing distress or inconvenience to any person, transmitting offensive or disruptive messages, or disrupting the normal flow of dialogue within our website.</p>
        </div>
        <div class="terms-modal-footer">
            <button class="terms-disagree-btn" id="termsDisagreeBtn">Close</button>
            <button class="terms-agree-btn" id="termsAgreeBtn">I Agree</button>
        </div>
    </div>
</div>
