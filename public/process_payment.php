<?php
header('Content-Type: application/json');

// Set timezone to Manila, Philippines (UTC+8)
date_default_timezone_set('Asia/Manila');

session_start();

// Include necessary files
include 'includes/config.php';
include 'includes/email_config.php';
include 'includes/email_payment.php';

// Get POST data
$item_type = $_POST['item_type'] ?? '';
$item_id = (int)($_POST['item_id'] ?? 0);
$payment_method = $_POST['payment_method'] ?? '';
$reference_number = $_POST['reference_number'] ?? '';
$notes = $_POST['notes'] ?? '';
$is_ajax = isset($_POST['is_ajax_request']) && $_POST['is_ajax_request'] == '1';

// Validate required fields
if (empty($item_type) || empty($item_id) || empty($payment_method)) {
    echo json_encode(['success' => false, 'message' => 'Missing required payment information']);
    exit();
}

// Additional validation for non-cash payments
if ($payment_method !== 'Cash' && empty($reference_number)) {
    echo json_encode(['success' => false, 'message' => 'Reference number is required for this payment method']);
    exit();
}

// Validate session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Determine table and fetch item details
$table_name = ($item_type === 'photo_booking') ? 'photo_bookings' : 'printing_orders';
$id_field = 'id';

try {
    // Fetch item details
    $stmt = $conn->prepare("SELECT * FROM $table_name WHERE $id_field = ? AND status = 'pending'");
    if (!$stmt) {
        throw new Exception('Failed to prepare item query: ' . $conn->error);
    }

    $stmt->bind_param("i", $item_id);
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute item query: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Item not found or already paid');
    }

    $item = $result->fetch_assoc();
    $stmt->close();

    // Calculate amount
    if ($item_type === 'photo_booking') {
        $amount = is_numeric($item['estimated_price']) ? (float)$item['estimated_price'] :
                 (float) str_replace(['₱', ',', ' '], '', $item['estimated_price']);
    } else {
        $amount = (float) $item['price'];
    }

    // Handle file upload (only required for non-cash payments)
    $proof_of_payment_path = null;
    if ($payment_method !== 'Cash') {
        // Check if file was uploaded and is not empty
        if (isset($_FILES['proof_of_payment']) && $_FILES['proof_of_payment']['error'] !== UPLOAD_ERR_NO_FILE && $_FILES['proof_of_payment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = "uploads/proof_of_payment/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = time() . '_' . uniqid() . '_' . basename($_FILES['proof_of_payment']['name']);
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['proof_of_payment']['tmp_name'], $target_file)) {
                $proof_of_payment_path = $target_file;
            } else {
                throw new Exception('Failed to upload proof of payment file');
            }
        } else {
            // For non-cash payments, proof of payment is required
            throw new Exception('Proof of payment file is required for this payment method');
        }
    }
    // For cash payments, $proof_of_payment_path remains null, which is correct

    // Start transaction for data consistency
    $conn->begin_transaction();

    try {
        // Insert into payments table with 'paid' status
        $stmt = $conn->prepare("INSERT INTO payments (user_id, user_email, payment_type, reference_id, amount, payment_method, transaction_number, proof_of_payment, notes, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid', NOW())");
        if (!$stmt) {
            throw new Exception('Failed to prepare payment statement: ' . $conn->error);
        }

        // Set null values for cash payments
        $transaction_number = ($payment_method === 'Cash') ? null : $reference_number;
        $proof_path = ($payment_method === 'Cash') ? null : $proof_of_payment_path;

        $stmt->bind_param("issidssss", $user_id, $user_email, $item_type, $item_id, $amount, $payment_method, $transaction_number, $proof_path, $notes);

        if (!$stmt->execute()) {
            throw new Exception('Failed to execute payment statement: ' . $stmt->error);
        }

        $payment_id = $conn->insert_id;
        $stmt->close();

        // Don't update item status here - let admin validate it first
        // Payment status is now 'paid' - booking/order status remains unchanged until admin validates

        // Commit transaction
        $conn->commit();

        // Get user name from database
        $user_stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $user_data = $user_result->fetch_assoc();
        $user_name = $user_data['full_name'] ?? 'Valued Customer';
        $user_stmt->close();

        // Determine redirect URL
        $redirect_url = ($item_type === 'photo_booking') ? 'user_bookings.php' : 'user_orders.php';
        $success_message = 'Payment submitted successfully! Your payment has been confirmed and is now marked as Paid.';

        echo json_encode([
            'success' => true,
            'message' => $success_message,
            'redirect_url' => $redirect_url
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();

        // Delete uploaded file if transaction failed
        if ($proof_of_payment_path && file_exists($proof_of_payment_path)) {
            @unlink($proof_of_payment_path);
        }

        error_log("Payment processing error: " . $e->getMessage());

        $error_message = 'Failed to submit payment: ' . $e->getMessage();
        echo json_encode(['success' => false, 'message' => $error_message]);
    }

} catch (Exception $e) {
    error_log("Payment validation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
