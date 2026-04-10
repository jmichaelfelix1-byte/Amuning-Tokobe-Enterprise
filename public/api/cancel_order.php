<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$order_type = isset($_POST['order_type']) ? trim($_POST['order_type']) : '';
$user_id = $_SESSION['user_id'];

if ($order_id <= 0 || empty($order_type)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    if ($order_type === 'printing_order') {
        // Verify user owns this order
        $stmt = $conn->prepare("SELECT id, status FROM printing_orders WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit();
        }
        
        $order = $result->fetch_assoc();
        $stmt->close();
        
        // Check if order can be cancelled (not processing, not completed)
        $cancelable_statuses = ['pending', 'declined'];
        if (!in_array(strtolower($order['status']), $cancelable_statuses)) {
            echo json_encode(['success' => false, 'message' => 'This order cannot be cancelled']);
            exit();
        }
        
        // Update order status to cancelled
        $stmt = $conn->prepare("UPDATE printing_orders SET status = 'cancelled' WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
        
        // Create notification
        $title = 'Order Cancelled';
        $message = 'Your printing order #' . str_pad($order_id, 4, '0', STR_PAD_LEFT) . ' has been cancelled.';
        $notif_stmt = $conn->prepare("
            INSERT INTO notifications 
            (user_id, order_type, order_id, notification_type, title, message, is_read, created_at) 
            VALUES (?, 'printing_order', ?, 'order_cancelled', ?, ?, FALSE, NOW())
        ");
        $notif_stmt->bind_param("iiss", $user_id, $order_id, $title, $message);
        $notif_stmt->execute();
        $notif_stmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Order cancelled successfully']);
        
    } elseif ($order_type === 'photo_booking') {
        // Same logic for photo bookings
        $stmt = $conn->prepare("SELECT id, status FROM photo_bookings WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
            exit();
        }
        
        $order = $result->fetch_assoc();
        $stmt->close();
        
        // Check if order can be cancelled
        $cancelable_statuses = ['pending', 'declined'];
        if (!in_array(strtolower($order['status']), $cancelable_statuses)) {
            echo json_encode(['success' => false, 'message' => 'This booking cannot be cancelled']);
            exit();
        }
        
        // Update order status to cancelled
        $stmt = $conn->prepare("UPDATE photo_bookings SET status = 'cancelled' WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
        
        // Create notification
        $title = 'Booking Cancelled';
        $message = 'Your photo booking #' . str_pad($order_id, 4, '0', STR_PAD_LEFT) . ' has been cancelled.';
        $notif_stmt = $conn->prepare("
            INSERT INTO notifications 
            (user_id, order_type, order_id, notification_type, title, message, is_read, created_at) 
            VALUES (?, 'photo_booking', ?, 'order_cancelled', ?, ?, FALSE, NOW())
        ");
        $notif_stmt->bind_param("iiss", $user_id, $order_id, $title, $message);
        $notif_stmt->execute();
        $notif_stmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid order type']);
    }
    
} catch (Exception $e) {
    error_log('Cancel order error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

$conn->close();
?>
