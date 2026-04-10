<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../includes/config.php';

/**
 * Create notification for order/booking status change
 * 
 * Expected POST parameters:
 * - user_id: ID of user to notify
 * - order_type: 'printing_order' or 'photo_booking'
 * - order_id: ID of the order/booking
 * - old_status: Previous status
 * - new_status: New status
 * - notification_type: Type of notification (e.g., 'status_changed')
 * - title: Notification title
 * - message: Notification message
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

$user_id = intval($data['user_id'] ?? 0);
$order_type = $data['order_type'] ?? '';
$order_id = intval($data['order_id'] ?? 0);
$old_status = $data['old_status'] ?? null;
$new_status = $data['new_status'] ?? null;
$notification_type = $data['notification_type'] ?? 'status_changed';
$title = $data['title'] ?? '';
$message = $data['message'] ?? '';

// Validate input
if (!$user_id || !$order_type || !$order_id || !$title) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

// Validate order_type
if (!in_array($order_type, ['printing_order', 'photo_booking'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid order type']);
    exit();
}

try {
    // Check if user exists
    $user_check = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $user_check->bind_param("i", $user_id);
    $user_check->execute();
    
    if ($user_check->get_result()->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        $user_check->close();
        exit();
    }
    $user_check->close();

    // Insert notification
    $insert_stmt = $conn->prepare(
        "INSERT INTO notifications 
        (user_id, order_type, order_id, notification_type, title, message, old_status, new_status, is_read, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, FALSE, NOW())"
    );
    
    $insert_stmt->bind_param(
        "isisisss",
        $user_id,
        $order_type,
        $order_id,
        $notification_type,
        $title,
        $message,
        $old_status,
        $new_status
    );
    
    if (!$insert_stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create notification', 'details' => $insert_stmt->error]);
        $insert_stmt->close();
        exit();
    }
    
    $notification_id = $insert_stmt->insert_id;
    $insert_stmt->close();
    
    // Fetch and return the created notification
    $fetch_stmt = $conn->prepare("SELECT * FROM notifications WHERE id = ?");
    $fetch_stmt->bind_param("i", $notification_id);
    $fetch_stmt->execute();
    $result = $fetch_stmt->get_result();
    $notification = $result->fetch_assoc();
    $fetch_stmt->close();
    
    http_response_code(201);
    echo json_encode(['success' => true, 'notification' => $notification]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
}
?>
