<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_POST['id']) || !is_numeric($_POST['id']) || !isset($_POST['is_available'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
    exit();
}

$service_id = (int)$_POST['id'];
$is_available = (int)$_POST['is_available'];

if ($is_available !== 0 && $is_available !== 1) {
    echo json_encode(['success' => false, 'message' => 'Invalid availability value']);
    exit();
}

try {
    // Get current state before update
    $check_stmt = $conn->prepare("SELECT is_available FROM print_services WHERE id = ?");
    $check_stmt->bind_param("i", $service_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Service not found']);
        exit();
    }
    
    $check_row = $check_result->fetch_assoc();
    $old_availability = $check_row['is_available'];
    $check_stmt->close();

    $stmt = $conn->prepare("UPDATE print_services SET is_available = ? WHERE id = ?");
    $stmt->bind_param("ii", $is_available, $service_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Log to service_history
            $old_values = json_encode(['is_available' => $old_availability]);
            $new_values = json_encode(['is_available' => $is_available]);
            $admin_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin';
            
            $history_stmt = $conn->prepare("INSERT INTO service_history (service_id, service_type, action_type, old_values, new_values, changed_by) VALUES (?, ?, ?, ?, ?, ?)");
            $action = 'availability_changed';
            $history_stmt->bind_param("isssss", $service_id, $service_type, $action, $old_values, $new_values, $admin_name);
            $service_type = 'print';
            $history_stmt->execute();
            $history_stmt->close();
            
            echo json_encode(['success' => true, 'message' => 'Service availability updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Service not found or no changes made']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update service availability']);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
