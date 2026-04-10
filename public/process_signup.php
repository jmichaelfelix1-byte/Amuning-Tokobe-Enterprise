<?php
session_start();
require_once 'includes/config.php';
require_once 'email_verification.php'; // Add this line

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $verification_code = rand(100000, 999999); // Generate verification code

    $errors = [];

    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }

    if (empty($password)) {
        $errors[] = 'Password is required.';
    } else {
        // Validate password strength
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'"\\|,.<>\/?]/', $password)) {
            $errors[] = 'Password must contain at least one special character (!@#$%^&*()_+-=[]{};:\'"\\|,.<>/?)';
        }
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, register_as FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if ($user['register_as'] === 'google') {
                $errors[] = 'This email is already registered with Google. Please sign in using Google.';
            } else {
                $errors[] = 'This email is already registered. Please sign in instead.';
            }
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $user_type = 'user';
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 minute'));

        $stmt = $conn->prepare("INSERT INTO users (email, password, user_type, register_as, full_name, verification_code, verification_code_expires_at, is_verified) VALUES (?, ?, ?, 'email', ?, ?, ?, 0)");
       $stmt->bind_param("ssssss", $email, $hashed_password, $user_type, $full_name, $verification_code, $expires_at);


        if ($stmt->execute()) {
            // Send verification email
            if (sendVerificationEmail($email, $full_name, $verification_code)) {
                $_SESSION['temp_email'] = $email; // Store email for verification
                echo json_encode([
                    'success' => true, 
                    'message' => 'Signup successful! Please check your email for verification code.',
                    'redirect' => 'verify.php?email=' . urlencode($email)
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Signup successful but failed to send verification email. Please contact support.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Signup failed. Please try again.']);
        }
        $stmt->close();
    } else {
        // Return errors as JSON
        echo json_encode(['success' => false, 'message' => implode("\n", $errors)]);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>