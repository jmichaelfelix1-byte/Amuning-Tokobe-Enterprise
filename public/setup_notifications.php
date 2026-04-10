<?php
/**
 * Setup script for notifications table
 * This file creates the notifications table if it doesn't exist
 * 
 * Access: http://localhost/Amuning/public/setup_notifications.php
 */

require_once 'includes/config.php';

// Only allow setup if explicitly enabled or during first setup
$setup_token = $_GET['token'] ?? '';
$expected_token = 'setup_notifications_' . md5('amuning_notifications');

if ($setup_token !== $expected_token && isset($_GET['token'])) {
    echo "Invalid setup token.";
    exit();
}

try {
    // Check if notifications table exists
    $check_sql = "SHOW TABLES LIKE 'notifications'";
    $result = $conn->query($check_sql);

    if ($result->num_rows > 0) {
        echo "✓ Notifications table already exists.";
        exit();
    }

    // Create the table
    $create_sql = <<<SQL
    CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        order_type ENUM('printing_order', 'photo_booking') NOT NULL,
        order_id INT NOT NULL,
        notification_type VARCHAR(100) NOT NULL COMMENT 'status_changed, payment_received, order_ready, etc.',
        title VARCHAR(255) NOT NULL,
        message TEXT,
        old_status VARCHAR(50),
        new_status VARCHAR(50),
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_is_read (is_read),
        INDEX idx_created_at (created_at)
    )
SQL;

    if ($conn->query($create_sql) === TRUE) {
        echo "✓ Notifications table created successfully!<br>";
        echo "You can now delete this file or visit <a href='inbox.php'>your inbox</a>.";
    } else {
        echo "✗ Error creating table: " . $conn->error;
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage();
}

$conn->close();
?>
