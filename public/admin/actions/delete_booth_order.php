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
    // First get the order details to check if it exists and get ORDER STATUS (not payment status)
    $stmt = $conn->prepare("SELECT pb.id, pb.status as order_status, COALESCE(p.status, 'unpaid') as payment_status
                           FROM photo_bookings pb
                           LEFT JOIN payments p ON p.reference_id = pb.id AND p.payment_type = 'photo_booking'
                           WHERE pb.id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }

    $order = $result->fetch_assoc();
    $stmt->close();

    // Only allow deletion of declined orders for safety - CHECK ORDER STATUS, not payment status
    if ($order['order_status'] !== 'declined') {
        echo json_encode(['success' => false, 'message' => 'Only declined orders can be deleted']);
        exit();
    }

    // Start transaction to delete both order and payment
    $conn->begin_transaction();

    // Delete associated payment first
    $delete_payment_stmt = $conn->prepare("DELETE FROM payments WHERE reference_id = ? AND payment_type = 'photo_booking'");
    $delete_payment_stmt->bind_param("i", $order_id);
    $delete_payment_stmt->execute();
    $delete_payment_stmt->close();

    // Delete the booth order
    $delete_order_stmt = $conn->prepare("DELETE FROM photo_bookings WHERE id = ?");
    $delete_order_stmt->bind_param("i", $order_id);

    if ($delete_order_stmt->execute()) {
        if ($delete_order_stmt->affected_rows > 0) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Order deleted successfully']);
        } else {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Order not found or already deleted']);
        }
    } else {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to delete order']);
    }

    $delete_order_stmt->close();

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>