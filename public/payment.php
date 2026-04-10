<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone to Manila
date_default_timezone_set('Asia/Manila');

session_start();
include 'includes/config.php';

// Set content type for potential AJAX requests early
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Please login to continue']);
        exit();
    } else {
        header('Location: login.php');
        exit();
    }
}

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($booking_id <= 0 && $order_id <= 0) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode(['success' => false, 'message' => 'Invalid booking or order ID']);
        exit();
    } else {
        header('Location: index.php');
        exit();
    }
}

$item = null;
$item_type = '';
$table_name = '';
$reference_id = 0;

if ($booking_id > 0) {
    // Fetch booking details
    $stmt = $conn->prepare("SELECT * FROM photo_bookings WHERE id = ?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['success' => false, 'message' => 'Booking not found']);
            exit();
        } else {
            header('Location: index.php');
            exit();
        }
    }

    $item = $result->fetch_assoc();
    $item_type = 'photo_booking';
    $table_name = 'photo_bookings';
    $reference_id = $booking_id;
    $stmt->close();
} elseif ($order_id > 0) {
    // Fetch order details
    $stmt = $conn->prepare("SELECT * FROM printing_orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit();
        } else {
            header('Location: index.php');
            exit();
        }
    }

    $item = $result->fetch_assoc();
    $item_type = 'printing_order';
    $table_name = 'printing_orders';
    $reference_id = $order_id;
    $stmt->close();
}

// Handle payment confirmation with proof of payment upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $is_ajax = (isset($_POST['is_ajax_request']) && $_POST['is_ajax_request'] === '1') || 
               (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    
    // Ensure JSON content type for AJAX responses
    if ($is_ajax) {
        header('Content-Type: application/json');
        ob_start();
    }
    
    // Validate required fields
    $payment_method = $_POST['payment_method'] ?? '';
    $reference_number = $_POST['reference_number'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Validate required fields
    if (empty($payment_method)) {
        $error_message = 'Please fill in all required fields.';
        if ($is_ajax) {
            ob_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $error_message]);
            exit();
        } else {
            $redirect_url = ($item_type === 'photo_booking') ? 'user_bookings.php' : 'user_orders.php';
            header("Location: {$redirect_url}?payment_error=1&message=" . urlencode($error_message));
            exit();
        }
    }

    // Only require reference number and proof of payment for non-cash payments
    if ($payment_method !== 'Cash') {
        if (empty($reference_number)) {
            $error_message = 'Please fill in all required fields.';
            if ($is_ajax) {
                ob_clean();
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $error_message]);
                exit();
            } else {
                $redirect_url = ($item_type === 'photo_booking') ? 'user_bookings.php' : 'user_orders.php';
                header("Location: {$redirect_url}?payment_error=1&message=" . urlencode($error_message));
                exit();
            }
        }
    }

    // Handle file upload
    $proof_of_payment_path = null;
    $upload_error = false;
    
    // Only require proof of payment upload for non-cash payments
    if ($payment_method !== 'Cash') {
        if (isset($_FILES['proof_of_payment']) && $_FILES['proof_of_payment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/proof_of_payment/';
        if (!is_dir($upload_dir)) {
            // Create directory with proper permissions
            if (!mkdir($upload_dir, 0755, true)) {
                $error_message = 'Failed to create upload directory. Please contact administrator.';
                $upload_error = true;
            }
        }

        if (!$upload_error) {
            $file_extension = strtolower(pathinfo($_FILES['proof_of_payment']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];

            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = 'payment_' . $reference_id . '_' . time() . '.' . $file_extension;
                $proof_of_payment_path = $upload_dir . $new_filename;

                if (!move_uploaded_file($_FILES['proof_of_payment']['tmp_name'], $proof_of_payment_path)) {
                    $error_message = 'Failed to upload proof of payment file. Please try again.';
                    $upload_error = true;
                }
            } else {
                $error_message = 'Invalid file type. Only JPG, PNG, and PDF files are allowed.';
                $upload_error = true;
            }
        }
    } else {
        $error_message = 'Please upload proof of payment.';
        $upload_error = true;
    }
    }

    if ($upload_error) {
        if ($is_ajax) {
            ob_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $error_message]);
            exit();
        } else {
            $redirect_url = ($item_type === 'photo_booking') ? 'user_bookings.php' : 'user_orders.php';
            header("Location: {$redirect_url}?payment_error=1&message=" . urlencode($error_message));
            exit();
        }
    }
}

// If we reach here, display the payment form
$page_title = 'Payment - ' . ($item_type === 'photo_booking' ? 'Photo Booking' : 'Printing Order');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="assets/css/common.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .error-message {
            color: #dc3545;
            font-size: 14px;
            margin-top: 5px;
        }
        .required-field::after {
            content: " *";
            color: #dc3545;
        }
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d1edff;
            color: #0c5460;
            border: 1px solid #b8daff;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="payment-container" style="max-width: 800px; margin: 50px auto; padding: 0 20px;">
        <div class="payment-card" style="background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(245, 39, 108, 0.1); border: 1px solid rgba(245, 39, 108, 0.2); overflow: hidden;">
            <div class="payment-header" style="background: linear-gradient(135deg, #F5276C 0%, #FF6939 100%); color: white; padding: 30px 20px; text-align: center;">
                <h1 style="margin: 0; font-size: 28px; font-weight: 700;"> Complete Your Payment</h1>
                <p style="margin: 10px 0 0; opacity: 0.9;">Secure payment for your <?php echo $item_type === 'photo_booking' ? 'photo booking' : 'printing order'; ?></p>
            </div>

            <div class="payment-body" style="padding: 30px 20px;">
                <!-- Display any existing error messages -->
                <?php if (isset($_GET['payment_error']) && isset($_GET['message'])): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars(urldecode($_GET['message'])); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['payment_success']) && isset($_GET['message'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars(urldecode($_GET['message'])); ?>
                    </div>
                <?php endif; ?>

                <div class="booking-summary" style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 30px; border-left: 4px solid #F5276C;">
                    <h3 style="margin-top: 0; color: #F5276C; font-size: 18px;"> <?php echo $item_type === 'photo_booking' ? 'Booking' : 'Order'; ?> Summary</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <?php if ($item_type === 'photo_booking'): ?>
                            <p><strong>Booking ID:</strong> #<?php echo $item['id']; ?></p>
                            <p><strong>Event Type:</strong> <?php echo htmlspecialchars($item['event_type']); ?></p>
                            <p><strong>Product:</strong> <?php echo htmlspecialchars($item['product']); ?></p>
                            <p><strong>Package:</strong> <?php echo htmlspecialchars($item['package_type']); ?></p>
                            <p><strong>Event Date:</strong> <?php echo date('F d, Y', strtotime($item['event_date'])); ?></p>
                            <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($item['time_of_service'])); ?></p>
                            <p><strong>Venue:</strong> <?php echo htmlspecialchars($item['venue']); ?></p>
                            <p><strong>Price:</strong> ₱<?php 
                                $price = (float) str_replace(['₱', ',', ' '], '', $item['estimated_price'] ?? '0');
                                echo number_format($price, 2);
                            ?></p>
                            <p><strong>Travel Fee:</strong> ₱<?php 
                                $travel_fee = (float) str_replace(['₱', ',', ' '], '', $item['travel_fee'] ?? '0');
                                echo number_format($travel_fee, 2);
                            ?></p>
                        <?php else: ?>
                            <p><strong>Order ID:</strong> #<?php echo str_pad($item['id'], 4, '0', STR_PAD_LEFT); ?></p>
                            <p><strong>Service:</strong> <?php echo htmlspecialchars($item['service']); ?></p>
                            <p><strong>Size:</strong> <?php echo htmlspecialchars($item['size']); ?></p>
                            <p><strong>Paper Type:</strong> <?php echo htmlspecialchars($item['paper_type']); ?></p>
                            <p><strong>Quantity:</strong> <?php echo htmlspecialchars($item['quantity']); ?></p>
                            <p><strong>Order Date:</strong> <?php echo date('F d, Y', strtotime($item['order_date'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="payment-amount" style="text-align: center; margin: 30px 0;">
                    <h2 style="color: #F5276C; margin-bottom: 10px;">Total Amount</h2>
                    <div style="font-size: 36px; font-weight: 700; color: #333;">
                        ₱<?php
                            if ($item_type === 'photo_booking') {
                                $price = (float) str_replace(['₱', ',', ' '], '', $item['estimated_price'] ?? '0');
                                $travel_fee = (float) str_replace(['₱', ',', ' '], '', $item['travel_fee'] ?? '0');
                                $amount = $price + $travel_fee;
                            } else {
                                $amount = (float) $item['price'];
                            }
                            echo number_format($amount, 2);
                        ?>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data" id="paymentForm" style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <input type="hidden" name="is_ajax_request" value="0" id="is_ajax_field">
                    <input type="hidden" name="item_type" value="<?php echo htmlspecialchars($item_type); ?>">
                    <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($reference_id); ?>">
                    <h3 style="color: #F5276C; margin-bottom: 20px;"> Payment Method</h3>

                    <!-- PayMongo GCash Payment Section -->
                    <div id="paymongo-gcash-section" style="margin-bottom: 15px; padding: 15px; background: #e3f2fd; border: 1px solid #90caf9; border-radius: 6px;">
                        <p style="margin: 0 0 15px 0; color: #1976d2; font-weight: 500;">
                            <i class="fas fa-info-circle"></i> Click the button below to be redirected to the PayMongo payment page to complete your GCash payment securely.
                        </p>
                        <button type="button" id="paymongo_checkout_btn" style="background: linear-gradient(135deg, #F5276C 0%, #FF6939 100%); color: white; border: none; padding: 12px 30px; border-radius: 6px; font-size: 15px; font-weight: 600; cursor: pointer; width: 100%; transition: all 0.3s ease;">
                            <i class="fas fa-credit-card"></i> Proceed to GCash Payment
                        </button>
                    </div>
                </form>

                <div class="payment-notice" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 20px 0;">
                    <p style="margin: 0; color: #856404; font-weight: 500;">
                        Important: After submitting your payment details and proof of payment, we will verify the transaction within 24 hours. You will receive a confirmation email once your payment is approved.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <script>
    $(document).ready(function() {
        console.log('=== Payment Form Initialized ===');
        
        // Amount from PHP
        const amount = <?php echo $amount; ?>;
        const itemId = <?php echo $reference_id; ?>;
        const itemType = '<?php echo $item_type; ?>';
        
        console.log('Amount:', amount, 'ItemId:', itemId, 'ItemType:', itemType);
        console.log('PayMongo button found:', $('#paymongo_checkout_btn').length);

        // PayMongo Checkout Handler
        $('#paymongo_checkout_btn').on('click', function(e) {
            console.log('=== PayMongo Button Clicked ===');
            e.preventDefault();
            e.stopPropagation();
            
            const btn = $(this);
            btn.prop('disabled', true);
            btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...');

            console.log('Making AJAX request to api/create_paymongo_checkout.php');
            console.log('Data:', { item_id: itemId, item_type: itemType, amount: amount });

            // Show loading SweetAlert
            Swal.fire({
                title: 'Initializing Payment...',
                text: 'Redirecting to PayMongo payment gateway',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Call PayMongo checkout creation endpoint
            $.ajax({
                url: 'api/create_paymongo_checkout.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    item_id: itemId,
                    item_type: itemType,
                    amount: amount
                },
                timeout: 15000,
                success: function(response) {
                    console.log('=== PayMongo Response ===');
                    console.log('Full response:', response);
                    
                    if (response.success && response.checkout_url) {
                        console.log('✓ Checkout successful');
                        console.log('Checkout URL:', response.checkout_url);
                        console.log('Redirecting user...');
                        
                        // Close alert and redirect
                        Swal.close();
                        window.location.href = response.checkout_url;
                    } else {
                        console.error('✗ Error: Missing checkout_url');
                        console.log('Response success:', response.success);
                        console.log('Response message:', response.message);
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Payment Initialization Failed',
                            text: response.message || 'Failed to create checkout. Please try again.',
                            confirmButtonColor: '#f5276c'
                        });
                        btn.prop('disabled', false);
                        btn.html('<i class="fas fa-credit-card"></i> Proceed to GCash Payment');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('=== AJAX Error ===');
                    console.error('Status:', status);
                    console.error('Error:', error);
                    console.log('Response status:', xhr.status);
                    console.log('Response text:', xhr.responseText);
                    
                    let errorMsg = 'Failed to initialize payment';
                    
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        if (errorData.message) {
                            errorMsg = errorData.message;
                        }
                    } catch(e) {
                        if (xhr.responseText) {
                            errorMsg = xhr.responseText.substring(0, 200);
                        }
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Payment Error',
                        text: errorMsg,
                        confirmButtonColor: '#f5276c'
                    });
                    btn.prop('disabled', false);
                    btn.html('<i class="fas fa-credit-card"></i> Proceed to GCash Payment');
                }
            });
        });

        // Form submission for non-GCash payments
        $('#paymentForm').on('submit', function(e) {
            e.preventDefault();
            // With only GCash available, form submission shouldn't occur
            // Users click the PayMongo button instead
        });
    });
    </script>
</body>
</html>