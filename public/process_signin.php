<?php
session_start();
require_once 'includes/config.php';
require_once 'email_verification.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['username']);
    $password = $_POST['password'];

    $errors = [];

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, password, user_type, register_as, full_name, is_verified, login_attempts, locked_until FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Check for active lockout
            if (!empty($user['locked_until'])) {
                $now = new DateTime('now');
                $lockedUntil = new DateTime($user['locked_until']);
                if ($lockedUntil > $now) {
                    $interval = $now->diff($lockedUntil);
                    $minutes = ($interval->h * 60) + $interval->i + ($interval->d * 24 * 60);
                    echo json_encode(['success' => false, 'message' => "Account locked due to multiple failed attempts. Try again in {$minutes} minute(s)."]);
                    $stmt->close();
                    $conn->close();
                    exit();
                }
            }
            if ($user['register_as'] === 'email') {
                if (password_verify($password, $user['password'])) {
                    
                    // CHECK VERIFICATION STATUS
                    if ($user['is_verified'] == 0) {
                        // User not verified - generate new code and send email
                        $new_code = rand(100000, 999999);
                        $expires_at = date('Y-m-d H:i:s', strtotime('+1 minute'));
                        
                        $updateStmt = $conn->prepare("UPDATE users SET verification_code = ?, verification_code_expires_at = ? WHERE email = ?");
                        $updateStmt->bind_param("sss", $new_code, $expires_at, $email);
                        
                        if ($updateStmt->execute()) {
                            // Send new verification email
                            if (sendVerificationEmail($email, $user['full_name'], $new_code)) {
                                echo json_encode([
                                    'success' => false, 
                                    'message' => 'Please verify your email first. A new verification code has been sent to your email.',
                                    'redirect' => 'verify.php?email=' . urlencode($email) . '&from=signin'
                                ]);
                            } else {
                                echo json_encode(['success' => false, 'message' => 'Please verify your email first. Failed to send verification email.']);
                            }
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Please verify your email first. Error generating verification code.']);
                        }
                        $updateStmt->close();
                    } else {
                        // User is verified - proceed with login
                        // Reset login attempts on successful login
                        $resetStmt = $conn->prepare("UPDATE users SET login_attempts = 0, last_failed_login = NULL, locked_until = NULL WHERE id = ?");
                        $resetStmt->bind_param("i", $user['id']);
                        $resetStmt->execute();
                        $resetStmt->close();

                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_email'] = $email;
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['user_type'] = $user['user_type'];

                        $redirect_url = ($user['user_type'] === 'admin') ? 'admin/dashboard.php' : 'index.php';
                        echo json_encode(['success' => true, 'message' => 'Login successful!', 'redirect' => $redirect_url]);
                    }
                } else {
                    // Wrong password: increment attempts and apply lockouts if thresholds reached
                    $attempts = (int)$user['login_attempts'] + 1;
                    $lockedUntil = null;
                    $remainingAttempts = max(0, 5 - $attempts);

                    if ($attempts >= 15) {
                        $lockedUntil = (new DateTime('now'))->modify('+15 minutes')->format('Y-m-d H:i:s');
                    } elseif ($attempts >= 10) {
                        $lockedUntil = (new DateTime('now'))->modify('+10 minutes')->format('Y-m-d H:i:s');
                    } elseif ($attempts >= 5) {
                        $lockedUntil = (new DateTime('now'))->modify('+5 minutes')->format('Y-m-d H:i:s');
                    }

                    $updateStmt = $conn->prepare("UPDATE users SET login_attempts = ?, last_failed_login = NOW(), locked_until = ? WHERE id = ?");
                    $updateStmt->bind_param("isi", $attempts, $lockedUntil, $user['id']);
                    $updateStmt->execute();
                    $updateStmt->close();

                    $msg = 'Invalid email or password.';
                    if ($lockedUntil) {
                        $msg = 'Too many failed attempts. Your account is temporarily locked.';
                    } else if ($remainingAttempts > 0) {
                        $msg = "Invalid email or password. You have {$remainingAttempts} attempt(s) remaining before your account is locked.";
                    }
                    echo json_encode(['success' => false, 'message' => $msg, 'attempts_remaining' => $remainingAttempts, 'is_locked' => !empty($lockedUntil)]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'This account is registered with Google. Please sign in using Google.']);
            }
        } else {
            // Do not reveal that the email doesn't exist; return generic message
            echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>