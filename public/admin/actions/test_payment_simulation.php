<?php
session_start();
require_once '../../includes/config.php';

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

if (!isset($_POST['booking_id']) || empty($_POST['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
    exit();
}

$booking_id = intval($_POST['booking_id']);

try {
    // Check if booking exists
    $booking_check = "SELECT id, status FROM photo_bookings WHERE id = ?";
    $stmt = $conn->prepare($booking_check);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Booking not found.");
    }
    
    $booking = $result->fetch_assoc();
    $current_status = $booking['status'];
    $stmt->close();
    
    // Check if payment record exists for this booking
    $payment_check = "SELECT id FROM payments WHERE reference_id = ? AND payment_type = 'photo_booking'";
    $stmt = $conn->prepare($payment_check);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $payment_result = $stmt->get_result();
    $payment_exists = $payment_result->num_rows > 0;
    $stmt->close();
    
    // Generate a test transaction number
    $test_transaction_number = 'TEST_' . date('YmdHis') . '_' . $booking_id;
    
    // First, get the booking details to get email and amount
    $booking_details = "SELECT email, estimated_price, travel_fee FROM photo_bookings WHERE id = ?";
    $stmt = $conn->prepare($booking_details);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $booking_result = $stmt->get_result();
    $booking_data = $booking_result->fetch_assoc();
    $stmt->close();
    
    $customer_email = $booking_data['email'];
    $amount = floatval($booking_data['estimated_price']) + floatval($booking_data['travel_fee'] ?? 0);
    
    // Get user_id from email
    $user_id = null;
    $user_sql = "SELECT id FROM users WHERE email = ?";
    $user_stmt = $conn->prepare($user_sql);
    if ($user_stmt) {
        $user_stmt->bind_param("s", $customer_email);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        if ($user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();
            $user_id = $user_data['id'];
        }
        $user_stmt->close();
    }
    
    if ($payment_exists) {
        // Update existing payment to 'paid'
        $update_payment = "UPDATE payments SET status = 'paid', transaction_number = ? WHERE reference_id = ? AND payment_type = 'photo_booking'";
        $stmt = $conn->prepare($update_payment);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("si", $test_transaction_number, $booking_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
    } else {
        // Insert new payment record with 'paid' status
        $insert_payment = "INSERT INTO payments (user_id, user_email, reference_id, payment_type, amount, status, payment_method, transaction_number)
                          VALUES (?, ?, ?, 'photo_booking', ?, 'paid', 'test', ?)";
        $stmt = $conn->prepare($insert_payment);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("isids", $user_id, $customer_email, $booking_id, $amount, $test_transaction_number);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
    }
    
    // Now automatically update the booking status to 'booked' if it's currently 'validated'
    $order_status_updated = false;
    if ($current_status === 'validated') {
        $update_booking = "UPDATE photo_bookings SET status = 'booked' WHERE id = ? AND status = 'validated'";
        $stmt = $conn->prepare($update_booking);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $booking_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $order_status_updated = ($stmt->affected_rows > 0);
        $stmt->close();
        
        // Create a notification for the status change
        if ($order_status_updated) {
            $user_sql = "SELECT id FROM users WHERE email = (SELECT email FROM photo_bookings WHERE id = ?)";
            $stmt = $conn->prepare($user_sql);
            if ($stmt) {
                $stmt->bind_param("i", $booking_id);
                $stmt->execute();
                $user_result = $stmt->get_result();
                if ($user_result->num_rows > 0) {
                    $user_data = $user_result->fetch_assoc();
                    $user_id = $user_data['id'];
                    
                    $notification_sql = "INSERT INTO notifications 
                    (user_id, order_type, order_id, notification_type, title, message, old_status, new_status, is_read, created_at) 
                    VALUES (?, 'photo_booking', ?, 'status_changed', 'Photo Booking Update', 
                            CONCAT('Your photo booking #', ?, ' status has been updated to Booked.'), 'validated', 'booked', FALSE, NOW())";
                    
                    $notif_stmt = $conn->prepare($notification_sql);
                    if ($notif_stmt) {
                        $notif_stmt->bind_param("iii", $user_id, $booking_id, $booking_id);
                        $notif_stmt->execute();
                        $notif_stmt->close();
                    }
                }
                $stmt->close();
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Test payment processed successfully. Payment status changed to Paid and order status updated to Booked.',
        'payment_status' => 'paid',
        'order_status' => 'booked',
        'transaction_number' => $test_transaction_number
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
