<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

try {
    // Fetch all photo bookings with separate payment and order status
    // Use a subquery to get only the LATEST payment for each booking to avoid duplicate/old payment records
    $sql = "SELECT pb.id, pb.name, pb.email, pb.mobile, pb.event_type, pb.product, pb.duration, pb.package_type,
                   pb.event_date, pb.time_of_service, pb.venue, pb.street_address, pb.city, pb.region,
                   pb.postal_code, pb.country, pb.remarks, pb.estimated_price, pb.travel_fee, pb.booking_date, pb.status as order_status, pb.user_archived, pb.admin_archived,
                   COALESCE(
                       CASE
                           WHEN p.status = 'paid' THEN 'Paid'
                           ELSE NULL
                       END,
                       'Payment Not Submitted'
                   ) as payment_status_display,
                   CASE
                       WHEN pb.status = 'pending' THEN 'Booking not yet processed'
                       WHEN pb.status = 'validated' THEN 'Booking Validated'
                       WHEN pb.status = 'booked' THEN 'Booked'
                       WHEN pb.status = 'processing' THEN 'Booking processing'
                       WHEN pb.status = 'completed' THEN 'Booking Completed'
                       WHEN pb.status = 'cancelled' THEN 'Booking Cancelled'
                       WHEN pb.status = 'declined' THEN 'Booking Declined'
                       ELSE 'Booking not yet processed'
                   END as order_status_display,
                   CASE WHEN p.id IS NULL THEN 'unpaid' ELSE p.status END as payment_status_raw
            FROM photo_bookings pb
            LEFT JOIN (
                SELECT * FROM payments 
                WHERE payment_type = 'photo_booking'
                ORDER BY created_at DESC
            ) p ON p.reference_id = pb.id AND p.id = (
                SELECT id FROM payments 
                WHERE reference_id = pb.id AND payment_type = 'photo_booking'
                ORDER BY created_at DESC
                LIMIT 1
            )
            ORDER BY pb.id DESC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    $photoBookings = [];

    while ($row = $result->fetch_assoc()) {
        $photoBookings[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'mobile' => $row['mobile'],
            'event_type' => $row['event_type'],
            'product' => $row['product'],
            'duration' => $row['duration'],
            'package_type' => $row['package_type'],
            'event_date' => $row['event_date'],
            'time_of_service' => $row['time_of_service'],
            'venue' => $row['venue'],
            'street_address' => $row['street_address'],
            'city' => $row['city'],
            'region' => $row['region'],
            'postal_code' => $row['postal_code'],
            'country' => $row['country'],
            'remarks' => $row['remarks'],
            'estimated_price' => $row['estimated_price'],
            'travel_fee' => $row['travel_fee'],
            'date' => $row['booking_date'],
            'status' => $row['order_status'],
            'payment_status' => $row['payment_status_raw'],
            'payment_status_display' => $row['payment_status_display'],
            'order_status_display' => $row['order_status_display'],
            'user_archived' => (int)$row['user_archived'],
            'admin_archived' => (int)$row['admin_archived']
        ];
    }

    echo json_encode(['success' => true, 'data' => $photoBookings]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
