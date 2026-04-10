<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';

if (!isset($_GET['token'])) {
    die("Invalid link.");
}

$token = $_GET['token'];

// Check if token is valid
$stmt = $conn->prepare("SELECT email, reset_expires FROM users WHERE reset_token=?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Invalid or expired token.");
}

$user = $result->fetch_assoc();

// Check expiration
if (strtotime($user['reset_expires']) < time()) {
    die("Token has expired.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $update = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE reset_token=?");
    $update->bind_param("ss", $newPassword, $token);
    $update->execute();

    $_SESSION['success'] = "Password successfully reset. You can now log in.";
    header("Location: signin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password</title>
  <link rel="stylesheet" href="assets/css/auth_pages.css">
</head>
<body>
  <div class="auth-container">
    <h2>Reset Password</h2>
    <p>Enter your new password below.</p>
    <form method="POST">
        <div class="input-group">
            <label>New Password</label>
            <input type="password" name="password" placeholder="Enter new password" required>
        </div>
        <button type="submit" class="auth-btn">Reset Password</button>
    </form>
  </div>
</body>
</html>
