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
$valid_statuses = ['pending', 'validated', 'processing', 'ready_to_pickup', 'paid', 'completed', 'cancelled', 'declined'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Start transaction for atomic operations
    $conn->begin_transaction();

    // Get the current status, user_id and payment_method before updating
    $get_order_sql = "SELECT status, user_id, payment_method FROM printing_orders WHERE id = ?";
    $get_stmt = $conn->prepare($get_order_sql);
    $get_stmt->bind_param("i", $order_id);
    $get_stmt->execute();
    $get_result = $get_stmt->get_result();

    if ($get_result->num_rows === 0) {
        throw new Exception("Printing order not found.");
    }

    $order_data = $get_result->fetch_assoc();
    $old_status = $order_data['status'];
    $user_id = $order_data['user_id'];
    $payment_method = $order_data['payment_method'] ?? 'online';
    $get_stmt->close();

    // Update order status
    $sql = "UPDATE printing_orders SET status = ?" . ($decline_reason !== null ? ", decline_reason = ?" : "") . " WHERE id = ?";
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
    // For online payments: notify when status becomes 'processing' (after payment)
    // For in-person payments: notify when status becomes 'completed' (ready for pickup)
    $should_notify = false;
    if ($status !== $old_status && $user_id) {
        if ($payment_method === 'online' && $status === 'processing') {
            $should_notify = true;
        } elseif ($payment_method === 'in_person' && $status === 'completed') {
            $should_notify = true;
        }
    }

    if ($should_notify) {
        $status_labels = [
            'pending' => 'Pending',
            'validated' => 'Validated',
            'processing' => 'Processing',
            'ready_to_pickup' => 'Ready to Pick-Up',
            'paid' => 'Paid',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'declined' => 'Declined'
        ];

        $title = 'Printing Order Update';
        $message = 'Your printing order status has been updated to ' . $status_labels[$status] . '.';

        $notification_sql = "INSERT INTO notifications 
        (user_id, order_type, order_id, notification_type, title, message, old_status, new_status, is_read, created_at) 
        VALUES (?, 'printing_order', ?, 'status_changed', ?, ?, ?, ?, FALSE, NOW())";

        $notif_stmt = $conn->prepare($notification_sql);
        $notification_id = null;
        if ($notif_stmt) {
           $notif_stmt->bind_param(
                "iissss",
                $user_id,
                $order_id,
                $title,
                $message,
                $old_status,
                $status
            );
            $notif_stmt->execute();
            $notification_id = $notif_stmt->insert_id;
            $notif_stmt->close();
        }

        // If order is declined, create a conversation for messaging
        if ($status === 'declined' && $notification_id) {
            $conv_subject = 'Printing Order #' . $order_id . ' - Declined';
            $conv_sql = "INSERT INTO conversations 
            (user_id, order_type, order_id, notification_id, subject, created_at, updated_at) 
            VALUES (?, 'printing_order', ?, ?, ?, NOW(), NOW())";

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
                    $decline_msg = 'Your printing order has been declined. You can now edit your order in the Manage Printing Orders section.
                    Please reply to this message if you have any questions or would like to discuss this further.';
                    
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

    // If status is 'completed', update stock quantity
    if ($status === 'completed') {
        // Get order details for stock update
        $order_sql = "SELECT service, quantity FROM printing_orders WHERE id = ?";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param("i", $order_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();

        if ($order_result->num_rows > 0) {
            $order = $order_result->fetch_assoc();
            $service_name = $order['service'];
            $quantity = $order['quantity'];

            // Update stock quantity in print_services table
            $stock_sql = "UPDATE print_services SET stock_quantity = stock_quantity - ? WHERE service_name = ?";
            $stock_stmt = $conn->prepare($stock_sql);
            $stock_stmt->bind_param("is", $quantity, $service_name);

            if (!$stock_stmt->execute()) {
                throw new Exception("Failed to update stock quantity: " . $stock_stmt->error);
            }

            $stock_stmt->close();
        }
        $order_stmt->close();
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Printing order status updated successfully'
    ]);

    $stmt->close();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
