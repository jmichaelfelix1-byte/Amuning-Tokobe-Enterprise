<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'user') {
    header('Location: signin.php');
    exit();
}

$page_title = 'My Profile | Amuning Tokobe Enterprise';
$additional_css = ['profile.css'];

// Include database config
require_once 'includes/config.php';

// Fetch user's profile data
$user_id = $_SESSION['user_id'];
$user_profile = [];

try {
    $stmt = $conn->prepare("SELECT id, email, full_name, mobile, address, register_as, profile, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user_profile = $result->fetch_assoc();
    }

    $stmt->close();
} catch (Exception $e) {
    error_log("Database error in profile.php: " . $e->getMessage());
    $error_message = "Failed to load profile data.";
}

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        // Handle profile update
        $full_name = trim($_POST['full_name'] ?? '');
        $mobile = trim($_POST['mobile'] ?? '');
        $address = trim($_POST['address'] ?? '');

        // Basic validation
        if (empty($full_name)) {
            $message = "Full name is required.";
            $message_type = "error";
        } elseif (!empty($mobile) || !empty($address)) {
            // If user is trying to update mobile or address, check if they have any bookings
            $hasBookings = false;
            
            try {
                $bookingStmt = $conn->prepare("SELECT id FROM photo_bookings WHERE email = ? LIMIT 1");
                $bookingStmt->bind_param("s", $user_profile['email']);
                $bookingStmt->execute();
                $bookingResult = $bookingStmt->get_result();
                $hasBookings = $bookingResult->num_rows > 0;
                $bookingStmt->close();
            } catch (Exception $e) {
                error_log("Booking check error: " . $e->getMessage());
            }

            if (!$hasBookings) {
                $message = "You can only update your mobile number and address after placing a photo booking order.";
                $message_type = "error";
            } else {
            try {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, mobile = ?, address = ? WHERE id = ?");
                $stmt->bind_param("sssi", $full_name, $mobile, $address, $user_id);

                if ($stmt->execute()) {
                    $message = "Profile updated successfully!";
                    $message_type = "success";
                    // Update session data
                    $_SESSION['user_name'] = $full_name;
                    $_SESSION['full_name'] = $full_name;
                    // Refresh profile data
                    $user_profile['full_name'] = $full_name;
                    $user_profile['mobile'] = $mobile;
                    $user_profile['address'] = $address;
                } else {
                    $message = "Failed to update profile.";
                    $message_type = "error";
                }

                $stmt->close();
            } catch (Exception $e) {
                error_log("Profile update error: " . $e->getMessage());
                $message = "An error occurred while updating your profile.";
                $message_type = "error";
            }
            }
        } else {
            try {
                $stmt = $conn->prepare("UPDATE users SET full_name = ?, mobile = ?, address = ? WHERE id = ?");
                $stmt->bind_param("sssi", $full_name, $mobile, $address, $user_id);

                if ($stmt->execute()) {
                    $message = "Profile updated successfully!";
                    $message_type = "success";
                    // Update session data
                    $_SESSION['user_name'] = $full_name;
                    $_SESSION['full_name'] = $full_name;
                    // Refresh profile data
                    $user_profile['full_name'] = $full_name;
                    $user_profile['mobile'] = $mobile;
                    $user_profile['address'] = $address;
                } else {
                    $message = "Failed to update profile.";
                    $message_type = "error";
                }

                $stmt->close();
            } catch (Exception $e) {
                error_log("Profile update error: " . $e->getMessage());
                $message = "An error occurred while updating your profile.";
                $message_type = "error";
            }
        }
    } elseif ($action === 'change_password') {
        // Handle password change
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Check if user registered with Google (no password to change)
        if ($user_profile['register_as'] === 'google') {
            $message = "Password change is not available for Google accounts.";
            $message_type = "error";
        } elseif (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = "All password fields are required.";
            $message_type = "error";
        } elseif ($new_password !== $confirm_password) {
            $message = "New passwords do not match.";
            $message_type = "error";
        } elseif (strlen($new_password) < 6) {
            $message = "New password must be at least 6 characters long.";
            $message_type = "error";
        } else {
            try {
                // Verify current password
                $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    if (password_verify($current_password, $user['password'])) {
                        // Update password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $update_stmt->bind_param("si", $hashed_password, $user_id);

                        if ($update_stmt->execute()) {
                            $message = "Password changed successfully!";
                            $message_type = "success";
                        } else {
                            $message = "Failed to change password.";
                            $message_type = "error";
                        }
                        $update_stmt->close();
                    } else {
                        $message = "Current password is incorrect.";
                        $message_type = "error";
                    }
                }

                $stmt->close();
            } catch (Exception $e) {
                error_log("Password change error: " . $e->getMessage());
                $message = "An error occurred while changing your password.";
                $message_type = "error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="assets/css/profile.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="banner">
        <div class="banner-content">
            <h1>My Profile</h1>
            <p>Manage your account information and preferences</p>
        </div>
    </section>

    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php if (!empty($user_profile['profile'])): ?>
                    <?php
                    // Check if it's a full URL (Google profile picture) or relative path
                    if (filter_var($user_profile['profile'], FILTER_VALIDATE_URL)) {
                        $profile_src = $user_profile['profile'];
                    } else {
                        // If path doesn't start with 'assets/', add it
                        $profile_src = strpos($user_profile['profile'], 'assets/') === 0 ? $user_profile['profile'] : 'assets/' . $user_profile['profile'];
                    }
                    ?>
                    <img src="<?php echo htmlspecialchars($profile_src); ?>" alt="Profile Picture" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;" onerror="this.onerror=null; this.src='assets/images/default-avatar.jpg';">
                <?php else: ?>
                    <i class="fas fa-user"></i>
                <?php endif; ?>
                <div class="profile-edit-overlay">
                    <i class="fas fa-camera"></i>
                </div>
                <input type="file" id="profile-picture-input" accept="image/*" style="display: none;">
            </div>
            <h2><?php echo htmlspecialchars($user_profile['full_name'] ?? 'User'); ?></h2>
            <p><?php echo htmlspecialchars($user_profile['email']); ?></p>
        </div>

        <div class="profile-forms">
            <!-- Profile Information Form -->
            <form class="form-section" method="POST" action="">
                <input type="hidden" name="action" value="update_profile">

                <h3><i class="fas fa-user-edit"></i> Profile Information</h3>

                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name"
                           value="<?php echo htmlspecialchars($user_profile['full_name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email"
                           value="<?php echo htmlspecialchars($user_profile['email'] ?? ''); ?>"
                           class="readonly-field" readonly>
                    <small style="color: #666; font-size: 12px;">Email cannot be changed</small>
                </div>

                <div class="form-group">
                    <label for="mobile">Mobile Number</label>
                    <input type="tel" id="mobile" name="mobile"
                            id="mobile"
                            name="mobile"
                            maxlength="11"
                            pattern="^09[0-9]{9}$"
                            placeholder="09XXXXXXXXX"
                            value="<?php echo htmlspecialchars($user_profile['mobile'] ?? ''); ?>"
                            required>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" placeholder="Enter your complete address"><?php echo htmlspecialchars($user_profile['address'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Account Type</label>
                    <input type="text" value="<?php echo ucfirst($user_profile['register_as'] ?? 'email'); ?> Account" class="readonly-field" readonly>
                </div>

                <div class="form-group">
                    <label>Member Since</label>
                    <input type="text" value="<?php echo date('M d, Y', strtotime($user_profile['created_at'] ?? '')); ?>" class="readonly-field" readonly>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Update Profile</button>
                </div>
            </form>

            <!-- Password Change Form -->
            <?php if ($user_profile['register_as'] !== 'google'): ?>
            <form class="form-section" method="POST" action="">
                <input type="hidden" name="action" value="change_password">

                <h3><i class="fas fa-lock"></i> Change Password</h3>

                <div class="form-group">
                    <label for="current_password">Current Password *</label>
                    <div class="password-input-group">
                        <input type="password" id="current_password" name="current_password" required>
                        <i class="fas fa-eye password-toggle" data-target="current_password" role="button" aria-label="Show password"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password *</label>
                    <div class="password-input-group">
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                        <i class="fas fa-eye password-toggle" data-target="new_password" role="button" aria-label="Show password"></i>
                    </div>
                    <small style="color: #666; font-size: 12px;">Password must be at least 6 characters long</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password *</label>
                    <div class="password-input-group">
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                        <i class="fas fa-eye password-toggle" data-target="confirm_password" role="button" aria-label="Show password"></i>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">Change Password</button>
                </div>
            </form>
            <?php else: ?>
            <div class="form-section">
                <h3><i class="fas fa-google"></i> Password Management</h3>
                <p style="color: #666; margin: 0;">
                    <i class="fas fa-info-circle"></i>
                    You signed up with Google, so password management is handled through your Google account.
                    If you need to change your password, please update it in your Google account settings.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/script.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;

            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Profile picture edit functionality
        const profileAvatar = document.querySelector('.profile-avatar');
        const profileEditOverlay = document.querySelector('.profile-edit-overlay');
        const profilePictureInput = document.getElementById('profile-picture-input');

        // Show file picker when clicking the edit overlay
        profileEditOverlay.addEventListener('click', function() {
            profilePictureInput.click();
        });

        // Handle file selection
        profilePictureInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid File Type',
                        text: 'Please select a valid image file (JPG, PNG, GIF, or WebP).'
                    });
                    return;
                }

                // Validate file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: 'Please select an image smaller than 5MB.'
                    });
                    return;
                }

                // Show loading state
                Swal.fire({
                    title: 'Uploading...',
                    text: 'Please wait while we upload your profile picture.',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Upload file
                const formData = new FormData();
                formData.append('profile_picture', file);

                fetch('upload_profile_picture.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update profile picture display
                        const img = profileAvatar.querySelector('img');
                        if (img) {
                            img.src = data.image_url + '?t=' + Date.now(); // data.image_url already includes assets/
                        } else {
                            // Replace the icon with an image
                            profileAvatar.innerHTML = `
                                <img src="${data.image_url}?t=${Date.now()}" alt="Profile Picture" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;" onerror="this.onerror=null; this.src='assets/images/default-avatar.jpg';">
                                <div class="profile-edit-overlay">
                                    <i class="fas fa-camera"></i>
                                </div>
                                <input type="file" id="profile-picture-input" accept="image/*" style="display: none;">
                            `;
                            // Re-attach event listeners
                            attachProfileEditListeners();
                        }

                        // Also update header profile picture
                        const headerImg = document.querySelector('.profile-pic');
                        if (headerImg) {
                            headerImg.src = data.image_url + '?t=' + Date.now();
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message,
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Upload Failed',
                            text: data.message
                        });
                    }
                })
                .catch(error => {
                    console.error('Upload error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Failed',
                        text: 'An error occurred while uploading your profile picture. Please try again.'
                    });
                });
            }
        });

        // Function to attach profile edit listeners (for when DOM is updated)
        function attachProfileEditListeners() {
            const newProfileAvatar = document.querySelector('.profile-avatar');
            const newProfileEditOverlay = document.querySelector('.profile-edit-overlay');
            const newProfilePictureInput = document.getElementById('profile-picture-input');

            if (newProfileEditOverlay && newProfilePictureInput) {
                newProfileEditOverlay.addEventListener('click', function() {
                    newProfilePictureInput.click();
                });
            }
        }

        // Show success/error messages using SweetAlert2
        <?php if (!empty($message)): ?>
            Swal.fire({
                icon: '<?php echo $message_type; ?>',
                title: '<?php echo $message_type === 'success' ? 'Success!' : 'Error!'; ?>',
                text: '<?php echo addslashes($message); ?>',
                showConfirmButton: true,
                confirmButtonText: 'Close',
                confirmButtonColor: '#f5276c',
                showCloseButton: true
            });
        <?php endif; ?>
    </script>
</body>
</html>
