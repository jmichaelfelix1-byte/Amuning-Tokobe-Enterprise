<?php
header('Content-Type: application/json');

// Database configuration
include 'includes/config.php';
include 'includes/email_functions.php';

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
if (!isset($data['booking_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit();
}

$booking_id = (int)$data['booking_id'];

// Update booking status to 'paid'
$stmt = $conn->prepare("UPDATE photo_bookings SET status = 'paid' WHERE id = ?");
$stmt->bind_param("i", $booking_id);

if ($stmt->execute()) {
    // Fetch updated booking details
    $stmt2 = $conn->prepare("SELECT * FROM photo_bookings WHERE id = ?");
    $stmt2->bind_param("i", $booking_id);
    $stmt2->execute();
    $result = $stmt2->get_result();

    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();

        // Prepare booking details for email
        $bookingDetails = [
            'booking_id' => $booking['id'],
            'event_type' => $booking['event_type'],
            'product' => $booking['product'],
            'package_type' => $booking['package_type'],
            'duration' => $booking['duration'],
            'event_date' => $booking['event_date'],
            'time_of_service' => $booking['time_of_service'],
            'venue' => $booking['venue'],
            'street_address' => $booking['street_address'],
            'city' => $booking['city'],
            'region' => $booking['region'],
            'postal_code' => $booking['postal_code'],
            'country' => $booking['country'],
            'estimated_price' => (float) str_replace(['₱', ','], '', $booking['estimated_price']),
            'status' => 'paid'
        ];

        // Send payment confirmation email
        $emailResult = sendPhotoPaymentConfirmationEmail($booking['email'], $booking['name'], $bookingDetails);

        echo json_encode([
            'success' => true,
            'message' => 'Payment confirmed successfully!',
            'email_sent' => $emailResult['success']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Booking not found'
        ]);
    }

    $stmt2->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update booking status: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
