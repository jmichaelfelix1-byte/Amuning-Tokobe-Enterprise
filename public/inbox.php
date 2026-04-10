<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] != 'user' && $_SESSION['user_type'] != 'admin')) {
    header('Location: signin.php');
    exit();
}

require_once 'includes/config.php';

$user_id = $_SESSION['user_id'];
$page_title = 'Notifications - Amuning Tokobe Enterprise';

// Handle marking notification as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'mark_as_read') {
        $notification_id = intval($_POST['notification_id'] ?? 0);
        
        if ($notification_id > 0) {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $notification_id, $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update notification']);
            }
            $stmt->close();
        }
        exit();
    }
    
    if ($_POST['action'] === 'mark_all_as_read') {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE, read_at = NOW() WHERE user_id = ? AND is_read = FALSE");
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
        }
        $stmt->close();
        exit();
    }
    
    if ($_POST['action'] === 'delete') {
        $notification_id = intval($_POST['notification_id'] ?? 0);
        
        if ($notification_id > 0) {
            $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $notification_id, $user_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete notification']);
            }
            $stmt->close();
        }
        exit();
    }
}

// Get all notifications for user
$query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

// Count unread notifications
$unread_count_query = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = FALSE";
$unread_stmt = $conn->prepare($unread_count_query);
$unread_stmt->bind_param("i", $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_data = $unread_result->fetch_assoc();
$unread_count = $unread_data['unread'];
$unread_stmt->close();

$additional_css = ['common.css', 'index.css', 'inbox.css'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="icon" type="image/x-icon" href="../images/amuninglogo.ico">
    <link rel="shortcut icon" href="../images/amuninglogo.ico" type="image/x-icon">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/index.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="inbox-container">
        <div class="inbox-header">
            <div>
                <h1><i class="fas fa-bell"></i> Notifications</h1>
                <?php if ($unread_count > 0): ?>
                    <p class="notification-timestamp"><?php echo $unread_count; ?> unread notification<?php echo $unread_count !== 1 ? 's' : ''; ?></p>
                <?php endif; ?>
            </div>
            <?php if ($unread_count > 0): ?>
                <div class="inbox-actions">
                    <button class="btn-mark-all" onclick="markAllAsRead()">
                        <i class="fas fa-check"></i> Mark All as Read
                    </button>
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($notifications)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>No notifications yet</p>
                <p style="font-size: 0.95rem;">Check back later for updates about your orders</p>
            </div>
        <?php else: ?>
            <!-- Notification Tabs -->
            <div class="inbox-tabs">
                <button class="tab-button active" onclick="switchTab('unread')">
                    <i class="fas fa-envelope"></i> Unread
                    <span class="tab-badge" id="unread-count"><?php 
                        $unread = array_filter($notifications, function($n) { return !$n['is_read']; });
                        echo count($unread);
                    ?></span>
                </button>
                <button class="tab-button" onclick="switchTab('read')">
                    <i class="fas fa-envelope-open"></i> Read
                    <span class="tab-badge" id="read-count"><?php 
                        $read = array_filter($notifications, function($n) { return $n['is_read']; });
                        echo count($read);
                    ?></span>
                </button>
            </div>

            <!-- Unread Tab Content -->
            <div id="unread" class="tab-content active">
                <?php 
                $unread_notifications = array_filter($notifications, function($n) { return !$n['is_read']; });
                if (empty($unread_notifications)): 
                ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>All caught up!</p>
                        <p style="font-size: 0.95rem;">You have no unread notifications</p>
                    </div>
                <?php else: ?>
                    <div class="notifications-list">
                        <?php foreach ($unread_notifications as $notification): ?>
                            <div class="notification-item unread" id="notification-<?php echo $notification['id']; ?>">
                                <div class="notification-header">
                                    <div>
                                        <span class="notification-type-badge badge-<?php echo str_replace('_', '-', $notification['notification_type']); ?>">
                                            <?php 
                                            $type_labels = [
                                                'status_changed' => 'Status Update',
                                                'payment_received' => 'Payment Received',
                                                'order_ready' => 'Order Ready',
                                                'order_completed' => 'Order Completed',
                                                'payment_approved' => 'Payment Approved'
                                            ];
                                            echo $type_labels[$notification['notification_type']] ?? ucwords(str_replace('_', ' ', $notification['notification_type']));
                                            ?>
                                        </span>
                                        <h3 class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></h3>
                                    </div>
                                    <p class="notification-timestamp"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></p>
                                </div>

                                <?php if (!empty($notification['message'])): ?>
                                    <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <?php endif; ?>



                                <?php if (!empty($notification['old_status']) && !empty($notification['new_status'])): ?>
                                    <div class="notification-details">
                                        <strong>Status Change:</strong>
                                        <div class="status-change">
                                            <span class="status-badge status-old"><?php echo ucfirst($notification['old_status']); ?></span>
                                            <i class="fas fa-arrow-right" style="color: #cbd5e1;"></i>
                                            <span class="status-badge status-new"><?php echo ucfirst($notification['new_status']); ?></span>
                                        </div>
                                        <p style="margin: 8px 0 0 0;">
                                            <strong>Order Type:</strong> <?php echo ucwords(str_replace('_', ' ', $notification['order_type'])); ?> 
                                            <strong style="margin-left: 15px;">Order ID:</strong> #<?php echo $notification['order_id']; ?>
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <div class="notification-actions">
                                    <button class="btn-mark-read" onclick="markAsRead(<?php echo $notification['id']; ?>)">
                                        <i class="fas fa-envelope-open"></i> Mark as Read
                                    </button>
                                    <?php if (strtolower($notification['new_status']) === 'booked' && strtolower($notification['order_type']) === 'photo_booking'): ?>
                                        <a href="download_booking_receipt.php?booking_id=<?php echo $notification['order_id']; ?>" target="_blank" class="btn-download" style="background: #22c55e; color: white; text-decoration: none; padding: 8px 16px; border-radius: 4px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 500;">
                                            <i class="fas fa-download"></i> Download Receipt
                                        </a>
                                    <?php elseif (strtolower($notification['order_type']) === 'printing_order' && (strtolower($notification['new_status']) === 'processing' || strtolower($notification['new_status']) === 'completed')): ?>
                                        <a href="download_printing_receipt.php?order_id=<?php echo $notification['order_id']; ?>" target="_blank" class="btn-download" style="background: #22c55e; color: white; text-decoration: none; padding: 8px 16px; border-radius: 4px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 500;">
                                            <i class="fas fa-download"></i> Download Receipt
                                        </a>
                                    <?php endif; ?>
                                    <?php if (strtolower($notification['new_status']) === 'declined'): ?>
                                        <button class="btn-reply" onclick="replyToNotification(<?php echo $notification['id']; ?>, <?php echo $notification['order_id']; ?>)" style="background: #f5276c; color: white;">
                                            <i class="fas fa-reply"></i> Reply to Admin
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn-delete" onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Read Tab Content -->
            <div id="read" class="tab-content">
                <?php 
                $read_notifications = array_filter($notifications, function($n) { return $n['is_read']; });
                if (empty($read_notifications)): 
                ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <p>No read notifications</p>
                        <p style="font-size: 0.95rem;">Your read notifications will appear here</p>
                    </div>
                <?php else: ?>
                    <div class="notifications-list">
                        <?php foreach ($read_notifications as $notification): ?>
                            <div class="notification-item" id="notification-<?php echo $notification['id']; ?>">
                                <div class="notification-header">
                                    <div>
                                        <span class="notification-type-badge badge-<?php echo str_replace('_', '-', $notification['notification_type']); ?>">
                                            <?php 
                                            $type_labels = [
                                                'status_changed' => 'Status Update',
                                                'payment_received' => 'Payment Received',
                                                'order_ready' => 'Order Ready',
                                                'order_completed' => 'Order Completed',
                                                'payment_approved' => 'Payment Approved'
                                            ];
                                            echo $type_labels[$notification['notification_type']] ?? ucwords(str_replace('_', ' ', $notification['notification_type']));
                                            ?>
                                        </span>
                                        <h3 class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></h3>
                                    </div>
                                    <p class="notification-timestamp"><?php echo date('M d, Y H:i', strtotime($notification['created_at'])); ?></p>
                                </div>

                                <?php if (!empty($notification['message'])): ?>
                                    <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                                <?php endif; ?>



                                <?php if (!empty($notification['old_status']) && !empty($notification['new_status'])): ?>
                                    <div class="notification-details">
                                        <strong>Status Change:</strong>
                                        <div class="status-change">
                                            <span class="status-badge status-old"><?php echo ucfirst($notification['old_status']); ?></span>
                                            <i class="fas fa-arrow-right" style="color: #cbd5e1;"></i>
                                            <span class="status-badge status-new"><?php echo ucfirst($notification['new_status']); ?></span>
                                        </div>
                                        <p style="margin: 8px 0 0 0;">
                                            <strong>Order Type:</strong> <?php echo ucwords(str_replace('_', ' ', $notification['order_type'])); ?> 
                                            <strong style="margin-left: 15px;">Order ID:</strong> #<?php echo $notification['order_id']; ?>
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <div class="notification-actions">
                                    <?php if (strtolower($notification['new_status']) === 'booked' && strtolower($notification['order_type']) === 'photo_booking'): ?>
                                        <a href="download_booking_receipt.php?booking_id=<?php echo $notification['order_id']; ?>" target="_blank" class="btn-download" style="background: #22c55e; color: white; text-decoration: none; padding: 8px 16px; border-radius: 4px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 500;">
                                            <i class="fas fa-download"></i> Download Receipt
                                        </a>
                                    <?php elseif (strtolower($notification['order_type']) === 'printing_order' && (strtolower($notification['new_status']) === 'processing' || strtolower($notification['new_status']) === 'completed')): ?>
                                        <a href="download_printing_receipt.php?order_id=<?php echo $notification['order_id']; ?>" target="_blank" class="btn-download" style="background: #22c55e; color: white; text-decoration: none; padding: 8px 16px; border-radius: 4px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 500;">
                                            <i class="fas fa-download"></i> Download Receipt
                                        </a>
                                    <?php endif; ?>
                                    <?php if (strtolower($notification['new_status']) === 'declined'): ?>
                                        <button class="btn-reply" onclick="replyToNotification(<?php echo $notification['id']; ?>, <?php echo $notification['order_id']; ?>)" style="background: #f5276c; color: white;">
                                            <i class="fas fa-reply"></i> Reply to Admin
                                        </button>
                                    <?php endif; ?>
                                    <button class="btn-delete" onclick="deleteNotification(<?php echo $notification['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/script.js"></script>
    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => {
                content.classList.remove('active');
            });

            // Remove active class from all buttons
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(button => {
                button.classList.remove('active');
            });

            // Show selected tab content
            const selectedContent = document.getElementById(tabName);
            if (selectedContent) {
                selectedContent.classList.add('active');
            }

            // Mark selected button as active
            event.target.closest('.tab-button').classList.add('active');
        }

        function markAsRead(notificationId) {
            fetch('inbox.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_as_read&notification_id=' + notificationId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const element = document.getElementById('notification-' + notificationId);
                    if (element) {
                        element.classList.remove('unread');
                        element.querySelector('.btn-mark-read').remove();
                        location.reload(); // Reload to update unread count
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function markAllAsRead() {
            fetch('inbox.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=mark_all_as_read'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function deleteNotification(notificationId) {
            if (confirm('Are you sure you want to delete this notification?')) {
                fetch('inbox.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=delete&notification_id=' + notificationId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const element = document.getElementById('notification-' + notificationId);
                        if (element) {
                            element.style.animation = 'fadeOut 0.3s ease-out';
                            setTimeout(() => {
                                element.remove();
                                // Check if there are any notifications left
                                if (document.querySelectorAll('.notification-item').length === 0) {
                                    location.reload();
                                }
                            }, 300);
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }

        function replyToNotification(notificationId, orderId) {
            // Create or get conversation for this notification
            const formData = new FormData();
            formData.append('action', 'start_conversation');
            formData.append('notification_id', notificationId);
            
            fetch('api/messages.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.conversation_id) {
                    // Redirect to messages page with conversation selected
                    window.location.href = 'messages.php?conv_id=' + data.conversation_id;
                } else {
                    alert('Error: ' + (data.message || 'Could not open conversation'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error accessing messages');
            });
        }

        // Add fade out animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                to {
                    opacity: 0;
                    transform: translateX(-20px);
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
