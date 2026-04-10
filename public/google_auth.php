<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/google_config.php';

if (isset($_GET['code'])) {
    // Handle the OAuth callback
    $client->authenticate($_GET['code']);
    $_SESSION['access_token'] = $client->getAccessToken();

    // Get user info from Google
    $oauth2 = new Google_Service_Oauth2($client);
    $userInfo = $oauth2->userinfo->get();

    $email = $userInfo->email;
    $fullName = $userInfo->name;
    $profilePicture = $userInfo->picture ?? null;

    // Check if user already exists
    $stmt = $conn->prepare("SELECT id, full_name, user_type FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // User exists, sign in and update profile if needed
        $user = $result->fetch_assoc();

        // Only update profile picture with Google picture if user doesn't have a custom uploaded picture
        if ($profilePicture !== null) {
            // Check if current profile picture is a URL (Google picture) or a local path (custom upload)
            $stmt_check = $conn->prepare("SELECT profile FROM users WHERE id = ?");
            $stmt_check->bind_param("i", $user['id']);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $user_data = $result_check->fetch_assoc();
            $stmt_check->close();
            
            // Only update if current profile is empty or is a URL (Google picture)
            if (empty($user_data['profile']) || filter_var($user_data['profile'], FILTER_VALIDATE_URL)) {
                $updateStmt = $conn->prepare("UPDATE users SET profile = ? WHERE id = ?");
                $updateStmt->bind_param("si", $profilePicture, $user['id']);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $email;
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_type'] = $user['user_type'];
        $stmt->close();
        $conn->close();
        header('Location: index.php');
        exit();
    } else {
        if (isset($_SESSION['google_signup']) || isset($_GET['signup'])) {
            // Clear the session flag
            unset($_SESSION['google_signup']);
            // User doesn't exist, insert new user for signup
            $dummyPassword = bin2hex(random_bytes(16)); // 32 char random string
            $hashedPassword = password_hash($dummyPassword, PASSWORD_DEFAULT);
            $userType = 'user';

            $insertStmt = $conn->prepare("INSERT INTO users (email, password, user_type, register_as, full_name, mobile, address, profile) VALUES (?, ?, ?, 'google', ?, NULL, NULL, ?)");
            $insertStmt->bind_param("sssss", $email, $hashedPassword, $userType, $fullName, $profilePicture);
            if ($insertStmt->execute()) {
                // Get the new user ID
                $newUserId = $conn->insert_id;
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['user_email'] = $email;
                $_SESSION['full_name'] = $fullName;
                $_SESSION['user_type'] = $userType;
                $insertStmt->close();
                $stmt->close();
                $conn->close();
                header('Location: index.php');
                exit();
            } else {
                $insertStmt->close();
                $stmt->close();
                $conn->close();
                // Clear the session flag on failure
                unset($_SESSION['google_signup']);
                header('Location: signin.php?message=signup_failed');
                exit();
            }
        } else {
            // User doesn't exist, redirect to signin with message
            $stmt->close();
            $conn->close();
            header('Location: signin.php?message=no_account_google');
            exit();
        }
    }

} else {
    // Redirect to Google OAuth
    if (isset($_GET['signup'])) {
        $_SESSION['google_signup'] = true;
    }
    $authUrl = $client->createAuthUrl();
    header('Location: ' . $authUrl);
    exit();
}
?>
