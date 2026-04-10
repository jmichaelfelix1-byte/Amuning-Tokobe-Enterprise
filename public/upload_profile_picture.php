<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'includes/config.php';

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $file = $_FILES['profile_picture'];

    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'File upload error.';
        echo json_encode($response);
        exit();
    }

    // Check file type
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        $response['message'] = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
        echo json_encode($response);
        exit();
    }

    // Check file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        $response['message'] = 'File size too large. Maximum size is 5MB.';
        echo json_encode($response);
        exit();
    }

    // Create upload directory if it doesn't exist
    $upload_dir = 'assets/images/email_profile/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            $response['message'] = 'Failed to create upload directory.';
            echo json_encode($response);
            exit();
        }
    }

    // Generate unique filename
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
    $file_path = $upload_dir . $new_filename;
    $db_path = 'assets/images/email_profile/' . $new_filename; // Path for database storage (with assets/ prefix)

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // Delete old profile picture if it exists
        $stmt = $conn->prepare("SELECT profile FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user_data = $result->fetch_assoc();
            if (!empty($user_data['profile'])) {
                // Handle both old format (full path) and new format (relative path)
                $old_file_path = $user_data['profile'];
                if (strpos($old_file_path, 'assets/images/email_profile/') === 0) {
                    // Old format, convert to full path
                    $old_file = $old_file_path;
                } elseif (strpos($old_file_path, 'images/email_profile/') === 0) {
                    // New format, add assets/ prefix
                    $old_file = 'assets/' . $old_file_path;
                } else {
                    // Skip deletion for non-email profile images (like Google profile pictures)
                    $old_file = null;
                }

                if ($old_file && file_exists($old_file)) {
                    unlink($old_file);
                }
            }
        }
        $stmt->close();

        // Update database with new profile picture path
        $stmt = $conn->prepare("UPDATE users SET profile = ? WHERE id = ?");
        $stmt->bind_param("si", $db_path, $user_id);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Profile picture updated successfully!';
            $response['image_url'] = $db_path; // Return the database path for frontend display
        } else {
            $response['message'] = 'Failed to update profile picture in database.';
            // Delete the uploaded file if database update failed
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        $stmt->close();
    } else {
        $response['message'] = 'Failed to upload file.';
    }
} else {
    $response['message'] = 'Invalid request.';
}

$conn->close();
echo json_encode($response);
?>
