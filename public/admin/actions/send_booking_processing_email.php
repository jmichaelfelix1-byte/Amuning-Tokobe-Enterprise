<?php
session_start();
require_once '../../includes/config.php';
require_once '../../includes/email_bookings.php';

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
$bookingDetailsJson = isset($_POST['booking_details']) ? $_POST['booking_details'] : '';

if (!$userEmail || !$userName || !$bookingDetailsJson) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    $bookingDetails = json_decode($bookingDetailsJson, true);
    if (!$bookingDetails) {
        echo json_encode(['success' => false, 'message' => 'Invalid booking details']);
        exit();
    }

    // Send the email
    $result = sendBookingProcessingEmail($userEmail, $userName, $bookingDetails);

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
