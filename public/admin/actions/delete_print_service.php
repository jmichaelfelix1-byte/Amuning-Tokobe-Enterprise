<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$id = $_POST['id'] ?? null;

if (!$id || !is_numeric($id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid service ID']);
    exit();
}

try {
    // Check if service exists
    $checkSql = "SELECT id FROM print_services WHERE id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Print service not found']);
        exit();
    }

    // Delete the service
    $deleteSql = "DELETE FROM print_services WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteSql);
    $deleteStmt->bind_param("i", $id);

    if ($deleteStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Print service deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete print service']);
    }

    $deleteStmt->close();
    $checkStmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>
