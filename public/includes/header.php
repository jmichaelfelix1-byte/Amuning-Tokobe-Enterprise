<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Amuning Tokobe Enterprise - Professional Printing and Photo Services">
    <title><?php echo isset($page_title) ? $page_title : 'Amuning Tokobe Enterprise'; ?></title>
    <link rel="icon" type="image/x-icon" href="../images/amuninglogo.ico">
    <link rel="shortcut icon" href="../images/amuninglogo.ico" type="image/x-icon">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/common.css">
    <?php if(isset($additional_css)) { 
        foreach($additional_css as $css) {
            echo '<link rel="stylesheet" href="assets/css/'.$css.'">';
        }
    } ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <!-- Leaflet Map Library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="assets/js/profile.js"></script>
    
    <!-- Chatbot Styles and Script -->
    <link rel="stylesheet" href="assets/css/chatbot.css">
    <script src="assets/js/chatbot.js"></script>
    
    <!-- Notification Script -->
    <script src="assets/js/notifications.js"></script>
</head>
<body>
    <!-- Header -->
    <section class="header">
        <div class="header-container">
            <a href="index.php" class="logopic">
                <img src="../images/amuninglogo.png" alt="Amuning Logo">
            </a>
            <a href="index.php" class="logo">Amuning Tokobe Enterprise</a>

            <nav class="navbar" id="navbar">
                <?php 
                $current_page = basename($_SERVER['PHP_SELF']);

                // Get user profile picture if logged in
                $user_profile_pic = 'assets/images/default-avatar.jpg';
        if(isset($_SESSION['user_type']) && ($_SESSION['user_type'] == 'user' || $_SESSION['user_type'] == 'admin') && isset($_SESSION['user_id'])) {
                    require_once 'includes/config.php';
                    try {
                        $stmt = $conn->prepare("SELECT profile FROM users WHERE id = ?");
                        $stmt->bind_param("i", $_SESSION['user_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0) {
                            $user_data = $result->fetch_assoc();
                            if (!empty($user_data['profile'])) {
                                // Check if it's a full URL (Google profile picture) or relative path
                                if (filter_var($user_data['profile'], FILTER_VALIDATE_URL)) {
                                    $user_profile_pic = $user_data['profile'];
                                } else {
                                    // If path doesn't start with 'assets/', add it
                                    $user_profile_pic = strpos($user_data['profile'], 'assets/') === 0 ? $user_data['profile'] : 'assets/' . $user_data['profile'];
                                }
                            }
                        }
                        $stmt->close();
                    } catch (Exception $e) {
                        // Use default avatar if query fails
                        error_log("Header profile query error: " . $e->getMessage());
                    }
                    //$conn->close();
                }
                ?>
                <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" id="homeLink">Home</a>
                <a href="index.php#gal" id="galleryLink">Gallery</a>
                <a href="about.php" class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>">About Us</a>
                
                <div class="dropdown">
                    <a href="javascript:void(0);" class="dropbtn <?php echo (in_array($current_page, ['print.php', 'photo.php'])) ? 'active' : ''; ?>" id="dropdownToggle">
                        <span>Services</span>
                        <span class="dropdown-icon">⏷</span>
                    </a>
                    <div class="dropdown-content" id="dropitDown">
                        <a href="print.php" class="<?php echo ($current_page == 'print.php') ? 'active' : ''; ?>">Print Services</a>
                        <a href="photo.php" class="<?php echo ($current_page == 'photo.php') ? 'active' : ''; ?>">Photo Services</a>
                    </div>
                </div>
                
                <a href="contact.php" class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>">Contact Us</a>
                <?php if(isset($_SESSION['user_type']) && ($_SESSION['user_type'] == 'user' || $_SESSION['user_type'] == 'admin')): ?>
                    <!-- Notification Bell -->
                    <a href="inbox.php" class="notification-bell" id="notificationBell" title="View Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                    </a>
            <div class="dropdown">
                <a href="javascript:void(0);" class="dropbtn" id="profileToggle">
                    <img src="<?php echo $user_profile_pic; ?>" alt="Profile" class="profile-pic" onerror="this.onerror=null; this.src='assets/images/default-avatar.jpg';">
                    <span class="dropdown-icon">⏷</span>
                </a>
                <div class="dropdown-content" id="profileDropdown">
                    <?php if($_SESSION['user_type'] == 'admin'): ?>
                        <!-- Admin-specific links -->
                        <a href="admin/dashboard.php" class="dropdown-item">
                            <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                        </a>
                        <div class="dropdown-divider"></div>
                    <?php else: ?>
                        <!-- Regular user links -->
                        <!-- Common logout for all users -->
                    <a href="profile.php" class="dropdown-item">
                        <i class="fa-solid fa-user"></i> Profile
                    </a>
                    
                    <a href="messages.php" class="dropdown-item">
                        <i class="fa-solid fa-envelope"></i> Messages
                    </a>
                    
                      <a href="inbox.php" class="dropdown-item">
                        <i class="fa-solid fa-comments"></i> Notifications
                    </a>
                    <a href="user_orders.php" class="dropdown-item">
                        <i class="fa-solid fa-bag-shopping"></i> Manage Printing Orders
                    </a>
                    
                     <a href="user_bookings.php" class="dropdown-item">
                        <i class="fa-solid fa-calendar"></i> Manage Bookings
                    </a>
                        <div class="dropdown-divider"></div>
                    <?php endif; ?>
                    
                   
                     
                    <a href="logout.php" class="dropdown-item" onclick="clearSessionOnLogout()">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            <?php else: ?>
            <a href="signin.php" class="sign <?php echo ($current_page == 'signin.php') ? 'active' : ''; ?>">Sign In</a>
            <?php endif; ?>
            </nav>

            <div id="menu-btn" class="fas fa-bars"></div>
        </div>
    </section>

    <script>
        // Function to trigger profile picture editing
        function triggerProfilePictureEdit() {
            // If on profile page, trigger the edit overlay
            if (window.location.pathname.includes('profile.php')) {
                const editOverlay = document.querySelector('.profile-edit-overlay');
                if (editOverlay) {
                    editOverlay.click();
                }
            } else {
                // Redirect to profile page with anchor
                window.location.href = 'profile.php#change-picture';
            }
        }

        // Function to clear session-related data on logout
        function clearSessionOnLogout() {
            // Clear the booking policy popup flags to show them again on next session
            // Photo service flags
            sessionStorage.removeItem('photoPolicyPopupDisabled');
            sessionStorage.removeItem('photoPolicySessionTime');
            // Printing service flags
            sessionStorage.removeItem('printingPolicyPopupDisabled');
            sessionStorage.removeItem('printingPolicySessionTime');
        }
    </script>

    <!-- Main Content Wrapper -->
    <main class="main-content">