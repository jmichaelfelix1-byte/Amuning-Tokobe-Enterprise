<?php
session_start();
require_once '../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    // Get all conversations for current user
    if ($action === 'get_conversations') {
        $query = "SELECT c.*, 
                  (SELECT message_text FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
                  (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
                  (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND is_read = FALSE AND sender_type = 'admin') as unread_count,
                  COALESCE(
                    (SELECT decline_reason FROM printing_orders WHERE id = c.order_id AND c.order_type = 'printing_order'),
                    (SELECT decline_reason FROM photo_bookings WHERE id = c.order_id AND c.order_type = 'photo_booking')
                  ) as decline_reason
                  FROM conversations c
                  WHERE c.user_id = ?
                  ORDER BY c.updated_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $conversations = [];
        while ($row = $result->fetch_assoc()) {
            $conversations[] = $row;
        }
        
        echo json_encode(['success' => true, 'conversations' => $conversations]);
        $stmt->close();
        exit();
    }

    // Get messages for a specific conversation
    if ($action === 'get_messages') {
        $conversation_id = intval($_GET['conversation_id'] ?? 0);
        
        if ($conversation_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid conversation ID']);
            exit();
        }
        
        // Verify user owns this conversation
        $verify_query = "SELECT user_id FROM conversations WHERE id = ?";
        $verify_stmt = $conn->prepare($verify_query);
        $verify_stmt->bind_param("i", $conversation_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows === 0 || $verify_result->fetch_assoc()['user_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            $verify_stmt->close();
            exit();
        }
        $verify_stmt->close();
        
        // Get all messages in conversation
        $query = "SELECT m.*, 
                  COALESCE(u.full_name, 'Admin') as sender_name
                  FROM messages m
                  LEFT JOIN users u ON m.sender_id = u.id
                  WHERE m.conversation_id = ?
                  ORDER BY m.created_at ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $conversation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $messages = [];
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        // Mark messages as read
        $update_query = "UPDATE messages SET is_read = TRUE, read_at = NOW() WHERE conversation_id = ? AND sender_type = 'admin' AND is_read = FALSE";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $conversation_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        echo json_encode(['success' => true, 'messages' => $messages]);
        $stmt->close();
        exit();
    }

    // Send a message
    if ($action === 'send_message') {
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        $message_text = trim($_POST['message_text'] ?? '');
        
        if ($conversation_id <= 0 || empty($message_text)) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit();
        }
        
        // Verify user owns this conversation
        $verify_query = "SELECT user_id FROM conversations WHERE id = ?";
        $verify_stmt = $conn->prepare($verify_query);
        $verify_stmt->bind_param("i", $conversation_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if ($verify_result->num_rows === 0 || $verify_result->fetch_assoc()['user_id'] != $user_id) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
            $verify_stmt->close();
            exit();
        }
        $verify_stmt->close();
        
        // Insert message
        $insert_query = "INSERT INTO messages (conversation_id, sender_id, sender_type, message_text, created_at, is_read) 
                         VALUES (?, ?, 'user', ?, NOW(), FALSE)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iis", $conversation_id, $user_id, $message_text);
        
        if ($insert_stmt->execute()) {
            // Update conversation timestamp
            $update_conv = "UPDATE conversations SET updated_at = NOW() WHERE id = ?";
            $update_stmt = $conn->prepare($update_conv);
            $update_stmt->bind_param("i", $conversation_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            echo json_encode(['success' => true, 'message' => 'Message sent successfully', 'message_id' => $insert_stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send message']);
        }
        $insert_stmt->close();
        exit();
    }

    // Create or get conversation for a notification
    if ($action === 'start_conversation') {
        $notification_id = intval($_POST['notification_id'] ?? 0);
        
        if ($notification_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
            exit();
        }
        
        // Get notification details
        $notif_query = "SELECT * FROM notifications WHERE id = ? AND user_id = ?";
        $notif_stmt = $conn->prepare($notif_query);
        $notif_stmt->bind_param("ii", $notification_id, $user_id);
        $notif_stmt->execute();
        $notif_result = $notif_stmt->get_result();
        
        if ($notif_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Notification not found']);
            $notif_stmt->close();
            exit();
        }
        
        $notification = $notif_result->fetch_assoc();
        $notif_stmt->close();
        
        // Check if conversation already exists
        $check_query = "SELECT id FROM conversations WHERE notification_id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $notification_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Conversation exists, return it
            $conv_data = $check_result->fetch_assoc();
            echo json_encode(['success' => true, 'conversation_id' => $conv_data['id']]);
            $check_stmt->close();
            exit();
        }
        $check_stmt->close();
        
        // Create new conversation
        $order_type = $notification['order_type'];
        $order_id = $notification['order_id'];
        $subject = ucfirst(str_replace('_', ' ', $order_type)) . ' #' . $order_id . ' - Declined';
        
        $insert_query = "INSERT INTO conversations 
                        (user_id, order_type, order_id, notification_id, subject, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param(
            "iisis",
            $user_id,
            $order_type,
            $order_id,
            $notification_id,
            $subject
        );
        
        if ($insert_stmt->execute()) {
            $conversation_id = $insert_stmt->insert_id;
            
            // Post initial admin message
            $admin_id = 1; // Default admin user - adjust if needed
            $decline_msg = 'Your ' . str_replace('_', ' ', $order_type) . ' #' . $order_id . ' has been declined. Please reply to this message if you have any questions or would like to discuss this further.';
            
            $msg_query = "INSERT INTO messages 
                         (conversation_id, sender_id, sender_type, message_text, created_at, is_read) 
                         VALUES (?, ?, 'admin', ?, NOW(), FALSE)";
            
            $msg_stmt = $conn->prepare($msg_query);
            $msg_stmt->bind_param("iis", $conversation_id, $admin_id, $decline_msg);
            $msg_stmt->execute();
            $msg_stmt->close();
            
            echo json_encode(['success' => true, 'conversation_id' => $conversation_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create conversation']);
        }
        $insert_stmt->close();
        exit();
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit();
?>
