<?php
header('Content-Type: application/json');

// Set timezone to Manila, Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

session_start();

// Include necessary files
include 'includes/config.php';

// Get POST data
$booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$type = isset($_POST['type']) ? $_POST['type'] : '';

// Validate session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Validate input
if (($type === 'photo' && !$booking_id) || ($type === 'printing' && !$order_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters.']);
    exit();
}

try {
    $conn->begin_transaction();

    if ($type === 'photo') {
        // Check if booking exists and belongs to user
        $stmt = $conn->prepare("SELECT id, status FROM photo_bookings WHERE id = ? AND email = ?");
        $stmt->bind_param("is", $booking_id, $user_email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Booking not found or access denied.']);
            exit();
        }

        $booking = $result->fetch_assoc();

        // Check if payment status allows deletion
        if (strtolower($booking['status']) === 'paid') {
            echo json_encode(['success' => false, 'message' => 'Cannot delete a paid booking.']);
            exit();
        }

        // Delete the booking
        $delete_stmt = $conn->prepare("DELETE FROM photo_bookings WHERE id = ? AND email = ?");
        $delete_stmt->bind_param("is", $booking_id, $user_email);

        if (!$delete_stmt->execute()) {
            throw new Exception('Failed to delete booking: ' . $delete_stmt->error);
        }

        $delete_stmt->close();

    } elseif ($type === 'printing') {
        // Check if order exists and belongs to user
        $stmt = $conn->prepare("SELECT id, status FROM printing_orders WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $order_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Order not found or access denied.']);
            exit();
        }

        $order = $result->fetch_assoc();

        // Check if payment status allows deletion
        if (strtolower($order['status']) === 'paid') {
            echo json_encode(['success' => false, 'message' => 'Cannot delete a paid order.']);
            exit();
        }

        // Delete the order
        $delete_stmt = $conn->prepare("DELETE FROM printing_orders WHERE id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $order_id, $user_id);

        if (!$delete_stmt->execute()) {
            throw new Exception('Failed to delete order: ' . $delete_stmt->error);
        }

        $delete_stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid deletion type.']);
        exit();
    }

    // Commit transaction
    $conn->commit();

    $item_type = $type === 'photo' ? 'booking' : 'order';
    echo json_encode([
        'success' => true,
        'message' => ucfirst($item_type) . ' deleted successfully.'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    error_log("Deletion error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to delete item: ' . $e->getMessage()]);
}

$conn->close();
?>
