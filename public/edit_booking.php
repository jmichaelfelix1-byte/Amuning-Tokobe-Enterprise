<?php
session_start();
require_once 'includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $mobile = trim($_POST['mobile']);
        $event_type = trim($_POST['event_type']);
        $duration = trim($_POST['duration']);
        $event_date = $_POST['event_date'];
        $time_of_service = $_POST['time_of_service'];
        $venue = trim($_POST['venue']);
        $street_address = trim($_POST['street_address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $region = trim($_POST['region'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $country = trim($_POST['country'] ?? '');

        // Verify the booking belongs to the current user
        $verify_stmt = $conn->prepare("SELECT id, status FROM photo_bookings WHERE id = ? AND email = ?");
        $verify_stmt->bind_param("is", $id, $_SESSION['user_email']);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();

        if ($verify_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Booking not found or unauthorized']);
            $verify_stmt->close();
            exit;
        }

        $booking = $verify_result->fetch_assoc();
        $verify_stmt->close();

        // Check if booking is validated, paid, or processing
        if (in_array(strtolower($booking['status']), ['validated', 'booked', 'paid', 'processing', 'completed'])) {
            echo json_encode(['success' => false, 'message' => 'Cannot edit a validated, paid, or processing booking. Please contact support if you need to make changes.']);
            exit;
        }

        // Update the booking
        $update_stmt = $conn->prepare("
            UPDATE photo_bookings 
            SET name = ?, email = ?, mobile = ?, event_type = ?, duration = ?, 
                event_date = ?, time_of_service = ?, venue = ?, street_address = ?, 
                city = ?, region = ?, postal_code = ?, country = ?
            WHERE id = ? AND email = ?
        ");

        $update_stmt->bind_param(
            "sssssssssssssss",
            $name, $email, $mobile, $event_type, $duration,
            $event_date, $time_of_service, $venue, $street_address,
            $city, $region, $postal_code, $country,
            $id, $_SESSION['user_email']
        );

        if ($update_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Booking updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update booking: ' . $update_stmt->error]);
        }

        $update_stmt->close();

    } catch (Exception $e) {
        error_log("Edit booking error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
