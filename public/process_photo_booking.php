<?php
header('Content-Type: application/json');

// Set timezone to Manila, Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

// Database configuration
include 'includes/config.php';
include 'includes/email_functions.php'; // Now includes all organized email modules

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
$required_fields = ['name', 'email', 'mobile', 'eventType', 'product', 'eventDate', 'timeOfService', 'venue', 'street', 'city', 'region', 'postal', 'country', 'packageType', 'duration', 'price'];

foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required field: ' . $field
        ]);
        exit();
    }
}

// Prepare SQL statement
$stmt = $conn->prepare("INSERT INTO photo_bookings (name, email, mobile, event_type, product, duration, package_type, event_date, time_of_service, venue, street_address, city, region, postal_code, country, remarks, estimated_price, travel_fee, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'SQL prepare failed: ' . $conn->error
    ]);
    exit();
}

// Bind parameters
$remarks = isset($data['remarks']) ? $data['remarks'] : '';
$travel_fee = isset($data['travelFee']) ? $data['travelFee'] : '0';

$stmt->bind_param(
    "ssssssssssssssssss",
    $data['name'],
    $data['email'],
    $data['mobile'],
    $data['eventType'],
    $data['product'],
    $data['duration'],
    $data['packageType'],
    $data['eventDate'],
    $data['timeOfService'],
    $data['venue'],
    $data['street'],
    $data['city'],
    $data['region'],
    $data['postal'],
    $data['country'],
    $remarks,
    $data['price'],
    $travel_fee
);

// Execute statement
if ($stmt->execute()) {
    $booking_id = $stmt->insert_id;
    
    // Prepare booking details for email
    $bookingDetails = [
        'booking_id' => $booking_id,
        'event_type' => $data['eventType'],
        'product' => $data['product'],
        'package_type' => $data['packageType'],
        'duration' => $data['duration'],
        'event_date' => $data['eventDate'],
        'time_of_service' => $data['timeOfService'],
        'venue' => $data['venue'],
        'street_address' => $data['street'],
        'city' => $data['city'],
        'region' => $data['region'],
        'postal_code' => $data['postal'],
        'country' => $data['country'],
        'estimated_price' => (float) str_replace(['₱', ','], '', $data['price']),
        'travel_fee' => (float) str_replace(['₱', ','], '', $travel_fee),
        'status' => 'pending'
    ];
    
    // Send received booking email
    $emailResult = sendPhotoBookingReceivedEmail($data['email'], $data['name'], $bookingDetails);
    
    echo json_encode([
        'success' => true,
        'message' => 'Booking successfully saved! Confirmation email sent.',
        'booking_id' => $booking_id,
        'email_sent' => $emailResult['success']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save booking: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
