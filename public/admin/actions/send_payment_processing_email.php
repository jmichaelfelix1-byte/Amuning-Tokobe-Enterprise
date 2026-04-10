<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/email_payment.php';

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
$paymentDetailsJson = isset($_POST['payment_details']) ? $_POST['payment_details'] : '';

if (!$userEmail || !$userName || !$paymentDetailsJson) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    $paymentDetails = json_decode($paymentDetailsJson, true);
    if (!$paymentDetails) {
        echo json_encode(['success' => false, 'message' => 'Invalid payment details']);
        exit();
    }

    // Send the email
    $result = sendPaymentProcessingEmail($userEmail, $userName, $paymentDetails);

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
