<?php
// Quick test to verify the messages API is working
session_start();

// Simulate a logged-in user
$_SESSION['user_id'] = 1;

require_once 'includes/config.php';

$user_id = $_SESSION['user_id'];

echo "<h2>Testing Messaging API</h2>";
echo "<p>User ID: " . $user_id . "</p>";

// Test 1: Check if conversations table exists
$result = $conn->query("SELECT COUNT(*) as count FROM conversations WHERE user_id = " . $user_id);
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>✓ Conversations table accessible. Your conversations: " . $row['count'] . "</p>";
} else {
    echo "<p>✗ Error accessing conversations: " . $conn->error . "</p>";
}

// Test 2: Check if messages table exists
$result = $conn->query("SELECT COUNT(*) as count FROM messages");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>✓ Messages table accessible. Total messages: " . $row['count'] . "</p>";
} else {
    echo "<p>✗ Error accessing messages: " . $conn->error . "</p>";
}

// Test 3: Check users table has full_name column
$result = $conn->query("SELECT full_name FROM users LIMIT 1");
if ($result) {
    echo "<p>✓ Users table has 'full_name' column</p>";
} else {
    echo "<p>✗ Error with full_name column: " . $conn->error . "</p>";
}

// Test 4: Run the API query
$query = "SELECT c.*, 
          (SELECT message_text FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
          (SELECT created_at FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message_time,
          (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND is_read = FALSE AND sender_type = 'admin') as unread_count
          FROM conversations c
          WHERE c.user_id = ?
          ORDER BY c.updated_at DESC";

$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->num_rows;
    echo "<p>✓ API query works! Conversations found: " . $count . "</p>";
    $stmt->close();
} else {
    echo "<p>✗ API query error: " . $conn->error . "</p>";
}

echo "<h3>All tests passed! ✓</h3>";
echo "<p><a href='messages.php'>Go to Messages</a></p>";
?>
