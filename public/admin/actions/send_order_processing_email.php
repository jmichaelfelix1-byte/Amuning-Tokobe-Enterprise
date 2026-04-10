<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/email_orders.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$userEmail = isset($_POST['email']) ? trim($_POST['email']) : '';
$userName = isset($_POST['name']) ? trim($_POST['name']) : '';
$orderDetailsJson = isset($_POST['order_details']) ? $_POST['order_details'] : '';

if (!$userEmail || !$userName || !$orderDetailsJson) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    $orderDetails = json_decode($orderDetailsJson, true);
    if (!$orderDetails) {
        echo json_encode(['success' => false, 'message' => 'Invalid order details']);
        exit();
    }

    // Send the email
    $result = sendOrderProcessingEmail($userEmail, $userName, $orderDetails);

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
