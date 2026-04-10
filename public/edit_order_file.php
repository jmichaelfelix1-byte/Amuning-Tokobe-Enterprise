<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'includes/config.php';

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

try {
    // Validate input
    if (!isset($_POST['order_id']) || !isset($_FILES['new_file'])) {
        throw new Exception('Missing required fields');
    }

    $order_id = intval($_POST['order_id']);
    $file = $_FILES['new_file'];

    // Validate order belongs to user
    $stmt = $conn->prepare("SELECT id, image_path, status FROM printing_orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if (!$order) {
        throw new Exception('Order not found or access denied');
    }

    // Check if order is validated or beyond
    if (in_array(strtolower($order['status']), ['validated', 'processing', 'completed'])) {
        throw new Exception('Cannot edit a validated or processing order. Please contact support if you need to make changes.');
    }

    // Validate file
    $max_file_size = 52428800; // 50MB
    if ($file['size'] > $max_file_size) {
        throw new Exception('File size exceeds 50MB limit');
    }

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'pdf', 'psd', 'ai'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_extension, $allowed_extensions)) {
        throw new Exception('File type not allowed. Accepted: ' . implode(', ', $allowed_extensions));
    }

    // Create upload directory if it doesn't exist
    $upload_dir = 'uploads/printing/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique filename
    $timestamp = time();
    $random_string = bin2hex(random_bytes(5));
    $new_filename = $order_id . '_' . $timestamp . '_' . $random_string . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        throw new Exception('Failed to move uploaded file');
    }

    // Delete old file if it exists
    if (!empty($order['image_path']) && file_exists($order['image_path'])) {
        unlink($order['image_path']);
    }

    // Update database
    $update_stmt = $conn->prepare("UPDATE printing_orders SET image_path = ?, status = 'pending' WHERE id = ? AND user_id = ?");
    $update_stmt->bind_param("sii", $upload_path, $order_id, $user_id);

    if ($update_stmt->execute()) {
        $update_stmt->close();
        $response['success'] = true;
        $response['message'] = 'File updated successfully';
    } else {
        throw new Exception('Database update failed');
    }

} catch (Exception $e) {
    error_log("File upload error: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
exit();
?>
