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
    $stmt = $conn->prepare("SELECT id FROM printing_orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }

    $stmt->close();

    // Archive the order
    $archive_stmt = $conn->prepare("UPDATE printing_orders SET admin_archived = 1, archived_date = NOW() WHERE id = ?");
    $archive_stmt->bind_param("i", $order_id);
    
    if ($archive_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Order archived successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to archive order']);
    }
    $archive_stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
