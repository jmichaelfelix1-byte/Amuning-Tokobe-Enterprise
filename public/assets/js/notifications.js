// Notification system initialization
document.addEventListener('DOMContentLoaded', function() {
    // Only initialize if user is logged in (notification bell exists)
    const notificationBell = document.getElementById('notificationBell');
    if (!notificationBell) return;

    // Fetch unread notification count on page load
    updateNotificationCount();

    // Poll for new notifications every 30 seconds
    setInterval(updateNotificationCount, 30000);

    // Also update count when user returns to the tab
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            updateNotificationCount();
        }
    });
});

/**
 * Update the notification badge count
 */
function updateNotificationCount() {
    fetch('api/get_notifications.php?action=get_unread_count')
        .then(response => response.json())
        .then(data => {
            const badge = document.getElementById('notificationBadge');
            const count = data.unread_count || 0;

            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        })
        .catch(error => console.error('Error updating notification count:', error));
}

/**
 * Show a toast notification popup
 */
function showNotificationToast(notification) {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('notification-toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'notification-toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 400px;
        `;
        document.body.appendChild(toastContainer);
    }

    // Create toast element
    const toast = document.createElement('div');
    const statusLabels = {
        'pending': 'Pending',
        'processing': 'Processing',
        'paid': 'Paid',
        'completed': 'Completed',
        'cancelled': 'Cancelled',
        'declined': 'Declined'
    };

    const oldStatus = statusLabels[notification.old_status] || notification.old_status;
    const newStatus = statusLabels[notification.new_status] || notification.new_status;

    toast.innerHTML = `
        <div style="
            background: white;
            border-left: 4px solid #f5276c;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            animation: slideInRight 0.3s ease-out;
        ">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                <strong style="color: #1e293b; font-size: 1rem;">${notification.title}</strong>
                <button onclick="this.closest('div').parentElement.remove()" style="
                    background: none;
                    border: none;
                    color: #64748b;
                    cursor: pointer;
                    font-size: 1.2rem;
                    padding: 0;
                    width: 24px;
                    height: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                ">×</button>
            </div>
            <p style="color: #475569; margin: 8px 0; font-size: 0.95rem;">${notification.message}</p>
            ${notification.old_status && notification.new_status ? `
                <div style="
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    margin-top: 10px;
                    padding-top: 10px;
                    border-top: 1px solid #e2e8f0;
                ">
                    <span style="
                        background: #fee2e2;
                        color: #991b1b;
                        padding: 4px 8px;
                        border-radius: 4px;
                        font-size: 0.85rem;
                        font-weight: 600;
                    ">${oldStatus}</span>
                    <i class="fas fa-arrow-right" style="color: #cbd5e1; font-size: 0.8rem;"></i>
                    <span style="
                        background: #dcfce7;
                        color: #15803d;
                        padding: 4px 8px;
                        border-radius: 4px;
                        font-size: 0.85rem;
                        font-weight: 600;
                    ">${newStatus}</span>
                </div>
            ` : ''}
        </div>
    `;

    toastContainer.appendChild(toast);

    // Auto-remove after 6 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 6000);
}

// Add animation styles
if (!document.getElementById('notification-animations')) {
    const style = document.createElement('style');
    style.id = 'notification-animations';
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Check for new notifications (real-time polling)
 * This should be called periodically or triggered by events
 */
function checkForNewNotifications(sinceTime = null) {
    const params = new URLSearchParams({
        action: 'get_new_notifications'
    });

    if (sinceTime) {
        params.append('since', sinceTime);
    }

    fetch('api/get_notifications.php?' + params.toString())
        .then(response => response.json())
        .then(data => {
            if (data.notifications && data.notifications.length > 0) {
                // Show toast for each new notification
                data.notifications.forEach(notification => {
                    showNotificationToast(notification);
                });

                // Update badge count
                updateNotificationCount();
            }
        })
        .catch(error => console.error('Error checking for new notifications:', error));
}

// Optionally enable aggressive polling for new notifications (every 10 seconds)
// Uncomment the line below to enable real-time notifications
// setInterval(() => checkForNewNotifications(new Date().toISOString()), 10000);
