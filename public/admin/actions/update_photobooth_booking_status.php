<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;
    $reason = $_POST['reason'] ?? '';

    if (!$id || !$status) {
        throw new Exception('Missing required parameters');
    }

    // Valid statuses: pending, confirmed, completed, cancelled
    $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (!in_array($status, $validStatuses)) {
        throw new Exception('Invalid status');
    }

    // Update booking status
    $query = "UPDATE photo_bookings SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $status, $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update booking status');
    }

    // Log the status change
    $logQuery = "INSERT INTO booking_logs (booking_id, old_status, new_status, changed_by, reason, changed_at) 
                 VALUES (?, ?, ?, ?, ?, NOW())";
    $logStmt = $conn->prepare($logQuery);
    $adminName = $_SESSION['user_name'] ?? 'Admin';
    $logStmt->bind_param("issss", $id, $status, $status, $adminName, $reason);
    @$logStmt->execute(); // Non-critical, continue even if logging fails

    echo json_encode([
        'success' => true,
        'message' => 'Booking status updated successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
