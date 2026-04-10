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
$action = isset($_POST['action']) ? trim($_POST['action']) : 'archive'; // 'archive' or 'unarchive'
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
        
        if ($action === 'archive') {
            // Check if order can be archived (completed, declined, or cancelled)
            $archivable_statuses = ['completed', 'cancelled', 'declined'];
            if (!in_array(strtolower($order['status']), $archivable_statuses)) {
                echo json_encode(['success' => false, 'message' => 'This order cannot be archived']);
                exit();
            }
            
            // Mark order as archived by user
            $stmt = $conn->prepare("UPDATE printing_orders SET user_archived = 1 WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode(['success' => true, 'message' => 'Order archived successfully']);
        } else {
            // Unarchive action
            $stmt = $conn->prepare("UPDATE printing_orders SET user_archived = 0 WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode(['success' => true, 'message' => 'Order restored to active']);
        }
        
    } elseif ($order_type === 'photo_booking') {
        // Same logic for photo bookings (uses email instead of user_id)
        $user_email = $_SESSION['user_email'];
        $stmt = $conn->prepare("SELECT id, status FROM photo_bookings WHERE id = ? AND email = ?");
        $stmt->bind_param("is", $order_id, $user_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
            exit();
        }
        
        $order = $result->fetch_assoc();
        $stmt->close();
        
        if ($action === 'archive') {
            // Check if order can be archived
            $archivable_statuses = ['completed', 'cancelled', 'declined'];
            if (!in_array(strtolower($order['status']), $archivable_statuses)) {
                echo json_encode(['success' => false, 'message' => 'This booking cannot be archived']);
                exit();
            }
            
            // Mark order as archived by user
            $stmt = $conn->prepare("UPDATE photo_bookings SET user_archived = 1 WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode(['success' => true, 'message' => 'Booking archived successfully']);
        } else {
            // Unarchive action
            $stmt = $conn->prepare("UPDATE photo_bookings SET user_archived = 0 WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $stmt->close();
            
            echo json_encode(['success' => true, 'message' => 'Booking restored to active']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid order type']);
    }
    
} catch (Exception $e) {
    error_log('Archive order error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred']);
}

$conn->close();
?>
