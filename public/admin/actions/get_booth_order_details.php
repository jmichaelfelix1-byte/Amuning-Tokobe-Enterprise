<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit();
}

$order_id = intval($_GET['id']);

try {
    // Get booth order details
    $sql = "SELECT pb.*, COALESCE(p.status, 'unpaid') as payment_status
            FROM photo_bookings pb
            LEFT JOIN payments p ON p.reference_id = pb.id AND p.payment_type = 'photo_booking'
            WHERE pb.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }

    $order = $result->fetch_assoc();

    echo json_encode(['success' => true, 'data' => $order]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
