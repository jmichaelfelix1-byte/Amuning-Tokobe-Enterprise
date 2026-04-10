<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    // Fetch recent photo bookings with payment status (last 10)
    $sql = "SELECT pb.id, pb.name, pb.mobile, pb.event_type, pb.product, pb.duration, pb.package_type,
                   pb.event_date, pb.time_of_service, pb.booking_date, pb.status,
                   COALESCE(p.status, 'unpaid') as payment_status
            FROM photo_bookings pb
            LEFT JOIN payments p ON p.reference_id = pb.id AND p.payment_type = 'photo_booking'
            ORDER BY pb.booking_date DESC
            LIMIT 10";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    $photoBookings = [];

    while ($row = $result->fetch_assoc()) {
        // Combine event details
        $eventDetails = $row['event_type'] . ' - ' . $row['product'] . ' - ' .
                       $row['duration'] . ' - ' . $row['package_type'];

        // Map status to display text (same as in get_all_photo_bookings.php)
        $status = $row['status'];
        $order_status_display = 'Booking not yet process';
        
        if ($status === 'completed') {
            $order_status_display = 'BOOKING COMPLETED';
        } else if ($status === 'declined') {
            $order_status_display = 'BOOKING DECLINED';
        } else if ($status === 'processing') {
            $order_status_display = 'BOOKING PROCESSING';
        }

        $photoBookings[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'mobile' => $row['mobile'],
            'event_details' => $eventDetails,
            'event_date' => date('M d, Y', strtotime($row['event_date'])),
            'event_date_raw' => $row['event_date'],
            'time_of_service' => date('h:i A', strtotime($row['time_of_service'])),
            'booking_date' => date('M d, Y', strtotime($row['booking_date'])),
            'status' => $row['status'], // Changed from 'booking_status' to 'status'
            'order_status_display' => $order_status_display, // Add this field
            'payment_status' => $row['payment_status']
        ];
    }

    echo json_encode(['success' => true, 'data' => $photoBookings]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>