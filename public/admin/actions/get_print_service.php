<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'Service ID is required']);
    exit();
}

$serviceId = intval($_GET['id']);

try {
    // Fetch single print service by ID
    $sql = "SELECT id, service_name, description, base_price, paper_types, sizes, stock_quantity, image_path, is_available
            FROM print_services
            WHERE id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $serviceId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Service not found']);
        exit();
    }

    $service = $result->fetch_assoc();

    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $service['id'],
            'service_name' => $service['service_name'],
            'description' => $service['description'],
            'base_price' => $service['base_price'],
            'paper_types' => $service['paper_types'],
            'sizes' => $service['sizes'],
            'stock_quantity' => $service['stock_quantity'],
            'image_path' => $service['image_path'],
            'is_available' => (bool)$service['is_available']
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>
