<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin' || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$admin_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    // Get all conversations (for admin dashboard)
    if ($action === 'get_all_conversations') {
        $query = "SELECT c.*, 
                  COALESCE(u.full_name, 'User') as user_name,
                  u.email as user_email,
                  (SELECT message_text FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
                  (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
                  (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND is_read = FALSE AND sender_type = 'user') as unread_count,
                  COALESCE(
                    (SELECT decline_reason FROM printing_orders WHERE id = c.order_id AND c.order_type = 'printing_order'),
                    (SELECT decline_reason FROM photo_bookings WHERE id = c.order_id AND c.order_type = 'photo_booking')
                  ) as decline_reason
                  FROM conversations c
                  LEFT JOIN users u ON c.user_id = u.id
                  ORDER BY c.updated_at DESC";
        
        $stmt = $conn->prepare($query);
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

    // Get messages for a specific conversation (admin view)
    if ($action === 'get_conversation') {
        $conversation_id = intval($_GET['conversation_id'] ?? 0);
        
        if ($conversation_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid conversation ID']);
            exit();
        }
        
        // Get conversation details
        $conv_query = "SELECT c.*, 
                       COALESCE(u.full_name, 'User') as user_name,
                       u.email as user_email
                       FROM conversations c
                       LEFT JOIN users u ON c.user_id = u.id
                       WHERE c.id = ?";
        
        $conv_stmt = $conn->prepare($conv_query);
        $conv_stmt->bind_param("i", $conversation_id);
        $conv_stmt->execute();
        $conv_result = $conv_stmt->get_result();
        
        if ($conv_result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Conversation not found']);
            $conv_stmt->close();
            exit();
        }
        
        $conversation = $conv_result->fetch_assoc();
        $conv_stmt->close();
        
        // Get all messages in conversation
        $msg_query = "SELECT m.*, 
                      COALESCE(u.full_name, 'Admin') as sender_name
                      FROM messages m
                      LEFT JOIN users u ON m.sender_id = u.id
                      WHERE m.conversation_id = ?
                      ORDER BY m.created_at ASC";
        
        $msg_stmt = $conn->prepare($msg_query);
        $msg_stmt->bind_param("i", $conversation_id);
        $msg_stmt->execute();
        $msg_result = $msg_stmt->get_result();
        
        $messages = [];
        while ($row = $msg_result->fetch_assoc()) {
            $messages[] = $row;
        }
        
        // Mark messages as read
        $update_query = "UPDATE messages SET is_read = TRUE, read_at = NOW() WHERE conversation_id = ? AND sender_type = 'user' AND is_read = FALSE";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("i", $conversation_id);
        $update_stmt->execute();
        $update_stmt->close();
        
        echo json_encode([
            'success' => true, 
            'conversation' => $conversation,
            'messages' => $messages
        ]);
        $msg_stmt->close();
        exit();
    }

    // Send a message (admin reply)
    if ($action === 'send_message') {
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        $message_text = trim($_POST['message_text'] ?? '');
        
        if ($conversation_id <= 0 || empty($message_text)) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit();
        }
        
        // Insert message
        $insert_query = "INSERT INTO messages (conversation_id, sender_id, sender_type, message_text, created_at, is_read) 
                         VALUES (?, ?, 'admin', ?, NOW(), FALSE)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iis", $conversation_id, $admin_id, $message_text);
        
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

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
exit();
?>
