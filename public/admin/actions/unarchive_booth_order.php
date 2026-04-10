<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid order ID']);
    exit();
}

$order_id = (int)$_POST['id'];

try {
    // Check if order exists
    $stmt = $conn->prepare("SELECT id FROM photo_bookings WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }

    $stmt->close();

    // Unarchive the order
    $unarchive_stmt = $conn->prepare("UPDATE photo_bookings SET admin_archived = 0 WHERE id = ?");
    $unarchive_stmt->bind_param("i", $order_id);
    
    if ($unarchive_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Order restored successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to restore order']);
    }
    $unarchive_stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
