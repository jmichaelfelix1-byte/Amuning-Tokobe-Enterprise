<?php
// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <div class="logo">
            <a href="../index.php"><img src="../../images/amuninglogo.png" alt="Amuning Logo" class="logo-img"></a>
            <span class="logo-text">Amuning</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage-printing-orders.php" class="nav-link <?php echo ($current_page == 'manage-printing-orders.php') ? 'active' : ''; ?>">
                    <i class="fas fa-print"></i>
                    <span>Manage Printing Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage-booth-orders.php" class="nav-link <?php echo ($current_page == 'manage-booth-orders.php') ? 'active' : ''; ?>">
                    <i class="fas fa-camera"></i>
                    <span>Manage Bookings</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="bookingscalendar.php" class="nav-link <?php echo ($current_page == 'bookingscalendar.php') ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Photobooth Schedule</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="customers.php" class="nav-link <?php echo ($current_page == 'customers.php') ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="adminmessages.php" class="nav-link <?php echo ($current_page == 'adminmessages.php') ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage-photo-services.php" class="nav-link <?php echo ($current_page == 'manage-photo-services.php') ? 'active' : ''; ?>">
                    <i class="fas fa-camera-retro"></i>
                    <span>Manage Photo Services</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="manage-print-services.php" class="nav-link <?php echo ($current_page == 'manage-print-services.php') ? 'active' : ''; ?>">
                    <i class="fas fa-print"></i>
                    <span>Manage Print Services</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="report.php" class="nav-link <?php echo ($current_page == 'report.php') ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Reports</span>
                </a>
            </li>
            <!-- <li class="nav-item">
                <a href="manage-payment.php" class="nav-link <?php echo ($current_page == 'manage-payment.php') ? 'active' : ''; ?>">
                    <i class="fas fa-credit-card"></i>
                    <span>Manage Payments</span>
                </a>
            </li> -->
        </ul>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-details">
                <span class="user-name"><?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Admin User'; ?></span>
                <span class="user-role">Administrator</span>
            </div>
        </div>
        <form method="POST" action="logout.php" class="logout-form">
            <button type="submit" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</aside>

<script>
    // Update unread message badge on sidebar
    function updateMessageBadge() {
        fetch('api/messages.php?action=get_all_conversations')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const conversations = data.conversations;
                    const totalUnread = conversations.reduce((sum, conv) => sum + (conv.unread_count || 0), 0);
                    
                    // Find the Messages link in sidebar
                    const messagesLink = document.querySelector('a[href*="adminmessages.php"]');
                    if (messagesLink) {
                        let badge = messagesLink.querySelector('.unread-badge-sidebar');
                        
                        if (totalUnread > 0) {
                            if (!badge) {
                                badge = document.createElement('span');
                                badge.className = 'unread-badge-sidebar';
                                messagesLink.appendChild(badge);
                            }
                            badge.textContent = totalUnread;
                            badge.style.display = 'inline-block';
                        } else {
                            if (badge) {
                                badge.style.display = 'none';
                            }
                        }
                    }
                }
            })
            .catch(error => console.error('Error updating badge:', error));
    }

    // Update badge on page load
    window.addEventListener('DOMContentLoaded', () => {
        updateMessageBadge();
    });

    // Update badge every 2 seconds
    setInterval(updateMessageBadge, 2000);
</script>
