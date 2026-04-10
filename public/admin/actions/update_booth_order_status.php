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

if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Order ID is required']);
    exit();
}

if (!isset($_POST['status']) || empty($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Status is required']);
    exit();
}

$order_id = intval($_POST['id']);
$status = trim($_POST['status']);
$decline_reason = isset($_POST['reason']) ? trim($_POST['reason']) : null;

// Validate status
$valid_statuses = ['pending', 'validated', 'booked', 'completed', 'cancelled', 'declined'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Get the current status and user_id before updating
    $get_order_sql = "SELECT status, email FROM photo_bookings WHERE id = ?";
    $get_stmt = $conn->prepare($get_order_sql);
    $get_stmt->bind_param("i", $order_id);
    $get_stmt->execute();
    $get_result = $get_stmt->get_result();

    if ($get_result->num_rows === 0) {
        throw new Exception("Booth order not found.");
    }

    $order_data = $get_result->fetch_assoc();
    $old_status = $order_data['status'];
    $customer_email = $order_data['email'];
    $get_stmt->close();

    // Get user_id from email
    $user_id = null;
    if ($customer_email) {
        $user_sql = "SELECT id FROM users WHERE email = ?";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param("s", $customer_email);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        if ($user_result->num_rows > 0) {
            $user_data = $user_result->fetch_assoc();
            $user_id = $user_data['id'];
        }
        $user_stmt->close();
    }

    $sql = "UPDATE photo_bookings SET status = ?" . ($decline_reason !== null ? ", decline_reason = ?" : "") . " WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    if ($decline_reason !== null) {
        $stmt->bind_param("ssi", $status, $decline_reason, $order_id);
    } else {
        $stmt->bind_param("si", $status, $order_id);
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    // Create notification if status changed and user_id exists
    if ($status !== $old_status && $user_id) {
        $status_labels = [
            'pending' => 'Pending',
            'validated' => 'Validated',
            'booked' => 'Booked',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'declined' => 'Declined'
        ];

        $title = 'Photo Booking Update';
        $message = 'Your photo booking #' . $order_id . ' status has been updated to ' . $status_labels[$status] . '.';

        $notification_sql = "INSERT INTO notifications 
        (user_id, order_type, order_id, notification_type, title, message, old_status, new_status, is_read, created_at) 
        VALUES (?, 'photo_booking', ?, 'status_changed', ?, ?, ?, ?, FALSE, NOW())";

        $notif_stmt = $conn->prepare($notification_sql);
        $notification_id = null;
        if ($notif_stmt) {
            $notif_stmt->bind_param("iissss", $user_id, $order_id, $title, $message, $old_status, $status);
            $notif_stmt->execute();
            $notification_id = $notif_stmt->insert_id;
            $notif_stmt->close();
        }

        // Send email based on new status
        require_once '../../includes/email_bookings.php';
        require_once '../../includes/email_templates.php';
        
        // Get full booking details for email
        $booking_sql = "SELECT * FROM photo_bookings WHERE id = ?";
        $booking_stmt = $conn->prepare($booking_sql);
        $booking_stmt->bind_param("i", $order_id);
        $booking_stmt->execute();
        $booking_result = $booking_stmt->get_result();
        
        if ($booking_result->num_rows > 0) {
            $booking_details = $booking_result->fetch_assoc();
            $customer_name = $booking_details['name'] ?? 'Valued Customer';
            
            // Send emails based on status
            if ($status === 'validated') {
                sendPhotoBookingValidatedEmail($customer_email, $customer_name, $booking_details);
            } elseif ($status === 'booked') {
                sendPhotoBookingBookedEmail($customer_email, $customer_name, $booking_details);
            } elseif ($status === 'completed') {
                sendPhotoBookingCompletedEmail($customer_email, $customer_name, $booking_details);
            } elseif ($status === 'cancelled') {
                // Optional: Send cancellation email
                // sendBookingCancellationEmail($customer_email, $customer_name, $booking_details);
            } elseif ($status === 'declined') {
                // Include decline reason in notification message
                if ($decline_reason) {
                    $message .= ' Reason: ' . $decline_reason;
                }
            }
        }
        $booking_stmt->close();

        // If order is declined, create a conversation for messaging
        if ($status === 'declined' && $notification_id) {
            $conv_subject = 'Photo Booking #' . $order_id . ' - Declined';
            $conv_sql = "INSERT INTO conversations 
            (user_id, order_type, order_id, notification_id, subject, created_at, updated_at) 
            VALUES (?, 'photo_booking', ?, ?, ?, NOW(), NOW())";

            $conv_stmt = $conn->prepare($conv_sql);
            if ($conv_stmt) {
                $conv_stmt->bind_param(
                    "iiss",
                    $user_id,
                    $order_id,
                    $notification_id,
                    $conv_subject
                );
                $conv_stmt->execute();
                $conversation_id = $conv_stmt->insert_id;
                $conv_stmt->close();

                // Create initial admin message explaining the decline
                if ($conversation_id) {
                    $admin_id = $_SESSION['user_id'] ?? 0;
                    $decline_msg = 'Your photo booking #' . $order_id . ' has been declined. Please reply to this message if you have any questions or would like to discuss this further.';
                    
                    $msg_sql = "INSERT INTO messages 
                    (conversation_id, sender_id, sender_type, message_text, created_at, is_read) 
                    VALUES (?, ?, 'admin', ?, NOW(), FALSE)";

                    $msg_stmt = $conn->prepare($msg_sql);
                    if ($msg_stmt) {
                        $msg_stmt->bind_param(
                            "iis",
                            $conversation_id,
                            $admin_id,
                            $decline_msg
                        );
                        $msg_stmt->execute();
                        $msg_stmt->close();
                    }
                }
            }
        }
    }

    if ($stmt->affected_rows === 0) {
        // If no rows affected, it means the status was already set to the new value
        // This is still a success case, just no change needed
    }

    echo json_encode([
        'success' => true,
        'message' => 'Booth order status updated successfully'
    ]);

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
