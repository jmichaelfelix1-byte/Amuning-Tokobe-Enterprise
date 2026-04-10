<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Invalid customer ID']);
    exit();
}

$customer_id = (int)$_GET['id'];

try {
    // Fetch customer details excluding password
    $stmt = $conn->prepare("SELECT id, email, full_name, mobile, address, user_type, register_as, profile, created_at
                           FROM users
                           WHERE id = ? AND user_type = 'user'");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Customer not found']);
        exit();
    }

    $customer = $result->fetch_assoc();
    $stmt->close();

    echo json_encode(['success' => true, 'data' => $customer]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>
