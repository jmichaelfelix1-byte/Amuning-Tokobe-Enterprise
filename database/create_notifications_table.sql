-- Create notifications table
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
);
