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
    // Get printing order details with user email from users table
    $sql = "SELECT po.*, u.email as user_email, COALESCE(p.status, 'unpaid') as payment_status
            FROM printing_orders po
            LEFT JOIN users u ON u.id = po.user_id
            LEFT JOIN payments p ON p.reference_id = po.id AND p.payment_type = 'printing_order'
            WHERE po.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }

    $order = $result->fetch_assoc();
    
    // Count files from JSON image_path
    $fileCount = 0;
    if (!empty($order['image_path'])) {
        $files = json_decode($order['image_path'], true);
        $fileCount = is_array($files) ? count($files) : 1;
    }
    $order['file_count'] = $fileCount;

    echo json_encode(['success' => true, 'data' => $order]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
